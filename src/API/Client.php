<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\API;

use Drupal\commerce_multisafepay_payments\API\Object\Gateways;
use Drupal\commerce_multisafepay_payments\API\Object\Issuers;
use Drupal\commerce_multisafepay_payments\API\Object\Orders;
use Drupal\commerce_multisafepay_payments\Exceptions\ExceptionHelper;

/**
 * Class Client.
 */
class Client
{

  /**
   * MSP Orders.
   *
   * @var \Drupal\commerce_multisafepay_payments\API\Object\Orders
   */
    public $orders;

  /**
   * MSP issuers.
   *
   * @var \Drupal\commerce_multisafepay_payments\API\Object\Issuers
   */
    public $issuers;

  /**
   * Object.
   *
   * @var object
   */
    public $transactions;

  /**
   * MSP gateways.
   *
   * @var \Drupal\commerce_multisafepay_payments\API\Object\Gateways
   */
    public $gateways;

  /**
   * API Key.
   *
   * @var string
   */
    protected $apiKey;

  /**
   * API url.
   *
   * @var string
   */
    public $apiUrl;

  /**
   * API endpoint.
   *
   * @var string
   */
    public $apiEndpoint;

  /**
   * MSP request.
   *
   * @var string
   */
    public $request;

  /**
   * MSP response.
   *
   * @var string
   */
    public $response;

  /**
   * Debug mode.
   *
   * @var bool
   */
    public $debug;

  /**
   * Exception helper.
   *
   * @var \Drupal\commerce_multisafepay_payments\Exceptions\ExceptionHelper
   */
    protected $exceptionHelper;

  /**
   * Client constructor.
   */
    public function __construct()
    {
        $this->orders = new Orders($this);
        $this->issuers = new Issuers($this);
        $this->gateways = new Gateways($this);
        $this->exceptionHelper = new ExceptionHelper();
    }

  /**
   * Get the request.
   *
   * @return mixed
   *   The request
   */
    public function getRequest()
    {
        return $this->request;
    }

  /**
   * Get the response.
   *
   * @return mixed
   *   The response
   */
    public function getResponse()
    {
        return $this->response;
    }

  /**
   * Set the API url.
   *
   * @param string $url
   *   The API url.
   */
    public function setApiUrl($url)
    {
        $this->apiUrl = trim($url);
    }

  /**
   * Set if the API should be debugging.
   *
   * @param string $debug
   *   Set if you want to log request.
   */
    public function setDebug($debug)
    {
        $this->debug = trim($debug);
    }

  /**
   * Set the API key.
   *
   * @param string $api_key
   *   Api key.
   */
    public function setApiKey($api_key)
    {
        $this->apiKey = trim($api_key);
    }

  /**
   * Process and send the request to MultiSafepay.
   *
   * @param string $http_method
   *   GET / POST / ETC.
   * @param string $api_method
   *   Endpoint of url.
   * @param null|string $http_body
   *   Request send to MultiSafepay (JSON).
   *
   * @return mixed
   *   Process the api request
   */
    public function processApiRequest(
        $http_method,
        $api_method,
        $http_body = null
    ) {
        if (empty($this->apiKey)) {
            $this->exceptionHelper->paymentGatewayException(
                "Please configure your MultiSafepay API Key."
            );
        }

        $url = $this->apiUrl . $api_method;
        $ch = curl_init($url);

        $request_headers = [
        "Accept: application/json",
        "api_key:" . $this->apiKey,
        ];

        if ($http_body !== null) {
            $request_headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $http_body);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        $body = curl_exec($ch);

        if ($this->debug) {
            $this->request = $http_body;
            $this->response = $body;
        }

        if (curl_errno($ch)) {
            $this->exceptionHelper->paymentGatewayException(
                "Unable to communicate with the MultiSafepay payment server ("
                . curl_errno($ch) . "): " . curl_error($ch) . "."
            );
        }

        curl_close($ch);
        return $body;
    }
}
