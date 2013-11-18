<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

use \components\message_board\models\Feeds;
use \components\message_board\models\FeedSources;

abstract class TwitterSourceHandler extends SourceHandler
{
  
  /**
   * Gets the API instance associated with this source.
   * @return mixed An API class that provides a connection to the associated service.
   */
  public function get_service_api()
  {
    
    return mk('Component')
      ->helpers('api_cache')
      ->call('access_service', array('name' => 'twitter-1.1'));
    
  }
  
}