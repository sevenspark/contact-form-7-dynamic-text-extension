<?php 


add_action( 'plugins_loaded', 'wpcf7dtx_update_check' );
function wpcf7dtx_update_check(){
    if( WPCF7DTX_VERSION !== get_option( 'cf7dtx_version', '' ) ){

        //TODO: Uncomment
        update_option( 'cf7dtx_version', WPCF7DTX_VERSION );

        // Run the update handler
        add_action('admin_init', 'wpcf7dtx_update');
    }
}
function wpcf7dtx_update(){

    wpcf7dtx_v4_2_0_access_scan_check();

}



/*** 4.2.0 - Security Access ***/
function wpcf7dtx_v4_2_0_access_scan_check(){

    $op = 'cf7dtx_v4_2_0_access_scan_check_status';
    $status = get_option( $op, '' );

    // intervention_required - show a notice to the admin
    // intervention_not_required - we can ignore
    // intervention_completed - no need to show notice any longer
    // notice_dismissed - alert was dismissed

    // If we've never checked before
    if( $status === '' ){
        // Run a scan
        $r = wpcf7dtx_scan_forms_for_access_keys();
        $found = count($r['forms']);
        $scanned = $r['forms_scanned'];

        // If keys were found
        if( $found || $scanned === 20 ){
            // We'll show a notice to the user
            $status = 'intervention_required';
        }
        else{
            $status = 'intervention_not_required';            
        }
        wpcf7dtx_set_update_access_scan_check_status( $status );
    }
}

add_action('admin_notices', 'wpcf7dtx_access_keys_notice');
function wpcf7dtx_access_keys_notice(){

    // Don't show on the Scan Results screen to avoid confusion
    if( isset($_GET['page']) && $_GET['page'] === 'cf7dtx_settings' && ( isset( $_GET['scan-meta-keys']) || isset($_GET['dismiss-access-keys-notice']))) return;

    // If this user is not an administrator, don't do anything
    $user = wp_get_current_user();
    if ( !in_array( 'administrator', (array) $user->roles ) ) return;

    // If the status doesn't require intervention, don't do anything
    $status = get_option( 'cf7dtx_v4_2_0_access_scan_check_status', '' );
    if( $status !== 'intervention_required' ){
        return;
    }
    ?>
    <div class="notice notice-error">
		<p>
            <?php _e('CF7 DTX: Shortcode data access requires allow-listing.', 'contact-form-7-dynamic-text-extension'); ?>
            <a href="<?php echo wpcf7dtx_get_admin_settings_screen_url(); ?>"><?php _e('Edit Settings', 'contact-form-7-dynamic-text-extension' ); ?></a>
            |
            <a href="<?php echo wpcf7dtx_get_admin_scan_screen_url(); ?>"><?php _e('Scan &amp; Resolve', 'contact-form-7-dynamic-text-extension' ); ?></a>
            |
            <a href=""><?php _e('More Information', 'contact-form-7-dynamic-text-extension' ); ?></a>
            <?php if( isset($_GET['page']) && $_GET['page'] === 'cf7dtx_settings' ): ?>
            | <a href="<?php echo admin_url('admin.php?page=cf7dtx_settings&dismiss-access-keys-notice'); ?>"><?php _e('Dismiss', 'contact-form-7-dynamic-text-extension' ); ?></a>
            <?php endif; ?>
        </p>
	</div>
    <?php

}
function wpcf7dtx_set_update_access_scan_check_status( $status ){
    update_option( 'cf7dtx_v4_2_0_access_scan_check_status', $status );
}