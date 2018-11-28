<?php
/*
 * Plugin Name: HSA Products
 * Version: 1.0
 * Description: HSA Product Pages
 * Author: Ryan Stimmler - Red Orange Design
 * Author URI: http://redorangedesign.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: hsa-products
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Ryan Stimmler
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


define('HSA_POST_TYPE_NAME', 'hsa-products');
define('RO_SCRIPT_DEBUG', true);


// Load plugin class files
require_once( 'includes/class-hsa-products.php' );
require_once( 'includes/class-hsa-products-settings.php' );

// Load plugin libraries

require_once( 'includes/lib/class-hsa-products-admin-api.php' );
require_once( 'includes/lib/class-hsa-products-post-type.php' );
require_once( 'includes/lib/class-hsa-products-taxonomy.php' );

/**
 * Returns the main instance of HSA_Products to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object HSA_Products
 */
function HSA_Products () {
	$instance = HSA_Products::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = HSA_Products_Settings::instance( $instance );
	}

	return $instance;
}

HSA_Products();