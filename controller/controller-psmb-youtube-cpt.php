<?php
/**
 * Youtube CPT Controller
 *
 * @package Premise Social Media Blogger
 */

/**
 * Model
 */
// Load Premise_Social_Media_Blogger_Youtube_CPT class.
require_once PSMB_PATH . 'model/model-psmb-youtube-cpt.php';


/**
 * Do logic
 *
 * @see  Premise_Social_Media_Blogger_Youtube_CPT class
 */
$youtube_playlists = array( 'ids' => array() );

// Register as many Youtube Videos custom post type as Youtube playlists we have.
if ( function_exists( 'premise_get_value' ) ) {
	$youtube_playlists = premise_get_value( 'psmb_youtube[playlists]' );
}

$meta_box_post_registered = false;

foreach ( (array) $youtube_playlists['ids'] as $playlist_id ) {

	if ( ! isset( $youtube_playlists[ $playlist_id ] ) ) {

		continue;
	}

	$playlist = $youtube_playlists[ $playlist_id ];

	if ( 'post' === $playlist['post_type'] ) {

		if ( ! $meta_box_post_registered ) {
			// Register the Meta Box for regular post type, once.
			new Premise_Social_Media_Blogger_Youtube_CPT( 99, 'Posts', 'post' );

			$meta_box_post_registered = true;
		}

		continue;
	}

	$cpt_instance_id = $playlist['cpt_instance_id'];

	Premise_Social_Media_Blogger_Youtube_CPT::get_instance( $cpt_instance_id, $playlist['title'] );
}
