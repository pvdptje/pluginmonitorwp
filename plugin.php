<?php
/*
Plugin Name: Plugin Activity Monitor
Description: Tracks plugin activations, installations, and deletions with email notifications.
Version: 1.0
Author: Your Name
*/

// Store initial active plugins on activation
register_activation_hook(__FILE__, 'pam_store_active_plugins');
function pam_store_active_plugins() {
    update_option('pam_initial_active_plugins', get_option('active_plugins'));
}

// Plugin activation handler
add_action('activated_plugin', 'pam_handle_plugin_activation');
function pam_handle_plugin_activation($plugin_slug) {
    $plugins = get_plugins();
    if (isset($plugins[$plugin_slug])) {
        $plugin_name = $plugins[$plugin_slug]['Name'];
        pam_send_notification('activated', $plugin_name);
    }
}

// Plugin installation handler
add_action('upgrader_process_complete', 'pam_handle_plugin_installation', 10, 2);
function pam_handle_plugin_installation($upgrader, $options) {
    if ($options['action'] === 'install' && $options['type'] === 'plugin') {
        $plugin_slug = $upgrader->plugin_info();
        $plugins = get_plugins();
        if (isset($plugins[$plugin_slug])) {
            $plugin_name = $plugins[$plugin_slug]['Name'];
            pam_send_notification('installed', $plugin_name);
        }
    }
}

// Plugin deletion handler
add_action('deleted_plugin', 'pam_handle_plugin_deletion', 10, 2);
function pam_handle_plugin_deletion($plugin_file, $deleted) {
    if ($deleted) {
        $plugin_slug = dirname($plugin_file);
        pam_send_notification('deleted', $plugin_slug);
    }
}

// Notification email sender
function pam_send_notification($action, $plugin) {
    $to = get_option('admin_email');
    $subject = "Plugin {$action}: {$plugin}";
    $message = "A plugin has been {$action} on your site:\n\n";
    $message .= "Plugin: {$plugin}\n";
    $message .= "Action: {$action}\n";
    $message .= "Site: " . get_bloginfo('name') . " (" . site_url() . ")\n";
    $message .= "Timestamp: " . current_time('mysql') . "\n";
    
    wp_mail($to, $subject, $message);
}
