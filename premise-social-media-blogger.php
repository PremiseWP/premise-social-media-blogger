<?php
/**
 * Plugin Name: Premise Social Media Blogger
 * Description: Automatically creates a post on your site when you upload a video on your YouTube playlist or a post on your Instagram account. Posts are created with description, categories (youtube), tags, date, and of course image or video. You can then add more content or rearrange to your liking. Easy to setup, with full detailed instructions included. Let us know your thoughts on Twitter <a href="https://twitter.com/premisewp" target="_blank">@premisewp</a> and if you need help use hashtag <code>#PremiseSupport</code>, we'll come to the rescue.
 * Plugin URI:
 * Version:     1.1.0
 * Author:      Premise WP (@premisewp)
 * Author URI:  http://premisewp.com
 * License:     see LICENSE file
 *
 * @package Premise Social Media Blogger
 */

// Prevent direct access to this file.
defined( 'ABSPATH' ) or exit;

// The plugin's path and url constants.
define( 'PSMB_PATH', plugin_dir_path( __FILE__ ) );
define( 'PSMB_URL',  plugin_dir_url( __FILE__ ) );




/**
 * The main function that returns Premise_Social_Media_Blogger
 *
 * The main function responsible for returning the one true Premise_Social_Media_Blogger
 * Instance that functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example         <?php $psmb = psmb(); ?>
 *
 * @return   object The one true Premise_Social_Media_Blogger Instance.
 */
function psmb() {

	return Premise_Social_Media_Blogger::get_instance();
}




// Instantiate our main class and setup Premise Social Media Blogger Plugin
// Must use 'plugins_loaded' hook.
add_action( 'plugins_loaded', array( psmb(), 'psmb_setup' ) );

// (un)Install Plugin
register_activation_hook( __FILE__, array( 'Premise_Social_Media_Blogger', 'do_install' ) );

// Uninstall Plugin.
register_uninstall_hook( __FILE__, array( 'Premise_Social_Media_Blogger', 'do_uninstall' ) );

// Flush rewrite rules on new CPT.
add_action( 'init', array( 'Premise_Social_Media_Blogger', 'flush_rewrite_rules_on_new_cpt' ), 11 );

/**
 * Load Premise Social Media Blogger Plugin!
 *
 * This is Premise Social Media Blogger Plugin main class.
 */
class Premise_Social_Media_Blogger {


	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 *
	 * @var object
	 */
	protected static $instance = null;




	/**
	 * Settings Object.
	 *
	 * This handles the admin settings and screens.
	 *
	 * @var object
	 */
	public $settings;




	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see 	psmb_setup()
	 */
	public function __construct() {}



	/**
	 * Access this plugin’s working instance
	 *
	 * @return  object instance of this class
	 */
	public static function get_instance() {

		null === self::$instance and self::$instance = new self;

		return self::$instance;
	}





	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 */
	public function __clone() {

		// Cloning instances of the class is forbidden.
		exit;
	}




	/**
	 * Disable unserializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {

		// Unserializing instances of the class is forbidden.
		exit;
	}





	/**
	 * Setup Premise Social Media Blogger Plugin
	 *
	 * Does includes, registers hooks & load language.
	 */
	public function psmb_setup() {

		$this->do_includes();
		$this->psmb_hooks();
		$this->load_language( 'psmb' );
	}






	/**
	 * Includes
	 */
	protected function do_includes() {

		require_once PSMB_PATH . 'TGM-Plugin-Activation/class-tgm-plugin-activation.php';

		require_once PSMB_PATH . 'includes/library.php';
		require_once PSMB_PATH . 'controller/controller-psmb-youtube-cpt.php';
		require_once PSMB_PATH . 'controller/controller-psmb-instagram-cpt.php';

		if ( is_admin() ) {

			require_once PSMB_PATH . 'controller/controller-psmb-settings.php';
		}
	}



	/**
	 * Flush rewrite rules when new CPT!
	 *
	 * @link http://wordpress.stackexchange.com/questions/123401/where-when-how-to-properly-flush-rewrite-rules-within-the-scope-of-a-plugin#answer-123406
	 */
	static function flush_rewrite_rules_on_new_cpt() {


		if ( get_transient( 'psmb_new_cpt' ) ) {

			/**
			 * Flush rewrite rules.
			 *
			 * Register Custom Post Type BEFORE flushing rewrite rules!
			 *
			 * @link https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
			 */
			flush_rewrite_rules();

			// Delete transient.
			delete_transient( 'psmb_new_cpt' );
		}
	}



	/**
	 * Install
	 *
	 * @param boolean $networkwide Network wide?.
	 */
	static function do_install( $networkwide ) {

		/**
		 * Schedule YouTube and Instagram Hourly checks.
		 * CRON job!
		 *
		 * @link https://developer.wordpress.org/reference/functions/wp_schedule_event/
		 */
		wp_schedule_event( time(), 'hourly', 'hourly_checks' );
	}





	/**
	 * Uninstall
	 *
	 * @param boolean $networkwide Network wide?.
	 */
	static function do_uninstall( $networkwide ) {

		// Flush rewrite rules.
		// https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
		flush_rewrite_rules();
	}




	/**
	 * Premise Hooks
	 *
	 * Registers and enqueues scripts, adds classes to the body of DOM
	 */
	public function psmb_hooks() {

		// Must be left first, do NOT move to psmb_hooks()!
		add_action( 'tgmpa_register', array( $this, 'require_plugins' ) );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'psmb_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'psmb_scripts' ) );

		// Add classes to body.
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );

		// Add CRONs.
		add_action( 'hourly_checks', array( $this, 'hourly_checks' ) );

		// Register our custom template ONCE, even if no Instagram CPT anyway (for regular post type)!
		add_filter( 'template_include', 'Premise_Social_Media_Blogger_Instagram_CPT::instagram_page_template', 99 );

		// Register our custom template ONCE, even if no Youtube CPT anyway (for regular post type)!
		add_filter( 'template_include', 'Premise_Social_Media_Blogger_Youtube_CPT::youtube_page_template', 99 );
	}


	/**
	 * Add premise classes to body of document in the front-end and backend
	 *
	 * @param  array|string $classes  array|string of classes being passed to the body.
	 * @return array|string           array|string including our new classes.
	 */
	public function body_class( $classes ) {

		if ( is_admin() ) {
			return $classes . ' psmb psmb-admin '; // end with space to avoid conflict with other classes
		}

		return $classes;
	}






	/**
	 * Premise Social Media Blogger Plugin CSS & JS
	 *
	 * Premise Social Media Blogger Plugin loads 0 main files:
	 *
	 * @author Dave Gandy http://twitter.com/davegandy
	 */
	public function psmb_scripts() {

		if ( ! is_admin() ) {
			wp_register_style( 'psmb_css_front', plugins_url( 'css/premise-social-media-blogger.min.css', __FILE__ ) );
			wp_enqueue_style( 'psmb_css_front' );
		}
		else {
			wp_register_style( 'psmb_css_admin', plugins_url( 'css/psmb-admin.css', __FILE__ ) );
			wp_enqueue_style( 'psmb_css_admin' );
		}
	}



	/**
	 * Hourly Checks callback
	 *
	 * @see wp_schedule_event()
	 */
	public function hourly_checks() {

		// Proceed to check the Playlists for new items & post them.
		require_once PSMB_PATH . 'controller/controller-psmb-youtube-hourly-checks.php';

		// Proceed to check the Account for new items & post them.
		require_once PSMB_PATH . 'controller/controller-psmb-instagram-hourly-checks.php';
	}





	/**
	 * Loads translation file.
	 *
	 * Currently not supported. but here for future integration
	 *
	 * @wp-hook init
	 *
	 * @param   string $domain Domain.
	 * @return  void
	 */
	public function load_language( $domain ) {
		load_plugin_textdomain(
			$domain,
			false,
			PSMB_PATH . 'languages'
		);
	}


	/**
	 * Require the plugins necessary to run our code
	 */
	function require_plugins() {
		// Begin with an empty array.
		$plugins = array();

		if ( ! class_exists( 'Premise_WP' ) ) {
			// Require Premise WP from GitHub.
			$plugins[] = array(
				'name'             => 'Premise-WP',
				'slug'             => 'Premise-WP',
				'source'           => 'https://github.com/PremiseWP/Premise-WP/archive/master.zip',
				'required'         => true,
				'version'          => '1.4.8',
				'force_activation' => false,
			);
		}

		if ( ! empty( $plugins ) ) {

			// Set our config settings.
			$config = array(
				'id'           => 'psmb-tgmpa',
				'default_path' => '',
				'menu'         => 'psmb-tgmpa-install-plugins',
				'parent_slug'  => 'plugins.php',
				'capability'   => 'install_plugins',
				'has_notices'  => true,
				'dismissable'  => false,
				'is_automatic' => true,
				'message'      => 'this is the message right before the plugins table',
			);

			tgmpa( $plugins, $config );
		}

		return false;
	}
}
