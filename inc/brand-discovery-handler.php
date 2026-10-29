<?php
/**
 * SUED Studio — Brand Discovery Quiz & CRM Handler
 *
 * Responsibilities:
 *  1. Register a custom DB table `{prefix}sued_brand_discovery` on theme activation & init.
 *  2. Provide a secure REST API endpoint `/wp-json/sued/v1/brand-discovery-submit` with validation,
 *     sanitization, honeypot protection, rate limiting, and email dispatch.
 *  3. Dispatch notification emails to designated team addresses.
 *  4. Provide a modern WP-Admin CRM page styled according to SUED Studio's visual identity.
 *  5. Register the `[sued_brand_discovery]` shortcode.
 *
 * @package SUED_Studio
 */

defined('ABSPATH') || exit;

/* ─── 1. Database — Custom Table `{prefix}sued_brand_discovery` ─── */

define('SUED_BRAND_DISCOVERY_DB_VERSION', '1.0.0');

function sued_create_brand_discovery_table(): void {
    global $wpdb;

    $table   = $wpdb->prefix . 'sued_brand_discovery';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        client_name     VARCHAR(150)        NOT NULL DEFAULT '',
        company_name    VARCHAR(150)        NOT NULL DEFAULT '',
        email           VARCHAR(200)        NOT NULL DEFAULT '',
        sector          VARCHAR(100)        NOT NULL DEFAULT '',
        project_type    VARCHAR(100)        NOT NULL DEFAULT '',
        answers_json    LONGTEXT            NOT NULL,
        status          ENUM('new','contacted','in_progress','completed','archived') NOT NULL DEFAULT 'new',
        ip_address      VARCHAR(45)         NOT NULL DEFAULT '',
        user_agent      VARCHAR(500)        NOT NULL DEFAULT '',
        source          VARCHAR(255)        NOT NULL DEFAULT '',
        created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY email (email),
        KEY status (status),
        KEY created_at (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('sued_brand_discovery_db_version', SUED_BRAND_DISCOVERY_DB_VERSION);
}
add_action('after_switch_theme', 'sued_create_brand_discovery_table');

add_action('init', function (): void {
    if (get_option('sued_brand_discovery_db_version') !== SUED_BRAND_DISCOVERY_DB_VERSION) {
        sued_create_brand_discovery_table();
    }
});

/**
 * Ensure the Brand Discovery page exists in the database with published status.
 */
function sued_ensure_brand_discovery_page(): void {
    global $wpdb;

    // Check if the page exists
    $page_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' AND post_status = 'publish' LIMIT 1",
            'brand-discovery'
        )
    );

    if (!$page_id) {
        $new_id = wp_insert_post([
            'post_title'     => 'Brand Discovery',
            'post_name'      => 'brand-discovery',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_content'   => '<!-- wp:sued-studio/brand-discovery {"align":"full"} /-->',
            'comment_status' => 'closed',
        ]);

        if ($new_id && !is_wp_error($new_id)) {
            update_post_meta($new_id, '_wp_page_template', 'brand-discovery');
            flush_rewrite_rules(false);
        }
    }
}
add_action('init', 'sued_ensure_brand_discovery_page', 5);
add_action('after_switch_theme', 'sued_ensure_brand_discovery_page');

/**
 * Direct route fallback: if /brand-discovery is hit, ensure the page and template resolve
 * even if WordPress rewrite cache has not yet refreshed.
 */
add_filter('template_include', function ($template) {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($request_uri === 'brand-discovery') {
        $page = get_page_by_path('brand-discovery');
        if ($page) {
            global $wp_query, $post;
            if (!$wp_query->is_page) {
                $wp_query->is_page           = true;
                $wp_query->is_singular       = true;
                $wp_query->is_home           = false;
                $wp_query->is_archive        = false;
                $wp_query->is_404            = false;
                $wp_query->queried_object    = $page;
                $wp_query->queried_object_id = $page->ID;
                $post = $page;
            }
        }
    }
    return $template;
}, 99);

/* ─── 2. REST API Endpoint: POST /wp-json/sued/v1/brand-discovery-submit ─── */

add_action('rest_api_init', function (): void {
    register_rest_route('sued/v1', '/brand-discovery-submit', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'sued_rest_handle_brand_discovery_submit',
        'permission_callback' => '__return_true', // Nonce check performed inside callback
    ]);
});

/**
 * REST API Callback for Brand Discovery submission.
 */
function sued_rest_handle_brand_discovery_submit(WP_REST_Request $request): WP_REST_Response {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_params();
    }

    // 1. Nonce Verification
    $nonce = $request->get_header('x_wp_nonce') ?: ($params['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Sessão expirada. Por favor, recarregue a página e tente novamente.', 'sued-studio'),
        ], 403);
    }

    // 2. Honeypot Spam Protection
    if (!empty($params['sued_hp_check']) || !empty($params['website'])) {
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Recebido com sucesso.', 'sued-studio'),
        ], 200);
    }

    // 3. Rate Limiting via Transients (max 5 submissions per 10 min per IP)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
        $ip = trim($forwarded[0]);
    }
    $ip_clean = filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';

    $transient_key = 'sued_bd_rate_' . md5($ip_clean);
    $attempts = (int) get_transient($transient_key);
    if ($attempts >= 5) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Muitas tentativas em pouco tempo. Aguarde alguns minutos antes de reenviar.', 'sued-studio'),
        ], 429);
    }
    set_transient($transient_key, $attempts + 1, 10 * MINUTE_IN_SECONDS);

    // 4. Data Extraction & Sanitization
    $identificacao = is_array($params['identificacao'] ?? null) ? $params['identificacao'] : [];
    
    $client_name  = sanitize_text_field($identificacao['nomeCliente'] ?? $params['clientName'] ?? '');
    $email        = sanitize_email($identificacao['email'] ?? $params['clientEmail'] ?? '');
    $company_name = sanitize_text_field($identificacao['nomeEmpresa'] ?? $params['companyName'] ?? '');
    $sector       = sanitize_text_field($identificacao['setor'] ?? $params['sector'] ?? '');
    
    $project_type_raw = $identificacao['tipoProjeto'] ?? $params['projectType'] ?? '';
    $project_type = is_array($project_type_raw) ? implode(', ', array_map('sanitize_text_field', $project_type_raw)) : sanitize_text_field($project_type_raw);

    // 5. Validation of Required Fields
    if (empty($client_name)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Por favor, informe seu nome na primeira etapa.', 'sued-studio'),
        ], 422);
    }

    if (empty($email) || !is_email($email)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Por favor, informe um endereço de e-mail válido.', 'sued-studio'),
        ], 422);
    }

    if (empty($company_name)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Por favor, informe o nome da sua empresa ou projeto.', 'sued-studio'),
        ], 422);
    }

    $source     = esc_url_raw($params['source'] ?? wp_get_referer() ?: home_url('/brand-discovery'));
    $user_agent = sanitize_text_field(substr(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500));

    // Full structured data sanitize
    $clean_data = sued_sanitize_brand_discovery_payload($params);
    $answers_json = wp_json_encode($clean_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // 6. DB Insertion
    global $wpdb;
    $table = $wpdb->prefix . 'sued_brand_discovery';

    $inserted = $wpdb->insert(
        $table,
        [
            'client_name'  => $client_name,
            'company_name' => $company_name,
            'email'        => $email,
            'sector'       => $sector,
            'project_type' => $project_type,
            'answers_json' => $answers_json,
            'status'       => 'new',
            'ip_address'   => $ip_clean,
            'user_agent'   => $user_agent,
            'source'       => $source,
            'created_at'   => current_time('mysql'),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    if (false === $inserted) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Erro ao processar dados no servidor. Tente novamente.', 'sued-studio'),
        ], 500);
    }

    $submission_id = $wpdb->insert_id;

    // 7. Dispatch Notification Emails
    sued_send_brand_discovery_notification_email($submission_id, [
        'client_name'  => $client_name,
        'company_name' => $company_name,
        'email'        => $email,
        'sector'       => $sector,
        'project_type' => $project_type,
        'source'       => $source,
        'ip'           => $ip_clean,
        'data'         => $clean_data,
    ]);

    return new WP_REST_Response([
        'success'       => true,
        'submission_id' => $submission_id,
        'message'       => __('Brand Discovery registrado com sucesso!', 'sued-studio'),
    ], 200);
}

/**
 * Deep sanitization for Brand Discovery submission payload.
 */
function sued_sanitize_brand_discovery_payload(array $data): array {
    $clean = [];
    foreach ($data as $key => $val) {
        $safe_key = sanitize_key($key);
        if (is_array($val)) {
            $clean[$safe_key] = sued_sanitize_brand_discovery_payload($val);
        } elseif (is_numeric($val)) {
            $clean[$safe_key] = $val;
        } elseif (is_string($val)) {
            $clean[$safe_key] = sanitize_textarea_field($val);
        } else {
            $clean[$safe_key] = $val;
        }
    }
    return $clean;
}

/* ─── 3. Email Notification to Team ────────────────────────────── */

/**
 * Dispatches notification emails to team members when a Brand Discovery is submitted.
 */
function sued_send_brand_discovery_notification_email(int $id, array $data): void {
    $recipients = [
        'henrich.caeiro@gmail.com',
        'le_19camargo@hotmail.com',
        'heloheloisa.srf@gmail.com',
    ];

    $subject = sprintf(
        '[SUED Studio] 🎨 Novo Brand Discovery #%d — %s (%s)',
        $id,
        $data['company_name'] ?: $data['client_name'],
        $data['project_type'] ?: 'Briefing'
    );

    $admin_url = admin_url('admin.php?page=sued-brand-discovery&id=' . $id);

    $html  = '<!DOCTYPE html>';
    $html .= '<html lang="pt-BR"><head><meta charset="UTF-8"><title>Novo Brand Discovery — SUED Studio</title></head>';
    $html .= '<body style="background-color:#0A1118; color:#F5F2EC; font-family:\'Montserrat\',Arial,sans-serif; margin:0; padding:24px;">';
    $html .= '<table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#0F1923; border:1px solid #2A3A47; border-radius:12px; overflow:hidden;">';
    
    // Header
    $html .= '<tr><td style="padding:28px 32px; background-color:#141F2B; border-bottom:1px solid #2A3A47;">';
    $html .= '<div style="font-size:16px; font-weight:700; letter-spacing:2px; color:#F5F2EC; text-transform:uppercase;">SUED<span style="color:#26AFFF;">.</span>STUDIO</div>';
    $html .= '<div style="color:#C8A96E; font-size:12px; font-weight:600; text-transform:uppercase; margin-top:4px; letter-spacing:1px;">Novo Formulário Brand Discovery Recebido</div>';
    $html .= '</td></tr>';

    // Summary Card
    $html .= '<tr><td style="padding:32px;">';
    $html .= '<div style="background-color:#141F2B; border:1px solid #2A3A47; border-radius:8px; padding:20px; text-align:center; margin-bottom:24px;">';
    $html .= '<div style="font-size:22px; font-weight:700; color:#26AFFF;">' . esc_html($data['company_name']) . '</div>';
    $html .= '<div style="font-size:14px; color:#C8A96E; margin-top:4px; font-weight:600;">' . esc_html($data['project_type'] ?: 'Briefing') . '</div>';
    $html .= '</div>';

    // Details Table
    $html .= '<table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; border-collapse:collapse; margin-bottom:24px;">';
    $fields = [
        'Cliente'       => $data['client_name'],
        'E-mail'        => sprintf('<a href="mailto:%s" style="color:#26AFFF; text-decoration:none;">%s</a>', esc_attr($data['email']), esc_html($data['email'])),
        'Empresa'       => $data['company_name'],
        'Setor'         => $data['sector'] ?: 'Não especificado',
        'Tipo Projeto'  => $data['project_type'] ?: 'Não especificado',
        'Origem'        => $data['source'],
        'Data'          => current_time('d/m/Y H:i'),
    ];

    foreach ($fields as $label => $val) {
        $html .= sprintf(
            '<tr><td width="35%%" style="color:#7A8B95; padding:8px 0; border-bottom:1px solid #1E2D3B; font-weight:600;">%s:</td><td style="color:#F5F2EC; padding:8px 0; border-bottom:1px solid #1E2D3B;">%s</td></tr>',
            esc_html($label),
            $val
        );
    }
    $html .= '</table>';

    // Highlight snippet from answers
    $negocio = $data['data']['negocio'] ?? [];
    if (!empty($negocio['elevatorPitch'])) {
        $html .= '<div style="background-color:#0A1118; border-left:3px solid #26AFFF; padding:12px 16px; margin-bottom:24px; border-radius:4px;">';
        $html .= '<div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#7A8B95; margin-bottom:4px;">Pitch do Negócio</div>';
        $html .= '<div style="font-size:13px; color:#F5F2EC; font-style:italic;">"' . esc_html($negocio['elevatorPitch']) . '"</div>';
        $html .= '</div>';
    }

    // CTA
    $html .= '<div style="text-align:center; margin-top:28px;">';
    $html .= sprintf(
        '<a href="%s" style="display:inline-block; background-color:#26AFFF; color:#0F1923; text-decoration:none; font-weight:700; font-size:14px; padding:12px 28px; border-radius:8px;">Acessar Briefing Completo no Painel</a>',
        esc_url($admin_url)
    );
    $html .= '</div>';

    $html .= '</td></tr>';

    // Footer
    $html .= '<tr><td style="padding:16px 32px; background-color:#0A1118; border-top:1px solid #1E2D3B; text-align:center; font-size:11px; color:#7A8B95;">';
    $html .= 'SUED Studio — Brand Discovery Automation. Notificação interna confidencial.';
    $html .= '</td></tr>';
    $html .= '</table></body></html>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: SUED Studio <contato@suedstudio.com.br>',
        sprintf('Reply-To: %s <%s>', $data['client_name'], $data['email']),
    ];

    wp_mail($recipients, $subject, $html, $headers);
}

/* ─── 4. WP-Admin CRM — Submenu under Leads SUED ───────────────── */

add_action('admin_menu', function (): void {
    add_submenu_page(
        'sued-leads',
        __('Brand Discovery', 'sued-studio'),
        __('Brand Discovery', 'sued-studio'),
        'manage_options',
        'sued-brand-discovery',
        'sued_render_brand_discovery_crm_page'
    );
}, 25);

/**
 * Render modern dark-mode CRM dashboard in WP-Admin for Brand Discovery.
 */
function sued_render_brand_discovery_crm_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(__('Acesso negado.', 'sued-studio'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'sued_brand_discovery';

    // Handle status change action
    if (
        isset($_GET['action'], $_GET['id'], $_GET['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sued_bd_action')
    ) {
        $item_id    = absint($_GET['id']);
        $new_status = sanitize_text_field(wp_unslash($_GET['action']));
        $allowed    = ['new', 'contacted', 'in_progress', 'completed', 'archived'];

        if (in_array($new_status, $allowed, true)) {
            $wpdb->update($table, ['status' => $new_status], ['id' => $item_id], ['%s'], ['%d']);
        }
    }

    // Filter by status
    $filter_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    $where         = $filter_status ? $wpdb->prepare('WHERE status = %s', $filter_status) : '';
    $search        = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    if ($search) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= ($where ? ' AND ' : 'WHERE ') . $wpdb->prepare('(client_name LIKE %s OR company_name LIKE %s OR email LIKE %s)', $like, $like, $like);
    }

    $submissions = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC");

    // Single item view
    $view_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $item    = $view_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $view_id)) : null;

    // Counts
    $counts_raw = $wpdb->get_results("SELECT status, COUNT(*) as total FROM {$table} GROUP BY status", OBJECT_K);
    $all_statuses = [
        'new'         => ['label' => 'Novos',         'icon' => '⚡', 'color' => '#26AFFF'],
        'contacted'   => ['label' => 'Em Contato',    'icon' => '📞', 'color' => '#C8A96E'],
        'in_progress' => ['label' => 'Em Andamento',  'icon' => '🎨', 'color' => '#3498DB'],
        'completed'   => ['label' => 'Concluídos',    'icon' => '💎', 'color' => '#00E5A3'],
        'archived'    => ['label' => 'Arquivados',    'icon' => '📁', 'color' => '#7A8B95'],
    ];

    $total_all = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    ?>

    <style>
      #wpcontent { background: #0A1118; color: #F5F2EC; }
      .sued-crm-wrap {
        max-width: 1350px;
        margin: 20px 20px 40px 0;
        font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      .sued-crm-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        border-bottom: 1px solid #2A3A47;
        padding-bottom: 18px;
        flex-wrap: wrap;
        gap: 16px;
      }
      .sued-crm-title {
        font-size: 26px;
        font-weight: 700;
        color: #F5F2EC;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
      }
      .sued-crm-title span { color: #C8A96E; }
      .sued-badge-count {
        background: #141F2B;
        border: 1px solid #2A3A47;
        color: #26AFFF;
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 20px;
      }
      .sued-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
      }
      .sued-stat-card {
        background: #0F1923;
        border: 1px solid #2A3A47;
        border-radius: 12px;
        padding: 16px 20px;
        text-decoration: none;
        color: #F5F2EC;
        transition: transform 0.15s, border-color 0.15s;
        display: block;
      }
      .sued-stat-card:hover, .sued-stat-card.active {
        border-color: #26AFFF;
        transform: translateY(-2px);
      }
      .sued-stat-num {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
      }
      .sued-stat-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #7A8B95;
      }
      .sued-crm-table {
        width: 100%;
        border-collapse: collapse;
        background: #0F1923;
        border: 1px solid #2A3A47;
        border-radius: 12px;
        overflow: hidden;
      }
      .sued-crm-table th {
        background: #141F2B;
        color: #7A8B95;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid #2A3A47;
      }
      .sued-crm-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #1E2D3B;
        font-size: 14px;
        color: #F5F2EC;
        vertical-align: middle;
      }
      .sued-crm-table tr:hover td { background: #141F2B; }
      .sued-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
      }
      .sued-btn-sm {
        background: #141F2B;
        border: 1px solid #2A3A47;
        color: #26AFFF;
        font-size: 12px;
        padding: 6px 14px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s, border-color 0.15s;
        cursor: pointer;
      }
      .sued-btn-sm:hover {
        background: #1E2D3B;
        border-color: #26AFFF;
        color: #FFFFFF;
      }
      .sued-btn-primary {
        background: #26AFFF;
        color: #0F1923 !important;
        font-weight: 700;
        border: none;
      }
      .sued-btn-primary:hover {
        background: #63C5FF;
        color: #0F1923 !important;
      }
      .sued-lead-modal {
        background: #0F1923;
        border: 1px solid #2A3A47;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 32px;
      }
      .sued-section-box {
        background: #141F2B;
        border: 1px solid #2A3A47;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 16px;
      }
      .sued-section-box h4 {
        margin: 0 0 14px 0;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #26AFFF;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .sued-tag {
        display: inline-block;
        background: #1E2D3B;
        border: 1px solid #2A3A47;
        color: #F5F2EC;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        margin: 3px;
      }
      .sued-tag.gold {
        border-color: rgba(200, 169, 110, 0.4);
        background: rgba(200, 169, 110, 0.1);
        color: #C8A96E;
      }
      .sued-tag.blue {
        border-color: rgba(38, 175, 255, 0.4);
        background: rgba(38, 175, 255, 0.1);
        color: #26AFFF;
      }
      .sued-color-swatch-sm {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        background: #0F1923;
        border: 1px solid #2A3A47;
        font-size: 12px;
        margin: 3px;
      }
      .sued-swatch-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,0.2);
      }
      .sued-slider-bar {
        background: #0A1118;
        border: 1px solid #2A3A47;
        border-radius: 20px;
        height: 12px;
        position: relative;
        margin: 8px 0;
        overflow: hidden;
      }
      .sued-slider-fill {
        background: linear-gradient(90deg, #26AFFF, #C8A96E);
        height: 100%;
        border-radius: 20px;
      }
    </style>

    <div class="sued-crm-wrap">
      
      <!-- Top Bar -->
      <div class="sued-crm-header">
        <div>
          <h1 class="sued-crm-title">Brand <span>Discovery</span> <span class="sued-badge-count"><?php echo esc_html($total_all); ?></span></h1>
          <p style="margin: 6px 0 0 0; color: #7A8B95; font-size: 13px;">Gestão de briefings criativos e descoberta de marca.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <form method="get" style="display:flex; gap:8px;">
            <input type="hidden" name="page" value="sued-brand-discovery">
            <?php if ($filter_status): ?>
              <input type="hidden" name="status" value="<?php echo esc_attr($filter_status); ?>">
            <?php endif; ?>
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar cliente, empresa..." style="background:#0F1923; border:1px solid #2A3A47; color:#F5F2EC; padding:6px 14px; border-radius:6px; font-size:13px;">
            <button type="submit" class="sued-btn-sm">Buscar</button>
            <?php if ($search || $filter_status): ?>
              <a href="<?php echo esc_url(admin_url('admin.php?page=sued-brand-discovery')); ?>" class="sued-btn-sm" style="color:#FF4D4D;">Limpar</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Single Submission Detail View -->
      <?php if ($item):
          $data = json_decode($item->answers_json, true) ?: [];
          $negocio = $data['negocio'] ?? [];
          $essencia = $data['essencia'] ?? [];
          $publico = $data['publico'] ?? [];
          $visual = $data['personalidadeVisual'] ?? [];
          $refs = $data['referencias'] ?? [];
          $tom = $data['tomDeVoz'] ?? [];
          $final = $data['contextualFinal'] ?? [];
      ?>
        <div class="sued-lead-modal">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; border-bottom:1px solid #2A3A47; padding-bottom:18px; flex-wrap:wrap; gap:16px;">
            <div>
              <a href="<?php echo esc_url(remove_query_arg(['id', 'action', '_wpnonce'])); ?>" class="sued-btn-sm" style="margin-bottom:12px; display:inline-block;">&larr; Voltar para a lista</a>
              <h2 style="font-size:24px; margin:0 0 6px 0; color:#F5F2EC;"><?php echo esc_html($item->company_name ?: $item->client_name); ?></h2>
              <div style="color:#7A8B95; font-size:13px;">
                Cliente: <strong style="color:#F5F2EC;"><?php echo esc_html($item->client_name); ?></strong> &bull;
                E-mail: <a href="mailto:<?php echo esc_attr($item->email); ?>" style="color:#26AFFF;"><?php echo esc_html($item->email); ?></a> &bull;
                Setor: <strong style="color:#F5F2EC;"><?php echo esc_html($item->sector ?: 'Não informado'); ?></strong> &bull;
                Data: <?php echo esc_html(date_i18n('d/m/Y \à\s H:i', strtotime($item->created_at))); ?>
              </div>
            </div>
            
            <!-- Status Actions -->
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:12px; color:#7A8B95;">Status atual:</span>
                <?php
                $st_info = $all_statuses[$item->status] ?? ['label' => $item->status, 'color' => '#26AFFF'];
                ?>
                <span class="sued-pill" style="background:<?php echo esc_attr($st_info['color']); ?>22; color:<?php echo esc_attr($st_info['color']); ?>; border:1px solid <?php echo esc_attr($st_info['color']); ?>;">
                  <?php echo esc_html($st_info['label']); ?>
                </span>
              </div>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <?php foreach ($all_statuses as $st_key => $st_data): ?>
                  <?php if ($st_key !== $item->status): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'sued-brand-discovery', 'id' => $item->id, 'action' => $st_key], admin_url('admin.php')), 'sued_bd_action')); ?>" class="sued-btn-sm" style="font-size:11px; padding:3px 8px;">
                      Mudar para <?php echo esc_html($st_data['label']); ?>
                    </a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Grid of Sections -->
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap:20px;">
            
            <!-- 1. O Negócio -->
            <div class="sued-section-box">
              <h4>🏢 O Negócio</h4>
              <p><strong>Pitch:</strong> <?php echo esc_html($negocio['elevatorPitch'] ?? '—'); ?></p>
              <p><strong>Descrição:</strong><br><?php echo nl2br(esc_html($negocio['descricaoCompleta'] ?? '—')); ?></p>
              <p><strong>Problema que resolve:</strong><br><?php echo nl2br(esc_html($negocio['problemaSolucionado'] ?? '—')); ?></p>
              <p><strong>Diferencial:</strong> <?php echo esc_html($negocio['diferencial'] ?? '—'); ?></p>
              <p><strong>Estágio:</strong> <?php echo esc_html($negocio['estagio'] ?? '—'); ?></p>
              <?php if (!empty($negocio['modeloNegocio'])): ?>
                <div style="margin-top:8px;">
                  <span style="font-size:12px; color:#7A8B95;">Modelos:</span>
                  <?php foreach ((array)$negocio['modeloNegocio'] as $m): ?>
                    <span class="sued-tag blue"><?php echo esc_html($m); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- 2. A Alma da Marca -->
            <div class="sued-section-box">
              <h4>💫 Alma da Marca & Arquétipos</h4>
              <p><strong>Propósito:</strong><br><?php echo nl2br(esc_html($essencia['proposito'] ?? '—')); ?></p>
              <p><strong>Personalidade:</strong><br><?php echo nl2br(esc_html($essencia['personalidadeMarca'] ?? '—')); ?></p>
              <p><strong>Sentimento que quer despertar:</strong> <?php echo esc_html($essencia['sentimentoCentral'] ?? '—'); ?></p>
              <?php if (!empty($essencia['valores'])): ?>
                <div style="margin-top:10px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Valores Inegociáveis:</span>
                  <?php foreach ((array)$essencia['valores'] as $val): ?>
                    <span class="sued-tag gold"><?php echo esc_html(ucfirst($val)); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($essencia['arquetipos'])): ?>
                <div style="margin-top:12px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Arquétipos Selecionados:</span>
                  <?php foreach ((array)$essencia['arquetipos'] as $arq): ?>
                    <span class="sued-tag blue" style="font-weight:700;"><?php echo esc_html(strtoupper($arq)); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- 3. Público & Concorrentes -->
            <div class="sued-section-box">
              <h4>🎯 Público-Alvo & Mercado</h4>
              <p><strong>Cliente Ideal:</strong><br><?php echo nl2br(esc_html($publico['clienteIdeal'] ?? '—')); ?></p>
              <p><strong>Dores e Frustrações:</strong><br><?php echo nl2br(esc_html($publico['dores'] ?? '—')); ?></p>
              <p><strong>Aspirações:</strong><br><?php echo nl2br(esc_html($publico['aspiracoes'] ?? '—')); ?></p>
              <p><strong>Por que escolheriam você:</strong> <?php echo esc_html($publico['gatilhoDecisao'] ?? '—'); ?></p>
              <p><strong>Anti-cliente (quem NÃO é):</strong> <?php echo esc_html($publico['antiCliente'] ?? '—'); ?></p>
              <?php if (!empty($publico['concorrentes'])): ?>
                <div style="margin-top:8px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Concorrentes Listados:</span>
                  <?php foreach ((array)$publico['concorrentes'] as $conc): ?>
                    <div style="font-size:13px; color:#F5F2EC; padding:4px 0;">&bull; <?php echo esc_html($conc); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- 4. Personalidade Visual -->
            <div class="sued-section-box">
              <h4>🎨 Personalidade Visual & Cores</h4>
              <?php if (!empty($visual['espectros'])): ?>
                <div style="margin-bottom:14px;">
                  <?php
                  $esp_labels = [
                      'minimalistaVsExuberante' => ['Minimalista', 'Exuberante'],
                      'classicoVsModerno'       => ['Clássico / Atemporal', 'Moderno / Futurista'],
                      'serioVsDescontraido'     => ['Sério / Corporativo', 'Descontraído / Irreverente'],
                      'acessivelVsPremium'      => ['Acessível / Popular', 'Premium / Exclusivo'],
                      'frioVsQuente'            => ['Frio / Técnico', 'Quente / Humano'],
                      'localVsGlobal'           => ['Local / Regional', 'Global / Internacional'],
                  ];
                  foreach ($visual['espectros'] as $esp_key => $esp_val):
                      $names = $esp_labels[$esp_key] ?? [$esp_key, ''];
                      $pct = min(100, max(0, ((int)$esp_val - 1) * 11.1));
                  ?>
                    <div style="margin-bottom:10px;">
                      <div style="display:flex; justify-content:space-between; font-size:11px; color:#7A8B95;">
                        <span><?php echo esc_html($names[0]); ?></span>
                        <strong style="color:#26AFFF;"><?php echo esc_html($esp_val); ?>/10</strong>
                        <span><?php echo esc_html($names[1]); ?></span>
                      </div>
                      <div class="sued-slider-bar">
                        <div class="sued-slider-fill" style="width:<?php echo esc_attr($pct); ?>%;"></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($visual['adjetivosPresentes'])): ?>
                <div style="margin-top:8px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Diretrizes Visuais (SIM):</span>
                  <?php foreach ((array)$visual['adjetivosPresentes'] as $adj): ?>
                    <span class="sued-tag blue"><?php echo esc_html($adj); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($visual['adjetivosAusentes'])): ?>
                <p style="margin-top:8px;"><strong>O que NÃO deve estar presente:</strong><br><?php echo esc_html($visual['adjetivosAusentes']); ?></p>
              <?php endif; ?>

              <?php if (!empty($visual['coresSelecionadas'])): ?>
                <div style="margin-top:10px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Paleta Selecionada:</span>
                  <?php foreach ((array)$visual['coresSelecionadas'] as $c): ?>
                    <span class="sued-tag gold"><?php echo esc_html($c); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($visual['notasCores'])): ?>
                <p style="margin-top:8px;"><strong>Notas de Cor:</strong> <?php echo esc_html($visual['notasCores']); ?></p>
              <?php endif; ?>
            </div>

            <!-- 5. Referências & Associações -->
            <div class="sued-section-box">
              <h4>🔍 Referências Criativas</h4>
              <?php if (!empty($refs['marcasAdmiradas'])): ?>
                <div style="margin-bottom:8px;">
                  <span style="font-size:12px; color:#00E5A3; display:block; margin-bottom:4px;">Marcas que Admira:</span>
                  <?php foreach ((array)$refs['marcasAdmiradas'] as $ad): ?>
                    <div style="font-size:13px; color:#F5F2EC; padding:3px 0;">&bull; <?php echo esc_html($ad); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($refs['marcasNaoQueridas'])): ?>
                <div style="margin-bottom:8px;">
                  <span style="font-size:12px; color:#FF4D4D; display:block; margin-bottom:4px;">Marcas que Repele:</span>
                  <?php foreach ((array)$refs['marcasNaoQueridas'] as $dis): ?>
                    <div style="font-size:13px; color:#F5F2EC; padding:3px 0;">&bull; <?php echo esc_html($dis); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <p><strong>Símbolo / Metáfora:</strong> <?php echo esc_html($refs['simboloMetafora'] ?? '—'); ?></p>
              <p><strong>Se fosse um lugar:</strong> <?php echo esc_html($refs['marcaComoLugar'] ?? '—'); ?></p>
              <p><strong>Se fosse música:</strong> <?php echo esc_html($refs['marcaComoMusica'] ?? '—'); ?></p>
              <p><strong>Textura / Material:</strong> <?php echo esc_html($refs['marcaComoMaterial'] ?? '—'); ?></p>
            </div>

            <!-- 6. Tom de Voz & Aplicações -->
            <div class="sued-section-box">
              <h4>🗣️ Tom de Voz & Aplicações</h4>
              <?php if (!empty($tom['estilos'])): ?>
                <div style="margin-bottom:10px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Estilos de Tom:</span>
                  <?php foreach ((array)$tom['estilos'] as $t_est): ?>
                    <span class="sued-tag gold"><?php echo esc_html(ucfirst($t_est)); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <p><strong>Frase que SIM é a voz da marca:</strong><br><em style="color:#00E5A3;">"<?php echo esc_html($tom['fraseSimEVoz'] ?? '—'); ?>"</em></p>
              <p><strong>Frase que NÃO é a voz da marca:</strong><br><em style="color:#FF4D4D;">"<?php echo esc_html($tom['fraseNaoEVoz'] ?? '—'); ?>"</em></p>
              <p><strong>Pessoa verbal:</strong> <?php echo esc_html(is_array($tom['pessoa'] ?? '') ? implode(', ', $tom['pessoa']) : ($tom['pessoa'] ?? '—')); ?></p>
              <?php if (!empty($tom['aplicacoesPrioritarias'])): ?>
                <div style="margin-top:10px;">
                  <span style="font-size:12px; color:#7A8B95; display:block; margin-bottom:4px;">Aplicações Prioritárias:</span>
                  <?php foreach ((array)$tom['aplicacoesPrioritarias'] as $app): ?>
                    <span class="sued-tag blue"><?php echo esc_html($app); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- 7. Contexto Final & Mensagem -->
            <div class="sued-section-box" style="grid-column: 1 / -1;">
              <h4>📝 Contexto Final & Mensagem do Cliente</h4>
              <p><strong>Mensagem para o designer:</strong><br><span style="font-size:15px; color:#26AFFF; font-style:italic;">"<?php echo nl2br(esc_html($final['mensagemParaDesigner'] ?? '—')); ?>"</span></p>
              <p><strong>História da marca:</strong><br><?php echo nl2br(esc_html($final['historiaDaMarca'] ?? '—')); ?></p>
              <p><strong>Maior medo / O que seria um fracasso:</strong><br><?php echo nl2br(esc_html($final['medoSobreAMarca'] ?? '—')); ?></p>
              <p><strong>Visão em 3 a 5 anos:</strong><br><?php echo nl2br(esc_html($final['visaoDeFuturo'] ?? '—')); ?></p>
              <p><strong>Informações adicionais:</strong><br><?php echo nl2br(esc_html($final['informacaoAdicional'] ?? '—')); ?></p>
            </div>

          </div>

          <!-- Raw JSON Viewer -->
          <div style="margin-top:20px; border-top:1px solid #2A3A47; padding-top:16px;">
            <details style="color:#7A8B95; font-size:12px;">
              <summary style="cursor:pointer; color:#26AFFF;">Ver Payload JSON Bruto</summary>
              <pre style="background:#0A1118; border:1px solid #2A3A47; padding:16px; border-radius:8px; overflow-x:auto; color:#F5F2EC; font-size:12px; margin-top:10px;"><?php echo esc_html(wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            </details>
          </div>

        </div>
      <?php endif; ?>

      <!-- Filter Cards -->
      <div class="sued-stats-grid">
        <a href="<?php echo esc_url(remove_query_arg(['status', 'id'])); ?>" class="sued-stat-card <?php echo !$filter_status ? 'active' : ''; ?>">
          <div class="sued-stat-num" style="color:#F5F2EC;"><?php echo esc_html($total_all); ?></div>
          <div class="sued-stat-label">Todos os Briefings</div>
        </a>
        <?php foreach ($all_statuses as $st_key => $st_data):
            $count = isset($counts_raw[$st_key]) ? $counts_raw[$st_key]->total : 0;
            $is_active = ($filter_status === $st_key);
        ?>
          <a href="<?php echo esc_url(add_query_arg('status', $st_key, remove_query_arg('id'))); ?>" class="sued-stat-card <?php echo $is_active ? 'active' : ''; ?>">
            <div class="sued-stat-num" style="color:<?php echo esc_attr($st_data['color']); ?>;"><?php echo esc_html($count); ?></div>
            <div class="sued-stat-label"><?php echo esc_html($st_data['icon'] . ' ' . $st_data['label']); ?></div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Submissions Table -->
      <table class="sued-crm-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Empresa / Projeto</th>
            <th>Cliente</th>
            <th>E-mail</th>
            <th>Setor</th>
            <th>Tipo</th>
            <th>Status</th>
            <th>Data</th>
            <th style="text-align:right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($submissions)): ?>
            <tr>
              <td colspan="9" style="text-align:center; padding:40px; color:#7A8B95;">
                Nenhum briefing encontrado.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($submissions as $sub):
                $st = $all_statuses[$sub->status] ?? ['label' => $sub->status, 'color' => '#26AFFF'];
            ?>
              <tr>
                <td style="color:#7A8B95; font-size:12px;">#<?php echo esc_html($sub->id); ?></td>
                <td><strong style="color:#F5F2EC;"><?php echo esc_html($sub->company_name ?: '—'); ?></strong></td>
                <td><?php echo esc_html($sub->client_name); ?></td>
                <td><a href="mailto:<?php echo esc_attr($sub->email); ?>" style="color:#26AFFF; text-decoration:none;"><?php echo esc_html($sub->email); ?></a></td>
                <td><span style="color:#7A8B95;"><?php echo esc_html($sub->sector ?: '—'); ?></span></td>
                <td><span class="sued-tag gold" style="font-size:11px;"><?php echo esc_html($sub->project_type ?: 'Briefing'); ?></span></td>
                <td>
                  <span class="sued-pill" style="background:<?php echo esc_attr($st['color']); ?>22; color:<?php echo esc_attr($st['color']); ?>; border:1px solid <?php echo esc_attr($st['color']); ?>;">
                    <?php echo esc_html($st['label']); ?>
                  </span>
                </td>
                <td style="color:#7A8B95; font-size:12px;"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($sub->created_at))); ?></td>
                <td style="text-align:right;">
                  <a href="<?php echo esc_url(add_query_arg(['page' => 'sued-brand-discovery', 'id' => $sub->id], admin_url('admin.php'))); ?>" class="sued-btn-sm sued-btn-primary">
                    Ver Briefing &rarr;
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    </div>
    <?php
}

/* ─── 5. Shortcode Support [sued_brand_discovery] ───────────────── */

add_shortcode('sued_brand_discovery', function (): string {
    ob_start();
    include SUED_DIR . '/blocks/brand-discovery/template.php';
    return ob_get_clean();
});

