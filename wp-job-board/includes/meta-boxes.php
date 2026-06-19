<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register meta boxes ────────────────────────────────────────────────────
function wjb_add_meta_boxes() {
    add_meta_box(
        'wjb_job_details',
        'Job Details',
        'wjb_render_meta_box',
        'job_listing',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wjb_add_meta_boxes' );


// ── Render meta box HTML ───────────────────────────────────────────────────
function wjb_render_meta_box( $post ) {
    wp_nonce_field( 'wjb_save_meta', 'wjb_nonce' );

    $sector   = get_post_meta( $post->ID, '_wjb_sector',   true );
    $location = get_post_meta( $post->ID, '_wjb_location', true );
    $level    = get_post_meta( $post->ID, '_wjb_level',    true );
    $type     = get_post_meta( $post->ID, '_wjb_type',     true );
    $apply    = get_post_meta( $post->ID, '_wjb_apply',    true );
    $active   = get_post_meta( $post->ID, '_wjb_active',   true );

    // Default active to yes on new posts
    if ( $active === '' ) $active = '1';
    ?>
    <style>
        .wjb-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .wjb-meta-grid label, .wjb-meta-full label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
        .wjb-meta-grid input, .wjb-meta-grid select,
        .wjb-meta-full input { width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .wjb-meta-full { margin-bottom: 16px; }
        .wjb-meta-active { display: flex; align-items: center; gap: 8px; padding: 12px; background: #f9f9f9; border-radius: 6px; border: 1px solid #e5e5e5; }
        .wjb-meta-active input { width: auto; margin: 0; }
        .wjb-meta-active span { font-size: 13px; color: #555; }
    </style>

    <div class="wjb-meta-grid">
        <div>
            <label for="wjb_sector">Sector / Department</label>
            <input type="text" id="wjb_sector" name="wjb_sector" value="<?php echo esc_attr( $sector ); ?>" placeholder="e.g. Technology, HR, Marketing" />
        </div>
        <div>
            <label for="wjb_location">Location</label>
            <input type="text" id="wjb_location" name="wjb_location" value="<?php echo esc_attr( $location ); ?>" placeholder="e.g. Remote, Colombo, London" />
        </div>
        <div>
            <label for="wjb_level">Level</label>
            <select id="wjb_level" name="wjb_level">
                <option value="">— Select —</option>
                <?php
                $levels = array( 'Entry Level', 'Mid Level', 'Senior', 'Lead', 'Management', 'Director', 'C-Level' );
                foreach ( $levels as $l ) {
                    echo '<option value="' . esc_attr( $l ) . '"' . selected( $level, $l, false ) . '>' . esc_html( $l ) . '</option>';
                }
                ?>
            </select>
        </div>
        <div>
            <label for="wjb_type">Job Type</label>
            <select id="wjb_type" name="wjb_type">
                <option value="">— Select —</option>
                <?php
                $types = array( 'Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship' );
                foreach ( $types as $t ) {
                    echo '<option value="' . esc_attr( $t ) . '"' . selected( $type, $t, false ) . '>' . esc_html( $t ) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="wjb-meta-full">
        <label for="wjb_apply">Apply Link or Email</label>
        <input type="text" id="wjb_apply" name="wjb_apply" value="<?php echo esc_attr( $apply ); ?>" placeholder="https://... or mailto:careers@yourcompany.com" />
    </div>

    <div class="wjb-meta-active">
        <input type="checkbox" id="wjb_active" name="wjb_active" value="1" <?php checked( $active, '1' ); ?> />
        <label for="wjb_active" style="font-weight:600;margin:0;">Active listing</label>
        <span>— Uncheck to hide this job from the board without deleting it.</span>
    </div>
    <?php
}


// ── Save meta ──────────────────────────────────────────────────────────────
function wjb_save_meta( $post_id ) {
    if ( ! isset( $_POST['wjb_nonce'] ) || ! wp_verify_nonce( $_POST['wjb_nonce'], 'wjb_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = array( 'wjb_sector', 'wjb_location', 'wjb_level', 'wjb_type' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // Apply link — handle URL or plain email address separately
    if ( isset( $_POST['wjb_apply'] ) ) {
        $raw = trim( $_POST['wjb_apply'] );
        if ( $raw === '' ) {
            update_post_meta( $post_id, '_wjb_apply', '' );
        } elseif ( strpos( $raw, 'mailto:' ) === 0 ) {
            $email = sanitize_email( substr( $raw, 7 ) );
            update_post_meta( $post_id, '_wjb_apply', $email ? 'mailto:' . $email : '' );
        } elseif ( strpos( $raw, '@' ) !== false && strpos( $raw, '/' ) === false ) {
            $email = sanitize_email( $raw );
            update_post_meta( $post_id, '_wjb_apply', $email ? 'mailto:' . $email : '' );
        } else {
            update_post_meta( $post_id, '_wjb_apply', esc_url_raw( $raw ) );
        }
    }

    // Checkbox — explicitly save 0 when unchecked
    update_post_meta( $post_id, '_wjb_active', isset( $_POST['wjb_active'] ) ? '1' : '0' );
}
add_action( 'save_post_job_listing', 'wjb_save_meta' );


// ── Add custom columns to admin list view ──────────────────────────────────
function wjb_admin_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $val ) {
        $new[ $key ] = $val;
        if ( $key === 'title' ) {
            $new['wjb_sector']   = 'Sector';
            $new['wjb_location'] = 'Location';
            $new['wjb_level']    = 'Level';
            $new['wjb_type']     = 'Type';
            $new['wjb_active']   = 'Active';
        }
    }
    return $new;
}
add_filter( 'manage_job_listing_posts_columns', 'wjb_admin_columns' );

function wjb_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'wjb_sector':   echo esc_html( get_post_meta( $post_id, '_wjb_sector',   true ) ); break;
        case 'wjb_location': echo esc_html( get_post_meta( $post_id, '_wjb_location', true ) ); break;
        case 'wjb_level':    echo esc_html( get_post_meta( $post_id, '_wjb_level',    true ) ); break;
        case 'wjb_type':     echo esc_html( get_post_meta( $post_id, '_wjb_type',     true ) ); break;
        case 'wjb_active':
            $active = get_post_meta( $post_id, '_wjb_active', true );
            echo $active === '1'
                ? '<span style="color:#2ea44f;font-weight:600;">✓ Active</span>'
                : '<span style="color:#cb2431;">✗ Hidden</span>';
            break;
    }
}
add_action( 'manage_job_listing_posts_custom_column', 'wjb_admin_column_content', 10, 2 );
