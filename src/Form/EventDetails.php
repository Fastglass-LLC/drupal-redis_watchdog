<?php

namespace Drupal\redis_watchdog\Form;

use Drupal\Core\Controller\ControllerBase;
use Drupal\redis_watchdog as rWatch;

class EventDetails extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'redis_watchdog';
  }

  /**
   * Form to show the details about an event by ID.
   *
   * @param int $eventid
   *
   * @return array
   */
  public function buildEventForm($eventid) {
    // @todo remove once working
    // $severity = watchdog_severity_levels();
    $severity = \Drupal\Core\Logger\RfcLogLevel::getLevels();
    $log = rWatch\RedisWatchdog::redis_watchdog_client();
    $result = $log->getSingle($eventid);

    $rows = [
      [
        ['data' => t('Type'), 'header' => TRUE],
        t($log->type),
      ],
      [
        ['data' => t('Date'), 'header' => TRUE],
        \Drupal::service('date.formatter')->format($log->timestamp, 'long'),
      ],
      [
        ['data' => t('User'), 'header' => TRUE],
        theme('username', ['account' => $log]),
      ],
      [
        ['data' => t('Location'), 'header' => TRUE],
        l($log->location, $log->location),
      ],
      [
        ['data' => t('Referrer'), 'header' => TRUE],
        l($log->referer, $log->referer),
      ],
      [
        ['data' => t('Message'), 'header' => TRUE],
        theme('redis_watchdog_message', ['event' => $log]),
      ],
      [
        ['data' => t('Severity'), 'header' => TRUE],
        $severity[$log->severity],
      ],
      [
        ['data' => t('Hostname'), 'header' => TRUE],
        check_plain($log->hostname),
      ],
      [
        ['data' => t('Operations'), 'header' => TRUE],
        $log->link,
      ],
    ];
    $build['redis_watchdog_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => ['redis_watchdog-event']],
    ];
    return $build;
  }
}
