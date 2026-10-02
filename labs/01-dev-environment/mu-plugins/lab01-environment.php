<?php
/**
 * Plugin Name: Lab 01 – Environment
 * Description: Environment-aware behaviour and secret loading for Lab 01.
 *
 * The real code lives outside the web root (LAB01_APP_DIR), so files like
 * .env and vendor/ can never be downloaded through the browser.
 *
 * @package Lab01
 */

if ( ! defined( 'LAB01_APP_DIR' ) ) {
	return;
}

$lab01_autoload = LAB01_APP_DIR . '/vendor/autoload.php';

if ( ! is_readable( $lab01_autoload ) ) {
	// Fail loudly in local, quietly everywhere else.
	if ( 'local' === wp_get_environment_type() ) {
		wp_die( 'Lab 01: run "composer install" first.' );
	}
	return;
}

require_once $lab01_autoload;

Lab01\Environment::boot();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'lab01', Lab01\Cli\ReportCommand::class );
}
