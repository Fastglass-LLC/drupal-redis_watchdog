<?php

namespace Drupal\redis_watchdog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redis_watchdog\RedisWatchdog as rWatch;


/**
 * This returns a themeable form that displays the total log count for different
 * types of logs.
 *
 *
 * This just needs to return HTML content.
 *
 */


class RedisWatchdogCountTable extends FormBase {

  const SESSION_KEY = 'mongodb_watchdog_overview_filter';

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'redis_watchdog_count_table';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the counts.
    // $wd_types_count = _redis_watchdog_get_message_types_count();
    $wd_types_count = rWatch::get_message_types_count();
    $header = [
      t('Log Type'),
      t('Count'),
    ];
    $rows = [];
    foreach ($wd_types_count as $key => $value) {
      $rows[] = [
        'data' => [
          // Cells
          $key,
          $value,
        ],
      ];
    }
    // Table of log items.
    $form['redis_watchdog_type_count_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-redis_watchdog_type_count'],
      '#empty' => t('No log messages available.'),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = $this->getFilters();
    foreach ($filters as $name => $filter) {
      if ($form_state->hasValue($name)) {
        $_SESSION[static::SESSION_KEY][$name] = $form_state->getValue($name);
      }
    }
  }


  /**
   * Resets the filter form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION[static::SESSION_KEY] = [];
  }

}