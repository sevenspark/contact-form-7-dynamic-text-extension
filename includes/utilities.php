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
    if ($type == 'none') {
        return $value;
    }
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
    if ($type == 'none') {
        return $value;
    }
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
 * @see https://www.w3schools.com/tags/tag_input.asp
 * @see https://www.w3schools.com/tags/tag_optgroup.asp
 * @see https://www.w3schools.com/tags/tag_option.asp
 * @see https://www.w3schools.com/tags/tag_select.asp
 * @see https://www.w3schools.com/tags/tag_textarea.asp
 *
 * @since 4.0.0
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
        'class' => array(),
        'disabled' => array(),
        'tabindex' => array(),
        'title' => array(),
        // ARIA properties
        'aria-invalid' => array(),
        'aria-describedby' => array(),
        // DTX properties
        'data-dtx-value' => array(),
    );
    if ($type != 'hidden') {
        $allowed_properties['autofocus'] = array();
        $allowed_properties['readonly'] = array();
        $allowed_properties['required'] = array();
    }
    if (in_array($type, array('checkbox', 'radio', 'acceptance'))) {
        // Properties exclusive to checkboxes and radio buttons
        $allowed_properties['checked'] = array();
        $allowed_properties['dtx-default'] = array();
    } elseif ($type == 'select') {
        // Properties exclusive to select fields
        $allowed_properties['size'] = array();
        $allowed_properties['multiple'] = array();
        $allowed_properties['dtx-default'] = array();
        unset($allowed_properties['type'], $allowed_properties['value']); // Remove invalid select attributes
    } else {
        // Properties exclusive to text-based inputs
        $allowed_properties['autocomplete'] = array();
        $allowed_properties['list'] = array();

        // Placeholder
        if (in_array($type, array('text', 'search', 'url', 'tel', 'email', 'password', 'number'))) {
            $allowed_properties['placeholder'] = array();
        }

        // Textarea
        if ($type == 'textarea') {
            // Additional properties exclusive to textarea fields
            $allowed_properties['cols'] = array();
            $allowed_properties['rows'] = array();
            $allowed_properties['minlength'] = array();
            $allowed_properties['maxlength'] = array();
            $allowed_properties['wrap'] = array();
            unset($allowed_properties['type'], $allowed_properties['value']); // Remove invalid textarea attributes
        } elseif (in_array($type, array('text', 'search', 'url', 'tel', 'email', 'password'))) {
            // Additional properties exclusive to these text-based fields
            $allowed_properties['size'] = array();
            $allowed_properties['minlength'] = array();
            $allowed_properties['maxlength'] = array();
            $allowed_properties['pattern'] = array();
        } elseif (in_array($type, array('number', 'range', 'date', 'datetime-local', 'time'))) {
            // Number and date inputs
            $allowed_properties['min'] = array();
            $allowed_properties['max'] = array();
            $allowed_properties['step'] = array();
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
 * @since 4.0.0
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
 * @since 4.0.0
 *
 * @param array $atts An associative array of input attributes.
 *
 * @return string HTML output of input field
 */
function wpcf7dtx_input_html($atts)
{
    return sprintf('<input %s />', wpcf7dtx_format_atts($atts));
}

/**
 * Create Checkbox Field HTML
 *
 * @since 4.0.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param string $label_text Optional. The text to display next to the checkbox or radio button.
 * @param bool $label_ui Optional. If true, will place input and label text inside a `<label>` element. Default is true.
 * @param bool $reverse Optional. If true, will reverse the order to display the text label first then the button. Has no effect if label text is empty. Default is false.
 *
 * @return string HTML output of the checkbox or radio button or empty string on failure.
 */
function wpcf7dtx_checkbox_html($atts, $label_text = '', $label_ui = true, $reverse = false)
{
    // Default field attributes
    $atts = array_merge(array('value' => '', 'dtx-default' => ''), array_change_key_case((array)$atts, CASE_LOWER));
    if ($atts['value'] && $atts['dtx-default'] && $atts['value'] == $atts['dtx-default']) {
        $atts['checked'] = 'checked';
    }
    $input = wpcf7dtx_input_html($atts);
    if (!empty(trim($label_text))) {
        $label_el = $label_ui ? 'span' : 'label'; // If not wrapping with a label element, display it next to it
        $label_text = sprintf(
            '<%1$s%2$s class="wpcf7-list-item-label">%3$s</%1$s>',
            $label_el,
            // If not wrapping with a label element and the element has an ID attribute, add a `for` attribute
            $label_ui ? '' : (wpcf7dtx_array_has_key('id', $atts) ? ' for="' . esc_attr($atts['id']) . '"' : ''),
            esc_html($label_text)
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
 * Create Checkbox Group HTML
 *
 * @since 4.0.3
 *
 * @param array $atts An associative array of select input attributes.
 * @param array|string $options Accepts an associative array of key/value pairs to use as the
 * select option's value/label pairs. It also accepts an associative array of associative
 * arrays with the keys being used as option group labels and the array values used as that
 * group's options. It also accepts a string value of HTML already formatted as options or
 * option groups. It also accepts a string value of a self-closing shortcode that is
 * evaluated and its output is either options or option groups.
 * @param bool $label_ui Optional. If true, will place input and label text inside a `<label>` element. Default is true.
 * @param bool $reverse Optional. If true, will reverse the order to display the text label first then the button. Has no effect if label text is empty. Default is false.
 *
 * @return string HTML output of the checkbox or radio button or empty string on failure.
 */
function wpcf7dtx_checkbox_group_html($atts, $options, $label_ui = false, $reverse = false, $exclusive = false)
{
    $group_html = '';
    if ($count = count($options)) {
        // Attributes specific to HTML creation
        $atts = array_merge(array(
            'type' => 'checkbox',
            'id' => '',
            'name' => '',
            'value' => '',
            'dtx-default' => ''
        ), array_change_key_case((array)$atts, CASE_LOWER));

        // Loop all the options
        $group_html = array();
        $id_prefix = ($atts['id'] ? $atts['id'] : uniqid($atts['name'] . '_')) . '_'; // Create prefix from passed ID or Name
        $i = 1;
        foreach ($options as $value => $label) {
            $my_atts = array_merge($atts, array(
                'id' => sanitize_html_class($id_prefix . $i) // Always have unique IDs for group items
            ));
            $dynamic_value = '';
            $dynamic_label = $label;
            if ($value && $label && $value === $label) {
                // These are identical, just handle it as one, could also be a raw shortcode
                $dynamic_option = trim(wpcf7dtx_get_dynamic($value, false, 'none')); // Do not sanitize yet, it may have HTML
                if (is_string($dynamic_option) && !empty($dynamic_option) && strpos($dynamic_option, '{') === 0 && strpos($dynamic_option, '}') === strlen($dynamic_option) - 1) {
                    // If it outputs JSON, try parsing it
                    try {
                        $dynamic_option = json_decode($dynamic_option, true);
                        if (is_array($dynamic_option) && count($dynamic_option)) {
                            $group_html[] = wpcf7dtx_checkbox_group_html(
                                $my_atts,
                                $dynamic_option,
                                $label_ui,
                                $reverse,
                                $exclusive
                            );
                        }
                    } catch (Exception $e) {
                        // Fail quietly
                        if (WP_DEBUG && WP_DEBUG_LOG) {
                            error_log('[Contact Form 7 - Dynamic Text Extension] Error parsing JSON value');
                            error_log($e->getMessage());
                        }
                    }
                    $i++;
                    continue; // Continue with next iteration
                } elseif (is_string($dynamic_option) && !empty($dynamic_option) && esc_html($dynamic_option) != $dynamic_option) {
                    $group_html[] = force_balance_tags($dynamic_option); // If it outputs HTML, escape and use them as-is
                    $i++;
                    continue; // Continue with next iteration
                } else {
                    $dynamic_value = $dynamic_option;
                    $dynamic_label = $dynamic_option;
                }
            } else {
                // These are different, could be raw shortcodes
                $dynamic_value = wpcf7dtx_get_dynamic($value, false);
                $dynamic_label = wpcf7dtx_get_dynamic($label, false);
            }
            // This could be a single??
            $class = array('wpcf7-list-item');
            $class[] = sanitize_html_class('wpcf7-list-item-' . $i);
            if ($i === 1) {
                $class[] = 'first';
            }
            if ($i === $count) {
                $class[] = 'last';
            }
            if ($exclusive) {
                $class[] = 'wpcf7-exclusive-checkbox';
            }
            if ($dynamic_value && $atts['dtx-default'] && $dynamic_value == $atts['dtx-default']) {
                $my_atts['checked'] = 'checked';
            }
            $group_html[] = sprintf(
                '<span class="%s">%s</span>',
                esc_attr(implode(' ', $class)),
                wpcf7dtx_checkbox_html(
                    // Overwrite name attribute
                    array_merge($my_atts, array(
                        'name' => $atts['type'] == 'radio' || $exclusive || $count === 1 ? $atts['name'] : $atts['name'] . '[]', // if there are multiple checkboxes and they aren't exclusive, names are an array
                        'value' => $dynamic_value
                    )),
                    $dynamic_label,
                    $label_ui,
                    $reverse
                )
            );
            $i++;
        }
        $group_html = implode('', $group_html);
    }
    return $group_html;
}

/**
 * Create Textarea Field HTML
 *
 * @since 4.0.0
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
 * @since 4.0.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param array|string $options Accepts an associative array of key/value pairs to use as the
 * select option's value/label pairs. It also accepts an associative array of associative
 * arrays with the keys being used as option group labels and the array values used as that
 * group's options. It also accepts a string value of HTML already formatted as options or
 * option groups. It also accepts a string value of a self-closing shortcode that is
 * evaluated and its output is either options or option groups.
 * @param bool $hide_blank Optional. If true, the first blank placeholder option will have the
 * `hidden` attribute added to it. Default is false.
 * @param bool $disable_blank Optional. If true, the first blank placeholder option will have
 * the `disabled` attribute added to it. Default is false.
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
            $allowed_html = wpcf7dtx_get_allowed_field_properties('option');
            foreach ($options as $option_value => $option_label) {
                if ($option_value === $option_label) {
                    // These are identical, just handle it as one, could also be a raw shortcode
                    $dynamic_option = trim(wpcf7dtx_get_dynamic($option_value, false, 'none')); // Do not sanitize yet, it may have HTML
                    if (is_string($dynamic_option) && !empty($dynamic_option) && (strpos($dynamic_option, '<option') === 0 || stripos($dynamic_option, '<optgroup') === 0)) {
                        $options_html .= wp_kses($dynamic_option, $allowed_html); // If it outputs HTML, escape and use them as-is
                    } elseif ($dynamic_option) {
                        // Just output the option
                        $dynamic_option = apply_filters('wpcf7dtx_escape', $dynamic_option);
                        $options_html .= sprintf(
                            '<option value="%1$s"%3$s>%2$s</option>',
                            esc_attr($dynamic_option),
                            esc_html($dynamic_option),
                            $atts['dtx-default'] == $dynamic_option ? ' selected' : ''
                        );
                    }
                } else {
                    $dynamic_option_value = wpcf7dtx_get_dynamic($option_value, false);
                    $options_html .= sprintf(
                        '<option value="%1$s"%3$s>%2$s</option>',
                        esc_attr(apply_filters('wpcf7dtx_escape', $dynamic_option_value)),
                        esc_html(apply_filters('wpcf7dtx_escape', wpcf7dtx_get_dynamic($option_label))),
                        $atts['dtx-default'] == $dynamic_option_value ? ' selected' : ''
                    );
                }
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
 * @param mixed $default The default value to return if not found or is empty. Default is
 * an empty string.
 *
 * @return mixed The value of the key found in the array if it exists or the value of
 * `$default` if not found or is empty.
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


/**
 * Check if admin has allowed access to a specific post meta key
 *
 * @since 4.2.0
 *
 * @param string $meta_key The post meta key to test
 *
 * @return bool True if this key can be accessed, false otherwise
 */
function wpcf7dtx_post_meta_key_access_is_allowed($meta_key)
{

    // Get the DTX Settings
    $settings = wpcf7dtx_get_settings();

    // Has access to all metadata been enabled?
    if (isset($settings['post_meta_allow_all']) && $settings['post_meta_allow_all'] === 'enabled') {
        return true;
    }

    // If not, check the Allow List
    $allowed_keys = array();

    // No key list from settings
    if (isset($settings['post_meta_allow_keys']) && is_string($settings['post_meta_allow_keys'])) {
        // Extract allowed keys from setting text area
        $allowed_keys = wpcf7dtx_parse_allowed_keys($settings['post_meta_allow_keys']);
    }

    // Allow custom filters
    $allowed_keys = apply_filters('wpcf7dtx_post_meta_key_allow_list', $allowed_keys);

    // Check if the key is in the allow list
    if (in_array($meta_key, $allowed_keys)) {
        return true; // The key is allowed
    }

    // Everything is disallowed by default
    return false;
}

/**
 * Check if admin has allowed access to a specific user data
 *
 * @since 4.2.0
 *
 * @param string $key The user data key to test
 *
 * @return bool True if this key can be accessed, false otherwise
 */
function wpcf7dtx_user_data_access_is_allowed($key)
{

    // Get the DTX Settings
    $settings = wpcf7dtx_get_settings(); //get_option('cf7dtx_settings', []);

    // Has access to all metadata been enabled?
    if (isset($settings['user_data_allow_all']) && $settings['user_data_allow_all'] === 'enabled') {
        return true;
    }

    // If not, check the Allow List
    $allowed_keys = array();

    // No key list from settings
    if (isset($settings['user_data_allow_keys']) && is_string($settings['user_data_allow_keys'])) {
        // Extract allowed keys from setting text area
        $allowed_keys = wpcf7dtx_parse_allowed_keys($settings['user_data_allow_keys']);
    }

    // Allow custom filters
    $allowed_keys = apply_filters('wpcf7dtx_user_data_key_allow_list', $allowed_keys);

    // Check if the key is in the allow list
    if (in_array($key, $allowed_keys)) {
        return true; // The key is allowed
    }

    // Everything is disallowed by default
    return false;
}

/**
 * Take the string saved in the options array from the allow list textarea and parse it into an array by newlines.
 * Also strip whitespace
 *
 * @since 4.2.0
 *
 * @param string $allowlist The string of allowed keys stored in the DB
 *
 * @return array Array of allowed keys
 */
function wpcf7dtx_parse_allowed_keys($allowlist)
{
    // Split by newlines
    $keys = wpcf7dtx_split_newlines($allowlist);
    // Trim whitespace
    $keys = array_map('trim', $keys);
    return $keys;
}

/**
 * Used to parse strings stored in the database that are from text areas with one element per line into an array of strings
 *
 * @since 4.2.0
 *
 * @param string $str The multi-line string to be parsed into an array
 *
 * @return array Array of parsed strings
 */
function wpcf7dtx_split_newlines($str)
{
    return preg_split('/\r\n|\r|\n/', $str);
}

/**
 * Gets the CF7 DTX settings field from the WP options table.  Returns an empty array if option has not previously been set
 *
 * @since 4.2.0
 *
 * @return array The settings array
 */
function wpcf7dtx_get_settings()
{
    return get_option('cf7dtx_settings', array());
}

/**
 * Updates the CF7 DTX settings in the WP options table
 *
 * @since 4.2.0
 *
 * @param array $settings The settings array
 *
 * @return void
 *
 */
function wpcf7dtx_update_settings($settings)
{
    update_option('cf7dtx_settings', $settings);
}


/**
 * Outputs a useful PHP Warning message to users on how to allow-list denied meta and user keys
 *
 * @since 4.2.0
 *
 * @param string $key The post meta or user key to which access is currently denied
 * @param string $type Either 'post_meta' or 'user_data', used to display an appropriate message to the user
 *
 * @return void
 */
function wpcf7dtx_access_denied_alert($key, $type)
{
    // Only check on the front end
    if (is_admin() || wp_doing_ajax() || wp_is_json_request()) return;

    switch ($type) {
        case 'post_meta':
            $shortcode = 'CF7_get_custom_field';
            $list_name = __('Meta Key Allow List', 'contact-form-7-dynamic-text-extension');
            break;
        case 'user_data':
            $shortcode = 'CF7_get_current_user';
            $list_name = __('User Data Key Allow List', 'contact-form-7-dynamic-text-extension');
            break;
        default:
            $shortcode = '';
            $list_name = '';
            break;
    }

    $settings_page_url = admin_url('admin.php?page=cf7dtx_settings');

    $msg = sprintf(
        __('CF7 DTX: Access denied to key: "%1$s" in dynamic contact form shortcode: [%2$s].  Please add this key to the %3$s at %4$s', 'contact-form-7-dynamic-text-extension'),
        $key,
        $shortcode,
        $list_name,
        $settings_page_url
    );

    trigger_error($msg, E_USER_WARNING);
}
