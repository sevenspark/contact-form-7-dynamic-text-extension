<?php

/*****************************************************
 * Included Shortcodes
 *
 * See documentation for usage:
 * https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/
 *
 *****************************************************/

/**
 * Initialise DTX included shortcodes
 *
 * Hooked to `init`
 *
 * @return void
 */
function wpcf7dtx_init_shortcodes()
{
    add_shortcode('CF7_GET', 'wpcf7dtx_get', 10, 1);
    add_shortcode('CF7_POST', 'wpcf7dtx_post', 10, 1);
    add_shortcode('CF7_URL', 'wpcf7dtx_url', 10, 1);
    add_shortcode('CF7_referrer', 'wpcf7dtx_referrer', 10, 1);
    add_shortcode('CF7_bloginfo', 'wpcf7dtx_bloginfo', 10, 1);
    add_shortcode('CF7_get_post_var', 'wpcf7dtx_get_post_var', 10, 1);
    add_shortcode('CF7_get_custom_field', 'wpcf7dtx_get_custom_field', 10, 1);
    add_shortcode('CF7_get_current_var', 'wpcf7dtx_get_current_var', 10, 1);
    add_shortcode('CF7_get_current_user', 'wpcf7dtx_get_current_user', 10, 1);
    add_shortcode('CF7_get_attachment', 'wpcf7dtx_get_attachment', 10, 1);
    add_shortcode('CF7_get_cookie', 'wpcf7dtx_get_cookie', 10, 1);
    add_shortcode('CF7_get_taxonomy', 'wpcf7dtx_get_taxonomy', 10, 1);
    add_shortcode('CF7_get_theme_option', 'wpcf7dtx_get_theme_option', 10, 1);
    add_shortcode('CF7_guid', 'wpcf7dtx_guid', 10, 0);
}
add_action('init', 'wpcf7dtx_init_shortcodes'); //Add init hook to add shortcodes

/**
 * Get Variable from $_GET Array
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-get-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get($atts = array())
{
    extract(shortcode_atts(array(
        'key' => 0,
        'default' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $value = apply_filters('wpcf7dtx_sanitize', wpcf7dtx_array_has_key($key, $_GET, $default));
    return apply_filters('wpcf7dtx_escape', $value, $obfuscate);
}

/**
 * Get Variable from $_POST Array
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-post-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_post($atts = array())
{
    extract(shortcode_atts(array(
        'key' => '',
        'default' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $value = apply_filters('wpcf7dtx_sanitize', wpcf7dtx_array_has_key($key, $_POST, $default));
    return apply_filters('wpcf7dtx_escape', $value, $obfuscate);
}

/**
 * Get Current URL or Part
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-url/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_url($atts = array())
{
    extract(shortcode_atts(array(
        'allowed_protocols' => '',
        'part' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $allowed_protocols = explode(',', sanitize_text_field($allowed_protocols));

    // Get the absolute URL
    if (is_multisite() && !is_subdomain_install()) {
        // Network installs not using subdomains
        $url = apply_filters('wpcf7dtx_sanitize', network_home_url($_SERVER['REQUEST_URI']), 'url', $allowed_protocols);
    } else {
        // Single installs and network installs using subdomains
        $url = apply_filters('wpcf7dtx_sanitize', home_url($_SERVER['REQUEST_URI']), 'url', $allowed_protocols);
    }
    if ($url && !empty($part = sanitize_key(strtolower($part)))) {
        // If an individual part is requested, get that specific value using parse_url()
        $part_constant_map = [
            'scheme' => PHP_URL_SCHEME, // e.g. `http`
            'host'  => PHP_URL_HOST, // the domain (or subdomain) of the current website
            'path'  => PHP_URL_PATH, // e.g. `/path/to/current/page/`
            'query' => PHP_URL_QUERY // after the question mark ?
        ];
        $value = '';
        if (array_key_exists($part, $part_constant_map)) {
            $value = apply_filters('wpcf7dtx_sanitize', strval(wp_parse_url($url, $part_constant_map[$part])), 'text');
        }
        return apply_filters('wpcf7dtx_escape', $value, $obfuscate, 'text');
    }
    // No part requested, return the absolute URL
    return apply_filters('wpcf7dtx_escape', $url, $obfuscate, 'url', $allowed_protocols);
}

/**
 * Get Referrering URL
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-referrer-url/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_referrer($atts = array())
{
    extract(shortcode_atts(array(
        'allowed_protocols' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    if ($value = wpcf7dtx_array_has_key('HTTP_REFERER', $_SERVER)) {
        $value = apply_filters('wpcf7dtx_sanitize', $value, 'url', $allowed_protocols);
        return apply_filters('wpcf7dtx_escape', $value, $obfuscate, 'url');
    }
    return '';
}

/**
 * Get Variable from Bloginfo
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-page-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_bloginfo($atts = array())
{
    extract(shortcode_atts(array(
        'show' => 'name', //Backwards compatibility
        'key' => 'name',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $key = $show != $key && $show != 'name' ? $show : $key; // Use old value of "show" if not set to default value
    return apply_filters('wpcf7dtx_escape', get_bloginfo($key), $obfuscate);
}

/**
 * Get Variable from a Post Object
 *
 * @link https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-page-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_post_var($atts = array())
{
    extract(shortcode_atts(array(
        'key' => 'post_title',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $key = strtolower(apply_filters('wpcf7dtx_sanitize', $key));
    switch ($key) {
        case 'acf_id': // If requesting the handle for ACF, return the post ID
        case 'id':
            $key = 'ID';
            break;
        case 'slug': // Alias
            $key = 'post_name';
            break;
        case 'title': // Alias
            $key = 'post_title';
            break;
        default:
            break;
    }
    $post_id = wpcf7dtx_get_post_id($post_id);
    if ($post_id) {
        return apply_filters('wpcf7dtx_escape', get_post_field($key, $post_id), $obfuscate);
    }
    return '';
}

/**
 * Get Value from Post Meta Field
 *
 * @see  https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-meta-custom-fields/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_custom_field($atts = array())
{
    extract(shortcode_atts(array(
        'key' => '',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));

    // If this key can't be accessed
    if (!wpcf7dtx_post_meta_key_access_is_allowed($key)) {
        // Trigger a warning if a denied key is in use
        wpcf7dtx_access_denied_alert($key, 'post_meta');
        return '';
    }

    $post_id = wpcf7dtx_get_post_id($post_id);
    $key = apply_filters('wpcf7dtx_sanitize', $key, 'text');
    if ($post_id && $key) {
        return apply_filters('wpcf7dtx_escape', get_post_meta($post_id, $key, true), $obfuscate);
    }
    return '';
}

/**
 * Get Variable from the Current Object
 *
 * @since 3.4.0
 *
 * @link https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_current_var($atts = array())
{
    extract(shortcode_atts(array(
        'key' => 'title',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $key = apply_filters('wpcf7dtx_sanitize', $key);
    $temp_key = str_replace('-', '_', sanitize_key($key));
    if ($temp_key === 'url') {
        return wpcf7dtx_url($atts); // Getting the current URL is the same for all WordPress pages
    } elseif (!empty($key)) {
        $type = '';
        $obj = null;
        if (!wp_doing_ajax()) {
            $obj = get_queried_object(); // Get the current WordPress queried object
            if (!is_null($obj)) {
                if ($obj instanceof WP_User) {
                    $type = 'user';
                } elseif (property_exists($obj, 'ID')) {
                    $type = 'post';
                } elseif (property_exists($obj, 'term_id')) {
                    $type = 'term';
                }
            } elseif (is_archive()) {
                $type = 'archive';
            }
        }
        switch ($type) {
            case 'user': // This is an author page
                switch ($temp_key) {
                    case 'acf_id': // Get handle for Advanced Custom Fields
                        return apply_filters('wpcf7dtx_escape', 'user_' . $obj->ID, $obfuscate);
                    case 'image':
                    case 'featured_image': // Get the profile picture of the user being displayed on the page
                        return apply_filters('wpcf7dtx_escape', get_avatar_url($obj->ID), $obfuscate, 'url');
                    case 'title': // Get author's display name
                        return apply_filters('wpcf7dtx_escape', $obj->display_name, $obfuscate);
                    case 'slug': // Not all author pages use the `user_login` variable for security reasons, so get what is currently displayed as slug
                        return apply_filters('wpcf7dtx_escape', basename(wpcf7dtx_url(array('part' => 'path'))), $obfuscate);
                    default: // Get user value by key should it exist
                        return apply_filters('wpcf7dtx_escape', $obj->get($key), $obfuscate);
                }
            case 'post': // This is a post object
                switch ($temp_key) {
                    case 'image':
                    case 'featured_image': // Get the current post's featured image
                        return wpcf7dtx_get_attachment(array_merge($atts, array('post_id' => $obj->ID)));
                    case 'terms': // Get the current post's assigned terms
                        return wpcf7dtx_get_taxonomy(array_merge($atts, array('post_id' => $obj->ID)));
                    default:
                        // Use the post object shortcode should it exist as a post variable
                        $value = wpcf7dtx_get_post_var(array_merge($atts, array('post_id' => $obj->ID)));
                        if (empty($value)) {
                            // Try post meta if post object variable failed
                            $value = wpcf7dtx_get_custom_field(array_merge($atts, array('post_id' => $obj->ID)));
                        }
                        return $value;
                }
            case 'term': // This is a taxonomy with a term ID
                switch ($key) {
                    case 'id': // Get term ID
                        return apply_filters('wpcf7dtx_escape', $obj->term_id, $obfuscate);
                    case 'acf_id': // Get handle for Advanced Custom Fields
                        return apply_filters('wpcf7dtx_escape', $obj->taxonomy . '_' . $obj->term_id, $obfuscate);
                    case 'title': // Get term name
                        return apply_filters('wpcf7dtx_escape', $obj->name, $obfuscate);
                    default:
                        if (property_exists($obj, $key)) {
                            // Get any property if it exists
                            return apply_filters('wpcf7dtx_escape', $obj->{$key}, $obfuscate);
                        }
                        // Otherwise, try meta data if the property doesn't exist
                        return apply_filters('wpcf7dtx_escape', get_metadata('term', $obj->ID, $key, true), $obfuscate);
                }
            case 'archive': // Possibly a date or formats archive
                switch ($temp_key) {
                    case 'title': // Get archive title
                        return apply_filters('wpcf7dtx_escape', get_the_archive_title(), $obfuscate);
                    default:
                        break;
                }
            default: // Possibly a search or 404 page at this point
                if ($temp_key == 'slug') {
                    // no idea what else to get except the slug maybe
                    return apply_filters('wpcf7dtx_escape', basename(wpcf7dtx_url(array('part' => 'path'))), $obfuscate);
                }
                break;
        }
    }
    return '';
}

/**
 * Get Value from Current User
 *
 * Retreives data from the `users` and `usermeta` tables.
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-user-user-meta/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_current_user($atts = array())
{
    extract(shortcode_atts(array(
        'key' => 'user_login',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    if (is_user_logged_in()) {

        // If this key can't be accessed
        if (!wpcf7dtx_user_data_access_is_allowed($key)) {
            // Trigger a warning if a denied key is in use
            wpcf7dtx_access_denied_alert($key, 'user_data');
            return '';
        }

        $user = wp_get_current_user();
        return apply_filters('wpcf7dtx_escape', $user->get($key), $obfuscate);
    }
    return '';
}

/**
 * Get Attachment
 *
 * Retreives an attachment ID or absolute URL depending on attributes
 *
 * @since 3.1.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-media-attachment/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_attachment($atts = array())
{
    extract(shortcode_atts(array(
        'id' => '', //Get attachment by ID
        'size' => 'full', //Define attachment size
        'post_id' => '', //If attachment ID is empty but post ID is not, get the featured image
        'return' => 'url', //Options are `id` or `url`
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));

    //No attachment ID was provided, check for post ID to get it's featured image
    if (empty($id)) {
        if ($post_id = wpcf7dtx_get_post_id($post_id)) {
            //If a post ID was provided, get it's featured image
            $id = get_post_thumbnail_id($post_id);
        }
    }

    //Get the value
    if ($id) {
        $id = intval(sanitize_text_field(strval($id)));
        switch ($return) {
            case 'id': //Return the attachment ID
                return apply_filters('wpcf7dtx_escape', $id, $obfuscate);
            default: //Return attachment URL
                $url = wp_get_attachment_image_url(intval($id), sanitize_text_field(strval($size)));
                return apply_filters('wpcf7dtx_escape', $url, $obfuscate, 'url');
        }
    }
    return '';
}

/**
 * Get Cookie Value
 *
 * Retreives the value of a cookie
 *
 * @since 3.3.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-cookie/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_cookie($atts = array())
{
    extract(shortcode_atts(array(
        'key' => '',
        'default' => '',
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $key = apply_filters('wpcf7dtx_sanitize', $key);
    $value = wpcf7dtx_array_has_key($key, $_COOKIE, $default);
    return apply_filters('wpcf7dtx_escape', $value, $obfuscate);
}

/**
 * Get Taxonomy
 *
 * Retreives a list of taxonomy values
 *
 * @since 3.3.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-taxonomy/
 * @see https://developer.wordpress.org/reference/classes/wp_term_query/get_terms/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_taxonomy($atts = array())
{
    extract(shortcode_atts(array(
        'post_id' => '',
        'taxonomy' => 'category', // Default taxonomy is `category`
        'fields' => 'names', // Return an array of term names
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $post_id = wpcf7dtx_get_post_id($post_id);
    $fields = apply_filters('wpcf7dtx_sanitize', $fields, 'key');
    if ($post_id && in_array($fields, array('names', 'slugs', 'ids'))) {
        $terms = wp_get_object_terms(
            $post_id, // Get only the ones assigned to this post
            apply_filters('wpcf7dtx_sanitize', $taxonomy, 'slug'),
            array('fields' => $fields)
        );
        if (is_array($terms) && count($values = array_values($terms)) && (is_string($values[0]) || is_numeric($values[0]))) {
            return apply_filters('wpcf7dtx_escape', implode(', ', $values), $obfuscate, 'text');
        }
    }
    return '';
}

/**
 * Get Theme Customization Option
 *
 * Retreives theme modification value for the active theme
 *
 * @since 3.3.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-theme-option/
 * @see https://developer.wordpress.org/reference/functions/get_theme_mod/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_theme_option($atts = array())
{
    extract(shortcode_atts(array(
        'key' => '',
        'default' => '', // Optional default value
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    if ($key = apply_filters('wpcf7dtx_sanitize', $key, 'text')) {
        $default = apply_filters('wpcf7dtx_sanitize', $default);
        return apply_filters('wpcf7dtx_escape', get_theme_mod($key, $default), $obfuscate);
    }
    return '';
}

/**
 * GUID Field
 *
 * Generate a random GUID (globally unique identifier)
 *
 * @since 3.1.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-guid/
 *
 * @return string a randomly generated 128-bit text string.
 */
function wpcf7dtx_guid()
{
    if (function_exists('com_create_guid') === true) {
        return esc_attr(trim(com_create_guid(), '{}'));
    }
    return esc_attr(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
}
