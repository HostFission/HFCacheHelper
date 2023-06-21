<?PHP
namespace HF\CacheHelper
{
  final class CActions
  {
    public static function init()
    {
      add_action('wp_insert_comment'        , [__CLASS__, 'onInsertComment'          ], 200, 2);
      add_action('transition_comment_status', [__CLASS__, 'onTransitionCommentStatus'], 200, 3);
      add_action('transition_post_status'   , [__CLASS__, 'onTransitionPostStatus'   ], 200, 3);
      add_action('edit_post'                , [__CLASS__, 'onEditPost'               ], 200, 2);
      add_action('delete_post'              , [__CLASS__, 'onDeletePost'             ], 200, 2);
      add_action('edit_attachment'          , [__CLASS__, 'onEditAttachment'         ], 200, 1);
      add_action('edit_term'                , [__CLASS__, 'onEditTerm'               ], 200, 4);
      add_action('delete_term'              , [__CLASS__, 'onDeleteTerm'             ], 200, 5);
    }

    public static function onInsertComment($comment_id, $comment)
    {
      CPurge::post($comment->comment_post_ID);
    }

    public static function onTransitionCommentStatus($new_status, $old_status, $comment)
    {
      CPurge::post($comment->comment_post_ID);
    }

    public static function onTransitionPostStatus($new_status, $old_status, $post)
    {
      CPurge::post($post->ID);
    }

    public static function onEditPost($post_id, $post)
    {
      CPurge::post($post_id);
    }

    public static function onDeletePost($post_id, $post)
    {
      CPurge::post($post_id);
    }

    public static function onEditAttachment($post_id)
    {
      CPurge::post($post_id);
    }

    public static function onEditTerm($term_id, $tt_id, $taxonomy, $args)
    {
      CPurge::homepage();
    }

    public static function onDeleteTerm($term, $tt_id, $taxonomy, $deleted_term, $object_ids)
    {
      CPurge::homepage();
    }
  }
}
?>