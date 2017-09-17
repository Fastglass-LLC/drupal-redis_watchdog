<?php

namespace Drupal\redis_watchdog;

use Drupal\redis_watchdog as Redis;
use Drupal\Component\Utility as Util;

class RedisWatchdog {

  /**
   * Return the Redis client for log activity.
   *
   * @return object
   */

  public function redis_watchdog_client() {
    $config = \Drupal::config('redis_watchdog.settings');
    $prefix = $config->get('watchdogprefix');
    $limit = $config->get('recentlimit');
    $archive = $config->get('archivelimit');
    $client = new Redis\RedisLog($prefix, $limit, $archive);
    return $client;
  }

  /**
   * Pulls all logs and returns them as a CSV file from the output buffer.
   *
   * @return string
   */

  public function redis_watchdog_csv_export() {
    $client = $this->redis_watchdog_client();
    $logs_to_export = $client->getAllMessages();
    ob_start();
    $df = fopen('php://output', 'w');
    foreach ($logs_to_export as $row) {
      // Convert the object to an array.
      $data = unserialize($row);
      $data = (array) $data;
      fputcsv($df, $data);
    }
    fclose($df);
    return ob_get_clean();
  }


  /**
   * Sets the page headers to force the browser to download a file.
   *
   * @param string $filename
   */
  public function redis_watchdog_download_send_headers($filename) {
    $filename = filter_xss($filename);
    $filename = Util\Xss::filter($filename);
    // Disable caching.
    $now = gmdate('D, d M Y H:i:s');
    header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
    header('Last-Modified: ' . $now . ' GMT');

    // Force download.
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');

    // Disposition and encoding on response body.
    header('Content-Disposition: attachment;filename=' . $filename);
    header('Content-Transfer-Encoding: binary');
  }

  /**
   * Destroys all Redis information.
   *
   * @return bool
   */
  public function redis_watchdog_redis_destroy() {
    $client = $this->redis_watchdog_client();
    if ($client->clear()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Private function to return the types of messages stored.
   *
   * @return array|mixed
   */
  public function _redis_watchdog_get_message_types() {
    $log = $this->redis_watchdog_client();
    return $log->getMessageTypes();
  }

  /**
   * Private function to return the count of message stored.
   *
   * @return array|mixed
   */
  public function _redis_watchdog_get_message_types_count() {
    $log = $this->redis_watchdog_client();
    return $log->getMessageTypesCounts();
  }
}