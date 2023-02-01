<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Controller;

use Drupal\commerce_multisafepay_payments\API\Client;
use Drupal\commerce_multisafepay_payments\Helpers\ApiHelper;
use Drupal\commerce_multisafepay_payments\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay_payments\Helpers\OrderHelper;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     */
    public function content(Request $request)
    {
        if (!$this->isAccessValid($request)) {
            return new Response('', 403);
        }

        $transactionId = $request->get('transactionid');
        $order = Order::load($transactionId);
        $currentState = $order->getState()->getValue()['value'];

        // Nothing have to be changed and is currently untouched by MultiSafepay, redirect for now
        if ($currentState === 'draft') {
            return new RedirectResponse($this->buildReturnUrl($request)->toString());
        }

        // If the order is not canceled, we can consider it an invalid request.
        if ($currentState !== 'canceled') {
            return new Response('', 403);
        }

        $mode = $this->gatewayHelper->getGatewayMode($order);
        $this->apiHelper->setApiSettings($this->client, $mode);
        $multiSafepayOrder = $this->client->orders->get('orders', $transactionId);

        if (in_array($multiSafepayOrder->status, [OrderHelper::MSP_COMPLETED, OrderHelper::MSP_INIT])) {
            // if the order is completed or initialized, we can move the order to the first workflow state
            $order->getState()->applyDefaultValue();
            $order->save();
            return new RedirectResponse($this->buildReturnUrl($request)->toString());
        }

        //If the order is canceled at Drupal and not but not yet paid at MultiSafepay, we can consider it not done yet.
        return new Response('', 403);
    }

    protected function buildReturnUrl(Request $request)
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
