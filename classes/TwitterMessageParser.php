<?php namespace components\message_board\classes; if(!defined('MK')) die('No direct access.');

use \components\message_board\models\FeedSources;

class TwitterMessageParser
{
  
  protected $source;
  protected $raw_message;
  protected $model;
  protected $metadata;
  protected $webpages;
  protected $images;
  
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
        'webpages' => $this->getWebpages(),
        'images' => $this->getImages()
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
    
    //Add trailing space for easier replacing.
    $msg->text .= ' ';
    
    //Link elements in the tweet content.
    $content = $msg->text;
    
    if(isset($msg->entities))
    {
      
      //Replace URL's?
      if(isset($msg->entities->urls))
      foreach($msg->entities->urls as $url)
      {
        
        $link = '<a href="'.($url->expanded_url ? $url->expanded_url : $url->url).'" target="_blank">'.
          ($url->display_url ? $url->display_url : $url->url).
          '</a>';
        
        $content = str_replace($url->url, $link, $content);
        
      }
      
      //Link media?
      if(isset($msg->entities->media))
      foreach($msg->entities->media as $url)
      {
        
        $link = '<a href="'.($url->expanded_url ? $url->expanded_url : $url->url).'" target="_blank">'.
          ($url->display_url ? $url->display_url : $url->url).
          '</a>';
        
        $content = str_replace($url->url, $link, $content);
        
      }
      
      //Replace hashtags?
      if(isset($msg->entities->hashtags))
      foreach($msg->entities->hashtags as $hashtag)
      {
        
        //Find the trailing character, to prevent overlap with replacing.
        $tc = $msg->text[$hashtag->indices[1]];
        
        $link = 
          '<a href="https://twitter.com/search?q=%23'.$hashtag->text.'" target="_blank">'.'#'.$hashtag->text.'</a>'.$tc;
        
        $content = str_replace('#'.$hashtag->text.$tc, $link, $content);
        
      }
      
      //Replace mentions?
      if(isset($msg->entities->user_mentions))
      foreach($msg->entities->user_mentions as $mention)
      {
        
        //Find the trailing character, to prevent overlap with replacing.
        $tc = $msg->text[$mention->indices[1]];
        
        $link = 
          '<a href="https://twitter.com/'.$mention->screen_name.'" target="_blank">'.'@'.$mention->screen_name.'</a>'.$tc;
        
        $content = str_replace('@'.$mention->screen_name.$tc, $link, $content);
        
      }
      
    }
    
    //Fetch the most basic information.
    $this->metadata = Data(array(
      'feed_source_id' => $src->id,
      'dt_posted' => date('Y-m-d H:i:s', strtotime($msg->created_at)),
      'author' => '@'.$msg->user->screen_name,
      'content' => trim($content),
      'remote_id' => $msg->id_str,
      'uri' => "https://twitter.com/{$msg->user->screen_name}/status/{$msg->id_str}",
    ));
    
    return $this->metadata;
    
  }
  
  public function getImages()
  {
    
    //Parsing it once will do.
    if(isset($this->images))
      return $this->images;
    
    //We need this one now.
    mk('Component')->check('media');
    
    $images = array();
    if(isset($this->raw_message->entities->media))
    foreach($this->raw_message->entities->media as $media)
    {
      
      //Since image downloads and processing may take a while, give the script some time.
      set_time_limit(20);
      
      try{
        
        $images[] = mk('Component')->helpers('media')
          ->call('download_remote_image', array('url'=>$media->media_url));
        
      } catch (\Exception $ex){
        mk('Logging')->log('MessageBoard', 'Image', 'Download / Processing failed: '.$ex->getMessage());
      }
      
    }
    
    $this->images = Data($images);
    
    return $this->images;
    
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
      
      //Since CURL calls may take a while, give the script some time.
      set_time_limit(20);
      
      try{
        
        $curl = curl_call($url->expanded_url);
        
        //Check for 200 status.
        if(isset($curl['status']) && $curl['status'] !== 200){
          mk('Logging')->log('MessageBoard', 'Webpage', 'Status based skip: '.$curl['status']);
          continue;
        }
        
        //Only HTML pages are ok.
        $type = explode(';', $curl['type']);
        $type = trim($type[0]);
        if($type !== 'text/html' && $type !== 'application/xhtml+xml'){
          mk('Logging')->log('MessageBoard', 'Webpage', 'Type based skip: '.$curl['type']);
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
        
      } catch (\Exception $ex){
        mk('Logging')->log('MessageBoard', 'Webpage', 'CURL / Parsing failed: '.$ex->getMessage());
      }
      
    }
    
    $this->webpages = Data($webpages);
    
    return $this->webpages;
    
  }
  
}