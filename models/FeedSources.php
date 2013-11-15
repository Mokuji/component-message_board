<?php namespace components\message_board\models; if(!defined('MK')) die('No direct access.');

class FeedSources extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'message_board__feed_sources',
    
    $relations = array(
    ),
    
    $validate = array(
    );
  
  public function get_latest_message()
  {
    return mk('Sql')->table('message_board', 'Message')
      ->where('feed_source_id', $this->id)
      ->order('dt_posted')
      ->execute_single();
  }
  
}
