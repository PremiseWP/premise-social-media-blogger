<?php
/**
 * Youtube Custom Post Type model
 *
 * @link https://github.com/PremiseWP/premise-portfolio/blob/master/classes/class-portfolio-cpt.php
 *
 * @package Premise Social Media Blogger
 */

/**
 * This class registers our custom post type and adds the meta box necessary
 */
class Premise_Social_Media_Blogger_Youtube_CPT {

	/**
	 * Instance ID.
	 *
	 * @var int
	 */
	public $instance_id;



	/**
	 * Instances.
	 *
	 * @var array
	 */
	private static $instances = array();



	/**
	 * The cutom post type supported
	 *
	 * @var array
	 */
	public $post_type = array( 'psmb_youtube_', 'post' );



	/**
	 * Gets or create a new instance.
	 *
	 * @param int    $instance_id   Instance ID.
	 * @param string $playlist_title Playlist name.
	 *
	 * @return object
	 */
	public static function get_instance( $instance_id, $playlist_title = '' ) {

		// Check if instance alreay created.
		if ( isset( self::$instances[ (int) $instance_id ] )
			&& self::$instances[ (int) $instance_id ] ) {

			return self::$instances[ (int) $instance_id ];
		}

		self::$instances[ (int) $instance_id ] = new self( $instance_id, $playlist_title );

		return self::$instances[ (int) $instance_id ];
	}

	/**
	 * Constructor
	 * Register YouTube Video CPT
	 *
	 * Add meta box (@see add_meta_boxes)
	 * Save meta box (@see do_save)
	 *
	 * @param int    $instance_id   Instance ID (for multiple Youtube Videos CPTs!).
	 * @param string $playlist_title Playlist name.
	 */
	public function __construct( $instance_id, $playlist_title, $post_type = '' ) {

		$this->instance_id = $instance_id;

		if ( $post_type !== 'post' ) {

			$this->post_type[0] .= $this->instance_id;

			if ( ! $playlist_title ) {

				$playlist_title = 'YouTube';
			}

			if ( class_exists( 'PremiseCPT' ) ) {

				/**
				 * Register YouTube Video custom post type
				 *
				 * Holds instance of new CPT
				 *
				 * @see Premise WP Framework for more information
				 * @link https://github.com/vallgroup/Premise-WP
				 *
				 * @var object
				 */
				$yt_cpt = new PremiseCPT(
					array(
						'plural' => sprintf( __( '%s Videos', 'psmb' ), $playlist_title ),
						'singular' => sprintf( __( '%s Video', 'psmb' ), $playlist_title ),
						'post_type_name' => 'psmb_youtube_' . $this->instance_id,
						'slug' => 'psmb-youtube-' . $this->instance_id,
					),
					array(
						'supports' => array(
							'title',
							'editor',
							'author',
							'thumbnail',
							'post-formats',
						),
						// @see https://developer.wordpress.org/resource/dashicons/#video-alt3.
						'menu_icon' => 'dashicons-video-alt3',
					)
				);

				$yt_cpt->register_taxonomy(
					array(
						'taxonomy_name' => 'psmb_youtube_' . $this->instance_id . '-category',
						'singular' => __( 'YouTube Category', 'psmb' ),
						'plural' => __( 'YouTube Categories', 'psmb' ),
						'slug' => 'psmb-youtube-' . $this->instance_id . '-category',
					),
					array(
						'hierarchical' => false, // No sub-categories.
					)
				);

				$yt_cpt->register_taxonomy(
					array(
						'taxonomy_name' => 'psmb_youtube_' . $this->instance_id . '-tag',
						'singular' => __( 'YouTube Tag', 'psmb' ),
						'plural' => __( 'YouTube Tags', 'psmb' ),
						'slug' => 'psmb-youtube-' . $this->instance_id . '-tag',
					),
					array(
						'hierarchical' => false, // No sub-tags.
					)
				);
			}
		}

		if ( is_admin() ) {

			add_action( 'load-post.php', array( $this, 'load_post_actions' ) ); // Add Youtube Videos post meta fields.
			add_action( 'load-post-new.php', array( $this, 'load_post_actions' ) ); // Add Youtube Videos post meta fields.
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

			// Regular post is Youtube?
			$meta_exists = premise_get_post_meta( (int) $_GET['post'], 'psmb_youtube' );
		}

		if ( in_array( $post_type, $this->post_type )
			&& $meta_exists ) {

			$post_type = $post_type !== 'post' ? 'psmb_youtube_' . $this->instance_id : '';

			add_meta_box(
				'psmb-youtube-cpt-meta-box',
				__( 'YouTube Video Options', 'psmb' ),
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
		wp_nonce_field( 'psmb_youtube_nonce_check', 'psmb_youtube_nonce' );

		// The url.
		premise_field( 'text', array(
			'name' => 'psmb_youtube[url]',
			'placeholder' => 'https://www.youtube.com/watch?v=xxxxxxxxxxxx',
			'label' => __( 'Video URL', 'psmb' ),
			'wrapper_class' => 'span12',
			'context' => 'post',
		) );

	}



	/**
	 * Save our custom post type meta data
	 *
	 * @param  int $post_id the post id for the post currently being edited.
	 * @return void         does not return anything
	 */
	public function do_save( $post_id ) {

		if ( ! isset( $_POST['psmb_youtube_nonce'] ) ) {

			return $post_id;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $_POST['psmb_youtube_nonce'], 'psmb_youtube_nonce_check' ) ) {
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

		$psmb_youtube = wp_unslash( $_POST['psmb_youtube'] );

		update_post_meta( $post_id, 'psmb_youtube', $psmb_youtube );
	}


	/**
	 * Load our custom template
	 *
	 * Only load the custom template if we are loading a video post. If it is a category or tags page
	 * wehre we need to display the loop of posts. Let the theme handle it.
	 *
	 * @param  string $template Template file.
	 *
	 * @return string           Template path.
	 */
	static function youtube_page_template( $template ) {

		global $post;

		if ( preg_match( '/^psmb_youtube_/', $post->post_type ) ) {

			// Is template overridden in theme?
			$new_template = locate_template( array( 'single-psmb-youtube.php' ) );

			if ( '' != $new_template ) {
				return $new_template;
			}

			return PSMB_PATH . 'view/single-psmb-youtube.php';
		}

		return $template;
	}


	/**
	 * Insert Youtube Post.
	 * Also inserts thumbnail & as video format.
	 *
	 * @see Premise_Social_Media_Blogger_Youtube::get_video_details()
	 *
	 * @param  array  $video_details Video details.
	 * @param  string $post_type     Post type.
	 *
	 * @return int                   Post ID or 0 on error.
	 */
	public function insert_youtube_post( $video_details, $post_type = '' ) {

		if ( ! $post_type ) {

			$post_type = 'psmb_youtube_' . $this->instance_id;
		}

		if ( $post_type === 'post' ) {

			// Add Youtube video URL to post content for automatic embedding.
			$video_details['description'] = $video_details['url'] . "\n<br />\n" .
				$video_details['description'];
		}

		// Insert new Youtube post.
		$youtube = array(
			'post_title' => $video_details['title'],
			'post_status' => 'publish',
			'post_date' => $video_details['date'],
			'post_type' => $post_type,
			'post_content' => $video_details['description'],
			'meta_input' => array(
				'psmb_youtube' => array( 'url' => $video_details['url'] ),
			),
		);

		$youtube_id = wp_insert_post( $youtube );

		if ( ! $youtube_id ) {

			$error[] = __( 'Unable to insert new YouTube post.', 'psmb' );

		} else {

			// Get playlist post format.
			$post_format = premise_get_option( 'psmb_youtube[playlists][' . $this->instance_id . '][post_format]' );

			set_post_format( $youtube_id, $post_format );

			$tags_taxonomy = 'psmb_youtube_' . $this->instance_id . '-tag';

			$category_taxonomy = 'psmb_youtube_' . $this->instance_id . '-category';

			if ( 'post' === $post_type  ) {

				$tags_taxonomy = 'post_tag';
				$category_taxonomy = 'category';

				// Categories are hierarchical: use ID!
				$term_id = term_exists( $video_details['category'], $category_taxonomy );

				if ( ! $term_id ) {

					if ( ! function_exists( 'wp_create_category' ) ) {

						// Fix PHP error Call to undefined function wp_create_category().
						require_once( ABSPATH . 'wp-load.php' );
						require_once( ABSPATH . 'wp-admin/includes/taxonomy.php' );
					}

					// Create Category!
					$term_id = wp_create_category( $video_details['category'] );
				} else {

					$term_id = $term_id['term_id'];
				}

				$video_details['category'] = array( (int) $term_id );
			}

			$playlist_category_id = premise_get_option( 'psmb_youtube[playlists][' . $this->instance_id . '][category_id]' );

			// Override Video Category?
			if ( $playlist_category_id ) {

				$video_details['category'] = array( (int) $playlist_category_id );
			}

			wp_set_post_terms( $youtube_id, $video_details['tags'], $tags_taxonomy );

			wp_set_post_terms( $youtube_id, $video_details['category'], $category_taxonomy );

			psmb_generate_featured_image( $video_details['thumbnail'], $youtube_id );
		}

		return $youtube_id;
	}
}
