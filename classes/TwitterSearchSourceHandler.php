<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

class TwitterSearchSourceHandler extends TwitterSourceHandler
{
  
  /**
   * Queries for new items and parses them into the normalized format.
   * @return \dependencies\Data A set of messages that are normalized.
   */
  public function query()
  {
    
    $api = $this->get_service_api();
    
    $result = json_decode(
      $api->search(array(
        'q' => $this->source->query->get(),
        'since_id' => $this->source->get_latest_message()->remote_id->get(),
        'count' => $this->source->feed->max_items->get()
      ))
      ->get('string')
    );
    
    $new_messages = $result->statuses;
    
    $message_models = array();
    
    if(count($new_messages) > 0){
      
      foreach($new_messages as $message){
        $parser = new TwitterMessageParser($this->source, $message);
        $message_models[] = $parser->getModel();
      }
      
    }
    
    return Data($message_models);
    
  }
  
}