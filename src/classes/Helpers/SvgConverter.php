<?php
/**
 * SVG Converter Helper
 *
 * @package easy-watermark
 */

namespace EasyWatermark\Helpers;

/**
 * Class SvgConverter
 */
class SvgConverter {

	/**
	 * Cache directory name
	 *
	 * @var string
	 */
	const CACHE_DIR = 'easy-watermark-cache';

	/**
	 * Gets a PNG path for a given SVG attachment ID.
	 * Converts if necessary and caches the result.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|false Path to PNG or false on failure/not SVG.
	 */
	public static function get_png_path( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return false;
		}

		$mime = get_post_mime_type( $attachment_id );
		
		// If it's not SVG, return false (processor will handle it normally)
		if ( 'image/svg+xml' !== $mime && 'image/svg' !== $mime && ! preg_match( '/\.svg$/i', $file_path ) ) {
			return false;
		}

		// Check for Imagick
		if ( ! class_exists( '\Imagick' ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/' . self::CACHE_DIR;

		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		// Create a unique hash for the cached file based on ID and modification time
		// This ensures that if the SVG is updated, we generate a new PNG
		$mtime      = filemtime( $file_path );
		$filename   = basename( $file_path, '.svg' );
		$cache_file = $cache_dir . '/' . $filename . '-' . $attachment_id . '-' . $mtime . '.png';

		// Return cached file if exists
		if ( file_exists( $cache_file ) ) {
			return $cache_file;
		}

		// Cleanup old cache files for this attachment
		self::cleanup_old_cache( $cache_dir, $filename, $attachment_id );

		// Convert
		try {
			$imagick = new \Imagick();
			$imagick->setBackgroundColor( new \ImagickPixel( 'transparent' ) );
			
			// Set resolution before reading
			$imagick->setResolution( 150, 150 );
			
			$imagick->readImage( $file_path );
			
			// Resize to 720px width
			$width  = $imagick->getImageWidth();
			$height = $imagick->getImageHeight();
			
			if ( $width > 0 ) {
				$new_width  = 720;
				$new_height = (int) ( ( $height / $width ) * $new_width );
				
				$imagick->resizeImage( $new_width, $new_height, \Imagick::FILTER_LANCZOS, 1 );
			}
			
			$imagick->setImageFormat( 'png' );
			$imagick->writeImage( $cache_file );
			
			$imagick->clear();
			$imagick->destroy();

			return $cache_file;

		} catch ( \Exception $e ) {
			error_log( 'Easy Watermark SVG Runtime Conversion Failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Cleans up old cache files for a specific attachment
	 *
	 * @param string $dir           Cache directory.
	 * @param string $filename_base Base filename.
	 * @param int    $id            Attachment ID.
	 * @return void
	 */
	private static function cleanup_old_cache( $dir, $filename_base, $id ) {
		$files = glob( $dir . '/' . $filename_base . '-' . $id . '-*.png' );
		if ( $files ) {
			foreach ( $files as $file ) {
				@unlink( $file );
			}
		}
	}
}
