<?php
/**
 * Plugin Name: Block Referer Spam in Statify
 * Plugin Author: Websupporter
 * Description: This plugin extends the functionality of statify and enables you to block certain referrers from being tracked. This enables you to block referer spam.
 **/

 
/** Hook into the tracking process of Statify **/
add_filter( 'statify_skip_tracking', 'brsis_block_referrer_spam' );
function brsis_block_referrer_spam( $skip ){
	
	$blocklist = get_option( 'brsis-referrers', '' );
	$blocklist = explode( PHP_EOL, $blocklist );
	
	$use_snippet = Statify_Frontend::$_options['snippet'];
	$is_snippet = $use_snippet && get_query_var('statify_target');
	
	/* Get referrer */
	if ( $is_snippet ) {
		$referrer = urldecode( get_query_var('statify_referrer') );
	} else if ( ! $use_snippet) {
		$referrer = ( isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : '' );
	} else {
		return $skip;
	}
	
	
	/* Get only the domain*/
	$referrer = trailingslashit( trim( $referrer ) );
	if( 0 === strpos( $referrer, 'http://' ) ){
		$referrer = substr( $referrer, 7 );
	} elseif( 0 === strpos( $referrer, 'https://' ) ){
		$referrer = substr( $referrer, 8 );
	}
	
	/** Skip if Referrer is blocked **/
	if( in_array( $referrer, $blocklist ) )
		return true;
	
	return $skip;
}

add_action( 'plugins_loaded', 'brsis_setup_plugin' );
function brsis_setup_plugin(){
	if ( is_admin() && current_user_can( 'install_plugins' ) ) {
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			'brsis_plugin_action_links'
		);
	}
}

function brsis_plugin_action_links( $links ){
	$links[] = '<a href="plugins.php?page=brsis">' . __( 'Settings' ) . '</a>';
	return $links;
}


add_action( 'admin_menu', 'brsis_admin_menu' );
function brsis_admin_menu(){
	add_submenu_page ( null, __( 'Statify Block Referrer Spam', 'brsis' ), __( 'Statify Block Referrer Spam', 'brsis' ), 'manage_options', 'brsis', 'brsis_admin_page' ); 
}

function brsis_admin_page(){
	if(	isset( $_POST['brsis'] ) && 
		isset( $_POST['brsis-nonce'] ) && 
		wp_verify_nonce( $_POST['brsis-nonce'], 'update-brsis-referrer' )
	){
		$brsis = array_map( 'sanitize_text_field', explode( PHP_EOL, $_POST['brsis'] ) );
		foreach( $brsis as $key => $val ){
			if( 0 === strpos( $val, 'http://' ) ){
				$val = substr( $val, 7 );
			} elseif( 0 === strpos( $val, 'https://' ) ){
				$val = substr( $val, 8 );
			}
			$brsis[ $key ] = trailingslashit( $val );
		}
		$brsis = array_unique( $brsis );
		$brsis = implode( PHP_EOL, $brsis );
		update_option( 'brsis-referrers', $brsis );
	}
	$blocked_referrers = get_option( 'brsis-referrers', '' );
	?>
	<div class="wrap">
		<h2><?php _e( 'Define Referrers to block', 'brsis' ); ?></h2>
		<form method="post">
			<p><?php _e( 'Define which referrers you do not want to track.', 'brsis' ); ?></p>
			<textarea width="100" rows="8" name="brsis"><?php echo $blocked_referrers; ?></textarea>
			<?php wp_nonce_field( 'update-brsis-referrer', 'brsis-nonce' ); ?>
			<br /><button class="button"><?php _e( 'Update' ); ?></button>
		</form>
	</div>
	<?php
}
?>
