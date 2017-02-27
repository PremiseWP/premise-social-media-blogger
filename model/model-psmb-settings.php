<?php
/**
 * Settings Model
 *
 * @see plugin Options
 * @link http://codex.wordpress.org/Creating_Options_Pages
 *
 * @package Premise Social Media Blogger
 */

defined( 'ABSPATH' ) or exit;

/**
 * Premise Social Media Blogger Settings class
 */
class Premise_Social_Media_Blogger_Settings {

	/**
	 * Settings to register
	 *
	 * @see constructor
	 *
	 * @var array
	 */
	private $settings = array(
		'psmb_youtube',
		'psmb_instagram',
	);


	/**
	 * Options Group for settings
	 *
	 * @var string
	 */
	private $options_group = 'psmb_settings';


	/**
	 * Options
	 *
	 * @see constructor
	 *
	 * @var array
	 */
	public $options = array();


	/**
	 * The defaults
	 *
	 * @see constructor
	 *
	 * @var array
	 */
	public $defaults = array();


	/**
	 * Constructor
	 *
	 * Set settings, plugin_page & defaults
	 */
	function __construct() {

		$this->defaults = array(
			'psmb_youtube' => array(
				'playlists' => array(),
				'cpt_instance_ids' => array(),
				'developer_key' => '',
			),
			'psmb_instagram' => array(
				'api_options' => array(),
				'account' => array(),
			),
		);

		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'run_defaults' ) );

		if ( ( isset( $_GET['page'] )
				&& 'psmb_settings' === $_GET['page'] )
			&& ( isset( $_GET['psmb_import_youtube_playlist'] )
				|| isset( $_GET['psmb_import_instagram_account'] )
				|| isset( $_GET['code'] ) ) ) {

			// Remove &psmb_import_youtube_playlist from URL!
			add_filter( 'removable_query_args', array( $this, 'remove_import_query_arg' ) );
		}

		$this->get_options();
	}



	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		add_options_page(
			__( 'Premise Social Media Blogger Settings', 'psmb' ),
			__( 'Premise Social Media Blogger', 'psmb' ),
			'edit_plugins',
			'psmb_settings',
			array( $this, 'plugin_settings' )
		);
	}

	/**
	 * Display plugin options
	 */
	public function plugin_settings() {

		wp_enqueue_media();

		// View.
		require_once PSMB_PATH . 'view/view-psmb-settings.php';
	}


	/**
	 * Get Options
	 */
	protected function get_options() {
		foreach ( $this->settings as $setting ) {

			$this->options[ $setting ] = get_option( $setting );
		}
	}


	/**
	 * Register Settings callback
	 */
	public function register_settings() {
		foreach ( $this->settings as $setting ) {

			register_setting( $this->options_group, $setting );
		}
	}


	/**
	 * Runs The default theme options
	 */
	public function run_defaults() {
		foreach ( $this->defaults as $key => $value ) {

			if ( ! get_option( $key ) ) {

				update_option( $key, $value );
			}
		}
	}


	/**
	 * Notifications helper
	 *
	 * @param  string $text Notification text.
	 * @param  string $type 'error' or 'update'.
	 */
	protected function notification( $text, $type = 'update' ) {

		$class = 'updated';

		if ( 'error' === $type ) {
			$class = 'error';
		}

		?>

		<div class="<?php echo esc_attr( $class ); ?>">
			<p><?php echo esc_html( $text ); ?></p>
		</div>

		<?php
	}


	/**
	 * Remove
	 * &psmb_import_youtube_playlist
	 * &psmb_import_instagram_account
	 * &code (Instagram auth)
	 * from URL!
	 *
	 * @see Wordpress "removable_query_args" filter
	 *
	 * @param  array $removable_query_args Removable query args.
	 *
	 * @return array Removable query args + psmb_import_youtube_playlist + psmb_import_instagram_account + code
	 */
	public function remove_import_query_arg( $removable_query_args ) {

		$removable_query_args[] = 'psmb_import_youtube_playlist';

		$removable_query_args[] = 'psmb_import_instagram_account';

		$removable_query_args[] = 'code';

		return $removable_query_args;
	}
}
