<?php
/**
 * WP-CLI report command.
 *
 * @package Lab01
 */

namespace Lab01\Cli;

use Lab01\Environment;
use Lab01\Secrets;
use WP_CLI;

/**
 * Prints the environment, so two developers can compare their setups line by line.
 */
final class ReportCommand {

	/**
	 * Show environment type, versions, debug settings and secret status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lab01 report
	 *
	 * @when after_wp_load
	 */
	public function report(): void {
		global $wpdb;

		$rows = array(
			array( 'Setting' => 'Environment type', 'Value' => wp_get_environment_type() ),
			array( 'Setting' => 'WordPress', 'Value' => get_bloginfo( 'version' ) ),
			array( 'Setting' => 'PHP', 'Value' => PHP_VERSION ),
			array( 'Setting' => 'Database server', 'Value' => $wpdb->db_server_info() ),
			array( 'Setting' => 'WP_DEBUG', 'Value' => WP_DEBUG ? 'true' : 'false' ),
			array( 'Setting' => 'WP_DEBUG_DISPLAY', 'Value' => WP_DEBUG_DISPLAY ? 'true' : 'false' ),
			array( 'Setting' => 'Search engines allowed', 'Value' => get_option( 'blog_public' ) ? 'yes' : 'no' ),
		);

		foreach ( Environment::REQUIRED_SECRETS as $key ) {
			$rows[] = array(
				'Setting' => $key,
				'Value'   => Secrets::mask( Secrets::get( $key ) ),
			);
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'Setting', 'Value' ) );

		$missing = Environment::missing_secrets();
		if ( ! empty( $missing ) ) {
			WP_CLI::warning( 'Missing secrets: ' . implode( ', ', $missing ) );
		}
	}
}
