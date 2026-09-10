<?php
/**
 * Configura o formulário CF7 (campos `_form`) e o envio de e-mail (`_mail`).
 * Executado via `wp eval-file` no container do WordPress.
 *
 * Uso: wp eval-file /tmp/fix-cf7.php --form_id=6
 *
 * @package Solar_Consulting_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Este script deve rodar via WP-CLI.\n" );
	exit( 1 );
}

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	include_once WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
}

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	fwrite( STDERR, "Plugin Contact Form 7 não encontrado.\n" );
	exit( 1 );
}

			$form_id = (int) getenv( 'SCL_FORM_ID' ) ?: 6;

$cf7 = WPCF7_ContactForm::get_instance( $form_id );
if ( ! $cf7 ) {
	fwrite( STDERR, "Formulário $form_id não encontrado.\n" );
	exit( 1 );
}

$props = $cf7->get_properties();

// Campos do formulário.
if ( '' === $props['form'] ) {
	$props['form'] = <<<FORM
<label> Seu nome
    [text* your-name]</label>

<label> Seu melhor e-mail
    [email* your-email]</label>

<label> WhatsApp / Telefone
    [tel your-phone]</label>

<label> Tipo de imóvel
    [select* your-property "Domicílio" "Pequeno negócio" "Outro"]</label>

<label> Consumo médio mensal (R$)
    [number your-bill]</label>

[submit "Quero meu diagnóstico gratuito"]
FORM;
}

$host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/^www\./', '', $_SERVER['HTTP_HOST'] ) : ( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'localhost' );
$from = strtolower( preg_replace( '/[^a-z0-9.-]/i', '', $host ) );
$from = $from ?: 'localhost';

// E-mail de envio para o admin (local sem SMTP; com Flamingo o lead fica salvo no banco).
$props['mail'] = array(
	'to' => get_option( 'admin_email' ),
	'from' => "wordpress@{$from}",
	'subject' => 'Novo lead: Diagnóstico Gratuito de Energia Solar',
	'body' => "Nome: [your-name]\nE-mail: [your-email]\nTelefone: [your-phone]\nTipo de imóvel: [your-property]\nConsumo mensal (R$): [your-bill]\n\nEnviado pela landing page.",
	'recipient' => get_option( 'admin_email' ),
	'sender' => "Solar Consulting <wordpress@{$from}>",
	'additional_headers' => 'Reply-To: [your-email]',
	'attachments' => '',
	'use_html' => 0,
	'exclude_blank' => 0,
);

$cf7->set_properties( $props );
$cf7->save();

echo "CF7 {$form_id} configurado. Campos: {$props['form']}\n";