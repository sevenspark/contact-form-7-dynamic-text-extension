<?php

/**
 * Custom DTX Allowed Protocols Filter
 *
 * @since 3.3.0
 *
 * @param array|string $protocols Optional. Specify protocols to allow either as an array of string values or a string value of comma separated protocols.
 * @param bool $replace Optional. If true, this function will only return the specified values. If false, will merge specified values with default values. Default is false.
 *
 * @return array An array of string values, default only includes `http` and `https` protocols.
 */
function wpcf7dtx_allow_protocols($protocols = false, $replace = false)
{
    // Get user-inputted protocols
    $user_protocols = false;
    if (is_string($protocols) && !empty($protocols)) {
        $user_protocols = explode(',', sanitize_text_field($protocols));
    } elseif (is_array($protocols) && count($protocols)) {
        $user_protocols = array_filter(array_values($protocols));
    }
    $default = array('http', 'https');
    if (is_array($user_protocols) && count($user_protocols)) {
        // Sanitize each value before adding
        $allowed_protocols = array();
        foreach ($user_protocols as $protocol) {
            $allowed_protocols[] = sanitize_text_field($protocol);
        }
        if ($replace) {
            return array_unique($allowed_protocols);
        }
        return array_unique(array_merge(array('http', 'https'), $allowed_protocols)); // Return merged values
    } elseif ($replace) {
        return array(); // None allowed, apparently
    }
    return $default; // Return only default values
}
add_filter('wpcf7dtx_allow_protocols', 'wpcf7dtx_allow_protocols', 10, 2);

/**
 * Custom DTX Sanitize Filter
 *
 * @since 3.3.0
 *
 * @param string $value value to be sanitized
 * @param string $type Optional. The type of sanitation to return. Default is `auto` where automatic identification will be used to attempt to identify URLs and email addresses vs text.
 * @param array|string $protocols Optional. Specify protocols to allow either as an array of string values or a string value of comma separated protocols.
 *
 * @return string the sanitized value
 */
function wpcf7dtx_sanitize($value = '', $type = 'auto', $protocols = false)
{
    $value = is_string($value) ? $value : strval($value); // Force string value
    if (!empty($value)) {
        $type = $type == 'auto' ? wpcf7dtx_detect_value_type($value) : sanitize_text_field($type);
        switch ($type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return sanitize_url($value, apply_filters('wpcf7dtx_allow_protocols', $protocols));
            case 'key':
                return sanitize_key($value);
            case 'slug':
                return sanitize_title($value);
            case 'textarea':
                return sanitize_textarea_field($value);
        }
    }
    return sanitize_text_field($value);
}
add_filter('wpcf7dtx_sanitize', 'wpcf7dtx_sanitize', 10, 3);

/**
 * Custom DTX Escape Filter
 *
 * @since 3.3.0
 *
 * @param string $value value to be escaped
 * @param bool $obfuscate Optional. If true, returned value will be obfuscated. Default is false.
 * @param string $type Optional. The type of escape to return. Default is `auto` where automatic identification will be used to attempt to identify the type of text.
 * @param array|string $protocols Optional. Specify protocols to allow either as an array of string values or a string value of comma separated protocols.
 *
 * @return string the escaped value
 */
function wpcf7dtx_escape($value = '', $obfuscate = false, $type = 'auto', $protocols = false)
{
    $value = apply_filters('wpcf7dtx_sanitize', $value, $type); // Sanitize value
    if (!empty($value)) {
        if ($obfuscate) {
            return apply_filters('wpcf7dtx_obfuscate', $value); // Return obfuscated value
        }
        $type = $type == 'auto' ? wpcf7dtx_detect_value_type($value) : sanitize_text_field($type);
        switch ($type) {
            case 'url':
                return esc_url($value, apply_filters('wpcf7dtx_allow_protocols', $protocols));
            case 'textarea':
                return esc_textarea($value);
        }
    }
    return esc_attr($value); // Return attribute value
}
add_filter('wpcf7dtx_escape', 'wpcf7dtx_escape', 10, 4);

/**
 * Detect Value Type
 *
 * @since 3.3.0
 *
 * @access private
 *
 * @param string $value the value to be identified
 *
 * @return string Potentially identifies string values as `url`, `email`, or `text`.
 */
function wpcf7dtx_detect_value_type($value)
{
    // Try to detect the value type
    $value = trim($value);
    $is_https_url = stripos($value, 'https') === 0 && strlen($value) > 5;
    $is_http_url = stripos($value, 'http') === 0 && strlen($value) > 4 && sanitize_key($value) != 'https';
    if ($is_https_url || $is_http_url) {
        return 'url';
    } elseif (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $value)) {
        return 'email';
    }
    return 'text';
}

/**
 * Obfuscate a value
 *
 * @see https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-obfuscate/
 *
 * @param mixed $value the value to be obfuscated
 *
 * @return string obfuscated value
 */
function wpcf7dtx_obfuscate($value = '')
{
    $o = '';
    if (!is_string($value)) {
        $value = sanitize_text_field(strval($value)); // Force value to be string and sanitize it
    }
    if (!empty($value)) {
        $value = htmlentities($value, ENT_QUOTES);
        foreach (str_split($value) as $letter) {
            $o .= '&#' . ord($letter) . ';';
        }
        return $o; // Return obfuscated value
    }
    return esc_attr($value); // Return default attribute escape
}
add_filter('wpcf7dtx_obfuscate', 'wpcf7dtx_obfuscate', 10, 1);

/**
 * Get Post ID
 *
 * @access private
 *
 * @param mixed $post_id
 *
 * @return int An integer value of the passed post ID or the post ID of the current `$post` global object. 0 on Failure.
 */
function wpcf7dtx_get_post_id($post_id, $context = 'dtx')
{
    $post_id = $post_id ? intval(sanitize_text_field(strval($post_id))) : '';
    if (!$post_id || !is_numeric($post_id)) {
        if ($context == 'dtx') {
            if (wp_doing_ajax()) {
                // If we're doing an AJAX call, just fail quietly
                return 0;
            } else {
                global $post;
                if (isset($post)) {
                    $post_id = $post->ID; // If the global $post object is set, get its ID
                } else {
                    $post_id = get_the_ID(); // Otherwise get it from "the loop"
                }
            }
        } elseif ($context == 'acf') {
            // When a post ID is not specified for ACF keys, it accepts the boolean `false`
            $post_id = false;
        }
    }
    return $post_id;
}

/**
 * Get Dynamic Value
 *
 * @since 3.2.2
 *
 * @param string $value The form tag value.
 * @param WPCF7_FormTag|false $tag Optional. Use to look up default value.
 * @param string $sanitize Optional. Specify type of sanitization. Default is `auto`.
 *
 * @return string The dynamic output or the original value, not escaped or sanitized.
 */
function wpcf7dtx_get_dynamic($value, $tag = false, $sanitize = 'auto')
{
    if ($tag !== false) {
        $default = $tag->get_option('defaultvalue', '', true);
        if (!$default) {
            $default = $tag->get_default_option(strval(reset($tag->values)));
        }
        $value = wpcf7_get_hangover($tag->name, $default);
    }
    $value = apply_filters('wpcf7dtx_sanitize', $value, $sanitize);
    if (is_string($value) && !empty($value)) {
        // If a shortcode was passed as the value, evaluate it and use the result
        $shortcode_tag = '[' . $value . ']';
        $shortcode_output = do_shortcode($shortcode_tag); //Shortcode value
        if (is_string($shortcode_output) && $shortcode_output != $shortcode_tag) {
            return apply_filters('wpcf7dtx_sanitize', $shortcode_output, $sanitize);
        }
    }
    return $value;
}

/**
 * Array Key Exists and Has Value
 *
 * @since 3.1.0
 *
 * @param string|int $key The key to search for in the array.
 * @param array $array The array to search.
 * @param mixed $default The default value to return if not found or is empty. Default is an empty string.
 *
 * @return mixed The value of the key found in the array if it exists or the value of `$default` if not found or is empty.
 */
function wpcf7dtx_array_has_key($key, $array = array(), $default = '')
{
    //Check if this key exists in the array
    $valid_key = (is_string($key) && !empty($key)) || is_numeric($key);
    $valid_array = is_array($array) && count($array);
    if ($valid_key && $valid_array && array_key_exists($key, $array)) {
        //Always return if it's a boolean or number, otherwise only return it if it has any value
        if ($array[$key] || is_bool($array[$key]) || is_numeric($array[$key])) {
            return $array[$key];
        }
    }
    return $default;
}

if (!function_exists('array_key_first')) {
    /**
     * Gets the first key of an array
     *
     * Gets the first key of the given array without affecting the internal array pointer.
     *
     * @param array $array
     * @return int|string|null
     */
    function array_key_first($array = array())
    {
        foreach ($array as $key => $value) {
            return $key;
        }
        return null;
    }
}
