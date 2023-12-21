<?php
/**
 * Class CF7DTX_Plugin_Settings
 *
 * Configure the plugin settings page.
 */
class CF7DTX_Plugin_Settings {

	/**
	 * Capability required by the user to access the My Plugin menu entry.
	 *
	 * @var string $capability
	 */
	private $capability = 'manage_options';

    private $sections;
    private $fields;

	/**
	 * The Plugin Settings constructor.
	 */
	function __construct($sections, $fields) {
		add_action( 'admin_init', [$this, 'settings_init'] );
		add_action( 'admin_menu', [$this, 'options_page'] );

        $this->sections = $sections;
        $this->fields = $fields;
	}

	/**
	 * Register the settings and all fields.
	 */
	function settings_init() : void {

		// Register a new setting this page.
		register_setting( 'cf7dtx_settings', 'cf7dtx_settings' );


        foreach( $this->sections as $section_id => $section ){
            // Register a new section.
            add_settings_section(
                $section_id,
                $section['title'],
                [$this, 'render_section'],
                'cf7dtx_settings',
            );

        }


		/* Register All The Fields. */
		foreach( $this->fields as $field ) {
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
	 */
	function options_page() : void {
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
	 */
	function render_options_page() : void {

		// check user capabilities
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}


        if( isset( $_GET['scan-meta-keys'] )){

            $results = $this->scan_forms();
            // dtxpretty($results);

            ?>
            <div class="wrap">
                <h1><?php _e('Dynamic Text Extension: Form Shortcode Scan', 'contact-form-7-dynamic-text-extension'); ?></h1>

                <?php $this->render_scan_results($results); ?>

            </div>
            <?php
        }
        else{



            // add error/update messages

            // check if the user have submitted the settings
            // WordPress will add the "settings-updated" $_GET parameter to the url
            if ( isset( $_GET['settings-updated'] ) ) {
                // add settings saved message with the class of "updated"
                add_settings_error( 'cf7dtx_messages', 'cf7dtx_message', __( 'Settings Saved', 'contact-form-7-dynamic-text-extension' ), 'updated' );
            }

            
            
            // show error/update messages
            settings_errors( 'cf7dtx_messages' );
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    /* output security fields for the registered setting "cf7dtx" */
                    settings_fields( 'cf7dtx_settings' );
                    /* output setting sections and their fields */
                    /* (sections are registered for "cf7dtx", each field is registered to a specific section) */
                    do_settings_sections( 'cf7dtx_settings' );
                    /* output save settings button */
                    submit_button( 'Save Settings' );
                    ?>
                </form>

                <a href="<?php echo admin_url('admin.php?page=cf7dtx_settings&scan-meta-keys') ?>">Scan Forms for Post Meta and User Data Keys</a>
            </div>
            <?php
        }
	}

	/**
	 * Render a settings field.
	 *
	 * @param array $args Args to configure the field.
	 */
	function render_field( array $args ) : void {

		$field = $args['field'];

		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'cf7dtx_settings' );

		switch ( $field['type'] ) {

			case "text": {
				?>
				<input
					type="text"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "checkbox": {
				?>
				<input
					type="checkbox"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="1"
					<?php echo isset( $options[ $field['id'] ] ) ? ( checked( $options[ $field['id'] ], 1, false ) ) : ( '' ); ?>
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "textarea": {
				?>
				<textarea
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
                    style="width:400px; height:200px;"
				><?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?></textarea>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "select": {
				?>
				<select
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
				>
					<?php foreach( $field['options'] as $key => $option ) { ?>
						<option value="<?php echo $key; ?>" 
							<?php echo isset( $options[ $field['id'] ] ) ? ( selected( $options[ $field['id'] ], $key, false ) ) : ( '' ); ?>
						>
							<?php echo $option; ?>
						</option>
					<?php } ?>
				</select>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "password": {
				?>
				<input
					type="password"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "wysiwyg": {
				wp_editor(
					isset( $options[ $field['id'] ] ) ? $options[ $field['id'] ] : '',
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
				<input
					type="email"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "url": {
				?>
				<input
					type="url"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "color": {
				?>
				<input
					type="color"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

			case "date": {
				?>
				<input
					type="date"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="cf7dtx_settings[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html_e( $field['description'], 'cf7dtx_settings' ); ?>
				</p>
				<?php
				break;
			}

		}
	}


	/**
	 * Render a section on a page, with an ID and a text label.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     An array of parameters for the section.
	 *
	 *     @type string $id The ID of the section.
	 * }
	 */
	function render_section( array $args ) : void {
        // return;
        // echo '<pre>';print_r($args);echo '</pre>';

        ?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php echo $this->sections[$args['id']]['description']; ?></p>
		<?php

	}

    function scan_forms(){
        
        $found = [];

        if( function_exists('wpcf7_contact_form') ){
            $forms = get_posts([
                'post_type' => 'wpcf7_contact_form',
            ]);
            
            // Loop through forms
            foreach( $forms as $form ){
    
                // Search for the custom fields shortcode -- //TODO and user
                if( str_contains($form->post_content, 'CF7_get_custom_field') 
                    // || str_contains...
                ){
    
                    $found[$form->ID] = [];
                    
                    $cf7 = wpcf7_contact_form( $form->ID );
                    $tags = $cf7->scan_form_tags();
                    
                    // Check each tag
                    foreach( $tags as $tag ){
                        // Find dynamic tags
                        if( str_starts_with( $tag->type, 'dynamic' ) ){
                            // Check each value
                            foreach( $tag->values as $val ){
                                // Find CF7_get_custom_field
                                if( str_starts_with( $val, 'CF7_get_custom_field' )){
                                    // Parse out the shortcode atts
                                    $atts = shortcode_parse_atts($val);
                                    if( $atts ){
                                        // Grab the meta key
                                        $meta_key = $atts['meta_key'];
    
                                        // TODO check allowlist?
    
                                        // Add meta key to the list
                                        if( $meta_key ) $found[$form->ID][] = $meta_key;
                                    }
                                }
                                //TODO Find user as well
                                //if( str_starts_with( $val, 'CF7_get_user...' )){
                            }
                        }
                    }
                }
            }
        }
        // dtxpretty( $found );
        return $found;

    }

    function render_scan_results( $results ){
        foreach( $results as $form_id => $r ){
            ?>
                <div>
                    Form <?php echo $form_id; ?> Keys: <?php echo implode(',',$r); ?>
                </div>
            <?php
        }
    }

}



$sections = [
    'post_meta_access' => [
        'title' => __('Post Meta Access', 'contact-form-7-dynamic-text-extension'),
        'description' => __('Control which post metadata the CF7 DTX shortcodes (CF7_get_custom_field) can access.  By default, all metadata is protected, so you can open up access through these settings.'),
    ],
    'user_data_access' => [
        'title' => __('User Data Access', 'contact-form-7-dynamic-text-extension'),
        'description' => __('Control which user data the CF7 DTX shortcodes (CF7_get_current_user) can access.  By default, all user data is protected, so you can open up access through these settings.'),
    ],
];

/**
 * Array of fields that should be displayed in the settings page.
 *
 * @var array $fields
 */
$fields = [
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
            'disabled' => __( 'Disabled - Only Allow Access to Meta Key Allow List', 'contact-form-7-dynamic-text-extension' ),
            'enabled' => __( 'Enabled - Allow Access to All Post Metadata', 'contact-form-7-dynamic-text-extension' ),
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
            'disabled' => __( 'Disabled - Only Allow Access to User Data Key Allow List', 'contact-form-7-dynamic-text-extension' ),
            'enabled' => __( 'Enabled - Allow Access to User Data', 'contact-form-7-dynamic-text-extension' ),
        ],
        'section' => 'user_data_access',
    ],
];


new CF7DTX_Plugin_Settings($sections, $fields);

