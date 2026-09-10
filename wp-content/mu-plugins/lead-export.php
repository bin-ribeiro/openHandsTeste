<?php
/**
 * Plugin Name: Lead Export (E-mail Marketing)
 * Description: Encaminha cada lead do Contact Form 7 para um webhook de e-mail marketing.
 * O endpoint é definido em wp-config.php via SCL_LEAD_WEBHOOK_URL (ou filtro scl_lead_webhook_url)..
 *
 * @package Solar_Consulting_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna a URL do webhook de e-mail marketing.
 *
 * @return string|false
 */
function scl_lead_webhook_url() {
	$url = defined( 'SCL_LEAD_WEBHOOK_URL' ) ? SCL_LEAD_WEBHOOK_URL : '';

	/**
	 * Filtro para personalizar o endpoint de captura de leads..
	 *
	 * @param string $url URL do webhook..
	 */
	$url = apply_filters( 'scl_lead_webhook_url', $url );

	return $url ? esc_url_raw( $url ) : false;
}

/**
 * Dispara o lead para o webhook de e-mail marketing após o CF7 validar.

 * O hook retorna apenas o formulário; a submissão atual é obtida via WPCF7_Submission.
 *
 * @param WPCF7_ContactForm $contact_form Formulário..
 */
function scl_lead_marketing_export( $contact_form ) {
	if ( ! $contact_form || 6 != (int) $contact_form->id() ) {
		return;
	}

	if ( ! class_exists( 'WPCF7_Submission' ) ) {
		return;
	}

	$submission = WPCF7_Submission::get_instance( $contact_form );
	if ( ! $submission ) {
		return;
	}

	$webhook = scl_lead_webhook_url();
	if ( ! $webhook ) {
		return;
	}

	$posted = $submission->get_posted_data();

	$payload = array(
		'name'        => isset( $posted['your-name'] ) ? sanitize_text_field( wp_unslash( $posted['your-name'] ) ) : '',
		'email'       => isset( $posted['your-email'] ) ? sanitize_email( wp_unslash( $posted['your-email'] ) ) : '',
		'phone'       => isset( $posted['your-phone'] ) ? sanitize_text_field( wp_unslash( $posted['your-phone'] ) ) : '',
		'property'    => isset( $posted['your-property'] ) ? ( is_array( $posted['your-property'] ) ? sanitize_text_field( wp_unslash( $posted['your-property'][0] ) ) : sanitize_text_field( wp_unslash( $posted['your-property'] ) ) ) : '',
		'bill'        => isset( $posted['your-bill'] ) ? (float) $posted['your-bill'] : 0,
		'page'        => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'source'     => 'landing-solar-consulting',
		'created_at' => current_time( 'mysql' ),
	);

	wp_remote_post( $webhook, array(
		'timeout' => 10,
		'blocking' => false,
		'headers'  => array( 'Content-Type' => 'application/json' ),
		'body'     => wp_json_encode( $payload ),
	) );
}

foreach ( array( 'wpcf7_mail_failed', 'wpcf7_mail_sent' ) as $hook ) {
	add_action( $hook, 'scl_lead_marketing_export', 10, 3 );
}
