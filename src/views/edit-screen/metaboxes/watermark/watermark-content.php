<?php
/**
 * Watermark content metabox
 *
 * @package easy-watermark
 */

use EasyWatermark\Features\WatermarkPreview;

global $post;

?>
<div class="watermark-content-metabox">

	<div class="image-content">
		<input class="watermark-id" name="watermark[attachment_id]" type="hidden" value="<?php echo esc_html( $attachment_id ); ?>" />
		<input class="watermark-url" name="watermark[url]" type="hidden" value="<?php echo esc_attr( $url ); ?>" />
		<input class="watermark-mime-type" name="watermark[mime_type]" type="hidden" value="<?php echo esc_attr( $mime_type ); ?>" />

		<div class="select-image-button">
			<a class="button-secondary" data-choose="<?php esc_attr_e( 'Choose Watermark Image', 'easy-watermark' ); ?>" data-button-label="<?php esc_attr_e( 'Set as Watermark Image', 'easy-watermark' ); ?>" href="#"><?php esc_html_e( 'Select/Upload Image', 'easy-watermark' ); ?></a>
		</div>

		<div class="watermark-image">
			<p class="description"><?php esc_html_e( 'Click on image to change it.', 'easy-watermark' ); ?></p>
			<img src="<?php echo esc_attr( $url ); ?>" style="max-width: 350px; height: auto;" />
			<style>
				/* Force visibility of the Opacity table and its children without breaking styles */
				.watermark-image table.form-table { 
					display: table !important; 
					opacity: 1 !important;
					visibility: visible !important;
				}
				.watermark-image table.form-table tr { 
					display: table-row !important; 
					opacity: 1 !important;
					visibility: visible !important;
				}
				.watermark-image table.form-table th, 
				.watermark-image table.form-table td { 
					display: table-cell !important; 
					opacity: 1 !important;
					visibility: visible !important;
				}
				
				/* Force visibility of the input container and input itself */
				.watermark-image .form-field {
					display: flex !important;
					align-items: center !important;
					opacity: 1 !important;
					visibility: visible !important;
				}
				
				.watermark-image input#opacity { 
					display: inline-block !important; 
					opacity: 1 !important;
					visibility: visible !important;
				}
				
				.watermark-image .form-field-append {
					display: inline-block !important;
				}

				.watermark-image span.form-field-text {
					height: 28px !important;
					line-height: 28px !important;
					display: inline-block !important;
				}
			</style>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Opacity', 'easy-watermark' ); ?></th>
						<td>
							<div class="form-field">
								<input type="number" size="3" min="0" max="100" step="0.1" name="watermark[opacity]" id="opacity" value="<?php echo esc_attr( $opacity ); ?>" />
								<div class="form-field-append">
									<span class="form-field-text"> %</span>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="text-content">
		<input class="watermark-text" name="watermark[text]" type="text" value="<?php echo esc_attr( $text ); ?>" placeholder="<?php esc_attr_e( 'Watermark text', 'easy-watermark' ); ?>" />
		<p class="description"><?php esc_html_e( 'You can use placeholders listed in "Placeholders" metabox.', 'easy-watermark' ); ?></p>
		<div class="text-preview" data-src="<?php echo esc_url( WatermarkPreview::get_url( 'text', $post->ID ) ); ?>"></div>
	</div>

</div>
