<?php
/**
 * Youtube Hourly Checks Controller
 * Proceed to check the Playlists for new items & post them.
 *
 * @package Premise Social Media Blogger
 */

/**
 * Models
 */
// Load Premise_Social_Media_Blogger_Youtube_CPT class.
require_once PSMB_PATH . 'model/model-psmb-youtube-cpt.php';

// Load Premise_Social_Media_Blogger_Youtube class.
require_once PSMB_PATH . 'model/model-psmb-youtube.php';


/**
 * Do logic
 *
 * @see  Premise_Social_Media_Blogger_Youtube_CPT class
 */
// Proceed to check the YouTube Playlists for new videos & post them.
$youtube_options = premise_get_option( 'psmb_youtube' );

$youtube_client = new Premise_Social_Media_Blogger_Youtube( $youtube_options['developer_key'] );

// Get saved playlists.
$youtube_playlists = $youtube_options['playlists'];

foreach ( (array) $youtube_playlists['ids'] as $playlist_id ) {

	if ( ! isset( $youtube_playlists[ $playlist_id ] ) ) {

		continue;
	}

	$playlist = $youtube_playlists[ $playlist_id ];

	// Get YouTube video ids.
	$playlists = $youtube_client->get_playlist( $playlist_id );

	if ( ! $playlists ) {

		continue;
	}

	$playlist_details = $youtube_client->get_playlist_details( $playlists[0] );

	$import_video_ids = $youtube_client->get_playlist_video_ids( $playlist_details['playlist_id'] );

	if ( ! $import_video_ids ) {

		continue;
	}

	$imported_video_ids = $import_video_ids;

	if ( $playlist['imported_video_ids'] ) {

		// Eliminate already imported videos!
		$import_video_ids = array_diff( $import_video_ids, $playlist['imported_video_ids'] );

		// Add videos to imported array.
		$imported_video_ids = array_merge( $playlist['imported_video_ids'], $import_video_ids );
	}

	// Eliminate old videos!
	$import_video_ids = array_diff( $import_video_ids, $playlist['video_ids'] );

	if ( ! $import_video_ids ) {

		continue;
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
		continue;
	}

	$youtube_options_updated = $youtube_options;

	// Save video IDs!
	$youtube_options_updated['playlists'][ $playlist_id ]['imported_video_ids'] = $imported_video_ids;

	$youtube_options_updated['playlists'][ $playlist_id ]['video_ids'] = array_merge( $playlist['video_ids'], $import_video_ids );

	update_option( 'psmb_youtube', $youtube_options_updated );
}

