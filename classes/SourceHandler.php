<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

use \components\message_board\models\Feeds;
use \components\message_board\models\FeedSources;

abstract class SourceHandler
{
  
  protected $source;
  
  /**
   * Creates a new SourceHandler instance.
   * @param  FeedSources $source The source for which to run queries.
   */
  public function __construct(FeedSources $source)
  {
    $this->source = $source;
  }
  
  /**
   * Gets the API instance associated with this source.
   * @return mixed An API class that provides a connection to the associated service.
   */
  abstract public function get_service_api();
  
  /**
   * Queries for new items and parses them into the normalized format.
   * @return \dependencies\Data A set of messages that are normalized.
   */
  abstract public function query();
  
}