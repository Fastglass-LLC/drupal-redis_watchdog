<?php

/**
 * @file
 * Provide drush integration for Redis Watchdog
 */

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
  return $items;
}

/**
 * Implements hook_drush_help().
 */

function redis_watchdog_drush_help($section) {
  switch ($section) {
    case 'drush:redis-watchdog-export':
      return t('Export the logs in Redis to a CSV file');
  }
}


/**
 * Callback from the redis-watchdog-export command.
 */
function drush_redis_watchdog_export($filepath) {

}