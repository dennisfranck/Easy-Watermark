<?php
/**
 * Abstract placeholder
 *
 * @package easy-watermark
 */

namespace EasyWatermark\Placeholders\Attachment;

use EasyWatermark\Placeholders\Abstracts\StringPlaceholder;

/**
 * Abstract placeholder
 */
class AttachmentSize extends StringPlaceholder {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->slug    = 'attachment_size';
		$this->name    = __( 'Attachment size', 'easy-watermark' );
		$this->example = __( '2 MB', 'easy-watermark' );

	}

	/**
	 * Tells whether placeholder is valid
	 *
	 * @param  Resolver $resolver Placeholders resolver instance.
	 * @return boolean
	 */
	public function is_valid( $resolver ) {
		return (bool) $resolver->get_attachment();
	}

	/**
	 * Resolves placeholder
	 *
	 * @param  Resolver $resolver Placeholders resolver instance.
	 * @return string
	 */
	public function resolve( $resolver ) {
		$file = get_attached_file( $resolver->get_attachment()->ID );
		if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return '';
		}
		$size = filesize( $file );
		if ( false === $size ) {
			return '';
		}
		return size_format( $size );
	}
}
