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
 * Validate Post ID
 *
 * Sanitizes or gets the post id and then checks if the current user can access the post.
 *
 * @since 4.5.1
 *
 * @param int|WP_Post|null|false $post_id Optional. Post ID or post object. Defaults
 *      to global $post.
 * @param string $context Optional. The context in which type of value to return on
 *      failure. Options are `acf` and `dtx`. Default is `dtx`.
 *
 * @return int|WP_Post|null|false The `$post_id` parameter on sucess, 0 otherwise.
 */
function wpcf7dtx_validate_post_id($post_id = null, $context = 'dtx')
{
    return wpcf7dtx_user_can_view_post(wpcf7dtx_get_post_id($post_id, $context));
}

/**
 * Checks if Current User Can Access Post
 *
 * Returns the post id on the following conditions:
 *  1. If the post is publicly published and is a
 *
 * @see https://developer.wordpress.org/reference/functions/is_post_publicly_viewable/
 * @see https://developer.wordpress.org/reference/functions/post_password_required/
 *
 * @since 4.5.1
 *
 * @param int|WP_Post|null|false $post_id Optional. Post ID or post object. Defaults to global $post.
 *
 * @return int|WP_Post|null|false The `$post_id` parameter on sucess, 0 otherwise.
 */
function wpcf7dtx_user_can_view_post($post_id = null)
{
    // Ensure we have a valid post id or null as functions allow, ACF context may pass boolean false as post id
    $_post_id = is_int($post_id) || $post_id instanceof WP_Post ? $post_id : null;
    if (
        (is_post_publicly_viewable($_post_id) && !post_password_required($_post_id)) ||  // Allows publicly published posts of post types and status that are publicly visible that have no password or user can access the password protected post
        current_user_can('edit_post', $_post_id) || // Allows anyone with edit access to this specific post (author, editor, admin, etc.)
        (get_post_status($_post_id) == 'private' && current_user_can('read_private_posts')) // Allows anyone with private capability to read private post
    ) {
        return $post_id; // Return the original, unaltered post id
    }
    return 0;
}

/**
 * Sanitize/Get Post ID
 *
 * Sanitizes the post id passed to the function. If omitted, it will get the current
 * post or page id prioritizing the global `$post` object.
 *
 * @access private
 *
 * @param int|string $post_id Optional. The post id to sanitize or a falsy value to
 *      get the current post or page id.
 * @param string $context Optional. The context in which type of value to return on
 *      failure. Options are `acf` and `dtx`. Default is `dtx`.
 *
 * @return int|false The post id on suceess. 0 on failure with `dtx` $context, boolean
 *      false with `acf` $context.
 */
function wpcf7dtx_get_post_id($post_id = '', $context = 'dtx')
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
 * @param string $option_name Optional. Specify an option from the $tag to retrieve and
 *      decode. Default is `value`.
 * @param string $option_pattern Optional. A regular expression pattern or one of the
 *      keys of preset patterns. If specified, only options whose value part matches
 *      this pattern will be returned.
 *
 * @return string The dynamic output or the original value, not escaped or sanitized.
 */
function wpcf7dtx_get_dynamic($value, $tag = false, $sanitize = 'auto', $option_name = 'value', $option_pattern = '')
{
    if (is_object($tag)) {
        if ($option_name != 'value') {
            $value = html_entity_decode(urldecode(strval($tag->get_option($option_name, $option_pattern, true))), ENT_QUOTES);
        } else {
            $default = $tag->get_option('defaultvalue', '', true);
            if (!$default) {
                $default = $tag->get_default_option(strval(reset($tag->values)));
            }
            $value = wpcf7_get_hangover($tag->name, $default);
        }
    }
    $value = apply_filters('wpcf7dtx_sanitize', $value, $sanitize);
    if (is_string($value) && !empty($value)) {
        // If a shortcode was passed as the value, attempt to evaluate itevaluate it and use the result
        $shortcode_tag = '[' . $value . ']';
        //var_dump('Shortcode tag?', $shortcode_tag);
        $shortcode_output = do_shortcode($shortcode_tag); //Shortcode value
        //var_dump('Shortcode value?', $shortcode_output);
        if ($shortcode_output != $shortcode_tag) {
            return apply_filters('wpcf7dtx_sanitize', $shortcode_output, $sanitize);
        }
    }
    return $value;
}

/**
 * Get Dynamic Attribute
 *
 * @since 5.0.1
 *
 * @param string $option_name Specify an option from the $tag to retrieve and decode.
 * @param WPCF7_FormTag $tag Use to look up default value.
 * @param string $sanitize Optional. Specify type of sanitization. Default is `auto`.
 * @param string $basetype Optional. Specify a basetype to use in the class instead
 *      of what is in the tag.
 * @param string $option_pattern Optional. A regular expression pattern or one of the
 *      keys of preset patterns. If specified, only options whose value part matches
 *      this pattern will be returned.
 *
 * @return string The dynamic output or the original value, not escaped or sanitized.
 */
function wpcf7dtx_get_dynamic_attr($option_name, $tag, $sanitize = 'auto', $basetype = '', $option_pattern = '')
{
    if ($option_name !== 'class') {
        $value = $tag->get_option($option_name, $option_pattern, true);
        if ($value === false) {
            return '';
        }
        return wpcf7dtx_dynamic_attr($value, $sanitize);
    }
    $values = array();
    if ($option_name == 'class') {
        $type = trim(sanitize_key($basetype ? $basetype : str_replace(array('dynamic_', 'dynamic'), '', $tag->basetype)));
        $values = explode(' ', wpcf7_form_controls_class($type));
        $values[] = 'wpcf7dtx';
        $values[] = sanitize_html_class('wpcf7dtx-' . $type);

        // Client-side validation by type
        switch ($type) {
            case 'range':
                $values[] =  'wpcf7-validates-as-number';
                break;
            case 'date':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':
                $values[] =  sanitize_html_class('wpcf7-validates-as-' . $type);
                break;
            case 'submit':
                $values[] = 'has-spinner';
                break;
            default:
                break;
        }
    }

    // Add in the user-added possibly dynamic user values
    $classes = $tag->get_option($option_name, $option_pattern, false);
    if (is_array($classes)) {
        foreach ($classes as $class) {
            $values[] = wpcf7dtx_dynamic_attr($class, $sanitize);
        }
    }
    return $values;
}

/**
 * Decode and Evaluate Dynamic Attribute
 *
 * @since 5.0.1
 *
 * @param string $value The value of the attribute.
 * @param string $sanitize Optional. Specify type of sanitization. Default is `auto`.
 *
 * @param string The dynamic output or the original value, not escaped or sanitized.
 */
function wpcf7dtx_dynamic_attr($value, $sanitize = 'auto')
{
    return wpcf7dtx_get_dynamic(html_entity_decode(urldecode(strval($value)), ENT_QUOTES), false, $sanitize); // Get dynamic attribute
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
                'label' => true,
                'disabled' => true,
                'hidden' => true
            ),
            'option' => array(
                'value' => true,
                'selected' => true,
                'disabled' => true,
                'hidden' => true
            )
        );
    }
    $allowed_properties = array(
        // Global properties
        'type' => true,
        'id' => true,
        'name' => true,
        'value' => true,
        'class' => true,
        'disabled' => true,
        'tabindex' => true,
        'title' => true,
        // ARIA properties
        'aria-invalid' => true,
        'aria-describedby' => true,
        // DTX properties
        'data-dtx-value' => true,
    );
    if ($type != 'hidden') {
        $allowed_properties['autofocus'] = true;
        $allowed_properties['readonly'] = true;
        $allowed_properties['required'] = true;
    }
    if (in_array($type, array('checkbox', 'radio', 'acceptance'))) {
        // Properties exclusive to checkboxes and radio buttons
        $allowed_properties['checked'] = true;
        $allowed_properties['dtx-default'] = true;
    } elseif ($type == 'select') {
        // Properties exclusive to select fields
        $allowed_properties['size'] = true;
        $allowed_properties['multiple'] = true;
        $allowed_properties['dtx-default'] = true;
        unset($allowed_properties['type'], $allowed_properties['value']); // Remove invalid select attributes
    } elseif ($type == 'label') {
        // Properties exclusive to label elements
        $allowed_properties['for'] = true;
        // Remove invalid label attributes
        unset(
            $allowed_properties['type'],
            $allowed_properties['name'],
            $allowed_properties['value'],
            $allowed_properties['disabled'],
            $allowed_properties['aria-invalid']
        );
    } else {
        // Properties exclusive to text-based inputs
        $allowed_properties['autocapitalize'] = true;
        $allowed_properties['autocomplete'] = true;
        $allowed_properties['list'] = true;

        // Placeholder
        if (in_array($type, array('text', 'textarea', 'search', 'url', 'tel', 'email', 'password', 'number'))) {
            $allowed_properties['placeholder'] = true;
        }

        // Textarea
        if ($type == 'textarea') {
            // Additional properties exclusive to textarea fields
            $allowed_properties['cols'] = true;
            $allowed_properties['rows'] = true;
            $allowed_properties['minlength'] = true;
            $allowed_properties['maxlength'] = true;
            $allowed_properties['wrap'] = true;
            unset($allowed_properties['type'], $allowed_properties['value']); // Remove invalid textarea attributes
        } elseif (in_array($type, array('text', 'search', 'url', 'tel', 'email', 'password'))) {
            // Additional properties exclusive to these text-based fields
            $allowed_properties['size'] = true;
            $allowed_properties['minlength'] = true;
            $allowed_properties['maxlength'] = true;
            $allowed_properties['pattern'] = true;
        } elseif (in_array($type, array('number', 'range', 'date', 'datetime-local', 'time'))) {
            // Number and date inputs
            $allowed_properties['min'] = true;
            $allowed_properties['max'] = true;
            $allowed_properties['step'] = true;
        }
    }
    if (is_array($extra) && count($extra)) {
        foreach ($extra as $property) {
            $allowed_properties[sanitize_title($property)] = true;
        }
    }
    return $allowed_properties;
}

/**
 * Returns a formatted string of HTML attributes
 *
 * Boolean attributes are set to themselves if the value is a boolean itself
 * or if the key is checked, disabled, multiple, readonly, required, or selected.
 * The value of the key class can be array for sanitizing.
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
            'checked',
            'disabled',
            'multiple',
            'readonly',
            'required',
            'selected'
        );
        foreach ($atts as $key => $value) {
            $key = trim(sanitize_title($key));
            if ($key) {
                if ($key == 'class' && is_array($value)) {
                    $sanitized_atts['class'] = array();
                    $value = array_values(array_unique(array_filter($value))); // Remove duplicates, empty values, and reindex keys
                    foreach ($value as $i => $class) {
                        // Sanitize the class and add it if it's not empty
                        if ($class = trim(sanitize_html_class(sanitize_text_field($class)))) {
                            $sanitized_atts['class'][] = $class;
                        }
                    }
                    $sanitized_atts['class'] = implode(' ', $sanitized_atts['class']); // Implode all classes to be a single attribute value
                } elseif (in_array($key, $boolean_attributes) || is_bool($value)) {
                    if ($value) {
                        $sanitized_atts[$key] = $key;
                    }
                } elseif (is_numeric($value) || (is_string($value) && $value)) {
                    // Allow all numbers (even if falsey) and strings (only if value)
                    $sanitized_atts[$key] = $value;
                }
            }
        }
        if (count($sanitized_atts)) {
            $output = array();
            foreach ($sanitized_atts as $sanitized_key => $sanitized_value) {
                $output[] = sprintf('%s="%s"', esc_attr($sanitized_key), esc_attr($sanitized_value));
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
    return sprintf('<input %s>', wpcf7dtx_format_atts($atts));
}

/**
 * Create Checkbox Field HTML
 *
 * @since 4.0.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param string $label_text Optional. The text to display next to the checkbox or
 *      radio button.
 * @param bool $label_ui Optional. If true, will place input and label text inside
 *      a `<label>` element. Default is true.
 * @param bool $reverse Optional. If true, will reverse the order to display the
 *      text label first then the button. Has no effect if label text is empty.
 *      Default is false.
 *
 * @return string HTML output of the checkbox or radio button or empty string on
 *      failure.
 */
function wpcf7dtx_checkbox_html($atts, $label_text = '', $label_ui = true, $reverse = false)
{
    // Default field attributes
    $atts = array_merge(array('value' => '', 'dtx-default' => ''), array_change_key_case((array)$atts, CASE_LOWER));

    // Checkboxes can have multiple values checked, check mine if it's listed as a default value
    if ($atts['type'] == 'checkbox' && is_string($atts['dtx-default']) && strpos($atts['dtx-default'], '_') !== false) {
        $default = array_unique(explode('_', $atts['dtx-default']));
        if (in_array($atts['value'], $default)) {
            $atts['checked'] = 'checked';
        }
    } elseif ((is_numeric($atts['dtx-default']) || $atts['dtx-default']) && $atts['value'] == $atts['dtx-default']) {
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
 * @param bool $label_ui Optional. If true, will place input and label text inside a `<label>`
 *      element. Default is true.
 * @param bool $reverse Optional. If true, will reverse the order to display the text label
 *      first then the button. Has no effect if label text is empty. Default is false.
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
            if (is_string($value) && !empty($value) && $value === $label) {
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
            $valid_default = is_numeric($atts['dtx-default']) || (is_string($atts['dtx-default']) && !empty($atts['dtx-default']));
            if ($valid_default && $dynamic_value == $atts['dtx-default']) {
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
 * Create Options HTML
 *
 * @since 4.4.0
 *
 * @param array $options Accepts an associative array of key/value pairs to use as the
 * select option's value/label pairs.
 * @param bool $selected_value Optional. The value that should be selected by default.
 *
 * @return string HTML output of options
 */
function wpcf7dtx_options_html($options, $selected_value = '')
{
    $html = '';
    foreach ($options as $value => $label) {
        $dynamic_value = wpcf7dtx_get_dynamic($value);
        $dynamic_label = wpcf7dtx_get_dynamic($label);
        $html .= sprintf(
            '<option value="%1$s"%3$s>%2$s</option>',
            esc_attr(apply_filters('wpcf7dtx_escape', $dynamic_value)),
            esc_html(apply_filters('wpcf7dtx_escape', $dynamic_label)),
            $selected_value == $dynamic_label ? ' selected' : ''
        );
    }
    return $html;
}

/**
 * Create Select Field HTML
 *
 * @since 4.0.0
 *
 * @param array $atts An associative array of select input attributes.
 * @param array|string $options Accepts an associative array of key/value pairs to use as the
 *      select option's value/label pairs. It also accepts an associative array of associative
 *      arrays with the keys being used as option group labels and the array values used as that
 *      group's options. It also accepts a string value of HTML already formatted as options or
 *      option groups. It also accepts a string value of a self-closing shortcode that is
 *      evaluated and its output is either options or option groups.
 * @param bool $hide_blank Optional. If true, the first blank placeholder option will have the
 *      `hidden` attribute added to it. Default is false.
 * @param bool $disable_blank Optional. If true, the first blank placeholder option will have
 *      the `disabled` attribute added to it. Default is false.
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
 *      an empty string.
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
