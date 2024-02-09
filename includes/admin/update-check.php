<?php

/**
 * Check for Updates
 *
 * Hooked to `plugins_loaded` to compare source code version with database version.
 *
 * @since 4.2.0
 *
 * @return void
 */
function wpcf7dtx_update_check()
{
    if (WPCF7DTX_VERSION !== get_option('cf7dtx_version', '')) {

        // Update the database version with the current plugin version
        update_option('cf7dtx_version', WPCF7DTX_VERSION);

        // Run the update handler
        add_action('admin_init', 'wpcf7dtx_update');
    }
}
add_action('plugins_loaded', 'wpcf7dtx_update_check');

/**
 * Maybe Update DTX
 *
 * Optionally hooked to `admin_init` when source code version is newer than database version.
 *
 * @since 4.2.0
 *
 * @return void
 */
function wpcf7dtx_update()
{

    // v4.2.0 will scan for meta and user keys that should be allow-listed and display an admin alert
    wpcf7dtx_v4_2_0_access_scan_check();

    // Future update processes would go here

}

/**
 * DTX Form Scan
 *
 * Scan for meta and user keys that should be allowlisted and display an admin alert.
 *
 * @since 4.2.0
 *
 * @return void
 */
function wpcf7dtx_v4_2_0_access_scan_check()
{

    $op = 'cf7dtx_v4_2_0_access_scan_check_status';
    $status = get_option($op, '');

    // Status values:
    // intervention_required - show a notice to the admin
    // intervention_not_required - we can ignore
    // intervention_completed - no need to show notice any longer
    // notice_dismissed - alert was dismissed by user

    // If we've never checked before
    if ($status === '') {
        // Run a scan - 20 by default.  If they have more than 20 forms, we'll alert regardless.
        // For less than 20 forms, we'll only alert if we detect an issue
        $num_to_scan = 20;
        $r = wpcf7dtx_scan_forms_for_access_keys($num_to_scan);
        $found = count($r['forms']);
        $scanned = $r['forms_scanned'];

        // If keys were found, or if we scanned the max number (so there are likely more to be scanned)
        if ($found || $scanned === $num_to_scan) {
            // We'll show a notice to the user
            $status = 'intervention_required';
        } else {
            // No keys need to be allow-listed, no need to show the user a list
            $status = 'intervention_not_required';
        }
        wpcf7dtx_set_update_access_scan_check_status($status);
    }
}

/**
 * DTX Admin Notice
 *
 * Display an admin notice if there are unresolved issues with accessing disallowed keys via DTX shortcodes
 *
 * @since 4.2.0
 *
 * @return void
 */
function wpcf7dtx_access_keys_notice()
{

    // Don't show this notice on the Scan Results screen to avoid confusion
    if (isset($_GET['page']) && $_GET['page'] === 'cf7dtx_settings' && (isset($_GET['scan-meta-keys']) || isset($_GET['dismiss-access-keys-notice']))) return;

    // If this user is not an administrator, don't do anything.  Only admins should see this.
    $user = wp_get_current_user();
    if (!in_array('administrator', (array) $user->roles)) return;

    // If the status doesn't require intervention, don't do anything
    $status = get_option('cf7dtx_v4_2_0_access_scan_check_status', '');
    if ($status !== 'intervention_required') {
        return;
    }
?>
    <div class="notice notice-error">
        <p>
            <?php _e('CF7 DTX: Shortcode data access requires allow-listing.', 'contact-form-7-dynamic-text-extension'); ?>
            <a href="<?php echo wpcf7dtx_get_admin_settings_screen_url(); ?>"><?php _e('Edit Settings', 'contact-form-7-dynamic-text-extension'); ?></a>
            |
            <a href="<?php echo wpcf7dtx_get_admin_scan_screen_url(); ?>"><?php _e('Scan &amp; Resolve', 'contact-form-7-dynamic-text-extension'); ?></a>
            |
            <a href="<?php echo WPCF7DTX_DATA_ACCESS_KB_URL; ?>" target="_blank"><?php _e('More Information', 'contact-form-7-dynamic-text-extension'); ?></a>
            <?php if (isset($_GET['page']) && $_GET['page'] === 'cf7dtx_settings') : ?>
                | <a href="<?php echo admin_url('admin.php?page=cf7dtx_settings&dismiss-access-keys-notice'); ?>"><?php _e('Dismiss', 'contact-form-7-dynamic-text-extension'); ?></a>
            <?php endif; ?>
        </p>
    </div>
<?php
}
add_action('admin_notices', 'wpcf7dtx_access_keys_notice');

/**
 * Set Scan Status
 *
 * @since 4.2.0
 *
 * @param string $status
 *
 * @return void
 */
function wpcf7dtx_set_update_access_scan_check_status($status)
{
    update_option('cf7dtx_v4_2_0_access_scan_check_status', $status);
}
