<?php
/**
 * Environment-aware behaviour.
 *
 * @package Lab01
 */

namespace Lab01;

/**
 * Changes site behaviour based on wp_get_environment_type().
 *
 * One codebase, deployed everywhere. Only the configuration changes.
 */
final class Environment {

	/**
	 * Secrets this site needs to work correctly.
	 */
	public const REQUIRED_SECRETS = array( 'WEATHER_API_KEY', 'MAPS_API_KEY' );

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! self::is_production() ) {
			// Stop search engines from indexing local, development and staging sites.
			add_filter( 'pre_option_blog_public', '__return_zero' );
		}

		add_action( 'admin_bar_menu', array( self::class, 'add_admin_bar_badge' ), 1 );
		add_action( 'admin_notices', array( self::class, 'show_missing_secrets_notice' ) );
	}

	/**
	 * Whether this is the production environment.
	 */
	public static function is_production(): bool {
		return 'production' === wp_get_environment_type();
	}

	/**
	 * Names of required secrets that are not set.
	 *
	 * @return string[]
	 */
	public static function missing_secrets(): array {
		return array_values(
			array_filter(
				self::REQUIRED_SECRETS,
				static fn( string $key ): bool => null === Secrets::get( $key )
			)
		);
	}

	/**
	 * Show the environment type in the admin bar, so nobody edits production by mistake.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar instance.
	 */
	public static function add_admin_bar_badge( \WP_Admin_Bar $admin_bar ): void {
		$type   = wp_get_environment_type();
		$colors = array(
			'local'       => '#2271b1',
			'development' => '#00a32a',
			'staging'     => '#dba617',
			'production'  => '#d63638',
		);

		$admin_bar->add_node(
			array(
				'id'    => 'lab01-environment',
				'title' => sprintf(
					'<span style="background:%1$s;color:#fff;padding:2px 8px;border-radius:3px;">%2$s</span>',
					esc_attr( $colors[ $type ] ?? '#50575e' ),
					esc_html( strtoupper( $type ) )
				),
			)
		);
	}

	/**
	 * Warn administrators when a required secret is missing.
	 */
	public static function show_missing_secrets_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$missing = self::missing_secrets();
		if ( empty( $missing ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: comma-separated list of secret names. */
					__( 'Missing secrets: %s. Set them in the hosting environment, not in code.', 'lab01' ),
					implode( ', ', $missing )
				)
			)
		);
	}
}
