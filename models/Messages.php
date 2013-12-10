<?php namespace components\message_board\models; if(!defined('MK')) die('No direct access.');

class Messages extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'message_board__messages',
    
    $relations = array(
    ),
    
    $validate = array(
    );
  
  public function get_webpages()
  {
    return mk('Sql')->table('message_board', 'MessageWebpages')
      ->where('message_id', $this->id)
      ->execute();
  }
  
  public function get_images()
  {
    return mk('Sql')->table('message_board', 'MessagesToImages')
      ->where('message_id', $this->id)
      ->join('Images', $I)
      ->execute($I);
  }
  
  public function save_webpages()
  {
    
    //Save all the pages provided.
    foreach($this->webpages as $webpage)
    {
      
      $webpage
        ->merge(array('message_id' => $this->id))
        ->save();
      
    }
    
    //Clear cache.
    $this->webpages->un_set();
    
    return $this;
    
  }
  
  public function save_images()
  {
    
    //Link all the images provided.
    foreach($this->images as $image)
    {
      
      //Store the link.
      mk('Sql')->model('message_board', 'MessagesToImages')
        ->set(array(
          'message_id' => $this->id,
          'image_id' => $image->id
        ))
        ->save();
      
    }
    
    //Clear cache.
    $this->images->un_set();
    
    return $this;
    
  }
  
}
