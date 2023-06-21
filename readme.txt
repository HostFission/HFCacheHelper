=== HostFission Cache Helper ===

Requires at least: 6.0
Tested up to     : 6.3
Stable tag       : 6.3
Requires PHP     : 7.4
License          : GPLv2
License URI      : http://www.gnu.org/licenses/gpl-2.0.html
Tags             : hostfission, cache
Contributors     : gnif

Official caching helper for users of the HostFission WordPress platform.

== Description ==

This plugin enables FastCGI caching of resources on the HostFission WordPress platform without breaking cached pages due to expired `nonce` values.

It also automatically clears the URIs for updated pages and provides a global `Purge Cache` link on the admin toolbar you can use if fails to purge a URI from the cache.

Note: Only install this plugin if you are using the HostFission WordPress hosting platform. It adds headers to the served pages, which are removed by HostFission HTTP servers before reaching the client. It also requires a specific server side configuration to operate, without this it does nothing.

== Installation ==

1. Upload [HF Cache Helper](https://github.com/HostFission/HFCacheHelper/archive/refs/heads/main.zip) to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Set up a `crontab` to execute `/wp-cron.php` periodically, preferably every 5 minutes.
4. [Optional] If you haven't done so already, add the following line to your wp-config.php file:
  `define('DISABLE_WP_CRON', true);`

== Frequently Asked Questions ==

- Why do I need this plugin?
If you are using the HostFission platform, this plugin allows you to take advantage of FastCGI caching without breaking URLs that use a nonce (e.g., forms and reset API functionality). If you are not using HostFission, this plugin is unnecessary and could pose a security risk.

- What does this plugin do?
This plugin hooks into the nonce generation process and records all the nonce action names and values used on the page. It sets a header called `X-HF-Nonce` with the nonce details, which is utilized by the HostFission platform. The header is stripped from the output and not sent to the client.

- Why do I need to use `crontab`?
If you do not use `crontab` the execution of `wp-cron.php` may be delayed, espesially as cached content will not trigger it to execute. The cron job writes a file called `.hf-nonce.php` in the `/wp-content/plugins/hf-cache-helper/` directory. This file contains the nonce values for all known action names used on your website. The HostFission platform relies on this file to update cached content before serving it to the client.

- How is the `.hf-nonce.php` file protected?
The `.hf-nonce.php` file is written as a hidden file (prefixed with a period) and has a PHP extension. If it is mistakenly served, the first entry in the file is a dummy PHP `die()` statement, preventing exposure of its contents.

== Changelog ==

= v1.0.0 =
* Initial Release
