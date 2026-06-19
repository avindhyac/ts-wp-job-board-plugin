<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin settings page ────────────────────────────────────────────────────
function wjb_portal_settings_menu() {
    add_options_page(
        'Job Board Portal',
        'Job Board Portal',
        'manage_options',
        'wjb-portal-settings',
        'wjb_render_portal_settings'
    );
}
add_action( 'admin_menu', 'wjb_portal_settings_menu' );


function wjb_render_portal_settings() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $message = '';
    $error   = '';

    if ( isset( $_POST['wjb_portal_save'] ) && check_admin_referer( 'wjb_portal_settings_save' ) ) {
        $new_slug = sanitize_title( wp_unslash( $_POST['wjb_portal_slug'] ?? '' ) );
        if ( $new_slug ) {
            update_option( 'wjb_portal_slug', $new_slug );
            flush_rewrite_rules();
        }

        $new_pw  = isset( $_POST['wjb_portal_password'] ) ? $_POST['wjb_portal_password'] : '';
        $confirm = isset( $_POST['wjb_portal_confirm'] )  ? $_POST['wjb_portal_confirm']  : '';

        if ( $new_pw !== '' ) {
            if ( $new_pw !== $confirm ) {
                $error = 'Passwords do not match.';
            } elseif ( strlen( $new_pw ) < 8 ) {
                $error = 'Password must be at least 8 characters.';
            } else {
                update_option( 'wjb_portal_password_hash', wp_hash_password( $new_pw ) );
                if ( ! $message ) $message = 'Settings saved.';
            }
        } elseif ( ! $error ) {
            $message = 'Settings saved.';
        }
    }

    $slug       = get_option( 'wjb_portal_slug', 'job-board-admin' );
    $has_pw     = (bool) get_option( 'wjb_portal_password_hash' );
    $portal_url = home_url( '/' . $slug . '/' );
    ?>
    <div class="wrap">
        <h1>Job Board Portal Settings</h1>

        <?php if ( $message ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
        <?php endif; ?>
        <?php if ( $error ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'wjb_portal_settings_save' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="wjb_portal_slug">Portal URL slug</label></th>
                    <td>
                        <code><?php echo esc_url( home_url( '/' ) ); ?></code>
                        <input type="text" id="wjb_portal_slug" name="wjb_portal_slug"
                               value="<?php echo esc_attr( $slug ); ?>" class="regular-text" />
                        <p class="description">
                            Client link: <a href="<?php echo esc_url( $portal_url ); ?>" target="_blank"><?php echo esc_url( $portal_url ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjb_portal_password">Portal password</label></th>
                    <td>
                        <input type="password" id="wjb_portal_password" name="wjb_portal_password"
                               class="regular-text" autocomplete="new-password" />
                        <p class="description">
                            <?php echo $has_pw
                                ? 'A password is set. Enter a new one to change it, or leave blank to keep the current one.'
                                : '<strong style="color:#b32d2e">No password set.</strong> Set one before sharing the portal URL with the client.';
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjb_portal_confirm">Confirm password</label></th>
                    <td>
                        <input type="password" id="wjb_portal_confirm" name="wjb_portal_confirm"
                               class="regular-text" autocomplete="new-password" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="wjb_portal_save" value="1" class="button button-primary">Save Settings</button>
            </p>
        </form>
    </div>
    <?php
}
