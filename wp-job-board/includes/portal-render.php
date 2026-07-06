<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wjb_portal_get_stats() {
    global $wpdb;

    $active = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID)
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
         WHERE p.post_type = %s AND p.post_status = %s",
        '_wjb_active', '1', 'job_listing', 'publish'
    ) );

    $hidden = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID)
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
         WHERE p.post_type = %s AND p.post_status = %s",
        '_wjb_active', '0', 'job_listing', 'publish'
    ) );

    $month_start = gmdate( 'Y-m-01 00:00:00' );
    $added_month = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(ID)
         FROM {$wpdb->posts}
         WHERE post_type = %s AND post_status = %s AND post_date_gmt >= %s",
        'job_listing', 'publish', $month_start
    ) );

    return array(
        'active'      => $active,
        'hidden'      => $hidden,
        'total'       => $active + $hidden,
        'added_month' => $added_month,
    );
}

function wjb_portal_render_page() {
    // ── Handle login POST ────────────────────────────────────────────────
    $login_error = '';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wjb_portal_login'] ) ) {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'wjb_portal_login' ) ) {
            $login_error = 'Invalid request. Please try again.';
        } else {
            $pw = isset( $_POST['wjb_portal_password'] ) ? $_POST['wjb_portal_password'] : '';
            if ( wjb_portal_attempt_login( $pw ) ) {
                $slug = get_option( 'wjb_portal_slug', 'job-board-admin' );
                wp_safe_redirect( home_url( '/' . $slug . '/' ) );
                exit;
            }
            $login_error = 'Incorrect password. Please try again.';
        }
    }

    $is_auth  = wjb_portal_is_authenticated();
    $has_pw   = (bool) get_option( 'wjb_portal_password_hash' );

    // ── Data ─────────────────────────────────────────────────────────────
    $stats      = [];
    $jobs       = [];
    $nonce      = '';
    $logout_url = '';

    if ( $is_auth ) {
        $stats = wjb_portal_get_stats();
        $jobs  = get_posts( [
            'post_type'              => 'job_listing',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'update_post_meta_cache' => true,
        ] );
        $nonce      = wp_create_nonce( 'wjb_portal_nonce' );
        $slug       = get_option( 'wjb_portal_slug', 'job-board-admin' );
        $logout_url = home_url( '/' . $slug . '/logout/' );
    }

    $site_name = get_bloginfo( 'name' );
    $ajax_url  = admin_url( 'admin-ajax.php' );
    $levels    = [ 'Entry Level', 'Mid Level', 'Senior', 'Lead', 'Management', 'Director', 'C-Level' ];
    $types     = [ 'Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship' ];

    // Suppress any output buffered from WP hooks before we take over
    while ( ob_get_level() > 0 ) ob_end_clean();

    header( 'Content-Type: text/html; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex, nofollow' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $is_auth ? 'Job Board — Admin' : 'Job Board — Sign In'; ?> | <?php echo esc_html( $site_name ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?php echo esc_url( WJB_URL . 'assets/portal.css' ); ?>?v=1.1.1">
</head>
<body class="wjbp-body">

<?php if ( ! $is_auth ) : ?>
<!-- ════════════════════════════════════════════════════════════════════════
     LOGIN
     ════════════════════════════════════════════════════════════════════════ -->
<div class="wjbp-login-wrap">
    <div class="wjbp-login-card">

        <div class="wjbp-login-logo">
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect width="32" height="32" rx="8" fill="#8b5cf6"/>
                <path d="M8 10h16M8 16h11M8 22h13" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
            <span>Job Board</span>
        </div>

        <h1 class="wjbp-login-title">Welcome back</h1>
        <p class="wjbp-login-sub">Sign in to manage your listings.</p>

        <?php if ( ! $has_pw ) : ?>
            <div class="wjbp-notice wjbp-notice--warn">
                No portal password has been configured yet. Please ask your administrator to set one.
            </div>
        <?php else : ?>

            <?php if ( $login_error ) : ?>
                <div class="wjbp-notice wjbp-notice--error"><?php echo esc_html( $login_error ); ?></div>
            <?php endif; ?>

            <form method="post" class="wjbp-login-form" autocomplete="on">
                <?php wp_nonce_field( 'wjb_portal_login' ); ?>
                <div class="wjbp-field">
                    <label for="wjbp-pw">Password</label>
                    <input type="password" id="wjbp-pw" name="wjb_portal_password"
                           required autocomplete="current-password" autofocus />
                </div>
                <button type="submit" name="wjb_portal_login" value="1"
                        class="wjbp-btn wjbp-btn--primary wjbp-btn--full">
                    Sign in
                </button>
            </form>

        <?php endif; ?>
    </div>
</div>

<?php else : ?>
<!-- ════════════════════════════════════════════════════════════════════════
     DASHBOARD
     ════════════════════════════════════════════════════════════════════════ -->
<div class="wjbp-app">

    <!-- Nav ──────────────────────────────────────────────────────────── -->
    <header class="wjbp-nav">
        <div class="wjbp-nav-brand">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="4" fill="#8b5cf6"/>
                <path d="M7 8h10M7 12h7M7 16h9" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            Job Board
        </div>
        <div class="wjbp-nav-right">
            <a href="<?php echo esc_url( $logout_url ); ?>" class="wjbp-signout">Sign out</a>
        </div>
    </header>

    <main class="wjbp-main">

        <!-- Page header ──────────────────────────────────────────────── -->
        <div class="wjbp-page-header">
            <div>
                <h1 class="wjbp-page-title">Job Listings</h1>
                <p class="wjbp-page-sub">Manage your open roles.</p>
            </div>
            <button class="wjbp-btn wjbp-btn--primary" id="wjbp-add-btn">+ Add Job</button>
        </div>

        <!-- Stats ────────────────────────────────────────────────────── -->
        <div class="wjbp-stats-row">
            <div class="wjbp-stat-card">
                <div class="wjbp-stat-value wjbp-stat-value--purple" id="wjbp-stat-active">
                    <?php echo esc_html( number_format_i18n( $stats['active'] ) ); ?>
                </div>
                <div class="wjbp-stat-label">Active listings</div>
            </div>
            <div class="wjbp-stat-card">
                <div class="wjbp-stat-value wjbp-stat-value--amber" id="wjbp-stat-hidden">
                    <?php echo esc_html( number_format_i18n( $stats['hidden'] ) ); ?>
                </div>
                <div class="wjbp-stat-label">Hidden listings</div>
            </div>
            <div class="wjbp-stat-card">
                <div class="wjbp-stat-value" id="wjbp-stat-total">
                    <?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?>
                </div>
                <div class="wjbp-stat-label">Total published</div>
            </div>
            <div class="wjbp-stat-card">
                <div class="wjbp-stat-value wjbp-stat-value--green" id="wjbp-stat-month">
                    <?php echo esc_html( number_format_i18n( $stats['added_month'] ) ); ?>
                </div>
                <div class="wjbp-stat-label">Added in <?php echo esc_html( gmdate( 'F' ) ); ?></div>
            </div>
        </div>

        <!-- Jobs table ───────────────────────────────────────────────── -->
        <div class="wjbp-card" id="wjbp-jobs-card">
            <?php if ( empty( $jobs ) ) : ?>
                <div class="wjbp-empty" id="wjbp-empty-state">
                    <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect x="8" y="12" width="32" height="28" rx="4" stroke="#3b3b55" stroke-width="2"/>
                        <path d="M16 20h16M16 27h10M16 34h12" stroke="#3b3b55" stroke-width="2" stroke-linecap="round"/>
                        <path d="M32 8v8M28 12h8" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <p>No job listings yet.</p>
                    <button class="wjbp-btn wjbp-btn--primary" id="wjbp-add-btn-empty">Add your first job</button>
                </div>
            <?php endif; ?>

            <table class="wjbp-table<?php echo empty( $jobs ) ? ' wjbp-hidden' : ''; ?>" id="wjbp-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Sector</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="wjbp-th-act"></th>
                    </tr>
                </thead>
                <tbody id="wjbp-tbody">
                    <?php foreach ( $jobs as $job ) :
                        $active   = get_post_meta( $job->ID, '_wjb_active',   true );
                        $sector   = get_post_meta( $job->ID, '_wjb_sector',   true );
                        $location = get_post_meta( $job->ID, '_wjb_location', true );
                        $level    = get_post_meta( $job->ID, '_wjb_level',    true );
                        $type     = get_post_meta( $job->ID, '_wjb_type',     true );
                        $apply    = get_post_meta( $job->ID, '_wjb_apply',    true );
                        $is_active = ( $active === '1' );
                    ?>
                    <tr class="wjbp-row" data-id="<?php echo esc_attr( $job->ID ); ?>"
                        data-active="<?php echo esc_attr( $active ); ?>"
                        data-title="<?php echo esc_attr( $job->post_title ); ?>"
                        data-description="<?php echo esc_attr( $job->post_content ); ?>"
                        data-sector="<?php echo esc_attr( $sector ); ?>"
                        data-location="<?php echo esc_attr( $location ); ?>"
                        data-level="<?php echo esc_attr( $level ); ?>"
                        data-type="<?php echo esc_attr( $type ); ?>"
                        data-apply="<?php echo esc_attr( $apply ); ?>">
                        <td class="wjbp-td-title"><?php echo esc_html( $job->post_title ); ?></td>
                        <td class="wjbp-td-meta"><?php echo esc_html( $sector ?: '—' ); ?></td>
                        <td class="wjbp-td-meta"><?php echo esc_html( $location ?: '—' ); ?></td>
                        <td>
                            <?php if ( $type ) : ?>
                                <span class="wjbp-badge"><?php echo esc_html( $type ); ?></span>
                            <?php else : ?>
                                <span class="wjbp-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="wjbp-toggle<?php echo $is_active ? ' is-active' : ''; ?>"
                                    data-id="<?php echo esc_attr( $job->ID ); ?>"
                                    aria-label="<?php echo $is_active ? 'Active — click to hide' : 'Hidden — click to activate'; ?>"
                                    title="<?php echo $is_active ? 'Active' : 'Hidden'; ?>">
                                <span class="wjbp-toggle-knob"></span>
                            </button>
                        </td>
                        <td class="wjbp-td-actions">
                            <button class="wjbp-icon-btn wjbp-edit-btn"
                                    data-id="<?php echo esc_attr( $job->ID ); ?>"
                                    aria-label="Edit">
                                <svg viewBox="0 0 20 20" fill="none">
                                    <path d="M13.586 3.586a2 2 0 1 1 2.828 2.828L7 15.828 3 17l1.172-4 9.414-9.414z"
                                          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <button class="wjbp-icon-btn wjbp-icon-btn--danger wjbp-delete-btn"
                                    data-id="<?php echo esc_attr( $job->ID ); ?>"
                                    aria-label="Delete">
                                <svg viewBox="0 0 20 20" fill="none">
                                    <path d="M4 6h12M8 6V4h4v2M9 10v5M11 10v5M5 6l1 11a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-11"
                                          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- .wjbp-card -->

    </main>
</div><!-- .wjbp-app -->


<!-- ════════════════════════════════════════════════════════════════════════
     ADD / EDIT MODAL
     ════════════════════════════════════════════════════════════════════════ -->
<div class="wjbp-overlay" id="wjbp-modal-overlay" aria-hidden="true">
    <div class="wjbp-modal" role="dialog" aria-modal="true" aria-labelledby="wjbp-modal-heading">

        <div class="wjbp-modal-header">
            <h2 class="wjbp-modal-heading" id="wjbp-modal-heading">Add Job</h2>
            <button class="wjbp-close-btn" id="wjbp-modal-close" aria-label="Close">
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <form class="wjbp-modal-body" id="wjbp-job-form" autocomplete="off" novalidate>
            <input type="hidden" id="wjbp-job-id" name="id" value="">

            <div class="wjbp-field">
                <label for="wjbp-f-title">Job Title <span class="wjbp-req" aria-hidden="true">*</span></label>
                <input type="text" id="wjbp-f-title" name="title" required
                       placeholder="e.g. Senior UX Designer" />
            </div>

            <div class="wjbp-form-grid">
                <div class="wjbp-field">
                    <label for="wjbp-f-sector">Sector</label>
                    <input type="text" id="wjbp-f-sector" name="sector"
                           placeholder="e.g. Technology, HR" />
                </div>
                <div class="wjbp-field">
                    <label for="wjbp-f-location">Location</label>
                    <input type="text" id="wjbp-f-location" name="location"
                           placeholder="e.g. Remote, Colombo" />
                </div>
                <div class="wjbp-field">
                    <label for="wjbp-f-level">Level</label>
                    <select id="wjbp-f-level" name="level">
                        <option value="">— Select —</option>
                        <?php foreach ( $levels as $l ) : ?>
                            <option value="<?php echo esc_attr( $l ); ?>"><?php echo esc_html( $l ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wjbp-field">
                    <label for="wjbp-f-type">Job Type</label>
                    <select id="wjbp-f-type" name="type">
                        <option value="">— Select —</option>
                        <?php foreach ( $types as $t ) : ?>
                            <option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="wjbp-field">
                <label for="wjbp-f-apply">Apply Link or Email</label>
                <input type="text" id="wjbp-f-apply" name="apply"
                       placeholder="https://... or careers@company.com" />
            </div>

            <div class="wjbp-field">
                <label for="wjbp-f-description">Job Description</label>
                <textarea id="wjbp-f-description" name="description" rows="6"
                          placeholder="Describe the role, responsibilities, and what you're looking for…"></textarea>
            </div>

            <div class="wjbp-field wjbp-field--inline">
                <label class="wjbp-switch-wrap" for="wjbp-f-active">
                    <input type="checkbox" id="wjbp-f-active" name="active" value="1" checked>
                    <span class="wjbp-switch" aria-hidden="true"></span>
                    <span class="wjbp-switch-label">Active listing</span>
                </label>
                <p class="wjbp-hint">Uncheck to hide this job without deleting it.</p>
            </div>

            <div class="wjbp-modal-footer">
                <button type="button" class="wjbp-btn wjbp-btn--ghost" id="wjbp-form-cancel">Cancel</button>
                <button type="submit" class="wjbp-btn wjbp-btn--primary" id="wjbp-form-save">Save Job</button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     DELETE CONFIRM
     ════════════════════════════════════════════════════════════════════════ -->
<div class="wjbp-overlay wjbp-overlay--sm" id="wjbp-confirm-overlay" aria-hidden="true">
    <div class="wjbp-modal wjbp-modal--sm" role="dialog" aria-modal="true" aria-labelledby="wjbp-confirm-heading">
        <h2 class="wjbp-modal-heading" id="wjbp-confirm-heading">Delete this job?</h2>
        <p class="wjbp-confirm-body">This will permanently remove the listing and cannot be undone.</p>
        <div class="wjbp-modal-footer">
            <button class="wjbp-btn wjbp-btn--ghost" id="wjbp-confirm-cancel">Cancel</button>
            <button class="wjbp-btn wjbp-btn--danger" id="wjbp-confirm-ok">Yes, delete</button>
        </div>
    </div>
</div>

<!-- Toast ──────────────────────────────────────────────────────────────── -->
<div class="wjbp-toast" id="wjbp-toast" role="status" aria-live="polite" aria-atomic="true"></div>

<?php endif; ?>

<script>
var WJB_PORTAL = <?php echo wp_json_encode( [
    'ajaxUrl' => $ajax_url,
    'nonce'   => $nonce,
] ); ?>;
</script>
<?php if ( $is_auth ) : ?>
<script src="<?php echo esc_url( WJB_URL . 'assets/portal.js' ); ?>?v=1.1.1"></script>
<?php endif; ?>
</body>
</html>
<?php
}
