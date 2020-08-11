=== Contact Form 7 Dynamic Text Extension ===
Contributors: sevenspark
Donate link: http://bit.ly/bVogDN
Tags: Contact Form 7, Contact, Contact Form, dynamic, text, input, GET, POST, title, slug
Requires at least: 5.0
Tested up to: 5.5
Stable tag: 2.0.3

This plugin provides 2 new tag types for the Contact Form 7 Plugin. It allows the dynamic generation of the default value for a text input box via any shortcode.

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

There are many more case-specific examples, and since you can use any shortcode, you can add any text data to the form that can be generated on the server with PHP.



= WHAT DOES IT DO? =

This plugin provides a new tag type for the Contact Form 7 Plugin. It allows the dynamic generation of content for a text input box via any shortcode.
For example, it comes with several built-in shortcodes that will allow the Contact Form to be populated from any $_GET PHP variable or any info from the
get_bloginfo() function, among others.


= KNOWLEDGEBASE =

Please visit the [Contact Form 7 - Dynamic Text Extension Knowledgebase](https://sevenspark.com/docs/contact-form-7-dynamic-text-extension) for full documentation.


Like the Dynamic Text Extension?  Please consider supporting its development by [Donating](http://bit.ly/bVogDN).

Or check out other SevenSpark plugins, [UberMenu - WordPress Mega Menu Plugin](https://wpmegamenu.com), [ShiftNav - Mobile Menu Plugin](https://shiftnav.io), [Bellows Accordion Menu](https://wpaccordionmenu.com)


== Installation ==

[Installation Guide](https://sevenspark.com/docs/contact-form-7-dynamic-text-extension/install)
[Quick Start Guide](https://sevenspark.com/docs/contact-form-7-dynamic-text-extension/quick-start)


== Frequently Asked Questions ==

Visit the [FAQs](https://sevenspark.com/docs/contact-form-7-dynamic-text-extension/faqs)


== Screenshots ==

1. The new Dynamic Text Field options.


== Changelog ==

= 2.0.3 =

* Please update!
* Security: Fix Reflected XSS - more: https://sevenspark.com/docs/cf7-dtx-security-2019-07-24

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
