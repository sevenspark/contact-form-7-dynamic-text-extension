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
 * @shortcode CF7_GET
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-get-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => 0,
        'default' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $raw = wpcf7dtx_array_has_key($atts['key'], $_GET, $atts['default']);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters(
            'wpcf7dtx_escape',
            apply_filters('wpcf7dtx_sanitize', $raw),
            $atts['obfuscate']
        ), // Sanitized & escaped value to output
        $raw, // Raw value
        'GET', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Variable from $_POST Array
 *
 * @shortcode CF7_POST
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-php-post-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_post($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => '',
        'default' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $raw = wpcf7dtx_array_has_key($atts['key'], $_POST, $atts['default']);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', apply_filters(
            'wpcf7dtx_sanitize',
            apply_filters('wpcf7dtx_sanitize', $raw)
        ), $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'POST', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Current URL or Part
 *
 * @shortcode CF7_URL
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-url/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_url($atts = array())
{
    $atts = shortcode_atts(array(
        'allowed_protocols' => '',
        'part' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $atts['allowed_protocols'] = explode(',', sanitize_text_field($atts['allowed_protocols']));
    extract($atts);

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
            $value = strval(wp_parse_url($url, $part_constant_map[$part]));
        }
        return apply_filters(
            'wpcf7dtx_shortcode', // DTX built-in shortcode hook
            apply_filters(
                'wpcf7dtx_escape',
                apply_filters('wpcf7dtx_sanitize', $value, 'text'),
                $obfuscate,
                'text'
            ), // Sanitized & escaped value to output
            $value, // Raw value
            'URL', // Shortcode tag
            $atts // Shortcode attributes
        );
    }
    // No part requested, return the absolute URL
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $url, $obfuscate, 'url', $allowed_protocols), // Sanitized & escaped value to output
        $url, // Raw value
        'URL', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Referring URL
 *
 * @shortcode CF7_referrer
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-referrer-url/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_referrer($atts = array())
{
    $atts = shortcode_atts(array(
        'allowed_protocols' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $url = wpcf7dtx_array_has_key('HTTP_REFERER', $_SERVER);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', apply_filters(
            'wpcf7dtx_sanitize',
            $url,
            'url',
            $atts['allowed_protocols']
        ), $atts['obfuscate'], 'url'), // Sanitized & escaped value to output
        $url, // Raw value
        'referrer', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Variable from Bloginfo
 *
 * @shortcode CF7_bloginfo
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-page-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_bloginfo($atts = array())
{
    $atts = shortcode_atts(array(
        'show' => 'name', //Backwards compatibility
        'key' => 'name',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    extract($atts);
    $key = $show != $key && $show != 'name' ? $show : $key; // Use old value of "show" if not set to default value
    $raw = get_bloginfo($key);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $obfuscate), // Sanitized & escaped value to output
        $raw, // Raw value
        'bloginfo', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Variable from a Post Object
 *
 * @shortcode CF7_get_post_var
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-page-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_post_var($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => 'post_title',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $key = strtolower(apply_filters('wpcf7dtx_sanitize', $atts['key']));
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
    $atts['post_id'] = wpcf7dtx_validate_post_id($atts['post_id']);
    $raw = get_post_field($key, $atts['post_id']);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_post_var', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Value from Post Meta Field
 *
 * @shortcode CF7_get_custom_field
 *
 * @see  https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-post-meta-custom-fields/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_custom_field($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => '',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));

    // If this key can't be accessed
    if (!wpcf7dtx_post_meta_key_access_is_allowed($atts['key'])) {
        // Trigger a warning if a denied key is in use
        wpcf7dtx_access_denied_alert($atts['key'], 'post_meta');
        return '';
    }

    $key = apply_filters('wpcf7dtx_sanitize', $atts['key'], 'text');
    $atts['post_id'] = wpcf7dtx_validate_post_id($atts['post_id']);
    $raw = $atts['post_id'] && $key ? get_post_meta($atts['post_id'], $key, true) : '';
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_custom_field', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Variable from the Current Object
 *
 * @shortcode CF7_get_current_var
 *
 * @since 3.4.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-variables/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_current_var($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => 'title',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    extract($atts);
    $key = apply_filters('wpcf7dtx_sanitize', $key);
    $temp_key = str_replace('-', '_', sanitize_key($key));
    $raw = '';
    $value = '';
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
                        $raw = 'user_' . $obj->ID;
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    case 'image':
                    case 'featured_image': // Get the profile picture of the user being displayed on the page
                        $raw = get_avatar_url($obj->ID);
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate, 'url');
                        break;
                    case 'title': // Get author's display name
                        $raw = $obj->display_name;
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    case 'slug': // Not all author pages use the `user_login` variable for security reasons, so get what is currently displayed as slug
                        $raw = basename(wpcf7dtx_url(array('part' => 'path')));
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    default: // Get user value by key should it exist
                        $raw = $obj->get($key);
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
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
                        $raw = $obj->term_id;
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    case 'acf_id': // Get handle for Advanced Custom Fields
                        $raw = $obj->taxonomy . '_' . $obj->term_id;
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    case 'title': // Get term name
                        $raw = $obj->name;
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    default:
                        if (property_exists($obj, $key)) {
                            // Get any property if it exists
                            $raw = $obj->{$key};
                        } else {
                            // Otherwise, try meta data if the property doesn't exist
                            $raw = get_metadata('term', $obj->ID, $key, true);
                        }
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                }
            case 'archive': // Possibly a date or formats archive
                switch ($temp_key) {
                    case 'title': // Get archive title
                        $raw = get_the_archive_title();
                        $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                        break;
                    default:
                        break;
                }
            default: // Possibly a search or 404 page at this point
                if ($temp_key == 'slug') {
                    // no idea what else to get except the slug maybe
                    $raw = basename(wpcf7dtx_url(array('part' => 'path')));
                    $value = apply_filters('wpcf7dtx_escape', $raw, $obfuscate);
                }
                break;
        }
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        $value, // Sanitized & escaped value to output
        $raw, // Raw value
        'get_current_var', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Value from Current User
 *
 * Retreives data from the `users` and `usermeta` tables.
 *
 * @shortcode CF7_get_current_user
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-current-user-user-meta/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_current_user($atts = array())
{
    $atts = shortcode_atts(array(
        'key' => 'user_login',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $raw = '';
    if (is_user_logged_in()) {

        // If this key can't be accessed
        if (!wpcf7dtx_user_data_access_is_allowed($atts['key'])) {
            // Trigger a warning if a denied key is in use
            wpcf7dtx_access_denied_alert($atts['key'], 'user_data');
            return '';
        }

        $raw = wp_get_current_user()->get($atts['key']);
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_current_user', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Attachment
 *
 * Retreives an attachment ID or absolute URL depending on attributes.
 *
 * @shortcode CF7_get_attachment
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
    $atts = shortcode_atts(array(
        'id' => '', // Get attachment by ID
        'size' => 'full', // Define attachment size
        'post_id' => '', // If attachment ID is empty but post ID is not, get the featured image
        'return' => 'url', // Options are `id` or `url`
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER));

    // No attachment ID was provided, check for post ID to get it's featured image
    if (empty($atts['id'])) {
        if ($atts['post_id'] = wpcf7dtx_validate_post_id($atts['post_id'])) {
            //If a post ID was provided, get it's featured image
            $atts['id'] = get_post_thumbnail_id($atts['post_id']);
        }
    }

    //Get the value
    $value = '';
    $raw = '';
    if ($atts['id']) {
        $atts['id'] = intval(sanitize_text_field(strval($atts['id'])));
        switch ($atts['return']) {
            case 'id': //Return the attachment ID
                $raw = $atts['id'];
                $value = apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']);
                break;
            default: //Return attachment URL
                $raw = wp_get_attachment_image_url(intval($atts['id']), sanitize_text_field(strval($atts['size'])));
                $value = apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate'], 'url');
                break;
        }
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        $value, // Sanitized & escaped value to output
        $raw, // Raw value
        'get_attachment', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Cookie Value
 *
 * Retreives the value of a cookie
 *
 * @shortcode CF7_get_cookie
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
    $atts = shortcode_atts(array(
        'key' => '',
        'default' => '',
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $raw = wpcf7dtx_array_has_key(apply_filters('wpcf7dtx_sanitize', $atts['key']), $_COOKIE, $atts['default']);
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_cookie', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Taxonomy
 *
 * Retreives a list of taxonomy values
 *
 * @shortcode CF7_get_taxonomy
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
    $atts = shortcode_atts(array(
        'post_id' => '',
        'taxonomy' => 'category', // Default taxonomy is `category`
        'fields' => 'names', // Return an array of term names
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $atts['post_id'] = wpcf7dtx_validate_post_id($atts['post_id']);
    $fields = apply_filters('wpcf7dtx_sanitize', $atts['fields'], 'key');
    $raw = '';
    $value = '';
    if ($atts['post_id'] && in_array($fields, array('names', 'slugs', 'ids'))) {
        $terms = wp_get_object_terms(
            $atts['post_id'], // Get only the ones assigned to this post
            apply_filters('wpcf7dtx_sanitize', $atts['taxonomy'], 'slug'),
            array('fields' => $fields)
        );
        if (is_array($terms) && count($raw = array_values($terms)) && (is_string($raw[0]) || is_numeric($raw[0]))) {
            //return apply_filters('wpcf7dtx_escape', implode(', ', $values), $obfuscate, 'text');
            $value = implode(', ', $raw);
        }
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $value, $atts['obfuscate'], 'text'), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_taxonomy', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * Get Theme Customization Option
 *
 * Retreives theme modification value for the active theme.
 *
 * @shortcode CF7_get_theme_option
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
    $atts = shortcode_atts(array(
        'key' => '',
        'default' => '', // Optional default value
        'obfuscate' => '' // Optionally obfuscate returned value
    ), array_change_key_case((array)$atts, CASE_LOWER));
    $default = apply_filters('wpcf7dtx_sanitize', $atts['default']);
    $raw = $default;
    if ($key = apply_filters('wpcf7dtx_sanitize', $atts['key'], 'text')) {
        $raw = get_theme_mod($key, $default);
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        apply_filters('wpcf7dtx_escape', $raw, $atts['obfuscate']), // Sanitized & escaped value to output
        $raw, // Raw value
        'get_theme_option', // Shortcode tag
        $atts // Shortcode attributes
    );
}

/**
 * GUID Field
 *
 * Generate a random GUID (globally unique identifier)
 *
 * @shortcode CF7_guid
 *
 * @since 3.1.0
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-shortcode-guid/
 *
 * @return string a randomly generated 128-bit text string.
 */
function wpcf7dtx_guid()
{
    if (function_exists('com_create_guid')) {
        $raw = trim(com_create_guid(), '{}');
    } else {
        $raw = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    return apply_filters(
        'wpcf7dtx_shortcode', // DTX built-in shortcode hook
        esc_attr($raw), // Sanitized & escaped value to output
        $raw, // Raw value
        'guid', // Shortcode tag
        array() // Shortcode attributes
    );
}
