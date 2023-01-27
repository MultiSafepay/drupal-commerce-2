<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\API\Object;

/**
 * Class Issuers.
 */
class Issuers extends Core
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
   * Create predefined GET Issuers request.
   *
   * @param string $endpoint
   *   Endpoint.
   * @param string $type
   *   Type.
   * @param array $body
   *   Data send to MSP.
   * @param bool $query_string
   *   How the url should be created.
   *
   * @return mixed
   *   Get the issuers
   */
    public function get(
        $endpoint = 'issuers',
        $type = 'ideal',
        array $body = [],
        $query_string = false
    ) {

        $result = parent::get($endpoint, $type, $body, $query_string);
        $this->success = $result->success;
        $this->data = $result->data;

        return $this->data;
    }
}
