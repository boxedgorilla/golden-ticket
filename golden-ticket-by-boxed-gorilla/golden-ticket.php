<?php
/*
Plugin Name: Golden Ticket
Description: Transform your WordPress site into your own secret chocolate factory. In Golden Ticket mode your site is private except for special pages. In Inventing Room mode your site is open except for your secret workshop pages.
Version: 2.2.0
Author: Boxed Gorilla LLC
Author URI: https://boxedgorilla.com
License: GPL2
Text Domain: golden-ticket
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GT_VERSION', '2.2.0' );


/* ═══════════════════════════════════════════════════════════════════════════
   ACTIVATION & i18n
   ═══════════════════════════════════════════════════════════════════════════ */

register_activation_hook( __FILE__, 'gt_activate' );
function gt_activate() {
    add_option( 'gt_show_onboarding', '1' );
}

add_action( 'plugins_loaded', 'gt_load_textdomain' );
function gt_load_textdomain() {
    load_plugin_textdomain( 'golden-ticket', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}


/* ═══════════════════════════════════════════════════════════════════════════
   HELPER: Fetch all manageable content items (cached per request)
   ═══════════════════════════════════════════════════════════════════════════ */

function gt_get_all_items() {
    static $items = null;
    if ( $items !== null ) {
        return $items;
    }

    $pages = get_pages( array(
        'post_status' => 'publish',
        'sort_column' => 'post_title',
        'sort_order'  => 'ASC',
    ) );

    $posts = get_posts( array(
        'post_type'   => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ) );

    $products = array();
    if ( post_type_exists( 'product' ) ) {
        $products = get_posts( array(
            'post_type'   => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ) );
    }

    $items = array(
        'pages'    => $pages,
        'posts'    => $posts,
        'products' => $products,
    );
    return $items;
}


/* ═══════════════════════════════════════════════════════════════════════════
   SETTINGS & OPTIONS
   ═══════════════════════════════════════════════════════════════════════════ */

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gt_add_action_links' );
function gt_add_action_links( $links ) {
    $settings_link = '<a href="options-general.php?page=gt-settings">' . __( 'Settings', 'golden-ticket' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

add_action( 'admin_menu', 'gt_add_settings_page' );
function gt_add_settings_page() {
    add_options_page(
        __( 'The Golden Ticket', 'golden-ticket' ),
        __( 'Golden Ticket', 'golden-ticket' ),
        'manage_options',
        'gt-settings',
        'gt_render_settings_page'
    );
}

add_action( 'admin_init', 'gt_register_settings' );
function gt_register_settings() {
    register_setting( 'gt_settings_group', 'gt_allowed_pages', 'gt_sanitize_page_list' );
    register_setting( 'gt_settings_group', 'gt_allowed_pages_action', 'sanitize_text_field' );
    register_setting( 'gt_settings_group', 'gt_access_mode', 'gt_sanitize_mode' );
    register_setting( 'gt_settings_group', 'gt_redirect_url', 'gt_sanitize_redirect_url' );
}

function gt_sanitize_page_list( $input ) {
    if ( empty( $input ) ) {
        return '';
    }
    $ids = is_array( $input ) ? array_map( 'intval', $input ) : array( intval( $input ) );
    $ids = array_filter( $ids, function( $v ) { return $v > 0; } );
    return implode( ',', $ids );
}

function gt_sanitize_mode( $input ) {
    return $input === 'inventing' ? 'inventing' : 'golden';
}

function gt_sanitize_redirect_url( $input ) {
    $url = esc_url_raw( trim( $input ) );
    return $url ? $url : '';
}

add_filter( 'pre_update_option_gt_allowed_pages', 'gt_handle_allowed_pages_update', 10, 2 );
function gt_handle_allowed_pages_update( $new_value, $old_value ) {
    $action = isset( $_POST['gt_allowed_pages_action'] )
        ? sanitize_text_field( $_POST['gt_allowed_pages_action'] )
        : 'add';

    $new_ids = array_filter( array_map( 'intval', explode( ',', $new_value ) ) );
    $old_ids = array_filter( array_map( 'intval', explode( ',', $old_value ) ) );

    if ( $action === 'add' ) {
        $merged = array_unique( array_merge( $old_ids, $new_ids ) );
    } else {
        $merged = array_diff( $old_ids, $new_ids );
    }

    // Bulk WooCommerce product actions
    if ( ! empty( $_POST['gt_add_all_products'] ) || ! empty( $_POST['gt_remove_all_products'] ) ) {
        $product_ids = get_posts( array(
            'post_type'   => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ) );
        if ( ! empty( $_POST['gt_add_all_products'] ) ) {
            $merged = array_unique( array_merge( $merged, $product_ids ) );
        }
        if ( ! empty( $_POST['gt_remove_all_products'] ) ) {
            $merged = array_diff( $merged, $product_ids );
        }
    }

    sort( $merged );

    // Check achievements after save
    gt_check_achievements( count( $merged ) );

    return implode( ',', $merged );
}

add_filter( 'pre_update_option_gt_access_mode', 'gt_handle_mode_change', 10, 2 );
function gt_handle_mode_change( $new_value, $old_value ) {
    $new = gt_sanitize_mode( $new_value );
    if ( $new !== $old_value ) {
        update_option( 'gt_allowed_pages', '' );
    }
    return $new;
}


/* ═══════════════════════════════════════════════════════════════════════════
   ADMIN ASSET ENQUEUE
   ═══════════════════════════════════════════════════════════════════════════ */

add_action( 'admin_enqueue_scripts', 'gt_enqueue_admin_assets' );
function gt_enqueue_admin_assets( $hook ) {
    if ( $hook !== 'settings_page_gt-settings' ) {
        return;
    }

    wp_enqueue_style( 'gt-admin-style', plugin_dir_url( __FILE__ ) . 'css/gt-admin.css', array(), GT_VERSION );
    wp_enqueue_script( 'gt-admin-script', plugin_dir_url( __FILE__ ) . 'js/gt-admin.js', array( 'jquery' ), GT_VERSION, true );

    $items     = gt_get_all_items();
    $raw       = get_option( 'gt_allowed_pages', '' );
    $saved_ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

    $all_items = array();
    foreach ( $items['pages'] as $p ) {
        $title = $p->post_title !== '' ? $p->post_title : __( '(Untitled)', 'golden-ticket' );
        $all_items[] = array( intval( $p->ID ), esc_js( $title ), 'page' );
    }
    foreach ( $items['posts'] as $p ) {
        $title = $p->post_title !== '' ? $p->post_title : __( '(Untitled)', 'golden-ticket' );
        $all_items[] = array( intval( $p->ID ), esc_js( $title ), 'post' );
    }
    foreach ( $items['products'] as $p ) {
        $title = $p->post_title !== '' ? $p->post_title : __( '(Untitled)', 'golden-ticket' );
        $all_items[] = array( intval( $p->ID ), esc_js( $title ), 'product' );
    }

    wp_localize_script( 'gt-admin-script', 'gtData', array(
        'allItems'      => $all_items,
        'savedIds'      => array_values( $saved_ids ),
        'currentMode'   => get_option( 'gt_access_mode', 'golden' ),
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'dismissNonce'  => wp_create_nonce( 'gt_dismiss_onboarding' ),
    ) );
}

/* AJAX handler for dismissing onboarding from the in-banner panel */
add_action( 'wp_ajax_gt_dismiss_onboarding', 'gt_ajax_dismiss_onboarding' );
function gt_ajax_dismiss_onboarding() {
    check_ajax_referer( 'gt_dismiss_onboarding', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error();
    }
    delete_option( 'gt_show_onboarding' );
    wp_send_json_success();
}


/* ═══════════════════════════════════════════════════════════════════════════
   SETTINGS PAGE
   ═══════════════════════════════════════════════════════════════════════════ */


function gt_render_settings_page() {
    $current_action = get_option( 'gt_allowed_pages_action', 'add' );
    $current_mode   = get_option( 'gt_access_mode', 'golden' );
    $redirect_url   = get_option( 'gt_redirect_url', '' );
    $items          = gt_get_all_items();
    $raw_allowed    = get_option( 'gt_allowed_pages', '' );
    $saved_ids      = array_filter( array_map( 'intval', explode( ',', $raw_allowed ) ) );
    $just_saved      = isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true';
    $mode_class      = $current_mode === 'inventing' ? 'inventing-mode' : 'golden-mode';
    $show_onboarding = get_option( 'gt_show_onboarding' ) === '1' && current_user_can( 'manage_options' );
    ?>
    <div class="wrap gt-wrap">

        <?php if ( $just_saved ): ?>
            <div id="success-message" class="golden-success">
                <?php esc_html_e( 'Settings saved!', 'golden-ticket' ); ?> &#x1f3ab;&#x2728;
            </div>
        <?php endif; ?>

        <form method="post" action="options.php" id="golden-ticket-form">
            <?php settings_fields( 'gt_settings_group' ); ?>

            <div id="gt-parent-container" class="<?php echo esc_attr( $mode_class ); ?>">

                <!-- Animated Header -->
                <div class="golden-ticket-header<?php echo $show_onboarding ? ' gt-banner-with-instructions' : ''; ?>" id="gt-banner">
                    <!-- Background stars -->
                    <div class="gt-header-backdrop">
                        <span class="gt-bg-star gt-bg-star-1">&#x2726;</span>
                        <span class="gt-bg-star gt-bg-star-2">&#x2727;</span>
                        <span class="gt-bg-star gt-bg-star-3">&#x2605;</span>
                        <span class="gt-bg-star gt-bg-star-4">&#x2726;</span>
                        <span class="gt-bg-star gt-bg-star-5">&#x2727;</span>
                        <span class="gt-bg-star gt-bg-star-6">&#x2605;</span>
                        <span class="gt-bg-star gt-bg-star-7">&#x2726;</span>
                    </div>

                    <!-- Factory cityscape — full-width silhouette along bottom -->
                    <div class="gt-factory-cityscape">
                        <div class="gt-smokestack gt-smokestack-1"><div class="gt-smoke"></div></div>
                        <div class="gt-smokestack gt-smokestack-2"><div class="gt-smoke"></div></div>
                        <div class="gt-smokestack gt-smokestack-3"><div class="gt-smoke"></div></div>
                        <div class="gt-smokestack gt-smokestack-4"><div class="gt-smoke"></div></div>
                    </div>

                    <!-- Floating chocolate bars (Wonka opening credits) -->
                    <div class="gt-choco-bar gt-choco-1"></div>
                    <div class="gt-choco-bar gt-choco-2"></div>
                    <div class="gt-choco-bar gt-choco-3"></div>

                    <!-- Scattered stars -->
                    <div class="gt-header-stars">
                        <span class="gt-star gt-star-1">&#x2726;</span>
                        <span class="gt-star gt-star-2">&#x2727;</span>
                        <span class="gt-star gt-star-3">&#x2726;</span>
                        <span class="gt-star gt-star-4">&#x2605;</span>
                        <span class="gt-star gt-star-5">&#x2727;</span>
                    </div>

                    <!-- Header content — flex row -->
                    <div class="gt-header-content">
                        <div class="gt-header-left">
                            <div class="gt-ticket-icon">
                                <div class="gt-ticket-shape">
                                    <span class="gt-ticket-star">&#x2605;</span>
                                    <span class="gt-ticket-text">GT</span>
                                    <span class="gt-ticket-star">&#x2605;</span>
                                </div>
                            </div>
                            <div class="gt-header-text">
                                <h1><?php esc_html_e( 'Golden Ticket', 'golden-ticket' ); ?></h1>
                                <p><?php esc_html_e( 'Where the magic happens', 'golden-ticket' ); ?></p>
                            </div>
                        </div>

                        <?php if ( $show_onboarding ) : ?>
                        <div class="gt-instructions-panel" id="gt-instructions">
                            <button type="button" class="gt-instructions-close" id="gt-instructions-close">&#x2715;</button>
                            <h3>&#x1f36b; <?php esc_html_e( 'Welcome to the Golden Ticket Factory!', 'golden-ticket' ); ?></h3>
                            <p class="gt-instructions-subtitle"><?php esc_html_e( 'Your site is now equipped with access control magic. Here\'s how to get started:', 'golden-ticket' ); ?></p>
                            <ol>
                                <li><strong><?php esc_html_e( 'Choose your mode:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Golden Ticket (site locked, grant access) or Inventing Room (site open, lock specific pages).', 'golden-ticket' ); ?></li>
                                <li><strong><?php esc_html_e( 'Select content:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Pick which pages, posts, or products to grant/revoke access.', 'golden-ticket' ); ?></li>
                                <li><strong><?php esc_html_e( 'Save & verify:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Test in an incognito window to confirm your settings work.', 'golden-ticket' ); ?></li>
                            </ol>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Toolbar: Mode + Action -->
                <div class="gt-toolbar">
                    <div class="gt-toolbar-group">
                        <span id="mode-golden" class="mode-option<?php echo $current_mode === 'golden' ? ' active' : ''; ?>">
                            <?php esc_html_e( 'Golden Ticket', 'golden-ticket' ); ?>
                        </span>
                        <label class="switch">
                            <input type="checkbox" id="gt-access-mode" name="gt_access_mode" value="inventing" <?php checked( $current_mode, 'inventing' ); ?> />
                            <span class="slider"></span>
                        </label>
                        <span id="mode-inventing" class="mode-option<?php echo $current_mode === 'inventing' ? ' active' : ''; ?>">
                            <?php esc_html_e( 'Inventing Room', 'golden-ticket' ); ?>
                        </span>
                    </div>
                    <div class="gt-toolbar-divider"></div>
                    <div class="gt-toolbar-group">
                        <label class="gt-radio-label">
                            <input type="radio" name="gt_allowed_pages_action" value="add" <?php checked( $current_action, 'add' ); ?> />
                            <span id="label-add-text"><?php echo $current_mode === 'inventing'
                                ? esc_html__( 'Protect Content', 'golden-ticket' )
                                : esc_html__( 'Grant Tickets', 'golden-ticket' ); ?></span>
                        </label>
                        <label class="gt-radio-label">
                            <input type="radio" name="gt_allowed_pages_action" value="remove" <?php checked( $current_action, 'remove' ); ?> />
                            <span id="label-remove-text"><?php echo $current_mode === 'inventing'
                                ? esc_html__( 'Open Content', 'golden-ticket' )
                                : esc_html__( 'Revoke Tickets', 'golden-ticket' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- How it works -->
                <p id="action-desc" class="gt-howto">
                    <?php if ( $current_mode === 'inventing' ) : ?>
                        <?php esc_html_e( 'Site is open.', 'golden-ticket' ); ?>
                        <?php echo $current_action === 'add'
                            ? esc_html__( 'Select items to protect (require login).', 'golden-ticket' )
                            : esc_html__( 'Select items to open (remove login).', 'golden-ticket' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Site requires login.', 'golden-ticket' ); ?>
                        <?php echo $current_action === 'add'
                            ? esc_html__( 'Select items to grant tickets (make public).', 'golden-ticket' )
                            : esc_html__( 'Select items to revoke tickets (require login).', 'golden-ticket' ); ?>
                    <?php endif; ?>
                </p>

                <!-- Two columns -->
                <div id="gt-flex-container">
                    <!-- Left: Content selector -->
                    <div id="gt-left-column">
                        <div class="gt-column-header">&#x1f4cb; <?php esc_html_e( 'Select Content', 'golden-ticket' ); ?></div>
                        <input type="text" id="gt-search" placeholder="<?php esc_attr_e( 'Search pages, posts, products...', 'golden-ticket' ); ?>" />
                        <?php
                        echo '<select id="gt-page-select" name="gt_allowed_pages[]" multiple size="12">';
                        if ( ! empty( $items['pages'] ) ) {
                            echo '<optgroup label="' . esc_attr__( 'Pages', 'golden-ticket' ) . '">';
                            foreach ( $items['pages'] as $page ) {
                                $sel = in_array( intval( $page->ID ), $saved_ids, true ) ? ' selected="selected"' : '';
                                $title = $page->post_title !== '' ? $page->post_title : __( '(Untitled)', 'golden-ticket' );
                                printf( '<option value="%d"%s>%s</option>', intval( $page->ID ), $sel, esc_html( $title ) );
                            }
                            echo '</optgroup>';
                        }
                        if ( ! empty( $items['posts'] ) ) {
                            echo '<optgroup label="' . esc_attr__( 'Posts', 'golden-ticket' ) . '">';
                            foreach ( $items['posts'] as $post_item ) {
                                $sel = in_array( intval( $post_item->ID ), $saved_ids, true ) ? ' selected="selected"' : '';
                                $title = $post_item->post_title !== '' ? $post_item->post_title : __( '(Untitled)', 'golden-ticket' );
                                printf( '<option value="%d"%s>%s</option>', intval( $post_item->ID ), $sel, esc_html( $title ) );
                            }
                            echo '</optgroup>';
                        }
                        if ( ! empty( $items['products'] ) ) {
                            echo '<optgroup label="' . esc_attr__( 'Products', 'golden-ticket' ) . '">';
                            foreach ( $items['products'] as $product ) {
                                $sel = in_array( intval( $product->ID ), $saved_ids, true ) ? ' selected="selected"' : '';
                                $title = $product->post_title !== '' ? $product->post_title : __( '(Untitled)', 'golden-ticket' );
                                printf( '<option value="%d"%s>%s</option>', intval( $product->ID ), $sel, esc_html( $title ) );
                            }
                            echo '</optgroup>';
                        }
                        echo '</select>';
                        ?>
                        <div class="gt-controls-row">
                            <label><input type="checkbox" id="gt-add-all" /> <span id="label-add-all"><?php echo $current_mode === 'inventing' ? esc_html__( 'Protect All', 'golden-ticket' ) : esc_html__( 'Grant All', 'golden-ticket' ); ?></span></label>
                            <label><input type="checkbox" id="gt-remove-all" /> <span id="label-remove-all"><?php echo $current_mode === 'inventing' ? esc_html__( 'Open All', 'golden-ticket' ) : esc_html__( 'Revoke All', 'golden-ticket' ); ?></span></label>
                            <?php if ( post_type_exists( 'product' ) ) : ?>
                            <label><input type="checkbox" id="gt-add-all-products" name="gt_add_all_products" /> <span id="label-add-all-products"><?php echo $current_mode === 'inventing' ? esc_html__( 'Protect Products', 'golden-ticket' ) : esc_html__( 'Grant Products', 'golden-ticket' ); ?></span></label>
                            <label><input type="checkbox" id="gt-remove-all-products" name="gt_remove_all_products" /> <span id="label-remove-all-products"><?php echo $current_mode === 'inventing' ? esc_html__( 'Open Products', 'golden-ticket' ) : esc_html__( 'Revoke Products', 'golden-ticket' ); ?></span></label>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="revoke-all-btn">
                            <?php echo $current_mode === 'inventing'
                                ? esc_html__( 'Remove All Protected', 'golden-ticket' )
                                : esc_html__( 'Revoke All Tickets', 'golden-ticket' ); ?>
                        </button>
                    </div>

                    <!-- Right: Preview -->
                    <div id="gt-right-column" class="<?php echo esc_attr( $mode_class ); ?>">
                        <div class="gt-column-header" id="preview-header">
                            <?php echo $current_mode === 'inventing'
                                ? '&#x1f512; ' . esc_html__( 'Protected Content', 'golden-ticket' )
                                : '&#x1f3ab; ' . esc_html__( 'Content with Golden Tickets', 'golden-ticket' ); ?>
                        </div>
                        <ul id="gt-current-list"></ul>
                        <div id="ticket-stats">
                            <span id="ticket-prefix"><?php echo $current_mode === 'inventing' ? '&#x1f512;' : '&#x1f3ab;'; ?></span>
                            <span id="ticket-count">0</span>
                            <span id="ticket-label"><?php echo $current_mode === 'inventing'
                                ? esc_html__( 'Protected Items', 'golden-ticket' )
                                : esc_html__( 'Golden Tickets Active', 'golden-ticket' ); ?></span>
                        </div>
                        <p id="preview-info">
                            <?php echo $current_mode === 'inventing'
                                ? esc_html__( 'Only these items require login. Everything else is open.', 'golden-ticket' )
                                : esc_html__( 'These items are public — all others require login.', 'golden-ticket' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Footer: Redirect + Save -->
                <div class="gt-footer">
                    <div class="gt-redirect-row">
                        <label class="gt-redirect-label">&#x1f517; <?php esc_html_e( 'Redirect', 'golden-ticket' ); ?>
                            <span class="tooltip">&#x2139;&#xfe0f;<span class="tooltiptext"><?php esc_html_e( 'Custom login URL. Leave empty for default WordPress login.', 'golden-ticket' ); ?></span></span>
                        </label>
                        <input type="url" name="gt_redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>"
                               placeholder="<?php esc_attr_e( 'https://example.com/my-login', 'golden-ticket' ); ?>"
                               class="gt-redirect-input" />
                    </div>
                    <?php submit_button(
                        __( 'Save Settings', 'golden-ticket' ),
                        'primary golden-save-btn', '', false
                    ); ?>
                </div>

            </div>
        </form>
    </div>
    <?php
}



/* ═══════════════════════════════════════════════════════════════════════════
   FRONTEND ACCESS CONTROL
   ═══════════════════════════════════════════════════════════════════════════ */

add_action( 'template_redirect', 'gt_force_login_check' );
function gt_force_login_check() {
    if ( is_user_logged_in() ) {
        return;
    }

    global $pagenow;
    if ( $pagenow === 'wp-login.php' ) {
        return;
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
        return;
    }

    // Works for pages, posts, products, and any custom post type
    $current_page_id = 0;
    if ( is_singular() ) {
        $current_page_id = get_the_ID();
    }

    $raw_allowed = get_option( 'gt_allowed_pages', '' );
    $allowed_ids = array_filter( array_map( 'intval', explode( ',', $raw_allowed ) ) );
    $mode        = get_option( 'gt_access_mode', 'golden' );

    $has_ticket = ( $current_page_id > 0 && in_array( $current_page_id, $allowed_ids, true ) );

    if ( $mode === 'inventing' ) {
        $needs_login = $has_ticket;
    } else {
        $needs_login = ! $has_ticket;
    }

    if ( ! $needs_login ) {
        return;
    }

    // Custom redirect URL or default WordPress login
    $custom_url = get_option( 'gt_redirect_url', '' );
    if ( $custom_url ) {
        $login_url = add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $custom_url );
    } else {
        $login_url = wp_login_url( home_url( $_SERVER['REQUEST_URI'] ) );
    }

    wp_safe_redirect( $login_url );
    exit;
}


/* ═══════════════════════════════════════════════════════════════════════════
   ADMIN BAR
   ═══════════════════════════════════════════════════════════════════════════ */

add_action( 'admin_bar_menu', 'gt_admin_bar_menu', 100 );
function gt_admin_bar_menu( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $mode  = get_option( 'gt_access_mode', 'golden' );
    $raw   = get_option( 'gt_allowed_pages', '' );
    $count = $raw ? count( array_filter( explode( ',', $raw ) ) ) : 0;

    if ( $mode === 'inventing' ) {
        $title = sprintf( '🔒 %d %s', $count, __( 'protected', 'golden-ticket' ) );
    } else {
        $title = sprintf( '🎫 %d %s', $count, __( 'tickets', 'golden-ticket' ) );
    }

    $wp_admin_bar->add_node( array(
        'id'    => 'golden-ticket',
        'title' => $title,
        'href'  => admin_url( 'options-general.php?page=gt-settings' ),
    ) );
}


/* ═══════════════════════════════════════════════════════════════════════════
   DASHBOARD WIDGET
   ═══════════════════════════════════════════════════════════════════════════ */

add_action( 'wp_dashboard_setup', 'gt_dashboard_widget_setup' );
function gt_dashboard_widget_setup() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    wp_add_dashboard_widget(
        'gt_dashboard_widget',
        '🎫 ' . __( 'Golden Ticket Factory Status', 'golden-ticket' ),
        'gt_dashboard_widget_render'
    );
}

function gt_dashboard_widget_render() {
    $mode  = get_option( 'gt_access_mode', 'golden' );
    $raw   = get_option( 'gt_allowed_pages', '' );
    $count = $raw ? count( array_filter( explode( ',', $raw ) ) ) : 0;

    echo '<div style="text-align:center; padding: 15px 0;">';

    if ( $mode === 'golden' ) {
        echo '<div style="font-size: 36px; margin-bottom: 10px;">🏭🔒</div>';
        echo '<strong style="font-size: 16px;">' . esc_html__( 'The Factory is LOCKED', 'golden-ticket' ) . '</strong><br>';
        printf(
            '<span style="color: #32CD32; font-size: 20px; font-weight: bold;">%d</span> %s<br>',
            $count,
            esc_html( _n( 'room is open to visitors', 'rooms are open to visitors', $count, 'golden-ticket' ) )
        );
        echo '<small style="color:#999;">' . esc_html__( 'All other rooms require a Golden Ticket (login) to enter.', 'golden-ticket' ) . '</small>';
    } else {
        echo '<div style="font-size: 36px; margin-bottom: 10px;">🏭🔓</div>';
        echo '<strong style="font-size: 16px;">' . esc_html__( 'The Factory is OPEN', 'golden-ticket' ) . '</strong><br>';
        printf(
            '<span style="color: #FF4500; font-size: 20px; font-weight: bold;">%d</span> %s<br>',
            $count,
            esc_html( _n( 'secret room is behind locked doors', 'secret rooms are behind locked doors', $count, 'golden-ticket' ) )
        );
        echo '<small style="color:#999;">' . esc_html__( 'All other rooms are open to everyone.', 'golden-ticket' ) . '</small>';
    }

    echo '<div style="margin-top: 12px;">';
    echo '<a href="' . esc_url( admin_url( 'options-general.php?page=gt-settings' ) ) . '" class="button button-primary" style="background: #6A5ACD; border-color: #6A5ACD;">';
    echo '🎫 ' . esc_html__( 'Manage Tickets', 'golden-ticket' );
    echo '</a></div>';
    echo '</div>';
}


/* ═══════════════════════════════════════════════════════════════════════════
   LIST TABLE COLUMNS (Pages, Posts, Products)
   ═══════════════════════════════════════════════════════════════════════════ */

add_filter( 'manage_pages_columns', 'gt_add_ticket_column' );
add_action( 'manage_pages_custom_column', 'gt_render_ticket_column', 10, 2 );
add_filter( 'manage_posts_columns', 'gt_add_ticket_column' );
add_action( 'manage_posts_custom_column', 'gt_render_ticket_column', 10, 2 );

function gt_add_ticket_column( $columns ) {
    $columns['golden_ticket'] = '🎫';
    return $columns;
}

function gt_render_ticket_column( $column_name, $post_id ) {
    if ( $column_name !== 'golden_ticket' ) {
        return;
    }

    static $allowed_ids = null;
    static $mode = null;

    if ( $allowed_ids === null ) {
        $raw         = get_option( 'gt_allowed_pages', '' );
        $allowed_ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        $mode        = get_option( 'gt_access_mode', 'golden' );
    }

    $in_list = in_array( $post_id, $allowed_ids, true );

    if ( $mode === 'golden' ) {
        echo $in_list ? '<span title="' . esc_attr__( 'Has Golden Ticket (public)', 'golden-ticket' ) . '">🎫</span>' : '<span style="color:#ccc;">—</span>';
    } else {
        echo $in_list ? '<span title="' . esc_attr__( 'Protected (login required)', 'golden-ticket' ) . '">🔒</span>' : '<span style="color:#ccc;">—</span>';
    }
}


/* ═══════════════════════════════════════════════════════════════════════════
   ONBOARDING NOTICE
   ═══════════════════════════════════════════════════════════════════════════ */

add_action( 'admin_notices', 'gt_onboarding_notice' );
function gt_onboarding_notice() {
    if ( get_option( 'gt_show_onboarding' ) !== '1' ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    /* On the settings page the instructions appear inside the banner instead */
    global $pagenow;
    if ( $pagenow === 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] === 'gt-settings' ) {
        return;
    }

    $dismiss_url = wp_nonce_url( add_query_arg( 'gt_dismiss_onboarding', '1' ), 'gt_dismiss_onboarding' );
    ?>
    <div class="notice notice-info is-dismissible gt-onboarding-notice" style="
        border-left-color: #6A5ACD; padding: 15px 20px;
        background: linear-gradient(135deg, #f8f4ff, #fff);
    ">
        <h3 style="margin-top:0; color:#6A5ACD;">🍫 <?php esc_html_e( 'Welcome to the Golden Ticket Factory!', 'golden-ticket' ); ?></h3>
        <p>
            <?php esc_html_e( 'Your site is now equipped with access control magic. Here\'s how to get started:', 'golden-ticket' ); ?>
        </p>
        <ol style="margin-bottom: 10px;">
            <li><strong><?php esc_html_e( 'Choose your mode:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Golden Ticket (site locked, grant access) or Inventing Room (site open, lock specific pages).', 'golden-ticket' ); ?></li>
            <li><strong><?php esc_html_e( 'Select content:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Pick which pages, posts, or products to grant/revoke access.', 'golden-ticket' ); ?></li>
            <li><strong><?php esc_html_e( 'Save & verify:', 'golden-ticket' ); ?></strong> <?php esc_html_e( 'Test in an incognito window to confirm your settings work.', 'golden-ticket' ); ?></li>
        </ol>
        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gt-settings' ) ); ?>" class="button button-primary" style="background:#6A5ACD; border-color:#6A5ACD;">
            🎫 <?php esc_html_e( 'Go to Settings', 'golden-ticket' ); ?>
        </a>
        <a href="<?php echo esc_url( $dismiss_url ); ?>" class="button" style="margin-left: 8px;">
            <?php esc_html_e( 'Dismiss', 'golden-ticket' ); ?>
        </a>
    </div>
    <?php
}

add_action( 'admin_init', 'gt_handle_dismiss_onboarding' );
function gt_handle_dismiss_onboarding() {
    if ( isset( $_GET['gt_dismiss_onboarding'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'gt_dismiss_onboarding' ) ) {
        delete_option( 'gt_show_onboarding' );
        wp_safe_redirect( remove_query_arg( array( 'gt_dismiss_onboarding', '_wpnonce' ) ) );
        exit;
    }
}


/* ═══════════════════════════════════════════════════════════════════════════
   ACHIEVEMENTS
   ═══════════════════════════════════════════════════════════════════════════ */

function gt_check_achievements( $count ) {
    $seen = get_option( 'gt_achievements_seen', array() );
    if ( ! is_array( $seen ) ) {
        $seen = array();
    }

    $milestones = array(
        'first_ticket' => array( 1, '🎫', __( 'First Golden Ticket granted! Your factory is open for business.', 'golden-ticket' ) ),
        'ten_tickets'  => array( 10, '🏭', __( '10 items managed! Your chocolate factory is expanding.', 'golden-ticket' ) ),
        'fifty_tickets' => array( 50, '🌟', __( '50 items managed! You\'re a Master Chocolatier.', 'golden-ticket' ) ),
    );

    $new_achievements = array();
    foreach ( $milestones as $key => $data ) {
        if ( $count >= $data[0] && ! in_array( $key, $seen, true ) ) {
            $new_achievements[] = $data;
            $seen[]             = $key;
        }
    }

    if ( ! empty( $new_achievements ) ) {
        update_option( 'gt_achievements_seen', $seen );
        set_transient( 'gt_new_achievements', $new_achievements, 60 );
    }
}

add_action( 'admin_notices', 'gt_achievement_notices' );
function gt_achievement_notices() {
    $achievements = get_transient( 'gt_new_achievements' );
    if ( empty( $achievements ) ) {
        return;
    }
    delete_transient( 'gt_new_achievements' );

    foreach ( $achievements as $data ) {
        printf(
            '<div class="notice notice-success is-dismissible" style="border-left-color: #FFD700; background: linear-gradient(135deg, #fffdf0, #fff);">
                <p><strong>%s %s</strong></p>
            </div>',
            esc_html( $data[1] ),
            esc_html( $data[2] )
        );
    }
}
