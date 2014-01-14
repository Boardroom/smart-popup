//FINAL popup logic
//v12.24.2013
//v1.14.2014 added one-day cookie on close

jQuery(document).ready(function($){    
	var cookie_value = $.cookie('popup_cookie');
	if (cookie_value != 'nevershow') {
		var uid = $.cookie('com.silverpop.iMA.uid');		
		if (uid) {
		    //alert ("sp UID found");
			var nl = "BLS";
            //var nl = "NL";
                    var url = location.protocol + '//' + location.host + '/popup/' + 'sp_user_subs.php?uid=' + uid + "&nl=" + nl;			
			$.getJSON(url, function(data) {
                           if (!data) { return true; }                         		    
				if (data != "null") {				    
					$.each( data, function( key, val ) {
					   
						if (key == nl) {
							
						};						
						//popup code begins
						if (val==="Yes") {
                            //alert("Subscribed YES");	                            					
							$.magnificPopup.open({	
							  items: {
							  src: '#small-dialog'          
							  },
							  
							  type: 'inline',

							  fixedContentPos: true,
							  fixedBgPos: true,

							  overflowY: 'auto',

							  closeBtnInside: true,
							  preloader: false,
							  
							  midClick: true,
							  removalDelay: 300,
							  mainClass: 'my-mfp-slide-bottom',
							  
							  callbacks: {
							  beforeOpen: function(){
							  $('head').append('<meta id="viewport" name="viewport" content="width=device-width, initial-scale=.7, maximum-scale=.7, user-scalable=no">');                
							  },
								close: function(){			
								$('#viewport').remove();
								$('head').append('<meta id="viewport" name="viewport" content="width=964">');
								}
							  }
							  
							});      
							$(".mfp-close").addClass("facebook");
							_gaq.push(['_trackEvent', 'Pop Up', 'Display', 'BLP Facebook', 0, true]);
						   }
						 else if (val==="No") {
						 //alert("Subscribed NO");                                    
						    $.magnificPopup.open({	
							  items: {
							  src: '#small-dialog2'          
							  },
							  
							  type: 'inline',

							  fixedContentPos: true,
							  fixedBgPos: true,

							  overflowY: 'auto',

							  closeBtnInside: true,
							  preloader: false,
							  
							  midClick: true,
							  removalDelay: 300,
							  mainClass: 'my-mfp-slide-bottom',
							  
							  callbacks: {
							  beforeOpen: function(){
							  $('head').append('<meta id="viewport" name="viewport" content="width=device-width, initial-scale=.7, maximum-scale=.7, user-scalable=no">');                
							  },
								close: function(){			
								$('#viewport').remove();
								$('head').append('<meta id="viewport" name="viewport" content="width=964">');
								}
							  }
							  
							});
                                             $(".mfp-close").addClass("subscribe");
                                            _gaq.push(['_trackEvent', 'Pop Up', 'Display', 'BLS Best of Times', 0, true]);
						 };
					});
				} 				
			});
		}
		
		else if (!uid) {
		//alert("no sp UID");
		$.magnificPopup.open({	
		  items: {
		  src: '#small-dialog2'          
		  },
		  
		  type: 'inline',

		  fixedContentPos: true,
		  fixedBgPos: true,

		  overflowY: 'auto',

		  closeBtnInside: true,
		  preloader: false,
		  
		  midClick: true,
		  removalDelay: 300,
		  mainClass: 'my-mfp-slide-bottom',
		  
		  callbacks: {
		  beforeOpen: function(){
		  $('head').append('<meta id="viewport" name="viewport" content="width=device-width, initial-scale=.7, maximum-scale=.7, user-scalable=no">');                
		  },
			close: function(){			
			$('#viewport').remove();
			$('head').append('<meta id="viewport" name="viewport" content="width=964">');
			}
		  }
		  
		});
		$(".mfp-close").addClass("subscribe");
             _gaq.push(['_trackEvent', 'Pop Up', 'Display', 'BLS Best of Times', 0, true]);
		}

		
        		
	}
	
	else {
	return true;
	}
	
});

//Facebook Like Scripting
window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '222901567771096',                        // App ID from the app dashboard
      channelUrl : '//dv02blpdev.boardroomcorp.net/channel.html', // Channel file for x-domain comms
      status     : true,                                 // Check Facebook Login status
      xfbml      : true                                  // Look for social plugins on the page
    });

    // Additional initialization code such as adding Event Listeners goes here
	// subscribe to the event
FB.Event.subscribe('edge.create', function(response) {
 _gaq.push(['_trackEvent', 'Pop Up', 'Like', 'BLP Facebook', 0, true]);
writeCookie('nevershow');
});
FB.Event.subscribe('edge.remove', function(response) {
 _gaq.push(['_trackEvent', 'Pop Up', 'Unlike', 'BLP Facebook', 0, true]);
});
  };

  // Load the SDK asynchronously
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/all.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));


function writeCookie(value, value2) {
  var cookie_value = jQuery.cookie('popup_cookie');
  if (value2==='optin') {
    jQuery.cookie('popup_cookie', value, { expires: 3, path: '/' });
  } else if (value2==='oneday') {
    jQuery.cookie('popup_cookie', value, { expires: 1, path: '/' });
  } else if (cookie_value === undefined) {
    jQuery.cookie('popup_cookie', value, { expires: 999, path: '/' });
  } else {
    jQuery.cookie('popup_cookie', value, { expires: 999, path: '/' });
  }  
  return true;
}


// Dismiss Popup on Cookie click

jQuery(document).ready(function ($) {

$(document).on('click', '.popup-modal-dismiss-subscribe', function (e) {
_gaq.push(['_trackEvent', 'Pop Up', 'Decline', 'BLS Best of Times', 0, true]);
e.preventDefault();
$.magnificPopup.close();
});

$(document).on('click', '.popup-modal-dismiss-facebook', function (e) {
_gaq.push(['_trackEvent', 'Pop Up', 'Decline', 'BLP Facebook', 0, true]);
e.preventDefault();
$.magnificPopup.close();
});

$(document).on('click', '.facebook', function (e) {
_gaq.push(['_trackEvent', 'Pop Up', 'Close', 'BLP Facebook', 0, true]);
e.preventDefault();
writeCookie('nevershow', 'oneday');
$.magnificPopup.close();
});

$(document).on('click', '.subscribe', function (e) {
_gaq.push(['_trackEvent', 'Pop Up', 'Close', 'BLS Best of Times', 0, true]);
e.preventDefault();
writeCookie('nevershow', 'oneday');
$.magnificPopup.close();
});

});


// Opt In Form Scripting

jQuery(document).ready(function($){
	$('.submit').submit(function() {	
        $('.loadingGraphic').show();	
		var emailval = $('#email-mobile').val();				
		if (emailval) {
                        var nl = "BLS";
                        var url = location.protocol + '//' + location.host + '/popup/' + 'sp_user_update.php?email=' + emailval + "&nl=" + nl;
                        $.getJSON(url, function(data) {
                                    if (data != "null") {
                                                if (data == "true") {
                                                            // do something
															$(".thankyou").css("display", "block");
															$(".submit").css("display", "none");															
															_gaq.push(['_trackEvent', 'Pop Up', 'Opt In', 'BLS Best of Times', 0, true]);
															writeCookie('nevershow', 'optin');
															setTimeout(function() {
																  $.magnificPopup.close();                                                                   
															}, 3000);
                                                } else {
                                                            // do something else
															//alert("false");
                                                }
                                    } 
                        });
            }
		
		return false;
	});
	
	$('.submitDesktop').submit(function() {        	
	    $('.loadingGraphic').show();        		
		var emailval = $('#email-desktop').val();				
		if (emailval) {									
                        var nl = "BLS";						
                        var url = location.protocol + '//' + location.host + '/popup/' + 'sp_user_update.php?email=' + emailval + "&nl=" + nl;
                        $.getJSON(url, function(data) {
                                    if (data != "null") {
                                                if (data == "true") {
                                                            // do something
															$(".thankyou").css("display", "block");
															$(".submitDesktop").css("display", "none");															
															_gaq.push(['_trackEvent', 'Pop Up', 'Opt In', 'BLS Best of Times', 0, true]);
															writeCookie('nevershow', 'optin');
															setTimeout(function() {
																  $.magnificPopup.close(); 																  
															}, 3000);															
                                                } else {
                                                            // do something else
															//alert("false");
                                                }
                                    } 
                        });
            }
		
		return false;
	});

});

// Placeholder polyfill 

jQuery(document).ready(function ($) {

// Released under MIT license: http://www.opensource.org/licenses/mit-license.php
 
$('[placeholder]').focus(function() {
  var input = $(this);
  if (input.val() == input.attr('placeholder')) {
    input.val('');
    input.removeClass('placeholder');
  }
}).blur(function() {
  var input = $(this);
  if (input.val() == '' || input.val() == input.attr('placeholder')) {
    input.addClass('placeholder');
    input.val(input.attr('placeholder'));
  }
}).blur().parents('form').submit(function() {
  $(this).find('[placeholder]').each(function() {
    var input = $(this);
    if (input.val() == input.attr('placeholder')) {
      input.val('');
    }
  })
});

});

 

