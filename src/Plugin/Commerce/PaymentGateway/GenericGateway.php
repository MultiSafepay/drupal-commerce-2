<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay_payments\Helpers\GatewayStandardMethodsHelper;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-Site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_generic_gateway",
 *   label = "MultiSafepay (Generic Gateway)",
 *   display_label = "Generic Gateway",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" =
 *     "Drupal\commerce_multisafepay_payments\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */
class GenericGateway extends GatewayStandardMethodsHelper implements
    SupportsRefundsInterface
{

  /**
   * Default configuration.
   *
   * @return array|string[]
   *   The settings
   */
    public function defaultConfiguration()
    {
        return [
        'generic_gateway_code' => '',
        ] + parent::defaultConfiguration();
    }

  /**
   * Build the settings form for the generic gateway.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Added condition
   */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['generic_gateway_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Gateway code'),
        '#description' => $this->t('The gateway code.'),
        '#default_value' => $this->configuration['generic_gateway_code'],
        '#required' => true,
        ];

        return $form;
    }

  /**
   * Process the settings form for the generic gateway.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        $values = $form_state->getValue($form['#parents']);
        $this->configuration['generic_gateway_code'] = $values['generic_gateway_code'];
    }
}
