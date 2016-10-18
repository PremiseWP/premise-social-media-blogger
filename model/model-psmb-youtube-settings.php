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

		if ( isset( $get_vars['psmb_import_youtube_channel'] ) ) {

			$channel_id = $get_vars['psmb_import_youtube_channel'];

			// Import old videos.
			$import_errors = $this->import_old_youtube_videos( $channel_id );

			if ( $import_errors ) {

				esc_html_e( $this->notification(
					implode( '<br />', $import_errors ),
					'error'
				) );
			} else {

				esc_html_e( $this->notification(
					sprintf(
						__( 'The old videos were successfully imported. Check the %s for new entries!', 'psmb' ),
						( 'post' === $youtube_options['channels'][ $channel_id ]['post_type'] ?
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
			<?php esc_html_e( 'Your YouTube channels will be periodically checked for new videos.
				New videos will be automatically blogged (see options below).', 'psmb' ); ?>
		</p>
		<?php

		$reindex = 0;

		foreach ( (array) $youtube_options['channels']['ids'] as $index => $channel_id ) {

			if ( ! $channel_id ) {

				continue;
			}

			$this->youtube_channel_settings( $reindex++, $channel_id );
		}

		// New Channel.
		premise_field(
			'text',
			array(
				'name'    => 'psmb_youtube[channels][ids][' . $reindex . ']',
				'label'   => __( 'New YouTube channel ID', 'psmb' ),
				'placeholder' => 'UC70gZSTkSeqn61TJkpOm3bQ',
				'class'   => 'span12',
				'tooltip' => __( 'The ID is the last part of the channel URL, right after "channel/"', 'psmb' ),
			)
		);

		$youtube_options = $this->options['psmb_youtube'];

		// Save our cpt_instance_ids too when saving channel IDs!
		foreach ( (array) $youtube_options['cpt_instance_ids'] as $option_index => $option_value ) : ?>
			<input type="hidden"
				name="psmb_youtube[cpt_instance_ids][<?php esc_attr_e( $option_index ); ?>]"
				value="<?php esc_attr_e( $option_value ); ?>" />
		<?php
		endforeach;
	}


	/**
	 * YouTube Channel settings
	 *
	 * @param int    $index      YouTube Channel 'ids' index.
	 * @param string $channel_id YouTube Channel ID.
	 *
	 * Outputs the YouTube Channel options
	 */
	private function youtube_channel_settings( $index, $channel_id ) {

		static $youtube_client;

		$youtube_options = $this->options['psmb_youtube'];

		if ( ! $youtube_client ) {

			require_once PSMB_PATH . 'model/model-psmb-youtube.php';

			$youtube_client = new Premise_Social_Media_Blogger_Youtube( $youtube_options['developer_key'] );
		}

		premise_field(
			'text',
			array(
				'name'    => 'psmb_youtube[channels][ids][' . $index . ']',
				'label'   => __( 'YouTube channel ID', 'psmb' ),
				'placeholder' => 'UC70gZSTkSeqn61TJkpOm3bQ',
				'class'   => 'span12',
				'tooltip' => __( 'The ID is the last part of the channel URL, right after "channel/"', 'psmb' ),
			)
		);

		$channels = $youtube_client->get_channel( $channel_id );

		if ( ! $channels ) {
			esc_html_e( $this->notification(
				sprintf( __( '"%s" is not a valid YouTube Channel ID.', 'psmb' ), $channel_id ),
				'error'
			) );

			return;
		}

		$channel_details = $youtube_client->get_channel_details( $channels[0] );

		if ( ! isset( $youtube_options['channels'][ $channel_id ] ) ) {

			$video_ids = $youtube_client->get_playlist_video_ids( $channel_details['playlist_id'], 50 );

			$new_cpt_instance_id = 0;

			$history_channel = false;

			foreach ( (array) $youtube_options['cpt_instance_ids'] as $new_cpt_instance_id => $chan_id ) {

				if ( $channel_id === $chan_id ) {

					$history_channel = true;

					continue;
				}

				$new_cpt_instance_id++;
			}

			if ( ! $history_channel ) {

				// Add channel to cpt_instance_ids.
				$youtube_options['cpt_instance_ids'][] = $channel_id;
			}

			// Default channel settings.
			$channel = array(
				'video_ids' => $video_ids,
				'imported_video_ids' => array(),
				'old_videos_imported' => '0',
				'cpt_instance_id' => (string) $new_cpt_instance_id,
				'title' => $channel_details['title'],
				'post_type' => 'psmb_youtube',
			);

			// Update options.
			$youtube_options['channels'][ $channel_id ] = $channel;

			$this->options['psmb_youtube'] = $youtube_options;

			update_option( 'psmb_youtube', $youtube_options );

			Premise_Social_Media_Blogger_Youtube_CPT::get_instance( $channel['cpt_instance_id'], $channel['title'] );

			// Set New CPT transient!
			set_transient( 'psmb_new_cpt', true );

		} else {
			$channel = $youtube_options['channels'][ $channel_id ];

			$video_ids = $channel['video_ids'];
		}

		// Save our channels too when saving channel IDs!
		foreach ( (array) $channel as $option_index => $option_value ) :
			// Nested array of options.
			if ( is_array( $option_value )
				&& $option_value ) :
				foreach ( $option_value as $sub_option_index => $sub_option_value ) : ?>
			<input type="hidden"
				name="psmb_youtube[channels][<?php esc_attr_e( $channel_id ); ?>][<?php esc_attr_e( $option_index ); ?>][<?php esc_attr_e( $sub_option_index ); ?>]"
				value="<?php esc_attr_e( $sub_option_value ); ?>" />
			<?php endforeach;
			else : ?>
			<input type="hidden"
				name="psmb_youtube[channels][<?php esc_attr_e( $channel_id ); ?>][<?php esc_attr_e( $option_index ); ?>]"
				value="<?php esc_attr_e( is_array( $option_value ) ? '' : $option_value ); ?>" />
		<?php
			endif;
		endforeach;

		$select_attr = array(
			'name'    => 'psmb_youtube[channels][' . $channel_id . '][post_type]',
			'label'   => __( 'Post type', 'psmb' ),
			'class'   => 'span12',
			'options' => array(
				sprintf( __( '%s Videos (Custom Post Type)', 'psmb' ), $channel_details['title'] ) => 'psmb_youtube',
				__( 'Posts', 'psmb' ) => 'post',
			),
		);

		if ( $channel['imported_video_ids']
			&& 'post' !== $channel['post_type'] ) {

			$select_attr['tooltip'] = __( 'Warning: your custom post type and its videos
				will disappear from the menu if you select "Posts"', 'psmb' );
		}

		premise_field(
			'select',
			$select_attr
		);

		if ( ! $channel['old_videos_imported'] ) {
			$import_url = '?page=psmb_settings&psmb_import_youtube_channel=' .
				$channel_id;

			$old_videos_number = $channel['imported_video_ids'] ?
				count( $channel['video_ids'] ) - count( $channel['imported_video_ids'] ) :
				count( $channel['video_ids'] );
		}
		?>
		<p>
			<a href="<?php echo esc_url( $channel_details['url'] ); ?>" target="_blank">
				<?php esc_html_e( $channel_details['title'] ); ?>
			</a>
			<?php if ( $channel_details['description'] ) : ?>:
				<?php esc_html_e( $channel_details['description'] ); ?>
			<?php endif; ?>
			<br />
			<?php esc_html_e( sprintf( __( 'Number of owned videos: %d', 'psmb' ), count( $video_ids ) ) ); ?>
			<?php if ( $channel['imported_video_ids'] ) : ?>
				, <?php esc_html_e( sprintf(
					__( 'Imported: %d', 'psmb' ),
					count( $channel['imported_video_ids'] )
				) ); ?>
			<?php endif; ?>
			<?php if ( ! $channel['old_videos_imported']
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
	 * @param  int $channel_id Channel ID.
	 *
	 * @return bool|array      Errors or false.
	 */
	private function import_old_youtube_videos( $channel_id ) {

		$youtube_options = $this->options['psmb_youtube'];

		if ( ! isset( $youtube_options['channels'][ $channel_id ] ) ) {

			return array( __( 'YouTube Channel not found!', 'psmb' ) );

		}

		$channel = $youtube_options['channels'][ $channel_id ];

		if ( $channel['old_videos_imported'] ) {

			return array( __( 'Old videos already imported!', 'psmb' ) );

		}

		require_once PSMB_PATH . 'model/model-psmb-youtube.php';

		$youtube_client = new Premise_Social_Media_Blogger_Youtube( $youtube_options['developer_key'] );

		$channels = $youtube_client->get_channel( $channel_id );

		if ( ! $channels ) {

			return array( sprintf( __( '"%s" is not a valid YouTube Channel ID.', 'psmb' ), $channel_id ) );

		}

		$channel_details = $youtube_client->get_channel_details( $channels[0] );

		$video_ids = $youtube_client->get_playlist_video_ids( $channel_details['playlist_id'], 50 );

		$import_video_ids = $imported_video_ids = $video_ids;

		if ( $channel['imported_video_ids'] ) {

			// Eliminate already imported videos!
			$import_video_ids = array_diff( $video_ids, $channel['imported_video_ids'] );

			// Add videos to imported array.
			$imported_video_ids = array_merge( $channel['imported_video_ids'], $import_video_ids );
		}

		$youtube_cpt = Premise_Social_Media_Blogger_Youtube_CPT::get_instance( $channel['cpt_instance_id'], $channel['title'] );

		$videos = $youtube_client->get_videos( $import_video_ids );

		foreach ( (array) $videos as $video ) {
			// Get video details.
			$video_details = $youtube_client->get_video_details( $video );

			$post_type = 'post';

			if ( 'psmb_youtube' === $channel['post_type'] ) {

				$post_type = 'psmb_youtube_' . $channel['cpt_instance_id'];
			}

			// Insert YouTube post.
			$youtube_cpt->insert_youtube_post( $video_details, $post_type );
		}

		if ( $youtube_client->errors ) {
			return $youtube_client->errors;
		}

		// Mark as imported.
		$channel['old_videos_imported'] = '1';

		// Save video IDs!
		$channel['imported_video_ids'] = $imported_video_ids;

		$youtube_options['channels'][ $channel_id ] = $channel;

		update_option( 'psmb_youtube', $youtube_options );

		$this->options['psmb_youtube'] = $youtube_options;

		return false;
	}
}
