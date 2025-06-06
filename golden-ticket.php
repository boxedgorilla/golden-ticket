<?php
/*
Plugin Name: Golden Ticket
Description: Force login on your entire website, except for pages you grant "Golden Tickets" - allowing public access to those specific pages only.
Version: 1.0.0
Author: Boxed Gorilla LLC
Author URI: https://boxedgorilla.com
License: GPL2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Add "Settings" link under the plugin name on the Plugins page
 */
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    'fle_add_action_links'
);
function fle_add_action_links( $links ) {
    $settings_link = '<a href="options-general.php?page=fle-settings">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}


/**
 * Add our menu item under Settings
 */
add_action( 'admin_menu', 'fle_add_settings_page' );
function fle_add_settings_page() {
    add_options_page(
        'The Golden Ticket',        // Page title
        'Golden Ticket',            // Menu title
        'manage_options',           // Capability
        'fle-settings',             // Menu slug
        'fle_render_settings_page'  // Callback to output the page
    );
}


/**
 * Register the fle_allowed_pages setting so WP knows how to sanitize & store it.
 */
add_action( 'admin_init', 'fle_register_settings' );
function fle_register_settings() {
    // Register “fle_allowed_pages” as a single, comma‐separated string.
    register_setting(
        'fle_settings_group',      // settings group name (must match settings_fields() below)
        'fle_allowed_pages',       // the actual option name in the database
        'fle_sanitize_page_list'   // callback to clean the incoming array
    );

    // Also register “fle_allowed_pages_action” so we can store “add” vs “remove”
    register_setting(
        'fle_settings_group',
        'fle_allowed_pages_action',
        'sanitize_text_field'
    );
}


/**
 * Simplified sanitize callback - just clean the input
 */
function fle_sanitize_page_list( $input ) {
    if ( empty( $input ) ) {
        return '';
    }
    $submitted_ids = is_array( $input ) ? array_map( 'intval', $input ) : array( intval( $input ) );
    $submitted_ids = array_filter( $submitted_ids, function( $v ) {
        return $v > 0;
    } );
    return implode( ',', $submitted_ids );
}

/**
 * Merge add/remove actions with the existing allowed pages before saving.
 */
add_filter( 'pre_update_option_fle_allowed_pages', 'fle_handle_allowed_pages_update', 10, 2 );
function fle_handle_allowed_pages_update( $new_value, $old_value ) {
    // Determine which action was submitted (defaults to "add").
    $action = isset( $_POST['fle_allowed_pages_action'] )
        ? sanitize_text_field( $_POST['fle_allowed_pages_action'] )
        : 'add';

    $new_ids = array_filter( array_map( 'intval', explode( ',', $new_value ) ) );
    $old_ids = array_filter( array_map( 'intval', explode( ',', $old_value ) ) );

    if ( $action === 'add' ) {
        // Combine and de-duplicate IDs when granting tickets.
        $merged = array_unique( array_merge( $old_ids, $new_ids ) );
    } else {
        // Remove selected IDs when revoking tickets.
        $merged = array_diff( $old_ids, $new_ids );
    }

    sort( $merged );
    return implode( ',', $merged );
}


/**
 * Render the Settings page with Golden Ticket theme and animations
 */
function fle_render_settings_page() {
    // Fetch all pages and current whitelist for JS
    $all_pages      = get_pages( array(
        'post_status' => 'publish',
        'sort_column' => 'post_title',
        'sort_order'  => 'ASC',
    ) );
    $raw_allowed    = get_option( 'fle_allowed_pages', '' );
    $saved_ids      = array_filter( array_map( 'intval', explode( ',', $raw_allowed ) ) );
    $js_pages       = array();

    foreach ( $all_pages as $p ) {
        $js_pages[] = array( intval( $p->ID ), esc_js( $p->post_title ) );
    }

    $js_pages_json  = wp_json_encode( $js_pages );
    $saved_ids_json = wp_json_encode( $saved_ids );
    // Default to “add” if nothing’s in the database yet
    $current_action = get_option( 'fle_allowed_pages_action', 'add' );
    $plugin_url     = plugin_dir_url( __FILE__ );

    // Check if we just saved (WP will add ?settings-updated=true after a successful save)
    $just_saved = isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true';
    ?>
    <div class="wrap" style="padding-top:10px;">

        <?php if ( $just_saved ): ?>
            <!-- Success Message with Animation -->
            <div id="success-message" class="golden-success" style="
                background: linear-gradient(45deg, #32CD32, #7CFC00, #32CD32);
                color: #006400;
                padding: 15px 25px;
                border-radius: 25px;
                font-weight: bold;
                text-align: center;
                margin: 20px auto;
                max-width: 600px;
                box-shadow: 0 8px 25px rgba(50, 205, 50, 0.4);
                border: 3px solid #FFD700;
                animation: successPulse 2s ease-in-out;
                position: relative;
                overflow: hidden;
            ">
                <div class="success-shimmer" style="
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                    animation: successShimmer 1.5s ease-in-out;
                "></div>
                🎉 Golden Tickets Updated Successfully! 🎫✨
                <div style="font-size: 14px; margin-top: 5px; font-style: italic;">
                    The site's guest access has been modified!
                </div>
            </div>
        <?php endif; ?>

        <!-- Golden Ticket Header with Logo -->
        <div style="text-align: center; margin-bottom: 20px;">
            <div class="golden-ticket-header" style="
                background: linear-gradient(45deg, #6A5ACD, #9370DB, #6A5ACD);
                color: #FFD700;
                padding: 15px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(106, 90, 205, 0.4);
                display: inline-flex;
                align-items: center;
                gap: 15px;
                font-family: 'Georgia', serif;
                position: relative;
                overflow: hidden;
                cursor: pointer;
            " onclick="createHeaderSparkles()">
                <div class="ticket-shimmer" style="
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
                    animation: shimmer 3s ease-in-out 1;
                "></div>
                <img id="gt-banner"
                     src="<?php echo esc_url( $plugin_url . 'gt-icon.jpg' ); ?>"
                     alt="Golden Ticket"
                     style="
                        width: auto;
                        height: 150px;
                        border: 3px solid #FFD700;
                        position: relative;
                        z-index: 2;
                        animation: logoGlimmer 2s ease-in-out 1;
                        transition: transform 0.3s ease;
                     "
                     onmouseover="this.style.transform='scale(1.05) rotate(2deg)'"
                     onmouseout="this.style.transform='scale(1) rotate(0deg)'" />
                <div style="position: relative; z-index: 1;">
                    <h1 style="margin: 0; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                        🍫 Golden Ticket Settings 🎫
                    </h1>
                    <p style="margin: 5px 0 0 0; font-style: italic;">
                        Control access to your website like running your own secret chocolate factory!
                    </p>
                </div>
            </div>
        </div>


        <form method="post" action="options.php" id="golden-ticket-form">
            <?php
                // This prints out:
                // 1) A hidden input named “option_page” with value “fle_settings_group”
                // 2) A hidden input named “_wpnonce” with the proper nonce tied to “fle_settings_group-options”
                // 3) A hidden input named “_wp_http_referer”
                settings_fields( 'fle_settings_group' );
            ?>

            <!-- Enhanced CSS with Golden Ticket Animations -->
            <style>
                /* Shimmer animation for header */
                @keyframes shimmer {
                    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
                    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
                }

                /* Success message animations */
                @keyframes successPulse {
                    0% { transform: scale(0.8); opacity: 0; }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); opacity: 1; }
                }

                @keyframes successShimmer {
                    0% { left: -100%; }
                    100% { left: 100%; }
                }

                /* Logo glimmer animation – once on load */
                @keyframes logoGlimmer {
                    0% {
                        box-shadow: 0 0 5px rgba(255, 215, 0, 0.5), 0 0 10px rgba(255, 215, 0, 0.3);
                        transform: scale(1);
                    }
                    50% {
                        box-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 30px rgba(255, 215, 0, 0.6), 0 0 40px rgba(255, 215, 0, 0.4);
                        transform: scale(1.05);
                    }
                    100% {
                        box-shadow: 0 0 5px rgba(255, 215, 0, 0.5), 0 0 10px rgba(255, 215, 0, 0.3);
                        transform: scale(1);
                    }
                }

                /* Oompa Loompa Animation */
                .oompa-loompa {
                    position: absolute;
                    width: 20px;
                    height: 20px;
                    background: linear-gradient(45deg, #FF8C00, #FF4500);
                    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
                    z-index: 1000;
                    pointer-events: none;
                }
                .oompa-loompa::before {
                    content: attr(data-character);
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .ticket-being-revoked {
                    position: relative;
                    animation: ticketShake 0.5s ease-in-out;
                }
                @keyframes ticketShake {
                    0%, 100% { transform: translateX(0); }
                    25%       { transform: translateX(-3px) rotate(-1deg); }
                    75%       { transform: translateX(3px) rotate(1deg); }
                }
                @keyframes oompaWalk {
                    0%   { left: -30px; transform: scale(0.8); }
                    30%  { left: 10px;  transform: scale(1); }
                    70%  { left: calc(100% - 30px); transform: scale(1); }
                    100% { left: calc(100% + 30px); transform: scale(0.8); }
                }
                @keyframes oompaCharacterBounce {
                    0%   { transform: translateY(0) scale(1); }
                    100% { transform: translateY(-3px) scale(1.1); }
                }
                .oompa-message {
                    position: absolute;
                    top: -30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #FFD700;
                    color: #8B4513;
                    padding: 4px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: bold;
                    white-space: nowrap;
                    animation: messageFloat 2s ease-in-out;
                    z-index: 1001;
                }
                @keyframes messageFloat {
                    0%   { opacity: 0; transform: translateX(-50%) translateY(10px); }
                    20%  { opacity: 1; transform: translateX(-50%) translateY(0px); }
                    80%  { opacity: 1; transform: translateX(-50%) translateY(0px); }
                    100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
                }

                /* Golden Ticket Save Button Animation */
                .golden-save-btn {
                    background: linear-gradient(45deg, #228B22, #32CD32, #228B22) !important;
                    border: 2px solid #FFD700 !important;
                    color: #FFD700 !important;
                    font-weight: bold !important;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.5) !important;
                    transition: all 0.3s ease !important;
                    position: relative !important;
                    overflow: hidden !important;
                    box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3) !important;
                }
                .golden-save-btn:hover {
                    background: linear-gradient(45deg, #32CD32, #7CFC00, #32CD32) !important;
                    box-shadow: 0 6px 20px rgba(50, 205, 50, 0.5) !important;
                    transform: translateY(-3px) scale(1.02) !important;
                }
                @keyframes ticketTransform {
                    0%   { transform: scale(1) rotate(0deg); }
                    25%  { transform: scale(1.05) rotate(-2deg); }
                    50%  { transform: scale(1.1) rotate(2deg); background: linear-gradient(45deg, #FFD700, #FFA500); }
                    75%  { transform: scale(1.05) rotate(-1deg); }
                    100% { transform: scale(1) rotate(0deg); }
                }
                .golden-save-btn.saving {
                    animation: ticketTransform 0.8s ease-in-out !important;
                    background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700) !important;
                    color: #8B4513 !important;
                }

                /* Enhanced sparkle animations */
                .sparkle {
                    width: 8px;
                    height: 8px;
                    background: radial-gradient(circle, #FFD700 20%, #FFA500 70%, transparent);
                    border-radius: 50%;
                    position: fixed;
                    pointer-events: none;
                    z-index: 9999;
                    animation: sparkleFloat 2s ease-out forwards;
                }
                @keyframes sparkleFloat {
                    0%   { transform: scale(0) rotate(0deg); opacity: 1; box-shadow: 0 0 6px #FFD700; }
                    50%  { transform: scale(1.5) rotate(180deg); opacity: 1; box-shadow: 0 0 12px #FFD700, 0 0 18px #FFA500; }
                    100% { transform: scale(0.5) rotate(360deg); opacity: 0; box-shadow: 0 0 3px #FFD700; }
                }

                /* Enhanced confetti with physics */
                .confetti {
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    z-index: 9998;
                    pointer-events: none;
                }
                .confetti.gold   { background: linear-gradient(45deg, #FFD700, #FFA500); box-shadow:0 0 6px rgba(255,215,0,0.8); }
                .confetti.orange { background: linear-gradient(45deg, #FF8C00, #FF4500); box-shadow:0 0 6px rgba(255,140,0,0.8); }
                .confetti.green  { background: linear-gradient(45deg, #32CD32, #7CFC00); box-shadow:0 0 6px rgba(50,205,50,0.8); }
                .confetti.purple { background: linear-gradient(45deg, #9370DB, #BA55D3); box-shadow:0 0 6px rgba(147,112,219,0.8); }
                @keyframes confettiFall {
                    0%   { transform: translateY(0) rotateZ(0deg) rotateY(0deg) scale(1); opacity: 1; }
                    100% { transform: translateY(100vh) rotateZ(720deg) rotateY(180deg) scale(0.8); opacity: 0; }
                }

                /* Page option hover effects */
                #fle_page_select option {
                    padding: 5px !important;
                    transition: all 0.2s ease !important;
                }
                #fle_page_select option:hover {
                    background-color: rgba(147, 112, 219, 0.1) !important;
                }

                /* Update Preview button hover effect */
                #update-preview-btn:hover {
                    background: linear-gradient(45deg, #32CD32, #7CFC00) !important;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(50, 205, 50, 0.4);
                }

                /* Enhanced Revoke All button */
                #revoke-all-btn {
                    background: linear-gradient(45deg, #6A5ACD, #9370DB) !important;
                    color: #FFD700 !important;
                    border: 2px solid #9370DB !important;
                    padding: 10px 18px;
                    border-radius: 8px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 3px 10px rgba(106, 90, 205, 0.3);
                    position: relative;
                    overflow: hidden;
                }
                #revoke-all-btn:hover {
                    background: linear-gradient(45deg, #9370DB, #BA55D3) !important;
                    transform: translateY(-2px) scale(1.02);
                    box-shadow: 0 6px 15px rgba(106, 90, 205, 0.5);
                }
                #revoke-all-btn:active {
                    transform: translateY(0px) scale(0.98);
                }

                /* Center the entire flex container and constrain its max-width */
                #fle-parent-container {
                    display: flex;
                    justify-content: center;
                    padding-bottom: 20px;
                }
                /* Inner flex: two columns with 20px gap and wrapping */
                #fle-flex-container {
                    display: flex;
                    gap: 20px;
                    flex-wrap: wrap;
                    max-width: 800px;
                    width: 100%;
                }
                /* Left column: take available space */
                #fle-left-column {
                    flex: 1 1 300px;
                    min-width: 300px;
                }
                /* Right column: fixed width */
                #fle-right-column {
                    flex: 0 0 260px;
                }
                /* On very narrow screens (<600px), stack columns */
                @media screen and (max-width: 600px) {
                    #fle-left-column,
                    #fle-right-column {
                        flex: 1 1 100%;
                    }
                }

                /* Enhanced preview box styling with purple and green */
                #fle-right-column {
                    background: linear-gradient(135deg, #f0e6ff, #e6f7e6) !important;
                    border: 2px solid #9370DB !important;
                    border-radius: 10px !important;
                    box-shadow: 0 4px 10px rgba(147, 112, 219, 0.2) !important;
                    position: relative;
                    overflow: hidden;
                }
                #fle-right-column::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #FFD700, #32CD32, #9370DB, #FFD700);
                    animation: rainbow-border 3s linear infinite;
                }
                @keyframes rainbow-border {
                    0%   { background-position: 0% 50%; }
                    100% { background-position: 100% 50%; }
                }

                /* Action section styling */
                .action-section {
                    background: linear-gradient(135deg, #f0fff0, #f0e6ff) !important;
                    border-left: 4px solid #32CD32 !important;
                    transition: all 0.3s ease;
                }
                .action-section:hover {
                    box-shadow: 0 4px 12px rgba(50, 205, 50, 0.2);
                    transform: translateY(-1px);
                }

                /* Page select section styling */
                .page-select-section {
                    background: linear-gradient(135deg, #f0e6ff, #f0fff0) !important;
                    border-left: 4px solid #9370DB !important;
                    transition: all 0.3s ease;
                }
                .page-select-section:hover {
                    box-shadow: 0 4px 12px rgba(147, 112, 219, 0.2);
                    transform: translateY(-1px);
                }

                /* Tooltip styling */
                .tooltip {
                    position: relative;
                    display: inline-block;
                    cursor: help;
                }
                .tooltip .tooltiptext {
                    visibility: hidden;
                    width: 200px;
                    background: linear-gradient(45deg, #6A5ACD, #9370DB);
                    color: #FFD700;
                    text-align: center;
                    border-radius: 8px;
                    padding: 8px 12px;
                    position: absolute;
                    z-index: 1000;
                    bottom: 125%;
                    left: 50%;
                    margin-left: -100px;
                    opacity: 0;
                    transition: opacity 0.3s;
                    font-size: 12px;
                    box-shadow: 0 4px 15px rgba(106, 90, 205, 0.4);
                }
                .tooltip:hover .tooltiptext {
                    visibility: visible;
                    opacity: 1;
                }

                /* Loading state for save button */
                .golden-save-btn.loading {
                    background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700) !important;
                    background-size: 200% 200% !important;
                    animation: loadingShimmer 1.5s ease-in-out infinite !important;
                }
                @keyframes loadingShimmer {
                    0%   { background-position: 0% 50%; }
                    50%  { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
            </style>

            <!-- Parent wrapper to center everything -->
            <div id="fle-parent-container">
                <div id="fle-flex-container">
                    <!-- Left Column: Action + Multi-select -->
                    <div id="fle-left-column">
                        <!-- Action Radios with chocolate theme -->
                        <div class="action-section" style="padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="margin-top: 0; color: #228B22;">
                                🍫 Choose Your Action 
                                <span class="tooltip">ℹ️
                                    <span class="tooltiptext">
                                        Grant tickets to allow public access, or revoke to require login
                                    </span>
                                </span>
                            </h3>
                            <label style="margin-right:16px;">
                                <input type="radio"
                                       name="fle_allowed_pages_action"
                                       value="add"
                                       <?php checked( $current_action, 'add' ); ?> />
                                Grant Golden Tickets
                            </label>
                            <label>
                                <input type="radio"
                                       name="fle_allowed_pages_action"
                                       value="remove"
                                       <?php checked( $current_action, 'remove' ); ?> />
                                Revoke Golden Tickets
                            </label>
                            <p class="description" style="margin-top:8px; margin-bottom:0;">
                                <strong>How it works:</strong> Your entire website requires login, except pages with Golden Tickets can be viewed by anyone without logging in. 
                                Select pages below to <?php echo $current_action === 'add'
                                    ? '<strong>grant them Golden Tickets</strong> (skip login requirement)'
                                    : '<strong>revoke their Golden Tickets</strong> (require login)'; ?>.
                            </p>
                        </div>

                        <!-- Multi-select of Pages -->
                        <div class="page-select-section" style="padding: 15px; border-radius: 8px;">
                            <h3 style="margin-top: 0; color: #6A5ACD;">
                                📋 Select Pages 
                                <span class="tooltip">🎯
                                    <span class="tooltiptext">
                                        Hold Ctrl/Cmd to select multiple pages. Click to preview changes!
                                    </span>
                                </span>
                            </h3>
                            <?php
                                // Build the <select> and mark saved IDs as “selected”
                                echo '<select id="fle_page_select" name="fle_allowed_pages[]" multiple size="10" style="width:100%; border: 2px solid #9370DB; border-radius: 5px;">';
                                foreach ( $all_pages as $page ) {
                                    $is_selected = in_array( intval( $page->ID ), $saved_ids, true )
                                        ? ' selected="selected"'
                                        : '';
                                    printf(
                                        '<option value="%1$d"%3$s>%2$s</option>',
                                        esc_attr( $page->ID ),
                                        esc_html( $page->post_title ),
                                        $is_selected
                                    );
                                }
                                echo '</select>';
                            ?>
                            <p class="description" style="margin-top:6px; margin-bottom:10px;">
                                Hold Ctrl (Windows) or Cmd (Mac) to select multiple pages. Pages with Golden Tickets can be viewed by anyone without logging in – like having VIP access to your website! 🎫
                            </p>

                            <!-- Enhanced "Revoke All" BUTTON -->
                            <button type="button" id="revoke-all-btn">
                                🚫 Revoke All Golden Tickets 🚫 
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Preview Box -->
                    <div id="fle-right-column" style="padding:15px;">
                        <h2 style="margin-top:0; margin-bottom:12px; font-size:18px; color: #6A5ACD;">
                            🎫 Pages with Golden Tickets
                        </h2>
                        <ul id="fle-current-list" style="margin:0; padding-left:16px; list-style:none; min-height:100px;">
                            <!-- JS will fill this in -->
                        </ul>
                        <p style="font-size: 12px; color: #228B22; font-style: italic; margin-top: 15px; margin-bottom: 0;">
                            These pages can be viewed by anyone without logging in – all other pages require login first.
                        </p>

                        <!-- Stats Counter -->
                        <div id="ticket-stats" style="
                            margin-top: 15px;
                            padding: 10px;
                            background: rgba(255, 215, 0, 0.1);
                            border-radius: 5px;
                            border: 1px solid #FFD700;
                            text-align: center;
                            font-size: 14px;
                            color: #6A5ACD;
                            font-weight: bold;
                        ">
                            🎫 <span id="ticket-count">0</span> Golden Tickets Active
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <?php
                    submit_button(
                        '🎫 Save Golden Ticket Settings 🍫',
                        'primary golden-save-btn',
                        '',
                        false,
                        array( 'style' => 'font-size: 16px; padding: 12px 35px;' )
                    );
                ?>
            </div>
        </form>
    </div>

   <script type="text/javascript">
jQuery(document).ready(function($){
    // ──────────────────────────────────────────────────────────────
    // 1) VARIABLES
    // ──────────────────────────────────────────────────────────────
    var $banner = $('#gt-banner');
    var allPages    = <?php echo $js_pages_json;  ?>;  // [[ID, title], …]
    var savedIds    = <?php echo $saved_ids_json;  ?>; // e.g. [16, 708, 727, …]
    var workingIds  = savedIds.slice();                // “preview” state
    var $selectBox  = $('#fle_page_select');
    var $radioAdd   = $('input[name="fle_allowed_pages_action"][value="add"]');
    var $radioRemove= $('input[name="fle_allowed_pages_action"][value="remove"]');
    var $previewList= $('#fle-current-list');
    var $ticketCount= $('#ticket-count');

    // ──────────────────────────────────────────────────────────────
    // 2) ON PAGE LOAD: If we just saved (settings-updated=true), clear any left-pane selections
    // ──────────────────────────────────────────────────────────────
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('settings-updated') === 'true') {
        // Explicitly clear <select> selections in case WP re-populated them
        $selectBox.val([]);
        $selectBox.find('option').css({'background-color':'','color':''});
        // Reload workingIds from the newly-saved state
        workingIds = savedIds.slice();
    }

    // ──────────────────────────────────────────────────────────────
    // 3) UPDATE TICKET COUNTER WITH A “PULSE”
    // ──────────────────────────────────────────────────────────────
    function updateTicketCounter(count) {
        $ticketCount.text(count);
        $ticketCount.parent().css('animation','none');
        setTimeout(function(){
            $ticketCount.parent().css('animation','successPulse 0.5s ease-out');
        }, 10);
    }

   /**
 * Revokes one <li> by animating an Oompa Loompa in three slower phases:
 *   1) moderate dash from off-screen left to just before the ticket
 *   2) slow crawl across the ticket
 *   3) moderate dash off-screen right
 * Uses one of 🎩 🪄 🍫 ✨ at random, no colored circle behind it.
 */
function revokeWithOompaLoompas($li, callback) {
    // 1) Choose random emoji and message
    var emojis   = ['🎩','🪄','🍫','✨','👾','🥷','🔪','🗡️','🔮'];
    var messages = [
      "No more ticket!", "The suspense is terrible. I hope it’ll last.", 
      "You get NOTHING!", "Good day sir!", "Slugworth was here!", "Everlasting? Not anymore!",
      "She was a bad egg.", "Stop. Don't. Come back.",
      "Veruca demands it!", "Grandpa Joe's revenge!",
      "Chocolate Bar expired!", "Inventing room malfunction!",
      "Tunnel vision revoked!", "Pure imagination… GONE!",
      "Snozzberries taste like LIES!", "Strike that, reverse it!",
      "We have so much time and so little to see", "Candy cane confiscated!", "She was a bad egg.",
      "Help. Police. Murder.","Impossible, my dear lady! That’s absurd! Unthinkable!", "Stop. Don't. Come back.", "Oh, you have questions? Let me drop everything.",
      "You get nothing! You lose! Good day, sir!"
    ];
    var randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
    var randomMsg   = messages[Math.floor(Math.random() * messages.length)];

    // 2) Get <li> position and size
    var liOffset = $li.offset();
    var liWidth  = $li.outerWidth();
    var liHeight = $li.outerHeight();

    // 3) Create floating “oompa-message” above the ticket
    var $message = $('<div class="oompa-message"></div>')
      .text(randomMsg)
      .css({
        position:    'absolute',
        whiteSpace:  'nowrap',
        fontFamily:  "'Comic Sans MS', cursive",
        fontSize:    '12px',
        background:  '#FFD700',
        color:       '#8B4513',
        padding:     '4px 8px',
        borderRadius:'12px',
        zIndex:      1002,
        top:         (liOffset.top - 32) + 'px',                  // 32px above <li>
        left:        (liOffset.left + liWidth/2) + 'px',          // centered
        transform:   'translateX(-50%)',
        opacity:     0
      })
      .appendTo('body');

    // 4) Create the Oompa Loompa itself (off-screen left),
    //    with no background color—only the emoji from data-character
    var $oompa = $('<div class="oompa-loompa"></div>')
      .css({
        position:        'fixed',
        top:             (liOffset.top + liHeight/2 - 10) + 'px',  // vertical center
        left:            '-30px',                                  // 30px off-screen
        width:           '20px',
        height:          '20px',
        fontSize:        '16px',
        textAlign:       'center',
        lineHeight:      '20px',
        background:      'transparent',                             // no colored circle
        borderRadius:    '0px',                                     // no rounding
        zIndex:          1001,
        pointerEvents:   'none',
        transformOrigin: 'center center'
      })
      .attr('data-character', randomEmoji)
      .appendTo('body');

    // 5) Fade in the message above the ticket
    $message.animate({ opacity: 1 }, 300);

    // 6) After a brief pause, do a three-phase walk
    setTimeout(function(){
      // Calculate the X coordinates for each phase
      var phase1X = liOffset.left - 30;                  // just before the ticket (fast)
      var phase2X = liOffset.left + (liWidth/2) - 10;    // center of the ticket (slow)
      var finalX  = window.innerWidth + 30;              // off-screen right (fast)

      // Phase 1: moderate dash → just before ticket
      $oompa.css('animation', 'oompaCharacterBounce 0.4s ease-in-out infinite alternate');
      $oompa.animate(
        { left: phase1X + 'px' },
        {
          duration: 600,    // moderate speed (was 300)
          easing: 'linear',
          complete: function() {
            // Phase 2: slow creep across ticket
            $oompa.animate(
              { left: phase2X + 'px' },
              {
                duration: 1200,  // slower (was 800)
                easing: 'swing',
                complete: function() {
                  // Phase 3: moderate dash off-screen right
                  $oompa.animate(
                    { left: finalX + 'px' },
                    {
                      duration: 600,  // moderate (was 300)
                      easing: 'linear',
                      complete: function(){
                        // Cleanup Oompa & message
                        $oompa.remove();
                        $message.fadeOut(200, function(){ $(this).remove(); });

                        // Slide the <li> out with a flourish
                        $li.css({
                          position:   'relative',
                          transform:  'translateX(100%) rotate(10deg)',
                          opacity:    0,
                          transition: 'all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                        });

                        // After slide-out, call callback()
                        setTimeout(function(){
                          if (callback) callback();
                        }, 900);
                      }
                    }
                  );
                }
              }
            );
          }
        }
      );
    }, 500);
}


    // ──────────────────────────────────────────────────────────────
    // 5) RENDER PREVIEW (“Pages with Golden Tickets”)
    // ──────────────────────────────────────────────────────────────
    function renderPreview(ids) {
        $previewList.empty();
        updateTicketCounter(ids.length);

        if (!ids.length) {
            $previewList.append(
                '<li style="color:#999; font-style:italic; padding:15px; text-align:center; ' +
                'border:2px dashed #ccc; border-radius:8px;">' +
                  '🔒 All pages require login<br>' +
                  '<small>(no Golden Tickets granted)</small>' +
                '</li>'
            );
            return;
        }

        // Map ID → title
        var titleMap = {};
        allPages.forEach(function(pair){
            titleMap[pair[0]] = pair[1];
        });

        // Sort IDs alphabetically by title
        ids.sort(function(a,b){
            var ta = (titleMap[a]||'').toLowerCase(),
                tb = (titleMap[b]||'').toLowerCase();
            return ta < tb ? -1 : (ta > tb ? 1 : 0);
        });

        // Build one <li> per ID
        ids.forEach(function(id,index){
            var title = titleMap[id] || '(Unknown)';
            var $li = $(
                '<li style="' +
                  'margin-bottom:8px; ' +
                  'padding:8px 12px; ' +
                  'background: linear-gradient(135deg, rgba(50,205,50,0.1), rgba(255,215,0,0.1)); ' +
                  'border-radius:6px; ' +
                  'border-left:4px solid #32CD32; ' +
                  'border-right:2px solid #FFD700; ' +
                  'transition: all 0.3s ease; ' +
                  'cursor: pointer; ' +
                  'animation: slideIn 0.3s ease-out ' + (index * 0.1) + 's both;' +
                '">' +
                  '🎫 <strong>' + title + '</strong> ' +
                  '<small style="color:#666; display:block; margin-top:2px;">' +
                    '(Public access granted)' +
                  '</small>' +
                '</li>'
            );
            // Hover styling
            $li.hover(
                function(){
                    $(this).css({
                        'transform':'translateX(5px) scale(1.02)',
                        'box-shadow':'0 4px 12px rgba(50,205,50,0.3)',
                        'background':'linear-gradient(135deg, rgba(50,205,50,0.2), rgba(255,215,0,0.2))'
                    });
                },
                function(){
                    $(this).css({
                        'transform':'translateX(0) scale(1)',
                        'box-shadow':'none',
                        'background':'linear-gradient(135deg, rgba(50,205,50,0.1), rgba(255,215,0,0.1))'
                    });
                }
            );
            $previewList.append($li);
        });
    }

    // ──────────────────────────────────────────────────────────────
    // 6) UPDATE workingIds WHEN SELECT BOX OR RADIO CHANGES
    // ──────────────────────────────────────────────────────────────
    function updatePreview() {
        var action       = $radioAdd.is(':checked') ? 'add' : 'remove';
        var selectedVals = $selectBox.val() || [];     // ["123","456",…]
        var selectedIds  = selectedVals.map(function(x){ return parseInt(x, 10); });
        var newIds;

        if (action === 'add') {
            // Merge workingIds + selectedIds (no duplicates)
            newIds = workingIds.slice();
            selectedIds.forEach(function(id){
                if (newIds.indexOf(id) === -1) {
                    newIds.push(id);
                }
            });
        } else {
            // “remove” = filter out any selectedId
            newIds = workingIds.filter(function(id){
                return selectedIds.indexOf(id) === -1;
            });
        }

        workingIds = newIds;
        renderPreview(workingIds);

        // ——> DO NOT clear <select> here. We want the user’s left-pane selections  
        //     to remain “checked” until they hit Save. <——

        // Highlight logic in Grant mode
        if ($radioAdd.is(':checked')) {
            $selectBox.find('option:selected')
                      .css({'background-color':'#32CD32','color':'#fff'});
        } else {
            // Remove highlights in Revoke mode
            $selectBox.find('option').css({'background-color':'','color':''});
        }
    }

    // ──────────────────────────────────────────────────────────────
    // 7) INTERCEPT <OPTION> CLICKS FOR “NO-CTRL” MULTISELECT
    // ──────────────────────────────────────────────────────────────
    $selectBox.on('mousedown', 'option', function(e){
        // Let Shift + Click do native range-select
        if (e.shiftKey) return;
        e.preventDefault();              // block default multi-select
        this.selected = !this.selected;  // toggle on/off
        $(this).parent().trigger('change');
    });

    $selectBox.on('change', updatePreview);

    // ──────────────────────────────────────────────────────────────
    // 8) CLEAR LEFT SELECTIONS WHEN SWITCHING RADIOS (BUT KEEP workingIds)
    // ──────────────────────────────────────────────────────────────
    function clearSelectHighlights() {
        $selectBox.val([]); 
        $selectBox.find('option').css({'background-color':'','color':''});
        // Do NOT reset workingIds: preserve any adds/revokes you’ve made so far
        renderPreview(workingIds);
    }
    $radioAdd.on('change', clearSelectHighlights);
    $radioRemove.on('change', clearSelectHighlights);

    // ──────────────────────────────────────────────────────────────
    // 9) “Revoke All” BUTTON
    // ──────────────────────────────────────────────────────────────
    $('#revoke-all-btn').on('click', function(e){
        e.preventDefault();
        if (!workingIds.length) {
            alert('🎫 No Golden Tickets to revoke! All pages already require login.');
            return;
        }
        var confirmMsg = 
            '🚫 Are you sure you want to revoke ALL ' + workingIds.length +
            ' Golden Tickets?\n\nThis will make ALL pages require login.';
        if (!confirm(confirmMsg)) return;

        // Select every <option> where its value is in workingIds
        $selectBox.find('option').each(function(){
            var pid = parseInt($(this).val(),10);
            $(this).prop('selected', workingIds.indexOf(pid) !== -1);
        });
        $radioRemove.prop('checked', true);
        updatePreview();

       
    });

    // ──────────────────────────────────────────────────────────────
    // 10) CLICKING A TICKET IN THE PREVIEW REMOVES JUST THAT ONE
    // ──────────────────────────────────────────────────────────────
    $previewList.on('click','li', function(){
        if (!$radioRemove.is(':checked')) {
            return; // only revoke-on-click if “remove” is active
        }
        var titleText = $(this).find('strong').text();
        var match = allPages.filter(function(pair){
            return pair[1] === titleText;
        });
        if (!match.length) return;
        var pageId = match[0][0];

        // Mark its <option> selected so the form knows to remove it on save
        $selectBox.find('option[value="'+pageId+'"]').prop('selected', true);

        revokeWithOompaLoompas($(this), function(){
            workingIds = workingIds.filter(function(id){
                return id !== pageId;
            });
            renderPreview(workingIds);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11) BIG “BOTTOM-UP” CONFETTI ANIMATION
    // ──────────────────────────────────────────────────────────────
    if (!$('#confettiUpStyles').length) {
        $('head').append(
            '<style id="confettiUpStyles">' +
            '  .confetti {' +
            '    position: fixed; width: 8px; height: 8px; bottom: 0;' +
            '    pointer-events: none; opacity: 1; z-index: 9998;' +
            '  }' +
            '  .confetti.gold   { background: linear-gradient(45deg,#FFD700,#FFA500); box-shadow:0 0 6px rgba(255,215,0,0.8);} ' +
            '  .confetti.orange { background: linear-gradient(45deg,#FF8C00,#FF4500); box-shadow:0 0 6px rgba(255,140,0,0.8);} ' +
            '  .confetti.green  { background: linear-gradient(45deg,#32CD32,#7CFC00); box-shadow:0 0 6px rgba(50,205,50,0.8);} ' +
            '  .confetti.purple { background: linear-gradient(45deg,#9370DB,#BA55D3); box-shadow:0 0 6px rgba(147,112,219,0.8);} ' +
            '  @keyframes confettiUp {' +
            '    0%   { transform: translateY(0) rotate(0deg);   opacity:1;} ' +
            '    100% { transform: translateY(-100vh) rotate(720deg); opacity:0;} ' +
            '  }' +
            '</style>'
        );
    }
    function createConfetti(count) {
        count = count || 50;
        var colors = ['gold','orange','green','purple'];
        for (var i = 0; i < count; i++) {
            var confetti = $('<div class="confetti"></div>');
            var color    = colors[Math.floor(Math.random() * colors.length)];
            var startX   = Math.random() * window.innerWidth;
            var duration = 3 + Math.random() * 2;  // 3–5s
            var drift    = (Math.random() - 0.5) * 200;

            confetti.addClass(color);
            confetti.css({
                left:      startX + 'px',
                bottom:    '0px',
                animation: 'confettiUp ' + duration + 's ease-out forwards',
                transform: 'translateX(' + drift + 'px)'
            });
            $('body').append(confetti);

            setTimeout((function($el){
                return function(){ $el.remove(); };
            })(confetti), duration * 1000 + 500);
        }
    }

   // ──────────────────────────────────────────────────────────────
// 12) SUBMIT FORM + PLAY CONFETTI ANIMATIONS
// ──────────────────────────────────────────────────────────────
$('.golden-save-btn').on('click', function(e){
    e.preventDefault();

    var $btn  = $(this);
    var $form = $btn.closest('form');

    // 1) Add "loading" state immediately so user sees feedback
    $btn.addClass('loading');

    // 2) Fire confetti animation almost right away
    setTimeout(function(){
        createConfetti(100);
    }, 200);

    // 3) Switch to "saving" style after a brief pause
    setTimeout(function(){
        $btn.removeClass('loading').addClass('saving');
    }, 600);

    // 4) Actually submit the form a fraction of a second later
    //    (so the animations can start rendering before the page reloads)
    setTimeout(function(){
        $form.trigger('submit');
    }, 50);
});

    // ──────────────────────────────────────────────────────────────
    // 13) SPARKLES (UNCHANGED)
    // ──────────────────────────────────────────────────────────────
    function createSparkles(element, count) {
        count = count || 12;
        var rect    = element.getBoundingClientRect(),
            centerX = rect.left + rect.width/2,
            centerY = rect.top  + rect.height/2;

        for (var i = 0; i < count; i++){
            var sparkle = $('<div class="sparkle"></div>');
            var angle   = (360 / count) * i;
            var distance= 60 + Math.random() * 40;
            var x = centerX + Math.cos(angle * Math.PI/180) * distance;
            var y = centerY + Math.sin(angle * Math.PI/180) * distance;

            sparkle.css({
                left:  x + 'px',
                top:   y + 'px',
                animationDelay: (i * 0.1) + 's'
            });
            $('body').append(sparkle);
            setTimeout((function($el){
                return function(){ $el.remove(); };
            })(sparkle), 2000);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // 14) HEADER SPARKLE & AUTO-HIDE “SUCCESS” MESSAGE
    // ──────────────────────────────────────────────────────────────
    window.createHeaderSparkles = function() {
        createSparkles($('#gt-banner')[0], 15);
    };
    setTimeout(function(){
        $('#success-message').fadeOut(1000);
    }, 5000);

    // ──────────────────────────────────────────────────────────────
    // 15) INITIALIZE ON PAGE LOAD
    // ──────────────────────────────────────────────────────────────
    renderPreview(workingIds);
    setTimeout(function(){
        createSparkles($('#gt-banner')[0], 5);
    }, 1000);

    console.log('🎫 Golden Ticket Plugin JavaScript loaded!');
});
</script>

    <?php
}


/**
 * The main force-login logic runs on 'template_redirect'
 */
add_action( 'template_redirect', 'fle_force_login_check' );
function fle_force_login_check() {
    // Skip if user is already logged in
    if ( is_user_logged_in() ) {
        return;
    }

    // Skip if we’re on login/register/lost-password pages
    global $pagenow;
    if ( $pagenow === 'wp-login.php' ) {
        return;
    }

    // Skip AJAX requests
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    // Skip REST API requests
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    // Skip cron jobs
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
        return;
    }

    // Get current page ID if we’re on a single page
    $current_page_id = 0;
    if ( is_page() ) {
        $current_page_id = get_the_ID();
    }

    // Get the allowed pages from settings
    $raw_allowed = get_option( 'fle_allowed_pages', '' );
    $allowed_ids = array_filter( array_map( 'intval', explode( ',', $raw_allowed ) ) );

    // If current page has a Golden Ticket, allow access
    if ( $current_page_id > 0 && in_array( $current_page_id, $allowed_ids, true ) ) {
        return; // Golden Ticket found! Access granted.
    }

    // No Golden Ticket found – redirect to login
    $login_url = wp_login_url( home_url( $_SERVER['REQUEST_URI'] ) );
    wp_redirect( $login_url );
    exit;
}
