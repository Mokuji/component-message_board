<?php namespace components\message_board\models; if(!defined('MK')) die('No direct access.');

class Messages extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'message_board__messages',
    
    $relations = array(
    ),
    
    $validate = array(
    );
  
}
