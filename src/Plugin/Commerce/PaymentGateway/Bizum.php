<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay_payments\Helpers\GatewayStandardMethodsHelper;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_bizum",
 *   label = "MultiSafepay (Bizum)",
 *   display_label = "Bizum",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" =
 *     "Drupal\commerce_multisafepay_payments\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */
class Bizum extends GatewayStandardMethodsHelper implements
    SupportsRefundsInterface
{

}
