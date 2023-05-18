<?php

	/**
	 * Show requested custom slug on approval page
	 * @param  String $field_type The form field section
	 * @return HTML               The HTML to display
	 */
	function gmt_slicewp_show_custom_slug ($field_type) {

		// Only run on review page
		if ($field_type !== 'review_affiliate') return '';

		// Get affiliate page details
		$affiliate_id = ( ! empty( $_GET['affiliate_id'] ) ? sanitize_text_field( $_GET['affiliate_id'] ) : 0 );
		$sub_page	  = ( ! empty( $_GET['subpage'] ) ? sanitize_text_field( $_GET['subpage'] ) : '' );

		?>
		<!-- Custom Slug -->
		<div class="slicewp-field-wrapper slicewp-field-wrapper-inline">

			<div class="slicewp-field-label-wrapper">
				<label for="slicewp-custom-slug">
					<?php echo __( 'Custom Slug', 'slicewp' ); ?>
				</label>
			</div>

			<?php

				$field_value = slicewp_get_affiliate_meta( $affiliate_id, 'custom_slug', true );
				$is_locked   = true;

			?>

			<div class="<?php echo ( $is_locked ? 'slicewp-field-locked' : '' ); ?>">

				<input id="slicewp-custom-slug" name="custom_slug" type="text" value="<?php echo esc_attr( $field_value ); ?>" <?php echo ( $is_locked ? 'readonly' : '' ); ?>>

				<?php if ( $is_locked ): ?>
					<a href="#"><span class="dashicons dashicons-lock"></span><?php echo __( 'Unlock', 'slicewp' ); ?></a>
				<?php endif; ?>

			</div>

			<div class="slicewp-field-notice slicewp-field-notice-warning" <?php echo ( ! empty( $field_value ) && ! $is_locked ? 'style="display: block;"' : '' ); ?>><p><?php echo sprintf( __( "%sImportant note%s: Changing the affiliate's custom slug will break all of the affiliate's referral links using the current slug.", 'slicewp' ), '<strong>', '</strong>', '<strong>', '</strong>' ); ?></p></div>

		</div><!-- / Custom Slug -->
		<?php
	}
	add_action( 'slicewp_admin_form_fields', 'gmt_slicewp_show_custom_slug' );