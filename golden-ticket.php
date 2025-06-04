<?php
/*
Plugin Name: The Golden Ticket
Description: Redirects visitors to the login screen except on pages you list in settings. Find your Golden Ticket to access the chocolate factory!
Version: 2.0
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
 * Register our two settings (allowed pages + action)
 */
add_action( 'admin_init', 'fle_register_settings' );
function fle_register_settings() {
    register_setting(
        'fle_settings_group',
        'fle_allowed_pages',
        'fle_sanitize_page_list'
    );
    register_setting(
        'fle_settings_group',
        'fle_allowed_pages_action',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'add',
        )
    );
}


/**
 * Add our menu item under Settings
 */
add_action( 'admin_menu', 'fle_add_settings_page' );
function fle_add_settings_page() {
    add_options_page(
        'The Golden Ticket',
        'Golden Ticket',
        'manage_options',
        'fle-settings',
        'fle_render_settings_page'
    );
}


/**
 * Sanitize callback: merge or subtract based on action.
 */
function fle_sanitize_page_list( $input ) {
    $submitted_ids = is_array( $input )
        ? array_map( 'intval', $input )
        : array();

    $raw_current  = get_option( 'fle_allowed_pages', '' );
    $current_ids  = array_filter( array_map( 'intval', explode( ',', $raw_current ) ) );

    $action = isset( $_POST['fle_allowed_pages_action'] )
        ? sanitize_text_field( $_POST['fle_allowed_pages_action'] )
        : 'add';

    if ( $action === 'add' ) {
        $new_ids = array_unique( array_merge( $current_ids, $submitted_ids ) );
    } elseif ( $action === 'remove' ) {
        $new_ids = array_diff( $current_ids, $submitted_ids );
    } else {
        $new_ids = $current_ids;
    }

    $new_ids = array_filter( array_map( 'absint', $new_ids ), function( $v ) {
        return ( $v > 0 );
    } );

    return implode( ',', $new_ids );
}


/**
 * Render the Settings page with Golden Ticket theme and animations
 */
function fle_render_settings_page() {
    // Fetch all pages and current whitelist for JS
    $all_pages       = get_pages( array(
        'post_status' => 'publish',
        'sort_column' => 'post_title',
        'sort_order'  => 'ASC',
    ) );
    $raw_allowed     = get_option( 'fle_allowed_pages', '' );
    $saved_ids       = array_filter( array_map( 'intval', explode( ',', $raw_allowed ) ) );
    $js_pages        = array();
    foreach ( $all_pages as $p ) {
        $js_pages[] = array( intval( $p->ID ), esc_js( $p->post_title ) );
    }
    $js_pages_json   = wp_json_encode( $js_pages );
    $saved_ids_json  = wp_json_encode( $saved_ids );
    $current_action  = get_option( 'fle_allowed_pages_action', 'add' );
    $plugin_url      = plugin_dir_url( __FILE__ );
    ?>
    <div class="wrap" style="padding-top:10px;">
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
            ">
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
                     " />
                <div style="position: relative; z-index: 1;">
                    <h1 style="margin: 0; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                        🍫 WordPress Plugin Settings 🎫
                    </h1>
                    <p style="margin: 5px 0 0 0; font-style: italic;">
                        Force login on your entire site - except for pages with Golden Tickets!
                    </p>
                </div>
            </div>
        </div>

        <form method="post" action="options.php" id="golden-ticket-form">
            <?php settings_fields( 'fle_settings_group' ); ?>

            <!-- Enhanced CSS with Golden Ticket Animations -->
            <style>
                /* Shimmer animation for header */
                @keyframes shimmer {
                    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
                    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
                }
                
                /* Logo glimmer animation - once on load */
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
                }
                
                .golden-save-btn:hover {
                    background: linear-gradient(45deg, #32CD32, #7CFC00, #32CD32) !important;
                    box-shadow: 0 4px 15px rgba(50, 205, 50, 0.4) !important;
                    transform: translateY(-2px) !important;
                }
                
                /* Golden Ticket Transform Animation */
                @keyframes ticketTransform {
                    0% { transform: scale(1) rotate(0deg); }
                    50% { transform: scale(1.1) rotate(5deg); background: linear-gradient(45deg, #FFD700, #FFA500); }
                    100% { transform: scale(1) rotate(0deg); }
                }
                
                .golden-save-btn.saving {
                    animation: ticketTransform 0.6s ease-in-out !important;
                    background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700) !important;
                    color: #8B4513 !important;
                }
                
                /* Sparkle circles for the button */
.sparkle {
  width: 6px;
  height: 6px;
  background: gold;
  border-radius: 50%;
  position: absolute;
  animation: sparkle-fade 1.5s ease-out forwards;
}

/* Sparkle keyframes */
@keyframes sparkle-fade {
  0%   { transform: scale(0.5); opacity: 1; }
  50%  { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(1); opacity: 0; }
}
                }
                
   /* Confetti squares */
.confetti {
  width: 8px;
  height: 8px;
  opacity: 1;
}

/* Define color classes */
.gold    { background: #FFD700; }
.orange  { background: #FF8C00; }
.green   { background: #32CD32; }
.purple  { background: #800080; }


    @keyframes confettiFall {
        0%   { transform: translateY(0) rotate(0deg); }
        100% { transform: translateY(-120vh) rotate(360deg); }
    }

                
                /* Update Preview button hover effect */
                #update-preview-btn:hover {
                    background: linear-gradient(45deg, #32CD32, #7CFC00) !important;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(50, 205, 50, 0.4);
                }
                
                /* Revoke All button - Wonka Purple */
                #revoke-all-btn {
                    background: linear-gradient(45deg, #6A5ACD, #9370DB) !important;
                    color: #FFD700 !important;
                    border: 2px solid #9370DB !important;
                    padding: 8px 16px;
                    border-radius: 5px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                #revoke-all-btn:hover {
                    background: linear-gradient(45deg, #9370DB, #BA55D3) !important;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(106, 90, 205, 0.4);
                }
                
                /* Success message styling */
                .golden-success {
                    background: linear-gradient(45deg, #32CD32, #7CFC00);
                    color: #006400;
                    padding: 10px 20px;
                    border-radius: 25px;
                    font-weight: bold;
                    text-align: center;
                    margin: 10px 0;
                    box-shadow: 0 4px 15px rgba(50, 205, 50, 0.3);
                    animation: successSlide 0.5s ease-out;
                }
                
                @keyframes successSlide {
                    0% { transform: translateY(-20px); opacity: 0; }
                    100% { transform: translateY(0); opacity: 1; }
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
                }
                
                /* Action section styling */
                .action-section {
                    background: linear-gradient(135deg, #f0fff0, #f0e6ff) !important;
                    border-left: 4px solid #32CD32 !important;
                }
                
                /* Page select section styling */
                .page-select-section {
                    background: linear-gradient(135deg, #f0e6ff, #f0fff0) !important;
                    border-left: 4px solid #9370DB !important;
                }
            </style>

            <!-- Parent wrapper to center everything -->
            <div id="fle-parent-container">
                <div id="fle-flex-container">
                    <!-- Left Column: Action + Multi-select -->
                    <div id="fle-left-column">
                        <!-- Action Radios with chocolate theme -->
                        <div class="action-section" style="padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="margin-top: 0; color: #228B22;">🍫 Choose Your Action</h3>
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
                                <strong>How it works:</strong> Your entire website requires login, except pages with Golden Tickets can be viewed by anyone. 
                                Select pages below to <?php echo $current_action === 'add' ? '<strong>grant them Golden Tickets</strong> (skip login requirement)' : '<strong>revoke their Golden Tickets</strong> (require login)'; ?>.
                            </p>
                        </div>

                        <!-- Multi-select of Pages -->
                        <div class="page-select-section" style="padding: 15px; border-radius: 8px;">
                            <h3 style="margin-top: 0; color: #6A5ACD;">📋 Select Pages</h3>
                            <?php
                            echo '<select id="fle_page_select" name="fle_allowed_pages[]" multiple size="10" style="width:100%; border: 2px solid #9370DB; border-radius: 5px;">';
                            foreach ( $all_pages as $page ) {
                                printf(
                                    '<option value="%1$d">%2$s</option>',
                                    esc_attr( $page->ID ),
                                    esc_html( $page->post_title )
                                );
                            }
                            echo '</select>';
                            ?>
                            <p class="description" style="margin-top:6px; margin-bottom:10px;">
                                Hold Ctrl (Windows) or Cmd (Mac) to select multiple pages. Pages with Golden Tickets can be viewed without logging in - like having VIP access! 🎫
                            </p>
                            
                        
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
                            These pages can be viewed without login - everyone else must sign in first.
                        </p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <?php submit_button( '🎫 Save Golden Ticket Settings 🍫', 'primary golden-save-btn', '', false, array( 'style' => 'font-size: 16px; padding: 10px 30px;' ) ); ?>
            </div>
        </form>
    </div>


<script type="text/javascript">
jQuery(document).ready(function($){
    var allPages       = <?php echo $js_pages_json;  ?>;  // [[id,title], …]
    var savedIds       = <?php echo $saved_ids_json;  ?>; // [16,708,727, …]
    var pendingChanges = []; // pages to add/remove
    var $selectBox     = $('#fle_page_select');
    var $radioAdd      = $('input[name="fle_allowed_pages_action"][value="add"]');
    var $radioRemove   = $('input[name="fle_allowed_pages_action"][value="remove"]');
    var $previewList   = $('#fle-current-list');
    var $saveBtn       = $('.golden-save-btn');
    var $banner        = $('#gt-banner');

    // ------------- Helpers --------------

    // Render the “Current Whitelist” preview (saved + pending changes)
    function renderPreview(ids) {
        $previewList.empty();
        if (!ids || ids.length === 0) {
            $previewList.append(
                '<li style="color: #999; font-style: italic;">🔒 All pages require login (no Golden Tickets granted)</li>'
            );
            return;
        }
        var titleMap = {};
        allPages.forEach(function(pair){
            titleMap[pair[0]] = pair[1];
        });
        ids.sort(function(a, b){
            var ta = titleMap[a].toLowerCase();
            var tb = titleMap[b].toLowerCase();
            return ta < tb ? -1 : (ta > tb ? 1 : 0);
        });
        ids.forEach(function(id){
            var title = titleMap[id] || '(Unknown)';
            var $li = $(
                '<li style="margin-bottom:8px; padding:5px; background:rgba(50,205,50,0.1); ' +
                'border-radius:3px; border-left:3px solid #32CD32;">' +
                '🎫 <strong>' + title + '</strong> ' +
                '<small style="color:#666;">(No login required)</small>' +
                '</li>'
            );
            $previewList.append($li);
        });
    }

    // Compute “final” whitelist (savedIds + pendingChanges)
    function getCurrentWhitelistPreview() {
        var current = savedIds.slice();
        pendingChanges.forEach(function(change){
            if (change.action === 'add') {
                if (current.indexOf(change.id) === -1) {
                    current.push(change.id);
                }
            } else {
                var idx = current.indexOf(change.id);
                if (idx !== -1) {
                    current.splice(idx, 1);
                }
            }
        });
        return current.map(function(x){ return parseInt(x,10); });
    }

    // Highlight saved IDs in the multi-select
    function updateVisualIndicators() {
        $selectBox.find('option').css({
            'background-color': '',
            'color': '',
            'font-weight': '',
            'border-left': ''
        });
        savedIds.forEach(function(id){
            $selectBox.find('option[value="' + id + '"]').css({
                'background-color': '#e8f5e8',
                'border-left': '4px solid #32CD32',
                'color': '#333'
            });
        });
    }

    // Generic sparkle function for any element ($elem)
    function createSparklesOnElement($elem) {
        var offset = $elem.offset();
        var w      = $elem.outerWidth();
        var h      = $elem.outerHeight();

        for (var i = 0; i < 8; i++) {
            setTimeout(function(){
                var sparkle = $('<div class="sparkle"></div>');
                sparkle.css({
                    left:  offset.left + Math.random() * w,
                    top:   offset.top  + Math.random() * h,
                    animationDelay: Math.random() * 0.5 + 's'
                });
                $('body').append(sparkle);
                setTimeout(function(){
                    sparkle.remove();
                }, 1500);
            }, i * 100);
        }
    }

    // Specifically sparkles over the banner
    function createBannerSparkles() {
        if (!$banner.length) {
            return;
        }
        createSparklesOnElement($banner);
    }

    // Create confetti around banner + Save button
    function createConfetti() {
        var colors       = ['gold', 'orange', 'green', 'purple'];
        var btnOffset    = $saveBtn.offset();
        var btnWidth     = $saveBtn.outerWidth();
        var btnHeight    = $saveBtn.outerHeight();
        var bannerOffset, bannerWidth, bannerHeight;
        if ($banner.length) {
            bannerOffset = $banner.offset();
            bannerWidth  = $banner.outerWidth();
            bannerHeight = $banner.outerHeight();
        }

        for (var i = 0; i < 25; i++) {
            setTimeout(function(){
                var confetti = $('<div class="confetti"></div>');
                var color    = colors[Math.floor(Math.random() * colors.length)];
                confetti.addClass(color);

                var spawnTarget = 'button';
                if ($banner.length && Math.random() < 0.5) {
                    spawnTarget = 'banner';
                }

                var startLeft, startTop;
                if (spawnTarget === 'banner') {
                    startLeft = bannerOffset.left + Math.random() * bannerWidth;
                    startTop  = bannerOffset.top  + bannerHeight + 5;
                } else {
                    startLeft = btnOffset.left + Math.random() * btnWidth;
                    startTop  = btnOffset.top  + btnHeight + 5;
                }

                confetti.css({
                    left:     startLeft + 'px',
                    top:      startTop  + 'px',
                    position: 'absolute',
                    zIndex:   9999
                });
                $('body').append(confetti);

                var endLeft      = startLeft + (Math.random() * 100 - 50);
                var fallDistance = 150 + Math.random() * 100;
                var fallDuration = 1500 + Math.random() * 500;

                confetti.animate({
                    top:  (startTop + fallDistance) + 'px',
                    left: endLeft + 'px',
                    opacity: 0
                }, fallDuration, 'linear', function(){
                    confetti.remove();
                });
            }, i * 100);
        }
    }

    // ------------- Event Handlers --------------

    // 1) Option click in multi-select → track pendingChanges + update preview + highlight
    $selectBox.on('click', 'option', function(e){
        e.preventDefault();
        var pageId        = parseInt($(this).val(), 10);
        var currentAction = $radioAdd.is(':checked') ? 'add' : 'remove';

        // Remove any old pending for this page
        pendingChanges = pendingChanges.filter(function(change){
            return change.id !== pageId;
        });
        // Add new pending
        pendingChanges.push({ id: pageId, action: currentAction });

        // Will this page end up whitelisted?
        var previewList     = getCurrentWhitelistPreview();
        var willHaveTicket  = (previewList.indexOf(pageId) !== -1);

        if (currentAction === 'add') {
            // highlighting green if it ends up whitelisted
            $(this).css({
                'background-color': willHaveTicket ? '#32CD32' : '#ff6b6b',
                'color': 'white',
                'font-weight': 'bold'
            });
        } else {
            // remove mode → highlight red if it ends up removed
            $(this).css({
                'background-color': !willHaveTicket ? '#ff6b6b' : '#32CD32',
                'color': 'white',
                'font-weight': 'bold'
            });
        }

        renderPreview(getCurrentWhitelistPreview());
        return false;
    });

    // 2) Toggling Grant/Remove → clear selection, pending, reset preview + highlights
    $radioAdd.add($radioRemove).on('change', function(){
        $selectBox.find('option').prop('selected', false);
        pendingChanges = [];
        renderPreview(savedIds);
        updateVisualIndicators();
    });

    // 3) “Revoke All” button → queue removal for all saved IDs + trigger save click
    $('#revoke-all-btn').on('click', function(){
        if (savedIds.length === 0) {
            alert('🔒 No Golden Tickets to revoke – all pages already require login!');
            return;
        }
        if (confirm('🚨 Revoke ALL Golden Tickets?\n\nThis will require login for your entire site (no exceptions).')) {
            $radioRemove.prop('checked', true);
            pendingChanges = [];
            savedIds.forEach(function(id){
                pendingChanges.push({ id: id, action: 'remove' });
            });
            $saveBtn.trigger('click');
        }
    });

    // 4) Save Button Click → validate, populate <select>, run sparkles/confetti, then submit
    $saveBtn.on('click', function(e){
        e.preventDefault();

        if (pendingChanges.length === 0) {
            alert('No changes to save! Click on page names to grant or revoke Golden Tickets first.');
            return;
        }

        // Populate <select> for Settings API
        $selectBox.find('option').prop('selected', false);
        pendingChanges.forEach(function(change){
            $selectBox.find('option[value="' + change.id + '"]').prop('selected', true);
        });

        // Fire animations
        $saveBtn.addClass('saving');
        $saveBtn.text('🎫 Processing Golden Tickets... 🎫');
        // Sparkles on the button:
        createSparklesOnElement($saveBtn);
        // Sparkles on the banner:
        createBannerSparkles();
        // Confetti around both:
        createConfetti();

        // After short delay, submit form
        setTimeout(function(){
            $('#golden-ticket-form')[0].submit();
        }, 600);
    });

    // 5) Change Save button text when toggling Grant/Remove
    $radioAdd.add($radioRemove).on('change', function(){
        if ($radioAdd.is(':checked')) {
            $saveBtn.text('🎫 Grant Golden Tickets! 🍫');
        } else {
            $saveBtn.text('🚫 Revoke Golden Tickets 🚫');
        }
    });

    // INITIAL RENDER
    renderPreview(savedIds);
    updateVisualIndicators();
});
</script>


    <?php
}


/**
 * Front-end hook: force login except on allowed pages
 */
add_action( 'template_redirect', 'fle_force_login_except_allowed', 0 );
function fle_force_login_except_allowed() {
    if (
        is_admin()
        || is_user_logged_in()
        || ( defined( 'DOING_AJAX' )   && DOING_AJAX )
        || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
        || in_array( isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '', array( 'wp-login.php', 'wp-register.php' ), true )
    ) {
        return;
    }

    $raw   = get_option( 'fle_allowed_pages', '' );
    $ids   = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
    $pages = array_map( 'absint', $ids );

    if ( is_page( $pages ) ) {
        return;
    }

    wp_safe_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
    exit;
}
?>