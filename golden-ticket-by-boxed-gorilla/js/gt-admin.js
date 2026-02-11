/**
 * Golden Ticket Admin JavaScript – v2.2.0
 * "A little nonsense now and then is relished by the wisest men."
 */
jQuery(document).ready(function($){

    // ------------------------------------------------------------------
    // 1) VARIABLES & LOOKUP MAPS
    // ------------------------------------------------------------------
    var $banner      = $('#gt-banner');
    var allItems     = gtData.allItems;
    var savedIds     = gtData.savedIds;
    var currentMode  = gtData.currentMode;
    // Normalize IDs to numbers — wp_localize_script may pass strings
    var workingIds   = savedIds.map(function(x){ return parseInt(x, 10); });
    var $selectBox   = $('#gt-page-select');
    var $searchBox   = $('#gt-search');
    var $radioAdd    = $('input[name="gt_allowed_pages_action"][value="add"]');
    var $radioRemove = $('input[name="gt_allowed_pages_action"][value="remove"]');
    var $modeToggle  = $('#gt-access-mode');
    var $modeGolden  = $('#mode-golden');
    var $modeInvent  = $('#mode-inventing');
    var $labelAdd        = $('#label-add-text');
    var $labelRemove     = $('#label-remove-text');
    var $actionDesc      = $('#action-desc');
    var $previewList     = $('#gt-current-list');
    var $previewHeader   = $('#preview-header');
    var $previewInfo     = $('#preview-info');
    var $ticketCount     = $('#ticket-count');
    var $ticketPrefix    = $('#ticket-prefix');
    var $ticketLabel     = $('#ticket-label');
    var $revokeAll       = $('#revoke-all-btn');
    var $addAll          = $('#gt-add-all');
    var $removeAll       = $('#gt-remove-all');
    var $addAllProducts  = $('#gt-add-all-products');
    var $removeAllProducts = $('#gt-remove-all-products');
    var $parentContainer = $('#gt-parent-container');
    var $rightColumn     = $('#gt-right-column');
    // Keep as strings for jQuery $selectBox.val(allIds) compatibility
    var allIds = allItems.map(function(item){ return String(item[0]); });

    var titleMap = {};
    var typeMap  = {};
    allItems.forEach(function(item){
        var id = parseInt(item[0], 10);
        titleMap[id] = item[1];
        typeMap[id]  = item[2];
    });

    var typeIcons  = { page: '\uD83D\uDCC4', post: '\uD83D\uDCDD', product: '\uD83D\uDECD\uFE0F' };
    var typeLabels = { page: 'Page', post: 'Post', product: 'Product' };

    // ------------------------------------------------------------------
    // 2) SEARCH / FILTER
    // ------------------------------------------------------------------
    $searchBox.on('input', function(){
        var query = $(this).val().toLowerCase().trim();
        $selectBox.find('option').each(function(){
            var title = $(this).text().toLowerCase();
            $(this).toggle(!query || title.indexOf(query) !== -1);
        });
        $selectBox.find('optgroup').each(function(){
            $(this).toggle($(this).find('option:visible').length > 0);
        });
    });

    // ------------------------------------------------------------------
    // 3) CURSOR SPARKLE TRAIL
    // ------------------------------------------------------------------
    var lastSparkleTime = 0;
    $parentContainer.on('mousemove', function(e){
        var now = Date.now();
        if (now - lastSparkleTime < 60) return;
        lastSparkleTime = now;

        var colors = currentMode === 'inventing'
            ? ['rgba(147,112,219,0.6)', 'rgba(186,85,211,0.5)', 'rgba(106,90,205,0.4)']
            : ['rgba(255,215,0,0.6)', 'rgba(255,165,0,0.5)', 'rgba(50,205,50,0.4)'];
        var size = 3 + Math.random() * 4;

        var $spark = $('<div class="cursor-sparkle"></div>').css({
            left: e.clientX + 'px',
            top:  e.clientY + 'px',
            width:  size + 'px',
            height: size + 'px',
            background: colors[Math.floor(Math.random() * colors.length)]
        });
        $('body').append($spark);
        setTimeout(function(){ $spark.remove(); }, 600);
    });

    // ------------------------------------------------------------------
    // 4) MODE SWITCHING — with sparkle cascade
    // ------------------------------------------------------------------
    $modeToggle.on('change', function(){
        workingIds = [];
        $selectBox.val([]);
        $selectBox.find('option').css({'background-color':'','color':''});
        $addAll.prop('checked', false);
        $removeAll.prop('checked', false);
        $addAllProducts.prop('checked', false);
        $removeAllProducts.prop('checked', false);

        var newMode = this.checked ? 'inventing' : 'golden';

        $parentContainer.addClass('mode-transitioning');
        createModeCascade(newMode);

        setTimeout(function(){
            setModeUI(newMode);
            $parentContainer.removeClass('mode-transitioning');
        }, 450);
    });

    function createModeCascade(mode) {
        var colors = mode === 'inventing'
            ? ['#9370DB','#BA55D3','#6A5ACD','#FF4500','#8B008B']
            : ['#FFD700','#FFA500','#32CD32','#7CFC00','#D2691E'];

        // Sparkle burst from the toggle switch
        var switchRect = $('.switch')[0].getBoundingClientRect();
        var cx = switchRect.left + switchRect.width/2;
        var cy = switchRect.top + switchRect.height/2;

        for (var i = 0; i < 30; i++) {
            (function(delay){
                setTimeout(function(){
                    var angle = Math.random() * 360;
                    var dist  = 30 + Math.random() * 120;
                    var x = cx + Math.cos(angle * Math.PI/180) * dist;
                    var y = cy + Math.sin(angle * Math.PI/180) * dist;
                    var size = 3 + Math.random() * 7;
                    var shapes = ['sparkle-star','sparkle-circle','sparkle-diamond'];

                    var $spark = $('<div class="sparkle ' + shapes[Math.floor(Math.random()*shapes.length)] + '"></div>').css({
                        left: x + 'px', top: y + 'px',
                        width: size + 'px', height: size + 'px',
                        background: colors[Math.floor(Math.random() * colors.length)]
                    });
                    $('body').append($spark);
                    setTimeout(function(){ $spark.remove(); }, 2000);
                }, delay);
            })(i * 15);
        }
    }

    function setModeUI(mode){
        currentMode = mode;

        if (mode === 'inventing') {
            $modeGolden.removeClass('active');
            $modeInvent.addClass('active');
            $parentContainer.removeClass('golden-mode').addClass('inventing-mode');
            $rightColumn.removeClass('golden-mode').addClass('inventing-mode');
            $labelAdd.text('Protect Content');
            $labelRemove.text('Open Content');
            $previewHeader.html('\uD83D\uDD12 Protected Content');
            $previewInfo.text('Only these items require login. Everything else is open.');
            $revokeAll.text('Remove All Protected');
            $('#label-add-all').text('Protect All');
            $('#label-remove-all').text('Open All');
            $('#label-add-all-products').text('Protect Products');
            $('#label-remove-all-products').text('Open Products');
            $ticketPrefix.text('\uD83D\uDD12');
            $ticketLabel.text('Protected Items');
        } else {
            $modeInvent.removeClass('active');
            $modeGolden.addClass('active');
            $parentContainer.removeClass('inventing-mode').addClass('golden-mode');
            $rightColumn.removeClass('inventing-mode').addClass('golden-mode');
            $labelAdd.text('Grant Tickets');
            $labelRemove.text('Revoke Tickets');
            $previewHeader.html('\uD83C\uDFAB Content with Golden Tickets');
            $previewInfo.text('These items are public \u2014 all others require login.');
            $revokeAll.text('Revoke All Tickets');
            $('#label-add-all').text('Grant All');
            $('#label-remove-all').text('Revoke All');
            $('#label-add-all-products').text('Grant Products');
            $('#label-remove-all-products').text('Revoke Products');
            $ticketPrefix.text('\uD83C\uDFAB');
            $ticketLabel.text('Golden Tickets Active');
        }
        renderPreview(workingIds);
        updateActionDescription();
    }

    function updateActionDescription(){
        var action = $radioAdd.is(':checked') ? 'add' : 'remove';
        var text = '';
        if (currentMode === 'inventing') {
            text = 'Site is open. ' + (action === 'add'
                ? 'Select items to protect (require login).'
                : 'Select items to open (remove login).');
        } else {
            text = 'Site requires login. ' + (action === 'add'
                ? 'Select items to grant tickets (make public).'
                : 'Select items to revoke tickets (require login).');
        }
        $actionDesc.text(text);
    }

    // ------------------------------------------------------------------
    // 5) ON PAGE LOAD: Clear if just saved
    // ------------------------------------------------------------------
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('settings-updated') === 'true') {
        $selectBox.val([]);
        $selectBox.find('option').css({'background-color':'','color':''});
        workingIds = savedIds.map(function(x){ return parseInt(x, 10); });
        $addAll.prop('checked', false);
        $removeAll.prop('checked', false);
        $addAllProducts.prop('checked', false);
        $removeAllProducts.prop('checked', false);
    }

    // ------------------------------------------------------------------
    // 6) ANIMATED TICKET COUNTER
    // ------------------------------------------------------------------
    function updateTicketCounter(count) {
        var current = parseInt($ticketCount.text(), 10) || 0;
        if (current !== count) {
            // Animate count up/down
            var diff = count - current;
            var steps = Math.min(Math.abs(diff), 12);
            var stepTime = Math.max(30, 300 / steps);
            for (var i = 1; i <= steps; i++) {
                (function(step){
                    setTimeout(function(){
                        var val = Math.round(current + (diff * step / steps));
                        $ticketCount.text(val);
                    }, stepTime * step);
                })(i);
            }
            // Bounce
            $ticketCount.parent().css('animation','none');
            setTimeout(function(){
                $ticketCount.parent().css('animation','counterPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)');
            }, stepTime * steps);
        }
    }

    // ------------------------------------------------------------------
    // 7) OOMPA LOOMPA REVOKE — with richer quotes
    // ------------------------------------------------------------------
    function revokeWithOompaLoompas($li, callback) {
        var emojis = ['\uD83C\uDFA9','\uD83E\uDE84','\uD83C\uDF6B','\u2728','\uD83D\uDC7E','\uD83E\uDD77','\uD83D\uDD2E','\uD83E\uDDD9','\uD83C\uDFAA','\uD83C\uDCCF','\uD83E\uDDDB','\uD83C\uDF1F'];
        var messages = [
            "No more ticket!",
            "The suspense is terrible. I hope it'll last.",
            "You get NOTHING!",
            "Good day sir!",
            "Slugworth was here!",
            "Everlasting? Not anymore!",
            "She was a bad egg.",
            "Pure imagination\u2026 GONE!",
            "The Snozzberries taste like LIES!",
            "Strike that, reverse it!",
            "Help. Police. Murder.",
            "Stop. Don't. Come back.",
            "You get nothing! You lose!",
            "We are the music makers\u2026",
            "So shines a good deed in a weary world.",
            "Candy is dandy but liquor is quicker.",
            "A little nonsense now and then\u2026",
            "Invention, my dear friends, is 93% perspiration.",
            "There's no earthly way of knowing\u2026",
            "Time is a precious thing. Never waste it."
        ];
        var randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
        var randomMsg   = messages[Math.floor(Math.random() * messages.length)];

        var liOffset   = $li.offset();
        var scrollTop  = $(window).scrollTop();
        var scrollLeft = $(window).scrollLeft();
        var liWidth    = $li.outerWidth();
        var liHeight   = $li.outerHeight();

        var $message = $('<div class="oompa-message"></div>')
            .text(randomMsg)
            .css({
                position: 'fixed', whiteSpace: 'nowrap',
                fontFamily: "'Georgia', serif", fontSize: '12px',
                zIndex: 1002,
                top:  (liOffset.top - scrollTop - 38) + 'px',
                left: (liOffset.left - scrollLeft + liWidth/2) + 'px',
                transform: 'translateX(-50%)', opacity: 0
            })
            .appendTo('body');

        var $oompa = $('<div class="oompa-loompa"></div>')
            .css({
                position: 'fixed',
                top:  (liOffset.top - scrollTop + liHeight/2 - 10) + 'px',
                left: '-30px',
                width: '20px', height: '20px',
                fontSize: '18px', textAlign: 'center', lineHeight: '20px',
                background: 'transparent', borderRadius: '0',
                zIndex: 1001, pointerEvents: 'none'
            })
            .attr('data-character', randomEmoji)
            .appendTo('body');

        $message.animate({ opacity: 1 }, 300);

        setTimeout(function(){
            var phase1X = liOffset.left - scrollLeft - 30;
            var phase2X = liOffset.left - scrollLeft + (liWidth/2) - 10;
            var finalX  = window.innerWidth + 30;

            $oompa.css('animation', 'oompaCharacterBounce 0.35s ease-in-out infinite alternate');
            $oompa.animate({ left: phase1X + 'px' }, {
                duration: 500, easing: 'linear',
                complete: function() {
                    $oompa.animate({ left: phase2X + 'px' }, {
                        duration: 1000, easing: 'swing',
                        complete: function() {
                            $oompa.animate({ left: finalX + 'px' }, {
                                duration: 500, easing: 'linear',
                                complete: function(){
                                    $oompa.remove();
                                    $message.fadeOut(200, function(){ $(this).remove(); });

                                    // Mini sparkle burst where the ticket was
                                    for (var s = 0; s < 6; s++) {
                                        var $sp = $('<div class="sparkle sparkle-star"></div>').css({
                                            left: (liOffset.left - scrollLeft + Math.random() * liWidth) + 'px',
                                            top: (liOffset.top - scrollTop + Math.random() * liHeight) + 'px',
                                            width: '6px', height: '6px',
                                            background: currentMode === 'inventing' ? '#BA55D3' : '#FFD700'
                                        });
                                        $('body').append($sp);
                                        setTimeout((function(el){ return function(){ el.remove(); }; })($sp), 1800);
                                    }

                                    $li.css({
                                        position: 'relative',
                                        transform: 'translateX(120%) rotate(12deg)',
                                        opacity: 0,
                                        transition: 'all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                                    });
                                    setTimeout(function(){ if (callback) callback(); }, 900);
                                }
                            });
                        }
                    });
                }
            });
        }, 400);
    }

    // ------------------------------------------------------------------
    // 8) RENDER PREVIEW
    // ------------------------------------------------------------------
    function renderPreview(ids) {
        $previewList.empty();
        updateTicketCounter(ids.length);

        if (!ids.length) {
            var emptyIcon = currentMode === 'inventing' ? '\uD83D\uDD13' : '\uD83D\uDD12';
            var emptyMsg = currentMode === 'inventing'
                ? '<strong>All content is public</strong><br><small>No pages are protected \u2013 the factory gates are wide open!</small>'
                : '<strong>All content is locked</strong><br><small>No Golden Tickets granted \u2013 visitors need to log in everywhere.</small>';
            $previewList.append(
                '<li class="preview-empty">' +
                    '<div class="empty-icon">' + emptyIcon + '</div>' +
                    emptyMsg +
                '</li>'
            );
            return;
        }

        ids.sort(function(a,b){
            var ta = (titleMap[a]||'').toLowerCase(),
                tb = (titleMap[b]||'').toLowerCase();
            return ta < tb ? -1 : (ta > tb ? 1 : 0);
        });

        ids.forEach(function(id, index){
            var title = titleMap[id] || '(Unknown)';
            var type  = typeMap[id]  || 'page';
            var icon  = currentMode === 'inventing' ? '\uD83D\uDD12' : '\uD83C\uDFAB';
            var typeIcon = typeIcons[type] || '\uD83D\uDCC4';
            var typeLabel = typeLabels[type] || 'Page';
            var note  = currentMode === 'inventing' ? 'Login required' : 'Public access';

            var $li = $(
                '<li class="preview-item" style="animation-delay: ' + (index * 0.05) + 's;">' +
                    '<div class="preview-item-main">' +
                        '<span class="preview-ticket-icon">' + icon + '</span>' +
                        '<strong class="preview-title">' + title + '</strong>' +
                    '</div>' +
                    '<div class="preview-item-meta">' +
                        '<span class="preview-type-badge type-' + type + '">' +
                            typeIcon + ' ' + typeLabel +
                        '</span>' +
                        '<span class="preview-status">' + note + '</span>' +
                    '</div>' +
                '</li>'
            );
            $li.hover(
                function(){ $(this).addClass('preview-item-hover'); },
                function(){ $(this).removeClass('preview-item-hover'); }
            );
            $previewList.append($li);
        });
    }

    // ------------------------------------------------------------------
    // 9) UPDATE workingIds ON SELECT / RADIO CHANGE
    // ------------------------------------------------------------------
    var previousWorkingCount = workingIds.length;

    function updatePreview() {
        var action       = $radioAdd.is(':checked') ? 'add' : 'remove';
        var selectedVals = $selectBox.val() || [];
        var selectedIds  = selectedVals.map(function(x){ return parseInt(x, 10); });
        var newIds;

        if (action === 'add') {
            newIds = workingIds.slice();
            selectedIds.forEach(function(id){
                if (newIds.indexOf(id) === -1) newIds.push(id);
            });
        } else {
            newIds = workingIds.filter(function(id){
                return selectedIds.indexOf(id) === -1;
            });
        }

        // Grant animation: brief stamp flash on newly added
        if (action === 'add' && newIds.length > previousWorkingCount) {
            flashGrantStamp();
        }

        workingIds = newIds;
        previousWorkingCount = newIds.length;
        renderPreview(workingIds);

        if ($radioAdd.is(':checked')) {
            $selectBox.find('option:selected')
                      .css({'background-color':'#32CD32','color':'#fff'});
        } else {
            $selectBox.find('option').css({'background-color':'','color':''});
        }
    }

    function flashGrantStamp() {
        var $stamp = $('<div class="ticket-stamp">' +
            (currentMode === 'inventing' ? '\uD83D\uDD12' : '\uD83C\uDFAB') +
        '</div>');
        $rightColumn.css('position','relative');
        $rightColumn.append($stamp);
        setTimeout(function(){ $stamp.remove(); }, 800);
    }

    // ------------------------------------------------------------------
    // 10) NO-CTRL MULTISELECT
    // ------------------------------------------------------------------
    // Use native addEventListener to ensure we get the correct event target
    // across all browsers (Safari may not fire delegated mousedown on <option>).
    // Defer the toggle via setTimeout so the browser finishes processing
    // the mousedown before we modify the selection programmatically.
    $selectBox[0].addEventListener('mousedown', function(e){
        var option = e.target;
        if (option.tagName !== 'OPTION' || e.shiftKey) return;
        e.preventDefault();
        var wasSelected = option.selected;
        setTimeout(function(){
            option.selected = !wasSelected;
            $selectBox.trigger('change');
        }, 0);
    }, false);
    $selectBox.on('change', updatePreview);

    // ------------------------------------------------------------------
    // 11) CLEAR SELECTIONS ON RADIO SWITCH
    // ------------------------------------------------------------------
    function clearSelectHighlights() {
        $selectBox.val([]);
        $selectBox.find('option').css({'background-color':'','color':''});
        $addAll.prop('checked', false);
        $removeAll.prop('checked', false);
        renderPreview(workingIds);
        updateActionDescription();
    }
    $radioAdd.on('change', clearSelectHighlights);
    $radioRemove.on('change', clearSelectHighlights);

    // ------------------------------------------------------------------
    // 12) REVOKE ALL BUTTON
    // ------------------------------------------------------------------
    $('#revoke-all-btn').on('click', function(e){
        e.preventDefault();
        if (!workingIds.length) {
            alert(currentMode === 'inventing'
                ? 'No protected content to remove! Everything is already open.'
                : 'No Golden Tickets to revoke! All content already requires login.');
            return;
        }
        var confirmMsg = currentMode === 'inventing'
            ? 'Remove ALL ' + workingIds.length + ' protected items?\n\nThis will open them to everyone.'
            : 'Revoke ALL ' + workingIds.length + ' Golden Tickets?\n\nThis will make ALL content require login.';
        if (!confirm(confirmMsg)) return;

        $selectBox.find('option').each(function(){
            $(this).prop('selected', workingIds.indexOf(parseInt($(this).val(), 10)) !== -1);
        });
        $radioRemove.prop('checked', true);
        updatePreview();
        $addAll.prop('checked', false);
        $removeAll.prop('checked', false);
    });

    // ------------------------------------------------------------------
    // 13) ADD/REMOVE ALL CHECKBOXES
    // ------------------------------------------------------------------
    $addAll.on('change', function(){
        if (this.checked) {
            $removeAll.prop('checked', false);
            $radioAdd.prop('checked', true).trigger('change');
            $selectBox.val(allIds);
            updatePreview();
            updateActionDescription();
        }
    });
    $removeAll.on('change', function(){
        if (this.checked) {
            $addAll.prop('checked', false);
            $radioRemove.prop('checked', true).trigger('change');
            $selectBox.val(allIds);
            updatePreview();
            updateActionDescription();
        }
    });
    $addAllProducts.on('change', function(){
        if (this.checked) $removeAllProducts.prop('checked', false);
    });
    $removeAllProducts.on('change', function(){
        if (this.checked) $addAllProducts.prop('checked', false);
    });

    // ------------------------------------------------------------------
    // 14) CLICK PREVIEW ITEM TO REVOKE
    // ------------------------------------------------------------------
    $previewList.on('click', 'li.preview-item', function(){
        if (!$radioRemove.is(':checked')) return;

        var titleText = $(this).find('.preview-title').text();
        var matchId = null;
        for (var id in titleMap) {
            if (titleMap[id] === titleText) {
                matchId = parseInt(id, 10);
                break;
            }
        }
        if (matchId === null) return;

        $selectBox.find('option[value="' + matchId + '"]').prop('selected', true);

        revokeWithOompaLoompas($(this), function(){
            workingIds = workingIds.filter(function(wid){ return wid !== matchId; });
            previousWorkingCount = workingIds.length;
            renderPreview(workingIds);
        });
    });

    // ------------------------------------------------------------------
    // 15) CONFETTI — Multi-shape golden celebration
    // ------------------------------------------------------------------
    function createConfetti(count) {
        count = count || 60;
        var colors = ['gold','orange','green','purple','chocolate'];
        var shapes = ['confetti-rect','confetti-circle','confetti-ticket','confetti-star'];

        for (var i = 0; i < count; i++) {
            var color    = colors[Math.floor(Math.random() * colors.length)];
            var shape    = shapes[Math.floor(Math.random() * shapes.length)];
            var startX   = Math.random() * window.innerWidth;
            var duration = 2 + Math.random() * 2.5;
            var size     = 5 + Math.floor(Math.random() * 8);

            var $c = $('<div class="confetti ' + color + ' ' + shape + '"></div>').css({
                left:   startX + 'px',
                bottom: '0px',
                width:  size + 'px',
                height: (shape === 'confetti-rect' ? size * 0.4 : size) + 'px',
                animation: 'confettiUp ' + duration + 's ease-out forwards'
            });
            $('body').append($c);
            setTimeout((function(el){ return function(){ el.remove(); }; })($c), duration * 1000 + 500);
        }

        // Bonus: a few floating golden ticket shapes
        for (var t = 0; t < 5; t++) {
            var $ticket = $('<div class="confetti gold confetti-ticket"></div>').css({
                left:   (Math.random() * window.innerWidth) + 'px',
                bottom: '0px',
                width:  '16px', height: '10px',
                animation: 'ticketFloat ' + (3 + Math.random() * 2) + 's ease-out forwards'
            });
            $('body').append($ticket);
            setTimeout((function(el){ return function(){ el.remove(); }; })($ticket), 5500);
        }
    }

    // ------------------------------------------------------------------
    // 16) SUBMIT FORM + SAVE ANIMATION
    // ------------------------------------------------------------------
    $('.golden-save-btn').on('click', function(e){
        e.preventDefault();
        var $btn  = $(this);
        var $form = $btn.closest('form');

        $btn.addClass('loading');
        setTimeout(function(){ createConfetti(100); }, 200);
        setTimeout(function(){ $btn.removeClass('loading').addClass('saving'); }, 600);
        setTimeout(function(){ $form.trigger('submit'); }, 50);
    });

    // ------------------------------------------------------------------
    // 17) SPARKLES — Multi-shape bursts
    // ------------------------------------------------------------------
    function createSparkles(element, count) {
        if (!element) return;
        count = count || 12;
        var rect = element.getBoundingClientRect();
        var cx   = rect.left + rect.width/2;
        var cy   = rect.top  + rect.height/2;
        var shapes = ['sparkle-star','sparkle-circle','sparkle-diamond'];

        for (var i = 0; i < count; i++){
            var shape = shapes[Math.floor(Math.random() * shapes.length)];
            var angle = (360 / count) * i;
            var dist  = 40 + Math.random() * 60;
            var x     = cx + Math.cos(angle * Math.PI/180) * dist;
            var y     = cy + Math.sin(angle * Math.PI/180) * dist;
            var size  = 5 + Math.random() * 10;

            var $sp = $('<div class="sparkle ' + shape + '"></div>').css({
                left: x + 'px', top: y + 'px',
                width: size + 'px', height: size + 'px',
                animationDelay: (i * 0.06) + 's'
            });
            $('body').append($sp);
            setTimeout((function(el){ return function(){ el.remove(); }; })($sp), 2500);
        }
    }

    // ------------------------------------------------------------------
    // 18) AMBIENT PARTICLES — Dust or bubbles depending on mode
    // ------------------------------------------------------------------
    function spawnAmbient() {
        if (document.hidden || !$parentContainer.length) return;
        var rect = $parentContainer[0].getBoundingClientRect();

        if (currentMode === 'inventing') {
            // Iridescent soap bubbles (MR Bubbles!)
            var size = 8 + Math.random() * 16;
            var $bubble = $('<div class="magic-bubble"></div>').css({
                left:   (rect.left + Math.random() * rect.width) + 'px',
                top:    (rect.top + rect.height - 10) + 'px',
                width:  size + 'px',
                height: size + 'px'
            });
            $('body').append($bubble);
            setTimeout(function(){ $bubble.remove(); }, 5000);
        } else {
            // Golden dust (Wonka factory)
            var $dust = $('<div class="golden-dust"></div>').css({
                left: (rect.left + Math.random() * rect.width) + 'px',
                top:  (rect.top + Math.random() * rect.height) + 'px'
            });
            $('body').append($dust);
            setTimeout(function(){ $dust.remove(); }, 4000);
        }
    }
    setInterval(spawnAmbient, 700);

    // ------------------------------------------------------------------
    // 19) LOGO EASTER EGG — Click 5 times for a special animation
    // ------------------------------------------------------------------
    var logoClickCount = 0;
    var logoClickTimer = null;

    $banner.on('click', function(e){
        e.stopPropagation();
        logoClickCount++;

        if (logoClickTimer) clearTimeout(logoClickTimer);
        logoClickTimer = setTimeout(function(){ logoClickCount = 0; }, 2000);

        if (logoClickCount >= 5) {
            logoClickCount = 0;
            // Golden ticket jackpot!
            $banner.addClass('logo-easter-egg');
            setTimeout(function(){ $banner.removeClass('logo-easter-egg'); }, 3000);

            createSparkles($banner[0], 25);
            createConfetti(120);

            // Wonka wisdom toast
            var wisdoms = [
                "We are the music makers, and we are the dreamers of dreams.",
                "So much time and so little to do. Wait a minute. Strike that. Reverse it.",
                "A little nonsense now and then is relished by the wisest men.",
                "There is no life I know to compare with pure imagination.",
                "If you want to view paradise, simply look around and view it.",
                "Invention, my dear friends, is 93% perspiration, 6% electricity, 4% evaporation, and 2% butterscotch ripple."
            ];
            var $toast = $('<div class="oompa-message"></div>')
                .text(wisdoms[Math.floor(Math.random() * wisdoms.length)])
                .css({
                    position: 'fixed', zIndex: 10000,
                    top: '20%', left: '50%',
                    transform: 'translateX(-50%)',
                    fontSize: '14px', padding: '12px 20px',
                    maxWidth: '400px', whiteSpace: 'normal', textAlign: 'center',
                    opacity: 0
                })
                .appendTo('body');
            $toast.animate({ opacity: 1 }, 300);
            setTimeout(function(){
                $toast.animate({ opacity: 0 }, 600, function(){ $(this).remove(); });
            }, 4000);
        }
    });

    // ------------------------------------------------------------------
    // 20) HEADER SPARKLE & AUTO-HIDE SUCCESS
    // ------------------------------------------------------------------
    window.createHeaderSparkles = function() {
        if ($banner[0]) createSparkles($banner[0], 18);
    };

    var $successMsg = $('#success-message');
    if ($successMsg.length) {
        // Success sparkle burst
        setTimeout(function(){
            var msgEl = $successMsg[0];
            if (msgEl) createSparkles(msgEl, 10);
        }, 500);
        setTimeout(function(){
            $successMsg.addClass('success-fade-out');
            setTimeout(function(){ $successMsg.remove(); }, 1000);
        }, 5000);
    }

    // ------------------------------------------------------------------
    // 21) WONKA WISDOM FOOTER
    // ------------------------------------------------------------------
    var wisdomQuotes = [
        "\"A little nonsense now and then is relished by the wisest men.\" \u2014 Willy Wonka",
        "\"We are the music makers, and we are the dreamers of dreams.\" \u2014 Willy Wonka",
        "\"There is no life I know to compare with pure imagination.\" \u2014 Willy Wonka",
        "\"So shines a good deed in a weary world.\" \u2014 Willy Wonka",
        "\"If you want to view paradise, simply look around and view it.\" \u2014 Willy Wonka",
        "\"Invention is 93% perspiration, 6% electricity, 4% evaporation, and 2% butterscotch ripple.\" \u2014 Willy Wonka"
    ];
    var $form = $('#golden-ticket-form');
    if ($form.length) {
        var $wisdom = $('<div class="wonka-wisdom"></div>')
            .text(wisdomQuotes[Math.floor(Math.random() * wisdomQuotes.length)]);
        $form.after($wisdom);

        // Rotate quotes every 15 seconds
        setInterval(function(){
            $wisdom.animate({ opacity: 0 }, 500, function(){
                $(this).text(wisdomQuotes[Math.floor(Math.random() * wisdomQuotes.length)])
                       .animate({ opacity: 0.7 }, 500);
            });
        }, 15000);
    }

    // ------------------------------------------------------------------
    // 22) INITIALIZE
    // ------------------------------------------------------------------
    setModeUI(currentMode);

    // Grand opening sparkle burst
    setTimeout(function(){
        if ($banner[0]) createSparkles($banner[0], 10);
    }, 600);

    console.log(
        '%c\uD83C\uDFAB Golden Ticket v2.2.0 loaded! %cMode: ' + currentMode,
        'color: #FFD700; font-weight: bold; font-size: 14px; text-shadow: 1px 1px 3px #6A5ACD;',
        'color: #32CD32; font-weight: bold; font-size: 12px;'
    );
});
