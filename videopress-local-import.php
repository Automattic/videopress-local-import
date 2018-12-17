<?php
/**
 * Plugin Name:     VideoPress Local Import
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Move VideoPress shortcodes to local files
 * Author:          Automattic
 * Author URI:      https://automattic.com
 * Text Domain:     videopress-local-import
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Videopress_Local_Import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

require_once 'includes/video-custom-post-type.php';
require_once 'includes/class-videopress-local-import.php';

/**
 * Simple class to add the shortcode handler.
 */
class VideoPressLocal {

	/**
	 * The one instance of VideoPress Local.
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * API Endpoint
	 *
	 * @var string
	 */
	private static $endpoint = 'https://public-api.wordpress.com/rest/v1.1/videos/';

	/**
	 * Video to save.
	 *
	 * @var object
	 */
	private $video;

	/**
	 * Instantiate or return the one VideoPress Local instance.
	 *
	 * @return VideoPressLocal
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Construct the object.
	 *
	 * @return void
	 */
	public function __construct() {
		add_shortcode( 'wpvideo', array( $this, 'shortcode' ) );
	}

	/**
	 * Helper logger
	 *
	 * @param string $something Something to log to the front-end of the site.
	 * @return void
	 */
	private function log( $something = '' ) {
		printf( '<script async>console.log(%s)</script>', wp_json_encode( $something ) );
	}

	/**
	 * Start of the shortcode handler
	 *
	 * @param mixed $args Arguments for the shortcode.
	 * @return string Shortcode
	 */
	public function shortcode( $args ) {
		$this->log( $args );
		$found_video = $this->find_video( $args[0] );

		// If we have a video, show from info in the DB.
		if ( 0 !== $found_video ) {
			$this->log( $found_video );
			$attach_id = get_post_meta( $found_video->ID, '_videopress_local_video', true );
			$video     = get_post( $attach_id );
			$thumbnail = has_post_thumbnail( $found_video->ID ) ? get_the_post_thumbnail_url( $attach_id, 'large' ) : '';
			return do_shortcode( sprintf( '[video src=%s poster=%s]', esc_url( $video->guid ), esc_url( $thumbnail ) ) );

		} else {
			$video = $this->get_video( $args[0] );
			return do_shortcode( sprintf( '[video src=%s]', $video->original ) );
		}
	}

	/**
	 * Find the video, based on the guid.
	 *
	 * @param string $guid Unique ID for the video.
	 * @return mixed WP_Post of video or 0 if not found.
	 */
	private function find_video( $guid = '' ) {
		$args = [
			'post_type' => 'video',
			'name'      => $guid,
		];

		$query = new WP_Query( $args );

		if ( $query->post ) {
			return $query->post;
		} else {
			return 0;
		}

	}

	/**
	 * Get the video from the WordPress.com API
	 *
	 * @param string $guid ID of the video to lookup.
	 * @return object      API response back from the WordPress.com API.
	 */
	public function get_video( $guid = '' ) {
		$url      = sprintf( '%s%s', $this::$endpoint, $guid );
		$response = wp_remote_get( esc_url( $url ) );
		$body     = wp_remote_retrieve_body( $response );

		// Let's set this as a prop so we can use it on the shutdown action.
		$this->video = json_decode( $body );

		// Ok, we have it. Let's save it.
		if ( ! is_admin() ) {
			add_action( 'shutdown', array( $this, 'save_video' ), 10 );
		}
		return $this->video;

	}


	/**
	 * We have a video to save, so let's do that.
	 *
	 * @return object
	 */
	public function save_video() {
		return new VideoPressLocal_Import( $this->video );
	}
}
/**
 * Wrapper function to return the one VideoPress Local instance.
 *
 * @return VideoPressLocal
 */
function videopress_local() {
	return VideoPressLocal::instance();
}

// Kick off the class.
add_action( 'init', 'videopress_local' );
