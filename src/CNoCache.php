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
        defined('REST_REQUEST')             ||
        $_SERVER['REQUEST_METHOD'] != 'GET' ||
        !empty($_SESSION)                   ||
        is_user_logged_in()                 ||
        $pagenow === 'wp-login.php'         ||
        $pagenow === 'wp-register.php'      ||
        is_admin()                          ||
        is_feed()                           ||
        self::wooCommerce()                 ||
        self::isSitemap()                   ||
        apply_filters('do_not_cache', false);
    }

    private static function wooCommerce()
    {
      if (!class_exists('WooCommerce'))
        return false;

      // never cache these pages
      if (
        is_cart               () ||
        is_checkout           () ||
        is_order_received_page() ||
        is_account_page       ())
      return true;

      // do not cache if there is anything in the cart
      return isset(WC()->session) &&
        WC()->session->has_session() &&
        !WC()->cart->is_empty();
    }

    private static function isSitemap()
    {
      // there doesn't seem to be an eloquent way to do this
      $current_url = $_SERVER['REQUEST_URI'];
      return preg_match('#\/[^/]*sitemap[^/]*\.xml$#', $current_url);
    }
  }
}
?>