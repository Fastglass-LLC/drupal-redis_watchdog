<?php

namespace Drupal\redis_watchdog\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\redis_watchdog as rWatch;
use Drupal\Component\Utility as Util;

class RedisWatchdogOverview extends FormBase{

  public function buildForm(array $form, FormStateInterface $form_state) {
    $rows = [];
    $classes = [
      WATCHDOG_DEBUG => 'redis_watchdog-debug',
      WATCHDOG_INFO => 'redis_watchdog-info',
      WATCHDOG_NOTICE => 'redis_watchdog-notice',
      WATCHDOG_WARNING => 'redis_watchdog-warning',
      WATCHDOG_ERROR => 'redis_watchdog-error',
      WATCHDOG_CRITICAL => 'redis_watchdog-critical',
      WATCHDOG_ALERT => 'redis_watchdog-alert',
      WATCHDOG_EMERGENCY => 'redis_watchdog-emerg',
    ];

    $header = [
      '', // Icon column.
      ['data' => t('Type'), 'field' => 'w.type'],
      ['data' => t('Date'), 'field' => 'w.wid', 'sort' => 'desc'],
      t('Message'),
      ['data' => t('User'), 'field' => 'u.name'],
      ['data' => t('Operations')],
    ];
    // @todo remove when working
    // $log = redis_watchdog_client();
    $log = rWatch\redis_watchdog_client();
    $result = $log->getRecentLogs();
    foreach ($result as $log) {
      $rows[] = [
        'data' =>
          [
            // Cells
            ['class' => 'icon'],
            t($log->type),
            \Drupal::service('date.formater')->format($log->timestamp, 'short'),
            theme('redis_watchdog_message', ['event' => $log, 'link' => TRUE]),
            theme('username', ['account' => $log]),
            Util\Xss::filter($log->link),
          ],
        // Attributes for tr
        'class' => [
          Util\Html::cleanCssIdentifier('dblog-' . $log->type),
          $classes[$log->severity],
        ],
      ];
    }

    // Log type selector menu.
    // $build['redis_watchdog_filter_form'] = drupal_get_form('redis_watchdog_filter_form');
    $build['redis_watchdog_filter_form'] = \Drupal::formBuilder()->getForm($this->filterForm());
    // Clear log form.
    // $build['redis_watchdog_clear_log_form'] = drupal_get_form('redis_watchdog_clear_log_form');
    $build['redis_watchdog_filter_form'] = \Drupal::formBuilder()->getForm($this->redis_watchdog_clear_log_form());


    // Summary of log types stored and the number of items in the log.
    $build['redis_watchdog_type_count_table'] = redis_watchdog_log_type_count_table();

    if (isset($_SESSION['redis_watchdog_overview_filter']['type']) && !empty($_SESSION['redis_watchdog_overview_filter']['type'])) {
      $typeid = check_plain(array_pop($_SESSION['redis_watchdog_overview_filter']['type']));
      $build['redis_watchdog_table'] = redis_watchdog_type($typeid);
    }
    else {
      $build['redis_watchdog_table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['id' => 'admin-redis_watchdog'],
        '#empty' => t('No log messages available.'),
      ];
      $build['redis_watchdog_pager'] = ['#theme' => 'pager'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redis_watchdog_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  private function filterForm($form){
    // Message types.
    // @todo remove this once working
    // $wd_types = _redis_watchdog_get_message_types();
    $wd_types = rWatch\RedisWatchdog::get_message_types();

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
   * Return form for redis_watchdog clear button.
   *
   * @ingroup forms
   * @see redis_watchdog_clear_log_submit()
   */
  private function redis_watchdog_clear_log_form($form) {
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
      '#submit' => $this->redis_watchdog_clear_log_submit(),
    ];

    return $form;
  }

  /**
   * Submit callback: clear database with log messages.
   */
  private function redis_watchdog_clear_log_submit() {
    $_SESSION['redis_watchdog_overview_filter'] = [];
    // @todo remove this once working
    // $log = redis_watchdog_client();
    $log = rWatch\RedisWatchdog::redis_watchdog_client();
    $log->clear();

    drupal_set_message(t('Database log cleared.'));
  }
}