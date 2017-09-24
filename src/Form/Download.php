<?php

namespace Drupal\redis_watchdog\Form;

use Drupal\Core\Controller\ControllerBase;
use Drupal\redis_watchdog;

class Download extends ControllerBase {

  /**
   * Menu call back to redirect and cause a file download in the browser.
   */
  public function downloadForm(){
    $config = \Drupal::config('redis_watchdog.settings');
    $prefix = $config->get('prefix');
    if (empty($prefix)) {
      $prefix = '-';
    }
    else {
      $prefix = '-' . $prefix . '-';
    }
    Drupal\redis_watchdog\redis_watchdog_download_send_headers('drupal-redis-watchdog' . $prefix . 'export.csv');
    echo redis_watchdog_csv_export();
    die();
  }

}