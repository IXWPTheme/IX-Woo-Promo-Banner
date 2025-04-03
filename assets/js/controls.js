jQuery(document).ready(function($) {
    const $panel = $('.wpb-control-panel');
    const $preview = $('#wpb-grid-preview');
    const $applyBtn = $('#wpb-apply-changes');

    // Initial load
    updateGridPreview();

    $applyBtn.on('click', updateGridPreview);
    $('.wpb-control').on('change', function() {
        $applyBtn.prop('disabled', false);
    });

    function updateGridPreview() {
        $applyBtn.prop('disabled', true).text(wpbControls.i18n.updating);
        
        $.ajax({
            url: wpbControls.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpb_update_grid',
                nonce: wpbControls.nonce,
                columns: $('#wpb-columns').val(),
                limit: $('#wpb-limit').val(),
                show_title: $('#wpb-show-title').is(':checked'),
                show_price: $('#wpb-show-price').is(':checked')
            },
            success: function(response) {
                if (response.success) {
                    $preview.html(response.data.html);
                    $applyBtn.text(wpbControls.i18n.apply_changes);
                } else {
                    alert(response.data);
                    $applyBtn.prop('disabled', false);
                }
            },
            error: function() {
                alert(wpbControls.i18n.error_occurred);
                $applyBtn.prop('disabled', false);
            }
        });
    }
});