<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\API\Object;

/**
 * Class Orders.
 */
class Orders extends Core
{

  /**
   * Success?
   *
   * @var bool
   */
    public $success;

  /**
   * Data.
   *
   * @var object
   */
    public $data;

  /**
   * Predefined PATCH request for orders.
   *
   * @param array $body
   *   Request posted to MultiSafepay.
   * @param string $endpoint
   *   Endpoint of the URL.
   *
   * @return mixed
   *   Process patch request
   */
    public function patch(array $body, $endpoint = '')
    {
        $result = parent::patch($body, $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;
        return $result;
    }

  /**
   * Predefined GET request for orders.
   *
   * @param string $type
   *   Endpoint of the url.
   * @param string $id
   *   Order id added to the endpoint.
   * @param array $body
   *   Request posted to MultiSafepay.
   * @param bool $query_string
   *   How the request should be handled.
   *
   * @return mixed
   *   Process get request
   */
    public function get($type, $id, array $body = [], $query_string = false)
    {
        $result = parent::get($type, $id, $body, $query_string);
        $this->success = $result->success;
        $this->data = $result->data;
        return $this->data;
    }

  /**
   * Predefined POST Request for orders.
   *
   * @param array $body
   *   The data send to MultiSafepay.
   * @param string $endpoint
   *   Endpoint of the url.
   *
   * @return mixed
   *   Process post request
   */
    public function post(array $body, $endpoint = 'orders')
    {
        $result = parent::post($body, $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;
        return $this->data;
    }

  /**
   * Get payment link.
   *
   * @return mixed
   *   The payment link
   */
    public function getPaymentLink()
    {
        return $this->data->payment_url;
    }
}
