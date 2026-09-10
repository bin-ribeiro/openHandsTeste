# Solar Consulting Landing

Landing page em **WordPress (Full Site Editing)** para serviços de **consultoria em energia fotovoltaica** para pequenos negócios e residências. Ambiente 100% **local via Docker**, reproduzível e versionado no Git.

---

## Stack

| Componente | Tecnologia |
|---|---|
| WordPress | `wordpress:6.8-php8.3-apache` |
| Banco de dados | MariaDB 11.4 |
| Admin do banco | phpMyAdmin 5.2 |
| CLI | WP-CLI (`wordpress:cli-php8.3`) |
| Tema | Custom FSE — `solar-consulting-landing` |
| Formulário | Contact Form 7 |
| Captura de leads | Flamingo (persiste no banco) |
| SEO | Rank Math (gratuito) |
| Performance | LiteSpeed Cache |
| Backup | UpdraftPlus |

---

## Subindo o ambiente

```bash
# 1. Pré-requisitos: Docker + Docker Compose

# 2. Credenciais locais (opcional — já existem defaults)
cp .env.example .env

# 3. Suba o ambiente e configure tudo automaticamente
./bin/setup.sh
```

### Acessos

| Serviço | URL | Login |
|---|---|---|
| Landing page | http://localhost:12000/ | — |
| wp-admin | http://localhost:12000/wp-admin/ | `admin` / `admin123` |
| phpMyAdmin | http://localhost:12001/ | `root` / `root_local_dev` |

> ⚠️ **Segurança**: credenciais de dev. Nunca use em produção. Troque no `.env` e no `setup.sh` antes de publicar.



---

## Estrutura do projeto

```
├── docker-compose.yml             # WordPress + MariaDB + phpMyAdmin + WP-CLI
├── .env                            # Credenciais e portas locais
├── .env.example                    # Template versionado do .env
├── bin/setup.sh                    # Automação do setup completo
├── bin/fix-cf7.php                # Configura formulário CF7 (campos + e-mail)
└── wp-content/
    ├── themes/
    │   └── solar-consulting-landing/   # Tema FSE da landing page
    │       ├── templates/front-page.html  # Template da home
    │       ├── parts/header.html            # Cabeçalho (logo + nav + CTA)
    │       ├── parts/footer.html            # Rodapé (social + links)
    │       ├── parts/cta.html              # CTA + formulário (template part)
    │       ├── patterns/                    # Blocos reutilizáveis (editor visual)
    │       ├── theme.json                   # Design tokens (cores, tipografia…)
    │       └── functions.php
    ├── mu-plugins/                    # Must-use: rodam sempre
    │   ├── dynamic-host.php              # URLs dinâmicas (local/proxy/túnel)
    │   └── lead-export.php              # Webhook de e-mail marketing (opcional)
    ├── plugins/                            # Plugins (instalados via WP-CLI)
    └── uploads/                            # Mídias (volume Docker)
```

---

## Seções da landing page (patterns)

1. **Hero** — badge + título + subtítulo + 2 CTAs
2. **Benefícios** — 3 estatísticas (90% economia, 25 anos, 100% limpa)
3. **Como funciona** — 4 passos (Diagnóstico → Projeto → Homologação → Instalação)
4. **Depoimentos** — 3 cards de clientes
5. **FAQ** — 4 perguntas em accordion
6. **CTA + Formulário** — formulário de captura de leads (diagnóstico gratuito)

Cada seção é um **bloco reutilizável**: edite uma vez no editor e reutilize em outras páginas.

.

**Para editar visualmente**: no admin, vá em **Appearance → Editor (FSE) → Patterns**.

---

## Captura de leads e e-mail marketing

O formulário é criado pelo **Contact Form 7** (post type `wpcf7_contact_form`) e renderizado via shortcode no template. Os envios são **persistidos no banco pelo Flamingo** (post type `flamingo_inbound`) — você consulta os leads em **Flamingo → Inbound Messages**, mesmo sem SMTP configurado..

###Integração com e-mail marketing

O mu-plugin **`lead-export.php`** encaminha cada lead validado para um webhook de e-mail marketing (JSON POST). Para ativar:

1. Defina a URL do webhook em `wp-config.php`:. `define( 'SCL_LEAD_WEBHOOK_URL', 'https://seu-provider.com/api/lead' );`
2. (Alternativa) use o filtro `scl_lead_webhook_url` no tema.

O payload enviado: `name`, `email`, `phone`, `property`, `bill`, `page`, `ip`, `source`, `created_at`.

Outras opções:
- **Plugin de integração** do seu provedor (Mailchimp, Benchmark, RD Station, Brevo…) — geralmente oferecem bloco/shortcode próprio. Substitua então o shortcode no `parts/cta.html`.
- **FluentCRM / WP Webhooks** — envie os leads do CF7 para qualquer API de e-mail marketing.



---

## Fluxo de trabalho com o Git

```
├── wp-content/themes/...   # Código do tema versionado ✔
├── wp-content/mu-plugins/  # Must-use plugins versionados ✔
├── docker-compose.yml      # Infra versionada ✔
├── bin/setup.sh            # Setup versionado ✔
└── wp-content/uploads, plugins, core/  # Ignorados (.gitignore) ✔
```

**Por quê?** O core do WP e os plugins são baixados pelas imagens Docker e instalados pelo setup; versionamos somente **nosso código**. Para compartilhar o projeto, basta:

```bash
git clone <repo>
cp .env.example .env
./bin/setup.sh
```

> **Subir os mesmos plugins que o time:** nomeie os plugins no `setup.sh` (plugins gratuitos do repositório oficial são instalados via WP-CLI).

**Nota**: o setup.sh cria o formulário CF7 com um ID novo a cada execução e substitui o placeholder `SCL_FORM_ID` nos templates — por isso é seguro rodá-lo repetidamente (idempotente).

---

## Checklist de deploy (produção)

1. Troque **todas as credenciais** `.env` e `setup.sh`
2. Domínio + HTTPS (certificado SSL)
3. Remova o phpMyAdmin do compose
4. Configure **backups automáticos** (UpdraftPlus → nuvem)
5. **Rank Math**: configure sitemap, meta tags e Google Search Console
6. **LiteSpeed Cache**: ative page cache, CSS/JS combine e WebP
7. Configure reCAPTCHA no formulário (CF7)
8. Conecte o **e-mail marketing** desejado
9. Política de privacidade + LGPD

---

## Comandos úteis

```bash
# Ver containers
docker compose ps

# Logs do WordPress
docker compose logs -f wordpress

# Rodar comando WP-CLI qualquer
docker exec solar_wpcli wp <comando>

# Listar leads capturados
docker exec solar_wpcli wp post list --post_type=flamingo_inbound --fields=ID,title,date

# Parar ambiente
docker compose down

# Destruir volume de banco (reset total)
docker compose down -v
```

---

## Histórico do tema

- **v1.0.0** — Estrutura FSE completa, templates, patterns, formulário CF7 integrado com captura local (Flamingo) e webhook de e-mail marketing.