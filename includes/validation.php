<?php

/**
 * Add Frontend Validation Messages
 *
 * @since 4.0.0
 *
 * @param array An associative array of messages
 *
 * @return array A modified associative array of messages
 */
function wpcf7dtx_messages($messages)
{
    return array_merge($messages, array(
        'dtx_invalid_email' => array(
            'description' => __('There is a field with an invalid email address', 'contact-form-7-dynamic-text-extension'),
            'default' => __('Please enter a valid email address.', 'contact-form-7-dynamic-text-extension')
        ),
        'dtx_invalid_tel' => array(
            'description' => __('There is a field with an invalid phone number', 'contact-form-7-dynamic-text-extension'),
            'default' => __('Please enter a valid phone number.', 'contact-form-7-dynamic-text-extension')
        ),
        'dtx_invalid_number' => array(
            'description' => __('There is a field with an invalid number', 'contact-form-7-dynamic-text-extension'),
            'default' => __('Please enter a valid number.', 'contact-form-7-dynamic-text-extension')
        ),
        'dtx_invalid_date' => array(
            'description' => __('There is a field with an invalid date', 'contact-form-7-dynamic-text-extension'),
            'default' => __('Please enter a valid date.', 'contact-form-7-dynamic-text-extension')
        ),
    ));
}
add_filter('wpcf7_messages', 'wpcf7dtx_messages');

/**
 * Add DTX Error Code to Config Validator
 *
 * @since 5.0.0
 *
 * @param array $error_codes A sequential array of available error codes in Contact Form 7.
 *
 * @return array A modified sequential array of available error codes in Contact Form 7.
 */
function wpcf7dtx_config_validator_available_error_codes($error_codes)
{
    $dtx_errors = array('dtx_disallowed');
    return array_merge($error_codes, $dtx_errors);
}
add_filter('wpcf7_config_validator_available_error_codes', 'wpcf7dtx_config_validator_available_error_codes');

/**
 * Validate DTX Form Fields
 *
 * Frontend validation for DTX form tags
 *
 * @param WPCF7_Validation $result the current validation result object
 * @param WPCF7_FormTag $tag the current form tag being filtered for validation
 *
 * @return WPCF7_Validation a possibly modified validation result object
 */
function wpcf7dtx_validation_filter($result, $tag)
{
    $type = str_replace(array('dynamic_', 'dynamic'), '', $tag->basetype);
    if (empty($tag->name) || in_array($type, array('hidden', 'submit', 'reset'))) {
        return $result; // Bail early for tags without names or if a specific type
    }

    // Get the value
    $user_value = wpcf7dtx_array_has_key($tag->name, $_POST);
    if (is_array($user_value)) {
        $selection_count = count($user_value);
        if (!wpcf7_form_tag_supports($tag->type, 'selectable-values')) {
            // Field passed selectable values when it's doesn't support them
            $result->invalidate($tag, wpcf7_get_message('validation_error'));
            return $result;
        } elseif ($selection_count > 1) {
            if (!wpcf7_form_tag_supports($tag->type, 'multiple-controls-container')) {
                // Field passed multiple values when it's doesn't support them
                $result->invalidate($tag, wpcf7_get_message('validation_error'));
                return $result;
            }
            foreach ($user_value as $selection) {
                // Validate each selected choice
                $result = wpcf7dtx_validate_value($result, sanitize_textarea_field(strval($selection)), $tag, $type);
                if (!$result->is_valid($tag->name)) {
                    return $result; // Return early if any are invalid
                }
            }
            return $result;
        }
        $user_value = sanitize_text_field(strval(implode(' ', $user_value)));
    } elseif ($type == 'textarea') {
        $user_value = sanitize_textarea_field(strval($user_value));
    } else {
        $user_value = sanitize_text_field(strval($user_value));
    }
    // Validate and return
    return wpcf7dtx_validate_value($result, $user_value, $tag, $type);
}

/**
 * Validate Single Value
 *
 * @param WPCF7_Validation $result the current validation result object
 * @param string $value the current value being validated, sanitized
 * @param WPCF7_FormTag $tag the current form tag being filtered for validation
 * @param string $type Optional. The type of the current form tag. Default is blank for lookup.
 *
 * @return WPCF7_Validation a possibly modified validation result object
 */
function wpcf7dtx_validate_value($result, $value, $tag, $type = '')
{
    $type = $type ? $type : str_replace(array('dynamic_', 'dynamic'), '', $tag->basetype);

    // Validate required fields for value
    if ($tag->is_required() && empty($value)) {
        $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        return $result;
    }

    // Validate value by type
    if (!empty($value)) {
        switch ($type) {
            case 'email':
                if (!wpcf7_is_email($value)) {
                    $result->invalidate($tag, wpcf7_get_message('dtx_invalid_email'));
                    return $result;
                }
                break;
            case 'tel':
                if (!wpcf7_is_tel($value)) {
                    $result->invalidate($tag, wpcf7_get_message('dtx_invalid_tel'));
                    return $result;
                }
                break;
            case 'number':
            case 'range':
                if (!wpcf7_is_number($value)) {
                    $result->invalidate($tag, wpcf7_get_message('dtx_invalid_number'));
                    return $result;
                }
                break;
            case 'date':
                if (!wpcf7_is_date($value)) {
                    $result->invalidate($tag, wpcf7_get_message('dtx_invalid_date'));
                    return $result;
                }
                break;
        }

        // Finish validating text-based inputs
        $maxlength = $tag->get_maxlength_option();
        $minlength = $tag->get_minlength_option();
        if ($maxlength && $minlength && $maxlength < $minlength) {
            $maxlength = $minlength = null;
        }
        $code_units = wpcf7_count_code_units($value);
        if (false !== $code_units) {
            if ($maxlength && $maxlength < $code_units) {
                $result->invalidate($tag, wpcf7_get_message('invalid_too_long'));
                return $result;
            } elseif ($minlength && $code_units < $minlength) {
                $result->invalidate($tag, wpcf7_get_message('invalid_too_short'));
                return $result;
            }
        }
    }

    return $result;
}

/**
 * Backend Mail Configuration Validation
 *
 * Validate dynamic form tags used in mail configuration.
 *
 * @since 4.0.0
 *
 * @param WPCF7_ConfigValidator
 *
 * @return void
 */
function wpcf7dtx_validate($validator)
{
    // Check for sensitive form tags
    $manager = WPCF7_FormTagsManager::get_instance();
    $contact_form = $validator->contact_form();
    $form = $contact_form->prop('form');
    if (wpcf7_autop_or_not()) {
        $form = $manager->replace_with_placeholders($form);
        $form = wpcf7_autop($form);
        $form = $manager->restore_from_placeholders($form);
    }
    $form = $manager->replace_all($form);
    $tags = $manager->get_scanned_tags();
    foreach ($tags as $tag) {
        /** @var WPCF7_FormTag $tag */

        // Only validate DTX formtags
        if (in_array($tag->basetype, array_merge(
            array('dynamictext', 'dynamichidden'), // Deprecated DTX form tags
            array_keys(wpcf7dtx_config()) // DTX form tags
        ))) {
            // Check value for sensitive data
            $default = $tag->get_option('defaultvalue', '', true);
            if (!$default) {
                $default = $tag->get_default_option(strval(reset($tag->values)));
            }
            if (
                !empty($value = trim(wpcf7_get_hangover($tag->name, $default))) && // Has value
                ($result = wpcf7dtx_validate_sensitive_value($value))['status'] // Has sensitive data
            ) {
                $validator->add_error('form.body', 'dtx_disallowed', array(
                    'message' => sprintf(
                        __('[%1$s %2$s]: Access to key "%3$s" in shortcode "%4$s" is disallowed by default. To allow access, add "%3$s" to the %5$s Allow List.', 'contact-form-7-dynamic-text-extension'),
                        esc_html($tag->basetype),
                        esc_html($tag->name),
                        esc_html($result['key']),
                        esc_html($result['shortcode']),
                        esc_html($result['shortcode'] == 'CF7_get_current_user' ? __('User Data Key', 'contact-form-7-dynamic-text-extension') : __('Meta Key', 'contact-form-7-dynamic-text-extension'))
                    ),
                    'link' => wpcf7dtx_get_admin_settings_screen_url()
                ));
            }

            // Check placeholder for sensitive data
            if (
                ($tag->has_option('placeholder') || $tag->has_option('watermark')) && // Using placeholder
                !empty($placeholder = trim(html_entity_decode(urldecode($tag->get_option('placeholder', '', true)), ENT_QUOTES))) && // Has value
                ($result = wpcf7dtx_validate_sensitive_value($placeholder))['status'] // Has sensitive data
            ) {
                $validator->add_error('form.body', 'dtx_disallowed', array(
                    'message' => sprintf(
                        __('[%1$s %2$s]: Access to key "%3$s" in shortcode "%4$s" is disallowed by default. To allow access, add "%3$s" to the %5$s Allow List.', 'contact-form-7-dynamic-text-extension'),
                        esc_html($tag->basetype),
                        esc_html($tag->name),
                        esc_html($result['key']),
                        esc_html($result['shortcode']),
                        esc_html($result['shortcode'] == 'CF7_get_current_user' ? __('User Data Key', 'contact-form-7-dynamic-text-extension') : __('Meta Key', 'contact-form-7-dynamic-text-extension'))
                    ),
                    'link' => wpcf7dtx_get_admin_settings_screen_url()
                ));
            }
        }
    }

    // Validate email address
    if (!$validator->is_valid()) {
        $contact_form = null;
        $form_tags = null;
        foreach ($validator->collect_error_messages() as $component => $errors) {
            $components = explode('.', $component);
            if (count($components) === 2 && strpos($components[0], 'mail') === 0 && in_array($components[1], array('sender', 'recipient', 'additional_headers'))) {
                foreach ($errors as $error) {
                    // Focus on email fields that flag the invalid mailbox syntax warning, have to test link because code isn't sent and message could be in any language
                    if (strpos(wpcf7dtx_array_has_key('link', $error), 'invalid-mailbox-syntax') !== false) {
                        if (is_null($contact_form)) {
                            $contact_form = $validator->contact_form();
                        }
                        if (is_null($form_tags)) {
                            $form_tags = wpcf7_scan_form_tags();
                        }
                        $raw_value = $contact_form->prop($components[0])[$components[1]];
                        foreach ($form_tags as $tag) {
                            if (!empty($tag->name)) {
                                // Check if this form tag is in the raw value
                                $form_tag = '[' . $tag->name . ']';
                                if (strpos($raw_value, $form_tag) !== false && in_array($tag->basetype, array_keys(wpcf7dtx_config()))) {
                                    $validator->remove_error($component, 'invalid_mailbox_syntax'); // Remove error, this is ours to handle now
                                    $utm_source = urlencode(home_url());
                                    if (!in_array($tag->basetype, array('dynamic_hidden', 'dynamic_email'))) {
                                        $validator->add_error($component, 'invalid_mailbox_syntax', array(
                                            'message' => __('Only email, dynamic email, hidden, or dynamic hidden form tags can be used for email addresses.', 'contact-form-7-dynamic-text-extension'),
                                            'link' => esc_url(sprintf('https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/configuration-errors/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=config-error-invalid_mailbox_syntax#valid-form-tags', $utm_source))
                                        ));
                                    } else {
                                        $dynamic_value = wpcf7dtx_get_dynamic(false, $tag); // Get the dynamic value of this tag
                                        if (empty($dynamic_value) && $tag->basetype == 'dynamic_hidden') {
                                            $validator->add_error($component, 'maybe_empty', array(
                                                'message' => __('The dynamic hidden form tag must have a default value.', 'contact-form-7-dynamic-text-extension'),
                                                'link' => esc_url(sprintf('https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/configuration-errors/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=config-error-maybe_empty#maybe-empty', $utm_source))
                                            ));
                                        } elseif (empty($dynamic_value) && !$tag->is_required()) {
                                            $validator->add_error($component, 'maybe_empty', array(
                                                'message' => __('The dynamic form tag must be required or have a default value.', 'contact-form-7-dynamic-text-extension'),
                                                'link' => esc_url(sprintf('https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/configuration-errors/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=config-error-maybe_empty#maybe-empty', $utm_source))
                                            ));
                                        } elseif (!empty($dynamic_value)) {
                                            if (!wpcf7_is_email($dynamic_value)) {
                                                $validator->add_error($component, 'invalid_mailbox_syntax', array(
                                                    'message' => __('The default dynamic value does not result in a valid email address.', 'contact-form-7-dynamic-text-extension'),
                                                    'link' => esc_url(sprintf('https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/configuration-errors/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=config-error-invalid_mailbox_syntax#invalid-email-address', $utm_source))
                                                ));
                                            } elseif ($component[1] == 'sender' && !wpcf7_is_email_in_site_domain($dynamic_value)) {
                                                $validator->add_error($component, 'email_not_in_site_domain', array(
                                                    'message' => __('The dynamic email address for the sender does not belong to the site domain.', 'contact-form-7-dynamic-text-extension'),
                                                    'link' => esc_url(sprintf('https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/configuration-errors/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=config-error-email_not_in_site_domain#invalid-site-domain', $utm_source))
                                                ));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Initialise Custom Mail Template Validator
 *
 * Validator requires a version 5.7 or higher of Contact Form 7.
 *
 * @return void
 */
function wpcf7dtx_init_validator()
{
    if (wpcf7dtx_dependencies(WPCF7DTX_MINVERSION_MAILVALIDATION)) {
        add_action('wpcf7_config_validator_validate', 'wpcf7dtx_validate');
    }
}
add_action('plugins_loaded', 'wpcf7dtx_init_validator', 30);


/**
 * Validate Field Value for Sensitive Data
 *
 * @since 5.0.0
 *
 * @see https://developer.wordpress.org/reference/functions/get_bloginfo/#description
 *
 * @param string $content The string to validate.
 *
 * @return array An associative array with keys `status` (bool), `shortcode` (string), and `key` (string).
 * The value of `status` is true if the content is a shortcode that is attempting to access sensitive data. False
 * otherwise. The value of `shortcode` is the the shortcode that is making the attempt if `status` is true. The
 * value of `key` is the shortcode's `key` attribute of the attempt being made if `status` is true.
 */
function wpcf7dtx_validate_sensitive_value($content)
{
    $r = array(
        'status' => false,
        'shortcode' => '',
        'key' => ''
    );

    // Parse the attributes.  [0] is the shortcode name. ['key'] is the key attribute
    $atts = shortcode_parse_atts($content);

    // If we can't extract the atts, or the shortcode or `key` is not an att, don't validate
    if (!is_array($atts) || !array_key_exists('key', $atts) || !array_key_exists('0', $atts)) return $r;

    // Find the key and shortcode in question
    $key = sanitize_text_field($atts['key']);
    $shortcode = sanitize_text_field($atts['0']);

    // If the shortcode or key value does not exist, don't validate
    if (empty($shortcode) || empty($key)) return $r;

    $allowed = true;
    switch ($shortcode) {
        case 'CF7_get_custom_field':
            $allowed = wpcf7dtx_post_meta_key_access_is_allowed($key);
            break;
        case 'CF7_get_current_user':
            $allowed = wpcf7dtx_user_data_access_is_allowed($key);
            break;
        default:
    }

    if (!$allowed) {
        $r['status'] = true;
        $r['shortcode'] = $shortcode;
        $r['key'] = $key;
    }

    return $r;
}
