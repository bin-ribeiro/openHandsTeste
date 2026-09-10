<?php
/**
 * Solar Consulting Landing — funções do tema.
 *
 * @package Solar_Consulting_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SCL_VERSION', '1.0.0' );

/**
 * Configurações do tema (FSE não precisa de add_theme_support('block-templates')).
 */
function scl_setup_theme(): void {
	load_theme_textdomain( 'solar-consulting-landing', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 64,
		'width'       => 200,
		'flex-height'  => true,
		'flex-width'   => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	// Editor de blocos.
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'scl_setup_theme' );

/**
 * Registra estilos do front-end.
 */
function scl_scripts(): void {
	wp_enqueue_style(
		'scl-style',
		get_stylesheet_uri(),
		array(),
		SCL_VERSION
	);

	wp_enqueue_style(
		'scl-theme-css',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'scl-style' ),
		SCL_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'scl_scripts' );

/**
 * Ajusta a lista de classes do body para páginas internas.
 */
function scl_body_class( array $classes ): array {
	if ( is_front_page() ) {
		$classes[] = 'scl-front-page';
	}
	return $classes;
}
add_filter( 'body_class', 'scl_body_class' );

/**
 * Remove CSS e JS desnecessários do WooCommerce/emoji quando não usados.
 */
function scl_disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_footer', 'wp_print_footer_scripts' );
}
add_action( 'init', 'scl_disable_emoji' );