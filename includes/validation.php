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
                    return $result; // Return if any are invalid
                }
            }
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
add_action('wpcf7_config_validator_validate', 'wpcf7dtx_validate');
