<?php
/**
 * Instagram Settings Model
 *
 * @see plugin Options
 *
 * @package Premise Social Media Blogger
 */

defined( 'ABSPATH' ) or exit;

/**
 * Premise Social Media Blogger Instagram Settings class
 */
class Premise_Social_Media_Blogger_Instagram_Settings extends Premise_Social_Media_Blogger_Settings {

	/**
	 * Empty constructor so we do not call
	 * parent constructor twice!
	 *
	 * Only get options (empty otherwise).
	 */
	public function __construct() {

		$this->get_options();
	}



	/**
	 * Instagram settings
	 *
	 * Outputs the Instagram options
	 *
	 * @link https://github.com/florianbeer/Instagram-PHP-API/tree/master/example
	 */
	public function instagram_settings() {

		$instagram_options = $this->options['psmb_instagram'];

		$get_vars = wp_unslash( $_GET );

		if ( isset( $get_vars['psmb_import_instagram_account'] ) ) {

			// Import old photos.
			$import_errors = $this->import_old_instagram_photos();

			if ( $import_errors ) {

				esc_html_e( $this->notification(
					implode( '<br />', $import_errors ),
					'error'
				) );
			} else {

				esc_html_e( $this->notification(
					sprintf(
						__( 'The old photos were successfully imported. Check the %s for new entries!', 'psmb' ),
						( 'post' === $instagram_options['account']['post_type'] ?
							__( 'Posts', 'psmb' ) :
							__( 'Instagram photos', 'psmb' ) )
					),
					'update'
				) );
			}
		}

		$api_options = $instagram_options['api_options'];

		// Developer key.
		if ( ! $api_options
			|| ! $api_options['apiKey']
			|| ! $api_options['apiSecret'] ) : ?>
		<p>
			<?php echo sprintf(
				__( 'First of all, %s at Instagram. Valid Redirect URI: %s. You will receive an API Client ID and secret.', 'psmb' ),
				'<a href="http://instagr.am/developer/register/" target="_blank">' .
					__( 'register your application', 'psmb' ) .
				'</a>',
				'<code>' . admin_url( 'options-general.php' ) . '</code>'
			); ?>
		</p>
		<?php endif;
		premise_field(
			'text',
			array(
				'name'    => 'psmb_instagram[api_options][apiKey]',
				'label'   => __( 'Instagram Client ID', 'psmb' ),
				'placeholder' => '20cf390597f442398f35d9e88fd225df',
				'class'   => 'span6',
			)
		);

		premise_field(
			'text',
			array(
				'name'    => 'psmb_instagram[api_options][apiSecret]',
				'label'   => __( 'Instagram Client secret', 'psmb' ),
				'placeholder' => 'b845b0bf61624c98940f1f0a9018773b',
				'class'   => 'span6',
			)
		);

		if ( ! $api_options
			|| ! $api_options['apiKey']
			|| ! $api_options['apiSecret'] ) {

			return;
		}

		$this->instagram_account_settings();
	}




	/**
	 * Instagram Account settings
	 */
	private function instagram_account_settings() {

		$instagram_options = $this->options['psmb_instagram'];

		require_once PSMB_PATH . 'model/model-psmb-instagram.php';

		$api_options = $instagram_options['api_options'];

		$instagram_client = new Premise_Social_Media_Blogger_Instagram( $api_options );

		$get_vars = wp_unslash( $_GET );

		if ( ( isset( $get_vars['code'] )
				|| isset( $get_vars['error'] ) )
			&& ( ! isset( $api_options['apiToken'] )
				|| ! $api_options['apiToken'] ) ) {

			$token = '';

			if ( isset( $get_vars['code'] ) ) {

				// Receive OAuth code parameter.
				$code = $get_vars['code'];

				$token = $instagram_client->get_oauth_token( $code );
			}

			if ( $token ) {

				$api_options['apiToken'] = $token;

				// Save Instagram options.
				$instagram_options['api_options'] = $api_options;

				$this->options['psmb_instagram'] = $instagram_options;

				update_option( 'psmb_instagram', $instagram_options );

				esc_html_e( $this->notification(
					__( 'Your Instagram application has been successfully authorized!', 'psmb' ),
					'update'
				) );
			} else {

				// Get errors.
				$errors = isset( $get_vars['error'] ) ?
					$get_vars['error'] :
					implode( '<br />', $instagram_client->errors );

				esc_html_e( $this->notification(
					$errors,
					'error'
				) );
			}
		}

		if ( ! isset( $api_options['apiToken'] ) ) {

			// Authorize first.
			?>
			<p>
				<?php echo sprintf(
					__( 'Please authorize your Instagram application: %s.', 'psmb' ),
					'<a href="' . $instagram_client->get_login_url() . '">' .
						__( 'Login to Instagram', 'psmb' ) .
					'</a>'
				); ?>
			</p>
			<?php

			// No going further.
			return;

		} else {
			// Save our apiToken too when saving! ?>
			<input type="hidden"
				name="psmb_instagram[api_options][apiToken]"
				value="<?php esc_attr_e( $api_options['apiToken'] ); ?>" />
		<?php
		}

		?>
		<p>
			<?php esc_html_e( 'Your Instagram account will be periodically checked for new photos.
				New photos will be automatically blogged (see options below).', 'psmb' ); ?>
		</p>
		<?php

		$account_details = $instagram_client->get_account_details();

		if ( ! isset( $instagram_options['account']['username'] )
			|| $instagram_options['account']['username'] !== $account_details['username'] ) {

			$photos = $instagram_client->get_account_photos( 50 );

			$photo_ids = array();

			foreach ( (array) $photos as $photo ) {

				$photo_ids[] = $photo->id;
			}

			// Default account settings.
			$account = array(
				'username' => $account_details['username'],
				'photo_ids' => $photo_ids,
				'imported_photo_ids' => array(),
				'old_photos_imported' => '0',
				'title' => $account_details['title'],
				'account_id' => $account_details['account_id'],
				'post_type' => 'psmb_instagram',
				'category_id' => '',
				'post_format' => 'aside',
			);

			// Update options.
			$instagram_options['account'] = $account;

			$this->options['psmb_instagram'] = $instagram_options;

			update_option( 'psmb_instagram', $instagram_options );

			Premise_Social_Media_Blogger_Instagram_CPT::get_instance( $account['title'] );

			// Set New CPT transient!
			set_transient( 'psmb_new_cpt', true );

		} else {
			$account = $instagram_options['account'];

			$photo_ids = $account['photo_ids'];
		}

		// Save our account options too when saving!
		foreach ( (array) $account as $option_index => $option_value ) :

			// Nested array of options.
			if ( is_array( $option_value )
				&& $option_value ) :
				foreach ( $option_value as $sub_option_index => $sub_option_value ) : ?>
			<input type="hidden"
				name="psmb_instagram[account][<?php esc_attr_e( $option_index ); ?>][<?php esc_attr_e( $sub_option_index ); ?>]"
				value="<?php esc_attr_e( $sub_option_value ); ?>" />
			<?php endforeach;
			else : ?>
			<input type="hidden"
				name="psmb_instagram[account][<?php esc_attr_e( $option_index ); ?>]"
				value="<?php esc_attr_e( is_array( $option_value ) ? '' : $option_value ); ?>" />
		<?php
			endif;
		endforeach;

		$select_attr = array(
			'name'    => 'psmb_instagram[account][post_type]',
			'label'   => __( 'Post type', 'psmb' ),
			'class'   => 'span12',
			'options' => array(
				sprintf( __( '%s Photos (Custom Post Type)', 'psmb' ), $account_details['title'] ) => 'psmb_instagram',
				__( 'Posts', 'psmb' ) => 'post',
			),
		);

		if ( $account['imported_photo_ids']
			&& 'post' !== $account['post_type'] ) {

			$select_attr['tooltip'] = __( 'Warning: your custom post type and its photos
				will disappear from the menu if you select "Posts"', 'psmb' );
		}

		premise_field(
			'select',
			$select_attr
		);

		pwp_field( array(
			'type' => 'number',
			'name' => 'psmb_instagram[options][title_max_len]',
			'default' => '100',
			'max' => '250',
			'label' => 'Max length for post titles',
			'tooltip' => 'The plugin uses the first line of your post to set the post title. If the first line is longer than this number, then the plugin will set the title based on Username and Date of your post. For SEO purposes, this helps keep things consistent with the way you use instagram.',
		) );

		// Select default category.
		$this->select_default_category( $account );

		// Select default post format.
		$this->select_default_post_format( $account );

		if ( ! $account['old_photos_imported'] ) {
			$import_url = '?page=psmb_settings&psmb_import_instagram_account';

			$old_photos_number = $account['imported_photo_ids'] ?
				count( $account['photo_ids'] ) - count( $account['imported_photo_ids'] ) :
				count( $account['photo_ids'] );
		}
		?>
		<p>
			<a href="<?php echo esc_url( $account_details['url'] ); ?>" target="_blank">
				<?php esc_html_e( $account_details['title'] ); ?>
			</a>
			<?php if ( $account_details['description'] ) : ?>:
				<?php esc_html_e( $account_details['description'] ); ?>
			<?php endif; ?>
			<br />
			<?php esc_html_e( sprintf( __( 'Number of owned photos: %d', 'psmb' ), count( $photo_ids ) ) ); ?>
			<?php if ( $account['imported_photo_ids'] ) : ?>
				, <?php esc_html_e( sprintf(
					__( 'Imported: %d', 'psmb' ),
					count( $account['imported_photo_ids'] )
				) ); ?>
			<?php endif; ?>
			<?php if ( ! $account['old_photos_imported']
				&& $old_photos_number ) : ?>
				<a href="<?php echo esc_url( $import_url ); ?>" class="primary"style="float: right;"
					onclick="document.getElementById('import-instagram-spinner').className += ' is-active';">
					<span class="spinner" id="import-instagram-spinner"></span>
					<?php echo esc_html( sprintf(
						__( 'Import last %s photos', 'psmb' ),
						$old_photos_number
					) ); // TODO: fake primary button. ?>
				</a>
				<?php if ( 50 === $old_photos_number ) :
					esc_html_e( '(Instagram API maximum of 50 photos reached)', 'psmb' );
				endif; ?>
			<?php endif; ?>
			</p>
		<?php

	}



	/**
	 * Import old Instagram photos
	 * Insert Instagram posts.
	 *
	 * @uses Premise_Social_Media_Blogger_Instagram
	 * @uses Premise_Social_Media_Blogger_Instagram_CPT::insert_instagram_post()
	 *
	 * @return bool|array      Errors or false.
	 */
	private function import_old_instagram_photos() {

		$instagram_options = $this->options['psmb_instagram'];

		if ( ! $instagram_options['account'] ) {

			return array( __( 'Instagram Account not found!', 'psmb' ) );

		}

		$account = $instagram_options['account'];

		if ( $account['old_photos_imported'] ) {

			return array( __( 'Old photos already imported!', 'psmb' ) );

		}

		require_once PSMB_PATH . 'model/model-psmb-instagram.php';

		$instagram_client = new Premise_Social_Media_Blogger_Instagram( $instagram_options['api_options'] );

		$photos = $instagram_client->get_account_photos( 50 );

		$photo_ids = array();

		foreach ( (array) $photos as $photo ) {

			$photo_ids[] = $photo->id;
		}

		$import_photo_ids = $imported_photo_ids = $photo_ids;

		if ( $account['imported_photo_ids'] ) {

			// Eliminate already imported photos!
			$import_photo_ids = array_diff( $photo_ids, $account['imported_photo_ids'] );

			// Add photos to imported array.
			$imported_photo_ids = array_merge( $account['imported_photo_ids'], $import_photo_ids );
		}

		$instagram_cpt = Premise_Social_Media_Blogger_Instagram_CPT::get_instance( $account['title'] );

		foreach ( (array) $photos as $photo ) {

			if ( ! in_array( $photo->id, $import_photo_ids ) ) {

				continue;
			}

			// Get photo details.
			$photo_details = $instagram_client->get_photo_details( $photo );

			$post_type = $account['post_type'];

			// Insert Instagram post.
			$instagram_cpt->insert_instagram_post( $photo_details, $post_type );
		}

		if ( $instagram_client->errors ) {
			return $instagram_client->errors;
		}

		// Mark as imported.
		$account['old_photos_imported'] = '1';

		// Save photo IDs!
		$account['imported_photo_ids'] = $imported_photo_ids;

		$instagram_options['account'] = $account;

		update_option( 'psmb_instagram', $instagram_options );

		$this->options['psmb_instagram'] = $instagram_options;

		return false;
	}


	/**
	 * Select default category
	 * for each account.
	 *
	 * @param  array $account Account details.
	 */
	private function select_default_category( $account ) {

		$category_taxonomy = 'category';

		if ( 'post' !== $account['post_type'] ) {

			$category_taxonomy = 'psmb_instagram-category';
		}

		$category_options = array( __( 'No category', 'psmb' ) => '' );

		$category_terms = get_terms( $category_taxonomy, array( 'hide_empty' => false ) );

		foreach ( (array) $category_terms as $category_term ) {

			$category_options[ $category_term->name ] = $category_term->term_id;
		}

		// Select Category from already created categories, to override Photo category.
		$select_attr = array(
			'name'    => 'psmb_instagram[account][category_id]',
			'label'   => __( 'Category', 'psmb' ),
			'class'   => 'span12',
			'options' => $category_options,
		);

		premise_field(
			'select',
			$select_attr
		);
	}


	/**
	 * Select default post format
	 *
	 * @param  array $account Account details.
	 */
	private function select_default_post_format( $account ) {

		$format_options = array(
			__( 'Standard' ) => '',
			__( 'Aside' ) => 'aside',
			__( 'Gallery' ) => 'gallery',
			__( 'Link' ) => 'link',
			__( 'Image' ) => 'image',
			__( 'Quote' ) => 'quote',
			__( 'Status' ) => 'status',
			__( 'Video' ) => 'video',
			__( 'Audio' ) => 'audio',
			__( 'Chat' ) => 'chat',
		);

		// Select Post Format.
		$select_attr = array(
			'name'    => 'psmb_instagram[account][post_format]',
			'label'   => __( 'Post Format', 'psmb' ),
			'class'   => 'span12',
			'options' => $format_options,
		);

		premise_field(
			'select',
			$select_attr
		);
	}}
