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
 * Get Allowed HTML for Form Field Properties
 *
 * @since 3.6.0
 *
 * @param string $type Optional. The type of input for unique properties. Default is `text`.
 * @param array $extra Optional. A sequential array of properties to additionally include.
 *
 * @return array An associative array of allowed properties appropriate for use in `wp_kses()`
 */
function wpcf7dtx_get_allowed_field_properties($type = 'text', $extra = array())
{
    if (in_array($type, array('option', 'optgroup'))) {
        return array(
            'optgroup' => array(
                'label' => array(),
                'disabled' => array(),
                'hidden' => array()
            ),
            'option' => array(
                'value' => array(),
                'selected' => array(),
                'disabled' => array(),
                'hidden' => array()
            )
        );
    }
    $allowed_properties = array(
        // Global properties
        'type' => array(),
        'id' => array(),
        'name' => array(),
        'value' => array(),
        'required' => array(),
        'class' => array(),
        'disabled' => array(),
        'readonly' => array(),
        'tabindex' => array(),
        'size' => array(),
        'title' => array(),
        'autofocus' => array(),
        // ARIA properties
        'aria-invalid' => array(),
        'aria-describedby' => array(),
        // DTX properties
        'data-dtx-value' => array(),
    );
    if (in_array($type, array('checkbox', 'radio', 'acceptance'))) {
        // Properties exclusive to checkboxes and radio buttons
        $allowed_properties['checked'] = array();
        $allowed_properties['dtx-default'] = array();
    } elseif (in_array($type, array('number', 'range'))) {
        // Properties exclusive to number inputs
        $allowed_properties['step'] = array();
    } elseif ($type == 'select') {
        // Properties exclusive to select fields
        $allowed_properties['multiple'] = array();
        $allowed_properties['dtx-default'] = array();
        unset($allowed_properties['type'], $allowed_properties['value'], $allowed_properties['placeholder'], $allowed_properties['size']); // Remove invalid select attributes
    }
    if (!in_array($type, array('checkbox', 'radio', 'select', 'acceptance'))) {
        // Allowed properties for all text-based inputs
        $allowed_properties['placeholder'] = array();
        $allowed_properties['autocomplete'] = array();
        $allowed_properties['minlength'] = array();
        $allowed_properties['maxlength'] = array();
        if (in_array($type, array('number', 'range', 'date', 'datetime-local', 'time'))) {
            // Additional properties for number and date inputs
            $allowed_properties['min'] = array();
            $allowed_properties['max'] = array();
        }
        if ($type == 'textarea') {
            // Additional properties exclusive to textarea fields
            $allowed_properties['cols'] = array();
            $allowed_properties['rows'] = array();
            unset($allowed_properties['type'], $allowed_properties['value']); // Remove invalid textarea attributes
        } elseif (in_array($type, array('text', 'date', 'url', 'tel', 'email', 'password'))) {
            // Additional properties exclusive to specific text fields
            $allowed_properties['pattern'] = array();
        }
    }
    if (is_array($extra) && count($extra)) {
        foreach ($extra as $property) {
            $allowed_properties[sanitize_title($property)] = array();
        }
    }
    return $allowed_properties;
}

/**
 * Returns a formatted string of HTML attributes
 *
 * @since 3.6.0
 *
 * @param array $atts Associative array of attribute name and value pairs
 *
 * @return string Formatted HTML attributes with keys and values both escaped
 */
function wpcf7dtx_format_atts($atts)
{
    if (is_array($atts) && count($atts)) {
        $sanitized_atts = array();
        static $boolean_attributes = array(
            'checked', 'disabled', 'multiple', 'readonly', 'required', 'selected'
        );
        foreach ($atts as $key => $value) {
            $key = sanitize_key(strval($key));
            if ($key) {
                if (in_array($key, $boolean_attributes) || is_bool($value)) {
                    if ($value) {
                        $sanitized_atts[$key] = $key;
                    }
                } elseif ($value && (is_string($value) || is_numeric($value))) {
                    $sanitized_atts[$key] = $value;
                }
            }
        }
        if (count($sanitized_atts)) {
            $output = array();
            foreach ($sanitized_atts as $key => $value) {
                $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
            }
            return implode(' ', $output);
        }
    }
    return '';
}

/**
 * Create Input Field HTML
 *
 * @since 3.6.0
 *
 * @param array $atts An associative array of input attributes.
 *
 * @return string HTML output of input field
 */
function wpcf7dtx_input_html($atts)
{
    // Default field attributes
    $atts = array_merge(array(
        'type' => 'text',
        'value' => '',
    ), array_change_key_case((array)$atts, CASE_LOWER));
    if ($atts['type'] == 'checkbox' || $atts['type'] == 'radio') {
        if ($atts['value']) {
            $atts['checked'] = 'checked'; // If truthy, always set to this value
            $atts['dtx-default'] = 'on';
        } else {
            $atts['dtx-default'] = 'off';
        }
    }
    return sprintf('<input %s />', wpcf7dtx_format_atts($atts));
}

/**
 * Create Checkbox Field HTML
 *
 * @since 3.6.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param string $label_text The text to display next to the checkbox or radio button.
 * @param bool $label_ui Optional. If true, will place input inside a `<label>` element. Default is true.
 * @param bool $reverse Optional. If true, will reverse the order to display the text label first then the button. Default is false.
 *
 * @return string HTML output of the checkbox or radio button or empty string on failure.
 */
function wpcf7dtx_checkbox_html($atts, $label_text = '', $label_ui = true, $reverse = false)
{
    $input = wpcf7dtx_input_html($atts);
    if ($label_text) {
        $label_el = $label_ui ? 'span' : 'label'; // If not wrapping with a label element, display it next to it
        $label_text = sprintf(
            '<%1$s%2$s class="wpcf7-list-item-label">%3$ss</%1$s>',
            $label_el,
            // If not wrapping with a label element and the element has an ID attribute, add a `for` attribute
            $label_ui ? '' : (wpcf7dtx_array_has_key('id', $atts) ? ' for="' . esc_attr($atts['id']) . '"' : ''),
            apply_filters('wpcf7dtx_escape', $label_text)
        );
        if ($reverse) {
            $html = $label_text . $input;
        } else {
            $html = $input . $label_text;
        }
    } else {
        $html = $input;
    }
    if ($label_ui) {
        $html = '<label>' . $html . '</label>';
    }
    return $html;
}

/**
 * Create Textarea Field HTML
 *
 * @since 3.6.0
 *
 * @param array $atts An associative array of textarea field attributes.
 *
 * @return string HTML output of textarea field
 */
function wpcf7dtx_textarea_html($atts)
{
    // Attributes specific to HTML creation
    $atts = array_merge(array('value' => ''), array_change_key_case((array)$atts, CASE_LOWER));
    return sprintf(
        '<textarea %s>%s</textarea>',
        wpcf7dtx_format_atts($atts),
        apply_filters('wpcf7dtx_escape', $atts['value'], false, 'textarea')
    );
}

/**
 * Create Select Field HTML
 *
 * @since 3.6.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param array|string $options Accepts an associative array of key/value pairs to use as the
 * select option's value/label pairs. It also accepts an associative array of associative
 * arrays with the keys being used as option group labels and the array values used as that
 * group's options. It also accepts a string value of HTML already formatted as options or
 * option groups. It also accepts a string value of a self-closing shortcode that is
 * evaluated and its output is either options or option groups.
 * @param bool $hide_blank Optional. If true, the first blank placeholder option will have the `hidden` attribute added to it. Default is false.
 * @param bool $disable_blank Optional. If true, the first blank placeholder option will have the `disabled` attribute added to it. Default is false.
 *
 * @return string HTML output of select field
 */
function wpcf7dtx_select_html($atts, $options, $hide_blank = false, $disable_blank = false)
{
    // Attributes specific to HTML creation
    $atts = array_merge(array('placeholder' => '', 'dtx-default' => ''), array_change_key_case((array)$atts, CASE_LOWER));
    $options_html = ''; // Open options HTML

    // If using a placeholder, use it as the text of the first option
    if ($atts['placeholder']) {
        $options_html .= sprintf(
            '<option value=""%s%s%s>%s</option>',
            empty($atts['dtx-default']) ? ' selected' : '',
            $hide_blank ? ' hidden' : '',
            $disable_blank ? ' disabled' : '',
            apply_filters('wpcf7dtx_escape', $atts['placeholder'])
        );
    }
    if (is_array($options) && count($options)) {
        //Check if using option groups
        if (is_array(array_values($options)[0])) {
            foreach ($options as $group_name => $opt_group) {
                $options_html .= sprintf('<optgroup label="%s">', esc_attr(apply_filters('wpcf7dtx_escape', wpcf7dtx_get_dynamic($group_name)))); // Open option group
                foreach ($opt_group as $option_value => $option_label) {
                    // Check if option values and groups are dynamic
                    $dynamic_option_value = wpcf7dtx_get_dynamic($option_value);
                    $options_html .= sprintf(
                        '<option value="%1$s"%3$s>%2$s</option>',
                        esc_attr(apply_filters('wpcf7dtx_escape', $dynamic_option_value)),
                        esc_html(apply_filters('wpcf7dtx_escape', wpcf7dtx_get_dynamic($option_label))),
                        $atts['dtx-default'] == $dynamic_option_value ? ' selected' : ''
                    );
                }
                $options_html .= '</optgroup>'; // Close option group
            }
        } else {
            foreach ($options as $option_value => $option_label) {
                $dynamic_option_value = wpcf7dtx_get_dynamic($option_value);
                $options_html .= sprintf(
                    '<option value="%1$s"%3$s>%2$s</option>',
                    esc_attr(apply_filters('wpcf7dtx_escape', $dynamic_option_value)),
                    esc_html(apply_filters('wpcf7dtx_escape', wpcf7dtx_get_dynamic($option_label))),
                    $atts['dtx-default'] == $dynamic_option_value ? ' selected' : ''
                );
            }
        }
    } elseif (is_string($options) && !empty($options = trim($options))) {
        $allowed_html = wpcf7dtx_get_allowed_field_properties('option');
        // If options were passed as a string, go ahead and use them
        if (strpos($options, '<option') === 0 || stripos($options, '<optgroup') === 0) {
            $options_html .= wp_kses($options, $allowed_html);
        } else {
            // If a shortcode was passed as the options, evaluate it and use the result
            $shortcode_output = wpcf7dtx_get_dynamic($options);
            if (is_string($shortcode_output) && !empty($shortcode_output) && (strpos($shortcode_output, '<option') === 0) || strpos($shortcode_output, '<optgroup') === 0) {
                $options_html .= wp_kses($shortcode_output, $allowed_html);
            }
        }
    }
    return sprintf('<select %s>%s</select>', wpcf7dtx_format_atts($atts), $options_html);
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
