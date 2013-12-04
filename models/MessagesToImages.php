<?php namespace components\message_board\models; if(!defined('MK')) die('No direct access.');

class MessagesToImages extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'message_board__messages_to_images',
    
    $relations = array(
      'Images' => array('image_id' => 'media.Images.id')
    ),
    
    $validate = array(
    );
  
}
