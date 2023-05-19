<?php

	/**
	 * Get an encoded email link
	 * @return string The email link
	 */
	function gmt_slicewp_get_email () {
		$email = antispambot(get_bloginfo('admin_email'));
		return '<a href="mailto:' . $email . '">' . $email . '</a>';
	};

	/**
	 * Add metadata to database
	 * @param  Integer $affiliate_id The affiliate ID
	 * @param  Array   $meta_data    The meta data to add
	 */
	function gmt_slicewp_update_affiliate_meta ($affiliate_id, $meta_data) {

		// Sanitize array
		$meta_data = _slicewp_array_wp_kses_post($meta_data);

		// Add each meta item
		foreach ( $meta_data as $key => $value ) {
			if (!is_string($key)) continue;
			slicewp_update_affiliate_meta(absint($affiliate_id), $key, $value);
		}

	}

	/**
	 * Get the affiliate commission data
	 * @param  Object $affiliate_id The affiliate ID
	 * @return Array                Affiliate commissions
	 */
	function gmt_slicewp_get_affiliate_commissions ($affiliate_id) {

		// Get all commission data
		$commissions_raw = slicewp_get_commissions(array(
			'affiliate_id' => $affiliate_id,
			'number' => -1,
		));

		// Create commission data
		$commissions = array(
			'paid' => array(
				'sales' => 0,
				'total' => 0
			),
			'unpaid' => array(
				'sales' => 0,
				'total' => 0
			),
			'total' => array(),
		);

		foreach ($commissions_raw as $commission) {

			// Update paid/unpaid total
			$status = $commission->get('status');
			$commissions[$status]['sales']++;
			$commissions[$status]['total'] += floatval($commission->get('amount'));

			// Get product details
			$order = new EDD_Payment($commission->get('reference'));
			$products = array();
			foreach ($order->cart_details as $product) {
				$products[] = str_replace(' — _', '', $product['name']);
			}

			// Add details to total
			$commissions['total'][] = array(
				'date' => strtotime($commission->get('date_created')) * 1000,
				'status' => $status,
				'price' => floatval($commission->get('reference_amount')),
				'earned' => floatval($commission->get('amount')),
				'products' => $products,
			);

		}

		return $commissions;

	}

	/**
	 * Get affiliate details and respond with them
	 * @param  Object   $affiliate The affiliate details
	 * @return Response            The API response
	 */
	function gmt_slicewp_get_affiliate_details ($affiliate) {

		// Convert to an array
		$affiliate = (object) $affiliate->to_array();

		// If not yet active, send back pending response
		if ($affiliate->status === 'pending') {
			return new WP_REST_Response(array(
				'code' => 202,
				'status' => 'pending',
				'message' => 'Affiliate application is awaiting approval. If you have any questions before then, please email ' . gmt_slicewp_get_email() . '.',
			), 200);
		}

		// If not yet active, send back pending response
		if ($affiliate->status !== 'active') {
			return new WP_REST_Response(array(
				'code' => 202,
				'status' => 'rejected',
				'message' => 'Your application for an affiliate account cannot be approved at this time. Please feel free to reply to send any questions to ' . gmt_slicewp_get_email() . '.',
			), 200);
		}

		// Otherwise, respond with affiliate details
		$commissions = gmt_slicewp_get_affiliate_commissions($affiliate->id);
		return new WP_REST_Response(array(
			'code' => 200,
			'status' => 'active',
			'data' => array(
				'name' => slicewp_get_affiliate_name($affiliate->id),
				'paypal' => $affiliate->payment_email,
				'slug' => slicewp_get_affiliate_meta($affiliate->id, 'custom_slug', true),
				'state' => slicewp_get_affiliate_meta($affiliate->id, 'state', true),
				'country' => slicewp_get_affiliate_meta($affiliate->id, 'country', true),
				'admin' => gmt_slicewp_get_email(),
				'commissions' => $commissions,
			)
		), 200);

	}

	/**
	 * Get affiliate data
	 * @param  Object $request The request object
	 * @return JSON            The REST API Response
	 */
	function gmt_slicewp_get_affiliate ($request) {

		// if no email, throw an error
		if (empty($request['email']) || !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
			return new WP_REST_Response(array(
				'code' => 500,
				'status' => 'invalid_email',
				'message' => 'We are unable to create an affiliate account at this time. Please try again. If the problem continues, please contact ' . gmt_slicewp_get_email() . '.',
			), 500);
		}

		// Get the affiliate
		$affiliate = slicewp_get_affiliate_by_user_email($request['email']);

		// If not yet an affiliate, send response
		if (empty($affiliate)) {
			return new WP_REST_Response(array(
				'code' => 400,
				'status' => 'no_account',
				'message' => 'There is no affiliate account associated with this email address.',
			), 400);
		}

		// Otherwise, respond with affiliate details
		return gmt_slicewp_get_affiliate_details($affiliate);

	}


	/**
	 * Register a new affiliate
	 * @param  Object $request The request object
	 * @return JSON            The REST API Response
	 */
	function gmt_slicewp_register_affiliate ($request) {

		// Get request parameters
		$params = $request->get_params();

		// if no email, throw an error
		if (empty($params['email']) || !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
			return new WP_REST_Response(array(
				'code' => 500,
				'status' => 'invalid_email',
				'message' => 'We are unable to create an affiliate account at this time. Please try again. If the problem continues, please contact ' . gmt_slicewp_get_email() . '.',
			), 500);
		}

		// Make sure all required fields are provided
		if (empty($params['first_name']) || empty($params['last_name']) || empty($params['payment_email']) || empty($params['website']) || empty($params['promotional_methods']) || empty($params['state']) || empty($params['country'])) {
			return new WP_REST_Response(array(
				'code' => 400,
				'status' => 'missing_fields',
				'message' => 'Please complete all required fields.',
			), 400);
		}

		// Check for existing user
		$user = get_user_by('email', $params['email']);

		// If there isn't one, create one
		// Otherwise, add a few details
		if ($user === false) {
			$user_id = wp_insert_user(array(
				'user_email' => sanitize_email($params['email']),
				'user_login' => sanitize_user($params['email']),
				'first_name' => sanitize_text_field($params['first_name']),
				'last_name'  => sanitize_text_field($params['last_name']),
				'user_pass'  => wp_generate_password(30, false)
			));
		} else {
			$user_id = wp_update_user(array(
				'ID' => $user->ID,
				'first_name' => sanitize_text_field($params['first_name']),
				'last_name'  => sanitize_text_field($params['last_name']),
			));
		}

		// If user wasn't created/updated, throw an error
		if (is_wp_error($user_id)) {
			return new WP_REST_Response(array(
				'code' => 500,
				'status' => 'no_user',
				'message' => 'We are unable to create an affiliate account at this time. Please try again. If the problem continues, please contact ' . gmt_slicewp_get_email() . '.',
			), 500);
		}

		// If custom_slug is provided, ensure it's not already in use
		if (!empty($params['custom_slug'])) {
			$slug_in_use = slicewp_get_affiliate_by_custom_slug($params['custom_slug']);
			if (!empty($slug_in_use)) {
				return new WP_REST_Response(array(
					'code' => 400,
					'status' => 'slug_in_use',
					'message' => 'Your requested affiliate ID is already being used by someone else. Please choose another, or leave that field blank to have one automatically generated for you.',
				), 400);
			}
		}

		// Check if an affiliate already exists
		$affiliate = slicewp_get_affiliate_by_user_email($request['email']);

		// If already an affiliate, send response
		if (!empty($affiliate)) {
			return gmt_slicewp_get_affiliate_details($affiliate);
		}

		// Create affiliate
		$affiliate_id = slicewp_insert_affiliate(array(
			'user_id' => absint($user_id),
			'date_created' => slicewp_mysql_gmdate(),
			'date_modified' => slicewp_mysql_gmdate(),
			'payment_email' => sanitize_email($params['payment_email']),
			'website' => esc_url_raw($params['website']),
			'status' => 'pending',
		));

		// If created, add meta data, send notification email, and return success
		if ($affiliate_id) {

			// Create meta_data
			$meta_data = array(
				'promotional_methods' => sanitize_text_field($params['promotional_methods']),
				'state' => sanitize_text_field($params['state']),
				'country' => sanitize_text_field($params['country']),
			);
			if (!empty($params['custom_slug'])) {
				$meta_data['custom_slug'] = sanitize_text_field($params['custom_slug']);
			}

			// Add meta_data
			gmt_slicewp_update_affiliate_meta($affiliate_id, $meta_data);

			// Send the admin notification email
			slicewp_send_email_notification_admin_new_affiliate_registration($affiliate_id);

			// Return success
			return gmt_slicewp_get_affiliate_details(slicewp_get_affiliate($affiliate_id));

		}

		// Otherwise, return an error
		return new WP_REST_Response(array(
			'code' => 500,
			'status' => 'no_user',
			'message' => 'We are unable to create an affiliate account at this time. Please try again. If the problem continues, please contact ' . gmt_slicewp_get_email() . '.',
		), 500);

	}

	function gmt_slicewp_api_register_routes () {
		register_rest_route('gmt-slicewp/v1', '/affiliate/(?P<email>\S+)', array(
			array(
				'methods' => 'GET',
				'callback' => 'gmt_slicewp_get_affiliate',
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
				'args' => array(
					'email' => array(
						'type' => 'string',
					),
				),
			),
			array(
				'methods' => 'POST',
				'callback' => 'gmt_slicewp_register_affiliate',
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
				'args' => array(
					'email' => array(
						'type' => 'string',
					),
				),
			)
		));
	}
	add_action('rest_api_init', 'gmt_slicewp_api_register_routes');