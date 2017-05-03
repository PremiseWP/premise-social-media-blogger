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
$instagram_account_ids = array();

// Register as many Instagram Videos custom post type as Instagram accounts we have.
if ( function_exists( 'premise_get_value' ) ) {
	$instagram = premise_get_value( 'psmb_instagram' );

	foreach ( (array) $instagram as $key => $values ) {
		if ( strpos( $key, 'account' ) === 0 ) {
			$instagram_account_ids[] = (string) substr( $key, 7 );
		}
	}
}

$meta_box_post_registered = false;

foreach ( (array) $instagram_account_ids as $account_id ) {

	$instagram_account = $instagram[ 'account' . $account_id ];

	if ( 'post' === $instagram_account['post_type'] ) {

		if ( ! $meta_box_post_registered ) {
			// Register the Meta Box for regular post type, once.
			new Premise_Social_Media_Blogger_Instagram_CPT( 99, 'Posts', 'post' );

			$meta_box_post_registered = true;
		}

		continue;
	}

	$cpt_instance_id = $account_id;

	Premise_Social_Media_Blogger_Instagram_CPT::get_instance( (int) $cpt_instance_id, $instagram_account['title'] );
}
