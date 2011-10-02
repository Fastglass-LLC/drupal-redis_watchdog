<?php
class RedisLog {
  protected $client;
  protected $key;
  protected $types = array();

  public function __construct() {
    $this->client = Redis_Client::getClient();
    // TODO: Need to support a site prefix here.
    $this->key = 'drupal:watchdog';
  }

  /**
   * Implements hook_watchdog().
   */
  function log(array $log_entry) {
    // The user object may not exist in all conditions, so 0 is substituted if needed.
    $user_uid = isset($log_entry['user']->uid) ? $log_entry['user']->uid : 0;
    $wid = $this->getId();
    $message = array(
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
    );
    $message = (object) $message;
    $this->client->hSet($this->key, $wid, serialize($message));
  }


  protected function getId() {
    return $this->client->hIncrBy($this->key, 'counter', 1);
  }

  public function getMessageTypes() {
    if (empty($this->types)) {
      $types = $this->client->get($this->key . ':types');
      $this->types = unserialize($types);
    }
    return $this->types;
  }

  public function get($wid) {
    $result = $this->client->hGet($this->key, $wid);
    return $result ? unserialize($result) : FALSE;
  }

  public function getMultiple($limit = 50, $sort_field = 'wid', $sort_direction = 'DESC') {
    $logs = array();
    $types = array();
    $max_wid = $this->client->hGet($this->key, 'counter');
    $keys = range($max_wid, $max_wid - $limit);
    $res = $this->client->hmGet($this->key, $keys);
    foreach ($res as $entry) {
      $entry = unserialize($entry);
      $logs[] = $entry;
      if (!in_array($entry->type, $types)) {
        $types[] = $entry->type;
      }
    }
    $this->types = $types;

    $this->client->set($this->key . ':types', serialize($types));
    return $logs;
  }
}
