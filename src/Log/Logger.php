<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Log;

class Logger
{
    private $config;
    public function __construct()
    {
        $this->config = \Drupal::config('commerce_multisafepay_payments.settings');
    }

    public function debug(string $message)
    {
        if ($this->config->get('debug')) {
            \Drupal::logger('commerce_multisafepay_payments')->debug($message);
        }
    }

    public function error(string $message)
    {
        \Drupal::logger('commerce_multisafepay_payments')->error($message);
    }
}
