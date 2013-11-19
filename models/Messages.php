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
  
  public function save_webpages()
  {
    
    //Save all the pages provided.
    foreach($this->webpages as $webpage){
      try{
      $webpage
        ->merge(array('message_id' => $this->id))
        ->save();
        
      }catch(\Exception $ex){
        trace($ex);
      }
    }
    
    //Clear cache.
    $this->webpages->un_set();
    
    return $this;
    
  }
  
}
