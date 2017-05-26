<?php
/**
 * Functions library.
 *
 * @package Premise Social Media Blogger
 */

/**
 * Generate Featured Image
 * Call it after wp_insert_post()
 *
 * @link http://wordpress.stackexchange.com/questions/40301/how-do-i-set-a-featured-image-thumbnail-by-image-url-when-using-wp-insert-post
 *
 * @param string $image_url Image URL.
 * @param int    $post_id   Post ID.
 */
function psmb_generate_featured_image( $image_url, $post_id  ) {

	if ( ! $image_url
		|| ! $post_id ) {

		return;
	}

	$upload_dir = wp_upload_dir();

	$image_data = file_get_contents( $image_url );

	// Fix: use post ID for unique filename.
	$filename = $post_id . basename( $image_url );

	// Remove URL arguments if any ?arg1=xxx.
	if ( strpos( $filename, '?' ) !== false ) {

		$filename = substr( $filename, 0, strpos( $filename, '?' ) );
	}

	if ( wp_mkdir_p( $upload_dir['path'] ) ) {

		$file = $upload_dir['path'] . '/' . $filename;
	} else {

		$file = $upload_dir['basedir'] . '/' . $filename;
	}

	file_put_contents( $file, $image_data );

	$wp_filetype = wp_check_filetype( $filename, null );

	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => sanitize_file_name( $filename ),
		'post_content' => '',
		'post_status' => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

	$res1 = wp_update_attachment_metadata( $attach_id, $attach_data );
	$res2 = set_post_thumbnail( $post_id, $attach_id );
}



/**
 * Upload image
 *
 * @param string $image_url Image URL.
 *
 * @return string Uploaded image file URL.
 */
function psmb_upload_image( $image_url, $post_id  ) {

	if ( ! $image_url
		|| ! $post_id ) {

		return;
	}

	$upload_dir = wp_upload_dir();

	$image_data = file_get_contents( $image_url );

	// Fix: use post ID for unique filename.
	$filename = $post_id . basename( $image_url );

	// Remove URL arguments if any ?arg1=xxx.
	if ( strpos( $filename, '?' ) !== false ) {

		$filename = substr( $filename, 0, strpos( $filename, '?' ) );
	}

	if ( wp_mkdir_p( $upload_dir['path'] ) ) {

		$file = $upload_dir['path'] . '/' . $filename;
	} else {

		$file = $upload_dir['basedir'] . '/' . $filename;
	}

	file_put_contents( $file, $image_data );

	$wp_filetype = wp_check_filetype( $filename, null );

	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => sanitize_file_name( $filename ),
		'post_content' => '',
		'post_status' => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

	$res1 = wp_update_attachment_metadata( $attach_id, $attach_data );

	return $upload_dir['url'] . '/' . $filename;
}



/**
 * Linkify
 * Transforms the URLs present in text to anchors tags
 *
 * @example $text_linkified = linkify( $text );
 *
 * @link http://stackoverflow.com/questions/15928606/php-converting-text-links-to-anchor-tags
 *
 * @param string $text Text to linkify.
 *
 * @return string Linkified text
 */
function linkify( $text ) {

	$pattern = '((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,8}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';

	return preg_replace_callback( "#$pattern#i", function( $matches )
	{
		$input = $matches[0];

		$url = preg_match( '!^https?://!i', $input ) ? $input : "http://$input";

		return '<a href="' . $url . '" target="_blank">' . $input . '</a>';
	}, $text );
}


/**
 * Instagram Taggify
 * Transforms the hashtags & attags (@) present in text to anchors tags
 *
 * @example $text_taggified = psmb_instagram_taggify( $text );
 *
 * @param string $text Text to Taggify.
 *
 * @return string Taggified text
 */
function psmb_instagram_taggify( $text ) {

	// At tags.
	$text = preg_replace(
		'#@(\w+)#',
		'<a href="https://www.instagram.com/$1" target="_blank">$0</a>',
		$text
	);

	// Hash tags.
	$text = preg_replace(
		'/#(\w+)/',
		'<a href="https://www.instagram.com/explore/tags/$1" target="_blank">$0</a>',
		$text
	);

	return $text;
}


/**
 * Get the Youtube video url
 *
 * @return string|boolean the url escaped or false if url not set
 */
function psmb_get_yt_video_url() {
	$psmb_youtube = premise_get_value( 'psmb_youtube', 'post' );
	return ( isset( $psmb_youtube['url'] ) && '' !== $psmb_youtube['url'] ) ? esc_url( $psmb_youtube['url'] ) : false;
}


/**
 * Get the Instagram photo url
 *
 * @return string|boolean the url escaped or false if url not set
 */
function psmb_get_ig_photo_url() {
	$psmb_instagram = premise_get_value( 'psmb_instagram', 'post' );
	return ( isset( $psmb_instagram['url'] ) && '' !== $psmb_instagram['url'] ) ? esc_url( $psmb_instagram['url'] ) : false;
}
