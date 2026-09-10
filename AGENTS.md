# AGENTS.md — Solar Consulting Landing

## Como rodar

```bash
cp .env.example .env
./bin/setup.sh
```

- O setup.sh **carrega o `.env`** automaticamente (portas, credenciais, `CONTAINER_PREFIX`) e usa `sudo` só se necessário para acessar o Docker daemon.
- Para múltiplos ambientes na mesma máquina:, mude `CONTAINER_PREFIX` e `WP_PORT`/`PMA_PORT` no `.env`.
- WP-CLI roda via `docker exec ${CONTAINER_PREFIX}_wpcli wp ...` (o container wpcli fica persistente com `sleep infinity`; não use `docker compose run`).

## Estrutura

- **Tema FSE** em `wp-content/themes/solar-consulting-landing/`: `templates/front-page.html` (home), `parts/*.html` (header/footer/cta), `patterns/*.php` (seções), `theme.json` (design tokens.
- **Must-use plugins** em `wp-content/mu-plugins/` — versionados:
  - `dynamic-host.php` — reescreve `siteurl`/`home` conforme host da requisição (local/proxy/túnel).
  - `lead-export.php` — encaminha leads do CF7 para webhook de e-mail marketing (`SCL_LEAD_WEBHOOK_URL` ou filtro `scl_lead_webhook_url`).
- **Plugins instalados via WP-CLI** no `setup.sh` (não versionados — baixados do repositório oficial).

## Pontos de atenção

- Formulário CF7: o `post_content` do WP-CLI **não** cria os fields reais; os campos (`_form`) e o e-mail (`_mail`) são configurados por `bin/fix-cf7.php` via `wp eval-file` com `SCL_FORM_ID`.
- Captura de leads local: **Flamingo** persiste os envios em `flamingo_inbound` — mesmo com `mail_failed` (sem SMTP no dev), o lead fica salvo. Consultar: `wp post list --post_type=flamingo_inbound --fields=ID,post_title`.
- O tema **precisa** de `templates/index.html` (fallback) além do `front-page.html` — sem ele, `wp theme activate` falha com "Template is missing".
- Caracteres invisíveis (zero-width) em edições PHP podem causar `Parse error: unexpected token ","` — reescrever a linha afetada (sed/file_editor.) se isso ocorrer.


## Labels/IDs de referência (ambiente padrão solár:

- Formulário CF7 de leads: ID 6 (`wpcf7_contact_form`)
- Página inicial ("Início): ID 5 — setada como `show_on_front=page`
- Campos: `your-name`, `your-email`, `your-phone`, `your-property`, `your-bill`
- Endpoints REST do CF7: `POST /wp-json/contact-form-7/v1/contact-forms/6/feedback`

## Commit

- `git add -A`; mensagens descritivas em português; `Co-authored-by: openhands <openhands@all-hands.dev>`.
- Não versionar: `.env`, `wp-content/uploads/*` (exceto `.gitkeep`), `wp-content/plugins/*`, core do WP (`wp-admin/`, `wp-includes/`, arquivos raiz wp-*`.`