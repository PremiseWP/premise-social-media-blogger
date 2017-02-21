<?php
/**
 * Instagram Custom Post Type model
 *
 * @link https://github.com/PremiseWP/premise-portfolio/blob/master/classes/class-portfolio-cpt.php
 *
 * @package Premise Social Media Blogger
 */

/**
 * This class registers our custom post type and adds the meta box necessary
 */
class Premise_Social_Media_Blogger_Instagram_CPT {

	/**
	 * Instances.
	 *
	 * @var array
	 */
	private static $instance;



	/**
	 * The cutom post type supported
	 *
	 * @var array
	 */
	public $post_type = array( 'psmb_instagram', 'post' );



	/**
	 * Gets or create a new instance.
	 *
	 * @param string $title Name.
	 *
	 * @return object
	 */
	public static function get_instance( $title = '' ) {

		null === self::$instance and self::$instance = new self( $title );

		return self::$instance;
	}

	/**
	 * Constructor
	 * Register Instagram Photo CPT
	 *
	 * Add meta box (@see add_meta_boxes)
	 * Save meta box (@see do_save)
	 *
	 * @param string $title     Name.
	 * @param sting  $post_type Post Type (only to specify if regular 'post').
	 */
	public function __construct( $title, $post_type = '' ) {

		if ( 'post' !== $post_type ) {

			if ( ! $title ) {

				$title = 'Instagram';
			}

			if ( class_exists( 'PremiseCPT' ) ) {

				/**
				 * Register Instagram Photo custom post type
				 *
				 * Holds instance of new CPT
				 *
				 * @see Premise WP Framework for more information
				 * @link https://github.com/vallgroup/Premise-WP
				 *
				 * @var object
				 */
				$ig_cpt = new PremiseCPT(
					array(
						'plural' => sprintf( __( '%s Photos', 'psmb' ), $title ),
						'singular' => sprintf( __( '%s Photo', 'psmb' ), $title ),
						'post_type_name' => 'psmb_instagram',
						'slug' => 'psmb-instagram',
					),
					array(
						'supports' => array(
							'title',
							'editor',
							'author',
							'thumbnail',
							'post-formats',
						),
						// @see https://developer.wordpress.org/resource/dashicons/#format-gallery.
						'menu_icon' => 'dashicons-format-gallery',
					)
				);

				$ig_cpt->register_taxonomy(
					array(
						'taxonomy_name' => 'psmb_instagram-category',
						'singular' => __( 'Instagram Category', 'psmb' ),
						'plural' => __( 'Instagram Categories', 'psmb' ),
						'slug' => 'psmb-instagram-category',
					),
					array(
						'hierarchical' => false, // No sub-categories.
					)
				);

				$ig_cpt->register_taxonomy(
					array(
						'taxonomy_name' => 'psmb_instagram-tag',
						'singular' => __( 'Instagram Tag', 'psmb' ),
						'plural' => __( 'Instagram Tags', 'psmb' ),
						'slug' => 'psmb-instagram-tag',
					),
					array(
						'hierarchical' => false, // No sub-tags.
					)
				);
			}
		}

		if ( is_admin() ) {

			add_action( 'load-post.php', array( $this, 'load_post_actions' ) ); // Add Instagram Photos post meta fields.
			add_action( 'load-post-new.php', array( $this, 'load_post_actions' ) ); // Add Instagram Photos post meta fields.
		}
	}


	/**
	 * Load post actions.
	 *
	 * Add meta box (@see add_meta_boxes)
	 * Save meta box (@see do_save)
	 */
	public function load_post_actions() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'do_save' ), 10 );
	}


	/**
	 * Add the meta box if within our custom post type
	 *
	 * @param string $post_type the custom post type currently loaded.
	 */
	public function add_meta_boxes( $post_type ) {

		$meta_exists = 'post' !== $post_type;

		if ( ! $meta_exists
			&& isset( $_GET['post'] ) ) { // New Post?

			// Regular post is Instagram?
			$meta_exists = premise_get_post_meta( (int) $_GET['post'], 'psmb_instagram' );
		}

		if ( in_array( $post_type, $this->post_type )
			&& $meta_exists ) {

			add_meta_box(
				'psmb-instagram-cpt-meta-box',
				__( 'Instagram Photo Options', 'psmb' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}



	/**
	 * Render the metabox content
	 *
	 * Echoes the html for the meta box content
	 */
	public function render_meta_box() {
		wp_nonce_field( 'psmb_instagram_nonce_check', 'psmb_instagram_nonce' );

		// The url.
		premise_field( 'text', array(
			'name' => 'psmb_instagram[url]',
			'placeholder' => 'https://www.instagram.com/p/xxxxxxxxxxxx',
			'label' => __( 'Photo URL', 'psmb' ),
			'wrapper_class' => 'span12',
			'context' => 'post',
		) );

		// Only likes count available, api->getMediaLikes( $photo->id ) is empty!
		/*if ( isset( $_GET['post'] ) ) {
			// The likes.
			$likes = premise_get_post_meta( (int) $_GET['post'], 'psmb_instagram[likes]' );

			// Save our likes too when saving!
			foreach ( (array) $likes as $option_index => $option_value ) : ?>

				<input type="hidden"
					name="psmb_instagram[likes][<?php esc_attr_e( $option_index ); ?>]"
					value="<?php esc_attr_e( $option_value ); ?>" />
			<?php
			endforeach;
		}*/

	}



	/**
	 * Save our custom post type meta data
	 *
	 * @param  int $post_id the post id for the post currently being edited.
	 * @return void         does not return anything
	 */
	public function do_save( $post_id ) {

		if ( ! isset( $_POST['psmb_instagram_nonce'] ) ) {

			return $post_id;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $_POST['psmb_instagram_nonce'], 'psmb_instagram_nonce_check' ) ) {
			return $post_id;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$psmb_instagram = wp_unslash( $_POST['psmb_instagram'] );

		update_post_meta( $post_id, 'psmb_instagram', $psmb_instagram );
	}


	/**
	 * Load our custom template
	 *
	 * @param  string $template Template file.
	 *
	 * @return string           Template path.
	 */
	static function instagram_page_template( $template ) {

		global $post;

		if ( 'psmb_instagram' == $post->post_type ) {

			// Is template overridden in theme?
			$new_template = locate_template( array( 'single-psmb-instagram.php' ) );

			if ( '' != $new_template ) {
				return $new_template;
			}

			return PSMB_PATH . 'view/single-psmb-instagram.php';
		}

		return $template;
	}


	/**
	 * Insert Instagram Post.
	 * Also inserts thumbnail & as aside format.
	 *
	 * @see Premise_Social_Media_Blogger_Instagram::get_photo_details()
	 *
	 * @param  array  $photo_details Photo details.
	 * @param  string $post_type     Post type.
	 *
	 * @return int                   Post ID or 0 on error.
	 */
	public function insert_instagram_post( $photo_details, $post_type = '' ) {

		if ( ! $post_type ) {

			$post_type = 'psmb_instagram';
		}

		// Build our content.
		if ( 'post' === $post_type ) {

			// Regular Post: only description.
			$content = $photo_details['description'];

		} else {

			// CPT: embed Instagram + hidden description for SEO.
			$content = $photo_details['url'] . "\r\n" .
				'<div class="psmb-instagram-description" style="visibility:hidden;">' .
					$photo_details['description'] . '</div>';
		}

		// Add Instagram video URL (in place of description) to post content for automatic embedding.
		// Insert new Instagram post.
		$instagram = array(
			'post_title' => $photo_details['title'],
			'post_status' => 'publish',
			'post_date' => $photo_details['date'],
			'post_type' => $post_type,
			'post_content' => $content,
			'meta_input' => array(
				'psmb_instagram' => array( 'url' => $photo_details['url'] ),
			),
		);

		$instagram_id = wp_insert_post( $instagram );

		if ( ! $instagram_id ) {

			$error[] = __( 'Unable to insert new Instagram post.', 'psmb' );

		} else {

			// Get post format.
			$post_format = premise_get_option( 'psmb_instagram[account][post_format]' );

			set_post_format( $instagram_id, $post_format );

			$tags_taxonomy = 'psmb_instagram-tag';

			// No categories in Instagram!
			$category_taxonomy = 'psmb_instagram-category';

			if ( 'post' === $post_type  ) {

				$tags_taxonomy = 'post_tag';
				$category_taxonomy = 'category';

				// Categories are hierarchical: use ID!
				/*$term_id = term_exists( $photo_details['category'], $category_taxonomy );

				if ( ! $term_id ) {

					// Create Category!
					$term_id = wp_create_category( $photo_details['category'] );
				} else {

					$term_id = $term_id['term_id'];
				}

				$photo_details['category'] = array( (int) $term_id );*/
			}

			wp_set_post_terms( $instagram_id, $photo_details['tags'], $tags_taxonomy );

			$account_category_id = premise_get_option( 'psmb_instagram[account][category_id]' );

			// Default Category?
			if ( $account_category_id ) {

				$photo_details['category'] = array( (int) $account_category_id );

				wp_set_post_terms( $instagram_id, $photo_details['category'], $category_taxonomy );
			}

			psmb_generate_featured_image( $photo_details['thumbnail'], $instagram_id );
		}

		return $instagram_id;
	}
}
