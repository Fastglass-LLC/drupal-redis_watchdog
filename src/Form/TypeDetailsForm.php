<?php

namespace Drupal\redis_watchdog\Form;

use Drupal\Component\Utility as Util;
use Drupal\Core\Controller\ControllerBase;
use Drupal\redis_watchdog as rWatch;


class TypeDetailsForm extends ControllerBase {

  /**
   * @param int $tid
   * @param int $page
   *
   * @return mixed
   */
  public static function buildTypeForm(int $tid, int $page = 0) {
    $rows = [];
    $pagesize = 50;
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
    $log = rWatch\RedisWatchdog::redis_watchdog_client();
    // @todo pagination needed
    $result = $log->getMultipleByType($pagesize, $tid);
    foreach ($result as $log) {
      $rows[] = [
        'data' =>
          [
            // Cells
            ['class' => 'icon'],
            t($log->type),
            \Drupal::service('date.formatter')
              ->format($log->timestamp, 'short'),
            // theme('redis_watchdog_message', ['event' => $log, 'link' => TRUE]),
            [
              '#theme' => 'redis_watchdog_message',
              ['event' => $log, 'link' => TRUE],
            ],
            // theme('username', ['account' => $log]),
            [
              '#theme' => 'username',
              ['account' => $log],
            ],
            Util\Xss::filter($log->link),
          ],
        // Attributes for tr
        'class' => [
          Util\Html::cleanCssIdentifier('dblog-' . $log->type),
          $classes[$log->severity],
        ],
      ];
    }

    // Table of log items.
    $build['redis_watchdog_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-redis_watchdog'],
      '#empty' => t('No log messages available.'),
    ];
    $build['redis_watchdog_pager'] = ['#theme' => 'pager'];

    return $build;
  }
}