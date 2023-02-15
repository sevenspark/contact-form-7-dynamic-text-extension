=== Contact Form 7 - Dynamic Text Extension ===
Contributors: sevenspark, tessawatkinsllc
Donate link: https://just1voice.com/donate/
Tags: Contact Form 7, contact, contact form, dynamic, text, input, GET, POST, title, slug, autofill, auto-fill, prepopulate, pre-populate, form field
Tested up to: 6.1.1
Stable tag: 3.2

This plugin provides additional form tags for the Contact Form 7 plugin. It allows dynamic generation of content for text or hidden input fields using any shortcode.

== Description ==

Contact Form 7 is an excellent WordPress plugin and one of the top choices of free WordPress plugins for contact forms. Contact Form 7 - Dynamic Text Extension (DTX) makes it even more awesome by adding dynamic content capabilities. While default values in Contact Form 7 are static, DTX lets you create pre-populated fields based on other values. Some examples might include:

* Auto-filling a URL
* Auto-filling a post ID, title, or slug
* Pre-populating a product number
* Referencing other content on the site
* Populating with post info
* Populating with user info
* Populating with custom fields
* Generating unique identifiers for support tickets
* Any value using custom shortcodes

The possibilities are endless!

= WHAT DOES IT DO? =

DTX comes with several built-in shortcodes that will allow the contact form to be populated from HTTPS GET variable or any info from the `get_bloginfo()` function, among others. See below for included shortcodes.

Don't see the shortcode you need on the list? You can write a [custom one](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/custom-shortcodes/?utm_source=wordpress.org&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme)! Any shortcode that returns a string or numeric value can be used here. The included shortcodes just cover the most common scenarios, but DTX provides the flexibility for you to grab any value you have access to programmatically.

= HOW TO USE IT =

After installing and activating the plugin, you will have 2 new tag types to select from when creating or editing a Contact Form 7 form: the dynamic text field and dynamic hidden field. Most of the options in their tag generators will be familiar to Contact Form 7 users but there have been some upgrades.

**Dynamic Value**

This fields can take a shortcode, with two important provisions:

1. The shortcode should NOT include the normal square brackets (`[` and `]`). So, instead of `[CF7_GET key='value']` you would use `CF7_GET key='value'`.
1. Any parameters in the shortcode must use single quotes. That is: `CF7_GET key='value'` and not `CF7_GET key="value"`

**Dynamic placeholder**

Only available for the dynamic text form tag, this field can take static text or a shortcode. If using a shortcode, the same syntax applies from the dynamic value field. However, this field also has a few more needs:

1. The text/shortcode must first have apostrophes converted to it's HTML entity code, `&#39;`
1. After that, it must be URL encoded so that spaces become `%20` and other non-alphanumeric characters are converted.

**Read Only Attribute**

Only available for the dynamic text form tag, simply check this box if you do not want to let users edit this field. It will add the `readonly` attribute to your form field.

= INCLUDED SHORTCODES =

The plugin includes several shortcodes for use with the Dynamic Text Extension right out of the box. You can write your own as well—any self-closing shortcode will work, even with attributes!

**Current URL or Current URL Part**

Retrieve the current URL: `CF7_URL`

Your Contact Form 7 Tag would look like: `[dynamictext dynamicname "CF7_URL"]`

Optional parameter: `part`, which will return a parsed part of the URL.  Valid values are `host`, `query`, and `path`

Host: Just the domain name and tld 
`[dynamictext host "CF7_URL part='host'"]`

Query: The query string after the ?, if one exists 
`[dynamictext query "CF7_URL part='query'"]`

Path: The URL path, for example, /contact, if one exists 
`[dynamictext path "CF7_URL part='path'"]`



**Referrer URL**

Get the referral URL, if it exists. Note that this is not necessarily reliable as not all browsers send this data.

CF7 Tag: `[dynamictext dynamicname "CF7_referrer"]`

**Post/Page Info**

Retrieve information about the current post or page that the contact form is displayed on. The shortcode works as follows:

`CF7_get_post_var key='title'`      <-- retrieves the Post's Title
`CF7_get_post_var key='slug'`       <-- retrieves the Post's Slug

You can also retrieve any parameter from the global `$post` object. Just set that as the `key` value, for example `post_date`

The Contact Form 7 Tag would look like: `[dynamictext dynamicname "CF7_get_post_var key='title'"]`

Need to pull data from a _different_ post/page? Not a problem! Just specify it's post ID like this:

Dynamic value: `CF7_get_post_var key='title' post_id='245'`

Contact Form 7 Tag: `[dynamictext dynamicname "CF7_get_post_var key='title' post_id='245'"]`

**Post Meta & Custom Fields**

Retrieve custom fields from the current post/page. Just set the custom field as the key in the shortcode.

The dynamic value input becomes: `CF7_get_custom_field key='my_custom_field'`

And the tag looks like this: `[dynamictext dynamicname "CF7_get_custom_field key='my_custom_field'"]`

For the purposes of including an email address, you can obfuscate the custom field value by setting obfuscate='on' in the shortcode like this:
`[dynamictext dynamicname "CF7_get_custom_field key='my_custom_field' obfuscate='on'"]`

**Featured Images & Media Attachments**

Retrieve the current post's featured image, the featured image of a different page, or any attachment from the Media Library with this shortcode!

The base shortcode is simply: `CF7_get_attachment` which returns the absolute URL of the current page's featured image.

By setting the `post_id` attribute to a post ID, you can get the featured image of another page.

By setting the `id` attribute to an attachment ID, you can get the absolute URL of any image uploaded to your WordPress website.

By setting the `size` attribute to any size registered on your website, you can get a specific image size.

Want to return the attachment ID instead of the URL? Also not a problem! Just set `return='id'` in the shortcode.

Most of the optional attributes can be used at the same time. For example, if I wanted to retrieve the attachment ID of a featured image for a different post, then the dynamic text form tag would look like this:
`[dynamictext input_name "CF7_get_attachment post_id='123' return='id'"]`

If I wanted to get a specific image at a specific size, I can use this:
`[dynamictext input_name "CF7_get_attachment id='123' size='thumbnail'"]`

The only two attributes that can’t play together is `id` and `post_id`. If both are specified, it will get the attachment specified by `id` and completely ignore the `post_id` attribute. If neither are specified, then it looks to the current featured image assigned to the global `$post` object.

**Current User Info & User Meta**

Get data about the current logged-in user.

Dynamic value: `CF7_get_current_user key='user_displayname'`
CF7 Tag: `[dynamictext dynamicname "CF7_get_current_user"]`

Valid values for `key` include:

* `ID`
* `user_login`
* `display_name`
* `user_email`
* `user_firstname`
* `user_lastname`
* `user_description`

But also custom meta user keys!

For the purposes of including an email address, you can obfuscate the value by setting obfuscate='on' in the shortcode like this:
`[dynamictext dynamicname "CF7_get_current_user key='user_email' obfuscate='on'"]`

**Site/Blog Info**

Want to grab some information from your blog like the URL or the site name? Use the `CF7_bloginfo` shortcode. For example, to get the site's URL:

Enter the following into the "Dynamic Value" input: `CF7_bloginfo show='url'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_bloginfo show='url'"]`

Your form's dynamicname text input will then be pre-populated with your site's URL

**HTTP GET Variables**

Want to use a variable from the PHP `$_GET` array? Just use the `CF7_GET` shortcode. For example, if you want to get the foo parameter from the url
`http://mysite.com?foo=bar`

Enter the following into the "Dynamic Value" input: `CF7_GET key='foo'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_GET key='foo'"]`

Your form's dynamicname text input will then be pre-populated with the value of `foo`, in this case, `bar`

**HTTP POST Variables**

Grab variables from the PHP `$_POST` array. The shortcode is much like the GET shortcode:

Dynamic value: `CF7_POST key='foo'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_POST key='foo'"]`

**GUID**

Generate a globally unique identifier (GUID) in a form field. This is a great utility shortcode for forms that need unique identifiers for support tickets, receipts, reference numbers, etc., without having to expose personally identifiable information (PII). This shortcode takes no parameters: `CF7_guid`

Your Contact Form 7 Tag would look like: `[dynamictext dynamicname "CF7_guid"]`

**Shortcode attribute: obfuscate**

All of the included shortcodes have an `obfuscate` attribute that you can set to any truthy value to provide an additional layer of security for sensitive data.

== Installation ==

There are three (3) ways to install my plugin: automatically, upload, or manually.

= Install Method 1: Automatic Installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser.

1. Log in to your WordPress dashboard.
1. Navigate to **Plugins > Add New**.
1. Where it says “Keyword” in a dropdown, change it to “Author”
1. In the search form, type “TessaWatkinsLLC” (results may begin populating as you type but my plugins will only show when the full name is there)
1. Once you’ve found my plugin in the search results that appear, click the **Install Now** button and wait for the installation process to complete.
1. Once the installation process is completed, click the **Activate** button to activate my plugin.

= Install Method 2: Upload via WordPress Admin =

This method involves is a little more involved. You don’t need to leave your web browser, but you’ll need to download and then upload the files yourself.

1. [Download my plugin](https://wordpress.org/plugins/contact-form-7-dynamic-text-extension/) from WordPress.org; it will be in the form of a zip file.
1. Log in to your WordPress dashboard.
1. Navigate to **Plugins > Add New**.
1. Click the **Upload Plugin** button at the top of the screen.
1. Select the zip file from your local file system that was downloaded in step 1.
1. Click the **Install Now** button and wait for the installation process to complete.
1. Once the installation process is completed, click the **Activate** button to activate my plugin.

= Install Method 3: Manual Installation =

This method is the most involved as it requires you to be familiar with the process of transferring files using an SFTP client.

1. [Download my plugin](https://wordpress.org/plugins/contact-form-7-dynamic-text-extension/) from WordPress.org; it will be in the form of a zip file.
1. Unzip the contents; you should have a single folder named `contact-form-7-dynamic-text-extension`.
1. Connect to your WordPress server with your favorite SFTP client.
1. Copy the folder from step 2 to the `/wp-content/plugins/` folder in your WordPress directory. Once the folder and all of its files are there, installation is complete.
1. Now log in to your WordPress dashboard.
1. Navigate to **Plugins > Installed Plugins**. You should now see my plugin in your list.
1. Click the **Activate** button under my plugin to activate it.

== Screenshots ==

1. A screenshot of the form-tag generator for the dynamic text field.

== Frequently Asked Questions ==

Please check out the [FAQ on our website](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/frequently-asked-questions/?utm_source=wordpress.org&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

== Upgrade Notice ==

* 3.1.3 Fixed the syntax error that reappeared in 3.1.2. My apologies!

== Changelog ==

= 3.2 =

* Feature: Add optional 'part' parameter to CF7_URL shortcode to retrieve Host, Query, or Path from current URL
* Updated minimum PHP requirement to 7.4 moving forward
* Update branding assets
* Update Tested Up To to 6.1.1
* Plugin will now be jointly maintained by SevenSpark and AuRise Creative


= 3.1.3 =

* Fix: Fixed the syntax error that reappeared in 3.1.2.

= 3.1.2 =

**Release Date: January 27, 2023**

* Fix: updated the text domain to match the plugin slug
* Fix: updated all of the translated strings to match

= 3.1.1 =

**Release Date: January 26, 2023**

* Fix: Fixed the syntax error: Parse error: syntax error, unexpected `)` in /wp-content/plugins/contact-form-7-dynamic-text extension/includes/admin.php on line 212

= 3.1.0 =

**Release Date: January 25, 2023**

* Feature: Added the `CF7_get_attachment` shortcode. For usage details, see the [knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-media-attachment/?utm_source=wordpress.org&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme)
* Feature: Added the `CF7_guid` shortcode. For usage details, see the [knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-guid/?utm_source=wordpress.org&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).
* Feature: Added the dynamic placeholder option to the dynamic form tags that allows you to specify dynamic or static placeholder content while also setting dynamic values. For usage details, see the [knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/?utm_source=wordpress.org&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme)
* Feature: Added a "required" dynamic hidden tag (e.g., `[dynamichidden* ...]`). It is identical to the original dynamic hidden tag (as in the field is not actually validated as required because it is hidden); it just doesn't break your website if you use it. This feature was requested by a user.
* Feature: Added the `obfuscate` attribute to all included shortcodes

= 3.0.0 =

**Release Date: January 17, 2023**

* Major: Plugin was adopted by AuRise Creative
* Major: All functions use the `wpcf7dtx_` prefix
* Feature: Added a `post_id` key for the `CF7_get_post_var` shortcode so you can specify a different post
* Feature: Updated the `CF7_get_current_user` shortcode to be able to pull data from user metadata too
* Feature: Added the "obfuscate" option to `CF7_get_custom_field` shortcode
* Feature: Added the "placeholder" checkbox option to the `dynamictext` tag
* Fix: Added additional validation for post ID input
* Fix: Added additional validation for the `key` attribute in the `CF7_GET` and `CF7_POST` shortcodes
* Fix: Shortcode keys are normalized into lowercase before processing
* Security: Sanitizing URLs for the `CF7_URL` and `CF7_referrer` shortcode outputs
* Feature/Security: Added a `allowed_protocols` attribute to the `CF7_URL` and `CF7_referrer` shortcodes that defaults to `http,https`

= 2.0.3 =

* Security: [Fix Reflected XSS](https://web.archive.org/web/20230121180428/https://sevenspark.com/docs/cf7-dtx-security-2019-07-24)

= 2.0.2.1 =

* Update changelog properly for 2.0.2 changes:

= 2.0.2 =

* Update deprecated `get_currentuserinfo()` function to `wp_get_current_user()`
* Update deprecated functions from `WPCF7_add_shortcode` to `WPCF7_add_formtag` and class from `WPCF7_Shortcode` to `WPCF7_FormTag` to comply with CF7 4.6 changes

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