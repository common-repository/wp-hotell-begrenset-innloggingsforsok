<?php
/**
 * Plugin Name: 			WP Hotell Security
 * Description: 			A small and simple plugin that limits number of login attempts to protect your password.
 * Version: 				0.0.1
 * Author: 					WP Hotell United Works
 * Author URI: 				https://wphotell.unitedworks.no/
 * Requires: 				4.6 or higher
 * License: 				GPLv3 or later
 * License URI:       		http://www.gnu.org/licenses/gpl-3.0.html
 * Requires PHP: 			5.6
 * Tested up to:            7.0
 * Text Domain:				wphlb	
 * Domain Path:				/languages
 *
 * Copyright 2019 			WP Hotell Unted Works 		post@wphotell.no
 *
 * Original Plugin URI: 	https://wphotell.unitedworks.no/beste-wordpress-hosting/
 * Original Author URI: 	https://wphotell.unitedworks.no
 *
 * NOTAT:
 * WP Hotell er en optimalisert hostingplattform som lar deg bygge WordPress nettsteder – ikke teknikken som driver det.
 *
 * Dette programmet er gratis programvare; Du kan distribuere den og / eller endre
 * den under betingelsene i GNU General Public License, versjon 3, som
 * utgitt av Free Software Foundation.
 *
 * Dette programmet er distribuert i håp om at det vil være nyttig,
 * men UTEN NOEN GARANTI; uten engang den underforståtte garantien fra
 * SALGSMIDLER eller egnethet til en bestemt hensikt. Se
 * GNU General Public License for mer detaljer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}




register_activation_hook( __FILE__, 'wphlb_activation' );

function wphlb_activation(){
	if ( !get_option( 'wphlb_was_activated' ) ) {
		add_option( 'wphlb_was_activated', 1 );
		add_option( 'wphlb_show_logo', 1 );
		add_option( 'wphlb_limit_login', 1 );
		add_option( 'wphlb_blacklisted_ips', array() );
	}
}




function wphlb_load_translations() {
	load_plugin_textdomain( 'wphlb', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'wphlb_load_translations' );





function wphlb_options_page() {
	add_options_page( __('WP Hotell Security', 'wphlb' ), __('WP Hotell Security', 'wphlb' ), 'manage_options', 'wp-hotell-login', 'wphlb_options_page_content' );
}

add_action( 'admin_menu', 'wphlb_options_page' );




function wphlb_options_page_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	if( !empty( $_POST ) ) {
		check_admin_referer( 'wphlb-options-nonce' );
		update_option( 'wphlb_limit_login', (int) $_POST['limit_login'] );
		update_option( 'wphlb_show_logo', (int) $_POST['show_logo'] );
	}
	$limit_login = get_option( 'wphlb_limit_login' );
	$show_logo = get_option( 'wphlb_show_logo' );
?>
<div class="wrap">
	<br />
	<br />
	<img src="<?php echo plugins_url( 'assets/img/wphotell-AS-logo-2019.png', __FILE__ ); ?>">
	<br />
	<br />
	<form action="<?php menu_page_url( 'wp-hotell-login' ); ?>" method="post">
		<?php wp_nonce_field( 'wphlb-options-nonce' ); ?>
		<table class="form-table">
			<tr>
				<td>
					<input type="checkbox" id="wphlb_field_limit" name="limit_login" <?php checked( $limit_login ); ?> value="1"/><label for="wphlb_field_limit"><?php _e( 'Limiting number of login attempts', 'wphlb' ); ?></label><br />
					<input type="checkbox" id="wphlb_field_logo" name="show_logo" <?php checked( $show_logo ); ?> value="1"/><label for="wphlb_field_logo"><?php _e( 'Show WP Hotell login logo', 'wphlb' ); ?></label>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save changes', 'wphlb' ) ); ?>
	</form>
</div>
<?php
}





function wphlb_auth_signon( $user, $username, $password ) {
	if ( get_option( 'wphlb_limit_login' ) ) {
		$blacklisted = (array) get_option( 'wphlb_blacklisted_ips' );
		$user_ip = wphlb_get_ip();
		if ( isset( $user_ip ) ) {
			if ( isset( $blacklisted[$user_ip] ) ) {
				if ( $blacklisted[$user_ip]['attempts'] >= 3) {
					$duration = $blacklisted[$user_ip]['duration'];
					if ( $duration > time() ) {
						return new WP_Error( 'formangeforsok', sprintf( __( '<strong>WP Hotell says</strong>: You have tried to many times, you can try again in %1$s.', 'wphlb' ) , wphlb_tidsbeg( $duration ) ) . sprintf( '<br><a href="https://wphotell.unitedworks.no/?utm_source=wordpress-login&utm_medium=login&utm_campaign=support#kontakt-oss">%s</a>', __( 'Support', 'wphlb') ) );
					}
				}
			}
		}
	}
    return $user;
}

add_filter( 'authenticate', 'wphlb_auth_signon', 10001, 3 );



function wphlb_logfeil( $username ) {
	if ( get_option( 'wphlb_limit_login' ) ) {
		$blacklisted = (array) get_option( 'wphlb_blacklisted_ips' );
		$user_ip = wphlb_get_ip();
		if ( isset( $user_ip ) ) {
			$blacklisted[$user_ip]['attempts'] += 1;
			if ( $blacklisted[$user_ip]['attempts'] >= 3 ) {
				$blacklisted[$user_ip]['duration'] = time() + 300;
			}
			update_option( 'wphlb_blacklisted_ips', $blacklisted );
		}
	}
}

add_action( 'wp_login_failed', 'wphlb_logfeil', 10, 1 );




function wphlb_remove_user_ip() {
	if ( get_option( 'wphlb_limit_login' ) ) {
		$blacklisted = (array) get_option( 'wphlb_blacklisted_ips' );
		$user_ip = wphlb_get_ip();
		if ( isset( $user_ip ) ) {
			if ( isset( $blacklisted[$user_ip] ) ) {
				unset($blacklisted[$user_ip]);
				update_option( 'wphlb_blacklisted_ips', $blacklisted );
			}
		}
	}
}

add_action('wp_login', 'wphlb_remove_user_ip', 10, 2);
add_action( 'after_password_reset', 'wphlb_remove_user_ip', 10, 1 ); 



function wphlb_get_ip() {
    if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { 
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
	if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
        return $ip;
    }
	return false;
}




function wphlb_tidsbeg($timestamp) {

   $periods = array(
		array( __("second", "wphlb"), __("seconds", "wphlb") ),
		array( __("minute", "wphlb"), __("minutes", "wphlb") ),
		array( __("hour", "wphlb"), __("hours", "wphlb") ),
		array( __("day", "wphlb"), __("days", "wphlb") ),
		array( __("week", "wphlb"), __("weeks", "wphlb") ),
		array( __("month", "wphlb"), __("months", "wphlb") ),
		array( __("year", "wphlb"), __("years", "wphlb") )
    );
    $lengths = array(
        "60",
        "60",
        "24",
        "7",
        "4.35",
        "12"
    );
    $current_timestamp = time();
    $difference = abs($current_timestamp - $timestamp);
    for ($i = 0; $difference >= $lengths[$i] && $i < count($lengths) - 1; $i ++) {
        $difference /= $lengths[$i];
    }
    $difference = round($difference);
    if (isset($difference)) {
		return $difference." ".( $difference == 1 ? $periods[$i][0] : $periods[$i][1] );
    }
}





function wphlb_frontlogoen() {
	if ( get_option( 'wphlb_show_logo' ) ) {
?> 
<style type="text/css"> 
	body.login div#login h1 a {
		background-image: url(<?php echo plugins_url( 'assets/img/loginmerke.svg', __FILE__ ); ?>);
	} 
</style>
<?php 
	}
}

add_action( 'login_enqueue_scripts', 'wphlb_frontlogoen' );

