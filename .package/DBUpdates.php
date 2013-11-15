<?php namespace components\message_board; if(!defined('MK')) die('No direct access.');

//Make sure we have the things we need for this class.
mk('Component')->check('update');

class DBUpdates extends \components\update\classes\BaseDBUpdates
{
  
  protected
    $component = 'message_board',
    $updates = array(
    );
  
  public function install_0_0_1_alpha($dummydata, $forced)
  {
    
    if($forced){
      mk('Sql')->query("DROP TABLE IF EXISTS `#__message_board__feeds`");
      mk('Sql')->query("DROP TABLE IF EXISTS `#__message_board__feed_sources`");
      mk('Sql')->query("DROP TABLE IF EXISTS `#__message_board__messages`");
      mk('Sql')->query("DROP TABLE IF EXISTS `#__message_board__message_webpages`");
      mk('Sql')->query("DROP TABLE IF EXISTS `#__message_board__messages_to_images`");
    }
    
    mk('Sql')->query("
      CREATE TABLE `#__message_board__feeds` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `max_items` int(10) unsigned NOT NULL,
        `source_priority` ENUM('CHRONOLOGICAL', 'ROUND_ROBIN') NOT NULL DEFAULT 'CHRONOLOGICAL',
        `message_order` ENUM('CHRONOLOGICAL', 'ROUND_ROBIN', 'RANDOM') NOT NULL DEFAULT 'CHRONOLOGICAL',
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ");
    
    mk('Sql')->query("
      CREATE TABLE `#__message_board__feed_sources` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `feed_id` int(10) unsigned NOT NULL,
        `type` ENUM('TWITTER_TIMELINE', 'TWITTER_SEARCH') NOT NULL,
        `query` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `feed_id` (`feed_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ");
    
    mk('Sql')->query("
      CREATE TABLE `#__message_board__messages` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `feed_source_id` int(10) unsigned NOT NULL,
        `dt_posted` TIMESTAMP NOT NULL,
        `author` varchar(255) NOT NULL,
        `content` TEXT NOT NULL,
        `remote_id` varchar(255) NOT NULL,
        `uri` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `feed_source_id` (`feed_source_id`),
        INDEX `dt_posted` (`dt_posted`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ");
    
    mk('Sql')->query("
      CREATE TABLE `#__message_board__message_webpages` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `message_id` int(10) unsigned NOT NULL,
        `title` varchar(255) NOT NULL,
        `content` TEXT NOT NULL,
        `uri` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `message_id` (`message_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ");
    
    mk('Sql')->query("
      CREATE TABLE `#__message_board__messages_to_images` (
        `message_id` int(10) unsigned NOT NULL,
        `image_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`message_id`, `image_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ");
    
    //Queue self-deployment with CMS component.
    $this->queue(array(
      'component' => 'cms',
      'min_version' => '0.4.1-beta'
      ), function($version){
        
        //Ensure the component and it's views.
        tx('Component')->helpers('cms')->_call('ensure_pagetypes', array(
          array(
            'name' => 'message_board',
            'title' => 'Message board'
          ),
          array(
            'feeds' => 'SETTINGS'
          )
        ));
        
      }
    ); //END - Queue CMS
    
  }
  
}

