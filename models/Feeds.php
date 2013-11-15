<?php namespace components\message_board\models; if(!defined('MK')) die('No direct access.');

class Feeds extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'message_board__feeds',
    
    $relations = array(
    ),
    
    $validate = array(
    );
  
  public function get_sources()
  {
    
    return mk('Sql')->table('message_board', 'FeedSources')
      ->where('feed_id', $this->id)
      ->execute();
    
  }
  
}
