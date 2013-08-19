/**
 * nimbleLoader v2.0.1 - Display loading bar where you want with ease
 * Version v2.0.1
 * @requires    jQuery v1.7.1
 * @description Display a loading bar in whatever block element you want
 *
 ***********************************************************************************************************************
 *                                                                                                      Prerequisites ? *
 ***********************************************************************************************************************
 *
 * 1- Build and download animated gif adapted to your web site thanks tools like http://www.ajaxload.info/
 * 2- Add a script tag referencing jquery.nimble.loader.js
 * 3- Configure your nimbleLoader and you somme CSS class
 * 4- That's it ! nimbleLoader is ready to use
 *
 *
 ***********************************************************************************************************************
 *                                                                                                          Use case ? *
 ***********************************************************************************************************************
 *
 * Most of the time the nimbleLoader is used when sending ajax request, to warn users that there is something
 * happening in the page :
 * - A form submission is being performed
 * - Updating a block content by getting information with ajax request
 * - Uploading / Downloading data
 * - ...
 *
 * There are two main ways of using the nimbleLoader
 * 1- You want to use it as an "overlay" : the nimbleLoader will appear inside the targeted container but over its content
 *    You may want to display a background with the loading bar (the background will cover all the content of the nimbleLoader target
 * 2- You want to display the nimbleLoader inside an container but as a element placed into the content right after the container content
 *
 *
 * Limitations :
 * - nimbleLoader shouldn't be use on an element with the css "position" property set to "fixed"
 * - nimbleLoader could impact the display of absolute element contained in the target element if the target element
 *   become the first relative parent to the absolute element.
 *
 ***********************************************************************************************************************
 *                                                                                                  How to configure ? *
 ***********************************************************************************************************************
 *
 * 1- Choose your params
 *
 *         |  Type / Value accepted   |  Name                 |              |  Default value         | Description
 * --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
 * @option |  Boolean                 |  overlay              |  (optional)  |  default:true          | When true, nimbleLoader will appear above the content of the targeted element
 *                                                                                                      "loaderImgUrl" option will be ignore in this case.
 *                                                                                                      When false, it will be displayed as an img tag as the last child of the targeted element
 *                                                                                                      To build the image, "loaderImgUrl" will be used, and all other options will be ignored
 * @option |  String                  |  loaderClass          |  (optional)  |  default:"loading_bar" | CSS class for the element which will display your loading bar
 * @option |  Numeric                 |  zIndex               |  (optional)  |  default:undefined     | value of the z-index css property of the loading bar if you need to handle it.
 *         |                          |                       |              |                          This can be useful if you have loading bars on top of the other in a page
 * @option |  Boolean                 |  debug                |  (optional)  |  default:false         | useful to display debug info
 * @option |  String/Numeric          |  speed                |  (optional)  |  default:"fast"        | The speed you want tour loading bar to appear/disappear (numeric or 'slow', 'fast'...)
 * @option |  Boolean                 |  hasBackground        |  (optional)  |  default:false         | If true will add a background to the loader
 * @option |  css color               |  backgroundColor      |  (optional)  |  default:"black"       | Will decide the background color   (only useful when "hasBackground" is true)
 * @option |  0< opt =<1              |  backgroundOpacity    |  (optional)  |  default:0.5           | Will decide the background opacity (only useful when "hasBackground" is true)
 * @option |  String                  |  loaderImgUrl         |  (optional)  |  default:""            | Represent the img URL to build the nimbleLoader (only useful when "overlay" is false)
 *                                                                                                      If "overlay" is false, then "loaderImgUrl" is required
 * @option |  String                  |  position             |  (optional)  |  default:absolute      | A CSS position (absolute or fixed) which will allow the nimbleLoader to be displayed over some content
 * @option |  function                |  callbackOnHiding     |  (optional)  |  default:undefined     | Will be executed when nimbleLoader hide itself, when the fadeOut is done
 *
 *    Example : 
 *    var params = {
 *      loaderClass        : "loading_bar",
 *      debug              : false,
 *      speed              : 'fast',
 *      needPositionedParent : true
 *    }
 *
 * 2- Set your params
 *
 *    2.1 Global way
 *      $.fn.nimbleLoader.setSettings(params);
 *
 *    2.2 Specific way
 *      $("#myDiv").nimbleLoader("show", otherParams);
 *
 * 3- Don't forget to set the css of your loading bar : see the demo to have an example (style/loader.css)
 * 
 *
 * 
 ***********************************************************************************************************************
 *                                                                                                        How to use ? *
 ***********************************************************************************************************************
 *
 * => Showing a loading bar in <div id="myDiv"></div>
 * $("#myDiv").nimbleLoader("show");
 *
 * => Hiding the loading bar
 * $("#myDiv").nimbleLoader("hide");
 *
 */

if(jQuery)(function($){
  
  // Extend JQuery function : adding nimbleLoader
  $.extend($.fn,{
    
    nimbleLoader: function(method, options){
      
      /*************************************************************************
       *  Plugin Methods
       ************************************************************************/

      // Clone the global settings. $.extend is needed : we extend a new object with global settings
      var settings = $.extend( true, {}, $.fn.nimbleLoader.settings);

      // Catch settings given in parameters

      if(options){ jQuery.extend(settings, options); }

      // Function to init the loader
      function init($nimbleLoader, settings){
        var loader = new LoadingHandlerGenerator($nimbleLoader, settings);
        $nimbleLoader.data("loader", loader);
      }

      // Function to show the loading bar
      var show = function(){

        return this.each(function(){
          var $nimbleLoader = $(this);
          if($nimbleLoader.data("loader") !== undefined){
            var loader = $nimbleLoader.data("loader");
            loader.showLoading();
          }
          else{
            init($nimbleLoader, settings);
            $nimbleLoader.nimbleLoader('show');
          }
        });
      };

      // Function to hide the loading bar
      var hide = function(){
        return this.each(function(){
          var $nimbleLoader = $(this);
          if($nimbleLoader.data("loader") !== undefined){
            var loader = $nimbleLoader.data("loader");
            loader.hideLoading();
          }
        });
      };

      var methods = {
        show         : show,
        hide         : hide
      };
      
      /*************************************************************************
      *  Execution when calling .nimbleLoader()
      ************************************************************************/
      if(methods[method]){
        return methods[method].apply( this, Array.prototype.slice.call( arguments, 1));
      }
      else if(!method){
        return methods.show.apply(this , Array.prototype.slice.call( arguments, 1));
      }
      else{
        if(window && window.console){
          console.log("[jquery.nimble.loader] -> no method '"+method+"' to apply ");
        }
        return false;
      }

      /**
       * Closure function which define a loading bar element
       */
      function LoadingHandlerGenerator($parentSelector, params){

        /**
         * $loadingBar              : the loading bar jQuery element
         * debug                    : debug option to display in console how many times the loading bar has been called
         * speed                    : animation speed when showing/hiding the loading bar
         * previousCssPosition      : store the initial position (string) of the loader parent when "needPositionedParent" is true
         * countNbCall              : the counter to count the number of time the loader has been called
         * nbLoadingElements        : counter of the number of HTML elements involved (1 -only the loading bar- or 2 -the loading bar + the background-)
         */

        var $loadingBar;
        var debug                     = params.debug;
        var speed                     = params.speed;
		var phrase					  = params.string;
        var previousCssPosition       = "";
        var countNbCall               = 0;
        var nbLoadingElements         = 0;
        var waitForAnimation          = {
          isAnimated  : 0, // the number of animated elements, - 0 meaning then no animation
          callStack   : []
        };

        // Init the loader : set html and place it
        function initLoading(){

          // If the loader doesn't exists, we create and init it
          if(!$loadingBar){

            // Define loading bar basic element (used to build inline and positioned nimble loader)
            var $loader = $('<div></div>').addClass(params.loaderClass);
            nbLoadingElements = 1;

            /**
             * Configuration for inline nimbleLoader:
             * When creating an inline nimbleLoader, the plugin create a HTML img tag to display the nimbleLoader.
             * To create this img tag, params.loaderImgUrl is required.
             */
            if (!params.overlay) {

              $loadingBar = $loader;
              // loaderImgUrl is required to build the image that will represent the loader
              if(params.loaderImgUrl){
                $loadingBar.append("<p>Please wait, if you're uploading some files this operation could take a while</p><br/><img src='"+params.loaderImgUrl+"' alt='Loading,Please Wait'/>");
              }
              else{
                if(window && window.console){
                  console.log("[jquery.nimble.loader] -> loaderImgUrl should be defined when 'display' = 'inline'" );
                }
              }
              // Append the loading bar in its parent so that it appear after the content already in $parentSelector
              if($parentSelector && $parentSelector.length){
                $parentSelector.append($loadingBar);
              }
            }
            /**
             * Configuration for overlay nimbleLoader:
             */
            else{
              /**
               * Configuring background
               * If there is a background, we add it to the loadingBar selector
               * and increase the nbLoadingElements value
               * $nimbleLoader is now composed of two "brother" elements
               */
              if (params.hasBackground) {
                nbLoadingElements++;
                var opacity           = params.backgroundOpacity;
                var $backgroundLoader = $('<div></div>').css({
                  top                : 0,
                  left               : 0,
                  position           : params.position,
                  display            : "none",
                  height             : "100%",
                  width              : "100%",
                  "background-color" : params.backgroundColor,
                  "opacity"          : opacity,
                  filter             : "alpha(opacity="+Math.floor(100*opacity)+")" // This is for IE7 and IE8
                });
                $loadingBar = $backgroundLoader.add($loader);
              }else{
                $loadingBar = $loader;
              }

              /**
               * Prepend the loading bar in its parent.
               * It has to be done before configuring CSS properties: ce need to read some CSS property which should
               * come from css file, and these properties are set to the element only once it has been added to the dom
               */
              if($parentSelector && $parentSelector.length){$parentSelector.prepend($loadingBar);}

              // Configuring CSS z-index
              if (params.zIndex) { $loadingBar.css("z-index", params.zIndex);}

              /**
               * Configuring CSS position of the loading bar
               */
              if(params.position){
                var $elToPosition = $loadingBar.filter("."+params.loaderClass);
                $elToPosition.css("position", params.position);
              }

              /**
               * Configuring CSS position of the nimbleLoader target
               * If the params specified that the loading bar should have an absolute position, so we attribute a
               * relative position to its parent if it's not already positioned
               */
              if(params.position === "absolute"){
                // If nimbleLoader container is already positioned we keeps its position in memory to be able to
                // restore it when precessing hide on nimbleLoader. Otherwise the container will loose it.
                if($parentSelector.css("position") === "relative" || $parentSelector.css("position") === "absolute"){
                  previousCssPosition = $parentSelector.css("position");
                }
                // Else we positioned the nimbleLoader container as relative
                else{
                  if(params.position === "absolute" && ($parentSelector[0].tagName).toLowerCase() !== "body" ){
                    $parentSelector.css("position", "relative");
                  }
                }
              }
            }
          }
        }

        // Log counter element in the loading bar : show the number of time a loading bar has been call
        function logCounter(nbCall){
          if(window && window.console){
            var idAttr    = $parentSelector.attr("id");
            var classAttr = $parentSelector.attr("class");
            var params    = [];
            if(idAttr    != ""){params.push("#"+idAttr);}
            if(classAttr != ""){params.push("."+classAttr);}
            console.log("[jquery.nimble.loader] -> $("+params.join(" ")+").logCounter : "+nbCall);
          }
        }

        // Decrease the call counter and change the debug display if needed
        function decreaseCounter(){
          var ret = -1;
          if(countNbCall > 0){
            countNbCall--;
            ret = countNbCall;
          }
          if(debug){logCounter(ret);}
          return ret;
        }

        // Increase the call counter and change the debug display if needed
        function increaseCounter(){
          countNbCall++;
          if(debug){logCounter(countNbCall);}
          return countNbCall;
        }

        // Check if there is an action to do in the callStack and do the one of the top of the stack
        function callStack(){
          if(waitForAnimation.callStack.length > 0){
            if(waitForAnimation.isAnimated === 0) {
              var actionToDo = waitForAnimation.callStack.pop();

              if(actionToDo == "hideLoading"){
                processHide();
              }
              else if(actionToDo == "showLoading"){
                processShow();
              }
            }
          }
        }

        function showLoading() {
          unshiftAction("showLoading");
        }
        function hideLoading() {
          unshiftAction("hideLoading");
        }

        function unshiftAction(action){
          waitForAnimation.callStack.unshift(action);
          callStack();
        }

        // Show the loading bar element
        function processShow(){
          if(increaseCounter() == 1) { // Check if we have to show the loader it's the first
            initLoading();

            // We set a param to know that the animation to hide has begin
            waitForAnimation.isAnimated = nbLoadingElements;
            $loadingBar.fadeIn(speed, function(){

              // We set a param to know that the animation to show is finished
              waitForAnimation.isAnimated--;

              // During destroying, calls can be made to show the loader. So we let's the loader disappear and then we show it again
              callStack();

            });
          }
          else{
            callStack();
          }
        }

        // Hide the loading bar element
        function processHide(){
          
          // Check if we have to destroy the loader (it happens when the counter is equal to 0)
          if( decreaseCounter() === 0){ // If countNbCall == 0 decreaseCounter() returns -1

            // We set a param to know that the animation to hide has begin
            waitForAnimation.isAnimated = nbLoadingElements;

            // We animate the loader to make it disappear
            $loadingBar.fadeOut(speed, function(){
              // This will be called as many times as there are elements in the $loading element
              // We set a param to know that the animation to hide is finished
              waitForAnimation.isAnimated--;

              // We destroy the loader element
              $(this).remove();

              // Reset the initial position of the loader parent
              $parentSelector.css("position", previousCssPosition);

              // If all loaders have been destroyed, we reset the $loadingBar variable
              if (waitForAnimation.isAnimated === 0) {
                $loadingBar = undefined;
              }

              // If a callback is defined, we call it
              if(params.callbackOnHiding && typeof(params.callbackOnHiding) === "function"){
                params.callbackOnHiding();
              }

              // During destroying, calls can be made to show the loader. So we let's the loader disappear and then we show it again
              callStack();
            });
          }
          else{
            callStack();
          }
        }

        // Body of the closure function
        return  {
          showLoading : showLoading,
          hideLoading : hideLoading,
          init        : initLoading
        };
      }
    }
  });

  $.extend($.fn.nimbleLoader,{
    settings:{
      overlay              : true,
      position             : "absolute",
      loaderImgUrl         : "",
      loaderClass          : "loading_bar",
      callbackOnHiding     : function(){},
      speed                : 'fast',
      hasBackground        : false,
      backgroundColor      : "#ffffff",
      backgroundOpacity    : 0.5,
      debug                : false,
	  string			   : 'Please wait, if you\' uploading some files this operation could take a while'
    },
    setSettings: function(options){
      $.extend($.fn.nimbleLoader.settings, options);
    }
  });

})(jQuery);
