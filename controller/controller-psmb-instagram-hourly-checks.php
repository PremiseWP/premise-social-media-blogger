<?php
/**
 * Instagram Hourly Checks Controller
 * Proceed to check the Account for new items & post them.
 *
 * @package Premise Social Media Blogger
 */

/**
 * Models
 */
// Load Premise_Social_Media_Blogger_Instagram_CPT class.
require_once PSMB_PATH . 'model/model-psmb-instagram-cpt.php';

// Load Premise_Social_Media_Blogger_Instagram class.
require_once PSMB_PATH . 'model/model-psmb-instagram.php';


/**
 * Do logic
 *
 * @see  Premise_Social_Media_Blogger_Instagram_CPT class
 */
// Proceed to check the Instagram Account for new photos & post them.
$instagram_options = premise_get_option( 'psmb_instagram' );

$instagram_client = new Premise_Social_Media_Blogger_Instagram( $instagram_options['api_options'] );

// Get saved account.
$account = $instagram_options['account'];

if ( $account ) {

	$photos = $instagram_client->get_account_photos();

	$import_photo_ids = array();

	foreach ( (array) $photos as $photo ) {

		$import_photo_ids[] = $photo->id;
	}

	$imported_photo_ids = $import_photo_ids;

	if ( $account['imported_photo_ids'] ) {

		// Eliminate already imported photos!
		$import_photo_ids = array_diff( $import_photo_ids, $account['imported_photo_ids'] );

		// Add photos to imported array.
		$imported_photo_ids = array_merge( $account['imported_photo_ids'], $import_photo_ids );
	}

	// Eliminate old photos!
	$import_photo_ids = array_diff( $import_photo_ids, (array) $account['photo_ids'] );

	if ( $import_photo_ids ) {

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

		if ( ! $instagram_client->errors ) {

			$instagram_options_updated = $instagram_options;

			// Save photo IDs!
			$instagram_options_updated['account']['imported_photo_ids'] = $imported_photo_ids;

			$instagram_options_updated['account']['photo_ids'] = array_merge(
				$account['photo_ids'],
				$import_photo_ids
			);

			update_option( 'psmb_instagram', $instagram_options_updated );
		}
	}
}
