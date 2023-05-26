<?php

namespace RCP_VL;

class Setup {

	protected static $_instance;

	/**
	 * @var License
	 */
	public $licence;

	/* Only make one instance of the Setup */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Setup ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 **/
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );
		add_action( 'cmb2_admin_init', array( $this, 'rcpvl_register_option_fields' ) );
		add_action( 'cmb2_render_license_key', [ $this, 'render_license_key' ], 10, 5 );
		add_filter( 'cmb2_sanitize_license_key', [ $this, 'sanitize_license_key' ], 10, 2 );

		if ( self::get_option( 'toggle' ) === 'on' ) {
			FrontEnd::get_instance();
		}
	}

	/**
	 * Render the license key field
	 *
	 * @param $field
	 * @param $escaped_value
	 * @param $object_id
	 * @param $object_type
	 * @param $field_type_object \CMB2_Types
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function render_license_key( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		echo $field_type_object->input( [ 'type' => 'text' ] );

		$license = $this->licence->get_license_key();
		$status  = $this->licence->get_license_status(); ?>

		<p>
			<?php wp_nonce_field( $this->licence->get_prefix() . '_nonce', $this->licence->get_prefix() . '_nonce' ); ?>

			<?php if ( $status == 'valid' ) : ?>
				<?php wp_nonce_field( $this->licence->get_prefix() . '_deactivate_license', $this->licence->get_prefix() . '_deactivate_license' ); ?>
				<?php submit_button( 'Deactivate License', 'secondary', $this->licence->get_prefix() . '_license_deactivate', false ); ?>
				<span style="color:green">&nbsp;&nbsp;<?php _e( 'active', $this->licence->get_prefix() ); ?></span>
			<?php elseif ( $license ) : ?>
				<?php submit_button( 'Activate License', 'secondary', $this->licence->get_prefix() . '_license_activate', false ); ?>
			<?php endif; ?>
		</p>
		<?php
	}

	public function sanitize_license_key( $override_value, $value ) {
		return sanitize_text_field( $value );
	}

	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		require_once( RCPVL_PLUGIN_DIR . '/includes/License.php' );
		$this->licence = License::get_instance();
	}

	/**
	 * Make sure RCP is active
	 **/
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'restrict-content-pro/restrict-content-pro.php' ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );

		return false;
	}

	/**
	 * Required plugins notice
	 **/
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Restrict Content Pro is required for the Restrict Content Pro View Limit add-on to function.', 'rcpvl' ) );
	}

	/**
	 * Register settings
	 */
	public function rcpvl_register_option_fields() {
		$primary               = array(
			'id'           => 'rcpvl_license_page',
			'title'        => 'View Limit',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'rcpvl',
			'parent_slug'  => 'rcp-members',
			'tab_group'    => 'rcpvl_group',
			'tab_title'    => 'License',
		);
		$primary['display_cb'] = [ $this, 'options_display' ];
		$primary_options       = new_cmb2_box( $primary );
		$primary_options->add_field(
			array(
				'name' => 'License Key',
				'desc' => 'Enter your Restrict Content Pro - View Limit license key. This is required for automatic updates and support.',
				'id'   => 'license',
				'type' => 'license_key',
			)
		);

		$secondary               = array(
			'id'           => 'rcpvl_settings_page',
			'menu_title'   => 'Secondary Options', // Use menu title, & not title to hide main h2.
			'object_types' => array( 'options-page' ),
			'option_key'   => 'rcpvl_settings',
			'parent_slug'  => 'rcpvl',
			'tab_group'    => 'rcpvl_group',
			'tab_title'    => 'Settings',
		);
		$secondary['display_cb'] = [ $this, 'options_display' ];
		$secondary_options       = new_cmb2_box( $secondary );
		$secondary_options->add_field(
			array(
				'name' => 'Visibility',
				'desc' => 'Use these fields to set a view limit for non-members.',
				'type' => 'title',
				'id'   => 'vis_title',
			)
		);
		$secondary_options->add_field(
			array(
				'name' => 'Check to enable view limit',
				'id'   => 'toggle',
				'type' => 'checkbox',
			)
		);
		$secondary_options->add_field(
			array(
				'name'            => 'Pages before user is limited',
				'default'         => 5,
				'id'              => 'limit',
				'type'            => 'text_small',
				'attributes'      => array(
					'type'    => 'number',
					'pattern' => '\d*',
				),
				'sanitization_cb' => 'absint',
				'escape_cb'       => 'absint',
			)
		);
		$secondary_options->add_field(
			array(
				'name'            => 'Days before limit resets',
				'default'         => 30,
				'id'              => 'expires',
				'type'            => 'text_small',
				'attributes'      => array(
					'type'    => 'number',
					'pattern' => '\d*',
				),
				'sanitization_cb' => 'absint',
				'escape_cb'       => 'absint',
			)
		);
		$secondary_options->add_field(
			array(
				'name' => 'Redirect page',
				'id'   => 'redirect',
				'desc' => 'Leave blank to use the default RCP redirect page.',
				'type' => 'text_url',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'Background color',
				'id'      => 'bgcolor',
				'type'    => 'colorpicker',
				'default' => '#c00',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'Text color',
				'id'      => 'textcolor',
				'type'    => 'colorpicker',
				'default' => '#ffffff',
			)
		);
		$secondary_options->add_field(
			array(
				'name' => 'Count Bar',
				'desc' => 'Use these fields to customize the counter bar.',
				'type' => 'title',
				'id'   => 'count_title',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'First button text',
				'id'      => 'one_text',
				'type'    => 'text_medium',
				'default' => 'View Subscription Offers',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'Second button text',
				'id'      => 'two_text',
				'type'    => 'text_medium',
				'default' => 'Sign In',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'First button link',
				'id'      => 'one_link',
				'type'    => 'text_url',
				'default' => '\/register\/',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'Second button link',
				'id'      => 'two_link',
				'type'    => 'text_url',
				'default' => '\/login\/',
			)
		);
		$secondary_options->add_field(
			array(
				'name'    => 'Count bar message',
				'desc'    => '<em>%count%</em> - The number of views remaining.',
				'default' => 'You have %count% free article(s) remaining. Subscribe for unlimited access.',
				'id'      => 'message',
				'type'    => 'textarea_small',
			)
		);
	}

	/**
	 * Settings page output
	 */
	public function options_display( $cmb_options ) {
		$tabs = $this->options_page_tabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ): ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ): ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ): ?> nav-tab-active<?php endif; ?>" href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data" encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Tabs display output
	 */
	public function options_page_tabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();
		foreach ( \CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}

		return $tabs;
	}

	/**
	 * Return settings option
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return bool
	 * @author Tanner Moushey
	 */
	public static function get_option( $key, $default = false ) {
		if ( 'license' === $key ) {
			$settings = get_option( 'rcpvl', [] );
		} else {
			$settings = get_option( 'rcpvl_settings', [] );
		}

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

}