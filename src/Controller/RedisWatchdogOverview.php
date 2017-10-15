<?php

namespace Drupal\redis_watchdog\Controller;

use Drupal\Component\Utility as Util;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\redis_watchdog\Form as rForm;
use Drupal\redis_watchdog\RedisWatchdog;
use Psr\Log\LogLevel;

class RedisWatchdogOverview extends ControllerBase {

  protected $userStorage;

  protected $redis;

  const SEVERITY_PREFIX = 'redis_watchdog__severity_';

  /**
   * Used in processing of the log levels in displays and css classes.
   */
  const SEVERITY_CLASSES = [
    RfcLogLevel::DEBUG => self::SEVERITY_PREFIX . LogLevel::DEBUG,
    RfcLogLevel::INFO => self::SEVERITY_PREFIX . LogLevel::INFO,
    RfcLogLevel::NOTICE => self::SEVERITY_PREFIX . LogLevel::NOTICE,
    RfcLogLevel::WARNING => self::SEVERITY_PREFIX . LogLevel::WARNING,
    RfcLogLevel::ERROR => self::SEVERITY_PREFIX . LogLevel::ERROR,
    RfcLogLevel::CRITICAL => self::SEVERITY_PREFIX . LogLevel::CRITICAL,
    RfcLogLevel::ALERT => self::SEVERITY_PREFIX . LogLevel::ALERT,
    RfcLogLevel::EMERGENCY => self::SEVERITY_PREFIX . LogLevel::EMERGENCY,
  ];

  public function __construct() {
    $this->userStorage = $this->entityManager()->getStorage('user');
    $this->redis = new RedisWatchdog();
  }

  /**
   * Build the overview page.
   *
   * @return mixed
   */
  public function overview() {

    // Log type selector menu.
    // $build['redis_watchdog_filter_form'] = drupal_get_form('redis_watchdog_filter_form');
    $build['redis_watchdog_filter_form'] = \Drupal::formBuilder()
      ->getForm('\Drupal\redis_watchdog\Form\RedisWatchdogOverviewFilter');


    // Clear log form.
    $build['redis_watchdog_filter_form_clear'] = \Drupal::formBuilder()
      ->getForm('\Drupal\redis_watchdog\Form\RedisWatchdogOverviewClearForm');


    // // Summary of log types stored and the number of items in the log.
    $table = new \Drupal\redis_watchdog\Controller\RedisWatchdogCountTable();

    $build['redis_watchdog_type_count_table'] = $table->counttable();

    if (isset($_SESSION['redis_watchdog_overview_filter']['type']) && !empty($_SESSION['redis_watchdog_overview_filter']['type'])) {
      // @todo remove this if it works
      // $typeid = check_plain(array_pop($_SESSION['redis_watchdog_overview_filter']['type']));
      $typeid = Util\SafeMarkup::checkPlain(array_pop($_SESSION['redis_watchdog_overview_filter']['type']));
      // $build['redis_watchdog_table'] = redis_watchdog_type($typeid);
      $build['redis_watchdog_table'] = rForm\TypeDetailsForm::buildTypeForm($typeid);
    }
    else {
      $build['redis_watchdog_table'] = $this->overviewRedisRows();
    }
    return $build;
  }

  /**
   * This returns a themeable form that displays the total log count for
   * different types of logs.
   *
   * @return array
   */
  public function redis_watchdog_log_type_count_table() {
    // Get the counts.
    $redis = new RedisWatchdog();
    // $wd_types_count = _redis_watchdog_get_message_types_count();
    $wd_types_count = $redis->getMessageTypesCounts();
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
    $build['redis_watchdog_type_count_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-redis_watchdog_type_count'],
      '#empty' => t('No log messages available.'),
    ];

    return $build;
  }

  /**
   * Provides the rows in the overview page.
   *
   * @return array
   */
  public function overviewRedisRows() {
    $header = [
      ['data' => t('Type'), 'field' => 'w.type'],
      ['data' => t('Severity'), 'field' => 'w.severity'],
      ['data' => t('Date'), 'field' => 'w.wid', 'sort' => 'desc'],
      t('Message'),
      ['data' => t('User'), 'field' => 'u.name'],
    ];
    $levels = RfcLogLevel::getLevels();
    $result = $this->redis->getRecentLogs();
    foreach ($result as $log) {
      // Process the message into a link and substitute variables.
      // Truncate message to 56 chars.
      if ($log->variables === 'N;') {
        $output = $log->message;
      }
      // Message to translate with injected variables.
      else {
        $output = t($log->message, unserialize($log->variables));
      }
      $message = Util\Unicode::truncate(Util\Xss::filter($output, []), 56, TRUE, TRUE);
      $message = Link::createFromRoute($message, 'redis_watchdog.event', ['eventid' => $log->wid]);
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($log->uid),
      ];
      $rows[] = [
        'data' =>
          [
            t($log->type),
            [
              'class' => static::SEVERITY_CLASSES[$log->severity],
              'data' => $levels[$log->severity],
            ],
            \Drupal::service('date.formatter')
              ->format($log->timestamp, 'short'),
            ['data' => $message],
            ['data' => $username],
          ],
        'class' => static::SEVERITY_CLASSES[$log->severity],
      ];
    }
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows ?? [],
      '#empty' => t('No log messages available.'),
    ];
  }

  /**
   * Form to show the details about an event by ID.
   *
   * @param int $eventid
   *
   * @return array
   */
  public function buildEventForm($eventid) {

    $result = $this->redis->getSingle($eventid);

    if ($log = $result) {
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($log->uid),
      ];
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
          ['data' => $username],
        ],
        [
          ['data' => t('Location'), 'header' => TRUE],
          Link::fromTextAndUrl($log->location, Url::fromUri($log->location)),
        ],
        [
          ['data' => t('Referrer'), 'header' => TRUE],
          Link::fromTextAndUrl($log->referer, Url::fromUri($log->referer)),
        ],
        [
          ['data' => t('Message'), 'header' => TRUE],
          t($log->message, unserialize($log->variables)),
        ],
        [
          ['data' => t('Severity'), 'header' => TRUE],
          static::SEVERITY_CLASSES[$log->severity],
        ],
        [
          ['data' => t('Hostname'), 'header' => TRUE],
          Util\SafeMarkup::checkPlain($log->hostname),
        ],

      ];
    }
    $build['redis_watchdog_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => ['redis_watchdog-event']],
      '#empty' => t('No log messages available.'),
    ];
    return $build;
  }
}