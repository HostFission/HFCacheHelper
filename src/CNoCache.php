<?PHP
namespace HF\CacheHelper
{
  final class CNoCache
  {
    // returns true if we should not cache the page
    public static function check()
    {
      global $pagenow;

      return
        defined('XMLRPC_REQUEST')           ||
        defined('DOING_RPC')                ||
        $_SERVER['REQUEST_METHOD'] != 'GET' ||
        is_user_logged_in()                 ||
        $pagenow === 'wp-login.php'         ||
        $pagenow === 'wp-register.php'      ||
        is_admin()                          ||
        self::wooCommerce()                 ||
        apply_filters('do_not_cache', false);
    }

    // if WooCommerce is installed and has an active session
    private static function wooCommerce()
    {
      if (!class_exists('WooCommerce'))
        return false;

      return isset(WC()->session) &&
        WC()->session->has_session();
    }
  }
}
?>