<?php
/**
 * SVG Support Feature
 *
 * @package easy-watermark
 */

namespace EasyWatermark\Features;

use EasyWatermark\Traits\Hookable;

/**
 * Class SvgSupport
 */
class SvgSupport {

	use Hookable;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->hook();
	}

	/**
	 * Hooks into WordPress
	 *
	 * @return void
	 */
	public function hook() {
		add_filter( 'upload_mimes', [ $this, 'upload_mimes' ] );
	}

	/**
	 * Allows SVG upload
	 *
	 * @param  array $mimes Mime types.
	 * @return array
	 */
	public function upload_mimes( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
}
