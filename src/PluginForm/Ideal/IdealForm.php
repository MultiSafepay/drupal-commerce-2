<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\PluginForm\Ideal;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_multisafepay_payments\Helpers\ApiHelper;
use Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay_payments\Helpers\OrderHelper;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IdealForm.
 */
class IdealForm extends BasePaymentOffsiteForm
{

  /**
   * Order helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\OrderHelper
   */
    protected $mspOrderHelper;

  /**
   * Gateway Helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper
   */
    protected $mspGatewayHelper;

  /**
   * Api Helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\ApiHelper
   */
    protected $mspApiHelper;

  /**
   * Client.
   *
   * @var \Drupal\commerce_multisafepay_payments\API\Client
   */
    protected $mspClient;

  /**
   * Log Storage.
   *
   * @var object
   */
    protected $logStorage;

  /**
   * IdealForm constructor.
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
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Create HTML for the select bank page on the website
   */
    public function buildConfigurationForm(
        array $form,
        FormStateInterface $form_state
    ) {
        $form = parent::buildConfigurationForm($form, $form_state);

        $payment = $this->entity;

      // Get order.
        $order = $payment->getOrder();

      // Set the mode of the gateway.
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

        $this->mspApiHelper->setApiSettings($this->mspClient, $mode);

        $issuers = $this->mspClient->issuers->get();

        $issuerArray = [];

        $issuerArray['none'] = t('Choose your bank...');

        foreach ($issuers as $issuer) {
            $issuerArray[$issuer->code] = $issuer->description;
        }

        $form['issuer'] = [
        '#type'          => 'select',
        '#title'         => t('Select your bank'),
        '#options'       => $issuerArray,
        "#default_value" => 'none',
        ];

        $form['submit'] = [
        '#type'  => 'submit',
        '#value' => t('Submit'),
        ];

        $form['cancel'] = [
        '#type'       => 'html_tag',
        '#tag'        => 'a',
        '#attributes' => [
        "href" => $form['#cancel_url'],
        ],
        '#value'      => t('Go back'),
        ];

        return $form;
    }

  /**
   * Build the validation for the checkout form configuration.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   return to MultiSafepay checkout page
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
    public function validateConfigurationForm(
        array &$form,
        FormStateInterface $form_state
    ) {

      // Get issuer.
        $issuerId = \Drupal::request()->request->get(
            'payment_process'
        )['offsite_payment']['issuer'];

        $gatewayInfo = [
        "issuer_id" => $issuerId,
        ];

      // We will put the form builder of the Payment process of the customer here.
        $payment = $this->entity;

      // Create the order data.
        $data = $this->mspOrderHelper->createOrderData(
            $form,
            $payment,
            $gatewayInfo
        );

      // Get order.
        $order = $payment->getOrder();

      // Set the mode of the gateway.
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

      // Set the API settings.
        $this->mspApiHelper->setApiSettings($this->mspClient, $mode);

      // Post the data.
        $this->mspClient->orders->post($data);

      // Place in log storage.
        $this->logStorage->generate($payment->getOrder(), 'order_payment_link')
        ->setParams(
            [
            'payment_link' => $this->mspClient->orders->getPaymentLink(),
            ]
        )
        ->save();

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
