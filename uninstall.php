<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
delete_option( 'wphlb_was_activated' );
delete_option( 'wphlb_limit_login' );
delete_option( 'wphlb_show_logo' );
delete_option( 'wphlb_blacklisted_ips' );
?>