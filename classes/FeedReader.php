<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

class FeedReader
{
  
  protected
    $feed;
  
  public function __construct($feed_id)
  {
    
    //Gets the feed model.
    $this->feed = mk('Sql')->table('message_board', 'Feeds')
      ->pk($feed_id)
      ->execute_single()
      ->is('empty', function(){
        throw new \exception\NotFound('No feed with this ID.');
      });
    
  }
  
  /**
   * Fetches the feed messages in their specified priority and ordering.
   * @param  boolean $update  Whether or not to update the sources first.
   * @param  mixed   $options A set of options to manipulate the output.
   * @return array A set of messages.
   */
  public function fetch($update=true, $options=null)
  {
    
    $options = Data($options);
    
    //When requested, runs an update first.
    if($update === true)
      $this->update();
    
    //Collect the set of messages to work with per source.
    $messages_per_source = array();
    $exclude_sources = $options->exclude_sources->otherwise(array())->as_array();
    
    foreach($this->feed->sources as $source){
      
      //Skip sources that are to be filtered.
      if(in_array($source->id->get(), $exclude_sources))
        continue;
      
      //Gets the messages for this source.
      $messages_per_source[$source->id->get()] = mk('Sql')->table('message_board', 'Messages')
        ->where('feed_source_id', $source->id)
        ->order('dt_posted', 'DESC')
        ->execute()
        ->get('array');
      
    }
    
    if(count($messages_per_source) == 0)
      return array();
    
    //Merge the results based on the selected feed priority.
    $message_set = array();
    switch($this->feed->source_priority->get('string')){
      
      case 'CHRONOLOGICAL':
        
        for($size = 0; $size < $this->feed->max_items->get('int'); $size++){
          
          //The index of the pile that has the most recent message.
          $latest = null;
          
          foreach($messages_per_source as $index => $pile){
            
            //If this source has no (more) messages, skip this source.
            if(count($pile) == 0)
              continue;
            
            //If there is no latest message yet, set it for sure.
            if($latest === null)
              $latest = $index;
            
            //Otherwise compare the best known latest with the current pile.
            else{
              
              $best_known = $messages_per_source[$latest][0];
              $current = $pile[0];
              
              if(strtotime($current->dt_posted->get('string')) > strtotime($best_known->dt_posted->get('string')))
                $latest = $index;
              
            }
            
          }
          
          //Check if we found anything at all.
          if($latest === null)
            break; //End of the for-loop.
          
          //If we did, move the message from the pile to the message set.
          $message_set[] = array_shift($messages_per_source[$latest]);
          
        }
        
        break;
      
      case 'ROUND_ROBIN':
        
        //See what our modulus should be.
        $mod = count($messages_per_source);
        $keys = array_keys($messages_per_source);
        
        //Shift the messages in order.
        for($size = 0; $size < $this->feed->max_items->get('int'); $size++){
          $key = $keys[$size % $mod];
          $value = array_shift($messages_per_source[$key]);
          if($value)
            $message_set[] = $value;
        }
        
        break;
      
    }
    
    //Now order this message set the way it should be.
    $messages = array();
    switch($this->feed->message_order->get('string')){
      
      case 'RANDOM':
        shuffle($message_set);
        $messages = $message_set;
        break;
      
      case 'ROUND_ROBIN':
        
        //When we have round robin priority, the order should be correct already.
        if($this->feed->source_priority->get('string') == 'ROUND_ROBIN'){
          $messages = $message_set;
          break;
        }
        
        //First make a collection of messages per source.
        $message_sets_per_source = array();
        foreach($message_set as $message){
          if(!array_key_exists($message->feed_source_id->get('int'), $message_sets_per_source))
            $message_sets_per_source[$message->feed_source_id->get('int')] = array();
          $message_sets_per_source[$message->feed_source_id->get('int')][] = $message;
        }
        
        //Than round robin over them into the final set.
        $items = count($message_set);
        $keys = array_keys($message_sets_per_source);
        for($size = 0; $size < $items; $size++){
          
          //Find the next source.
          $target = $keys[$size % count($message_sets_per_source)];
          
          //Shift a message.
          $messages[] = array_shift($message_sets_per_source[$target]);
          
          //Remove the source when it's depleted.
          if(count($message_sets_per_source[$target]) == 0){
            unset($message_sets_per_source[$target]);
            $keys = array_keys($message_sets_per_source);
          }
          
        }
        
        break;
        
      case 'CHRONOLOGICAL':
        
        //When we have chronological priority, the order should be correct already.
        if($this->feed->source_priority->get('string') == 'CHRONOLOGICAL'){
          $messages = $message_set;
          break;
        }
        
        //In other cases we need to sort it ourselves.
        usort($message_set, function($a, $b){
          
          $ta = strtotime($a->dt_posted->get('string'));
          $tb = strtotime($b->dt_posted->get('string'));
          
          if($ta == $tb)
            return 0;
          
          if($ta > $tb)
            return -1;
          
          else
            return 1;
          
        });
        
        $messages = $message_set;
        
        break;
      
    }
    
    return $messages;
    
  }
  
  /**
   * Checks for new messages with each source and limits the database size to (feed:max_items) per feed source.
   * @return void
   */
  public function update()
  {
    
    foreach($this->feed->sources as $source){
      
      //Find the right source handler.
      switch($source->type->get()){
        
        case 'TWITTER_TIMELINE':
          $handler = new TwitterTimelineSourceHandler($source);
          break;
          
        case 'TWITTER_SEARCH':
          $handler = new TwitterSearchSourceHandler($source);
          break;
        
      }
      
      //Query for new messages.
      $messages = $handler->query();
      
      //Save those messages.
      mk('Logging')->log('Message board', 'New messages', $messages->size());
      foreach($messages as $message){
        $message
          ->save()
          ->save_webpages();
      }
      
    }
    
  }
  
}