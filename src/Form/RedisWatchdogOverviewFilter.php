<?php
/**
 * Created by PhpStorm.
 * User: bowens
 * Date: 10/6/17
 * Time: 21:49
 */

namespace Drupal\redis_watchdog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redis_watchdog\RedisWatchdog;

class RedisWatchdogOverviewFilter extends FormBase {

  const SESSION_KEY = 'redis_watchdog_overview_filter';

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'redis_watchdog_overview_filter';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Message types.
    // @todo remove this once working
    // $wd_types = _redis_watchdog_get_message_types();
    $redis = new RedisWatchdog();
    $wd_types = $redis->get_message_types();

    // Build a selection list of log types.
    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => t('Filter log messages by type'),
      '#collapsible' => empty($_SESSION['redis_watchdog_overview_filter']),
      '#collapsed' => TRUE,
    ];
    $form['filters']['type'] = [
      '#title' => t('Available types'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => array_flip($wd_types),
    ];
    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Filter'),
    ];

    if (!empty($_SESSION['redis_watchdog_overview_filter'])) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => t('Reset'),
      ];
    }

    if (!empty($_SESSION['redis_watchdog_overview_filter']['type'])) {
      $form['filters']['type']['#default_value'] = $_SESSION['redis_watchdog_overview_filter']['type'];
    }
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }


}