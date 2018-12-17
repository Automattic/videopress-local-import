<?php
/**
 * VideoPress Local Import Video Factory
 *
 * @package   Videopress_Local_Import
 */

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Factory for adding VideoPress videos to WordPress.
 */
class VideoPress_Local_Import {
	/**
	 * Is there a custom background color?
	 *
	 * @var bool
	 */
	public $bg_color;

	/**
	 * Description of the video.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Length of video, in milliseconds.
	 *
	 * @var int
	 */
	public $duration;

	/**
	 * Globaly unique identifier.
	 *
	 * @var string
	 */
	public $guid;

	/**
	 * Height of original video.
	 *
	 * @var int
	 */
	public $height;

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	public $original;

	/**
	 * URL to poster
	 *
	 * @var string
	 */
	public $poster;

	/**
	 * Parent Post ID
	 *
	 * @var string
	 */
	public $post_id = 0;

	/**
	 * Post Type
	 *
	 * @var string
	 */
	public $post_type = 'video';

	/**
	 * Status of video
	 *
	 * @var string
	 */
	public $post_status;

	/**
	 * Rating of video
	 *
	 * @var string
	 */
	public $rating;

	/**
	 * Title of video.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Date video was uploaded.
	 *
	 * @var string
	 */
	public $upload_date;

	/**
	 * Width of original video.
	 *
	 * @var int
	 */
	public $width;

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	public $timeout;

	/**
	 * Kick off the factory.
	 *
	 * @param object $video Video to add to the WordPress library.
	 */
	public function __construct( $video ) {
		$this->title         = $video->title;
		$this->description   = ( ! empty( $video->description ) ) ? $video->description : '';
		$this->guid          = $video->guid;
		$this->width         = $video->width;
		$this->height        = $video->height;
		$this->duration      = $video->duration;
		$this->display_embed = $video->display_embed;
		$this->rating        = $video->rating;
		$this->poster        = $video->poster;
		$this->original      = $video->original;
		$this->watermark     = $video->watermark;
		$this->bg_color      = $video->bg_color;
		$this->upload_date   = $video->upload_date;
		$this->timeout       = 120;

		// Are we doing an update?
		$this->update      = $this->is_this_an_update();
		$this->post_status = $this->publish_status();

		// Let's create an array of data that we can use to insert/update.
		$this->postarr = $this->setup_post_array();

		// Is this an update, or an insert?
		if ( ! $this->update ) {
			$this->add_video();
		} else {
			$this->update_video();
		}

		$this->media_sideload_image( $this->poster, true, $this->upload_date );
		$this->media_sideload_image( $this->original, false, $this->upload_date );

		// And then the post_meta.
		$this->update_post_meta();
	}

	/**
	 * Do we have a matching video in the DB?
	 *
	 * @return boolean
	 */
	public function is_this_an_update() {

		// Do we have anything in the DB?
		$args  = array(
			'post_type'   => $this->post_type,
			'post_status' => 'any',
			'meta_query'  => array(
				array(
					'key'   => 'guid',
					'value' => $this->guid,
				),
			),
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() && ! empty( $query->post->ID ) ) {
			$this->post_id = $query->post->ID;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build an array of data that we can use for insert/update post.
	 *
	 * @return array Post status
	 */
	public function setup_post_array() {
		return array(
			'ID'            => $this->post_id,
			'post_author'   => 1,
			'post_date'     => $this->upload_date,
			'post_date_gmt' => $this->upload_date,
			'post_content'  => $this->description,
			'post_title'    => $this->title,
			'post_status'   => $this->post_status,
			'post_type'     => $this->post_type,
			'post_name'     => $this->guid,
		);
	}

	/**
	 * Wrapper for wp_inser_post.
	 */
	public function add_video() {
		$pid           = wp_insert_post( $this->postarr );
		$this->post_id = $pid;

		if ( ! is_wp_error( $pid ) ) {
			$this->success = true;
		}
	}

	/**
	 * Wrapper for wp_update_post
	 */
	public function update_video() {
		$pid = wp_update_post( $this->postarr );
		if ( ! is_wp_error( $pid ) ) {
			$this->success = true;
		}
	}

	/**
	 * Wrapper for wp_delete_post
	 */
	public function delete_video() {
		$post = wp_delete_post( $this->post_id );
		if ( $post ) {
			$this->deleted = true;
		} else {
			$this->success = false;
		}
	}

	/**
	 * Update the post.
	 *
	 * @return void
	 */
	private function update_post_meta() {
		update_post_meta( $this->post_id, 'description', wp_kses_post( $this->description ) );
		update_post_meta( $this->post_id, 'guid', sanitize_title( $this->guid ) );
	}

	/**
	 * Sideload the media into the media library, and set as the post thumbnail.
	 *
	 * @param string  $media     Media to attach.
	 * @param boolean $thumbnail Do we want to set the post thumbnail.
	 * @param string  $time      Where in the uploads folder do we want to check for the file or save.
	 * @return void
	 */
	private function media_sideload_image( $media, $thumbnail = false, $time = '' ) {
		$this->featured_image_id = $this->handle_media_sideload_image( $media, $this->post_id, $this->description, 'id' );
		if ( ! $thumbnail ) {

			$returns = $this->handle_media_sideload_video( $media, $time );

		}
		$this->featured_image_error = ( is_wp_error( $this->featured_image_id ) ) ? $this->featured_image_id->get_error_message() : false;
		$this->featured_image_added = ( ! $this->featured_image_error ) ? set_post_thumbnail( $this->post_id, $this->featured_image_id ) : 0;
	}

	/**
	 * Handle loading video and then attach it.
	 *
	 * @param string $media Media to attach.
	 * @param string $time  Where in the uploads folder do we want to check for the file or save.
	 * @return int          Attachement ID
	 */
	private function handle_media_sideload_video( $media = '', $time = '' ) {

		$file_name = basename( $media );

		// Get the base directory where we'll store the file. This will just be wp-content/uploads.
		$upload_dir = wp_upload_dir( $this->get_upload_time_string( $time ) );
		$upload_dir = trailingslashit( $upload_dir['path'] );

		// Give the file a name that we'll use to write to disk. And build the path to the file including the directory and file name.
		$file_path = $upload_dir . $file_name;

		// Does the file exist here?
		// Let's see if it exists already without traversing the file system.
		$exists_in_db = attachment_url_to_postid( $file_path );

		if ( ! file_exists( $file_path ) ) {
			// We don't have the file, so let's download it.
			$file = wp_remote_get( $media, [ 'timeout' => apply_filters( 'videopress_local_import_timeout', $this->timeout ) ] );

			if ( is_wp_error( $file ) ) {
				return $file;
			}

			if ( empty( $file['headers'] ) ) {
				return new WP_Error( 'Missing Headers' );
			}

			// Open a resource for writing the file, write the data, and then close the resource.
			// @todo Need to update this at somepoint to use WP_Filesystem.
			$resource = fopen( $file_path, 'w' );
			fwrite( $resource, $file['body'] );
			fclose( $resource );
		}

		// Make an attachment here.
		$attach_id = $this->add_attachment( $file_path, $time );

		return $file_path;
	}

	/**
	 * Add attachment data for file.
	 *
	 * @param string  $file_path   Path to the attachment.
	 * @param string  $time        Time string to use for the directory structure.
	 * @param boolean $thumbnail   Should we make this the post thumbnail.
	 * @return int                 Attachment ID.
	 */
	private function add_attachment( $file_path = '', $time = '', $thumbnail = false ) {

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $file_path ), null );

		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir( $this->get_upload_time_string( $time ) );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $file_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => $this->title,
			'post_content'   => $this->description,
			'post_status'    => 'inherit',
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $file_path, $this->post_id );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		if ( $thumbnail ) {
			set_post_thumbnail( $post_parent, $attach_id );
		}

		update_post_meta( $this->post_id, '_videopress_local_video', $attach_id );

		return $attach_id;
	}

	/**
	 * Get the proper timestamp for the uploads dir
	 *
	 * @param string $time Uploaded time to format.
	 * @return string
	 */
	private function get_upload_time_string( $time = '' ) {
		$date = new DateTime( $time );
		return $date->format( 'Y/mm' );
	}

	/**
	 * Downloads an image from the specified URL and attaches it to a post.
	 * Awaiting a core fix so that we don't have to dulicate this function.
	 * https://github.com/WordPress/WordPress/pull/198
	 * https://core.trac.wordpress.org/ticket/19629
	 *
	 * @since 2.6.0
	 * @since 4.2.0 Introduced the `$return` parameter.
	 *
	 * @param string $file    The URL of the image to download.
	 * @param int    $post_id The post ID the media is to be associated with.
	 * @param string $desc    Optional. Description of the image.
	 * @param string $return  Optional. Accepts 'html' (image tag html) or 'src' (URL), or 'id' (attachment ID). Default 'html'.
	 * @return string|WP_Error Populated HTML img tag on success, WP_Error object otherwise.
	 */
	private function handle_media_sideload_image( $file, $post_id, $desc = null, $return = 'html' ) {
		if ( ! empty( $file ) ) {

			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			if ( ! $matches ) {
				return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
			}

			$file_array         = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, $post_id, $desc );

			// If we want a simple out to get to the image ID, make it available here.
			if ( 'id' === $return ) {
				return $id;
			}

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				unlink( $file_array['tmp_name'] );
				return $id;
			}

			$src = wp_get_attachment_url( $id );
		}

		// Finally, check to make sure the file has been saved, then return the HTML.
		if ( ! empty( $src ) ) {
			if ( 'src' === $return ) {
				return $src;
			}

			$alt  = isset( $desc ) ? esc_attr( $desc ) : '';
			$html = "<img src='$src' alt='$alt' />";
			return $html;
		} else {
			return new WP_Error( 'image_sideload_failed' );
		}
	}

	/**
	 * What should our publish status be for the video update.
	 *
	 * @return string  Publish status.
	 */
	private function publish_status() {
		return 'publish';
	}

}
