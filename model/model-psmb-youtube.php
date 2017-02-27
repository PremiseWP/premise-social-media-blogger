<?php
/**
 * Youtube Model
 *
 * @see google-api-php-client
 * @link https://developers.google.com/youtube/v3/
 *
 * @package Premise Social Media Blogger
 */

defined( 'ABSPATH' ) or exit;

/**
 * Premise Social Media Blogger Youtube class
 */
class Premise_Social_Media_Blogger_Youtube {

	/**
	 * Youtube Service
	 *
	 * @see Google_Service_YouTube class
	 *
	 * @var object
	 */
	private $yt_service;




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
	 * Set settings, plugin_page & defaults
	 *
	 * @param string $developer_key Google API key.
	 */
	function __construct( $developer_key ) {

		/**
		 * Youtube API.
		 *
		 * @link https://www.youtube.com/watch?v=jdqsiFw74Jk
		 */
		require_once PSMB_PATH . 'google-api-php-client/vendor/autoload.php';

		$client = new Google_Client();

		$client->setApplicationName( 'Premise Social Media Blogger' );

		$client->setDeveloperKey( $developer_key );

		$this->yt_service = new Google_Service_YouTube( $client );
	}




	/**
	 * Get YouTube Playlist
	 *
	 * @param  string $playlist_id Playlist ID.
	 *
	 * @return array              YouTube Playlists.
	 */
	public function get_playlist( $playlist_id ) {

		if ( ! $playlist_id ) {

			return;
		}

		$playlists = array();

		try {

			$params = array( 'id' => $playlist_id ); // UC70gZSTkSeqn61TJkpOm3bQ.

			$results = $this->yt_service->playlists->listPlaylists( 'contentDetails,snippet', $params );

			$playlists = $results->getItems();

		} catch ( Google_ServiceException $e ) {

			$this->add_error_from_exception( $e, 'service' );

		} catch ( Google_Exception $e ) {

			$this->add_error_from_exception( $e, 'client' );
		}

		return $playlists;
	}


	/**
	 * Get Playlist details:
	 * id,title,url,description,playlist_id
	 *
	 * @param  object $playlist Google_Service_YouTube_Playlist object.
	 *
	 * @return array         Playlist details array.
	 */
	public function get_playlist_details( $playlist ) {

		if ( ! $playlist instanceof Google_Service_YouTube_Playlist ) {

			return array();
		}

		$playlist_details = array(
			'id' => '',
			'title' => '',
			'url' => 'https://www.youtube.com/playlist/',
			'description' => '',
			'playlist_id' => '',
		);
		$playlist_id = 0;

		// var_dump( $playlist->getContentDetails(), $playlist->getSnippet() );

		$playlist_snippet = $playlist->getSnippet();

		$playlist_details['id'] = $playlist['id'];

		$playlist_details['title'] = $playlist_snippet['title'];

		$playlist_details['url'] .= $playlist['id'];

		$playlist_details['description'] = $playlist_snippet['description'];

		$playlist_details['playlist_id'] = $playlist->getContentDetails()->getRelatedPlaylists()->getUploads();

		return $playlist_details;
	}


	/**
	 * Get Playlist Video IDs
	 *
	 * @param string $playlist_id YouTube playlist ID.
	 * @param int    $max         Maximum of videos.
	 *
	 * @return array $video_ids YouTube video ids.
	 */
	public function get_playlist_video_ids( $playlist_id, $max = 5 ) {

		if ( ! $playlist_id ) {

			return array();
		}

		$video_ids = array();

		try {

			/**
			 * Params.
			 *
			 * @link https://developers.google.com/youtube/v3/docs/playlistItems/listy
			 */
			$params = array(
				'playlistId' => $playlist_id,
				'maxResults' => $max,
			);

			$results = $this->yt_service->playlistItems->listPlaylistItems( 'snippet', $params );

			$playlist_items = $results->getItems();

			// $videos_list = sprintf( '%d videos found:', count( $playlist_items ) );

			foreach ( (array) $playlist_items as $playlist_item ) {

				$video_ids[] = $playlist_item->getSnippet()->getResourceId()->getVideoId();
			}
		} catch ( Google_ServiceException $e ) {

			$this->add_error_from_exception( $e, 'service' );

		} catch ( Google_Exception $e ) {

			$this->add_error_from_exception( $e, 'client' );
		}

		return $video_ids;
	}


	/**
	 * Get Videos
	 * to retrieve other details like Category & Tags
	 * which are not available in the playlist items.
	 *
	 * @param array $video_ids YouTube video ids.
	 *
	 * @return array Youtube video items.
	 */
	public function get_videos( $video_ids ) {

		$videos = array();

		try {

			$params = array( 'id' => implode( ',', $video_ids ) );

			$results = $this->yt_service->videos->listVideos( 'snippet', $params );

			$videos = $results->getItems();

		} catch ( Google_ServiceException $e ) {

			$this->add_error_from_exception( $e, 'service' );

		} catch ( Google_Exception $e ) {

			$this->add_error_from_exception( $e, 'client' );
		}

		return $videos;
	}


	/**
	 * Get Video details:
	 * id,title,url,description,date,thumbnail,tags,category,embed_code
	 *
	 * @param  object $video Google_Service_YouTube_Video object.
	 *
	 * @return array         Video details array.
	 */
	public function get_video_details( $video ) {

		if ( ! $video instanceof Google_Service_YouTube_Video ) {

			return array();
		}

		$video_details = array(
			'id' => '',
			'title' => '',
			'url' => 'https://www.youtube.com/watch?v=',
			'description' => '',
			'date' => '',
			'thumbnail' => '',
			'tags' => array(),
			'category' => '',
			'embed_code' => '',
		);

		$video_snippet = $video->getSnippet();

		$video_details['id'] = $video['id'];

		$video_details['title'] = $video_snippet->getTitle();

		$video_details['url'] .= $video['id'];

		// Generate HTML links and line breaks.
		$video_details['description'] = nl2br( linkify( $video_snippet->getDescription() ) );

		$video_details['date'] = $video_snippet->getPublishedAt();

		$video_details['thumbnail'] = $video_snippet->getThumbnails()->getMaxres()['url'];

		$video_details['tags'] = $video_snippet->tags;

		/*$thumbnails_html = '<img src="' . $thumbnail['url'] . '" width="480" height="360" />';

		$tags_html = 'Tags: ' . implode( ', ', $video_snippet->tags );

		var_dump( $video_snippet );*/

		$category_id = $video_snippet->categoryId;

		if ( $category_id ) {

			// Get Video Category.
			try {

				$params = array( 'id' => $category_id );

				$results = $this->yt_service->videoCategories->listVideoCategories( 'snippet', $params );

				$categories = $results->getItems();

				foreach ( (array) $categories as $category ) {

					$video_details['category'] = $category->getSnippet()['title'];
				}
			} catch ( Google_ServiceException $e ) {

				$this->add_error_from_exception( $e, 'service' );

			} catch ( Google_Exception $e ) {

				$this->add_error_from_exception( $e, 'client' );
			}
		}

		$video_details['embed_code'] = premise_output_video( $video['id'] );

		return $video_details;
	}


	/**
	 * Add Error from Exception
	 * to errors array.
	 *
	 * @param exception $e    Exception.
	 * @param string    $type Error type: service or client.
	 */
	private function add_error_from_exception( $e, $type = 'service' ) {

		$error = sprintf(
			__( 'A %s error occurred: %s' ),
			$type,
			'<code>' . htmlspecialchars( $e->getMessage() ) . '</code>'
		);

		$this->errors[] = $error;
	}
}
