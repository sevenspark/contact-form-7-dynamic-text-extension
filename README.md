Contact Form 7 - Dynamic Text Extension (DTX)
=======================================

Contact Form 7 is an excellent WordPress plugin and one of the top choices of free WordPress plugins for contact forms. Contact Form 7 - Dynamic Text Extension (DTX) makes it even more awesome by adding dynamic content capabilities. While default values in Contact Form 7 are static, DTX lets you create pre-populated fields pulled from other locations. Some examples might include:

* Auto-filling a URL or just getting the domain name or path
* Auto-filling a post ID, title, or slug
* Auto-filling a title, URL, or slug for the current page
* Pre-populating a product number
* Referencing other content on the site
* Populating with post or page info
* Populating with the current user's info
* Populating with custom and meta fields
* Generating unique identifiers for support tickets
* Getting a list of post categories or other custom taxonomies
* Getting a value from a cookie
* Getting custom theme modifications
* Any value using custom shortcodes

The possibilities are endless!

<a href="https://wordpress.org/plugins/contact-form-7-dynamic-text-extension/?preview=1" target="_blank" rel="noopener" style="border: 1px solid;border-radius: 3px;box-sizing: border-box;cursor: pointer;display: inline-block;height: auto;line-height: 1;margin: 0;padding: 8px 16px;text-decoration: none;white-space: nowrap; line-height: 1;background: #0085ba;border-color: #0073aa #006799 #006799;box-shadow: 0 1px 0 #006799;color: #ffffff;font-size:20px">View Demo</a>

## WHAT DOES IT DO? ##

DTX gives you two new form tags, dynamic text and dynamic hidden, to use in your contact forms. Using these dynamic form tags, you can dynamically pre-populate them with almost anything!

![Screenshot of the form tag buttons in the form editor of Contact Form 7. A red square highlights the addition of two new buttons for "dynamic text" and "dynamic hidden"](/_wp-assets/screenshot-1.png)

New users can use the form tag generators while advanced users can dive straight into the shortcodes.

<img src="/_wp-assets/screenshot-2.png" alt="The form tag generator screen for the dynamic text form tag" width="300" /> <img src="/_wp-assets/screenshot-3.png" alt="The form tag generator screen for the dynamic hidden form tag" width="300" />

DTX also comes with several built-in shortcodes that will allow the contact form to be populated from HTTPS GET variable or any info from the `get_bloginfo()` function, among others. See below for included shortcodes.

Don't see the shortcode you need on the list? You can write a [custom one](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/custom-shortcodes/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme)! Any shortcode that returns a string or numeric value can be used here. The included shortcodes just cover the most common scenarios, but DTX provides the flexibility for you to grab any value you have access to programmatically.

### Dynamic Value ###

The bread and butter of this plugin, set a dynamic value! This field can take any shortcode, with two important provisions:

1. The shortcode should NOT include the normal square brackets (`[` and `]`). So, instead of `[CF7_GET key='value']` you would use `CF7_GET key='value'`.
1. Any parameters in the shortcode must use single quotes. That is: `CF7_GET key='value'` and not `CF7_GET key="value"`

### Dynamic Placeholder ###

Set a dynamic placeholder with this attribute! This feature accepts static text or a shortcode. If using a shortcode, the same syntax applies from the dynamic value field. However, this field also has a few more needs:

1. The text/shortcode must first have apostrophes converted to it's HTML entity code, `&#39;`
1. After that, it must be URL encoded so that spaces become `%20` and other non-alphanumeric characters are converted.

If you're using Contact Form 7's tag generator to create the form tag, those extra needs are already taken care of. Dynamic placeholders are not available for dynamic hidden form tags.

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Compatible with Caching Plugins ###

DTX is cache friendly! You can set a field to be calculated after the page loads by setting the `dtx_pageload` attribute to any dynamic form tag.

Many websites use caching plugins to optimize for performance. If your website caches the HTML of the form, then any dynamic form fields you have get their first calculated value cached alongside it. This becomes an issue if you're using DTX to pull values from a cookie or the current URL's query string.

This is best for dynamic form fields that:

* gets the current URL
* gets a value from the URL query
* gets a value from a cookie
* gets the current user's info
* generates a unique identifier (GUID)

For dynamic fields that are page-specific, it's perfectly safe to cache those values. For example, dynamic form fields that:

* getting the page or post's ID, title, or slug
* getting post meta for the current page
* getting the post's assigned categories, tags, or other custom taxonomy
* getting site info
* getting theme modification values

*Note: Enabling a dynamic field to be calculated after the page loads will add frontend JavaScript. Depending on the shortcode used as the dynamic value, an AJAX call to the server may be sent to be processed. The script is minified and loaded in the footer and is deferred, minimizing impact on site performance and the AJAX calls are called asynchronously to avoid being a render-blocking resource and minimizing main-thread work. The script itself can be safely cached too.*

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/form-tag-attribute-after-page-load/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Read Only Form Fields ###

Check this box if you do not want to let users edit this field. It will add the `readonly` attribute to the input form field. This feature is not available for dynamic hidden form tags.

### Obfuscate Values for Enhanced Privacy ###

If you're pre-filling a form field with an email address, bots can scrape that value from the page and use it for spam. You can add an additional layer of protecting by obfuscating the value, which turns each character into it's ASCII code. To the human eye, it looks like the character it's supposed to be because browsers will render the ASCII code, but for bots, it won't look like an email address!

## HOW TO USE IT ##

After installing and activating the plugin, you will have 2 new tag types to select from when creating or editing a Contact Form 7 form: the dynamic text field and dynamic hidden field. Most of the options in their tag generators will be familiar to Contact Form 7 users but there have been some upgrades.

### How to Obfuscate Values ###

All of the shortcodes included with the DTX plugin allow the `obfuscate` attribute that you can set to any truthy value to provide an additional layer of security for sensitive data.

The Contact Form 7 tag with obfuscation turned on would look like this: `[dynamictext user_email "CF7_get_current_user key='user_email' obfuscate='on'"]`

### How to Enable Cache-Friendly Mode ###

All of the dynamic form tags can be enabled for processing on the frontend of the website, or the client-side, by adding the `dtx_pageload` attribute to the Contact Form 7 form tag.

In the form editor of Contact Form 7, your form tag would look like: `[dynamictext current_url dtx_pageload "CF7_URL"]`

If using the tag generator, it's as simple as checking a box!

## INCLUDED SHORTCODES ##

The plugin includes several shortcodes for use with the Dynamic Text Extension right out of the box. You can write your own as well—any self-closing shortcode will work, even with attributes!

### Current URL or Part ###

Retrieve the current URL: `CF7_URL`

In the form editor of Contact Form 7, your form tag would look like: `[dynamictext dynamicname "CF7_URL"]`

Optional parameter: `part`, which will return a parsed part of the URL.  Valid values are `host`, `query`, and `path`

Host: Just the domain name and tld
`[dynamictext host "CF7_URL part='host'"]`

Query: The query string after the ?, if one exists
`[dynamictext query "CF7_URL part='query'"]`

Path: The URL path, for example, /contact, if one exists
`[dynamictext path "CF7_URL part='path'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-url/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Referrer URL ###

Get the referral URL, if it exists. Note that this is not necessarily reliable as not all browsers send this data.

CF7 Tag: `[dynamictext dynamicname "CF7_referrer"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-referrer-url/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Current Page Variables ###

Retrieve information about the current page that the contact form is displayed on. Works great for use in templated areas like the site header, footer, widget, or sidebar! The shortcode works as follows:

Built-in shortcode: `CF7_get_current_var`

Required attribute: `key`

Possible values for `key` include:

* `id`
* `title`
* `url` (an alias for `CF7_URL`)
* `slug`
* `featured_image`
* `terms` (an alias for `CF7_get_taxonomy`)

For pages that use a `WP_POST` object, this acts as an alias for `CF7_get_post_var` so those attributes work here as well.

For author pages, this acts as an alias for `CF7_get_current_user` so those attributes work here as well.

In the form editor of Contact Form 7, your form tag's value could look like: `CF7_get_current_var key='title'`

And then the full form tag would be: `[dynamictext dynamicname "CF7_get_current_var key='title'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-variables/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Post/Page Info ###

Retrieve information about the current post or page (must be for a WP_POST object) that the contact form is displayed on. The shortcode works as follows:

`CF7_get_post_var key='title'`      <-- retrieves the Post's Title
`CF7_get_post_var key='slug'`       <-- retrieves the Post's Slug

You can also retrieve any parameter from the global `$post` object. Just set that as the `key` value, for example `post_date`

The Contact Form 7 Tag would look like: `[dynamictext dynamicname "CF7_get_post_var key='title'"]`

Need to pull data from a _different_ post/page? Not a problem! Just specify it's post ID like this:

Dynamic value: `CF7_get_post_var key='title' post_id='245'`

Contact Form 7 Tag: `[dynamictext dynamicname "CF7_get_post_var key='title' post_id='245'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-page-variables//?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Post Meta & Custom Fields ###

Retrieve custom fields from the current post/page. Just set the custom field as the key in the shortcode.

The dynamic value input becomes: `CF7_get_custom_field key='my_custom_field'`

And the tag looks like this: `[dynamictext dynamicname "CF7_get_custom_field key='my_custom_field'"]`

For the purposes of including an email address, you can obfuscate the custom field value by setting obfuscate='on' in the shortcode like this:
`[dynamictext dynamicname "CF7_get_custom_field key='my_custom_field' obfuscate='on'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-meta-custom-fields/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Featured Images & Media Attachments ###

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

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-media-attachment/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Current User Info & User Meta ###

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

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-user-user-meta/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Site/Blog Info ###

Want to grab some information from your blog like the URL or the site name? Use the `CF7_bloginfo` shortcode. For example, to get the site's URL:

Enter the following into the "Dynamic Value" input: `CF7_bloginfo show='url'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_bloginfo show='url'"]`

Your form's dynamicname text input will then be pre-populated with your site's URL

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-site-blog-information/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Theme Options ###

Want to retrieve values from your active theme's Customizer? Now you can with the `CF7_get_theme_option` shortcode.

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-theme-option/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### HTTP GET Variables ###

Want to use a variable from the PHP `$_GET` array? Just use the `CF7_GET` shortcode. For example, if you want to get the foo parameter from the url
`http://mysite.com?foo=bar`

Enter the following into the "Dynamic Value" input: `CF7_GET key='foo'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_GET key='foo'"]`

Your form's dynamicname text input will then be pre-populated with the value of `foo`, in this case, `bar`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-get-variables/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### HTTP POST Variables ###

Grab variables from the PHP `$_POST` array. The shortcode is much like the GET shortcode:

Dynamic value: `CF7_POST key='foo'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_POST key='foo'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-post-variables/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### Cookie Values ###

If your WordPress website uses cookies, you might want to pull the value of a specific cookie into a form. You can do that with the `CF7_get_cookie` shortcode. It only needs a `key` attribute.

Dynamic value: `CF7_get_cookie key='foo'`

Your Content Form 7 Tag will look something like this: `[dynamictext dynamicname "CF7_get_cookie key='foo'"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-cookie/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

### GUID ###

Generate a globally unique identifier (GUID) in a form field. This is a great utility shortcode for forms that need unique identifiers for support tickets, receipts, reference numbers, etc., without having to expose personally identifiable information (PII). This shortcode takes no parameters: `CF7_guid`

In the form editor of Contact Form 7, your form tag would look like: `[dynamictext dynamicname "CF7_guid"]`

Learn more and see examples from [the DTX Knowledge base](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-guid/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

## Installation ##

### Minimum Requirements ###

To ensure your WordPress installation meets these requirements, you can login to your WordPress website and navigate to *Tools > Site Health* and click on the *Info* tab. Expand the *WordPress*, *Active Plugins*, and *Server* accordions and compare that information with the details below.

* WordPress version 5.5 or greater
* PHP version 7.4 or greater
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) version 5.7 or greater

There are three (3) ways to install this plugin: automatically, upload, or manually.

### Install Method 1: Automatic Installation ###

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser.

1. Log into your WordPress dashboard.
1. Navigate to **Plugins > Add New**.
1. Where it says "Keyword" in a dropdown, change it to "Author"
1. In the search form, type `TessaWatkinsLLC` (results may begin populating as you type but my plugins will only show when the full name is there)
1. Once you've found this plugin in the search results that appear, click the **Install Now** button and wait for the installation process to complete.
1. Once the installation process is completed, click the **Activate** button to activate the plugin.

### Install Method 2: Upload via WordPress Admin ###

This method is a little more involved. You don't need to leave your web browser, but you'll need to download and then upload the files yourself.

1. [Download this plugin](https://wordpress.org/plugins/contact-form-7-dynamic-text-extension/) from WordPress.org; it will be in the form of a zip file.
1. Log in to your WordPress dashboard.
1. Navigate to **Plugins > Add New**.
1. Click the **Upload Plugin** button at the top of the screen.
1. Select the zip file from your local file system that was downloaded in step 1.
1. Click the **Install Now** button and wait for the installation process to complete.
1. Once the installation process is completed, click the **Activate** button to activate the plugin.

### Install Method 3: Manual Installation ###

This method is the most involved as it requires you to be familiar with the process of transferring files using an SFTP client.

1. [Download this plugin](https://wordpress.org/plugins/contact-form-7-dynamic-text-extension/) from WordPress.org; it will be in the form of a zip file.
1. Unzip the contents; you should have a single folder named `contact-form-7-dynamic-text-extension`.
1. Connect to your WordPress server with your favorite SFTP client.
1. Copy the folder from step 2 to the `/wp-content/plugins/` folder in your WordPress directory. Once the folder and all of its files are there, installation is complete.
1. Now log in to your WordPress dashboard.
1. Navigate to **Plugins > Installed Plugins**. You should now see my plugin in your list.
1. Click the **Activate** button that appeared to activate it.

## Frequently Asked Questions ##

Please check out the [FAQ on the website](https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/frequently-asked-questions/?utm_source=github.com&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=readme).

## WordPress Changelog ##

Please see our [changelog.txt file](https://plugins.trac.wordpress.org/browser/contact-form-7-dynamic-text-extension/trunk/changelog.txt) for updates released to the WordPress repository as they may be different than what's committed here.
