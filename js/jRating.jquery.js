/************************************************************************
*************************************************************************
@Name :       	jRating - jQuery Plugin
@Revison :    	3.0
@Date : 		28/01/2013 
@Author:     	 ALPIXEL - (www.myjqueryplugins.com - www.alpixel.fr) 
@License :		 Open Source - MIT License : http://www.opensource.org/licenses/mit-license.php
 
**************************************************************************
*************************************************************************/
(function($) {
	$.fn.jRating = function(op) {
		var defaults = {
			/** String vars **/
			bigStarsPath : '../css/icon/stars.png', // path of the icon stars.png
			smallStarsPath :  '../css/icon/small.png', // path of the icon small.png
			phpPath : '../php/function.php', // path of the php file jRating.php
			type : 'big', // can be set to 'small' or 'big'

			/** Boolean vars **/
			step:true, // if true,  mouseover binded star by star,
			isDisabled:false,
			showRateInfo: true,
			canRateAgain : true,

			/** Integer vars **/
			length:10, // number of star to display
			decimalLength : 0, // number of decimals.. Max 3, but you can complete the function 'getNote'
			rateMax : 10, // maximal rate - integer from 0 to 9999 (or more)
			rateInfosX : -45, // relative position in X axis of the info box when mouseover
			rateInfosY : 5, // relative position in Y axis of the info box when mouseover
			nbRates : 3,

			/** Functions **/
			onSuccess : null,
			onError : null
		}; 

		if(this.length>0)
		return this.each(function() {
			/*vars*/
			var opts = $.extend(defaults, op),    
			newWidth = 0,
			starWidth = 0,
			starHeight = 0,
			bgPath = '',
			hasRated = false,
			globalWidth = 0,
			nbOfRates = opts.nbRates
			rate=0,
			submitted=false;

			if($(this).hasClass('jDisabled') || opts.isDisabled)
				var jDisabled = true;
			else
				var jDisabled = false;

			getStarWidth();
			$(this).height(starHeight);

			var average = parseFloat($(this).attr('data-average')), // get the average of all rates
			idBox = $(this).attr('data-id'), // get the id of the box
			widthRatingContainer = starWidth*opts.length, // Width of the Container
			widthColor = average/opts.rateMax*widthRatingContainer, // Width of the color Container

			quotient = 
			$('<div>', 
			{
				'class' : 'jRatingColor',
				css:{
					width:widthColor
				}
			}).appendTo($(this)),

			average = 
			$('<div>', 
			{
				'class' : 'jRatingAverage',
				css:{
					width:0,
					top:- starHeight
				}
			}).appendTo($(this)),

			 jstar =
			$('<div>', 
			{
				'class' : 'jStar',
				css:{
					width:widthRatingContainer,
					height:starHeight,
					top:- (starHeight*2),
					background: 'url('+bgPath+') repeat-x'
				}
			}).appendTo($(this));
			

			$(this).css({width: widthRatingContainer,overflow:'hidden',zIndex:1,position:'relative'});

			if(!jDisabled)
			$(this).unbind().bind({
				mouseenter : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
					if (opts.showRateInfo)
					var tooltip = 
					$('<p>',{
						'class' : 'jRatingInfos',
						html : getNote(relativeX)+' <span class="maxRate">/ '+opts.rateMax+'</span>',
						css : {
							top: (e.pageY + opts.rateInfosY),
							left: (e.pageX + opts.rateInfosX)
						}
					}).appendTo('body').show();
				},
				mouseover : function(e){
					$(this).css('cursor','pointer');	
				},
				mouseout : function(){
					$(this).css('cursor','default');
					if(hasRated) average.width(globalWidth);
					else average.width(0);
				},
				mousemove : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
					if(opts.step) newWidth = Math.floor(relativeX/starWidth)*starWidth + starWidth;
					else newWidth = relativeX;
					average.width(newWidth);					
					if (opts.showRateInfo)
					$("p.jRatingInfos")
					.css({
						left: (e.pageX + opts.rateInfosX)
					})
					.html(getNote(newWidth) +' <span class="maxRate">/ '+opts.rateMax+'</span>');
				},
				mouseleave : function(){
					$("p.jRatingInfos").remove();
				},
				click : function(e){
                    var element = this;
					
					/*set vars*/
					hasRated = true;
					globalWidth = newWidth;
					nbOfRates--;
					
					if(!opts.canRateAgain || parseInt(nbOfRates) <= 0) $(this).unbind().css('cursor','default').addClass('jDisabled');
					
					if (opts.showRateInfo) $("p.jRatingInfos").fadeOut('fast',function(){$(this).remove();});
					e.preventDefault();
					rate = getNote(newWidth);
					average.width(newWidth);
					$(this).attr('data-rate',rate);
				}
			});
			
			$('#submitrate').click(function(){
				if(rate>0){
					if(submitted==false){
						//submitted = true;
						var comment=$('#rcomment').val().replace(/\s+/g," ");
						var request= $.ajax({
							type: 'POST',url: opts.phpPath,data:{act:'rating',idBox:idBox,rate:rate,tkid:$('#tkid').val(),comment:comment},dataType : 'json',
							success : function (data) {
								$('.loading:first').remove();
								if(data[0]=='Voted'){
									noty({text: 'Thanks for voting!',type:'success',timeout:4000});
								}
								else{
									noty({text: data[0],type:'error',timeout:9000});
								}
							}
						});
						request.fail(function(jqXHR, textStatus){alert('Ajax Error: '+ textStatus);});
					}
					else
						noty({text: 'You have already rated this operator',type:'error',timeout:9000});
				}
				else
					noty({text: 'Please assign at least one star',type:'error',timeout:9000});
			});
			
			$(this).parent().parent().find('.faqrate').click(function(){
				var rate=$(this).parent().parent().find('.razorate').attr('data-rate');
				var token=$('#tok').val();
				if(rate>0){
					if(submitted==false){
						//submitted = true;
						var idBox=$(this).parent().parent().find('.razorate').attr('data-id');
						if(idBox!=undefined && rate!=undefined){
							var request= $.ajax({
								type: 'POST',url: opts.phpPath,data:{act:'faq_rating',idBox:idBox,rate:rate,token:token},dataType : 'json',
								success : function (data) {
									$('.loading:first').remove();
									if(data[0]=='Voted'){
										noty({text: 'Thanks for voting!',type:'success',timeout:4000});
									}
									else{
										noty({text: data[0],type:'error',timeout:9000});
									}
								}
							});
							request.fail(function(jqXHR, textStatus){alert('Ajax Error: '+ textStatus);});
						}
						else
							noty({text: 'Cannot Retrieve Data',type:'error',timeout:9000});
					}
					else
						noty({text: 'You have already rated this FAQ',type:'error',timeout:9000});
				}
				else
					noty({text: 'Please assign at least one star',type:'error',timeout:9000});
			});
			
			function getNote(a) {
				var a = parseFloat(100 * a / widthRatingContainer * opts.rateMax / 100); 
				var b = Math.pow(10, opts.decimalLength); 
				return Math.round(a * b) / b
			};

			function getStarWidth(){
				switch(opts.type) {
					case 'small' :
						starWidth = 12; // width of the picture small.png
						starHeight = 10; // height of the picture small.png
						bgPath = opts.smallStarsPath;
					break;
					default :
						starWidth = 23; // width of the picture stars.png
						starHeight = 20; // height of the picture stars.png
						bgPath = opts.bigStarsPath;
				}
			};

			function findRealLeft(obj) {
			  if( !obj ) return 0;
			  return obj.offsetLeft + findRealLeft( obj.offsetParent );
			};
		});

	}
})(jQuery);