<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Helpers;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_multisafepay_payments\Exceptions\ExceptionHelper;
use Drupal\commerce_multisafepay_payments\Log\Logger;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\state_machine\Plugin\Field\FieldType\StateItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GatewayStandardMethodsHelper.
 */
class GatewayStandardMethodsHelper extends OffsitePaymentGatewayBase implements
    SupportsNotificationsInterface
{

  /**
   * MultiSafepay Api Helper.
   *
   * @var ApiHelper
   */
    protected $mspApiHelper;

  /**
   * MultiSafePay Gateway Helper.
   *
   * @var GatewayHelper
   */
    protected $mspGatewayHelper;

  /**
   * MultiSafepay Order Helper.
   *
   * @var \Drupal\commerce_order\Entity\OrderHelper
   */
    protected $mspOrderHelper;

  /**
   * MultiSafepay Condition Helper.
   *
   * @var ConditionHelper
   */
    protected $mspConditionHelper;

  /**
   * ExceptionHelper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Exceptions\ExceptionHelper
   */
    protected $exceptionHelper;

  /**
   * The PaymentStorage System.
   *
   * @var object
   */
    protected $paymentStorage;

    private $logger;

  /**
   * GatewayStandardMethodsHelper constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param int $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   Payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   Payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTypeManagerInterface $entity_type_manager,
        PaymentTypeManager $payment_type_manager,
        PaymentMethodTypeManager $payment_method_type_manager,
        TimeInterface $time
    ) {
        parent::__construct(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $entity_type_manager,
            $payment_type_manager,
            $payment_method_type_manager,
            $time
        );
        $this->mspApiHelper = new ApiHelper();
        $this->mspGatewayHelper = new GatewayHelper();
        $this->mspOrderHelper = new OrderHelper();
        $this->mspConditionHelper = new ConditionHelper();
        $this->exceptionHelper = new ExceptionHelper();
        $this->paymentStorage = $entity_type_manager->getStorage(
            'commerce_payment'
        );
        $this->logger = new Logger();
    }

  /**
   * Set the order in the next workflow step.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param object $mspOrder
   *   MultiSafepay data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
    public function transitionOrder(OrderInterface $order, $mspOrder)
    {
        $this->logger->debug('preparing transition order');

        // Load order from database to prevent cached order state.
        $orderStorage = $this->entityTypeManager->getStorage('commerce_order');
        $orderStorage->resetCache([$order->id()]);
        $order = $orderStorage->load($order->id());

        /** @var StateItem $stateItem */
        $stateItem = $order->get('state')->first();

        if (OrderHelper::isStatusCancelled($mspOrder->status)) {
            // Move the order to cancel
            $this->logger->debug('Move order to cancelled');
            $transition = $stateItem->getTransitions()['cancel'];
            $stateItem->applyTransition($transition);
        }

        $this->logger->debug('Getting current value of the state');
        $currentState = $stateItem->getValue();


        if (OrderHelper::isStatusCompleted($mspOrder->status)) {
            $this->logger->debug('MultiSafepay status is considered completed');
            if ($currentState['value'] === 'canceled') {
                $this->logger->debug('Re-opening order. Moving order to default state plus one');
                // Re-open the order, move the order back to draft and move it to the next default status
                $this->mspOrderHelper->logMsp($order, 'order_reopened');
                $stateItem->applyDefaultValue();
                $stateItem->applyTransition(current($stateItem->getTransitions()));
            }

            $currentState = $stateItem->getValue();

            // Check if the order has reached the final to last step, even after the re-opening of the order.
            // If so, don't update the order
            if ($currentState['value'] === 'fulfillment') {
                $this->logger->debug('Order state is currently fulfillment, don\'t update the status');
                return;
            }

            //Move the order to the next default status
            $this->logger->debug('Moving order state to the next state');
            $stateItem->applyTransition(current($stateItem->getTransitions()));
        }

        $this->logger->debug('Saving the order state');
        $order->save();
    }

  /**
   * Get the MultiSafepay order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface  $order
   *   The order.
   * @param int $transactionId
   *   Order id.
   *
   * @return mixed|\Symfony\Component\HttpFoundation\Response
   *   Response given to MSP MCP or get order if gateway is from MSP
   *
   * @throws MissingDataException
   */
    public function getMspOrder(OrderInterface $order, $transactionId)
    {
        $client = new Client();

      // Get current gateway & Check if it is a MSP gateway.
        $gateway = $order->get('payment_gateway')->first()->get('entity')->getValue();
        if (!$this->mspGatewayHelper->isMspGateway($gateway->getPluginId())) {
            return new Response("Non MSP order");
        }

      // Set the mode of the gateway.
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

      // Set the API settings.
        $this->mspApiHelper->setApiSettings($client, $mode);

        return $client->orders->get('orders', $transactionId);
    }

  /**
   * Set the behavior when you get a notification back form the API.
   *
   * @param \Symfony\Component\HttpFoundation\Request  $request
   *   Url get data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The Response given to MSP MCP
   *
   * @throws MissingDataException
   */
    public function onNotify(Request $request)
    {
        // Get the order id & check if there's no transaction id.
        $transactionId = $request->get('transactionid');

        if (empty($transactionId)) {
            $this->logger->debug('Tried to start notification process, but no transaction ID was found');
            return new Response('Error 500', 500);
        }

        $this->logger->debug('Starting notification process for transaction ID #' . $transactionId);

        // Get the order & Check if order is not null.
        $this->logger->debug('Finding order for transaction ID #' . $transactionId);
        $order = Order::load($transactionId);

        if (is_null($order)) {
            $this->logger->debug('Could not find order for transaction ID #' . $transactionId);
            return new Response("Order does not exist");
        }

        $this->logger->debug('Found order for transaction ID #' . $transactionId);

        // Get payment gateway.
        $gateway = $order->get('payment_gateway')->first()->get('entity')->getValue();
        $this->logger->debug('Finding gateway for transaction ID #' . $transactionId);

        // Get the MSP order & check if payment details has been found.
        $mspOrder = $this->getMspOrder($order, $transactionId);
        $this->logger->debug('Finding MultiSafepay order for transaction ID #' . $transactionId);

        $paymentDetails = $mspOrder->payment_details ?? null;
        if (is_null($paymentDetails)) {
            $this->logger->debug('Could not find MultiSafepay order for transaction ID #' . $transactionId);
            return new Response("No payment details found");
        }

        // Set order in the next step.
        $this->transitionOrder($order, $mspOrder);

        // Get the payment.
        $this->logger->debug('Create payment line for order');
        $payment = $this->createPayment($order, $mspOrder);

        // Get the MSP status & check if order has changed state.
        $this->logger->debug('Set state for payment line');
        $state = OrderHelper::getPaymentState($mspOrder->status);
        if (!is_null($state)) {
            $payment->setState($state)->save();
        }

        // Check if status is uncleared.
        if ($mspOrder->status === OrderHelper::MSP_UNCLEARED) {
            $this->mspOrderHelper->logMsp($order, 'order_uncleared');
        }

        // Get the msp Gateway & Check if paid with other payment method then registered.
        $mspGateway = $mspOrder->payment_details->type;
        $this->mspGatewayHelper->logDifferentGateway(
            $mspGateway,
            $gateway,
            $order
        );

        return new Response('OK');
    }

  /**
   * Create and/or get payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface  $order
   *   The order.
   * @param object $mspOrder
   *   MultiSafepay order data.
   *
   * @return object
   *   Load the payment
   *
   * @throws \Drupal\Core\Entity\EntityStorageException|MissingDataException
   */
    public function createPayment(OrderInterface $order, $mspOrder)
    {
      // Set amount.
        $mspAmount = $mspOrder->amount / 100;

      // Get payment gateway.
        $gateway = $order->get('payment_gateway')->first()->get('entity')->getValue();

      // If payment already exist, else create a new payment.
        if (is_null(
            $this->paymentStorage->loadByRemoteId($mspOrder->transaction_id)
        )
        ) {
          // Check if the gateway if Banktransfer.
            if ($gateway->getPluginId() === 'msp_banktrans') {
                $this->mspOrderHelper->logMsp(
                    $order,
                    'order_banktransfer_started'
                );
            }

            $this->paymentStorage->create(
                [
                'state'           => 'new',
                'amount'          => new Price(
                    (string) $mspAmount,
                    $mspOrder->currency
                ),
                'payment_gateway' => $this->entityId,
                'order_id'        => $order->id(),
                'remote_id'       => $mspOrder->transaction_id,
                'remote_state'    => $mspOrder->status,
                ]
            )->save();
        }

      // Add payment capture to log.
        if ($mspOrder->status === OrderHelper::MSP_COMPLETED) {
            $this->mspOrderHelper->logMsp($order, 'order_payment_capture');
        }

      // Save the new record.
        return $this->paymentStorage->loadByRemoteId($mspOrder->transaction_id);
    }

  /**
   * Refund a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_price\Price|null $amount
   *   The amount to refund.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
    public function refundPayment(
        PaymentInterface $payment,
        Price $amount = null
    ) {
      // Get all data.
        $order = $payment->getOrder();
        $orderId = $orderNumber = $order->getOrderNumber() ?: $payment->getOrderId();
        $currency = $amount->getCurrencyCode();

      // If not specified, refund the entire amount.
        $amount = $amount ?: $payment->getAmount();

      // Check if $payment amount is =< then refund $amount.
        $this->assertRefundAmount($payment, $amount);

      // Set all data.
        $data = [
        "currency"    => $currency,
        "amount"      => $amount->getNumber() * 100,
        "description" => "Refund: {$orderId}",
        ];

      // Set the mode of the gateway.
        $mode = $this->mspGatewayHelper->getGatewayMode($payment->getOrder());

      // Make API request to send refund.
        $client = new Client();
        $mspApiHelper = new ApiHelper();
        $mspApiHelper->setApiSettings($client, $mode);

        $client->orders->post($data, "orders/{$orderId}/refunds");

      // If refund is processed and success is false.
        if ($client->orders->success === false) {
            $this->exceptionHelper->paymentGatewayException("Refund declined");
        }

      // Set new refunded amount.
        $oldRefundedAmount = $payment->getRefundedAmount();
        $newRefundedAmount = $oldRefundedAmount->add($amount);
        $payment->setRefundedAmount($newRefundedAmount);
        $payment->save();

      // Choose what log will be used.
        if ($newRefundedAmount->lessThan($payment->getAmount())) {
            $logfile = 'order_partial_refund';
        } else {
            $logfile = 'order_full_refund';
        }

      // Place log in order.
        $this->mspOrderHelper->logMsp($payment->getOrder(), $logfile);
    }

  /**
   * Check if we can get an order from the the Order number.
   *
   * @param int $transactionId
   *   Transaction id.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   Get the order. If it is not found, return null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
    public function getOrderFromOrderNumber($transactionId)
    {
        $orders = $this->entityTypeManager->getStorage('commerce_order')->loadByProperties(['order_number' => $transactionId]);
        if (!$orders) {
            return null;
        }

        $order = reset($orders);
        if (!$order) {
            return null;
        }

        return $order;
    }
}
