<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay_payments\Helpers\GatewayStandardMethodsHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_payafterdelivery",
 *   label = "MultiSafepay (Pay After Delivery)",
 *   display_label = "Pay After Delivery",
 *   forms = {
 *     "offsite-payment" =
 *     "Drupal\commerce_multisafepay_payments\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */
class PayAfterDelivery extends GatewayStandardMethodsHelper
{

  /**
   * Add data to config form.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Added conditions
   */
    public function buildConfigurationForm(
        array $form,
        FormStateInterface $form_state
    ) {
      // Make the Condition.
        $orderTotalCondition = $this->mspConditionHelper->orderTotalCondition(
            '<=',
            '300',
            'EUR'
        );
        $this->mspConditionHelper->orderCurrencyCondition('Euro');

      // Set the values.
        $form_state->setValues(
            array_merge($orderTotalCondition, $form_state->getValues())
        );

      // Build default form.
        $form = parent::buildConfigurationForm($form, $form_state);

      // Make a message.
        $form['details'] = $this->mspConditionHelper->orderConditionMessage();

        return $form;
    }
}
