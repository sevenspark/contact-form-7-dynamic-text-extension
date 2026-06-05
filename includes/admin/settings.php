<?php

/**
 * Class CF7DTX_Plugin_Settings
 *
 * Configure the plugin settings page.
 */
class CF7DTX_Plugin_Settings
{

    /**
     * Capability required by the user to access the My Plugin menu entry.
     *
     * @var string $capability
     */
    private $capability = 'manage_options';

    private $_sections;

    private $_fields;

    private $num_forms_to_scan = 20;

    /**
     * The Plugin Settings constructor.
     */
    function __construct()
    {
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_menu', [$this, 'options_page']);
    }

    /**
     * Array of fields that should be displayed in the settings page.
     *
     * @var array $fields
     */
    private function fields()
    {
        if (!is_array($this->_fields)) {
            $this->_fields = [
                [
                    'id' => 'post_meta_allow_keys',
                    'label' => __('Meta Key Allow List', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('Allow access to these specific post metadata keys.  Enter one per line.', 'contact-form-7-dynamic-text-extension'),
                    'type' => 'textarea',
                    'section' => 'post_meta_access',
                ],
                [
                    'id' => 'post_meta_allow_all',
                    'label' => __('Allow Access to All Post Metadata', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('**Use with caution.**  Should only be enabled if all authorized users with editor privileges (Contributor+) are trusted and should have access to this data.  All metadata from any post (including custom post types) will be accessible via the CF7_get_custom_field shortcode.  If in doubt, use the Allow List to allow only specific keys.', 'contact-form-7-dynamic-text-extension'),
                    'type' => 'select',
                    'options' => [
                        'disabled' => __('Disabled - Only Allow Access to Meta Key Allow List', 'contact-form-7-dynamic-text-extension'),
                        'enabled' => __('Enabled - Allow Access to All Post Metadata', 'contact-form-7-dynamic-text-extension'),
                    ],
                    'section' => 'post_meta_access',
                ],
                [
                    'id' => 'user_data_allow_keys',
                    'label' => __('User Data Key Allow List', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('Allow access to these specific user data keys.   Enter one per line.', 'contact-form-7-dynamic-text-extension'),
                    'type' => 'textarea',
                    'section' => 'user_data_access',
                ],
                [
                    'id' => 'user_data_allow_all',
                    'label' => __('Allow Access to All User Data', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('**Use with caution.**  Should only be enabled if all authorized users with editor privileges (Contributor+) are trusted and should have access to this data.  All of the current user\'s data fields will be accessible via the CF7_get_current_user shortcode.  If in doubt, use the Allow List to allow only specific keys.', 'contact-form-7-dynamic-text-extension'),
                    'type' => 'select',
                    'options' => [
                        'disabled' => __('Disabled - Only Allow Access to User Data Key Allow List', 'contact-form-7-dynamic-text-extension'),
                        'enabled' => __('Enabled - Allow Access to User Data', 'contact-form-7-dynamic-text-extension'),
                    ],
                    'section' => 'user_data_access',
                ],
            ];
        }
        return $this->_fields;
    }

    private function sections()
    {
        if (!is_array($this->_sections)) {
            $this->_sections = [
                'post_meta_access' => [
                    'title' => __('Post Meta Access', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('Control which post metadata the CF7 DTX shortcodes (CF7_get_custom_field) can access.  By default, all metadata is protected, so you can open up access through these settings.  Keep in mind that users with Contributor+ credentials can add shortcodes and therefore access this data, so make sure not to expose anything sensitive.', 'contact-form-7-dynamic-text-extension') .
                        ' <a href="' . WPCF7DTX_DATA_ACCESS_KB_URL . '" target="_blank">' . __('More Information', 'contact-form-7-dynamic-text-extension') . '</a>',
                ],
                'user_data_access' => [
                    'title' => __('User Data Access', 'contact-form-7-dynamic-text-extension'),
                    'description' => __('Control which user data the CF7 DTX shortcodes (CF7_get_current_user) can access.  By default, all user data is protected, so you can open up access through these settings.  Keep in mind that users with Contributor+ credentials can add shortcodes and therefore access this data, so make sure not to expose anything sensitive.', 'contact-form-7-dynamic-text-extension') .
                        ' <a href="' . WPCF7DTX_DATA_ACCESS_KB_URL . '" target="_blank">' . __('More Information', 'contact-form-7-dynamic-text-extension') . '</a>',
                ],
            ];
        }
        return $this->_sections;
    }


    /**
     * Register the settings and all fields.
     *
     * @since 4.2.0
     *
     * @return void
     */
    function settings_init(): void
    {

        // Register a new setting this page.
        register_setting('cf7dtx_settings', 'cf7dtx_settings');

        foreach ($this->sections() as $section_id => $section) {
            // Register a new section.
            add_settings_section(
                $section_id,
                $section['title'],
                [$this, 'render_section'],
                'cf7dtx_settings',
            );
        }


        /* Register All The Fields. */
        foreach ($this->fields() as $field) {
            // Register a new field in the main section.
            add_settings_field(
                $field['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
                $field['label'], /* Label for the field. */
                [$this, 'render_field'], /* The name of the callback function. */
                'cf7dtx_settings', /* The menu page on which to display this field. */
                $field['section'], /* The section of the settings page in which to show the box. */
                [
                    'label_for' => $field['id'], /* The ID of the field. */
                    'class' => 'cf7dtx_row', /* The class of the field. */
                    'field' => $field, /* Custom data for the field. */
                ]
            );
        }
    }

    /**
     * Add a subpage to the WordPress Settings menu.
     *
     * @since 4.2.0
     *
     * @return void
     */
    function options_page(): void
    {
        add_submenu_page(
            'wpcf7', /* Parent Menu Slug */
            'Contact Form 7 - Dynamic Text Extension', /* Page Title */
            'Dynamic Text Extension', /* Menu Title */
            $this->capability, /* Capability */
            'cf7dtx_settings', /* Menu Slug */
            [$this, 'render_options_page'], /* Callback */
        );
    }

    /**
     * Render the settings page.
     *
     * @since 4.2.0
     *
     * @return void
     */
    function render_options_page(): void
    {

        // check user capabilities
        if (!current_user_can($this->capability)) {
            return;
        }

        if (isset($_GET['dismiss-access-keys-notice'])) {
            wpcf7dtx_set_update_access_scan_check_status('notice_dismissed');
?>
            <div class="notice notice-success dtx-notice">
                <p><?php _e('Notice Dismissed.  You can run the scan any time from the CF7 DTX settings page', 'contact-form-7-dynamic-text-extension'); ?></p>
                <p><?php $this->render_back_to_settings_button(); ?></p>
            </div>
            <?php
            return;
        }

        /**
         * Perform Scan
         */
        if (array_key_exists('scan-meta-keys', $_GET)) {

            // Form submission
            if (array_key_exists('save-allows', $_POST)) {
                // Verify options nonce
                if (!wp_verify_nonce(trim(sanitize_text_field(wpcf7dtx_array_has_key('_wpnonce', $_POST))), 'cf7dtx_settings-options')) {
                    echo wp_kses_post(sprintf(
                        '<div class="notice notice-error dtx-notice"><p><strong>%s</strong></p><p>%s</p></div>',
                        esc_html__('Error saving allowlist.', 'contact-form-7-dynamic-text-extension'),
                        esc_html__('Please try again. If this continues, contact support.', 'contact-form-7-dynamic-text-extension')
                    ));
                    return; // Failed nonce challenge
                }
                $r = $this->handle_save_allows();
            ?>
                <div class="wrap">
                    <h1><?php _e('DTX: Keys Added To Allow List', 'contact-form-7-dynamic-text-extension'); ?></h1>

                    <?php $this->render_allow_keys_submission($r); ?>

                </div>
            <?php
            } else {
                // URL query
                // Verify scan nonce
                if (!wp_verify_nonce(trim(sanitize_text_field(wpcf7dtx_array_has_key('_wpnonce', $_GET))), 'dtx-scan')) {
                    echo wp_kses_post(sprintf(
                        '<div class="notice notice-error dtx-notice"><p><strong>%s</strong></p><p>%s</p></div>',
                        esc_html__('An unexpected error occurred.', 'contact-form-7-dynamic-text-extension'),
                        esc_html__('Please try again. If this continues, contact support.', 'contact-form-7-dynamic-text-extension'),
                    ));
                    return; // Failed nonce challenge
                }

                $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
                $results = wpcf7dtx_scan_forms_for_access_keys($this->num_forms_to_scan, $offset);

            ?>
                <div class="wrap">
                    <h1><?php _e('DTX: Form Shortcode Scan Results', 'contact-form-7-dynamic-text-extension'); ?></h1>

                    <?php $this->render_scan_results($results); ?>

                </div>
            <?php
            }
        } else {

            // add error/update messages

            // check if the user have submitted the settings
            // WordPress will add the "settings-updated" $_GET parameter to the url
            if (isset($_GET['settings-updated'])) {
                // add settings saved message with the class of "updated"
                add_settings_error('cf7dtx_messages', 'cf7dtx_message', __('Settings Saved', 'contact-form-7-dynamic-text-extension'), 'updated');
            }

            // show error/update messages
            settings_errors('cf7dtx_messages');
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    /* output security fields for the registered setting "cf7dtx" */
                    settings_fields('cf7dtx_settings');
                    /* output setting sections and their fields */
                    /* (sections are registered for "cf7dtx", each field is registered to a specific section) */
                    do_settings_sections('cf7dtx_settings');
                    /* output save settings button */
                    submit_button(__( 'Save Settings', 'contact-form-7-dynamic-text-extension' ));
                    ?>
                </form>

                <a href="<?php echo wpcf7dtx_get_admin_scan_screen_url(); ?>"><?php esc_html_e( 'Scan Forms for Post Meta and User Data Keys', 'contact-form-7-dynamic-text-extension' ); ?></a>
            </div>
            <?php
        }
    }

    /**
     * Render a settings field.
     *
     * @since 4.2.0
     *
     * @return void
     *
     * @param array $args Args to configure the field.
     */
    function render_field(array $args): void
    {

        $field = $args['field'];

        // Get the value of the setting we've registered with register_setting()
        $options = get_option('cf7dtx_settings');

        switch ($field['type']) {

            case "text": {
            ?>
                    <input type="text" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "checkbox": {
                ?>
                    <input type="checkbox" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="1" <?php echo isset($options[$field['id']]) ? (checked($options[$field['id']], 1, false)) : (''); ?>>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "textarea": {
                ?>
                    <textarea id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" style="width:400px; height:200px;"><?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?></textarea>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "select": {
                ?>
                    <select id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]">
                        <?php foreach ($field['options'] as $key => $option) { ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($options[$field['id']]) ? (selected($options[$field['id']], $key, false)) : (''); ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "password": {
                ?>
                    <input type="password" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "wysiwyg": {
                    wp_editor(
                        isset($options[$field['id']]) ? $options[$field['id']] : '',
                        $field['id'],
                        array(
                            'textarea_name' => 'cf7dtx_settings[' . $field['id'] . ']',
                            'textarea_rows' => 5,
                        )
                    );
                    break;
                }

            case "email": {
                ?>
                    <input type="email" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "url": {
                ?>
                    <input type="url" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "color": {
                ?>
                    <input type="color" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
                <?php
                    break;
                }

            case "date": {
                ?>
                    <input type="date" id="<?php echo esc_attr($field['id']); ?>" name="cf7dtx_settings[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'cf7dtx_settings'); ?>
                    </p>
        <?php
                    break;
                }
        }
    }


    /**
     * Render a section on a page, with an ID and a text label.
     *
     * @since 4.2.0
     *
     * @param array $args {
     *     An array of parameters for the section.
     *
     *     @type string $id The ID of the section.
     * }
     *
     * @return void
     */
    function render_section(array $args): void
    {
        echo sprintf(
            '<p id="%s">%s</p>',
            esc_attr($args['id']),
            wp_kses_data($this->sections()[$args['id']]['description'])
        );
    }

    /**
     * Render Scan Results
     *
     * @since 4.2.0
     *
     * @param array $results The results array
     *
     * @return void
     */
    function render_scan_results($results)
    {
        // No forms are using the shortcodes in question
        if (!count($results['forms'])) {

            wpcf7dtx_set_update_access_scan_check_status('intervention_not_required');

            echo '<div class="notice notice-success dtx-notice"><p>' . __('Scan complete. No keys detected.', 'contact-form-7-dynamic-text-extension') . '</p></div>';
            $this->render_back_to_settings_button();
            return;
        }

        // Check if we need to scan another batch
        if ($results['forms_scanned'] === $this->num_forms_to_scan) {
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $next_offset = $offset + $this->num_forms_to_scan;
            echo '<div class="notice notice-warning dtx-notice"><p>';
            echo sprintf(
                __('%1$s forms scanned.  There may be more forms to scan.', 'contact-form-7-dynamic-text-extension'),
                $results['forms_scanned'],
            );
            echo ' ';
            echo '<a href="' . wpcf7dtx_get_admin_scan_screen_url($next_offset) . '">' . sprintf(
                __('Scan %1$s more forms', 'contact-form-7-dynamic-text-extension'),
                $this->num_forms_to_scan
            ) . '</a>';
            echo '</p></div>';
        }

        $settings = wpcf7dtx_get_settings();
        $already_allowed_meta_keys = wpcf7dtx_parse_allowed_keys(wpcf7dtx_array_has_key('post_meta_allow_keys', $settings));
        $already_allowed_user_keys = wpcf7dtx_parse_allowed_keys(wpcf7dtx_array_has_key('user_data_allow_keys', $settings));

        // Check the results ahead of time to see if all of the keys are already in the allow list - if so, nothing to do
        $forms = $results['forms'];
        $all_keys_allowed = true;
        foreach ($forms as $form_id => $r) {
            if (count($r['meta_keys'])) {
                foreach ($r['meta_keys'] as $key) {
                    if (!in_array($key, $already_allowed_meta_keys)) {
                        $all_keys_allowed = false;
                        break;
                    }
                }
                if ($all_keys_allowed === false) break;
            }
            if (count($r['user_keys'])) {
                foreach ($r['user_keys'] as $key) {
                    if (!in_array($key, $already_allowed_user_keys)) {
                        $all_keys_allowed = false;
                        break;
                    }
                }
                if ($all_keys_allowed === false) break;
            }
        }

        if ($all_keys_allowed) {
            wpcf7dtx_set_update_access_scan_check_status('intervention_completed');
        }  ?>
        <style>
            .postbox,
            .dtx-notice {
                max-width: 600px;
                box-sizing: border-box;
            }

            .postbox-header {
                padding: 1em;
            }

            .postbox-header h2 {
                font-size: 14px;
                margin: 0;
            }

            .key-disabled {
                opacity: .8;
            }
        </style>
        <div>

            <?php if ($all_keys_allowed) : ?>
                <div class="notice notice-success dtx-notice">
                    <p><?php
                        echo sprintf(
                            __('Scan of %1$s forms complete. All keys detected are already on allow list.  No action necessary for these forms.', 'contact-form-7-dynamic-text-extension'),
                            $results['forms_scanned'],
                        ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error dtx-notice" style="width:600px; box-sizing:border-box;">
                    <p><strong><?php _e('Shortcodes accessing potentially sensitive Post Meta or User Data were detected in the forms listed below.', 'contact-form-7-dynamic-text-extension'); ?></strong></p>
                    <p><?php _e('Only keys on the allow list will return their value when accessed.  Attempting to access keys that are not on the allow list via DTX shortcodes will return an empty string and throw a warning message.', 'contact-form-7-dynamic-text-extension'); ?></p>
                    <p><?php _e('Review the keys below and confirm that you want to allow access, then select meta and/or user keys to add them to the relevant allow list.  Any keys for sensitive data should be removed by editing your contact form.', 'contact-form-7-dynamic-text-extension'); ?></p>
                    <p><?php _e('Note that keys which are already in the allow list are displayed but marked as already selected.', 'contact-form-7-dynamic-text-extension'); ?></p>
                    <p><a href="<?php echo WPCF7DTX_DATA_ACCESS_KB_URL; ?>" target="_blank"><?php _e('More Information', 'contact-form-7-dynamic-text-extension'); ?></a></p>
                </div>
            <?php endif; ?>

            <form action="admin.php?page=cf7dtx_settings&scan-meta-keys" method="post">

                <?php

                settings_fields('cf7dtx_settings');

                foreach ($results['forms'] as $form_id => $r) {
                ?>
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2><?php echo $r['title']; ?></h2>
                            <a href="<?php echo $r['admin_url']; ?>" target="_blank">View form</a>
                        </div>
                        <div class="inside">
                            <?php if (count($r['meta_keys'])) : ?>
                                <h4>Meta Keys</h3>

                                    <div>
                                        <?php foreach ($r['meta_keys'] as $key) {
                                            $already_allowed = in_array($key, $already_allowed_meta_keys);
                                            $name = "dtx_meta_key/$key";
                                        ?>
                                            <div>
                                                <label <?php if ($already_allowed) echo 'class="key-disabled" title="Already in Allow List"'; ?>>
                                                    <input name="<?php echo $name; ?>" id="<?php echo $name; ?>" type="checkbox" value="1" <?php if ($already_allowed) echo 'checked="checked" disabled'; ?> />
                                                    <?php echo $key; ?>
                                                </label>
                                            </div>
                                        <?php
                                        }
                                        ?>

                                    </div>
                                <?php endif; ?>

                                <?php if (count($r['user_keys'])) : ?>
                                    <h4>User Data Keys</h3>
                                        <div>
                                            <?php foreach ($r['user_keys'] as $key) {
                                                $name = "dtx_user_key/$key";
                                                $already_allowed = in_array($key, $already_allowed_user_keys);
                                            ?>
                                                <div>
                                                    <label <?php if ($already_allowed) echo 'class="key-disabled" title="Already in Allow List"'; ?>>
                                                        <input name="<?php echo $name; ?>" id="<?php echo $name; ?>" type="checkbox" value="1" <?php if ($already_allowed) echo 'checked="checked" disabled'; ?> />
                                                        <?php echo $key; ?>
                                                    </label>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                        </div>
                    </div>
                <?php
                }
                ?>

                <?php if (!$all_keys_allowed) submit_button(__('Add Selected Keys to Allow Lists', 'contact-form-7-dynamic-text-extension'), 'primary', 'save-allows'); ?>
            </form>
            <?php $this->render_back_to_settings_button(); ?>
        </div>
    <?php
    }

    /**
     * Handle Save Allows
     *
     * @since 4.2.0
     *
     * @return array Save results.
     */
    private function handle_save_allows()
    {
        $user_keys = [];
        $meta_keys = [];

        // Find saved keys
        foreach ($_POST as $key => $val) {
            if (str_starts_with($key, 'dtx_meta_key')) {
                $parts = explode('/', $key);
                $meta_keys[] = $parts[1];
            } else if (str_starts_with($key, 'dtx_user_key')) {
                $parts = explode('/', $key);
                $user_keys[] = $parts[1];
            }
        }

        // Add those keys in options
        $settings = wpcf7dtx_get_settings();

        // Meta Data
        if (count($meta_keys)) {
            // Get already saved values
            $post_meta_allow_keys = isset($settings['post_meta_allow_keys']) ? wpcf7dtx_parse_allowed_keys($settings['post_meta_allow_keys']) : [];
            // Merge with new values
            $new = array_unique(array_merge($post_meta_allow_keys, $meta_keys));
            $settings['post_meta_allow_keys'] = implode(PHP_EOL, $new);
        }


        // User Data
        if (count($user_keys)) {
            // Get already saved values
            $user_data_allow_keys = isset($settings['user_data_allow_keys']) ? wpcf7dtx_parse_allowed_keys($settings['user_data_allow_keys']) : [];
            // Merge with new values
            $new = array_unique(array_merge($user_data_allow_keys, $user_keys));
            $settings['user_data_allow_keys'] = implode(PHP_EOL, $new);
        }

        // Update with new settings
        wpcf7dtx_update_settings($settings);

        // Mark as intervention complete
        wpcf7dtx_set_update_access_scan_check_status('intervention_completed');

        return [
            'user' => $user_keys,
            'meta' => $meta_keys,
        ];
    }
    /**
     * Render Allow Keys Submission
     *
     * @since 4.2.0
     *
     * @param array $results The results array
     *
     * @return void
     */
    private function render_allow_keys_submission($r)
    {

    ?>
        <?php if (count($r['meta'])) : ?>
            <p><?php _e('Meta Keys Added', 'contact-form-7-dynamic-text-extension'); ?>: <?php echo implode(', ', $r['meta']); ?></p>
        <?php endif; ?>
        <?php if (count($r['user'])) : ?>
            <p><?php _e('User Data Keys Added', 'contact-form-7-dynamic-text-extension'); ?>: <?php echo implode(', ', $r['user']); ?></p>
        <?php endif; ?>

        <?php if (!count($r['meta']) && !count($r['user'])) : ?>
            <p><?php _e('No Keys Selected', 'contact-form-7-dynamic-text-extension'); ?></p>
        <?php endif; ?>

    <?php
        $this->render_back_to_settings_button();
    }

    /**
     * Render Back to Settings Button
     *
     * @since 4.2.0
     *
     * @return void
     */
    function render_back_to_settings_button()
    {
    ?>
        <a href="<?php echo wpcf7dtx_get_admin_settings_screen_url(); ?>">&laquo; <?php _e('Back to Settings', 'contact-form-7-dynamic-text-extension'); ?></a>
<?php
    }
}

new CF7DTX_Plugin_Settings();

/**
 * Get URL to Admin Scan Screen
 *
 * @param int $offset Optional.
 */
function wpcf7dtx_get_admin_scan_screen_url($offset = 0)
{
    $path = 'admin.php?page=cf7dtx_settings&scan-meta-keys';
    if ($offset) {
        $path .= '&offset=' . $offset;
    }
    return wp_nonce_url(admin_url($path), 'dtx-scan');
}
function wpcf7dtx_get_admin_settings_screen_url()
{
    return admin_url('admin.php?page=cf7dtx_settings');
}


/**
 * Search all CF7 forms for
 */
function wpcf7dtx_scan_forms_for_access_keys($num, $offset = 0)
{

    $found = [
        'forms' => [],
    ];
    $forms = [];

    if (function_exists('wpcf7_contact_form')) {

        $cf7forms = get_posts([
            'post_type' => 'wpcf7_contact_form',
            // 'numberposts' => $numposts, // sanity check
            'posts_per_page' => $num,
            'offset' => $offset,
        ]);

        $found['forms_scanned'] = count($cf7forms);

        // Loop through forms
        foreach ($cf7forms as $form) {

            // Search for the custom fields shortcode
            if (
                strpos($form->post_content, 'CF7_get_custom_field') !== false ||
                strpos($form->post_content, 'CF7_get_current_user') !== false
            ) {
                $cf7 = wpcf7_contact_form($form->ID);

                $forms[$form->ID] = [
                    'title' => $cf7->title(),
                    'meta_keys' => [],
                    'user_keys' => [],
                    'admin_url' => admin_url("admin.php?page=wpcf7&post={$form->ID}&action=edit"),
                ];

                $tags = $cf7->scan_form_tags();

                // Check each tag
                foreach ($tags as $tag) {
                    // Find dynamic tags
                    if (str_starts_with($tag->type, 'dynamic')) {
                        // Check each value
                        foreach ($tag->values as $val) {
                            // Find CF7_get_custom_field
                            if (str_starts_with($val, 'CF7_get_custom_field')) {
                                // Parse out the shortcode atts
                                $atts = shortcode_parse_atts($val);
                                if ($atts) {
                                    // Grab the meta key
                                    $meta_key = $atts['key'];

                                    // Add meta key to the list
                                    if ($meta_key) {
                                        $forms[$form->ID]['meta_keys'][] = $meta_key;
                                    }
                                }
                            }
                            // Find CF7_get_current_user
                            if (str_starts_with($val, 'CF7_get_current_user')) {
                                // Parse out the shortcode atts
                                $atts = shortcode_parse_atts($val);
                                if ($atts) {
                                    // Grab user data key
                                    $key = $atts['key'];
                                    if ($key) {
                                        $forms[$form->ID]['user_keys'][] = $key;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $found['forms'] = $forms;
    return $found;
}
