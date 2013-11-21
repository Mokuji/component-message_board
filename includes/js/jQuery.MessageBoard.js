(function($){
  
  var requiredOpts = ['feed_url', 'messageTemplate', 'layoutTemplate']
  var defaultOpts = {
    itemsPerPage: 4
  };
  
  var MessageBoard = function(Options){
    
    //A collection of data.
    var Data = {
      messages: null,
      messagesById: null,
      numPages: null,
      currentPage: null
    };
    
    //References to various DOM elements.
    var DOM = {
      container: null,
      layout: null,
      messages: null
    };
    
    //Templating helper.
    function template(key, data)
    {
      
      if(!window.EJS)
        throw new Error('The EJS library is not available for MessageBoard templates.');
      
      //For debugging purposes.
      EJS.config({
        cache: false
      });
      
      var template;
      
      //Run template determining callbacks.
      if($.isFunction(Options[key])){
        template = Options[key](data);
      }
      
      //Just a normal template.
      else {
        template = Options[key];
      }
      
      var engine = new EJS({url: template});
      return $(engine.render({data: data}));
      
    }
    
    //Initializes the message board.
    function __initialize(container)
    {
      
      //Check the required Options.
      for(var i = 0; i < requiredOpts.length; i++){
        if(!Options[requiredOpts[i]]){
          throw new Error('The required '+requiredOpts[i]+' is not provided in the MessageBoard options.');
        }
      }
      
      //Add the default options.
      Options = $.extend({}, defaultOpts, Options);
      
      //Prepare the layout.
      DOM.container = $(container);
      DOM.layout = template('layoutTemplate', {
        pagination:{
          visible: true,
          showArrows: true,
          prevArrowText: '&lt;',
          nextArrowText: '&gt;'
        }
      });
      DOM.messages = DOM.layout.find('.mb-messages');
      
      //Set a loading text.
      DOM.messages.text('Loading...');
      
      //Actually add the layout to the DOM.
      DOM.container.append(DOM.layout);
      
      //Bind events on the DOM.
      addEventHandlers();
      
      //Get the feed contents.
      reloadFeed();
      
    }
    
    //Binds events on the DOM.
    function addEventHandlers()
    {
      
      //Micro level.
      DOM.messages
        
        //Message click.
        .on('click', '.mb-message', function(e){
          e.preventDefault();
          focusMessage(this);
        })
        
        //Unfocus button click.
        .on('click', '.mb-unfocus', function(e){
          e.preventDefault();
          e.stopPropagation();
          unfocusMessage();
        });
      
      //Macro level.
      DOM.layout
        
        //Click prev page button.
        .on('click', '.mb-prev-page', function(e){
          e.preventDefault();
          prevPage();
        })
        
        //Click next page button.
        .on('click', '.mb-next-page', function(e){
          e.preventDefault();
          nextPage();
        });
      
      //Global level.
      $(document)
        
        //Key up event.
        .on('keyup', function(e){
          switch(e.keyCode){
            case 27: unfocusMessage(); break; //ESC
            case 37: prevPage(); break;       //Left arrow
            case 39: nextPage(); break;       //Right arrow
          }
        });
      
    }
    
    function prevPage()
    {
      
      //When focused, the navigation is per item.
      if(DOM.messages.hasClass('focusing')){
        
        var currentIndex = getFocusedMessageIndex();
        
        //If this previous message exists.
        if(currentIndex-1 >= 0 && Data.messages[currentIndex-1]){
          
          var targetMessage = Data.messages[currentIndex-1];
          
          //See if it is rendered.
          var matchingElement = DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]');
          if(matchingElement.size() > 0){
            focusMessage(matchingElement);
          }
          
          //This means (for certain) it's on the previous page.
          else{
            Data.currentPage--;
            renderMessages();
            focusMessage(
              DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]')
            );
          }
          
        }
        
        //If a previous message does not exist, we are dealing with the first message.
        //Meaning we'll loop back to the last message.
        else{
          
          var targetMessage = Data.messages[Data.messages.length-1];
          
          if(Data.currentPage != Data.numPages){
            Data.currentPage = Data.numPages;
            renderMessages();
          }
          
          focusMessage(
            DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]')
          );
          
        }
        
      }
      
      //When not focusing, got to the previous page normally.
      else{
        
        //Loop back when we got to the first page.
        if(Data.currentPage <= 1)
          Data.currentPage = Data.numPages;
        
        //Otherwise just go to the previous one.
        else
          Data.currentPage--;
        
        //Rebuild the messages DOM.
        renderMessages();
        
        
      }
    }
    
    function nextPage()
    {
      
      //When focused, the navigation is per item.
      if(DOM.messages.hasClass('focusing')){
        
        var currentIndex = getFocusedMessageIndex();
        
        //If this previous message exists.
        if(currentIndex+1 >= 0 && Data.messages[currentIndex+1]){
          
          var targetMessage = Data.messages[currentIndex+1];
          
          //See if it is rendered.
          var matchingElement = DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]');
          if(matchingElement.size() > 0){
            focusMessage(matchingElement);
          }
          
          //This means (for certain) it's on the next page.
          else{
            Data.currentPage++;
            renderMessages();
            focusMessage(
              DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]')
            );
          }
          
        }
        
        //If a next message does not exist, we are dealing with the last message.
        //Meaning we'll loop back to the first message.
        else{
          
          var targetMessage = Data.messages[0];
          
          if(Data.currentPage != 1){
            Data.currentPage = 1;
            renderMessages();
          }
          
          focusMessage(
            DOM.messages.find('.mb-message[data-message-id="'+targetMessage.id+'"]')
          );
          
        }
        
      }
      
      //When not focusing, do a normal page increment.
      else{
        
        //Loop back when we got to the last page.
        if(Data.numPages == Data.currentPage)
          Data.currentPage = 1;
        
        //Otherwise just go to the next one.
        else
          Data.currentPage++;
        
        //Rebuild the messages DOM.
        renderMessages();
        
      }
      
    }
    
    //Finds the focused message's index.
    function getFocusedMessageIndex()
    {
      
      var activeElement = DOM.messages.find('.mb-message.active');
      
      //Check if we have an active element being focused at all.
      if(activeElement.size() == 0 || !DOM.messages.hasClass('focusing'))
        return null;
      
      var currentlyActive = parseInt(activeElement.attr('data-message-id'), 10);
      
      //Now find the index of this message.
      var index = -1;
      var found = false;
      var target = null;
      
      do{
        index++;
        target = Data.messages[index];
        found = target.id == currentlyActive;
      }
      while(!found && index < Data.messages.length);
      
      //If we found it, return the index number.
      if(found)
        return index;
      
      return null;
      
    }
    
    //Focuses the provided message element.
    function focusMessage(element)
    {
      
      DOM.messages
        .addClass('focusing')
      .find('.mb-message')
        .removeClass('active')
      ;
      
      var $element = $(element);
      var message = Data.messagesById[$element.attr('data-message-id')];
      
      $element.addClass('active');
      
      if(message && message.webpages)
        $element.find('.mb-site-content')
          .html(message.webpages[0].content)
          .show();
      
    }
    
    //Unfocuses (any) message.
    function unfocusMessage()
    {
      
      DOM.messages
        .removeClass('focusing')
      .find('.mb-message')
        .removeClass('active')
      .find('.mb-site-content')
        .empty()
        .hide()
      ;
      
    }
    
    //Loads the feed data and renders messages.
    function reloadFeed()
    {
      
      //Make a REST call.
      $.ajax({
        url: Options.feed_url,
        dataType: 'json',
        contentType: 'application/json'
      })
        
        //Store the data and render messages.
        .done(function(messages){
          
          Data.messages = messages;
          
          //Map the messages to ID's, for easy access.
          Data.messagesById = {};
          for(var i = 0; i < Data.messages.length; i++){
            var message = Data.messages[i];
            Data.messagesById[message.id] = message;
          }
          
          //Reset the pagination details.
          Data.numPages = Math.ceil(Data.messages.length / Options.itemsPerPage);
          Data.currentPage = 1;
          
          renderMessages();
          
        })
        
        .error(function(){
          throw new Error('Failed to fetch message feed for MessageBoard.');
        });
      
    }
    
    //(Re-)renders the messages.
    function renderMessages()
    {
      
      //Start at the page offset.
      var index = (Data.currentPage-1) * Options.itemsPerPage;
      
      //End at whatever comes first, the page end or the last item.
      var end = Math.min(
        (Data.currentPage) * Options.itemsPerPage,
        Data.messages.length
      );
      
      console.log(Data.currentPage, index, end);
      
      //Go for it.
      DOM.messages.empty();
      while(index < end){
        DOM.messages.append( template('messageTemplate', Data.messages[index]) );
        index++;
      }
      
    }
    
    //Run our code.
    __initialize(this);
    
    //Allow chaining.
    return DOM.container;
    
  };
  
  //Export this extension.
  $.fn.MessageBoard = MessageBoard;
  
})(jQuery);