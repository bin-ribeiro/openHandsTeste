<?php
/**
 * Plugin Name: Dynamic Host
 * Description: Reescreve dinamicamente siteurl/home baseado no host da requisição.

 * Permite que o mesmo ambiente funcione via localhost:12000, hosts de proxy, túneis etc.
 *
 * @package Solar_Consulting_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'scl_dynamic_host_url' ) ) {
	/**
	 * Retorna a URL base dinâmica (scheme + host) quando HTTP_HOST estiver disponível.

	 *
	 * @param string $url URL original.
	 * @return string
	 */
	function scl_dynamic_host_url( string $url ): string {
		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			return $url;
		}

		$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		$scheme = ( is_ssl() || ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ? 'https' : 'http';

		return $scheme . '://' . $host;
	}
}

add_filter( 'option_siteurl', 'scl_dynamic_host_url' );
add_filter( 'option_home', 'scl_dynamic_host_url' );
add_filter( 'home_url', 'scl_dynamic_host_url' );
add_filter( 'site_url', 'scl_dynamic_host_url' );