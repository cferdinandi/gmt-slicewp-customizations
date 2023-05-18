# GMT SliceWP Customizations
Add WP Rest API hooks into SliceWP, and customize approval template.

## The Endpoint

```bash
/wp-json/gmt-slicewp/v1/user/<user_email_address>
```

- `GET` request gets affiliate details
- `POST` request creates a new affiliate for approval

## Making a request with WordPress

You'll need to configure an options menu to get the domain, username, and password for authorization. I recommend using the [Application Passwords](https://wordpress.org/plugins/application-passwords/) plugin with this.

```php
// Get all user purchases
wp_remote_request(
	rtrim($options['wp_api_url'], '/') . '/wp-json/gmt-slicewp/v1/affiliate/' . $email,
	array(
		'method'    => 'GET',
		'headers'   => array(
			'Authorization' => 'Basic ' . base64_encode($options['wp_api_username'] . ':' . $options['wp_api_password']),
		),
	)
);
```