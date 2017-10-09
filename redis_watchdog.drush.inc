<?php

/**
 * @file
 * Provide drush integration for Redis Watchdog.
 */

use Drupal\redis_watchdog\RedisWatchdog;

/**
 * Implementation of hook_drush_command().
 */

function redis_watchdog_drush_command() {
  $items = [];
  $items['redis-watchdog-export'] = [
    'description' => t('Export the Redis Watchdog logs to CSV'),
    'drupal dependencies' => ['redis_watchdog'],
    'aliases' => ['rwe'],
    'arguements' => [
      'filepath' => 'File path for the export.',
    ],
  ];
  $items['redis-watchdog-clear'] = [
    'description' => t('Clear the Redis memory'),
    'drupal dependencies' => ['redis_watchdog'],
    'aliases' => ['rwc'],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 */

function redis_watchdog_drush_help($section) {
  switch ($section) {
    case 'drush:redis-watchdog-export':
      return t('Export the logs in Redis to a CSV file. You must pass an argument to specify the path to the CSV to be created. Depending on the size of your logs, this operation could require a lot of time.');
    case 'drush:redis-watchdog-clear':
      return t('Clear all of the Redis memory of watchdog logs.');
  }
}

/**
 * Callback from the redis-watchdog-export command.
 */
function drush_redis_watchdog_export($filepath) {
  $redis = new RedisWatchdog();
  $data = $redis->exportCSV();
  $df = fopen($filepath, 'w');
  fwrite($df, $data);
  fclose($df);
}

/**
 * Callback from the redis-watchdog-clear command.
 */
function drush_redis_watchdog_clear() {
  RedisWatchdog::redis_watchdog_redis_destroy();
}