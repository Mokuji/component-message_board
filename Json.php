<?php namespace components\message_board; if(!defined('TX')) die('No direct access.');

use \components\message_board\classes\FeedReader;

class Json extends \dependencies\BaseComponent
{
  
  public function get_feed_messages($options, $sub_routes)
  {
    
    $reader = new FeedReader($sub_routes->{0});
    return $reader->fetch();
    
  }
  
}
