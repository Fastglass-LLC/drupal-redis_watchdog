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