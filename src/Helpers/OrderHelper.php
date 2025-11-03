<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Helpers;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\FlatRatePerItem;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\Url;

/**
 * Class OrderHelper.
 */
class OrderHelper
{

    use StringTranslationTrait;

    public const MSP_COMPLETED = "completed";

    public const MSP_INIT = "initialized";

    public const MSP_UNCLEARED = "uncleared";

    public const MSP_VOID = "void";

    public const MSP_DECLINED = "declined";

    public const MSP_EXPIRED = "expired";

    public const MSP_CANCELLED = "cancelled";

    public const MSP_REFUNDED = "refunded";

    public const MSP_PARTIAL_REFUNDED = "partial_refunded";

    public const AUTHORIZATION = "authorization";

    // Drupal Commerce Only.
    public const PARTIALLY_REFUNDED = "partially_refunded";

    public const AUTHORIZATION_EXPIRED = "authorization_expired";

    public const AUTHORIZATION_VOIDED = "authorization_voided";

    public const NEW = "new";

    /**
     * Set discount so we can use it anywhere.
     *
     * @var array
     */
    public $discount = ['type' => 'none', 'percentage' => 0, 'amount' => 0];

    /**
     * MultiSafepay API helper.
     *
     * @var \Drupal\commerce_multisafepay_payments\Helpers\ApiHelper
     */
    protected $mspApiHelper;

    /**
     * Drupal order log class.
     *
     * @var \Drupal\commerce_log\LogStorage
     */
    protected $logStorage;

    /**
     * Drupal module handler.
     *
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * OrderHelper constructor.
     */
    public function __construct()
    {
        $this->mspApiHelper = new ApiHelper();
        $this->logStorage = \Drupal::entityTypeManager()->getStorage(
            'commerce_log'
        );
        $this->moduleHandler = \Drupal::moduleHandler();
    }

    /**
     * Get and return the current status.
     *
     * @param string $state
     *   transaction state got from MultiSafepay.
     *
     * @return string|null
     *   The given status that should be used
     */
    public static function getPaymentState($state)
    {
        switch ($state) {
            case self::MSP_COMPLETED:
                return self::MSP_COMPLETED;

                break;
            case self::MSP_INIT:
                return self::NEW;

                break;
            case self::MSP_UNCLEARED:
                return self::AUTHORIZATION;

                break;
            case self::MSP_VOID:
                return self::AUTHORIZATION_VOIDED;

                break;
            case self::MSP_DECLINED:
                return self::AUTHORIZATION_VOIDED;

                break;
            case self::MSP_EXPIRED:
                return self::AUTHORIZATION_EXPIRED;

                break;
            case self::MSP_CANCELLED:
                return self::AUTHORIZATION_VOIDED;

                break;
            case self::MSP_REFUNDED:
                return self::MSP_REFUNDED;

                break;
            case self::MSP_PARTIAL_REFUNDED:
                return self::PARTIALLY_REFUNDED;

                break;
            default:
                return null;
        }
    }

    /**
     * Check if order has been completed.
     *
     * @param string $status
     *   MultiSafepay transaction status.
     *
     * @return bool
     *   TRUE / FALSE
     */
    public static function isStatusCompleted($status)
    {
        return in_array(
            $status,
            [
                OrderHelper::MSP_COMPLETED,
            ]
        );
    }

    /** Check if the order is cancelled */
    public static function isStatusCancelled($status)
    {
        return in_array(
            $status,
            [
                self::MSP_CANCELLED,
                self::MSP_EXPIRED,
                self::MSP_DECLINED,
                self::MSP_VOID,

            ]
        );
    }

    /**
     * Create the whole order data array.
     *
     * @param mixed $form
     *   Form details.
     * @param object $payment
     *   Payment object.
     * @param array $gatewayInfo
     *   Additional gateway info.
     *
     * @return array
     *   Create order data array
     *
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function createOrderData($form, $payment, array $gatewayInfo = [])
    {
        // Get URLS.
        $redirectUrl = $this->buildReturnUrl()->toString();
        $notification = $this->getNotifyUrl($payment)->toString();
        $cancelUrl = $form['#cancel_url'];

        /** @var \Drupal\commerce_order\Entity\Order $order */
        $order = $payment->getOrder();
        $orderId = $orderNumber = $order->getOrderNumber() ?: $payment->getOrderId();
        $currency = $payment->getAmount()->getCurrencyCode();
        $amount = $payment->getAmount()->getNumber();
        // Convert to cents.
        $amount = $amount * 100;

        // Redirect type and the gateway code.
        $gatewayCode = $this->getGatewayHelperOptions($order)['code'];

        // Check if gateway is the generic one; in that case retrieve the gateway code.
        if ($gatewayCode === 'GENERIC') {
            $gatewayCode = $payment->getPaymentGateway()->getPlugin()->getConfiguration()['generic_gateway_code'];
        }

        // Check if gateway is ideal and has no issuer id, if so: make redirect.
        if ($gatewayCode === 'IDEAL') {
            $type = 'direct';
        } else {
            $type = $this->getGatewayHelperOptions($order)['type'];
        }

        // Set the checkout and shopping cart data.
        $checkoutData = null;
        $shoppingCartData = null;
        $items = $this->getItemsData($order);

        // Check if the gateway uses the checkout & shopping data.
        if (GatewayHelper::isShoppingCartAllowed(
            $payment->getPaymentGateway()->getPluginId()
        )
        ) {
            $checkoutData = $this->getCheckoutData($order);
            $shoppingCartData = $this->getShoppingCartData($order);
        }

        $drupalVersion = \Drupal::VERSION;
        $commerceVersion = \Drupal::service('extension.list.module')->getExtensionInfo('commerce')['version'] ?? 'unknown';
        $pluginVersion = \Drupal::service('extension.list.module')->getExtensionInfo('commerce_multisafepay_payments')['version'] ?? 'unknown';

        $orderData = [
            "type" => $type,
            "gateway" => $gatewayCode,
            "order_id" => $orderId,
            "currency" => $currency,
            "amount" => $amount,
            "items" => $items,
            "description" => $orderId,
            "seconds_active" => \Drupal::config(
                'commerce_multisafepay_payments.settings'
            )->getRawData()['seconds_active'],
            "manual" => "false",
            "payment_options" => [
                "notification_url" => $notification,
                "redirect_url" => $redirectUrl,
                "cancel_url" => $cancelUrl,
                "close_window" => "TRUE",
            ],
            "customer" => $this->getCustomerData($order),
            "delivery" => $this->getShippingData($order),
            "shopping_cart" => $shoppingCartData,
            "checkout_options" => $checkoutData,
            "gateway_info" => $gatewayInfo,
            "plugin" => [
                "shop" => "Drupal: {$drupalVersion}, Commerce: {$commerceVersion}",
                "shop_version" => "Drupal: {$drupalVersion}, Commerce: {$commerceVersion}",
                "plugin_version" => $pluginVersion,
                "partner" => "MultiSafepay",
            ],
        ];

        /* Hook commerce_multisafepay_payments_multisafepay_orderdata_PAYMENT_METHOD_alter */
        $this->moduleHandler->alter(
            [
                'multisafepay_orderdata_' . strtolower($gatewayCode),
                'multisafepay_orderdata'
            ],
            $orderData,
            $payment,
            $gatewayInfo
        );

        return $orderData;
    }

    /**
     * Get the notification URL.
     *
     * @param object $payment
     *   Payment object.
     *
     * @return string
     *   Url
     */
    public function getNotifyUrl($payment)
    {
        return Url::fromRoute(
            'commerce_payment.notify',
            [
                'commerce_payment_gateway' => $payment->getPaymentGatewayId(),
            ],
            [
                'absolute' => true,
            ]
        );
    }

    /**
     * Get MSP gateway options form the order.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     *
     * @return mixed
     *   the gateway options
     *
     * @throws MissingDataException
     */
    public function getGatewayHelperOptions(OrderInterface $order)
    {

        // Get the gateway id.
        $gatewayId = $order->get('payment_gateway')->first()->get('entity')->getValue()->getPluginId();

        // Get the msp gateway options.
        $gatewayOptions = GatewayHelper::MSP_GATEWAYS['gateways'][$gatewayId];

        // Return the options.
        return $gatewayOptions;
    }

    /**
     * Create HTML element to show on the MSP checkout page.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   The order.
     *
     * @return string
     *   Get HTML data of all arrays
     */
    public function getItemsData(OrderInterface $order)
    {
        $html = "<ul>\n";

        // Generate a list of all ordered items.
        foreach ($order->getItems() as $item) {
            $product = $item->getPurchasedEntity();

            $quantity = (string)floatval($item->getQuantity());

            $html .= "<li>{$quantity}&times; : {$product->getTitle()}</li>\n";
        }

        $html .= "</ul>";
        return $html;
    }

    /**
     * Gathers the checkout data.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   The order.
     *
     * @return array
     *   Create tax tables array
     */
    public function getCheckoutData(OrderInterface $order)
    {
        // Get the order Items.
        $orderItems = $order->getItems();
        // Get one order item.
        $orderItem = $orderItems[0];

        // Check if the item has adjustments.
        if (!$orderItem->getAdjustments()) {
            // If True -> Return array.
            return [
                "tax_tables" =>
                    [
                        "default" =>
                            [
                                "shipping_taxed" => null,
                                "rate" => 0,
                            ],
                        'alternate' =>
                            [
                                [
                                    'standalone' => false,
                                    'name' => 'shipping',
                                    'rules' => [
                                        [
                                            'rate' => 0,
                                        ],
                                    ],
                                ],
                            ],
                    ],
            ];
        }

        // Make empty values.
        $checkoutData = [
            "tax_tables" => [
                'default' => [
                    "shipping_taxed" => null,
                    "rate" => 0,
                ],
                "alternate" => [],
            ],
        ];

        // TODO: WHEN DRUPAL FIXES THE FIXED DISCOUNT BUG CHANGE FLATRATE url(https://www.drupal.org/project/commerce/issues/2980713)
        // Check if there is a discount.
        if ($this->getAdjustment($orderItem, 'promotion')) {
            $adjustment = $this->getAdjustment($orderItem, 'promotion');
            // Check if the discount code uses percentage or flat rate.
            $this->discount = $adjustment->getPercentage()
                ?
                $this->discount = [
                    'type' => 'percentage',
                    'percentage' => $this->discount['percentage']
                        + $adjustment->getPercentage(),
                    'amount' => $this->discount['amount'],
                ]
                :
                $this->discount = [
                    'type' => 'flat',
                    'amount' => $this->discount['amount'] + abs(
                        $adjustment->getAmount()->getNumber()
                    ),
                    'percentage' => $this->discount['percentage'],
                ];

            // Set Discount table.
            $discountTable = [
                "standalone" => false,
                "name" => 'promotion',
                "rules" => [["rate" => 0]],
            ];

            // Make the taxtable for promotional items.
            array_push(
                $checkoutData['tax_tables']['alternate'],
                $discountTable
            );
        }
        // Check if there is Taxes.
        if ($this->getAdjustment($orderItem, 'tax')) {
            $adjustment = $this->getAdjustment($orderItem, 'tax');
            // Get VAT from first item.
            $getVAT = $adjustment->getPercentage();

            // Set the VAT of the item (default)
            $checkoutData['tax_tables']['default'] = [
                "shipping_taxed" => null,
                "rate" => $getVAT,
            ];

            // Set Rate.
            if (isset($checkoutData['tax_tables']['alternate'][0])) {
                $checkoutData['tax_tables']['alternate'][0]["rules"][0]["rate"]
                    = $getVAT;
            }
        }

        // Push a BTW0 alternate to the tax_tables.
        array_push(
            $checkoutData['tax_tables']['alternate'],
            [
                'standalone' => false,
                'name' => 'BTW0',
                'rules' => [
                    [
                        'rate' => 0,
                    ],
                ],
            ]
        );

        // Return the VAT data to use it in customer data.
        return $checkoutData;
    }

    /**
     * Get the adjustment type of an order item.
     *
     * @param \Drupal\commerce_order\Entity\OrderItem $orderItem
     *   Order item.
     * @param string $type
     *   Adjustment type.
     *
     * @return bool|object
     *   The adjustment data
     */
    public function getAdjustment(OrderItem $orderItem, $type)
    {
        // Loop through all adjustments.
        foreach ($orderItem->getAdjustments() as $key => $adjustment) {
            // Get the given adjustment $type.
            if ($adjustment->getType() === $type) {
                return $orderItem->getAdjustments()[$key];
            }
        }
        return false;
    }

    /**
     * Gathers the shopping cart data.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     *
     * @return array
     *   The shopping cart Array
     */
    public function getShoppingCartData(OrderInterface $order)
    {
        // Set $totalOrderedProducts for using Shipping Flat Rate Per Item.
        $totalOrderedProducts = 0;

        // Get the order Items.
        $orderItems = $order->getItems();
        // Create the array where we will put items in.
        $shoppingCartData = [
            "items" => [],
        ];

        $discountRow = [
            "name" => $this->t('Discount'),
            "description" => '',
            "quantity" => 1,
            "unit_price" => 0,
            "merchant_item_id" => 'msp-discount',
            "tax_table_selector" => "promotion",
        ];

        // Go through all items and put them in a array for the API.
        foreach ($orderItems as $orderItem) {
            // Add quantity total to $totalOrderedProducts.
            $totalOrderedProducts += $orderItem->getQuantity();

            $taxAdjustment = $this->getAdjustment($orderItem, 'tax');

            // So we can get values that doesnt have methods.
            $product = $orderItem->getPurchasedEntity();

            // Check if weight is enabled.
            if ($product->hasField('weight')
                && !empty($product->get('weight')->getValue())
            ) {
                $productWeight = $product->get('weight')->getValue()[0];
            } else {
                $productWeight = null;
            }

            // Check if price is incl. or excl. tax.
            $taxIncluded = false;
            // Check if tax adjustment exist.
            if ($taxAdjustment instanceof Adjustment) {
                $taxIncluded = $taxAdjustment->isIncluded();
            }

            $originalProductPrice = $product->getPrice()->getNumber();
            // Check if tax is included, if not: get price of product.
            if ($taxIncluded) {
                // Get price excl. tax.
                $discountRow['tax_table_selector'] = "BTW0";
                $productPrice = $product->getPrice()->getNumber() / (1
                        + $taxAdjustment->getPercentage());
            } else {
                // Get value of the item and convert it to cents.
                $productPrice = $product->getPrice()->getNumber();
            }
            // Get Quantity.
            $productQuantity = (string)floatval($orderItem->getQuantity());

            // Make an array of the item and fill it with the data.
            $item = [
                "name" => $product->getTitle(),
                "description" => '',
                "unit_price" => $productPrice,
                "quantity" => $productQuantity,
                "merchant_item_id" => $product->getProductId(),
                "tax_table_selector" => "default",
                "weight" => [
                    "unit" => $productWeight['unit'],
                    "value" => $productWeight['number'],
                ],
            ];

            // Push the item to the items array.
            array_push($shoppingCartData['items'], $item);

            // Check if there is a discount. if so Take it off.
            if ($this->discount['amount'] > 0.00
                || $this->discount['percentage'] > 0.00
            ) {
                // Check if its a percentage or Flat discount coupon and set their Algorithms.
                if ($this->discount['type'] === "percentage") {
                    $discountRow["unit_price"] += -(($originalProductPrice
                            * $productQuantity) * $this->discount['percentage']
                        - $this->discount['amount']);
                } else {
                    $discountRow["unit_price"] = -$this->discount['amount'];
                }
            }
        }

        // If there is a discount, push all discounts to the shopping cart data.
        if ($this->discount['amount'] > 0.00
            || $this->discount['percentage'] > 0.00
        ) {
            array_push($shoppingCartData['items'], $discountRow);
        }

        // Make Shipping record for shopping cart.
        $shipmentCartData = $this->getShippingCartData(
            $order,
            $totalOrderedProducts
        );

        // If no shipment cart data, exclude shipping from cart array.
        if (!empty($shipmentCartData)) {
            $shoppingCartData['items'][] = $shipmentCartData;
        }

        // Return the items array to use it in customer data.
        return $shoppingCartData;
    }

    /**
     * Return shipping item cart data.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     * @param int $quantity
     *   Quantity.
     *
     * @return array
     *   Shipment data
     */
    public function getShippingCartData(OrderInterface $order, $quantity = 1)
    {
        // If no shipping on the order. don't add shipping to cart.
        if (!$this->orderHasShipments($order)) {
            return [];
        }

        $shipments = $order->get('shipments')->referencedEntities();
        $firstShipment = reset($shipments);

        // Get plugin.
        $shippingPlugin = $firstShipment
            ->getShippingMethod()
            ->getPlugin();

        // Get configuration.
        $shippingMethod = $shippingPlugin->getConfiguration();

        // If shipping method is flat rate per item.
        if ($shippingPlugin instanceof FlatRatePerItem) {
            $shippingMethod['rate_amount']['number']
                = $shippingMethod['rate_amount']['number'] * $quantity;
        }

        // Make shipping amount object.
        $shippingAmount = new Price(
            (string)$shippingMethod['rate_amount']['number'],
            $shippingMethod['rate_amount']['currency_code']
        );

        // If price is higher than 0 / free.
        if ($shippingAmount->getNumber() > 0) {
            // Make an array of the item and fill it with the data.
            return [
                "name" => $shippingMethod['rate_label'],
                "description" => '',
                "unit_price" => $shippingAmount->getNumber(),
                "quantity" => 1,
                "merchant_item_id" => 'msp-shipping',
                "tax_table_selector" => "BTW0",
                "weight" => [
                    "unit" => 0,
                    "value" => 'KG',
                ],
            ];
        }

        return [];
    }

    /**
     * Check if the order has shipments.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     *
     * @return bool
     *   TRUE / FALSE
     */
    public function orderHasShipments(OrderInterface $order)
    {
        return $order->hasField('shipments')
            && !$order->get('shipments')->isEmpty();
    }

    /**
     * Get customer data and check if shipping must be added or not.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     * @param bool $shipping
     *   If you want shipment data.
     *
     * @return mixed
     *   Customer data
     *
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getCustomerData(OrderInterface $order, $shipping = false)
    {

        $shipmentData = $this->addAdditionalProfileData($order, $shipping);
        $profileData = $shipmentData['profileData'];
        $returnData = $shipmentData['additionalCustomerData'];

        if (!empty($profileData)) {
            // Split street and house number.
            $addressData = $this->parseCustomerAddress(
                $profileData->getAddressLine1()
            );

            // Return  data.
            $returnData["first_name"] = $profileData->getGivenName();
            $returnData["last_name"] = $profileData->getFamilyName();
            $returnData["address1"] = $addressData['address'];
            $returnData["address2"] = $profileData->getAddressLine2();
            $returnData["house_number"] = $addressData['housenumber'];
            $returnData["zip_code"] = trim($profileData->getPostalCode());
            $returnData["city"] = $profileData->getLocality();
            $returnData["state"] = $profileData->getAdministrativeArea();
            $returnData["country"] = $profileData->getCountryCode();
        }
        $returnData["email"] = $order->getEmail();

        return $returnData;
    }

    /**
     * Returns the correct profile and additional customer data if shipping if false.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     * @param bool $shipping
     *   If you want shipment data.
     *
     * @return array
     *   Get all data about the customer
     *
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function addAdditionalProfileData(OrderInterface $order, $shipping)
    {
        // Check if Order has no shipment.
        if ($shipping === false) {
            $profile = $order->getBillingProfile();
            $arrayData = empty($profile) ? [] : $profile->get('address')->first();

            // Get Lang.
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

            // Add data to array.
            $additionalCustomerData["locale"] = strtolower($language) . "_"
                . strtoupper($language);
            $additionalCustomerData["ip_address"] = \Drupal::request()
                ->getClientIp();
            $additionalCustomerData["forwarded_ip"] = self::getForwardedIp();

            return [
                'profileData' => $arrayData,
                'additionalCustomerData' => $additionalCustomerData,
            ];
        }

        // If Order has shipment.
        $shipments = $order->get('shipments')->referencedEntities();
        $firstShipment = reset($shipments);
        $arrayData = $firstShipment->getShippingProfile()->address->first();

        return ['profileData' => $arrayData, 'additionalCustomerData' => null];
    }

    /**
     * Get the client it's forwarded IP.
     *
     * @return mixed|null
     *   Forwarded IP
     */
    public static function getForwardedIp()
    {
        // Check if there is a Forwarded IP.
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Validate the IP if there is one.
            return self::validateIp($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            // If there is none return no IP.
            return null;
        }
    }

    /**
     * Validate if the IP is correct.
     *
     * @param string $ip
     *   The ip address.
     *
     * @return mixed|null
     *   TRUE / NULL
     */
    private static function validateIp($ip)
    {
        $ipList = explode(',', $ip);
        $ip = trim(reset($ipList));

        // Validate IP address.
        $isValid = filter_var($ip, FILTER_VALIDATE_IP);

        // Check if the IP is valid.
        if ($isValid) {
            return $isValid;
        } else {
            return null;
        }
    }

    /**
     * Get address and house number.
     *
     * @param string $streetAddress
     *   The address.
     *
     * @return mixed
     *   Parse customer address
     */
    public function parseCustomerAddress($streetAddress)
    {
        [$address, $apartment] = $this->parseAddress($streetAddress);
        $customer['address'] = $address;
        $customer['housenumber'] = $apartment;
        return $customer;
    }

    /**
     * Split and process address to street and house number.
     *
     * @param string $streetAddress
     *   Address.
     *
     * @return array
     *   Parsed address
     */
    public function parseAddress($streetAddress)
    {
        $address = $streetAddress;
        $apartment = "";

        // Get String length.
        $offset = strlen($streetAddress);

        // Loop until $offset returns TRUE.
        while (($offset = $this->splitAddress($streetAddress, ' ', $offset))
            !== false) {
            // Check if the length of the street address is lower than the offset.
            if ($offset < strlen($streetAddress) - 1
                && is_numeric(
                    $streetAddress[$offset + 1]
                )
            ) {
                // If True, Trim the address and Apartment.
                $address = trim(substr($streetAddress, 0, $offset));
                $apartment = trim(substr($streetAddress, $offset + 1));
                break;
            }
        }

        // Check if apartment is empty and street address is higher than 0.
        if (empty($apartment) && strlen($streetAddress) > 0
            && is_numeric(
                $streetAddress[0]
            )
        ) {
            // Find the position of the first occurrence of a substring in street address.
            $pos = strpos($streetAddress, ' ');

            // Check if strpos doesn't return false.
            if ($pos !== false) {
                // If True, Trim the address and Apartment.
                $apartment = trim(
                    substr($streetAddress, 0, $pos),
                    ", \t\n\r\0\x0B"
                );
                $address = trim(substr($streetAddress, $pos + 1));
            }
        }

        // Return the address and apartment back.
        return [$address, $apartment];
    }

    /**
     * Helps split the address.
     *
     * Helps split the address to street and house number to decide where to
     * split the string.
     *
     * @param string $streetAddress
     *   Address.
     * @param string $search
     *   Search value.
     * @param null|int $offset
     *   Offset.
     *
     * @return bool|int
     *   Splitted data, if failed FALSE
     */
    public function splitAddress($streetAddress, $search, $offset = null)
    {
        // Get the size of the Street Address.
        $size = strlen($streetAddress);

        // Check if the offset is null if so make offset the size as street length.
        if (is_null($offset)) {
            $offset = $size;
        }

        // Search for the chosen string.
        $position = strpos(
            strrev($streetAddress),
            strrev($search),
            $size - $offset
        );

        // Check if there was nothing found in the string.
        if ($position === false) {
            return false;
        }

        // Return the splitted address back.
        return $size - $position - strlen($search);
    }

    /**
     * Get shipment data.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   The order.
     *
     * @return array
     *   Customer data
     *
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getShippingData(OrderInterface $order)
    {
        // If Order has shipment.
        if (!$this->orderHasShipments($order)) {
            return [];
        }

        return $this->getCustomerData($order, true);
    }

    /**
     * Logs MSP order related actions of the order.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   Order.
     * @param string $log
     *   What should be logged.
     *
     * @throws MissingDataException
     */
    public function logMsp(OrderInterface $order, $log)
    {
        $client = new Client();
        // Set the mode of the gateway.
        $gatewayHelper = new GatewayHelper();
        $mode = $gatewayHelper->getGatewayMode($order);

        $this->mspApiHelper->setApiSettings($client, $mode);

        $orderId = $orderNumber = $order->getOrderNumber() ?: $order->id();
        $mspOrder = $client->orders->get('orders', $orderId);
        $gateway = $order->get('payment_gateway')->first()->get('entity')->getValue();

        $this->logStorage->generate($order, $log)->setParams(
            [
                'old_gateway' => $gateway->get('label'),
                'new_gateway' => $mspOrder->payment_details->type,
                'status' => $mspOrder->status,
                'amount' => number_format($mspOrder->amount / 100, 2),
                'currency' => $mspOrder->currency,
                'msp_id' => $mspOrder->transaction_id,
                'external_id' => $mspOrder->payment_details->external_transaction_id,
            ]
        )->save();
    }

    private function buildReturnUrl(): Url
    {
        return Url::fromRoute('commerce_multisafepay_payments.confirm', [], ['absolute' => true]);
    }
}
