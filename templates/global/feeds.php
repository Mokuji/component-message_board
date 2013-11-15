<?php namespace components\message_board; if(!defined('MK')) die('No direct access.');

__($names->component, 'FEEDS_VIEW_DESCRIPTION');

echo $data->feeds->as_table(array(
  __('Title', 1) => 'title',
  __($names->component, 'Sources', 1) => function($feed)use($names){
    
    return $feed->sources
      ->map(function($source)use($names){
        return __($names->component, $source->type, 1).': '.$source->query.br;
      })
      ->otherwise(__($names->component, 'None', 1));
    
  },
  __($names->component, 'Max. items', 1) => 'max_items',
  __($names->component, 'Source priority', 1) => function($feed)use($names){
    return __($names->component, $feed->source_priority->get(), 1);
  },
  __($names->component, 'Message order', 1) => function($feed)use($names){
    return __($names->component, $feed->message_order->get(), 1);
  }
));