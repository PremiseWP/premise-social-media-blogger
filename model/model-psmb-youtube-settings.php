<?php
/**
 * Youtube Settings Model
 *
 * @see plugin Options
 *
 * @package Premise Social Media Blogger
 */

defined( 'ABSPATH' ) or exit;

/**
 * Premise Social Media Blogger Youtube Settings class
 */
class Premise_Social_Media_Blogger_Youtube_Settings extends Premise_Social_Media_Blogger_Settings {

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
	 * YouTube settings
	 *
	 * Outputs the YouTube options
	 */
	public function youtube_settings() {

		$youtube_options = $this->options['psmb_youtube'];

		$get_vars = wp_unslash( $_GET );

		if ( isset( $get_vars['psmb_import_youtube_playlist'] ) ) {

			$playlist_id = $get_vars['psmb_import_youtube_playlist'];

			// Import old videos.
			$import_errors = $this->import_old_youtube_videos( $playlist_id );

			if ( $import_errors ) {

				esc_html_e( $this->notification(
					implode( '<br />', $import_errors ),
					'error'
				) );
			} else {

				esc_html_e( $this->notification(
					sprintf(
						__( 'The old videos were successfully imported. Check the %s for new entries!', 'psmb' ),
						( 'post' === $youtube_options['playlists'][ $playlist_id ]['post_type'] ?
							__( 'Posts', 'psmb' ) :
							__( 'YouTube Videos', 'psmb' ) )
					),
					'update'
				) );
			}
		}

		// Developer key.
		?>
		<p>
			<?php esc_html_e( 'To obtain a developer key, follow steps 1 to 4 available on this page:', 'psmb' ); ?>
			<a href="https://developers.google.com/youtube/v3/getting-started" target="_blank">
				YouTube Data API Overview
			</a>
		</p>
		<?php
		premise_field(
			'text',
			array(
				'name'    => 'psmb_youtube[developer_key]',
				'label'   => __( 'YouTube API key', 'psmb' ),
				'placeholder' => 'AIzaByAx4NyYdiXvbcYSok2GGqq4B73b0AjPn8Q',
				'class'   => 'span12',
			)
		);

		if ( ! $youtube_options['developer_key'] ) {

			return;
		}

		?>
		<p>
			<?php esc_html_e( 'Your YouTube playlists will be periodically checked for new videos.
				New videos will be automatically blogged (see options below).', 'psmb' ); ?>
		</p>
		<?php

		$reindex = 0;

		if ( isset( $youtube_options['playlists'] ) ) {
			foreach ( (array) $youtube_options['playlists']['ids'] as $index => $playlist_id ) {

				if ( ! $playlist_id ) {

					continue;
				}

				$this->youtube_playlist_settings( $reindex++, $playlist_id );
			}
		}

		// New Playlist.
		premise_field(
			'text',
			array(
				'name'    => 'psmb_youtube[playlists][ids][' . $reindex . ']',
				'label'   => __( 'New YouTube playlist ID', 'psmb' ),
				'placeholder' => 'UC70gZSTkSeqn61TJkpOm3bQ',
				'class'   => 'span12',
				'tooltip' => __( 'The ID is the last part of the playlist URL, right after "playlist/"', 'psmb' ),
			)
		);

		$youtube_options = $this->options['psmb_youtube'];

		if ( isset( $youtube_options['cpt_instance_ids'] ) ) {
			// Save our cpt_instance_ids too when saving playlist IDs!
			foreach ( (array) $youtube_options['cpt_instance_ids'] as $option_index => $option_value ) : ?>
				<input type="hidden"
					name="psmb_youtube[cpt_instance_ids][<?php esc_attr_e( $option_index ); ?>]"
					value="<?php esc_attr_e( $option_value ); ?>" />
			<?php
			endforeach;
		}
	}


	/**
	 * YouTube Playlist settings
	 *
	 * @param int    $index      YouTube Playlist 'ids' index.
	 * @param string $playlist_id YouTube Playlist ID.
	 *
	 * Outputs the YouTube Playlist options
	 */
	private function youtube_playlist_settings( $index, $playlist_id ) {

		static $youtube_client;

		$youtube_options = $this->options['psmb_youtube'];

		if ( ! $youtube_client ) {

			require_once PSMB_PATH . 'model/model-psmb-youtube.php';

			$youtube_client = new Premise_Social_Media_Blogger_Youtube( $youtube_options['developer_key'] );
		}

		premise_field(
			'text',
			array(
				'name'    => 'psmb_youtube[playlists][ids][' . $index . ']',
				'label'   => __( 'YouTube playlist ID', 'psmb' ),
				'placeholder' => 'UC70gZSTkSeqn61TJkpOm3bQ',
				'class'   => 'span12',
				'tooltip' => __( 'The ID is the last part of the playlist URL, right after "playlist/"', 'psmb' ),
			)
		);

		$playlists = $youtube_client->get_playlist( $playlist_id );

		if ( ! $playlists ) {
			esc_html_e( $this->notification(
				sprintf( __( '"%s" is not a valid YouTube Playlist ID.', 'psmb' ), $playlist_id ),
				'error'
			) );

			return;
		}

		$playlist_details = $youtube_client->get_playlist_details( $playlists[0] );

		if ( ! isset( $youtube_options['playlists'][ $playlist_id ] ) ) {

			$video_ids = $youtube_client->get_playlist_video_ids( $playlist_details['playlist_id'], 50 );

			$new_cpt_instance_id = 0;

			$history_playlist = false;

			foreach ( (array) $youtube_options['cpt_instance_ids'] as $new_cpt_instance_id => $chan_id ) {

				if ( $playlist_id === $chan_id ) {

					$history_playlist = true;

					continue;
				}

				$new_cpt_instance_id++;
			}

			if ( ! $history_playlist ) {

				// Add playlist to cpt_instance_ids.
				$youtube_options['cpt_instance_ids'][] = $playlist_id;
			}

			// Default playlist settings.
			$playlist = array(
				'video_ids' => $video_ids,
				'imported_video_ids' => array(),
				'old_videos_imported' => '0',
				'cpt_instance_id' => (string) $new_cpt_instance_id,
				'title' => $playlist_details['title'],
				'post_type' => 'psmb_youtube',
				'category_id' => '',
				'post_format' => 'video',
			);

			// Update options.
			$youtube_options['playlists'][ $playlist_id ] = $playlist;

			$this->options['psmb_youtube'] = $youtube_options;

			update_option( 'psmb_youtube', $youtube_options );

			Premise_Social_Media_Blogger_Youtube_CPT::get_instance( $playlist['cpt_instance_id'], $playlist['title'] );

			// Set New CPT transient!
			set_transient( 'psmb_new_cpt', true );

		} else {
			$playlist = $youtube_options['playlists'][ $playlist_id ];

			$video_ids = $playlist['video_ids'];
		}

		// Save our playlists too when saving playlist IDs!
		foreach ( (array) $playlist as $option_index => $option_value ) :
			// Nested array of options.
			if ( is_array( $option_value )
				&& $option_value ) :
				foreach ( $option_value as $sub_option_index => $sub_option_value ) : ?>
			<input type="hidden"
				name="psmb_youtube[playlists][<?php esc_attr_e( $playlist_id ); ?>][<?php esc_attr_e( $option_index ); ?>][<?php esc_attr_e( $sub_option_index ); ?>]"
				value="<?php esc_attr_e( $sub_option_value ); ?>" />
			<?php endforeach;
			else : ?>
			<input type="hidden"
				name="psmb_youtube[playlists][<?php esc_attr_e( $playlist_id ); ?>][<?php esc_attr_e( $option_index ); ?>]"
				value="<?php esc_attr_e( is_array( $option_value ) ? '' : $option_value ); ?>" />
		<?php
			endif;
		endforeach;

		$select_attr = array(
			'name'    => 'psmb_youtube[playlists][' . $playlist_id . '][post_type]',
			'label'   => __( 'Post type', 'psmb' ),
			'class'   => 'span12',
			'options' => array(
				sprintf( __( '%s Videos (Custom Post Type)', 'psmb' ), $playlist_details['title'] ) => 'psmb_youtube',
				__( 'Posts', 'psmb' ) => 'post',
			),
		);

		if ( $playlist['imported_video_ids']
			&& 'post' !== $playlist['post_type'] ) {

			$select_attr['tooltip'] = __( 'Warning: your custom post type and its videos
				will disappear from the menu if you select "Posts"', 'psmb' );
		}

		premise_field(
			'select',
			$select_attr
		);

		// Select default category for each playlist.
		$this->select_default_category( $playlist );

		// Select default post format for each playlist.
		$this->select_default_post_format( $playlist );

		if ( ! $playlist['old_videos_imported'] ) {
			$import_url = '?page=psmb_settings&psmb_import_youtube_playlist=' .
				$playlist_id;

			$old_videos_number = $playlist['imported_video_ids'] ?
				count( $playlist['video_ids'] ) - count( $playlist['imported_video_ids'] ) :
				count( $playlist['video_ids'] );
		}
		?>
		<p>
			<a href="<?php echo esc_url( $playlist_details['url'] ); ?>" target="_blank">
				<?php esc_html_e( $playlist_details['title'] ); ?>
			</a>
			<?php if ( $playlist_details['description'] ) : ?>:
				<?php esc_html_e( $playlist_details['description'] ); ?>
			<?php endif; ?>
			<br />
			<?php esc_html_e( sprintf( __( 'Number of owned videos: %d', 'psmb' ), count( $video_ids ) ) ); ?>
			<?php if ( $playlist['imported_video_ids'] ) : ?>
				, <?php esc_html_e( sprintf(
					__( 'Imported: %d', 'psmb' ),
					count( $playlist['imported_video_ids'] )
				) ); ?>
			<?php endif; ?>
			<?php if ( ! $playlist['old_videos_imported']
				&& $old_videos_number ) : ?>
				<a href="<?php echo esc_url( $import_url ); ?>" class="primary"style="float: right;"
					onclick="document.getElementById('import-youtube-spinner').className += ' is-active';">
					<span class="spinner" id="import-youtube-spinner"></span>
					<?php echo esc_html( sprintf(
						__( 'Import last %s videos', 'psmb' ),
						$old_videos_number
					) ); // TODO: fake primary button. ?>
				</a>
				<?php if ( 50 === $old_videos_number ) :
					esc_html_e( '(YouTube API maximum of 50 videos reached)', 'psmb' );
				endif; ?>
			<?php endif; ?>
			</p>
		<?php
	}


	/**
	 * Import old YouTube videos
	 * Insert YouTube posts.
	 *
	 * @uses Premise_Social_Media_Blogger_Youtube
	 * @uses Premise_Social_Media_Blogger_Youtube_CPT::insert_youtube_post()
	 *
	 * @param  int $playlist_id Playlist ID.
	 *
	 * @return bool|array      Errors or false.
	 */
	private function import_old_youtube_videos( $playlist_id ) {

		$youtube_options = $this->options['psmb_youtube'];

		if ( ! isset( $youtube_options['playlists'][ $playlist_id ] ) ) {

			return array( __( 'YouTube Playlist not found!', 'psmb' ) );

		}

		$playlist = $youtube_options['playlists'][ $playlist_id ];

		if ( $playlist['old_videos_imported'] ) {

			return array( __( 'Old videos already imported!', 'psmb' ) );

		}

		require_once PSMB_PATH . 'model/model-psmb-youtube.php';

		$youtube_client = new Premise_Social_Media_Blogger_Youtube( $youtube_options['developer_key'] );

		$playlists = $youtube_client->get_playlist( $playlist_id );

		if ( ! $playlists ) {

			return array( sprintf( __( '"%s" is not a valid YouTube Playlist ID.', 'psmb' ), $playlist_id ) );

		}

		$playlist_details = $youtube_client->get_playlist_details( $playlists[0] );

		$video_ids = $youtube_client->get_playlist_video_ids( $playlist_details['playlist_id'], 50 );

		$import_video_ids = $imported_video_ids = $video_ids;

		if ( $playlist['imported_video_ids'] ) {

			// Eliminate already imported videos!
			$import_video_ids = array_diff( $video_ids, $playlist['imported_video_ids'] );

			// Add videos to imported array.
			$imported_video_ids = array_merge( $playlist['imported_video_ids'], $import_video_ids );
		}

		$youtube_cpt = Premise_Social_Media_Blogger_Youtube_CPT::get_instance( $playlist['cpt_instance_id'], $playlist['title'] );

		$videos = $youtube_client->get_videos( $import_video_ids );

		foreach ( (array) $videos as $video ) {
			// Get video details.
			$video_details = $youtube_client->get_video_details( $video );

			$post_type = 'post';

			if ( 'psmb_youtube' === $playlist['post_type'] ) {

				$post_type = 'psmb_youtube_' . $playlist['cpt_instance_id'];
			}

			// Insert YouTube post.
			$youtube_cpt->insert_youtube_post( $video_details, $post_type );
		}

		if ( $youtube_client->errors ) {
			return $youtube_client->errors;
		}

		// Mark as imported.
		$playlist['old_videos_imported'] = '1';

		// Save video IDs!
		$playlist['imported_video_ids'] = $imported_video_ids;

		$youtube_options['playlists'][ $playlist_id ] = $playlist;

		update_option( 'psmb_youtube', $youtube_options );

		$this->options['psmb_youtube'] = $youtube_options;

		return false;
	}


	/**
	 * Select default category
	 * for each playlist.
	 *
	 * @param  array $playlist Playlist details.
	 */
	private function select_default_category( $playlist ) {

		$category_taxonomy = 'category';

		if ( 'post' !== $playlist['post_type'] ) {

			$category_taxonomy = 'psmb_youtube_' . $playlist['cpt_instance_id'] . '-category';
		}

		$category_options = array( __( 'YouTube category', 'psmb' ) => '' );

		$category_terms = get_terms( $category_taxonomy, array( 'hide_empty' => false ) );

		foreach ( (array) $category_terms as $category_term ) {

			$category_options[ $category_term->name ] = $category_term->term_id;
		}

		// Select Category from already created categories, to override Video category.
		$select_attr = array(
			'name'    => 'psmb_youtube[playlists][' . $playlist['cpt_instance_id'] . '][category_id]',
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
	 * for each playlist.
	 *
	 * @param  array $playlist Playlist details.
	 */
	private function select_default_post_format( $playlist ) {

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
			'name'    => 'psmb_youtube[playlists][' . $playlist['cpt_instance_id'] . '][post_format]',
			'label'   => __( 'Post Format', 'psmb' ),
			'class'   => 'span12',
			'options' => $format_options,
		);

		premise_field(
			'select',
			$select_attr
		);
	}
}
