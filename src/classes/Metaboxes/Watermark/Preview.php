<?php
/**
 * Metabox class
 *
 * @package easy-watermark
 */

namespace EasyWatermark\Metaboxes\Watermark;

use EasyWatermark\Core\View;
use EasyWatermark\Features\WatermarkPreview;
use EasyWatermark\Helpers\Image as ImageHelper;
use EasyWatermark\Metaboxes\WatermarkMetabox;
use EasyWatermark\Traits\Hookable;
use EasyWatermark\Watermark\Watermark;

/**
 * Metabox class
 */
class Preview extends WatermarkMetabox {

	use Hookable;

	/**
	 * Metabox position (normal|side|advanced)
	 *
	 * @var  string
	 */
	protected $position = 'side';

	/**
	 * Inits metabox
	 *
	 * @return void
	 */
	public function init() {
		$this->id    = 'preview';
		$this->title = __( 'Preview' );

		$this->hook();
	}

	/**
	 * Prepares params for metabox view
	 *
	 * @param  array  $params Params.
	 * @param  object $post Current post.
	 * @return array
	 */
	public function prepare_params( $params, $post ) {
		$watermark = Watermark::get( $post );

		$preview_image_id = $this->ensure_preview_image_id();

		if ( $preview_image_id ) {
			// Removed get_post check to avoid duplicate query as requested.
			// Validation is skipped in favor of performance.
		}

		$params['select_label'] = __( 'Select preview image', 'easy-watermark' );
		$params['change_label'] = __( 'Change preview image', 'easy-watermark' );
		$params['link_label']   = $preview_image_id ? $params['change_label'] : $params['select_label'];
		$params['has_image']    = (bool) $preview_image_id;

		$images          = [];
		$available_sizes = ImageHelper::get_available_sizes();

		// Remove small sizes to avoid low quality preview complaints.
		unset( $available_sizes['thumbnail'], $available_sizes['medium'] );

		if ( isset( $available_sizes['full'] ) ) {
			$full_size       = [ 'full' => $available_sizes['full'] ];
			unset( $available_sizes['full'] );
			$available_sizes = $full_size + $available_sizes;
		}

		foreach ( $available_sizes as $size => $label ) {
			$src            = WatermarkPreview::get_url( 'image', $post->ID, $size );
			$images[ $src ] = $label;
		}

		$params['images'] = $images;
		$params['popup']  = $this->get_preview_popup( $post->ID );

		return array_merge( $params, $watermark->get_params() );
	}

	/**
	 * Ensures preview image id is set
	 *
	 * @return int|false
	 */
	private function ensure_preview_image_id() {
		$preview_image_id = get_option( '_ew_preview_image_id' );

		if ( ! $preview_image_id ) {
			
			// Determine supported mime types based on GD capabilities
			$supported_types = [ 'image/jpeg', 'image/png', 'image/gif' ];
			
			if ( function_exists( 'gd_info' ) ) {
				$gd_info = gd_info();
				if ( isset( $gd_info['WebP Support'] ) && $gd_info['WebP Support'] ) {
					$supported_types[] = 'image/webp';
				}
				if ( isset( $gd_info['AVIF Support'] ) && $gd_info['AVIF Support'] ) {
					$supported_types[] = 'image/avif';
				}
			}

			$attachments = get_posts( [
				'post_type'      => 'attachment',
				'post_mime_type' => $supported_types,
				'posts_per_page' => 1,
				'orderby'        => 'rand',
				'fields'         => 'ids',
			] );

			if ( ! empty( $attachments ) ) {
				$preview_image_id = $attachments[0];
				update_option( '_ew_preview_image_id', $preview_image_id );
			}
		}

		return $preview_image_id;
	}

	/**
	 * Handles preview image selection
	 *
	 * @action wp_ajax_easy-watermark/preview_image
	 *
	 * @return void
	 */
	public function ajax_preview_image() {

		check_ajax_referer( 'preview_image', 'nonce' );

		if ( ! isset( $_REQUEST['attachment_id'] ) ) {
			wp_send_json_error( [
				'message' => __( 'No attachment id.', 'easy-watermark' ),
			] );
		}

		if ( ! isset( $_REQUEST['watermark_id'] ) ) {
			wp_send_json_error( [
				'message' => __( 'No watermark id.', 'easy-watermark' ),
			] );
		}

		$attachment_id = intval( $_REQUEST['attachment_id'] );
		$watermark_id  = intval( $_REQUEST['watermark_id'] );

		$updated = update_option( '_ew_preview_image_id', $attachment_id );

		if ( true === $updated || (int) get_option( '_ew_preview_image_id' ) === $attachment_id ) {
			wp_send_json_success( [
				'popup' => (string) $this->get_preview_popup( $watermark_id ),
			] );
		}

		wp_send_json_error( [
			'message' => __( 'Saving preview image failed.', 'easy-watermark' ),
		] );

	}

	/**
	 * Returns preview popup content
	 *
	 * @param  integer $watermark_id Watermark ID.
	 * @return View|null
	 */
	public function get_preview_popup( $watermark_id ) {

		$preview_image_id = $this->ensure_preview_image_id();

		$images          = [];
		$sizes           = [];
		$available_sizes = ImageHelper::get_available_sizes();

		// Remove small sizes to avoid low quality preview complaints.
		unset( $available_sizes['thumbnail'], $available_sizes['medium'] );

		// Ensure 'full' size is first.
		if ( isset( $available_sizes['full'] ) ) {
			$full_size       = [ 'full' => $available_sizes['full'] ];
			unset( $available_sizes['full'] );
			$available_sizes = $full_size + $available_sizes;
		}

		if ( $preview_image_id ) {
			$meta  = get_post_meta( $preview_image_id, '_wp_attachment_metadata', true );
			if ( is_array( $meta ) && isset( $meta['sizes'] ) ) {
				$sizes = $meta['sizes'];
			}
		}

		foreach ( $available_sizes as $size => $label ) {
			if ( 'full' === $size || array_key_exists( $size, $sizes ) ) {
				$src            = WatermarkPreview::get_url( 'image', $watermark_id, $size );
				$images[ $src ] = $label;
			}
		}

		return new View( 'edit-screen/metaboxes/watermark/preview-popup', [
			'images' => $images,
		] );
	}
}
