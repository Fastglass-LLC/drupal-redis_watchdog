# Redis Watchdog
Redis watchdog replacement for Drupal. This offloads Drupal logging into Redis
for performance.


Dependencies
------------------------------------
This module depends on the [Redis](https://www.drupal.org/project/redis) Drupal
module to supply the Redis client.

Installation
------------------------------------
Place the module in your Drupal website and enable the module. The reports will
be available at admin/reports/redislog in your site.