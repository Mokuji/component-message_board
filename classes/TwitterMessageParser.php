<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

use \components\message_board\models\FeedSources;

class TwitterMessageParser
{
  
  protected $source;
  protected $raw_message;
  protected $model;
  protected $metadata;
  protected $webpages;
  
  public function __construct(FeedSources $source, $message)
  {
    
    $this->source = $source;
    $this->raw_message = $message;
    
  }
  
  public function getModel()
  {
    
    //Parsing it only once will do.
    if(isset($this->model))
      return $this->model;
    
    //Create the basic model.
    $this->model = mk('Sql')
      ->model('message_board', 'Messages')
      ->set($this->getMetadata())
      ->merge(array(
        'webpages' => $this->getWebpages()
      ));
    
    return $this->model;
    
  }
  
  public function getMetadata()
  {
    
    //Parsing it only once will do.
    if(isset($this->metadata))
      return $this->metadata;
    
    //Shorten the variables a bit.
    $src = $this->source;
    $msg = $this->raw_message;
    
    //Fetch the most basic information.
    $this->metadata = Data(array(
      'feed_source_id' => $src->id,
      'dt_posted' => date('Y-m-d H:i:s', strtotime($msg->created_at)),
      'author' => '@'.$msg->user->screen_name,
      'content' => $msg->text,
      'remote_id' => $msg->id_str,
      'uri' => "https://twitter.com/{$msg->user->screen_name}/status/{$msg->id_str}",
    ));
    
    return $this->metadata;
    
  }
  
  public function getWebpages()
  {
    
    //Parsing it only once will do.
    if(isset($this->webpages))
      return $this->webpages;
    
    load_plugin('readability');
    
    $webpages = array();
    foreach($this->raw_message->entities->urls as $url)
    {
      
      $curl = curl_call($url->expanded_url);
      
      //Only HTML pages are ok.
      $type = explode(';', $curl['type']);
      $type = trim($type[0]);
      if($type !== 'text/html' && $type !== 'application/xhtml+xml'){
        mk('Logging')->log('Webpage', 'Type based skip', $type.' ['.$curl['type'].']');
        continue;
      }
      
      $reader = new \Readability($curl['data']);
      $content = $reader->getContent();
      
      $webpage = mk('Sql')
        ->model('message_board', 'MessageWebpages')
        ->set(array(
          'title' => str_max($content['title'], 252, '...'),
          'content' => $content['content'],
          'uri' => $url->expanded_url
        ));
      
      $webpages[] = $webpage;
      
    }
    
    $this->webpages = Data($webpages);
    
    return $this->webpages;
    
  }
  
}