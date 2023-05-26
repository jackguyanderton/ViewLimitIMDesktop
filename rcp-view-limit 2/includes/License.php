<?php

namespace RCP_VL;

class License {

	/**
	 * @var
	 */
	protected static $_instance;
	
	protected static $_prefix = 'rcpvl';

	/**
	 * Only make one instance of License
	 *
	 * @return License
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof License ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'admin_init', array( $this, 'activate_license'   ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
		add_action( 'admin_init', array( $this, 'check_license'      ) );
		add_action( 'admin_init', array( $this, 'plugin_updater'     ) );
		add_action( 'admin_notices', array( $this, 'license_admin_notice' ) );
	}

	public function get_prefix() {
		return self::$_prefix;
	}

	/**
	 * Return the license key
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_license_key() {
		return trim( Setup::get_option( 'license' ) );
	}

	/**
	 * Return the license status
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_license_status() {
		return trim( get_option( self::$_prefix . '_license_status' ) );
	}

	/**
	 * Handle License activation
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST[self::$_prefix . '_license_activate'], $_POST[self::$_prefix . '_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( self::$_prefix . '_nonce', self::$_prefix . '_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = $this->get_license_key();

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPVL_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPVL_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

		} else {

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired' :

						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked' :

						$message = __( 'Your license key has been disabled.' );
						break;

					case 'invalid' :
					case 'site_inactive' :

						$message = __( 'Your license is not active for this URL.' );
						break;

					case 'missing' :
					case 'item_name_mismatch' :

						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), RCPVL_ITEM_NAME );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.' );
						break;

					default :

						$message = __( 'An error occurred, please try again.' );
						break;
				}

			}
		}

		delete_transient( self::$_prefix . '_license_check' );

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=' . RCPVL_SETTINGS_PAGE );
			$redirect = add_query_arg( array( 'rcpvl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			wp_redirect( $redirect );
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"
		update_option( self::$_prefix . '_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=' . RCPVL_SETTINGS_PAGE ) );
		exit();
	}

	/**
	 * Handle License deactivation
	 */
	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST[self::$_prefix . '_license_deactivate'], $_POST[self::$_prefix . '_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( self::$_prefix . '_nonce', self::$_prefix . '_nonce' ) ) {
			return;
		}

		// retrieve the license from the database
		$license = $this->get_license_key();

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPVL_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPVL_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return;
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			update_option( self::$_prefix . '_license_status', 'deactivated' );
			delete_transient( self::$_prefix . '_license_check' );
		}

		wp_redirect( admin_url( 'admin.php?page=' . RCPVL_SETTINGS_PAGE ) );
		exit();
	}

	/**
	 * Check license
	 *
	 * @since       1.0.0
	 */
	public function check_license() {

		// Don't fire when saving settings
		if ( ! empty( $_POST[self::$_prefix . '_nonce'] ) ) {
			return;
		}

		$license = $this->get_license_key();
		$status  = $this->get_license_status();

		if ( $license && ! $status && ! get_transient( self::$_prefix . '_license_check' ) ) {

			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => trim( $license ),
				'item_name'  => urlencode( RCPVL_ITEM_NAME ),
				'url'        => home_url()
			);

			$response = wp_remote_post( RCPVL_STORE_URL, array( 'timeout' => 35, 'sslverify' => false, 'body' => $api_params ) );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$status = $license_data->license;

			update_option( self::$_prefix . '_license_status', $status );

			set_transient( self::$_prefix . '_license_check', $license_data->license, DAY_IN_SECONDS );

			if ( $status !== 'valid' ) {
				delete_option( self::$_prefix . '_license_status' );
			}
		}

	}

	/**
	 * Plugin Updater
	 */
	public function plugin_updater() {
		// load our custom updater
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include( RCPVL_PLUGIN_DIR . '/includes/updater.php' );
		}

		// retrieve our license key from the DB
		$license_key = $this->get_license_key();

		// setup the updater
		new \EDD_SL_Plugin_Updater( RCPVL_STORE_URL, RCPVL_PLUGIN_FILE, array(
				'version'   => RCPVL_VERSION,    // current version number
				'license'   => $license_key,     // license key (used get_option above to retrieve from DB)
				'item_name' => urlencode( RCPVL_ITEM_NAME ), // the name of our product in EDD
				'author'    => 'Tanner Moushey'  // author of this plugin
			)
		);

	}

	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 */
	public function license_admin_notice() {
		if ( isset( $_GET['rcpvl_activation'] ) && ! empty( $_GET['message'] ) ) {

			switch ( $_GET['rcpvl_activation'] ) {

				case 'false':
					$message = urldecode( $_GET['message'] );
					?>
					<div class="error">
						<p><?php echo $message; ?></p>
					</div>
					<?php
					break;

				case 'true':
				default:
					// Developers can put a custom success message here for when activation is successful if they way.
					break;

			}
		}
	}

}