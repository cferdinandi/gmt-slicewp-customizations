<?php

	/**
	 * Subscribe the affiliate to the mailing list.
	 * @param int $affiliate_id
	 */
	function gmt_slicewp_subscribe_affiliate_convertkit( $affiliate_id ) {

		// Check for affiliate id
		if (empty($affiliate_id)) return;

		// Check if we are reviewing the affiliate
		if ('review_affiliate' != $_POST['slicewp_action']) return;

		// Check if the affiliate has 'active' status
		$affiliate = slicewp_get_affiliate($affiliate_id);
		if ('active' != $affiliate->get('status')) return;

		// Prepare the affiliate data for mailing list
		$user = new WP_User($affiliate->get('user_id'));
		$subscriber_data = array(
			'email' => $user->user_email,
			'first_name' => !empty($user->first_name) ? $user->first_name : '',
			'last_name' => !empty($user->last_name) ? $user->last_name : '',
		);

		// Subscribe the affiliate to the mailing list
		$initialized = slicewp()->services['convertkit']->init(slicewp_get_setting('convertkit_api_key'));

		// Check for errors
		if (!$initialized) {
			slicewp_add_log( 'ConvertKit API: init() failed. Error: ' . slicewp()->services['convertkit']->get_last_error() );
			return;
		}

		// Add subscriber
		slicewp()->services['convertkit']->add_subscriber($subscriber_data);

	}
	add_action( 'slicewp_update_affiliate', 'gmt_slicewp_subscribe_affiliate_convertkit', 30 );

	// Remove default SliceWP Pro actions
	remove_action( 'slicewp_register_affiliate', 'slicewp_subscribe_affiliate_convertkit', 30 );
	remove_action( 'slicewp_update_affiliate', 'slicewp_subscribe_affiliate_convertkit', 30 );