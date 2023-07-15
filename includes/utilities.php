<?php

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

/**
 * Get Post ID
 *
 * @param mixed $post_id
 * @return int An integer value of the passed post ID or the post ID of the current `$post` global object. 0 on Failure.
 */
function wpcf7dtx_get_post_id($post_id)
{
    $post_id = is_numeric($post_id) && (int)$post_id > 0 ? intval($post_id) : 0;
    if (!$post_id) {
        //No post ID was provided, look it up
        global $post;
        if (isset($post) && property_exists($post, 'ID')) {
            $post_id = $post->ID;
        }
    }
    return $post_id;
}

/**
 * Parse Content for Specified Shortcodes
 *
 * Parse a string of content for a specific shortcode to retrieve its attributes and content
 *
 * @since 3.1.0
 *
 * @param string $content The content to parse
 * @param string $tag The shortcode tag
 *
 * @return array An associative array with `tag` (string) and `shortcodes` (sequential array). If shortcodes were discovered, each one has keys for `atts` (associative array) and `content` (string)
 */
function wpcf7dtx_get_shortcode_atts($content)
{
    $return = array(
        'tag' => '',
        'atts' => array()
    );
    //Search for shortcodes with attributes
    if (false !== ($start = strpos($content, ' '))) {
        $return['tag'] = substr($content, 0, $start); //Opens the start tag, assumes there are attributes because of the space

        //Parse for shortcode attributes: `shortcode att1='foo' att2='bar'`

        //Chop only the attributes e.g. `att1="foo" att2="bar"`
        $atts_str =  trim(str_replace($return['tag'], '', $content));
        if (strpos($atts_str, "'") !== false) {
            $atts = explode("' ", substr(
                $atts_str,
                0,
                -1 //Clip off the last character, which is a single quote
            ));
            if (is_array($atts) && count($atts)) {
                foreach ($atts as $att_str) {
                    $pair = explode("='", $att_str);
                    if (is_array($pair) && count($pair) > 1) {
                        $key = sanitize_key(trim($pair[0])); //Validate & normalize the key
                        if (!empty($key)) {
                            $return['atts'][$key] = sanitize_text_field(html_entity_decode($pair[1]));
                        }
                    }
                }
            }
        }
    }

    return $return;
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
