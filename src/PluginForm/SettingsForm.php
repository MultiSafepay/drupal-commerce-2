<?php declare(strict_types=1);
namespace Drupal\commerce_multisafepay_payments\PluginForm;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase
{

  /**
   * Get the formID.
   *
   * @return string
   *   Get form id
   */
    public function getFormId()
    {
        return 'commerce_multisafepay_payments_admin_settings';
    }

  /**
   * Get the editable configuration names.
   *
   * @return array
   *   Get editable config names array
   */
    protected function getEditableConfigNames()
    {
        return [
        'commerce_multisafepay_payments.settings',
        ];
    }

  /**
   * Build the form that displays it in your browser.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Build the general MultiSafepay settings page.
   */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      // Get the current data.
        $config = $this->config('commerce_multisafepay_payments.settings');

      // Plugin Status select field (Test or Live) & get the plugin status if
      // user has selected it before.
        $form['account_type'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Account type'),
        '#options'       => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
        ],
        '#default_value' => $config->get('account_type'),
        ];

      // API input field & get the api if user has filled it before.
        $form['live_api_key'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Live Api Key'),
        '#default_value' => $config->get('live_api_key'),
        ];

      // API input field & get the api if user has filled it before.
        $form['test_api_key'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Test Api Key'),
        '#default_value' => $config->get('test_api_key'),
        ];

      // Plugin seconds active field.
        $form['seconds_active'] = [
        '#type'          => 'number',
        '#title'         => $this->t('Seconds active'),
        '#default_value' => $config->get('seconds_active'),
        '#description'   => $this->t('Time an order stays active.'),
        ];

      // Generate the form.
        return parent::buildForm($form, $form_state);
    }

  /**
   * Behavior when you submit the form.
   *
   * @param array $form
   *   The form details.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $liveApiKey = $form_state->getValue('live_api_key');
        $testApiKey = $form_state->getValue('test_api_key');
        $accountType = $form_state->getValue('account_type');
        $secondsActive = $form_state->getValue('seconds_active');

      // Retrieve the configuration.
        $this->configFactory->getEditable('commerce_multisafepay_payments.settings')
        // Set the submitted configuration setting.
        ->set('live_api_key', $liveApiKey)
        ->set('test_api_key', $testApiKey)
        ->set('account_type', $accountType)
        ->set('seconds_active', $secondsActive)
        ->save();

        parent::submitForm($form, $form_state);
    }
}
