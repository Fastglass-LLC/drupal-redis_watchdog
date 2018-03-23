# Redis Watchdog
Redis watchdog replacement for Drupal. This offloads Drupal logging into Redis
for performance.

Requirements
------------------------------------
Redis Watchdog module requires PHP 5.5 or greater. In this module we use special
class methods that were introduced in PHP 5.5.

To operate, the module requires that you have PHPRedis module installed on your
server's PHP.

Dependencies
------------------------------------
This module depends on the [Redis](https://www.drupal.org/project/redis) Drupal
module to supply the Redis client.

Installation
------------------------------------
Place the module in your Drupal website and enable the module. The reports will
be available at admin/reports/redislog in your site.

Uninstallation
------------------------------------
Uninstalling this module will remove data for the function of the module but your
data in Redis will remain.

Drush integration
------------------------------------
A drush command is provided to export the logs to a CSV file. Export is also
available via the UI but for large sites this process could take a while and
might be best executed in PHP CLI. The command to use the drush export is as
follows:

  `drush redis-watchdog-export <filename>`
  or `drussh rwe <filename>`
  