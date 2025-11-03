<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Helpers;

use Drupal\commerce_multisafepay_payments\API\Client;

/**
 * Class ApiHelper.
 */
class ApiHelper
{

    /**
     * Set the api settings.
     *
     * @param \Drupal\commerce_multisafepay_payments\API\Client $client MSP client.
     * @param string $mode Mode the payment gateway is using (test / live / n/a)
     */
    public function setApiSettings(Client $client, $mode)
    {
        $config = \Drupal::config('commerce_multisafepay_payments.settings');

        // Get the needed Data to set the setting.
        $testApiKey = $config->get('test_api_key');
        $liveApiKey = $config->get('live_api_key');

        // Check if the gateway is N/A.
        if ($mode === "n/a") {
            $mode = $config->get('account_type');
        }

        // Check if the account type is set to Test Or live.
        if (strtolower($mode) === 'live') {
            // Set Live URL.
            $client->setApiUrl('https://api.multisafepay.com/v1/json/');
            // Set the API key.
            $client->setApiKey($liveApiKey);
        } else {
            if (strtolower($mode) === 'test') {
                // Set Test URL.
                $client->setApiUrl('https://testapi.multisafepay.com/v1/json/');
                // Set the API key.
                $client->setApiKey($testApiKey);
            }
        }
    }
}
