<?php

namespace Drupal\redis_watchdog\Form;

use Drupal\Core\Controller\ControllerBase;
use Drupal\redis_watchdog\RedisWatchdog as rWatch;
use Drupal\redis_watchdog\RedisWatchdog;

class Download extends ControllerBase {

  /**
   * Menu call back to redirect and cause a file download in the browser.
   */
  public function downloadForm(){
    $redis = new RedisWatchdog();
    $config = \Drupal::config('redis_watchdog.settings');
    $prefix = $config->get('prefix');
    if (empty($prefix)) {
      $prefix = '-';
    }
    else {
      $prefix = '-' . $prefix . '-';
    }
    $redis->downloadSendHeaders('drupal-redis-watchdog' . $prefix . 'export.csv');
    echo $redis->redis_watchdog_csv_export();
    die();
  }

}