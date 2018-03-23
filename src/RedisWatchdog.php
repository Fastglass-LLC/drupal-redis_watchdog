<?php

namespace Drupal\redis_watchdog;

use Drupal\Component\Utility as Util;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;

/**
 * Class RedisWatchdog.
 *
 * Provides operational code to Drupal sites.
 *
 * @package Drupal\redis_watchdog
 */
class RedisWatchdog {

  use RedisPrefixTrait;

  protected $logprefix;

  protected $prefix;

  protected $limit;

  protected $archive;

  private $client;

  protected $key;

  public function __construct() {
    $config = \Drupal::config('redis_watchdog.settings');
    $this->prefix = $this->getDefaultPrefix();
    $this->logprefix = $config->get('prefix');
    $this->key = $this->getDefaultPrefix() . ':' . $config->get('prefix');
    $this->limit = $config->get('recentlimit');
    $this->archive = $config->get('archivelimit');
    $this->client = ClientFactory::getClient();
  }

  /**
   * Return the Redis client for log activity.
   *
   * @deprecated To be removed before release.
   *
   * @return object
   */

  public static function redis_watchdog_client() {
    $config = \Drupal::config('redis_watchdog.settings');
    $client = ClientFactory::getClient();
    return $client;
  }

  /**
   * Return the Redis client for log activity.
   *
   * @return object
   */

  public function getClient() {
    return $this->client;
  }

  /**
   * Pulls all logs and returns them as a CSV file from the output buffer.
   *
   * @return string
   */

  public function exportCSV() {
    $logs_to_export = $this->getAllMessages();
    ob_start();
    $df = fopen('php://output', 'w');
    foreach ($logs_to_export as $row) {
      // Convert the object to an array.
      $data = unserialize($row);
      $messageString = theme('redis_watchdog_message', ['event' => $data]);
      $data = (array) $data;
      unset ($data['message']);
      $data['message'] = $messageString;
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
  public function downloadSendHeaders($filename) {
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
   * Private function to return the types of messages stored.
   *
   * @return array|mixed
   */
  public function get_message_types() {
    return $this->getMessageTypes();
  }

  /**
   * Private function to return the count of message stored.
   *
   * @return array|mixed
   */
  public function get_message_types_count() {
    return $this->getMessageTypesCounts();
  }

  /**
   * Clear all information from logs.
   */
  public function clear() {
    $typecount = $this->getTypeIDCounterValue();
    $this->client->multi();
    for ($i = 1; $i <= $typecount; $i++) {
      $this->client->delete($this->key . ':logs:' . $i);
    }
    $this->client->delete($this->key . ':type');
    $this->client->delete($this->key . ':counters');
    $this->client->delete($this->key . ':recentlogs');
    $this->client->delete($this->key);
    if ($this->client->exec()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieve recent log entries from linked list.
   *
   * @return array
   */
  public function getRecentLogs() {
    $logs = [];
    $res = $this->client->lRange($this->key . ':recentlogs', 0, -1);
    foreach ($res as $entry) {
      $entry = unserialize($entry);
      $logs[] = $entry;
    }
    return $logs;
  }

  /**
   * Return all logs stored in redis. Be cautious of use. Performance impact.
   *
   * @return array
   */

  public function getAllMessages() {
    $types = $this->getMessageTypes();
    $logs = [];
    foreach ($types as $logid) {
      $curr = $this->client->lGetRange($this->key . ':logs:' . $logid, 0, -1);
      $logs = array_merge($logs, $curr);
    }
    return $logs;
  }

  /**
   * Return the number of logs for a given type.
   *
   * @param int $tid
   *  Type ID Number.
   *
   * @return int
   */
  public function getTypeCount($tid) {
    return $this->client->lLen($this->key . ':logs:' . $tid);
  }

  /**
   * Return multiple log entries for a specific log type.
   *
   * @param int $limit
   *  Limit of logs to return.
   *
   * @param int $tid
   *  ID number of the type to return.
   *
   * @param int $page
   *  The page being requested.
   *
   * @return array
   */
  public function getMultipleByType($limit = 50, $tid = NULL, $page = 0) {
    // Start point for the range.
    $start = (empty($page)) ? 0 : $limit * $page;
    // End point for the range.
    $end = $start + $limit;
    $logs = [];
    $types = [];
    if ($tid) {
      // @todo provide a range control.
      $res = $this->client->lRange($this->key . ':logs:' . $tid, $start, $end);
      foreach ($res as $entry) {
        $entry = unserialize($entry);
        $logs[] = $entry;
        if (!in_array($entry->type, $types)) {
          $types[] = $entry->type;
        }
      }
      $this->types = $types;
    }
    return $logs;
  }

  /**
   * Retrive multiple log entries.
   *
   * @param int $limit
   *
   * @return array
   */
  public function getMultiple($limit = 50) {
    $logs = [];
    $types = [];
    $max_wid = $this->getLogCounter();
    if ($max_wid) {
      if ($max_wid > $limit) {
        $keys = range($max_wid, $max_wid - $limit);
      }
      else {
        $keys = range($max_wid, 1);
      }

      $res = $this->client->hmGet($this->key, $keys);
      foreach ($res as $entry) {
        $entry = unserialize($entry);
        $logs[] = $entry;
        if (!in_array($entry->type, $types)) {
          $types[] = $entry->type;
        }
      }
      $this->types = $types;
    }
    return $logs;
  }

  /**
   * Return the count of messages per type
   *
   * @return array
   */
  public function getMessageTypesCounts() {
    $types = $this->getMessageTypes();
    if (empty($this->typescount)) {
      $this->typescount = [];
      foreach ($types as $typename => $id) {
        $this->typescount += [$typename => $this->client->lLen($this->key . ':logs:' . $id)];
      }
    }
    return $this->typescount;
  }

  /**
   * Retrieve a single log entry
   *
   * @param int $wid
   *  Log key ID number.
   *
   * @return bool|mixed
   */
  public function getSingle($wid) {
    $result = $this->client->hGet($this->key, $wid);
    return $result ? unserialize($result) : FALSE;
  }

  /**
   * Returns the value of the typeid counter. This will indicate the number of
   * types stored.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hget
   */
  protected function getTypeIDCounterValue() {
    return $this->client->hGet($this->key . ':counters', 'typeid');
  }

  /**
   * Returns a value to use for the type ID number.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hincrby
   */
  protected function getTypeIDCounter() {
    return $this->client->hIncrBy($this->key . ':counters', 'typeid', 1);
  }

  /**
   * Return the message types. Names only.
   *
   * @return array
   */
  public function getMessageTypes() {
    if (empty($this->types)) {
      $this->types = $this->client->hGetAll($this->key . ':type');
    }
    return $this->types;
  }

  /**
   * Returns the value of the counter.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hget
   */
  protected function getLogCounter() {

    return $this->client->hGet($this->key . ':counters', 'logs');
  }

  /**
   * Returns the value of the counter and pushes it up by 1 when called.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hincrby
   */
  protected function getPushLogCounter() {
    return $this->client->hIncrBy($this->key . ':counters', 'logs', 1);
  }

  /**
   * Destroys all Redis information. This function is static to be accessible
   * externally without substantiating the class.
   *
   * @todo this is currently broken
   *
   * @return bool
   */
  public static function redis_watchdog_redis_destroy() {

    $redis = new RedisWatchdog;

    $typecount = self::getTypeIDCounterValue();
    self::$client->multi();
    for ($i = 1; $i <= $typecount; $i++) {
      self::$client->delete(self::$key . ':logs:' . $i);
    }
    self::$client->delete(self::$key . ':type');
    self::$client->delete(self::$key . ':counters');
    self::$client->delete(self::key . ':recentlogs');
    self::$client->delete(self::$key);
    if (self::$client->exec()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}