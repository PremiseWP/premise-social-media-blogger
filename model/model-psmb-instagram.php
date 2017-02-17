<?php
/**
 * Instagram Model
 *
 * @see Instagram-PHP-API
 * @link https://github.com/florianbeer/Instagram-PHP-API/tree/master/example
 *
 * @package Premise Social Media Blogger
 */

defined( 'ABSPATH' ) or exit;

use MetzWeb\Instagram\Instagram;

/**
 * Premise Social Media Blogger Instagram class
 */
class Premise_Social_Media_Blogger_Instagram {

	/**
	 * Instagram object
	 *
	 * @see MetzWeb\Instagram\Instagram class
	 *
	 * @var object
	 */
	private $instagram;




	/**
	 * Errors
	 *
	 * Check for errors whn finished using the object!
	 *
	 * @var array
	 */
	public $errors = array();



	/**
	 * Constructor
	 *
	 * Set API options.
	 *
	 * @param array $api_options API options (apiKey, apiSecret, apiCallback (optional), apiToken (optional)).
	 */
	function __construct( $api_options ) {

		/**
		 * Instagram API.
		 *
		 * @link https://github.com/florianbeer/Instagram-PHP-API/tree/master/example
		 */
		require_once PSMB_PATH . 'Instagram-PHP-API/src/Instagram.php';
		require_once PSMB_PATH . 'Instagram-PHP-API/src/InstagramException.php';

		$api_options['apiCallback'] = admin_url( 'options-general.php?page=psmb_settings' );

		$this->instagram = new Instagram( $api_options );

		if ( isset( $api_options['apiToken'] )
			&& $api_options['apiToken'] ) {

			$this->instagram->setAccessToken( $api_options['apiToken'] );
		}
	}



	/**
	 * Wrapper for Instagram object's getLoginUrl()
	 *
	 * @return string Login URL.
	 */
	public function get_login_url() {

		return $this->instagram->getLoginUrl();
	}



	/**
	 * Get OAuth token
	 *
	 * @param  string $code Instagram API code.
	 *
	 * @return string       Token.
	 */
	public function get_oauth_token( $code ) {

		$token = '';

		try {
			$token = $this->instagram->getOAuthToken( $code, true );

			// Store user access token.
			$this->instagram->setAccessToken( $token );

		} catch ( InstagramException $e ) {

			$this->add_error_from_exception( $e );

		}

		return $token;
	}



	/**
	 * Get Account details:
	 * id,title,url,description,account_id
	 *
	 * @return array         Account details array.
	 */
	public function get_account_details() {

		$account_details = array(
			'username' => '',
			'title' => '',
			'url' => 'https://www.instagram.com/',
			'description' => '',
			'account_id' => '',
		);

		$user = $this->instagram->getUser();

		// var_dump( (array) $user->data );

		$user = $user->data;

		$account_details['username'] = $user->username;

		$account_details['title'] = $user->full_name;

		$account_details['url'] .= $user->username;

		$account_details['description'] = $user->bio;

		$account_details['account_id'] = $user->id;

		return $account_details;
	}


	/**
	 * Get Account Photos
	 *
	 * @param string $account_id Instagram account ID.
	 *
	 * @return array $photos Instagram photos.
	 */
	public function get_account_photos( $max = 5 ) {

		$photos = array();

		try {

			$photos = $this->instagram->getUserMedia( 'self', $max );

			$photos = $photos->data;

		} catch ( InstagramException $e ) {

			$this->add_error_from_exception( $e );

		}

		return $photos;
	}


	/**
	 * Get Photo (or Video) details:
	 * id,type,title,url,description,date,thumbnail,tags,category,embed_code
	 *
	 * @param  object $photo Media object.
	 *
	 * @return array         Photo or Video details array.
	 */
	public function get_photo_details( $photo ) {

		$photo_details = array(
			'id' => '',
			'type' => '', // "image" or "video".
			'title' => '',
			'url' => '',
			'description' => '',
			'date' => '',
			'thumbnail' => '', // If video type, is video src.
			'tags' => array(),
			'category' => '', // Nope...
			'embed_code' => '',
			'likes' => array(),
			'location' => '',
		);

		$photo_details['id'] = $photo->id;

		$photo_details['type'] = $photo->type;

		$first_line    = substr( $photo->caption->text, 0, ( strpos( $photo->caption->text, "\n", 0 ) ) - 0 );
		$title_max_len = ( premise_get_value( 'psmb_instagram[options][title_max_len]' ) )
			? (int) premise_get_value( 'psmb_instagram[options][title_max_len]' )
			: 100;

		if ( $title_max_len >= strlen( $first_line ) ) {
			// use frist line as title
			$photo_details['title'] = $first_line;
		}
		else {
			// Username - datetime.
			$photo_details['title'] = $photo->caption->from->username .
				' - ' . gmdate( "Y/m/d h:i:s a", $photo->created_time );
		}

		$photo_details['url'] = $photo->link;

		// Generate HTML and taggify.
		// nl2br( linkify( psmb_instagram_taggify( $photo->caption->text ) ) ); // this is causing issues when saving the post.
		$photo_details['description'] = wpautop( wptexturize(
			psmb_instagram_taggify( $photo->caption->text )
		) );

		$photo_details['date'] = gmdate( "Y-m-d\TH:i:s\Z", $photo->created_time );

		$photo_details['thumbnail'] = $photo->images->standard_resolution->url;

		if ( $photo->type === 'video' ) {

			$photo_details['thumbnail'] = $photo->images->low_resolution->url;

			$photo_details['description'] = $photo->videos->standard_resolution->url . "\n\r" . $photo_details['description'];

			// error_log( json_encode( $photo ) );
		}

		$photo_details['tags'] = $photo->tags;

		// Empty!!
		// $photo_details['likes'] = $this->instagram->getMediaLikes( $photo->id );

		$photo_details['likes'] = $photo->likes->count;

		if ( $photo->location ) {

			$photo_details['location'] = $photo->location->name;
		}

		// $photo_details['embed_code'] = premise_output_photo( $photo->images->standard_resolution->url );

		// var_dump( $photo_details ); exit;

		return $photo_details;
	}


	/**
	 * Add Error from Exception
	 * to errors array.
	 *
	 * @param exception $e    Exception.
	 */
	private function add_error_from_exception( $e ) {

		$error = sprintf(
			__( 'An error occurred: %s' ),
			'<code>' . htmlspecialchars( $e->getMessage() ) . '</code>'
		);

		$this->errors[] = $error;
	}
}
