<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Exceptions;

use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Class ExceptionHelper.
 */
class ExceptionHelper
{

  /**
   * Sends a paymentGateway Exception and add a error to the log.
   *
   * @param string $errorInfo
   *   The full description of the error.
   * @param null|int $errorCode
   *   Error code.
   */
    public function paymentGatewayException($errorInfo, $errorCode = null)
    {
      // If code exist.
        if (isset($errorCode)) {
            (string) $errorCode .= " : ";
        }

        $message = "{$errorCode}{$errorInfo}";
        \Drupal::messenger()->addError($message);
        throw new PaymentGatewayException($message);
    }
}
