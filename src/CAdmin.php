<?PHP
namespace HF\CacheHelper
{
  final class CAdmin
  {
    public static function init()
    {
      add_action('admin_bar_menu', [__CLASS__, 'onAdminBarMenu'], 200, 1);
      add_action('admin_init'    , [__CLASS__, 'onAdminInit'   ]);
    }

    public static function onAdminBarMenu($wp_admin_bar)
    {
      if (!current_user_can('manage_options'))
        return;

      $href = add_query_arg(['hf_cache_helper_action' => 'purge']);
      $href = wp_nonce_url($href, 'hf_cache_helper-purge');

      $wp_admin_bar->add_menu([
        'id'    => 'hf-cache-helper-purge',
        'title' => 'Purge Cache',
        'href'  => $href,
        'meta'  => __('Purce Cache', 'hf-cache-helper')
      ]);
    }

    public static function onAdminInit()
    {
      $action = $_GET['hf_cache_helper_action'] ?? false;
      if ($action !== 'purge')
        return;

      if (!current_user_can('manage_options'))
        return;

      CServer::purgeAll();
    }
  }
}
?>