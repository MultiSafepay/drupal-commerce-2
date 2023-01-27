<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay_payments\Helpers\GatewayStandardMethodsHelper;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-Site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_ideal",
 *   label = "MultiSafepay (iDEAL)",
 *   display_label = "iDEAL",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" =
 *     "Drupal\commerce_multisafepay_payments\PluginForm\Ideal\IdealForm",
 *   },
 * )
 */
class Ideal extends GatewayStandardMethodsHelper implements
    SupportsRefundsInterface
{

  /**
   * Build the unique iDeal configuration form.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Added condition
   */
    public function buildConfigurationForm(
        array $form,
        FormStateInterface $form_state
    ) {
      // Make the Condition.
        $this->mspConditionHelper->orderCurrencyCondition('Euro');

      // Build default form.
        $form = parent::buildConfigurationForm($form, $form_state);

      // Make a message.
        $form['details'] = $this->mspConditionHelper->orderConditionMessage();

        return $form;
    }
}
