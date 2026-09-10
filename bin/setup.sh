#!/usr/bin/env bash
#
# Sobre o ambiente WordPress local (Docker) e configura a landing page.
#
# Uso: ./bin/setup.sh

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Carrega .env (se existir) para portas, prefixo e credenciais locais.

set -a
# shellcheck disable=SC1091
[ -f "$ROOT_DIR/.env" ] && . "$ROOT_DIR/.env"
set +a

# Usa sudo automaticamente se o usuário atual não tiver acesso ao Docker daemon.
if ! docker info >/dev/null 2>&1; then
	if sudo -n docker info >/dev/null 2>&1; then
		DOCKER="sudo docker"
	else
		echo "ERRO: sem acesso ao Docker daemon." >&2
		exit 1
	fi
else
	DOCKER="docker"
fi

COMPOSE="$DOCKER compose"
CONTAINER_PREFIX="${CONTAINER_PREFIX:-solar}"
# O container wpcli fica persistente(esleep infinity) e usamos docker exec
#(docker compose run conflita com o entrypoint "sh -c" do container)
WP="$DOCKER exec ${CONTAINER_PREFIX}_wpcli wp"

echo "==> 1/7 Subindo containers (WordPress, MariaDB, phpMyAdmin)"
$COMPOSE up -d --wait

echo "==> 2/7 Aguardando WordPress responder..."
for i in $(seq 1 30); do
	if curl -fsS http://localhost:${WP_PORT:-12000}/wp-admin/install.php >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

echo "==> 3/7 Instalando WordPress"
if ! $WP core is-installed --url="http://localhost:${WP_PORT:-12000}/" >/dev/null 2>&1; then
	$WP core install \
		--url="http://localhost:${WP_PORT:-12000}/" \
		--title="Solar Consulting — Energia Fotovoltaica" \
		--admin_user="admin" \
		--admin_password="admin123" \
		--admin_email="admin@example.com" \
		--skip-email
else
	echo "   WordPress já instalado — skipping."
fi

# Garante permissões dos bind mounts para o Apache (uid 33):
# uploads precisa ser escrevível; tema e plugins precisam ser legíveis.
mkdir -p wp-content/uploads wp-content/plugins wp-content/mu-plugins
chown -R 33:33 wp-content/uploads 2>/dev/null || sudo chown -R 33:33 wp-content/uploads 2>/dev/null || true
chmod -R a+rX wp-content/themes wp-content/plugins wp-content/mu-plugins 2>/dev/null || sudo chmod -R a+rX wp-content/themes wp-content/plugins wp-content/mu-plugins 2>/dev/null || true

echo "==> 4/7 Ativando tema e plugins básicos"
$WP theme activate solar-consulting-landing
$WP plugin install contact-form-7 --activate
$WP plugin install seo-by-rank-math --activate
$WP plugin install litespeed-cache --activate
$WP plugin install updraftplus --activate
# Flamingo: persiste os leads do CF7 no banco (captura local sem depender de SMTP)
$WP plugin install flamingo --activate

echo "==> 5/7 Criando página inicial"
PAGE_ID=$($WP post create \
	--post_type=page \
	--post_title="Início" \
	--post_status=publish \
	--porcelain)
$WP option update show_on_front page
$WP option update page_on_front "$PAGE_ID"

echo "==> 6/7 Criando formulário de leads no Contact Form  7"
FORM_ID=$($WP post create \
	--post_type=wpcf7_contact_form \
	--post_title="Diagnóstico Gratuito de Energia Solar" \
	--porcelain)

TMP_FORM="$(mktemp)"
cat >"$TMP_FORM" <<'EOF'
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
EOF

# Post content apenas identifica o formulário; os campos reais (_form) e o e-mail (_mail)
# são configurados via script PHP (evita problema de escaping do WP-CLI com colchetes).
$DOCKER exec -i ${CONTAINER_PREFIX}_wpcli sh -c "cat > /tmp/fix-cf7.php" < bin/fix-cf7.php
$DOCKER exec -e SCL_FORM_ID="$FORM_ID" ${CONTAINER_PREFIX}_wpcli wp eval-file /tmp/fix-cf7.php >/dev/null 2>&1 || {
	echo "   AVISO: falha ao configurar CF7 — ajuste manual via /wp-admin/admin.php?page=wpcf7"
}
rm -f "$TMP_FORM"

# Substitui o placeholder do shortcode no template por o ID real
sed -i "s/SCL_FORM_ID/$FORM_ID/g" \
	wp-content/themes/solar-consulting-landing/parts/cta.html \
	wp-content/themes/solar-consulting-landing/patterns/cta-form.php

echo "==> 7/7 Limpando permalinks"
$WP rewrite structure '/%postname%/' --hard
$WP cache flush 2>/dev/null || true

echo ""
echo "🎉 Landing page pronta!"
echo "   Site:            http://localhost:${WP_PORT:-12000}/"
echo "   Admin:           http://localhost:${WP_PORT:-12000}/wp-admin/"
echo "   Admin user/pass: admin / admin123"
echo "   phpMyAdmin:      http://localhost:${PMA_PORT:-12001}/"
echo "   (usr: root / senha do .env: DB_ROOT_PASSWORD)"