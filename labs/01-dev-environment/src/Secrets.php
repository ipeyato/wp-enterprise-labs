<?php
/**
 * Secret lookup.
 *
 * @package Lab01
 */

namespace Lab01;

use Dotenv\Dotenv;

/**
 * Reads secrets without hardcoding them in source code.
 *
 * Lookup order:
 * 1. vip_get_env_var() – on WordPress VIP, secrets are set with `vip config envvar set`.
 * 2. Real environment variables – how most hosts and container platforms inject secrets.
 * 3. A .env file – only in local and development environments.
 */
final class Secrets {

	/**
	 * Environments where reading a .env file is allowed.
	 */
	private const DOTENV_ENVIRONMENTS = array( 'local', 'development' );

	/**
	 * Whether the .env file has been loaded in this request.
	 *
	 * @var bool
	 */
	private static bool $dotenv_loaded = false;

	/**
	 * Get a secret by name.
	 *
	 * @param string $key Secret name, for example WEATHER_API_KEY.
	 * @return string|null The value, or null when it is not set.
	 */
	public static function get( string $key ): ?string {
		if ( function_exists( 'vip_get_env_var' ) ) {
			$value = vip_get_env_var( $key, null );
			if ( is_string( $value ) && '' !== $value ) {
				return $value;
			}
		}

		$value = getenv( $key );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}

		self::maybe_load_dotenv();

		$value = $_ENV[ $key ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Server-side config, not user input.

		return ( is_string( $value ) && '' !== $value ) ? $value : null;
	}

	/**
	 * Show only the last 4 characters, so secrets never end up in logs or screenshots.
	 *
	 * @param string|null $value Secret value.
	 * @return string
	 */
	public static function mask( ?string $value ): string {
		if ( null === $value ) {
			return '(missing)';
		}

		return str_repeat( '*', max( 0, strlen( $value ) - 4 ) ) . substr( $value, -4 );
	}

	/**
	 * Load .env once, and only in environments that allow it.
	 */
	private static function maybe_load_dotenv(): void {
		if ( self::$dotenv_loaded ) {
			return;
		}
		self::$dotenv_loaded = true;

		if ( ! in_array( wp_get_environment_type(), self::DOTENV_ENVIRONMENTS, true ) ) {
			return;
		}

		// safeLoad() does not throw when .env is missing.
		Dotenv::createImmutable( LAB01_APP_DIR )->safeLoad();
	}
}
