<?php
/**
 * Plugin Name: Webneat Deploy
 * Version: 1.0.0
 * Plugin URI: http://www.webneat.ca/webneat-deploy
 * 
 * Description: This plugins helps system addmin deployment easy.
 * Author: Amir Nasser (Webneat)
 * Author URI: http://www.webneat.ca/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: webneat
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Amir Nasser (Webneat)
 * @since 1.0.0
 * 
 * composer require "twig/twig:^2.0"
 */

 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-webdeploy-admin-api.php';
require_once 'includes/class-webdeploy.php';
require_once 'includes/class-webdeploy-settings.php';

// Load plugin libraries.
//require_once 'includes/lib/class-woodbine-tip-post-type.php';
//require_once 'includes/lib/class-woodbine-tip-taxonomy.php';
require_once 'vendor/autoload.php';


/**
 * Returns the main instance of webdeploy to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object webdeploy
 */
function webdeploy() {
	$instance = WebDeploy::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WebDeploy_Settings::instance( $instance );
	}

	return $instance;
}

webdeploy();

//echo get_option();die;