<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\API\Object;

/**
 * Class Gateways.
 */
class Gateways extends Core
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
   * Create predefined GET gateways request.
   *
   * @param string $endpoint
   *   Endpoint.
   * @param string $type
   *   Type.
   * @param array $body
   *   The request.
   * @param bool $query_string
   *   How url should be handled.
   *
   * @return mixed
   *   Process get request
   */
    public function get(
        $endpoint = 'gateways',
        $type = '',
        array $body = [],
        $query_string = false
    ) {
        $result = parent::get(
            $endpoint,
            $type,
            $body,
            $query_string
        );
        $this->success = $result->success;
        $this->data = $result->data;

        return $this->data;
    }
}
