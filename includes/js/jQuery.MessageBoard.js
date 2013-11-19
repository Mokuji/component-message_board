(function($){
  
  var requiredOpts = ['feed_url', 'template']
  
  var MessageBoard = function(options){
    
    //Check the required options.
    for(var i = 0; i < requiredOpts.length; i++){
      if(!options[requiredOpts[i]]){
        throw new Error('The required '+requiredOpts[i]+' is not provided in the MessageBoard options.');
      }
    }
    
    var $container = $(this);
    
    var template = function(message){
      
      if(!window.EJS)
        throw new Error('The EJS library is not available for MessageBoard templates.');
      
      //For debugging purposes.
      EJS.config({
        cache: false
      });
      
      var template;
      
      //Run template determining callbacks.
      if($.isFunction(options.template)){
        template = options.template(message);
      }
      
      //Just a normal template.
      else {
        template = options.template;
      }
      
      var engine = new EJS({url: template});
      return $(engine.render({message: message}));
      
    };
    
    $container.text('Loading...');
    
    //Get the feed contents.
    $.ajax(options.feed_url)
      
      .done(function(messages){
        $container.text('');
        for(var i = 0; i < messages.length; i++){
          $container.append( template(messages[i]) );
        }
      })
      
      .error(function(){
        throw new Error('Failed to fetch message feed for MessageBoard.');
      });
    
    return $container;
    
  };
  
  //Export this extension.
  $.fn.MessageBoard = MessageBoard;
  
})(jQuery);