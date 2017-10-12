<?php

namespace Drupal\redis_watchdog\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\redis_watchdog\RedisWatchdog;


/**
 * This returns a themeable form that displays the total log count for different
 * types of logs.
 *
 *
 * This just needs to return HTML content.
 *
 */
class RedisWatchdogCountTable extends ControllerBase {

  /**
   * @inheritDoc
   */
  public function counttable() {
    // Get the counts.
    // $wd_types_count = _redis_watchdog_get_message_types_count();
    $redis = new RedisWatchdog();
    $wd_types_count = $redis->get_message_types_count();
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
    $build = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-redis_watchdog_type_count'],
      '#empty' => t('No log messages available.'),
    ];

    return $build;
  }
}
