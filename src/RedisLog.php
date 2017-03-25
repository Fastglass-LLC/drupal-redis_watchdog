<?php

/**
 * Class RedisLog
 *
 * Provide log functions to Drupal to replace DBlog.
 */
class RedisLog {
  protected $client;
  protected $key;
  protected $types = [];

  public function __construct($prefix = '') {
    $this->client = Redis_Client::getClient();
    // TODO: Need to support a site prefix here.
    if (!empty($prefix)){
      $this->key = 'drupal:watchdog:' . $prefix . ':';
    }
    else {
      $this->key = 'drupal:watchdog';
    }

  }

  /**
   * Create a log entry.
   *
   * @todo To create sortable data, the types need to be processed in this function
   * to save the type in the log creation process.
   *
   * @param array $log_entry
   *
   * @see https://github.com/phpredis/phpredis#hset
   */
  function log(array $log_entry) {
    // The user object may not exist in all conditions, so 0 is substituted if needed.
    $user_uid = isset($log_entry['user']->uid) ? $log_entry['user']->uid : 0;
    $wid = $this->getPushLogCounter();
    $message = [
      'wid' => $wid,
      'uid' => $user_uid,
      'type' => substr($log_entry['type'], 0, 64),
      'message' => $log_entry['message'],
      'variables' => serialize($log_entry['variables']),
      'severity' => $log_entry['severity'],
      'link' => substr($log_entry['link'], 0, 255),
      'location' => $log_entry['request_uri'],
      'referer' => $log_entry['referer'],
      'hostname' => substr($log_entry['ip'], 0, 128),
      'timestamp' => $log_entry['timestamp'],
    ];

    // Store types in a separate hash table to build the filters menu.
    $this->client->hSetNx($this->key . ':type', $message['type'], TRUE);

    $message = (object) $message;
    $this->client->hSet($this->key, $wid, serialize($message));
  }

  /**
   * Returns the value of the counter.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hincrby
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
    return $this->client->hIncrBy($this->key . ':counters' , 'logs', 1);
  }

  /**
   * Returns the value of the type counter and pushes it up by 1 when called.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hincrby
   */
  protected function getTypeCounter() {
    return $this->client->hGet($this->key . ':counters', 'types');
  }

  /**
   * Returns the value of the type counter and pushes it up by 1 when called.
   *
   * @return integer
   *
   * @see https://github.com/phpredis/phpredis#hincrby
   */
  protected function getPushTypeCounter() {
    return $this->client->hIncrBy($this->key . ':counters', 'types', 1);
  }

  /**
   * Return the message types.
   *
   * @return array
   */
  public function getMessageTypes() {
    if (empty($this->types)) {
      $this->types = array_keys($this->client->hGetAll($this->key . ':type'));
    }
    return $this->types;
  }

  /**
   * Retrieve a single log entry
   *
   * @param $wid
   *  Log key ID number.
   *
   * @return bool|mixed
   */
  public function getSingle($wid) {
    $result = $this->client->hGet($this->key, $wid);
    return $result ? unserialize($result) : FALSE;
  }

  /**
   * Retrive multiple log entries.
   *
   * @param int $limit
   * @param string $sort_field
   * @param string $sort_direction
   *
   * @return array
   */
  public function getMultiple($limit = 50, $sort_field = 'wid', $sort_direction = 'DESC') {
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
   * Clear all information from logs.
   */
  public function clear() {
    $this->client->multi();
    $this->client->delete($this->key . ':type');
    $this->client->delete($this->key . ':counters');
    $this->client->delete($this->key);
    $this->client->exec();
  }

}

