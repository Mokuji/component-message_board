<?php namespace components\message_board; if(!defined('TX')) die('No direct access.');

use \components\message_board\classes\FeedReader;

class Json extends \dependencies\BaseComponent
{
  
  protected $permissions = array(
    'get_feed_messages' => 0,
    'get_feed_sources' => 0
  );
  
  public function get_feed_sources($options, $sub_routes)
  {
    
    //No reason we can't do this in parallel.
    mk('Session')->close();
    
    return mk('Sql')->table('message_board', 'FeedSources')
      ->where('feed_id', $sub_routes->{0})
      ->execute();
    
  }
  
  public function get_feed_messages($options, $sub_routes)
  {
    
    /*
      Note: 
        The session is preserved here.
        Because if an update is required, one user can perform many parallel requests that each trigger the update.
        This would potentially be a DOS attack.
    */
    
    #TODO: To mitigate a DDOS-attack against feeds that need to update, mark a feeds' state as 'IDLE' or 'UPDATING' in the database.
    
    $reader = new FeedReader($sub_routes->{0});
    $messages = $reader->fetch(true, $options);
    
    //Include custom getter stuff.
    foreach($messages as $message){
      $message->webpages;
    }
    
    return $messages;
    
  }
  
}
