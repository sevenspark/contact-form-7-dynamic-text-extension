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
    add_shortcode('CF7_referrer', 'wpcf7dtx_referrer');
    add_shortcode('CF7_bloginfo', 'wpcf7dtx_bloginfo');
    add_shortcode('CF7_get_post_var', 'wpcf7dtx_get_post_var');
    add_shortcode('CF7_get_custom_field', 'wpcf7dtx_get_custom_field');
    add_shortcode('CF7_get_current_user', 'wpcf7dtx_get_current_user');
    add_shortcode('CF7_get_attachment', 'wpcf7dtx_get_attachment');
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
 * Get the Current URL
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

    // Build the full URL
    if (!empty($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) !== 80) {
        $url = network_home_url() . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    } else {
        $url = network_home_url($_SERVER['REQUEST_URI']);
    }
    $url = apply_filters('wpcf7dtx_sanitize', $url, 'url', $allowed_protocols);

    if ($url && !empty($part = sanitize_key(strtolower($part)))) {
        // If an individual part is requested, get that specific value using parse_url()
        $part_constant_map = [
            'scheme' => PHP_URL_SCHEME, // e.g. `http`
            'host'  => PHP_URL_HOST,
            'path'  => PHP_URL_PATH,
            'query' => PHP_URL_QUERY // after the question mark ?
        ];
        if (array_key_exists($part, $part_constant_map)) {
            $value = apply_filters('wpcf7dtx_sanitize', strval(wp_parse_url($url, $part_constant_map[$part])), 'text');
        }
        return apply_filters('wpcf7dtx_escape', $value, $obfuscate, 'text');
    }
    // No part requested, return the whole thing
    return apply_filters('wpcf7dtx_escape', $url, $obfuscate, 'url', $allowed_protocols);
}

/**
 * Get Referrer
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_referrer($atts = array(), $content = '', $tag = '')
{
    extract(shortcode_atts(array(
        'allowed_protocols' => 'http,https',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $allowed_protocols = explode(',', sanitize_text_field($allowed_protocols));
    $value = empty($_SERVER['HTTP_REFERER']) ? '' : sanitize_url($_SERVER['HTTP_REFERER'], $allowed_protocols);
    if ($obfuscate && !empty($value)) {
        return wpcf7dtx_obfuscate($value);
    }
    return $value;
}

/**
 * Get Variable from Bloginfo
 *
 * See possible values: https://developer.wordpress.org/reference/functions/get_bloginfo/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_bloginfo($atts = array(), $content = '', $tag = '')
{
    extract(shortcode_atts(array(
        'show' => 'name', //Backwards compatibility
        'key' => 'name',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $key = $show != $key && $show != 'name' ? $show : $key; //Use old value of "show" if not set to default value
    $value = sanitize_text_field(strval(get_bloginfo($key)));
    if ($obfuscate && !empty($value)) {
        return wpcf7dtx_obfuscate($value);
    }
    return $value;
}

/**
 * Get Variable from a Post Object
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_post_var($atts = array(), $content = '', $tag = '')
{
    extract(shortcode_atts(array(
        'key' => 'post_title',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    switch ($key) {
        case 'slug':
            $key = 'post_name';
            break;
        case 'title':
            $key = 'post_title';
            break;
        default:
            break;
    }
    $post_id = wpcf7dtx_get_post_id($post_id);
    if ($post_id && is_string($key) && !empty($key)) {
        $value = sanitize_text_field(trim(strval(get_post_field($key, $post_id))));
        if ($obfuscate && !empty($value)) {
            return wpcf7dtx_obfuscate($value);
        }
        return $value;
    }
    return '';
}

/**
 * Get Value from Post Meta Field
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_custom_field($atts = array(), $content = '', $tag = '')
{
    extract(shortcode_atts(array(
        'key' => '',
        'post_id' => '',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    $post_id = wpcf7dtx_get_post_id($post_id);
    if ($post_id && is_string($key) && !empty($key)) {
        $value = get_post_meta($post_id, $key, true);
        if ($obfuscate && !empty($value)) {
            return wpcf7dtx_obfuscate($value);
        }
        return $value;
    }
    return '';
}

/**
 * Get Value from Current User
 *
 * Retreives data from the `users` and `usermeta` tables.
 * Documentation: https://developer.wordpress.org/reference/classes/wp_user/get/
 *
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_current_user($atts = array(), $content = '', $tag = '')
{
    extract(shortcode_atts(array(
        'key' => 'user_login',
        'obfuscate' => ''
    ), array_change_key_case((array)$atts, CASE_LOWER)));
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $value = $user->get($key);
        if ($obfuscate && !empty($value)) {
            return wpcf7dtx_obfuscate($value);
        }
        return $value;
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
 * @param array $atts Optional. An associative array of shortcode attributes. Default is an empty array.
 * @param string $content Optional. A string of content between the opening and closing tags. Default is an empty string.
 * @param string $tag Optional. The shortcode tag. Default is an empty string.
 *
 * @return string Output of the shortcode
 */
function wpcf7dtx_get_attachment($atts = array(), $content = '', $tag = '')
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
    $value = '';
    if ($id) {
        $id = intval(sanitize_text_field(strval($id)));
        switch ($return) {
            case 'id': //Return the attachment ID
                $value = esc_attr($id);
                break;
            default: //Return attachment URL
                $url = wp_get_attachment_image_url(intval($id), sanitize_text_field(strval($size)));
                $value = $url ? esc_url($url) : '';
                break;
        }
        if ($obfuscate && !empty($value)) {
            return wpcf7dtx_obfuscate($value);
        }
    }
    return $value;
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

