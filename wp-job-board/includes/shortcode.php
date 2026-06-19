<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return an inline SVG icon for a given sector name.
 * Matches loosely on keywords so free-text sector values still map to an icon;
 * falls back to a briefcase for anything unrecognised.
 */
function wjb_sector_icon( $sector ) {
    $key = strtolower( (string) $sector );

    $icons = array(
        // keyword => svg inner markup (24x24 viewBox, stroke = currentColor)
        'finance'    => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9a2.5 2.5 0 0 0-2.5-1.5c-1.4 0-2.5.8-2.5 2s1.1 1.6 2.5 2 2.5.8 2.5 2-1.1 2-2.5 2A2.5 2.5 0 0 1 9.5 15M12 6v1.5M12 16.5V18"/>',
        'account'    => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9a2.5 2.5 0 0 0-2.5-1.5c-1.4 0-2.5.8-2.5 2s1.1 1.6 2.5 2 2.5.8 2.5 2-1.1 2-2.5 2A2.5 2.5 0 0 1 9.5 15M12 6v1.5M12 16.5V18"/>',
        'tech'       => '<polyline points="3 13 8 13 10 17 14 7 16 13 21 13"/>',
        ' it'        => '<polyline points="3 13 8 13 10 17 14 7 16 13 21 13"/>',
        'software'   => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'human'      => '<circle cx="9" cy="7" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="18" cy="9" r="2.2"/><path d="M16 20a4 4 0 0 1 6-3"/>',
        'hr'         => '<circle cx="9" cy="7" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="18" cy="9" r="2.2"/><path d="M16 20a4 4 0 0 1 6-3"/>',
        'recruit'    => '<circle cx="9" cy="7" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="18" cy="9" r="2.2"/><path d="M16 20a4 4 0 0 1 6-3"/>',
        'marketing'  => '<path d="M3 11v3a1 1 0 0 0 1 1h3l4 4V7L7 11H4a1 1 0 0 0-1 0z"/><path d="M16 8a5 5 0 0 1 0 8"/><path d="M19 5a9 9 0 0 1 0 14"/>',
        'creative'   => '<path d="M3 11v3a1 1 0 0 0 1 1h3l4 4V7L7 11H4a1 1 0 0 0-1 0z"/><path d="M16 8a5 5 0 0 1 0 8"/><path d="M19 5a9 9 0 0 1 0 14"/>',
        'media'      => '<path d="M3 11v3a1 1 0 0 0 1 1h3l4 4V7L7 11H4a1 1 0 0 0-1 0z"/><path d="M16 8a5 5 0 0 1 0 8"/><path d="M19 5a9 9 0 0 1 0 14"/>',
        'engineer'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'customer'   => '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 0zM20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 0z"/>',
        'support'    => '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 0zM20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 0z"/>',
        'education'  => '<path d="M3 9l9-4 9 4-9 4-9-4z"/><path d="M7 11v4c0 1 2.5 2.5 5 2.5s5-1.5 5-2.5v-4"/>',
        'teach'      => '<path d="M3 9l9-4 9 4-9 4-9-4z"/><path d="M7 11v4c0 1 2.5 2.5 5 2.5s5-1.5 5-2.5v-4"/>',
        'train'      => '<path d="M3 9l9-4 9 4-9 4-9-4z"/><path d="M7 11v4c0 1 2.5 2.5 5 2.5s5-1.5 5-2.5v-4"/>',
        'hospitality'=> '<path d="M5 11h14a0 0 0 0 1 0 0 7 7 0 0 1-14 0 0 0 0 0 1 0 0z"/><path d="M3 11h18M12 4v3"/>',
        'food'       => '<path d="M5 11h14a0 0 0 0 1 0 0 7 7 0 0 1-14 0 0 0 0 0 1 0 0z"/><path d="M3 11h18M12 4v3"/>',
        'ship'       => '<path d="M2 16h11V8h3l4 4v4h-2M2 16a2 2 0 0 0 4 0M15 16a2 2 0 0 0 4 0"/>',
        'freight'    => '<path d="M2 16h11V8h3l4 4v4h-2M2 16a2 2 0 0 0 4 0M15 16a2 2 0 0 0 4 0"/>',
        'logistic'   => '<path d="M2 16h11V8h3l4 4v4h-2M2 16a2 2 0 0 0 4 0M15 16a2 2 0 0 0 4 0"/>',
        'apparel'    => '<path d="M12 4a2 2 0 0 0 2 2 4 4 0 0 1 4 4l-2 1v7H8v-7l-2-1a4 4 0 0 1 4-4 2 2 0 0 0 2-2z"/>',
        'fashion'    => '<path d="M12 4a2 2 0 0 0 2 2 4 4 0 0 1 4 4l-2 1v7H8v-7l-2-1a4 4 0 0 1 4-4 2 2 0 0 0 2-2z"/>',
        'retail'     => '<path d="M5 8h14l-1 11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
        'health'     => '<path d="M20 9h-5V4H9v5H4v6h5v5h6v-5h5z"/>',
        'medical'    => '<path d="M20 9h-5V4H9v5H4v6h5v5h6v-5h5z"/>',
        'care'       => '<path d="M20 9h-5V4H9v5H4v6h5v5h6v-5h5z"/>',
        'power'      => '<polygon points="13 2 4 14 11 14 10 22 20 9 13 9 13 2"/>',
        'energy'     => '<polygon points="13 2 4 14 11 14 10 22 20 9 13 9 13 2"/>',
        'utilit'     => '<polygon points="13 2 4 14 11 14 10 22 20 9 13 9 13 2"/>',
        'admin'      => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'operation'  => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    );

    $inner = '<rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>'; // default: briefcase
    foreach ( $icons as $needle => $svg ) {
        if ( strpos( $key, trim( $needle ) ) !== false ) {
            $inner = $svg;
            break;
        }
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

function wjb_render_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'per_page'   => 0,
        'sector'     => '',
        'location'   => '',
        'level'      => '',
        'type'       => '',
        // Hero copy (override per-page if desired)
        'show_hero'  => 'yes',
        'eyebrow'    => 'Where We Place Talent',
        'heading'    => 'Whatever <em>Your Field.</em><br>Whatever <em>Your Level.</em>',
        'subheading' => 'From recent graduates to seasoned executives, we place professionals across 12+ industries in Sri Lanka, the UK and Singapore.',
    ), $atts, 'job_board' );

    $per_page     = max( 0, intval( $atts['per_page'] ) );
    $current_page = max( 1, isset( $_GET['wjb_page'] ) ? intval( $_GET['wjb_page'] ) : 1 );

    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => '_wjb_active',
            'value'   => '1',
            'compare' => '=',
        ),
    );

    // Pre-filter via shortcode attributes (e.g. [job_board sector="Technology"])
    $attr_meta_map = array(
        'sector'   => '_wjb_sector',
        'location' => '_wjb_location',
        'level'    => '_wjb_level',
        'type'     => '_wjb_type',
    );
    foreach ( $attr_meta_map as $attr => $meta_key ) {
        if ( ! empty( $atts[ $attr ] ) ) {
            $meta_query[] = array(
                'key'     => $meta_key,
                'value'   => sanitize_text_field( $atts[ $attr ] ),
                'compare' => '=',
            );
        }
    }

    $query_args = array(
        'post_type'              => 'job_listing',
        'post_status'            => 'publish',
        'meta_query'             => $meta_query,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'posts_per_page'         => $per_page > 0 ? $per_page : -1,
        'paged'                  => $per_page > 0 ? $current_page : 1,
        'update_post_meta_cache' => true,
        'no_found_rows'          => $per_page === 0,
    );

    $query     = new WP_Query( $query_args );
    $jobs      = $query->posts;
    $max_pages = (int) $query->max_num_pages;
    $total     = $per_page > 0 ? (int) $query->found_posts : count( $jobs );
    wp_reset_postdata();

    // Collect unique filter values from queried jobs (uses meta cache, no extra queries)
    $sectors   = array();
    $locations = array();
    $levels    = array();
    $types     = array();

    foreach ( $jobs as $job ) {
        $s = get_post_meta( $job->ID, '_wjb_sector',   true );
        $l = get_post_meta( $job->ID, '_wjb_location', true );
        $v = get_post_meta( $job->ID, '_wjb_level',    true );
        $t = get_post_meta( $job->ID, '_wjb_type',     true );
        if ( $s ) $sectors[]   = $s;
        if ( $l ) $locations[] = $l;
        if ( $v ) $levels[]    = $v;
        if ( $t ) $types[]     = $t;
    }

    $sectors   = array_values( array_unique( $sectors ) );
    $locations = array_values( array_unique( $locations ) );
    $levels    = array_values( array_unique( $levels ) );
    $types     = array_values( array_unique( $types ) );
    sort( $sectors );

    // Allowed inline tags for the hero heading override
    $heading_tags = array( 'em' => array(), 'br' => array(), 'span' => array( 'class' => array() ), 'strong' => array() );

    ob_start();
    ?>
    <div class="wjb-wrap">

        <?php if ( $atts['show_hero'] !== 'no' && $atts['show_hero'] !== '0' ) : ?>
        <!-- Hero -->
        <header class="wjb-hero">
            <?php if ( $atts['eyebrow'] ) : ?>
                <span class="wjb-eyebrow"><?php echo esc_html( $atts['eyebrow'] ); ?></span>
            <?php endif; ?>
            <?php if ( $atts['heading'] ) : ?>
                <h1 class="wjb-heading"><?php echo wp_kses( $atts['heading'], $heading_tags ); ?></h1>
            <?php endif; ?>
            <?php if ( $atts['subheading'] ) : ?>
                <p class="wjb-subheading"><?php echo esc_html( $atts['subheading'] ); ?></p>
            <?php endif; ?>
        </header>
        <?php endif; ?>

        <!-- Search -->
        <div class="wjb-search-wrap">
            <input
                type="text"
                id="wjb-search"
                class="wjb-search"
                placeholder="Search roles, skills or keywords"
                autocomplete="off"
            />
        </div>

        <?php if ( ! empty( $sectors ) ) : ?>
        <!-- Sector pills -->
        <p class="wjb-pills-label">Sectors Listed:</p>
        <div class="wjb-sector-pills" id="wjb-sector-pills" role="group" aria-label="Filter by sector">
            <?php foreach ( $sectors as $s ) : ?>
                <button type="button" class="wjb-sector-pill" data-sector="<?php echo esc_attr( $s ); ?>" aria-pressed="false">
                    <?php echo wjb_sector_icon( $s ); // safe: static inline SVG ?>
                    <span><?php echo esc_html( $s ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Refine bar: Location / Level / Type + Reset -->
        <div class="wjb-refine">
            <span class="wjb-refine-label">Refine</span>
            <select id="wjb-filter-location" class="wjb-filter" aria-label="Filter by location">
                <option value="">All locations</option>
                <?php foreach ( $locations as $l ) : ?>
                    <option value="<?php echo esc_attr( $l ); ?>"><?php echo esc_html( $l ); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="wjb-filter-level" class="wjb-filter" aria-label="Filter by level">
                <option value="">All levels</option>
                <?php foreach ( $levels as $v ) : ?>
                    <option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $v ); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="wjb-filter-type" class="wjb-filter" aria-label="Filter by job type">
                <option value="">All types</option>
                <?php foreach ( $types as $t ) : ?>
                    <option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
                <?php endforeach; ?>
            </select>
            <button id="wjb-reset" class="wjb-reset-btn" style="display:none;" aria-label="Clear all filters">Clear all ✕</button>
        </div>

        <!-- Result count -->
        <p class="wjb-count" id="wjb-count" data-total="<?php echo esc_attr( $total ); ?>">
            <?php
            /* translators: %s: number of open roles */
            echo esc_html( sprintf( _n( '%s open role', '%s open roles', $total, 'wp-job-board' ), number_format_i18n( $total ) ) );
            ?>
        </p>

        <!-- Grid -->
        <div class="wjb-grid" id="wjb-grid">
            <?php if ( empty( $jobs ) ) : ?>
                <p class="wjb-no-results">No open positions right now. Check back soon.</p>
            <?php else : ?>
                <?php foreach ( $jobs as $job ) :
                    $sector   = get_post_meta( $job->ID, '_wjb_sector',   true );
                    $location = get_post_meta( $job->ID, '_wjb_location', true );
                    $level    = get_post_meta( $job->ID, '_wjb_level',    true );
                    $type     = get_post_meta( $job->ID, '_wjb_type',     true );
                    $apply    = get_post_meta( $job->ID, '_wjb_apply',    true );
                    $desc     = apply_filters( 'the_content', $job->post_content );
                    $posted   = human_time_diff( get_post_time( 'U', true, $job->ID ), time() ) . ' ago';

                    $tags     = array_filter( array( $sector, $location, $level ) );
                    $tags_str = implode( ' · ', array_map( 'strtoupper', $tags ) );

                    $data  = 'data-title="'    . esc_attr( strtolower( $job->post_title ) ) . '"';
                    $data .= ' data-sector="'  . esc_attr( $sector )   . '"';
                    $data .= ' data-location="'. esc_attr( $location ) . '"';
                    $data .= ' data-level="'   . esc_attr( $level )    . '"';
                    $data .= ' data-type="'    . esc_attr( $type )     . '"';
                    $data .= ' data-desc="'    . esc_attr( strtolower( wp_strip_all_tags( $job->post_content ) ) ) . '"';
                ?>
                <div class="wjb-card" <?php echo $data; ?>>
                    <div class="wjb-card-top">
                        <?php if ( $sector ) : ?>
                            <span class="wjb-card-sector"><?php echo esc_html( $sector ); ?></span>
                        <?php else : ?>
                            <span class="wjb-card-sector">Open Role</span>
                        <?php endif; ?>
                        <?php if ( $type ) : ?>
                            <span class="wjb-card-type"><?php echo esc_html( $type ); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="wjb-card-title"><?php echo esc_html( $job->post_title ); ?></h3>

                    <?php if ( $location || $level ) : ?>
                    <div class="wjb-card-meta">
                        <?php if ( $location ) : ?>
                            <span class="wjb-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/></svg>
                                <?php echo esc_html( $location ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $level ) : ?>
                            <span class="wjb-chip">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" y1="20" x2="6" y2="13"/><line x1="12" y1="20" x2="12" y2="8"/><line x1="18" y1="20" x2="18" y2="4"/></svg>
                                <?php echo esc_html( $level ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <p class="wjb-card-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $job->post_content ), 20, '.' ) ); ?></p>

                    <div class="wjb-card-posted">Posted <?php echo esc_html( $posted ); ?></div>

                    <div class="wjb-card-actions">
                        <button
                            class="wjb-btn wjb-btn-outline wjb-open-modal"
                            data-id="<?php echo esc_attr( $job->ID ); ?>"
                            data-title="<?php echo esc_attr( $job->post_title ); ?>"
                            data-tags="<?php echo esc_attr( $tags_str ); ?>"
                            data-desc="<?php echo esc_attr( $desc ); ?>"
                            data-apply="<?php echo esc_attr( $apply ); ?>"
                            data-type="<?php echo esc_attr( $type ); ?>"
                            data-posted="<?php echo esc_attr( $posted ); ?>"
                        >View Full Role</button>
                        <?php if ( $apply ) : ?>
                            <a href="<?php echo esc_url( $apply ); ?>" class="wjb-btn wjb-btn-solid" target="_blank" rel="noopener noreferrer">Quick Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p class="wjb-no-results" id="wjb-empty" style="display:none;">No roles match your search. Try different filters.</p>

        <?php if ( $per_page > 0 && $max_pages > 1 ) :
            $base_url = remove_query_arg( 'wjb_page' );
        ?>
        <nav class="wjb-pagination" aria-label="Job listings pages">
            <?php if ( $current_page > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'wjb_page', $current_page - 1, $base_url ) ); ?>" class="wjb-page-btn">← Prev</a>
            <?php else : ?>
                <span class="wjb-page-btn wjb-page-btn--disabled">← Prev</span>
            <?php endif; ?>
            <span class="wjb-page-info">Page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $max_pages ); ?></span>
            <?php if ( $current_page < $max_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'wjb_page', $current_page + 1, $base_url ) ); ?>" class="wjb-page-btn">Next →</a>
            <?php else : ?>
                <span class="wjb-page-btn wjb-page-btn--disabled">Next →</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

    </div><!-- .wjb-wrap -->

    <!-- Modal -->
    <div class="wjb-modal-overlay" id="wjb-modal-overlay" aria-hidden="true">
        <div class="wjb-modal" role="dialog" aria-modal="true" aria-labelledby="wjb-modal-title">
            <button class="wjb-modal-close" id="wjb-modal-close" aria-label="Close">✕</button>
            <div class="wjb-modal-tags" id="wjb-modal-tags"></div>
            <h2 class="wjb-modal-title" id="wjb-modal-title"></h2>
            <div class="wjb-modal-meta" id="wjb-modal-meta"></div>
            <div class="wjb-modal-body" id="wjb-modal-body"></div>
            <div class="wjb-modal-footer" id="wjb-modal-footer"></div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'job_board', 'wjb_render_shortcode' );
