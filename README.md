BoardroomPopup
==============

Dynamic Email Acquisition Popup

This application is a modal popup intended to add new subscribers to our email list.  It uses our ESP's API to check if a visitor to our web site is currently subscribed and then displays one of several versions based on that result.  

If the visitor is not subscribed it displays a sign up form that posts directly to our ESP database.  If the visitor is already subscribed we show them an alternative popup, in this case we ask them to 'like' us on Facebook.

The visitor can hide the popup indefinitely by clicking a link provided, by signing up, or liking us on Facebook.  In both cases a cookie is dropped to control this.

We also included Google Analytics event tracking to collect data on all important actions.  

We use the following to help make it happen:

jQuery Magnific Popup

jQuery Cookie
