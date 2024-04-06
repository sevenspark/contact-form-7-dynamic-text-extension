<?php

/**
 * Plugin Name: Contact Form 7 - Dynamic Text Extension
 * Description: Extends Contact Form 7 by adding dynamic form fields that accepts shortcodes to prepopulate form fields with default values and dynamic placeholders.
 * Version: VERSION_PLACEHOLDER
 * Author: AuRise Creative, SevenSpark
 * Author URI: https://aurisecreative.com
 * Plugin URI: https://aurisecreative.com/products/wordpress-plugin/contact-form-7-dynamic-text-extension/
 * License: GPL v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * Text Domain: contact-form-7-dynamic-text-extension
 *
 * @copyright Copyright (c) 2010-2024 Chris Mavricos, SevenSpark <https://sevenspark.com>
 * @copyright Copyright (c) 2022-2024 Tessa Watkins, AuRise Creative <https://aurisecreative.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

define('WPCF7DTX_VERSION', 'VERSION_PLACEHOLDER'); // Define current version of DTX
define('WPCF7DTX_MINVERSION', '5.7'); // The minimum version of CF7 required to use mail validator
defined('WPCF7DTX_DIR') || define('WPCF7DTX_DIR', __DIR__); // Define root directory
defined('WPCF7DTX_FILE') || define('WPCF7DTX_FILE', __FILE__); // Define root file
define('WPCF7DTX_DATA_ACCESS_KB_URL', 'https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/security/');

/**
 * Determine Dependencies are Met
 *
 * @since 4.2.1
 *
 * @return bool True if minimum version of Contact Form 7 is met. False otherwise.
 */
function wpcf7dtx_dependencies()
{
    return defined('WPCF7_VERSION') && version_compare(constant('WPCF7_VERSION'), WPCF7DTX_MINVERSION, '>=');
}

/**
 * Initialise Plugin
 *
 * @return void
 */
function wpcf7dtx_init()
{
    if (!wpcf7dtx_dependencies()) {
        add_action('admin_notices', function () {
            echo (wp_kses_post(sprintf(
                '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
                __('Form validation for dynamic fields created with <em>Contact Form 7 - Dynamic Text Extension</em> is not available!', 'contact-form-7-dynamic-text-extension'),
                sprintf(
                    __('<em>Contact Form 7</em> version %s or higher is required.', 'contact-form-7-dynamic-text-extension'),
                    esc_html(WPCF7DTX_MINVERSION)
                )
            )));
        });
    }
    add_action('wpcf7_init', 'wpcf7dtx_add_shortcodes'); // Add custom form tags to CF7
}
add_action('plugins_loaded', 'wpcf7dtx_init', 20);

/**
 * DTX Formg Tag Configuration
 *
 * @since 4.0.0
 *
 * @return array
 */
function wpcf7dtx_config()
{
    global $wpcf7_dynamic_fields_config;
    if (!isset($wpcf7_dynamic_fields_config)) {
        $wpcf7_dynamic_fields_config = array(
            'dynamic_text' => array(
                'title' => __('dynamic text', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly'),
                'description' => __('a single-line plain text', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_hidden' => array(
                'title' => __('dynamic hidden', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array(),
                'description' => __('a single-line plain text hidden input field', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'display-hidden' => true // Generates an HTML element that is not visible
                )
            ),
            'dynamic_email' => array(
                'title' => __('dynamic email', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly'),
                'description' => __('a single-line email address input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_url' => array(
                'title' => __('dynamic URL', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly'),
                'description' => __('a single-line URL input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_tel' => array(
                'title' => __('dynamic tel', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'pattern'),
                'description' => __('a single-line telephone number input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_number' => array(
                'title' => __('dynamic number', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max', 'step', 'pattern'),
                'description' =>  __('a numeric input field displayed as a number spinbox', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_range' => array(
                'title' => __('dynamic range', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max', 'step', 'pattern'),
                'description' =>  __('a numeric input field displayed as a slider between a minimum and maximum range', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_textarea' => array(
                'title' => __('dynamic textarea', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly'),
                'description' => __('a multi-line plain text input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_select' => array(
                'title' => __('dynamic drop-down menu', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'multiple', 'include_blank'),
                'description' => __('a drop-down menu (i.e select input field)', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_checkbox' => array(
                'title' => __('dynamic checkboxes', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('readonly', 'label_first', 'use_label_element', 'exclusive'),
                'description' => __('a group of checkboxes', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'multiple-controls-container' => true, // Generates an HTML element that can contain multiple form controls
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_radio' => array(
                'title' => __('dynamic radio buttons', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('readonly', 'label_first', 'use_label_element'),
                'description' => __('a group of radio buttons', 'contact-form-7-dynamic-text-extension'),
                'features' => array(
                    'multiple-controls-container' => true, // Generates an HTML element that can contain multiple form controls
                    'selectable-values' => true // Generates an option (or group of options) from which you can select one or more options
                )
            ),
            'dynamic_date' => array(
                'title' => __('dynamic date', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array('placeholder', 'readonly', 'min', 'max', 'step'),
                'description' =>  __('a date input field', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_submit' => array(
                'title' => __('dynamic submit', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array(),
                'description' =>  __('a submit button', 'contact-form-7-dynamic-text-extension')
            ),
            'dynamic_label' => array(
                'title' => __('dynamic label', 'contact-form-7-dynamic-text-extension'), //title
                'options' => array(),
                'description' =>  __('a label element', 'contact-form-7-dynamic-text-extension')
            )
        );
    }
    return $wpcf7_dynamic_fields_config;
}

/**
 * Add Custom Shortcodes to Contact Form 7
 *
 * @return void
 */
function wpcf7dtx_add_shortcodes()
{
    //Add the dynamic form fields
    foreach (wpcf7dtx_config() as $form_tag => $field) {
        $input_type = str_replace('dynamic_', '', $form_tag);
        $tag_types = array($form_tag, "$form_tag*");
        $callback = 'wpcf7dtx_shortcode_handler';
        $features = array_merge(array('name-attr' => true), wpcf7dtx_array_has_key('features', $field, array()));
        switch ($input_type) {
            case 'text':
            case 'hidden':
                // Add deprecated tags
                $dep_tag = str_replace('_', '', $form_tag);
                $tag_types[] = $dep_tag;
                $tag_types[] = "$dep_tag*";
                add_filter("wpcf7_validate_$dep_tag*", 'wpcf7dtx_validation_filter', 20, 2); // Validate required deprecated form tags
                break;
            case 'submit':
            case 'reset':
                $callback = 'wpcf7dtx_button_shortcode_handler';
                $features['name-attr'] = false;
                break;
            case 'label':
                $callback = 'wpcf7dtx_label_shortcode_handler';
                $features['name-attr'] = false;
                break;
            default:
                break;
        }
        add_filter("wpcf7_validate_$form_tag*", 'wpcf7dtx_validation_filter', 20, 2); // Validate required custom form tags
        wpcf7_add_form_tag($tag_types, $callback, $features);
    }
}

/**
 * Register Frontend Script
 *
 * Register the frontend script to be optionally loaded later.
 *
 * @since 3.5.0
 *
 * @param string $hook Hook suffix for the current page
 *
 * @return void
 */
function wpcf7dtx_enqueue_frontend_assets($hook = '')
{
    $debug = defined('WP_DEBUG') && constant('WP_DEBUG');
    $url = plugin_dir_url(WPCF7DTX_FILE);
    $path = plugin_dir_path(WPCF7DTX_FILE);
    wp_register_script(
        'wpcf7dtx', // Handle
        $url . 'assets/scripts/dtx' . ($debug ? '' : '.min') . '.js', // Source
        array('jquery-core'), // Dependencies
        $debug ? @filemtime($path . 'assets/scripts/dtx.js') : WPCF7DTX_VERSION, // Version
        array('in_footer' => true, 'strategy' => 'defer') // Defer loading in footer

    );
    wp_localize_script(
        'wpcf7dtx', // Handle
        'dtx_obj', // Object
        array('ajax_url' => admin_url('admin-ajax.php')) // Data
    );
}
add_action('wp_enqueue_scripts', 'wpcf7dtx_enqueue_frontend_assets');

/**
 * Include Utility Functions
 */
include_once(WPCF7DTX_DIR . '/includes/utilities.php');

/**
 * Include Validation Functions
 */
include_once(WPCF7DTX_DIR . '/includes/validation.php');

/**
 * Form Tag Handler
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_shortcode_handler($tag)
{
    // Name attribute is required for these form tags
    if (empty($tag->name)) {
        return '';
    }

    // Validate
    $validation_error = wpcf7_get_validation_error($tag->name);

    //Configure input attributes
    $atts = array();
    $atts['type'] = sanitize_key(str_replace(array('dynamic_', 'dynamic'), '', $tag->basetype));
    $atts['name'] = $tag->name;
    $atts['id'] = strval($tag->get_id_option());
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['class'] = explode(' ', wpcf7_form_controls_class($atts['type']));
    $atts['class'][] = 'wpcf7dtx';
    $atts['class'][] = sanitize_html_class('wpcf7dtx-' . $atts['type']);
    if ($validation_error) {
        $atts['class'][] = 'wpcf7-not-valid';
        $atts['aria-invalid'] = 'true';
        $atts['aria-describedby'] = wpcf7_get_validation_error_reference($tag->name);
    } else {
        $atts['aria-invalid'] = 'false';
    }
    if ($tag->has_option('readonly')) {
        $atts['readonly'] = 'readonly';
    }

    // Dynamically determine disabled attribute, remove if invalid
    $atts['disabled'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('disabled', '', true)), ENT_QUOTES));
    if ($atts['disabled'] != 'disabled') {
        unset($atts['disabled']);
    }

    // Dynamically determine autofocus attribute, remove if invalid
    $atts['autofocus'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('autofocus', '', true)), ENT_QUOTES));
    if ($atts['autofocus'] != 'autofocus') {
        unset($atts['autofocus']);
    }

    // Add required attribute to applicable input types
    if ($tag->is_required() && !in_array($atts['type'], array('hidden', 'quiz'))) {
        $atts['aria-required'] = 'true';
        $atts['required'] = 'required';
    }

    // Evaluate the dynamic value
    $sanitize_type = $atts['type'] == 'textarea' ? $atts['type'] : 'auto';
    $value = wpcf7dtx_get_dynamic(false, $tag, $sanitize_type);

    // Identify placeholder
    if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
        //Reverse engineer what JS did (converted quotes to HTML entities --> URL encode) then sanitize
        $placeholder = html_entity_decode(urldecode($tag->get_option('placeholder', '', true)), ENT_QUOTES);
        if ($placeholder) {
            // If a different placeholder text has been specified, set both attributes
            $placeholder = wpcf7dtx_get_dynamic($placeholder, false, $sanitize_type);
            $atts['placeholder'] = $placeholder;
            $atts['value'] = $value;
        } else {
            // Default behavior of using the value as the placeholder
            $atts['placeholder'] = $value;
            $atts['value'] = '';
        }
    } else {
        $atts['value'] = $value;
    }

    // Page load attribute
    if ($tag->has_option('dtx_pageload') && is_array($tag->raw_values) && count($tag->raw_values)) {
        $atts['data-dtx-value'] = rawurlencode(sanitize_text_field($tag->raw_values[0]));
        $atts['class'][] = 'dtx-pageload';
        if (wp_style_is('wpcf7dtx', 'registered') && !wp_script_is('wpcf7dtx', 'queue')) {
            // If already registered, just enqueue it
            wp_enqueue_script('wpcf7dtx');
        } elseif (!wp_style_is('wpcf7dtx', 'registered')) {
            // If not registered, do that first, then enqueue it
            wpcf7dtx_enqueue_frontend_assets();
            wp_enqueue_script('wpcf7dtx');
        }
    }

    // Additional configuration based on form field type
    if (in_array($atts['type'], array('select', 'checkbox', 'radio'))) {
        /**
         * Configuration for selection-based fields
         */
        if ($tag->has_option('default')) {
            $atts['dtx-default'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('default', '', true)), ENT_QUOTES));
        }

        // Get options for selection-based fields
        $options = array();
        $pipes = $tag->pipes->to_array();
        if (count($pipes)) {
            foreach ($pipes as $pipe) {
                $key = trim(strval($pipe[0]));
                $value = trim(strval($pipe[1]));
                $valid_key = is_numeric($key) || (is_string($key) && !empty($key)); // Allow falsey numbers but not booleans or strings
                $valid_value = is_numeric($value) || (is_string($value) && !empty($value)); // Allow falsey numbers but not booleans or strings
                if ($valid_key && $valid_value) {
                    $options[$key] = $value;
                }
            }
        }
        if ($atts['type'] == 'select' && $tag->has_option('include_blank')) {
            $atts['placeholder'] = wpcf7dtx_array_has_key('placeholder', $atts, __('&#8212;Please choose an option&#8212;', 'contact-form-7-dynamic-text-extension'));
        }
        if ($atts['type'] == 'select') {
            $atts['size'] = $tag->get_size_option('1');
        }
    } else {
        /**
         * Configuration for text-based fields
         */
        $atts['size'] = $tag->get_size_option('40');
        $atts['list'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('list', '', true)), ENT_QUOTES));
        $atts['autocapitalize'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('autocapitalize', '', true)), ENT_QUOTES));
        $atts['pattern'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('pattern', '', true)), ENT_QUOTES));
        $atts['min'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('min', '', true)), ENT_QUOTES));
        $atts['max'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('max', '', true)), ENT_QUOTES));
        $atts['step'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('step', '', true)), ENT_QUOTES));

        // Min and Max length attributes
        $atts['maxlength'] = $tag->get_maxlength_option();
        $atts['minlength'] = $tag->get_minlength_option();
        if ($atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength']) {
            unset($atts['maxlength'], $atts['minlength']);
        }

        // Autocomplete attribute
        if ($atts['type'] == 'hidden') {
            $atts['autocomplete'] = 'off'; // Always disable for hidden fields
        } else {
            // Disable autocomplete for this field if a dynamic value has been specified
            $atts['autocomplete'] = $atts['value'] ? 'off' : $tag->get_option('autocomplete', '[-0-9a-zA-Z]+', true);
        }

        switch ($atts['type']) {
            case 'range':
                // Client-side validation by type
                $atts['class'][] =  'wpcf7-validates-as-number';
                break;
            case 'date':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':
                // Client-side validation by type
                $atts['class'][] =  sanitize_html_class('wpcf7-validates-as-' . $atts['type']);
                break;
            case 'textarea':
                // Attributes unique to textareas
                $atts['cols'] = $tag->get_cols_option('40');
                $atts['rows'] = $tag->get_rows_option('10');
                $atts['wrap'] = $tag->get_option('wrap', '', true);
                if (!in_array($atts['wrap'], array('hard', 'soft'))) {
                    unset($atts['wrap']);
                }
                break;
        }
    }

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($atts['class']);

    // Output the form field HTML
    $wrapper = '<span class="wpcf7-form-control-wrap %1$s" data-name="%1$s">%2$s%3$s</span>';
    $allowed_html = array('br' => array(), 'span' => array('id' => array(), 'class' => array(), 'data-name' => array(), 'aria-hidden' => array()));
    switch ($atts['type']) {
        case 'checkbox':
        case 'radio':
            return wp_kses(sprintf(
                str_replace('<span class=', '<span%4$s class=', $wrapper), // Insert a 4th parameter for wrapper
                esc_attr($tag->name),
                wpcf7dtx_checkbox_group_html(
                    $atts,
                    $options,
                    in_array('use_label_element', $tag->options),
                    in_array('label_first', $tag->options),
                    in_array('exclusive', $tag->options)
                ),
                $validation_error,
                $atts['id'] ? sprintf(' id="%s"', esc_attr($atts['id'])) : ''
            ), array_merge($allowed_html, array(
                'label' => array('for' => array()),
                'input' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
        case 'select':
            $allowed_html = array_merge($allowed_html, wpcf7dtx_get_allowed_field_properties('option'), array(
                'select' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            ));
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_select_html(
                    $atts,
                    $options,
                    $tag->has_option('dtx_hide_blank'),
                    $tag->has_option('dtx_disable_blank')
                ),
                $validation_error
            ), array_merge($allowed_html, wpcf7dtx_get_allowed_field_properties('option'), array(
                'select' => wpcf7dtx_get_allowed_field_properties($atts['type']),
            )));
        case 'textarea':
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_textarea_html($atts),
                $validation_error
            ), array_merge($allowed_html, array(
                'textarea' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
        default:
            return wp_kses(sprintf(
                $wrapper,
                esc_attr($tag->name),
                wpcf7dtx_input_html($atts),
                $validation_error
            ), array_merge($allowed_html, array(
                'input' => wpcf7dtx_get_allowed_field_properties($atts['type'])
            )));
    }
}

/**
 * Form Tag Handler for Dynamic Submit
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_button_shortcode_handler($tag)
{
    //Configure input attributes
    $atts = array();
    $atts['type'] = sanitize_key(str_replace('dynamic_', '', $tag->basetype));
    $atts['id'] = strval($tag->get_id_option());
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['value'] = wpcf7dtx_get_dynamic(false, $tag); // Evaluate the dynamic value
    $atts['class'] = explode(' ', wpcf7_form_controls_class($atts['type']));
    $atts['class'][] = 'wpcf7dtx';
    $atts['class'][] = sanitize_html_class('wpcf7dtx-' . $atts['type']);
    if ($atts['type'] == 'submit') {
        $atts['class'][] = 'has-spinner';
    }

    // Default value if empty
    if (empty($atts['value'])) {
        switch ($atts['type']) {
            case 'reset':
                $atts['value'] = __('Clear', 'contact-form-7-dynamic-text-extension');
                break;
            default:
                $atts['value'] = __('Send', 'contact-form-7-dynamic-text-extension');
                break;
        }
    }

    // Page load attribute
    if ($tag->has_option('dtx_pageload') && is_array($tag->raw_values) && count($tag->raw_values)) {
        $atts['data-dtx-value'] = rawurlencode(sanitize_text_field($tag->raw_values[0]));
        $atts['class'][] = 'dtx-pageload';
        if (wp_style_is('wpcf7dtx', 'registered') && !wp_script_is('wpcf7dtx', 'queue')) {
            // If already registered, just enqueue it
            wp_enqueue_script('wpcf7dtx');
        } elseif (!wp_style_is('wpcf7dtx', 'registered')) {
            // If not registered, do that first, then enqueue it
            wpcf7dtx_enqueue_frontend_assets();
            wp_enqueue_script('wpcf7dtx');
        }
    }

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($atts['class']);

    // Output the form field HTML
    return wp_kses(
        wpcf7dtx_input_html($atts),
        array('input' => wpcf7dtx_get_allowed_field_properties($atts['type']))
    );
}

/**
 * Form Tag Handler for Dynamic Label
 *
 * @param WPCF7_FormTag $tag Current Contact Form 7 tag object
 *
 * @return string HTML output of the shortcode
 */
function wpcf7dtx_label_shortcode_handler($tag)
{
    $atts = array();
    $atts['id'] = strval($tag->get_id_option());
    $atts['class'] = wpcf7_form_controls_class('wpcf7dtx wpcf7dtx-label');
    $atts['for'] = wpcf7dtx_get_dynamic(html_entity_decode(urldecode($tag->get_option('for', '', true)), ENT_QUOTES)); // Get dynamic attribute

    // Page load attribute
    if ($tag->has_option('dtx_pageload') && is_array($tag->raw_values) && count($tag->raw_values)) {
        $atts['data-dtx-value'] = rawurlencode(sanitize_text_field($tag->raw_values[0]));
        $atts['class'] .= ' dtx-pageload';
        if (wp_style_is('wpcf7dtx', 'registered') && !wp_script_is('wpcf7dtx', 'queue')) {
            // If already registered, just enqueue it
            wp_enqueue_script('wpcf7dtx');
        } elseif (!wp_style_is('wpcf7dtx', 'registered')) {
            // If not registered, do that first, then enqueue it
            wpcf7dtx_enqueue_frontend_assets();
            wp_enqueue_script('wpcf7dtx');
        }
    }

    // Wrap up class attribute
    $atts['class'] = $tag->get_class_option($atts['class']);

    // Output the form field HTML
    return wp_kses(
        sprintf(
            '<label %s>%s</label>',
            wpcf7dtx_format_atts($atts),
            wpcf7dtx_get_dynamic(false, $tag) // Evaluate the dynamic label text
        ),
        array_merge(
            wp_kses_allowed_html('data'), // Get allowed HTML for inline data
            array('label' => wpcf7dtx_get_allowed_field_properties('label')) // Include our label field
        )
    );
}

/**
 * AJAX Request Handler for After Page Loading
 *
 * @since 3.5.0
 *
 * @param array $_POST A sequential array of url encoded shortcode values to evaluate
 *
 * @return array A sequential array of objects with `raw_value` and `value` keys
 */
function wpcf7dtx_js_handler()
{
    $return = array();
    $queue = wpcf7dtx_array_has_key('shortcodes', $_POST);
    if (is_array($queue) && count($queue)) {
        foreach ($queue as $field) {
            $multiline = wpcf7dtx_array_has_key('multiline', $field, false);
            $raw_value = sanitize_text_field(rawurldecode(wpcf7dtx_array_has_key('value', $field)));
            $return[] = array(
                'raw_value' => esc_attr($raw_value),
                'value' => esc_attr(wpcf7dtx_get_dynamic($raw_value, false, $multiline ? 'textarea' : 'auto'))
            );
        }
    }
    wp_die(json_encode($return));
}
add_action('wp_ajax_wpcf7dtx', 'wpcf7dtx_js_handler'); // Add AJAX call for logged in users
add_action('wp_ajax_nopriv_wpcf7dtx', 'wpcf7dtx_js_handler'); // Add AJAX call for anonymous users

if (is_admin()) {
    /**
     * Include the Admin Stuff
     */
    include_once(WPCF7DTX_DIR . '/includes/admin.php');
}

/**
 * Built-in Shortcodes
 */
include_once(WPCF7DTX_DIR . '/includes/shortcodes.php');

/**
 * Website's custom shortcodes, if they exist
 */
$user_files = array(
    constant('WP_CONTENT_DIR') . '/dtx.php', // e.g. C:\path\to\website\wp-content\dtx.php
    get_template_directory() . '/dtx.php', // e.g. C:\path\to\website\wp-content/themes/parent-theme/dtx.php
    get_stylesheet_directory() . '/dtx.php' // e.g. C:\path\to\website\wp-content/themes/child-theme/dtx.php
);
foreach ($user_files as $user_file) {
    if (file_exists($user_file)) {
        include_once($user_file);
    }
}
