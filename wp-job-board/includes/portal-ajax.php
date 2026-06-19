<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Shared guard ──────────────────────────────────────────────────────────
function wjb_portal_verify_ajax() {
    check_ajax_referer( 'wjb_portal_nonce', 'nonce' );
    if ( ! wjb_portal_is_authenticated() ) {
        wp_send_json_error( [ 'message' => 'Session expired. Please sign in again.' ], 401 );
    }
}

// ── Add job ───────────────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_wjb_portal_add_job', 'wjb_portal_ajax_add_job' );
add_action( 'wp_ajax_wjb_portal_add_job',        'wjb_portal_ajax_add_job' );
function wjb_portal_ajax_add_job() {
    wjb_portal_verify_ajax();

    $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    if ( ! $title ) {
        wp_send_json_error( [ 'message' => 'Job title is required.' ] );
    }

    $post_id = wp_insert_post( [
        'post_type'    => 'job_listing',
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_content' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
    }

    wjb_portal_save_meta( $post_id );
    wp_send_json_success( [ 'id' => $post_id, 'message' => 'Job added.' ] );
}

// ── Update job ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_wjb_portal_update_job', 'wjb_portal_ajax_update_job' );
add_action( 'wp_ajax_wjb_portal_update_job',        'wjb_portal_ajax_update_job' );
function wjb_portal_ajax_update_job() {
    wjb_portal_verify_ajax();

    $post_id = intval( $_POST['id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'job_listing' ) {
        wp_send_json_error( [ 'message' => 'Invalid job.' ] );
    }

    $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    if ( ! $title ) {
        wp_send_json_error( [ 'message' => 'Job title is required.' ] );
    }

    wp_update_post( [
        'ID'           => $post_id,
        'post_title'   => $title,
        'post_content' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
    ] );

    wjb_portal_save_meta( $post_id );
    wp_send_json_success( [ 'id' => $post_id, 'message' => 'Job updated.', 'job' => wjb_portal_job_data( $post_id ) ] );
}

// ── Toggle active ─────────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_wjb_portal_toggle_job', 'wjb_portal_ajax_toggle_job' );
add_action( 'wp_ajax_wjb_portal_toggle_job',        'wjb_portal_ajax_toggle_job' );
function wjb_portal_ajax_toggle_job() {
    wjb_portal_verify_ajax();

    $post_id = intval( $_POST['id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'job_listing' ) {
        wp_send_json_error( [ 'message' => 'Invalid job.' ] );
    }

    $current = get_post_meta( $post_id, '_wjb_active', true );
    $new_val = ( $current === '1' ) ? '0' : '1';
    update_post_meta( $post_id, '_wjb_active', $new_val );

    wp_send_json_success( [ 'id' => $post_id, 'active' => $new_val ] );
}

// ── Delete job ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_wjb_portal_delete_job', 'wjb_portal_ajax_delete_job' );
add_action( 'wp_ajax_wjb_portal_delete_job',        'wjb_portal_ajax_delete_job' );
function wjb_portal_ajax_delete_job() {
    wjb_portal_verify_ajax();

    $post_id = intval( $_POST['id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'job_listing' ) {
        wp_send_json_error( [ 'message' => 'Invalid job.' ] );
    }

    wp_delete_post( $post_id, true );
    wp_send_json_success( [ 'id' => $post_id, 'message' => 'Job deleted.' ] );
}

// ── Helpers ───────────────────────────────────────────────────────────────
function wjb_portal_save_meta( $post_id ) {
    foreach ( [ 'sector', 'location', 'level', 'type' ] as $field ) {
        update_post_meta( $post_id, '_wjb_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
    }

    $apply = trim( wp_unslash( $_POST['apply'] ?? '' ) );
    if ( $apply === '' ) {
        update_post_meta( $post_id, '_wjb_apply', '' );
    } elseif ( strpos( $apply, 'mailto:' ) === 0 ) {
        $email = sanitize_email( substr( $apply, 7 ) );
        update_post_meta( $post_id, '_wjb_apply', $email ? 'mailto:' . $email : '' );
    } elseif ( strpos( $apply, '@' ) !== false && strpos( $apply, '/' ) === false ) {
        $email = sanitize_email( $apply );
        update_post_meta( $post_id, '_wjb_apply', $email ? 'mailto:' . $email : '' );
    } else {
        update_post_meta( $post_id, '_wjb_apply', esc_url_raw( $apply ) );
    }

    update_post_meta( $post_id, '_wjb_active', ( isset( $_POST['active'] ) && $_POST['active'] === '1' ) ? '1' : '0' );
}

function wjb_portal_job_data( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) return null;
    return [
        'id'          => $post_id,
        'title'       => $post->post_title,
        'description' => $post->post_content,
        'sector'      => get_post_meta( $post_id, '_wjb_sector',   true ),
        'location'    => get_post_meta( $post_id, '_wjb_location', true ),
        'level'       => get_post_meta( $post_id, '_wjb_level',    true ),
        'type'        => get_post_meta( $post_id, '_wjb_type',     true ),
        'apply'       => get_post_meta( $post_id, '_wjb_apply',    true ),
        'active'      => get_post_meta( $post_id, '_wjb_active',   true ),
    ];
}
