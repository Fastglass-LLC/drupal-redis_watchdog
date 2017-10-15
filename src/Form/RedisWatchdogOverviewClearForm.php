<?php


namespace Drupal\redis_watchdog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redis_watchdog\RedisWatchdog;

class RedisWatchdogOverviewClearForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'redis_watchdog_overview_clear_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['redis_watchdog_clear'] = [
      '#type' => 'fieldset',
      '#title' => t('Clear log messages'),
      '#description' => t('This will permanently remove the log messages from the database.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['redis_watchdog_clear']['clear'] = [
      '#type' => 'submit',
      '#value' => t('Clear log messages'),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['redis_watchdog_overview_filter'] = [];
    // @todo remove this once working
    // $log = redis_watchdog_client();
    $redis = new RedisWatchdog();
    $redis->clear();
    drupal_set_message(t('Database log cleared.'));
  }
}