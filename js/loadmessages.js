(function($) {

	$.fn.scrollPagination = function(options) {
		
		var settings = { 
			nop		:	10,
			offset	:	10,
			error	:	'No More Messages', 	
			delay	:	900,
			scroll	:	true,
			id		:	'',
			add		:	0
		}
		
		// Extend the options so they work with the plugin
		if(options) {
			$.extend(settings, options);
		}
		
		// For each so that we keep chainability.
		return this.each(function() {
			
			// Some variables 
			$this = $(this);
			$settings = settings;
			var offset = $settings.offset;
			var id = $settings.id;
			var busy = false;
			
			// Custom messages based on settings
			if($settings.scroll == true) $initmessage = 'Scroll for more or click here';
			else $initmessage = 'Click for More Messages';
			
			// Append custom messages and extra UI
			$this.append('<div class="row-fluid"><div class="span12"><div class="loading-bar">'+$initmessage+'</div></div></div>');
			
			function getData() {
				var request= $.ajax({
					type: 'POST',url: '../php/function.php',data: {action:'scrollpagination',number:$settings.nop,offset:offset+$settings.add,id:id},dataType : 'json',
					success : function (data) {
						$this.find('.loading-bar').html($initmessage);
						if(data['ret'] == "End") {
							$this.find('.loading-bar').html($settings.error);
						}
						else if(data['ret'] == "Error") { 
							$this.find('.loading-bar').html(data[1]);
						}
						else if(data['ret'] == "Entry"){
							offset = offset+$settings.nop;
							mess= new Array();
							var count= data['messages'].length;
							for(var i=0; i<count;i++){
								if(i%2==0)
									mess.push('<div class="row-fluid evenmessage"><div class="row-fluid"><div class="span2 usrinfo"><p class="username">'+data['messages'][i][0]+'</p><p class="date">'+data['messages'][i][2]+'</p></div><div class="span8 messagecell">'+data['messages'][i][1]+'</div></div>');
								else
									mess.push('<div class="row-fluid oddmessage"><div class="row-fluid"><div class="span2 usrinfo"><p class="username">'+data['messages'][i][0]+'</p><p class="date">'+data['messages'][i][2]+'</p></div><div class="span8 messagecell">'+data['messages'][i][1]+'</div></div>');
								var upcount=data['messages'][i].length;
								if(upcount>3){
									mess.push('<div class="row attachment"><div class="span2 offset1 attachmentsec">Attachment</div><div class="span8">');
									for(var j=3;j<upcount;j++)
										mess.push(data['messages'][i][j]);
									mess.push('</div></div>');
								}
								mess.push('</div>');
							}
							$this.children('.row-fluid').last().before(mess.join(''));
							busy = false;
						}
						else
							$this.find('.loading-bar').html($settings.error);
					}
				});
				request.fail(function(jqXHR, textStatus){alert('Error: '+ textStatus);});
			}
			
			// If scrolling is enabled
			if($settings.scroll == true) {
				// .. and the user is scrolling
				$(window).scroll(function() {
					
					// Check the user is at the bottom of the element
					if($(window).scrollTop() + $(window).height() > $this.height() && !busy) {
						
						// Now we are working, so busy is true
						busy = true;
						
						// Tell the user we're loading posts
						$this.find('.loading-bar').html('Loading Posts');
						
						// Run the function to fetch the data inside a delay
						// This is useful if you have content in a footer you
						// want the user to see.
						setTimeout(function() {
							
							getData();
							
						}, $settings.delay);
							
					}	
				});
			}
			
			// Also content can be loaded by clicking the loading bar/
			$this.find('.loading-bar').click(function() {
			
				if(busy == false) {
					busy = true;
					getData();
				}
			
			});
			
		});
	}

})(jQuery);
