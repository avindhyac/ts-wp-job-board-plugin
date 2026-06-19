<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register dashboard page ────────────────────────────────────────────────
function wjb_register_dashboard() {
    add_submenu_page(
        'edit.php?post_type=job_listing',
        'Job Board Dashboard',
        'Dashboard',
        'edit_posts',
        'wjb-dashboard',
        'wjb_render_dashboard'
    );
}
add_action( 'admin_menu', 'wjb_register_dashboard' );


// ── Enqueue dashboard styles ───────────────────────────────────────────────
function wjb_dashboard_styles( $hook ) {
    if ( $hook !== 'job_listing_page_wjb-dashboard' ) return;
    wp_add_inline_style( 'wp-admin', wjb_dashboard_css() );
}
add_action( 'admin_enqueue_scripts', 'wjb_dashboard_styles' );


// ── Data helpers ───────────────────────────────────────────────────────────
function wjb_get_stats() {
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

function wjb_get_breakdown( $meta_key ) {
    global $wpdb;

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.meta_value AS label, COUNT(DISTINCT p.ID) AS cnt
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value != ''
         INNER JOIN {$wpdb->postmeta} pa ON pa.post_id = p.ID AND pa.meta_key = '_wjb_active' AND pa.meta_value = '1'
         WHERE p.post_type = %s AND p.post_status = %s
         GROUP BY pm.meta_value
         ORDER BY cnt DESC",
        $meta_key, 'job_listing', 'publish'
    ) );

    return $rows ? $rows : array();
}

function wjb_get_recent( $limit = 8 ) {
    return get_posts( array(
        'post_type'      => 'job_listing',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array( 'key' => '_wjb_active', 'value' => '1', 'compare' => '=' ),
        ),
        'update_post_meta_cache' => true,
    ) );
}


// ── Render ─────────────────────────────────────────────────────────────────
function wjb_render_dashboard() {
    $stats    = wjb_get_stats();
    $sectors  = wjb_get_breakdown( '_wjb_sector' );
    $types    = wjb_get_breakdown( '_wjb_type' );
    $levels   = wjb_get_breakdown( '_wjb_level' );
    $recent   = wjb_get_recent( 8 );

    $sector_max = $sectors ? (int) $sectors[0]->cnt : 1;
    $month_name = gmdate( 'F' );
    ?>
    <div class="wrap wjb-dash">

        <!-- Header -->
        <div class="wjb-dash-header">
            <div>
                <h1 class="wjb-dash-title">Job Board</h1>
                <p class="wjb-dash-sub">Overview of all active listings and recent activity.</p>
            </div>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=job_listing' ) ); ?>" class="button button-primary wjb-add-btn">
                + Add New Job
            </a>
        </div>

        <!-- Stat cards -->
        <div class="wjb-stat-row">
            <?php
            $cards = array(
                array( 'label' => 'Active Listings',   'value' => $stats['active'],      'color' => '#10b981', 'icon' => '●' ),
                array( 'label' => 'Hidden Listings',   'value' => $stats['hidden'],      'color' => '#f59e0b', 'icon' => '●' ),
                array( 'label' => 'Total Published',   'value' => $stats['total'],       'color' => '#6366f1', 'icon' => '●' ),
                array( 'label' => 'Added in ' . $month_name, 'value' => $stats['added_month'], 'color' => '#0ea5e9', 'icon' => '●' ),
            );
            foreach ( $cards as $card ) : ?>
                <div class="wjb-stat-card">
                    <span class="wjb-stat-dot" style="background:<?php echo esc_attr( $card['color'] ); ?>"></span>
                    <div class="wjb-stat-value"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></div>
                    <div class="wjb-stat-label"><?php echo esc_html( $card['label'] ); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Main columns -->
        <div class="wjb-dash-cols">

            <!-- Left: Recent listings -->
            <div class="wjb-panel wjb-panel-recent">
                <div class="wjb-panel-head">
                    <h2>Recent Active Listings</h2>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=job_listing' ) ); ?>">View all →</a>
                </div>
                <?php if ( empty( $recent ) ) : ?>
                    <p class="wjb-empty">No active listings yet. <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=job_listing' ) ); ?>">Add your first job.</a></p>
                <?php else : ?>
                <table class="wjb-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Sector</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Posted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent as $job ) :
                            $sector   = get_post_meta( $job->ID, '_wjb_sector',   true );
                            $location = get_post_meta( $job->ID, '_wjb_location', true );
                            $type     = get_post_meta( $job->ID, '_wjb_type',     true );
                            $posted   = human_time_diff( get_post_time( 'U', true, $job->ID ), time() ) . ' ago';
                        ?>
                        <tr>
                            <td class="wjb-td-title">
                                <a href="<?php echo esc_url( get_edit_post_link( $job->ID ) ); ?>">
                                    <?php echo esc_html( $job->post_title ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $sector ?: '—' ); ?></td>
                            <td><?php echo esc_html( $location ?: '—' ); ?></td>
                            <td>
                                <?php if ( $type ) : ?>
                                    <span class="wjb-badge"><?php echo esc_html( $type ); ?></span>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="wjb-td-muted"><?php echo esc_html( $posted ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $job->ID ) ); ?>" class="wjb-link-edit">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Right: Breakdowns -->
            <div class="wjb-panel-stack">

                <!-- By sector -->
                <div class="wjb-panel">
                    <div class="wjb-panel-head">
                        <h2>By Sector</h2>
                        <span class="wjb-panel-meta"><?php echo count( $sectors ); ?> active</span>
                    </div>
                    <?php if ( empty( $sectors ) ) : ?>
                        <p class="wjb-empty">No sector data yet.</p>
                    <?php else : ?>
                    <ul class="wjb-bar-list">
                        <?php foreach ( $sectors as $row ) :
                            $pct = $sector_max > 0 ? round( ( $row->cnt / $sector_max ) * 100 ) : 0;
                        ?>
                        <li>
                            <div class="wjb-bar-top">
                                <span class="wjb-bar-label"><?php echo esc_html( $row->label ); ?></span>
                                <span class="wjb-bar-count"><?php echo esc_html( $row->cnt ); ?></span>
                            </div>
                            <div class="wjb-bar-track">
                                <div class="wjb-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <!-- By type + by level (side by side) -->
                <div class="wjb-mini-row">

                    <div class="wjb-panel">
                        <div class="wjb-panel-head">
                            <h2>By Type</h2>
                        </div>
                        <?php if ( empty( $types ) ) : ?>
                            <p class="wjb-empty">No data yet.</p>
                        <?php else : ?>
                        <ul class="wjb-pill-list">
                            <?php
                            $type_colors = array( 'Full-Time' => '#10b981', 'Part-Time' => '#f59e0b', 'Contract' => '#6366f1', 'Freelance' => '#0ea5e9', 'Internship' => '#ec4899' );
                            foreach ( $types as $row ) :
                                $col = isset( $type_colors[ $row->label ] ) ? $type_colors[ $row->label ] : '#888';
                            ?>
                            <li>
                                <span class="wjb-dot" style="background:<?php echo esc_attr( $col ); ?>"></span>
                                <span class="wjb-pill-label"><?php echo esc_html( $row->label ); ?></span>
                                <span class="wjb-pill-count"><?php echo esc_html( $row->cnt ); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>

                    <div class="wjb-panel">
                        <div class="wjb-panel-head">
                            <h2>By Level</h2>
                        </div>
                        <?php if ( empty( $levels ) ) : ?>
                            <p class="wjb-empty">No data yet.</p>
                        <?php else : ?>
                        <ul class="wjb-pill-list">
                            <?php foreach ( $levels as $row ) : ?>
                            <li>
                                <span class="wjb-dot" style="background:#6366f1"></span>
                                <span class="wjb-pill-label"><?php echo esc_html( $row->label ); ?></span>
                                <span class="wjb-pill-count"><?php echo esc_html( $row->cnt ); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>

                </div><!-- .wjb-mini-row -->

            </div><!-- .wjb-panel-stack -->
        </div><!-- .wjb-dash-cols -->

    </div><!-- .wjb-dash -->
    <?php
}


// ── CSS ────────────────────────────────────────────────────────────────────
function wjb_dashboard_css() {
    return '
/* ---- Wrap ---- */
.wjb-dash { max-width: 1200px; }

/* ---- Header ---- */
.wjb-dash-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin: 18px 0 28px;
    flex-wrap: wrap;
}
.wjb-dash-title {
    font-size: 24px !important;
    font-weight: 600 !important;
    margin: 0 0 4px !important;
    color: #1d2327;
    line-height: 1.2;
}
.wjb-dash-sub { margin: 0; color: #646970; font-size: 13px; }
.wjb-add-btn { height: 36px; line-height: 36px !important; padding: 0 16px !important; }

/* ---- Stat cards ---- */
.wjb-stat-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.wjb-stat-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 8px;
    padding: 20px 20px 18px;
    position: relative;
}
.wjb-stat-dot {
    display: block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-bottom: 12px;
}
.wjb-stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    color: #1d2327;
    margin-bottom: 6px;
}
.wjb-stat-label {
    font-size: 12px;
    font-weight: 500;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ---- Main layout ---- */
.wjb-dash-cols {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    align-items: start;
}
.wjb-panel-stack { display: flex; flex-direction: column; gap: 20px; }
.wjb-mini-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ---- Panel ---- */
.wjb-panel {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 8px;
    padding: 0;
    overflow: hidden;
}
.wjb-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px 13px;
    border-bottom: 1px solid #f0f0f1;
}
.wjb-panel-head h2 {
    font-size: 13px !important;
    font-weight: 600 !important;
    margin: 0 !important;
    padding: 0 !important;
    color: #1d2327;
    border: none !important;
}
.wjb-panel-head a { font-size: 12px; color: #2271b1; text-decoration: none; }
.wjb-panel-head a:hover { text-decoration: underline; }
.wjb-panel-meta { font-size: 12px; color: #646970; }
.wjb-empty { padding: 16px 18px; margin: 0; color: #646970; font-size: 13px; }

/* ---- Recent table ---- */
.wjb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.wjb-table thead th {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #646970;
    padding: 9px 12px 8px;
    text-align: left;
    border-bottom: 1px solid #f0f0f1;
    background: #fafafa;
    white-space: nowrap;
}
.wjb-table tbody tr { border-bottom: 1px solid #f6f7f7; }
.wjb-table tbody tr:last-child { border-bottom: none; }
.wjb-table tbody tr:hover { background: #f9f9fb; }
.wjb-table tbody td { padding: 10px 12px; vertical-align: middle; color: #3c434a; }
.wjb-td-title a { font-weight: 500; color: #2271b1; text-decoration: none; }
.wjb-td-title a:hover { text-decoration: underline; }
.wjb-td-muted { color: #646970 !important; font-size: 12px; white-space: nowrap; }
.wjb-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    background: #f0f0f1;
    color: #3c434a;
    border-radius: 4px;
    padding: 2px 8px;
    white-space: nowrap;
}
.wjb-link-edit { font-size: 12px; color: #646970; text-decoration: none; }
.wjb-link-edit:hover { color: #2271b1; }

/* ---- Sector bar list ---- */
.wjb-bar-list { margin: 0; padding: 12px 18px 14px; list-style: none; display: flex; flex-direction: column; gap: 13px; }
.wjb-bar-top { display: flex; justify-content: space-between; margin-bottom: 5px; }
.wjb-bar-label { font-size: 13px; color: #3c434a; font-weight: 400; }
.wjb-bar-count { font-size: 12px; font-weight: 600; color: #1d2327; }
.wjb-bar-track { height: 5px; background: #f0f0f1; border-radius: 99px; overflow: hidden; }
.wjb-bar-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 99px; }

/* ---- Type / level pill list ---- */
.wjb-pill-list { margin: 0; padding: 12px 14px 14px; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.wjb-pill-list li { display: flex; align-items: center; gap: 8px; font-size: 12px; }
.wjb-dot { display: block; width: 7px; height: 7px; border-radius: 50%; flex: 0 0 auto; }
.wjb-pill-label { flex: 1; color: #3c434a; }
.wjb-pill-count { font-weight: 600; color: #1d2327; min-width: 20px; text-align: right; }

@media (max-width: 1100px) {
    .wjb-dash-cols { grid-template-columns: 1fr; }
    .wjb-stat-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .wjb-stat-row { grid-template-columns: 1fr 1fr; }
    .wjb-mini-row { grid-template-columns: 1fr; }
}
';
}
