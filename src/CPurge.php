<?PHP
namespace HF\CacheHelper
{
  final class CPurge
  {
    public static function homepage()
    {
      if (function_exists('icl_get_home_url'))
        CServer::purgeURL(trailingslashit(icl_get_home_url()));
      else
        CServer::purgeURL(trailingslashit(home_url()));
    }

    public static function post($post_id)
    {
      $status = get_post_status($post_id);

      // if the post is not published, we need to guess it's URI
      if ($status === 'publish')
      {
        if (!function_exists('get_sample_permalink'))
          require_once ABSPATH . '/wp-admin/includes/post.php';

        $url = get_sample_permalink($post_id);
        $url = str_replace(['%postname%', '%pagename%'], $url[1], $url[0]);
      }
      else
        $url = get_permalink($post_id);

      if (empty($url))
        return;

      if ($status === 'trash')
        $url = str_replace('__trashed', '', $url);

      CServer::purgeURL($url);

      self::postArchives($post_id);
      self::postCategories($post_id);
      self::postTags($post_id);
      self::postAuthorArchives($post_id);
      self::customTaxonomies();
    }

    public static function postArchives($post_id)
    {
      $post_type = get_post_type($post_id);
      $url       = get_post_type_archive_link($post_type);
      if ($url)
        CServer::purgeURL($url);

      $types = get_post_types(['public' => true]);
      if (in_array($post_type, $types, true))
      {
        list($day, $month, $year) = explode('/', get_the_time('d/m/Y', $post_id), 3);
        if ($year)
        {
          CServer::purgeURL(get_year_link($year));
          if ($month)
          {
            CServer::purgeURL(get_month_link($year, $month));
            if ($day)
              CServer::purgeURL(get_day_link($year, $month, $day));
          }
        }
      }
    }

    public static function postCategories($post_id)
    {
      $categories = wp_get_post_categories($post_id);
      if (!is_wp_error($categories))
        foreach($categories as $id)
          CServer::purgeURL(get_category_link($id));
    }

    public static function allCategories()
    {
      foreach(get_categories() as $category)
        CServer::purgeURL(get_category_link($c->term_id));
    }

    public static function postTags($post_id)
    {
      $tags = get_the_tags($post_id);
      if (is_wp_error($tags) || empty($tags))
        return;

      foreach($tags as $tag)
        CServer::purgeURL(get_tag_link($tag->term_id));
    }

    public static function allPostTags()
    {
      foreach(get_tags() as $tag)
        CServer::purgeURL(get_tag_link($tag->term_id));
    }

    public static function postAuthorArchives($post_id)
    {
      $id = get_post($post_id)->post_author;
      if (!empty($id))
        CServer::purgeURL(get_author_posts_url($id));
    }

    public static function customTaxonomies()
    {
      $taxonomies = get_taxonomies([
        'public'   => true,
        '_builtin' => false,
      ]);

      $builtin = ['category', 'post_tag', 'link_category'];
      foreach($taxonomies as $taxon)
      {
        if (in_array($taxon, $builtin))
          continue;

        $terms = get_the_terms($post_id, $taxon);
        if (is_wp_error($terms) || empty($terms))
          continue;

        foreach($terms as $term)
          CServer::purgeURL(get_term_link($term, $taxon));
      }
    }
  }
}
?>