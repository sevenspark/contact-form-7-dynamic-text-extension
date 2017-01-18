=== Contact Form 7 Dynamic Text Extension ===
Contributors: sevenspark
Donate link: http://bit.ly/bVogDN
Tags: Contact Form 7, Contact, Contact Form, dynamic, text, input, GET, POST, title, slug
Requires at least: 4.7
Tested up to: 4.7.1
Stable tag: 2.0.2.1

This plugin provides 2 new tag types for the Contact Form 7 Plugin. It allows the dynamic generation of content for a text input box via any shortcode.
It also offers dynamic hidden field functionality, which can be utilized to dynamically set the Email Recipient (To:) address. 

== Description ==

Contact Form 7 is an excellent WordPress plugin, and the CF7 DTX Plugin makes it even more awesome by adding dynamic content capabilities.
While default values in Contact Form 7 are static. CF7 DTX lets you create pre-populated fields based on other values.  Some examples might include:

* Auto-filling a URL
* Auto-filling a Post ID, title, or slug
* Pre-populating a Product Number
* Referencing other content on the site
* Populating with post info 
* Populating with user info
* Populating with custom fields
* Any value you can write a shortcode for

There are many more case-specific examples. I searched for a solution, and there are some decent hacks out there. Many of them are 
explored in this forum topic: 
[Contact Form 7 Input Fields Values as PHP Get-Variables](http://wordpress.org/support/topic/contact-form-7-input-fields-values-as-php-get-viarables). 
However, they all involved hacking the current Contact Form 7 code, which means next time the plugin is updated their edits will be 
overwritten. That's bad.

This Dynamic Text Extension plugin provides a more elegant solution that leaves the Contact Form 7 Plugin intact.

= WHAT DOES IT DO? =

This plugin provides a new tag type for the Contact Form 7 Plugin. It allows the dynamic generation of content for a text input box via any shortcode. 
For example, it comes with several built-in shortcodes that will allow the Contact Form to be populated from any $_GET PHP variable or any info from the 
get_bloginfo() function, among others.  See below for included shortcodes.

Don't see the shortcode you need on the list?  You can write a custom one! Any shortcode that returns a string value can be used here.  The included shortcodes just cover the most common scenarios, but the plugin provides the flexibility for you to grab any value you have access to programmatically.

= HOW TO USE IT =

After installing and activating the plugin, the Contact Form 7 tag generator will have 2 new tag types: Dynamic Text Field and Dynamic Hidden Field. Most of the options will be 
familiar to Contact Form 7 users. There are two important fields:

**Dynamic Value**

This field takes a shortcode, with two important provisions:

1. The shortcode should NOT include the normal square brackets ([ and ]). So, instead of [CF7_GET key='value'] you would use CF7_GET key='value' .
2. Any parameters in the shortcode must use single quotes. That is: CF7_GET key='value' and not CF7_GET key="value"
	
	
**Uneditable Option**

As these types of fields should often remain uneditable by the user, there is a checkbox to turn this option on (Not applicable for hidden fields).


= INCLUDED SHORTCODES =

The plugin includes 2 basic shortcodes for use with the Dynamic Text extension. You can write your own as well - any shortcode will work

**PHP GET Variables**

Want to use a variable from the PHP GET array? Just use the CF7_GET shortcode. For example, if you want to get the foo parameter from the url 
http://mysite.com?foo=bar

Enter the following into the "Dynamic Value" input

CF7_GET key='foo'

Your Content Form 7 Tag will look something like this:

[dynamictext dynamicname "CF7_GET key='foo'"]

Your form's dynamicname text input will then be pre-populated with the value of foo, in this case, bar


**PHP POST Variables**

New in version 1.0.3!

Grab variables from the $_POST array.  The shortcode is much like the GET shortcode:

CF7_POST key='foo'

Your Content Form 7 Tag will look something like this:

[dynamictext dynamicname "CF7_POST key='foo'"]


**Blog Info**

Want to grab some information from your blog like the URL or the sitename? Use the CF7_bloginfo shortcode. For example, to get the site's URL:

Enter the following into the "Dynamic Value" input

CF7_bloginfo show='url'

Your Content Form 7 Tag will look something like this:

[dynamictext dynamicname "CF7_bloginfo show='url'"]

Your form's dynamicname text input will then be pre-populated with your site's URL


**Post Info**

New in version 1.0.3!

Retrieve information about the current post/page (that the contact form is displayed on).  The shortcode works as follows:

CF7_get_post_var key='title'      <-- retrieves the Post's Title
CF7_get_post_var key='slug'       <-- retrieves the Post's Slug

You can also retrieve any parameter from the $post object.  Just set that as the key value, for example 'post_date'

The Contact Form 7 Tag would look like:

[dynamictext dynamicname "CF7_get_post_var key='title'"]

**Current URL**

New in version 1.0.3!

Retrieve the current URL.  The shortcode takes no parameters:

CF7_URL

So your Contact Form 7 Tag would look like:

[dynamictext dynamicname "CF7_URL"]

**Custom Fields**

New in version 1.0.4!

Retrieve custom fields from the current post/page.  Just set the custom field as the key in the shortcode.

The dynamic value input becomes:

CF7_get_custom_field key='my_custom_field'

And the tag looks like this:

[dynamictext dynamicname "CF7_get_custom_field key='my_custom_field'"]

For the purposes of including an email address, you can obfuscate the custom field value by setting obfuscate='on' in the shortcode.

**Current User Info**

Get data about the current user - assuming they are logged in.  Defaults to user name, but you can set the key to any valid value in 
http://codex.wordpress.org/Function_Reference/get_currentuserinfo 

CF7_get_current_user

[dynamictext dynamicname "CF7_get_current_user"]

**Referrer URL**

Get the referral URL, if it exists.  Note that this is not necessarily reliable as not all browsers send this data.

[dynamictext dynamicname "CF7_referrer"]


Like the Dynamic Text Extension?  Please consider supporting its development by [Donating](http://bit.ly/bVogDN).

Or check out my upcoming premium plugin, [UberMenu - WordPress Mega Menu Plugin](http://wpmegamenu.com)


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the Contact Form 7 Plugin located at http://wordpress.org/extend/plugins/contact-form-7/
1. Upload the plugin folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You will now have a "Dynamic Text" tag option in the Contact Form 7 tag generator


== Frequently Asked Questions ==

None.  Yet.


== Screenshots ==

1. The new Dynamic Text Field options.


== Changelog ==

= 2.0.2.1 =

* Update changelog properly for 2.0.2 changes:

= 2.0.2 =

* Update deprecated get_currentuserinfo() function to wp_get_current_user()
* Update deprecated functions from WPCF7_add_shortcode to WPCF7_add_formtag and class from WPCF7_Shortcode to WPCF7_FormTag to comply with CF7 4.6 changes

= 2.0.1 =

* Hook change to guarantee the plugin only runs when Contact Form 7 is present in the admin (avoids errors if Contact Form 7 is disabled, or if there is a plugin sequencing issue)

= 2.0 =

* Complete rewrite for Compatibility with Contact Form 7 v4

= 1.2 =
* Compatibility update for Contact Form 7 v3.9

= 1.1.0.2 =
* Updated to work with Contact Form 7 v3.7.x

= 1.1.0.1 =
* Removed undefined variable warning

= 1.1 =
* Updated for compatibility with Contact Form 7 v3.6
* Added Referrer shortcode

= 1.0.4.2 =
* Fixed a bug that created repeating square brackets around dynamic text values in cases where the form doesn't validate and JavaScript is deactivated.

= 1.0.4.1 =
* Removed trailing whitespace to fix "Headers already sent" errors

= 1.0.4 =
* Added Current User Info shortcode
* Added Post Custom Field shortcode (with obfuscation support)
* Added Hidden Field capability

= 1.0.3 =
* Added $_POST shortcode
* Added current post/page variable shortcode
* Added current URL shortcode

= 1.0.2 =
* Fixed administrative control panel dependency issue

= 1.0.1 =
* Fixed dependency issue.


== Upgrade Notice ==

2.0 complete rewrite for compatibility with latest Contact Form 7