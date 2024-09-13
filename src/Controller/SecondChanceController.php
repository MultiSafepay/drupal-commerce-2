<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Controller;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_multisafepay_payments\Helpers\ApiHelper;
use Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay_payments\Helpers\OrderHelper;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SecondChanceController extends ControllerBase
{
    private $gatewayHelper;
    private $client;
    private $apiHelper;

    public function __construct()
    {
        $this->gatewayHelper = new GatewayHelper();
        $this->client = new Client();
        $this->apiHelper = new ApiHelper();
    }

  /**
   * Returns a render-able array for a test page.
   * @throws EntityStorageException
   */
    public function content(Request $request): RedirectResponse
    {
        $redirect_url = new RedirectResponse($this->buildReturnUrl($request)->toString());
        $supportSentence = 'Please, can you create a new order? Otherwise, contact the support team.';

        if (!$this->isAccessValid($request)) {
            $this->messenger()->addError($this->t(
                'Invalid Transaction ID. @supportSentence',
                ['@supportSentence' => $supportSentence]
            ));
            return $redirect_url;
        }

        $transactionId = $request->get('transactionid');
        $order = Order::load($transactionId);
        if (is_null($order)) {
            $this->messenger()->addError($this->t(
                'Invalid Order. @supportSentence',
                ['@supportSentence' => $supportSentence]
            ));
            return $redirect_url;
        }

        $mode = $this->gatewayHelper->getGatewayMode($order);
        $this->apiHelper->setApiSettings($this->client, $mode);
        $multiSafepayOrder = $this->client->orders->get('orders', $transactionId);

        if (in_array((string) $multiSafepayOrder->status, [OrderHelper::MSP_COMPLETED, OrderHelper::MSP_INIT], true)) {
            // If the order is completed or initialized, we can move the order to the first workflow state
            $getState = $order->getState();
            if (!is_null($getState)) {
                $getState->applyDefaultValue();
            }
            $order->save();
            return $redirect_url;
        }

          // Get the gateway used for the order
          $gatewayOrder = !empty($multiSafepayOrder->payment_details) ? ' using ' . $multiSafepayOrder->payment_details->type : '';
          // If the gateway is not found, we will not show the gateway in the message
          $message = $this->t(
              'There was a problem with the transaction@gateway. @supportSentence',
              ['@gateway' => $gatewayOrder, '@supportSentence' => $supportSentence]
          );
          $this->messenger()->addError($message);
          return $redirect_url;
    }

    protected function buildReturnUrl(Request $request): Url
    {
        return Url::fromRoute('commerce_payment.checkout.return', [
            'commerce_order' => $request->get('transactionid'),
            'step' => 'payment',
        ], ['absolute' => true]);
    }

    private function isAccessValid(Request $request): bool
    {
        //Get and check if the transactionID is valid
        $transactionId = $request->get('transactionid');
        if (!$transactionId) {
            return false;
        }

        $order = Order::load($transactionId);
        if (!$order) {
            return false;
        }

        if ($order->get('payment_gateway')->isEmpty()) {
            return false;
        }

        /** @var PaymentGateway $paymentMethod */
        $paymentMethod = $order->get('payment_gateway')->entity;

        return $this->gatewayHelper->isMspGateway($paymentMethod->getPluginId());
    }
}
