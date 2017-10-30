<?php
namespace Drupal\redis_watchdog\Commands;

use Drush\Commands\DrushCommands;

/**
 *
 * In addition to a commandfile like this one, you need a drush.services.yml
 * in root of your module.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class RedisWatchdogCommands extends DrushCommands {

  /**
   * Export the Redis Watchdog logs to CSV
   *
   * @command redis:watchdog:export
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @validate-module-enabled redis_watchdog
   * @aliases rwe,redis-watchdog-export
   */
  public function watchdogExport()
  {
      // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
      // legacy command.
  }

  /**
   * Clear the Redis memory
   *
   * @command redis:watchdog:clear
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @validate-module-enabled redis_watchdog
   * @aliases rwc,redis-watchdog-clear
   */
  public function watchdogClear()
  {
      // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
      // legacy command.
  }


}
