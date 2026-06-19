<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Rewrite rules ──────────────────────────────────────────────────────────
function wjb_portal_register_rewrite() {
    $slug = get_option( 'wjb_portal_slug', 'job-board-admin' );
    $s    = preg_quote( $slug, '/' );
    add_rewrite_rule( '^' . $s . '/?$',          'index.php?wjb_portal=dashboard', 'top' );
    add_rewrite_rule( '^' . $s . '/logout/?$',   'index.php?wjb_portal=logout',    'top' );
}
add_action( 'init', 'wjb_portal_register_rewrite' );

function wjb_portal_query_vars( $vars ) {
    $vars[] = 'wjb_portal';
    return $vars;
}
add_filter( 'query_vars', 'wjb_portal_query_vars' );

function wjb_portal_template_redirect() {
    $portal = get_query_var( 'wjb_portal' );
    if ( ! $portal ) return;

    if ( $portal === 'logout' ) {
        wjb_portal_clear_cookie();
        $slug = get_option( 'wjb_portal_slug', 'job-board-admin' );
        wp_safe_redirect( home_url( '/' . $slug . '/' ) );
        exit;
    }

    if ( $portal === 'dashboard' ) {
        wjb_portal_render_page();
        exit;
    }
}
add_action( 'template_redirect', 'wjb_portal_template_redirect' );


// ── Auth helpers ───────────────────────────────────────────────────────────

function wjb_portal_is_authenticated() {
    if ( empty( $_COOKIE['wjb_portal_session'] ) ) return false;

    $cookie = sanitize_text_field( wp_unslash( $_COOKIE['wjb_portal_session'] ) );
    $parts  = explode( ':', $cookie, 2 );
    if ( count( $parts ) !== 2 ) return false;

    [ $hash, $raw_time ] = $parts;
    $time = intval( $raw_time );

    if ( $time <= 0 || ( time() - $time ) > 43200 ) return false; // 12-hour expiry

    $stored = get_option( 'wjb_portal_password_hash' );
    if ( ! $stored ) return false;

    $expected = hash_hmac( 'sha256', 'wjb_portal_' . $time, $stored . AUTH_KEY );

    return hash_equals( $expected, $hash );
}

function wjb_portal_attempt_login( $password ) {
    $stored = get_option( 'wjb_portal_password_hash' );
    if ( ! $stored || ! wp_check_password( $password, $stored ) ) return false;

    $time  = time();
    $hash  = hash_hmac( 'sha256', 'wjb_portal_' . $time, $stored . AUTH_KEY );
    $value = $hash . ':' . $time;

    setcookie( 'wjb_portal_session', $value, [
        'expires'  => $time + 43200,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Strict',
    ] );

    return true;
}

function wjb_portal_clear_cookie() {
    setcookie( 'wjb_portal_session', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Strict',
    ] );
}
