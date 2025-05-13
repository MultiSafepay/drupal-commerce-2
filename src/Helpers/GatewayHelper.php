<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Helpers;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Class GatewayHelper.
 */
class GatewayHelper
{

    use StringTranslationTrait;

    public const MSP_GATEWAYS = [
    'gateways' => [
      // Redirects.
      'msp_directbanktransfer'       => [
        'code'          => 'DBRTP',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_belfius'                  => [
        'code'          => 'BELFIUS',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_applepay'                  => [
        'code'          => 'APPLEPAY',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_mistercash'               => [
        'code'          => 'MISTERCASH',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_amex'                     => [
        'code'          => 'AMEX',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_billink'                     => [
          'code'          => 'BILLINK',
          'type'          => 'redirect',
          'shopping_cart' => true,
      ],
      'msp_dirdeb'                   => [
        'code'          => 'DIRDEB',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_dotpay'                   => [
        'code'          => 'DOTPAY',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_eps'                      => [
        'code'          => 'EPS',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_ferbuy'                   => [
        'code'          => 'AMEX',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_giropay'                  => [
        'code'          => 'GIROPAY',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_idealqr'                  => [
        'code'          => 'IDEALQR',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_visa'                     => [
        'code'          => 'VISA',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_maestro'                  => [
        'code'          => 'MAESTRO',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_mastercard'               => [
        'code'          => 'MASTERCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_paysafecard'              => [
        'code'          => 'PSAFECARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_directbank'               => [
        'code'          => 'DIRECTBANK',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_trustpay'                 => [
        'code'          => 'TRUSTPAY',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_wallet'                   => [
        'code'          => '',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_afterpay'                 => [
        'code'          => 'AFTERPAY',
        'type'          => 'redirect',
        'shopping_cart' => true,
      ],
      'msp_klarna'                   => [
        'code'          => 'KLARNA',
        'type'          => 'redirect',
        'shopping_cart' => true,
      ],
      'msp_payafterdelivery'         => [
        'code'          => 'PAYAFTER',
        'type'          => 'redirect',
        'shopping_cart' => true,
      ],
      'msp_santander'                => [
        'code'          => 'SANTANDER',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_generic_gateway'          => [
        'code'          => 'GENERIC',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],


      // Direct.
      'msp_bizum'                  => [
          'code'          => 'BIZUM',
          'type'          => 'direct',
          'shopping_cart' => false,
      ],
      'msp_cbc'                      => [
        'code'          => 'CBC',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_ideal'                    => [
        'code'          => 'IDEAL',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_kbc'                      => [
        'code'          => 'KBC',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_trustly'                  => [
        'code'          => 'TRUSTLY',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_paypal'                   => [
        'code'          => 'PAYPAL',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_alipayplus'               => [
        'code'          => 'ALIPAYPLUS',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_banktrans'                => [
        'code'          => 'BANKTRANS',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],
      'msp_einvoice'                 => [
        'code'          => 'EINVOICE',
        'type'          => 'direct',
        'shopping_cart' => true,
      ],
      'msp_inghome'                  => [
        'code'          => 'INGHOME',
        'type'          => 'direct',
        'shopping_cart' => false,
      ],

      // Gift cards.
      'msp_babygiftcard'             => [
        'code'          => 'BABYGIFTCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_beautyandwellness'        => [
        'code'          => 'BEAUTYANDWELLNESS',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_boekenbon'                => [
        'code'          => 'BOEKENBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_erotiekbon'               => [
        'code'          => 'EROTIEKBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_fashioncheque'            => [
        'code'          => 'FASHIONCHEQUE',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_fashiongiftcard'          => [
        'code'          => 'FASHIONGIFTCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_fietsenbon'               => [
        'code'          => 'FIETSENBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_gezondheidsbon'           => [
        'code'          => 'GEZONDHEIDSBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_givacard'                 => [
        'code'          => 'GIVACARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_good4fun'                 => [
        'code'          => 'GOOD4FUN',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_goodcard'                 => [
        'code'          => 'GOODCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_nationaletuinbon'         => [
        'code'          => 'NATIONALETUINBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_nationaleverwencadeaubon' => [
        'code'          => 'NATIONALEVERWENCADEAUBON',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_parfumcadeaukaart'        => [
        'code'          => 'PARFUMCADEAUKAART',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_podiumcadeaukaart'        => [
        'code'          => 'PODIUM',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_sportenfit'               => [
        'code'          => 'SPORTENFIT',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_vvvcadeaukaart'           => [
        'code'          => 'VVVGIFTCRD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_wellnessgiftcard'         => [
        'code'          => 'WELLNESSGIFTCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_wijncadeau'               => [
        'code'          => 'WIJNCADEAU',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_winkelcheque'             => [
        'code'          => 'WINKELCHEQUE',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_yourgift'                 => [
        'code'          => 'YOURGIFT',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
      'msp_webshopgiftcard'          => [
        'code'          => 'WEBSHOPGIFTCARD',
        'type'          => 'redirect',
        'shopping_cart' => false,
      ],
    ],
    ];

  /**
   * MultiSafepay order helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Helpers\OrderHelper
   */
    protected $mspOrderHelper;

  /**
   * GatewayHelper constructor.
   */
    public function __construct()
    {
        $this->mspOrderHelper = new OrderHelper();
    }

  /**
   * Check if shopping cart is TRUE or FALSE.
   *
   * @param string $gatewayId
   *   The gateway is.
   *
   * @return bool
   *   Is shopping cart allowed
   */
    public static function isShoppingCartAllowed($gatewayId)
    {
        if (self::MSP_GATEWAYS['gateways'][$gatewayId]['shopping_cart']) {
            return true;
        }
        return false;
    }

  /**
   * Check if the $gateway is part of MSP.
   *
   * @param string $gateway
   *   Gateway.
   *
   * @return bool
   *   True / False.
   */
    public function isMspGateway($gateway)
    {
        return isset(self::MSP_GATEWAYS['gateways'][$gateway]);
    }

  /**
   * Check if the given gateway has changed to another gateway.
   *
   * @param string $mspGateway
   *   Old gateway.
   * @param \Drupal\commerce_payment\Entity\PaymentGateway $gateway
   *   New gateway.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
    public function logDifferentGateway(
        $mspGateway,
        PaymentGateway $gateway,
        OrderInterface $order
    ) {
        if ($mspGateway
        !== self::MSP_GATEWAYS['gateways'][$gateway->getPluginId()]['code']
        ) {
            $this->mspOrderHelper->logMsp($order, 'order_new_gateway');
        }
    }

  /**
   * Get the gateway mode from the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface  $order
   *   Order.
   *
   * @return string
   *   Get Gateway mode (test / n/a / live)
   *
   * @throws MissingDataException
   */
    public function getGatewayMode(OrderInterface $order)
    {
        return $order->get('payment_gateway')->first()->get('entity')->getValue()->get(
            'configuration'
        )['mode'];
    }
}
