<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\PluginForm\StandardPayment;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_multisafepay_payments\Helpers\ApiHelper;
use Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay_payments\Helpers\OrderHelper;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StandardPaymentForm.
 */
class StandardPaymentForm extends BasePaymentOffsiteForm
{

  /**
   * MultiSafepay order helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\OrderHelperOrderHelper
   */
    protected $mspOrderHelper;

  /**
   * Gateway helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper
   */
    protected $mspGatewayHelper;

  /**
   * Api helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\ApiHelper
   */
    protected $mspApiHelper;

  /**
   * MSP Client.
   *
   * @var \Drupal\commerce_multisafepay_payments\API\Client
   */
    protected $mspClient;

  /**
   * Log storage.
   *
   * @var object
   */
    protected $logStorage;

  /**
   * FormStandardHelper constructor.
   */
    public function __construct()
    {
        $this->mspOrderHelper = new OrderHelper();
        $this->mspGatewayHelper = new GatewayHelper();
        $this->mspApiHelper = new ApiHelper();
        $this->mspClient = new Client();
        $this->logStorage = \Drupal::entityTypeManager()->getStorage(
            'commerce_log'
        );
    }

  /**
   * Build the checkout form configuration.
   *
   * @param mixed $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Redirect to MultiSafepay page
   */
    public function buildConfigurationForm(
        $form,
        FormStateInterface $form_state
    ) {
      // We will put the form builder of the Payment process of the customer here.
        parent::buildConfigurationForm($form, $form_state);

        $payment = $this->entity;

      // Get order.
        $order = $payment->getOrder();

      // Set the mode of the gateway.
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

      // Create the order data.
        $data = $this->mspOrderHelper->createOrderData($form, $payment);

      // Set the API settings.
        $this->mspApiHelper->setApiSettings($this->mspClient, $mode);

      // Post the data.
        $this->mspClient->orders->post($data);

      // Place in log storage.
        $this->logStorage->generate($order, 'order_payment_link')->setParams(
            [
            'payment_link' => $this->mspClient->orders->getPaymentLink(),
            ]
        )->save();

      // Redirect to the offsite (MSP)
        return $this->buildRedirectForm(
            $form,
            $form_state,
            $this->mspClient->orders->getPaymentLink(),
            [],
            'get'
        );
    }
}
