<?PHP
/*
 Plugin Name: HostFission Cache Helper
 Plugin URI: https://hostfission.com
 Description: Works with the HostFission infrastructure to accelerate your website
 Version: 1.0
 Author: HostFission
 Author URI: https://hostfission.com
 License: GPLv2
 */

namespace HF\CacheHelper
{
  require_once(__DIR__ . '/src/CServer.php');
  require_once(__DIR__ . '/src/CPurge.php');
  require_once(__DIR__ . '/src/CActions.php');
  require_once(__DIR__ . '/src/CAdmin.php');
  require_once(__DIR__ . '/src/CNoCache.php');

  final class Plugin
  {
    private static $actions   = [];
    private static $internal  = false;
    private static $buffered  = false;
    private static $activated = false;

    public static function init()
    {
      register_activation_hook  (__FILE__, [__CLASS__, 'activate'  ]);
      register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);

      add_action('template_redirect'     , [__CLASS__, 'buffer'  ]);
      add_action('nonce_user_logged_out' , [__CLASS__, 'nonce'   ], 10, 2);
      add_action('shutdown'              , [__CLASS__, 'shutdown'], 0);

      // setup a 5 minute cron job
      add_filter('cron_schedules', function($schedules)
      {
        if (array_key_exists('5min', $schedules))
          return $scheduled;

        $schedules['5min'] =
        [
          'interval' => 300,
          'display'  => __( 'Every Five Minutes' )
        ];

        return $schedules;
      });
      add_action('hf_cache_helper_cron', [__CLASS__, 'cron']);

      CActions::init();
      CAdmin  ::init();
    }

    public static function activate()
    {
      global $wpdb;
      $table_name = $wpdb->prefix . 'hf_cache_helper';
      $charset    = $wpdb->get_charset_collate();

      // we must purge the entire cache when activating as cached resources
      // will be missing the X-HF-Nonce header
      CServer::purgeAll();

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      $sql = "CREATE TABLE $table_name (" .
        "action VARCHAR(64) NOT NULL, " .
        "nonce CHAR(10) NOT NULL, ".
        "PRIMARY KEY(action)".
      ") $charset";
      dbDelta($sql);

      if (!wp_next_scheduled('hf_cache_helper_cron'))
        wp_schedule_event(time(), '5min', 'hf_cache_helper_cron');

      self::$activated = true;
    }

    public static function deactivate()
    {
      global $wpdb;
      wp_clear_scheduled_hook('hf_cache_helper_cron');

      $table_name = $wpdb->prefix . 'hf_cache_helper';
      $wpdb->query("DROP TABLE IF EXISTS $table_name");

      // we must remove the file to stop the HTTP frontend serving cached requests
      // if we don't they will be served with stale/expired nonce values
      if (file_exists(__DIR__ . '/.hf-nonce.php'))
        unlink(__DIR__ . '/.hf-nonce.php');

      // be nice and clear the cache
      CServer::purgeAll();
    }

    public static function buffer()
    {
      ob_start();
      self::$buffered = true;
    }

    public static function nonce($uid, $action)
    {
      if ($uid            != 0 || // do not record logged in user nonces
          self::$internal      || // prevent recursion
          array_key_exists($action, self::$actions)) //prevent duplicates
        return;

      self::$internal = true;
      self::$actions[$action] = wp_create_nonce($action);
      self::$internal = false;
    }

    public static function shutdown()
    {
      global $wpdb;

      remove_action('nonce_user_logged_out', [__CLASS__, 'nonce']);
      $data = [];
      foreach(self::$actions as $name => $value)
      {
        $data[] = $name . '|' . $value;
        $stmt = $wpdb->prepare(
          "INSERT INTO "  . $wpdb->prefix . "hf_cache_helper (action, nonce) " .
          "VALUES (%s, %s) ON DUPLICATE KEY UPDATE nonce = VALUES(nonce)",
          [$name, $value]);
        $wpdb->query($stmt);
      }

      if (!headers_sent() && !CNoCache::check())
      {
        // tell the backend to cache this page and provide the nonce data (if any)
        header("X-HF-Cache: 1");
        if (!empty($data))
          header("X-HF-Nonce: " . implode('|', $data));
      }

      if (self::$buffered)
        ob_end_flush();

      // do the first cron run to enable the caching
      if (self::$activated)
        self::cron();
    }

    public static function cron()
    {
      global $wpdb;
      $actions = $wpdb->get_results("SELECT action FROM " . $wpdb->prefix . "hf_cache_helper");
      $out     = ['<?php die() ?>|'];
      foreach($actions as $row)
        $out[] = $row->action . '|' . wp_create_nonce($row->action);
      file_put_contents(__DIR__ . '/.hf-nonce.php', implode('|', $out));
    }
  }

  Plugin::init();
}
?>