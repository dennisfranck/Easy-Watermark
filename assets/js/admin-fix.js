jQuery(document).ready(function($){
    var frame;
    
    // --- 3. FORCE OPACITY VISIBILITY ---
    function fixOpacityVisibility() {
        var $imgContainer = $('.watermark-image');
        if (!$imgContainer.length) return;

        var $opacityTable = $imgContainer.find('table.form-table');
        
        // Remove 'hidden' class if present
        $opacityTable.removeClass('hidden');
        $opacityTable.find('*').removeClass('hidden');
        
        $opacityTable.show();
        $opacityTable.css('display', 'table'); 
        
        // Ensure input is visible
        $opacityTable.find('input').show();
        $opacityTable.find('tr').show();
        $opacityTable.find('td').show();
        $opacityTable.find('th').show();
    }

    // Inject inline CSS style to guarantee visibility override globally
    $('<style>.watermark-image table.form-table { display: table !important; } .watermark-image table.form-table tr, .watermark-image table.form-table td, .watermark-image table.form-table th { display: table-cell !important; } .watermark-image table.form-table input { display: inline-block !important; visibility: visible !important; opacity: 1 !important; } .watermark-image .form-field { display: flex !important; visibility: visible !important; opacity: 1 !important; } .watermark-image span.form-field-text { height: 28px !important; line-height: 28px !important; display: inline-block !important; }</style>').appendTo('head');


    // --- 1. TYPE SWITCHING ---
    function toggleWatermarkType() {
        var type = $('input[name="watermark[type]"]:checked').val();
        
        // Safety check
        if (!type) return;

        if ( type === 'text' ) {
            // Show Text
            $('.text-content').show();
            $('#text-options').show();
            $('#placeholders').show();
            
            // Hide Image
            $('.image-content').hide();
            $('#scaling').hide();
        } else if ( type === 'image' ) {
            // Show Image
            $('.image-content').show();
            $('#scaling').show();
            
            // Hide Text
            $('.text-content').hide();
            $('#text-options').hide();
            $('#placeholders').hide();

            // Ensure Opacity is visible
            fixOpacityVisibility();
        }
    }

    // Kill existing handlers on the radio buttons if possible to prevent conflict
    $('input[name="watermark[type]"]').off('change'); 

    // Re-bind using document delegation to ensure it persists
    $(document).on('change', 'input[name="watermark[type]"]', toggleWatermarkType);
    
    // Initial run
    toggleWatermarkType();
    
    // Polling to ensure state (in case other scripts interfere or load late)
    var pollCount = 0;
    var pollInterval = setInterval(function() {
        toggleWatermarkType();
        fixOpacityVisibility(); // Constant check
        pollCount++;
        if (pollCount > 10) clearInterval(pollInterval); // Check for 5 seconds
    }, 500);


    // --- 2. IMAGE UPLOAD HANDLING ---
    function openMediaFrame(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // CRITICAL: Stop other handlers

        if ( frame ) {
            frame.open();
            return;
        }
        
        var button = $(this);
        // Normalize to the link if clicked on wrapper/image
        if ( !button.is('a') ) {
             button = button.closest('.image-content').find('.select-image-button a');
        }
        
        // Last resort fallback
        if ( !button.length ) {
            button = $('.select-image-button a');
        }

        if ( typeof wp === 'undefined' || !wp.media ) {
            console.error('Easy Watermark: WordPress Media Library is not loaded.');
            return;
        }
        
        // Create media frame
        frame = wp.media({
            title: button.data('choose') || 'Choose Watermark Image',
            button: {
                text: button.data('button-label') || 'Set as Watermark Image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // Handle selection
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('.watermark-id').val(attachment.id).trigger('change');
            $('.watermark-url').val(attachment.url).trigger('change');
            $('.watermark-mime-type').val(attachment.mime).trigger('change');
            
            // Force Update Preview
            var $imgContainer = $('.watermark-image');
            var $img = $imgContainer.find('img');
            
            $img.attr('src', attachment.url);
            $img.attr('srcset', ''); // Clear srcset to prevent conflicts
            
            // Force visibility
            $imgContainer.show();
            $imgContainer.css('display', 'block'); // Inline force
            $img.show();
            $img.css('display', 'block'); // Inline force
            
            // Force Opacity Input Visibility
            fixOpacityVisibility();

            // Inject inline CSS style to guarantee visibility override
            $('<style>.watermark-image table.form-table { display: table !important; } .watermark-image table.form-table tr, .watermark-image table.form-table td, .watermark-image table.form-table th { display: table-cell !important; } .watermark-image table.form-table input { display: inline-block !important; visibility: visible !important; opacity: 1 !important; } .watermark-image .form-field { display: flex !important; visibility: visible !important; opacity: 1 !important; } .watermark-image span.form-field-text { height: 28px !important; line-height: 28px !important; display: inline-block !important; }</style>').appendTo('head');

            $('.watermark-content-metabox').addClass('has-image');
            
            // Also update the hidden input explicitly
            $('input[name="watermark[attachment_id]"]').val(attachment.id);
        });
        
        frame.open();
    }

    // Unbind previous handlers
    $('.select-image-button a').off('click'); 
    $('.watermark-image img').off('click');
    $('.watermark-image').off('click');
    
    // Bind new handlers
    $(document).on('click', '.select-image-button a', openMediaFrame);
    $(document).on('click', '.watermark-image img', openMediaFrame);
    $(document).on('click', '.watermark-image', function(e) {
        // Only trigger if clicking the container background, not the image itself (handled above)
        if (e.target === this) {
            openMediaFrame.call(this, e);
        }
    });

    // Final safety: run on window load as well
    $(window).on('load', function() {
        toggleWatermarkType();
    });

    // --- 4. AUTO SAVE AFTER WATERMARK APPLICATION ---
    $(document).ajaxSuccess(function(event, xhr, settings) {
        try {
            var data = settings.data;
            if (!data) return;

            var actionName = '';
            
            if (typeof data === 'string') {
                // Decode to handle %2F and +
                var decoded = decodeURIComponent(data.replace(/\+/g, ' '));
                // Extract action param (simple check)
                if (decoded.indexOf('action=easy-watermark/apply_single') !== -1) {
                    actionName = 'easy-watermark/apply_single';
                } else if (decoded.indexOf('action=easy-watermark/apply_all') !== -1) {
                    actionName = 'easy-watermark/apply_all';
                }
            } else if (typeof data === 'object' && data.action) {
                actionName = data.action;
            }

            if (actionName === 'easy-watermark/apply_single' || actionName === 'easy-watermark/apply_all') {
                // Find the update button (standard WP Edit Media screen)
                // #publish is the standard ID for the update button in post.php
                var $updateBtn = $('#publish');
                
                if (!$updateBtn.length) {
                    $updateBtn = $('input[name="save"]');
                }

                if ($updateBtn.length) {
                    // Trigger click to save/update the media post
                    // Increased timeout to 1000ms to ensure UI is ready
                    setTimeout(function() {
                        $updateBtn.trigger('click');
                    }, 1000);
                }
            }
        } catch (e) {
            console.error('Easy Watermark Auto-Save Error:', e);
        }
    });
});
