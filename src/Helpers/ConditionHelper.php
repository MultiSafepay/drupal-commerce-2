<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\Helpers;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ConditionHelper.
 */
class ConditionHelper
{

    use StringTranslationTrait;

    /**
     * Get value of enabled Total.
     *
     * @var object
     */
    protected $enabledTotal;

    /**
     * Get value of $currencyType.
     *
     * @var object
     */
    protected $enabledCurrency;

    /**
     * Get value of $currencyType.
     *
     * @var object
     */
    protected $currencyType;

    /**
     * Sets the Order amount condition.
     *
     * @param string $operator
     *   Type of operator (example: lesser than = <,
     *   greater than = > etc)
     * @param float $amount
     *   The amount.
     * @param string $currency
     *   Type of currency (USD, EUR)
     *
     * @return array
     *   Created rule
     */
    public function orderTotalCondition($operator, $amount, $currency)
    {

        // Create a condition.
        $condition = [
            'conditions' => [
                [
                    'plugin' => 'order_total_price',
                    'configuration' => [
                        'operator' => $operator,
                        'amount' => [
                            'number' => $amount,
                            'currency_code' => $currency,
                        ],
                    ],
                ],
            ],
        ];

        // Set enabled TRUE because we use it.
        $this->enabledTotal = true;

        return $condition;
    }

    /**
     * Set the currency to this so we can use it in the messages.
     *
     * @param string $currency
     *   Sets the currency type.
     *
     * @return bool
     *   Created rule
     */
    public function orderCurrencyCondition($currency)
    {
        $this->currencyType = $currency;
        $this->enabledCurrency = true;
        return true;
    }

    /**
     * Sets the message of the condition.
     *
     * @return array
     *   Created message
     */
    public function orderConditionMessage()
    {
        $message = $this->t(
            'This gateway contains a restriction. To enable it please click on Order and Enable:'
        );

        // Set styling.
        $form['styling'] = [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' => '
                h3, li {
                    color: #0074bd;
                }
       ',
        ];

        // Set message.
        $form['details'] = [
            '#type' => 'html_tag',
            '#tag' => 'b',
            '#value' => '
            <h3>' . $message . '</h3>
            <ul>
                ' . $this->checkTotalCondition() . '
                ' . $this->checkCurrencyCondition() . '
            </ul>
       ',
        ];

        return $form;
    }

    /**
     * Sets the message of the condition.
     *
     * @return string
     *   Created message
     */
    public function checkTotalCondition()
    {
        return $this->enabledTotal ? '<li>' . $this->t('Current order total') . '</li>'
            : '';
    }

    /**
     * Sets the message of the condition.
     *
     * @return string
     *   Created message
     */
    public function checkCurrencyCondition()
    {
        return $this->enabledCurrency ? '<li>' . $this->t('Order currency') . ' - '
            . $this->t(
                $this->currencyType
            ) . '</li>' : '';
    }
}
