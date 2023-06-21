<?PHP
namespace HF\CacheHelper
{
  final class CServer
  {
    private static $multi;
    private static $handles = [];
    private static $resolve;
    private static $pending  = [];
    private static $purgeAll = false;
    private static $running  = false;

    private static function init()
    {
      if (self::$multi)
        return;

      self::$multi = curl_multi_init();
      add_action('shutdown', [__CLASS__, 'purgeShutdown'], 10);

      $site = get_site_url();
      $host = preg_replace('#^https?://#', '', $site);
      self::$resolve = ["$host:443:127.0.0.1", "$host:80:127.0.0.1"];
    }

    public static function purgeShutdown()
    {
      $running = false;
      do
      {
        curl_multi_exec(self::$multi, $running);
        if ($running)
          curl_multi_select(self::$multi);
      }
      while($running);

      foreach(self::$handles as $curl)
      {
        curl_multi_remove_handle(self::$multi, $curl);
        curl_close($curl);
      }
      curl_multi_close(self::$multi);
    }

    private static function addPurge($uri)
    {
      self::init();
      $curl = self::$handles[] = curl_init(get_site_url() . $uri);
      curl_setopt_array($curl,
      [
        CURLOPT_CUSTOMREQUEST        => "PURGE",
        CURLOPT_FOLLOWLOCATION       => true,
        CURLOPT_RETURNTRANSFER       => true,
        CURLOPT_DNS_USE_GLOBAL_CACHE => false,
        CURLOPT_RESOLVE              => self::$resolve
      ]);
      curl_multi_add_handle(self::$multi, $curl);
    }

    public static function purgeAll()
    {
      // purge all makes individual URI purges useless
      // so remove any if they exist
      foreach(self::$handles as $curl)
      {
        curl_multi_remove_handle(self::$multi, $curl);
        curl_close($curl);
      }

      self::addPurge("/purgeall");

      // start execution immediately, there is no point waiting
      self::$purgeAll = true;
      curl_multi_exec(self::$multi, self::$running);
    }

    public static function purgeURI($uri)
    {
      if ($uri[0] != '/')
        $uri = '/' . $uri;

      if (self::$purgeAll)
        return;

      // prevent duplicate purges
      if (in_array($uri, self::$pending))
        return;
      self::$pending[] = $uri;

      self::addPurge("/purge" . $uri);
    }

    public static function purgeURL($url)
    {
      $uri = str_replace(get_site_url(), '', $url);
      if (empty($uri))
        $uri = '/';
      self::purgeURI($uri);
    }
  }
}
?>