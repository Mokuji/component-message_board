<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

use \components\message_board\models\Feeds;
use \components\message_board\models\FeedSources;

class TwitterTimelineSourceHandler extends TwitterSourceHandler
{
  
  /**
   * Queries for new items and parses them into the normalized format.
   * @return \dependencies\Data A set of messages that are normalized.
   */
  public function query()
  {
    
    $api = $this->get_service_api();
    
    $new_messages = json_decode(
      $api->user_timeline(array(
        'screen_name' => $this->source->query->get(),
        'since_id' => $this->source->get_latest_message()->remote_id->get(),
        'count' => $this->source->feed->max_items->get()
      ))
      ->get('string')
    );
    
    $message_models = array();
    
    if(count($new_messages) > 0){
      
      foreach($new_messages as $message){
        
        $mmodel = mk('Sql')->model('message_board', 'Messages')
          ->set(array(
            'feed_source_id' => $this->source->id,
            'dt_posted' => date('Y-m-d H:i:s', strtotime($message->created_at)),
            'author' => '@'.$message->user->screen_name,
            'content' => $message->text,
            'remote_id' => $message->id_str,
            'uri' => "https://twitter.com/{$message->user->screen_name}/status/{$message->id_str}"
          ));
        
        $message_models[] = $mmodel;
        
      }
      
    }
    
    return Data($message_models);
    
  }
  
}