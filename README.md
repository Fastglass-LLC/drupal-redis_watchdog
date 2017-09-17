# Redis Watchdog
Redis watchdog replacement for Drupal. This offloads Drupal logging into Redis
for performance.

Requirements
------------------------------------
Redis Watchdog module requires PHP 7.0 or greater. Although Drupal 8 requires
PHP 5.6 we use type casting in method calls that was only implemented in 
PHP 7.0. Type casting allows strict control over a method in a class.

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

PHP Memory Settings
------------------------------------
If your site has generated a lot of logs you could run into a memory issue with
PHP on your server. If the recent logs page is crashing with a 500 error, you
may be exceeding the memory allowed by the PHP installation on your web server.
The reason is the amount of data Redis is attempting to retrieve is very large
to fix this you have to do one of two things; increase memory to PHP for a
single script (be careful with this setting) or export your logs to CSV then clear
redis with Drush. To prevent this from happening, set your log limits in the
module to something reasonable.

Pagination of the logs is needed to help fix this problem, but it has not been
implemented yet.

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
  