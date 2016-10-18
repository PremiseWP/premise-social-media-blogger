<?php
/**
 * Instagram CPT Controller
 *
 * @package Premise Social Media Blogger
 */

/**
 * Model
 */
// Load Premise_Social_Media_Blogger_Instagram_CPT class.
require_once PSMB_PATH . 'model/model-psmb-instagram-cpt.php';


/**
 * Do logic
 *
 * @see  Premise_Social_Media_Blogger_Instagram_CPT class
 */
$instagram_account = array();

// Register as many Instagram Videos custom post type as Instagram account we have.
if ( function_exists( 'premise_get_value' ) ) {
	$instagram_account = premise_get_value( 'psmb_instagram[account]' );
}

if ( $instagram_account ) {

	if ( 'post' === $instagram_account['post_type'] ) {

		// Register the Meta Box for regular post type, once.
		new Premise_Social_Media_Blogger_Instagram_CPT( 'Posts', 'post' );
	} else {

		Premise_Social_Media_Blogger_Instagram_CPT::get_instance( $instagram_account['title'] );
	}
}