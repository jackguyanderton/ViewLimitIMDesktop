<?php

namespace RCP_VL;

class FrontEnd {
	protected static $instance;

	protected static $_ignore_filter = false;

	/**
	 *  Only make one instance of the FrontEnd
	 **/
	public static function get_instance() {
		if ( ! self::$instance instanceof FrontEnd ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *  Add hooks and actions
	 **/
	protected function __construct() {
		add_action( 'wp_head', array( $this, 'init' ) );
		add_filter( 'rcp_member_can_access', [ $this, 'maybe_allow_premium_access' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_allow_premium_posts' ], 50 );
	}

	public function maybe_allow_premium_posts() {
		global $rcp_options;

		if ( $this->can_access_premium() ) {
			unset( $rcp_options['hide_premium'] );
		}
	}

	public function maybe_allow_premium_access( $can_access ) {
		if ( self::$_ignore_filter ) {
			return $can_access;
		}

		if ( $this->can_access_premium() ) {
			return true;
		}

		return $can_access;
	}

	public function can_access_premium() {
		$limit = Setup::get_option( 'limit' );

		if ( empty( $_COOKIE['rcpvl-visited'] ) ) {
			return true;
		}

		$pages = explode( ',', $_COOKIE['rcpvl-visited'] );

		// if we've already visited this page,
		if ( in_array( get_home_url( null, $_SERVER['REQUEST_URI'] ), $pages ) ) {
			return true;
		}

		if ( count( $pages ) < $limit ) {
			return true;
		}

		return false;
	}

	/**
	 *  Create variables
	 **/
	public function init() {
		global $rcp_options;

		self::$_ignore_filter = true;
		$active               = ( rcp_user_can_access( get_current_user_id(), get_the_ID() ) || is_home() || is_archive() ? false : true );
		self::$_ignore_filter = false;

		if ( ! $active ) {
			return;
		}

		$loggedIn   = ( is_user_logged_in() ? true : false );
		$background = Setup::get_option( 'bgcolor' );
		$color      = Setup::get_option( 'textcolor' );
		$limit      = Setup::get_option( 'limit' );
		$expires    = Setup::get_option( 'expires' );
		$redirect   = Setup::get_option( 'redirect' );

		// rcp redirect as default
		if ( ! $redirect ) {
			$redirect = get_the_permalink( $rcp_options['redirect'] );
		}

		?>
		<style>
			.rcpvl-color {
				background-color: <?php echo $background ?>;
				color: <?php echo $color ?>;
			}
			.rcpvl-color a {
				color: <?php echo $color ?>;
			}
			#rcpvl-link-two:hover {
				color: <?php echo $background ?>;
				background-color: <?php echo $color ?>;
			}
		</style>
		<script type="text/javascript">
          var rcpvlActive = '<?php echo $active ?>';
          var rcpvlLoggedIn = '<?php echo $loggedIn ?>';
          var rcpvlLimit = '<?php echo $limit ?>';
          var rcpvlExpires = '<?php echo $expires ?>';
          var rcpvlRedirect = '<?php echo $redirect ?>';
		</script>
		<?php

		add_action( 'get_footer', array( $this, 'rcpvlNotice' ) );
		wp_enqueue_style( 'rcpvl-public-css', RCPVL_PLUGIN_URL . 'assets/css/public.css', array(), RCPVL_VERSION );
		wp_enqueue_script( 'rcpvl-js-cookie-js', RCPVL_PLUGIN_URL . 'assets/js/vendor/js.cookie.js', array(), RCPVL_VERSION );
		wp_enqueue_script( 'rcpvl-public-js', RCPVL_PLUGIN_URL . 'assets/js/public.js', array( 'rcpvl-js-cookie-js' ), RCPVL_VERSION, true );
	}

	/**
	 *  Output for notice/count bar
	 **/
	public function rcpvlNotice() {
		$oneText          = Setup::get_option( 'one_text' );
		$twoText          = Setup::get_option( 'two_text' );
		$oneLink          = Setup::get_option( 'one_link' );
		$twoLink          = Setup::get_option( 'two_link' );
		$message          = Setup::get_option( 'message' );
		$messageProcessed = str_replace( '%count%', '<span id="rcpvl-count"></span>', $message ); ?>
		<section id="rcpvl-notice" class="rcpvl-color">
			<div id="rcpvl-hide-notice" aria-label="Close Account Notice Box">&times;</div>
			<div id="rcpvl-container">
				<div id="rcpvl-message"><?php echo $messageProcessed ?>
				</div>
				<div id="rcpvl-actions">
					<a id="rcpvl-link-one" href="<?php echo $oneLink ?>"><?php echo $oneText ?></a>
					<a id="rcpvl-link-two" href="<?php echo $twoLink ?>"><?php echo $twoText ?></a>
				</div>
			</div>
		</section>
		<?php
	}
}
