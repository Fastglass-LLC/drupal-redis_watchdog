<?php

namespace Drupal\redis_watchdog\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class RedisWatchdogSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['redis_watchdog.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redis_watchdog_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('redis_watchdog.settings');

    $form['watchdog'] = [
      '#type' => 'fieldset',
      '#title' => t('Drupal Watchdog Logs'),
      '#description' => t('Controls for the Watchdog module'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['watchdog']['redis_watchdogprefix'] = [
      '#type' => 'textfield',
      '#title' => t('Key prefix'),
      '#description' => t('You may specify a prefix if you are using a single Redis server for multiple sites. This will add a prefix to the beginning of the keys.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#default_value' => $config->get('prefix'),
    ];
    $form['watchdog']['redis_watchdogrecentlimit'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Recent log limit'),
      '#description' => t('This is a limit on the display of recent logs in the default log view. This can be any number, but the larger the number the larger the dataset that is pulled into the default log page.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#default_value' => $config->get('recentlimit'),
    ];
    $form['watchdog']['redis_watchdogtypepagelimit'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Type log page size'),
      '#description' => t('Page size for view logs of a specific type.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#default_value' => $config->get('pagelimit'),
    ];
    $form['watchdog']['redis_watchdogarchivelimit'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Archive log limit'),
      '#description' => t('This is the limit on the amount of logs that will be saved for any log type.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#default_value' => $config->get('archivelimit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('redis_watchdog.settings')
      ->set('prefix', $form_state->getValue('redis_watchdogprefix'))
      ->set('recentlimit', $form_state->getValue('redis_watchdogrecentlimit'))
      ->set('pagelimit', $form_state->getValue('redis_watchdogtypepagelimit'))
      ->set('archivelimit', $form_state->getValue('redis_watchdogarchivelimit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}