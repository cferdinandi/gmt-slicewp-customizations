<?php

/**
 * Plugin Name: GMT SliceWP Customizations
 * Plugin URI: https://github.com/cferdinandi/gmt-slicewp-customizations/
 * GitHub Plugin URI: https://github.com/cferdinandi/gmt-slicewp-customizations/
 * Description: Add WP Rest API hooks into SliceWP.
 * Version: 1.2.1
 * Author: Chris Ferdinandi
 * Author URI: http://gomakethings.com
 * License: GPLv3
 */


// Security
if (!defined('ABSPATH')) exit;

// Require files
require_once('api.php');
require_once('templates.php');
require_once('hooks.php');