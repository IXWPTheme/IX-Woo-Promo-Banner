jQuery(document).ready(function($) {
    const $builder = $('.wpb-grid-builder');
    const $preview = $('#wpb-grid-preview');
    const $shortcodeOutput = $('#wpb-shortcode-output');
    const $copyButton = $('#wpb-copy-shortcode');
    let previewTimeout;

    // Initialize Select2 for product selection
    $('#wpb-products').select2({
        ajax: {
            url: wpbGridEditor.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    action: 'woocommerce_json_search_products',
                    security: wpbGridEditor.nonce,
                    term: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: Object.keys(data).map(id => ({
                        id: id,
                        text: data[id]
                    }))
                };
            },
            cache: true
        },
        placeholder: wpbGridEditor.i18n.select_products,
        width: '100%',
        minimumInputLength: 2
    });

    // Update preview when controls change
    $builder.on('change input', '.wpb-control', function() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(updatePreview, 500);
    });

    // Copy shortcode button
    $copyButton.on('click', function() {
        $shortcodeOutput.select();
        document.execCommand('copy');
        
        const $this = $(this);
        $this.text(wpbGridEditor.i18n.copied);
        setTimeout(() => {
            $this.text(wpbGridEditor.i18n.copy_shortcode);
        }, 2000);
    });

    // Initial update
    updatePreview();

    function updatePreview() {
        const formData = {};
        
        // Gather all control values
        $builder.find('[data-attribute]').each(function() {
            const $control = $(this);
            const attr = $control.data('attribute');
            
            if ($control.is(':checkbox')) {
                formData[attr] = $control.is(':checked') ? 'yes' : 'no';
            } else if ($control.is('select[multiple]')) {
                formData[attr] = $control.val() ? $control.val().join(',') : '';
            } else {
                formData[attr] = $control.val();
            }
        });

        // Show loading state
        $preview.addClass('loading');
        $preview.html('<div class="wpb-loading">' + wpbGridEditor.i18n.generating_preview + '</div>');

        // AJAX request
        $.ajax({
            url: wpbGridEditor.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpb_generate_grid_preview',
                nonce: wpbGridEditor.nonce,
                form_data: $.param(formData)
            },
            success: function(response) {
                if (response.success) {
                    $shortcodeOutput.val(response.data.shortcode);
                    $preview.html(response.data.preview);
                }
            },
            complete: function() {
                $preview.removeClass('loading');
            }
        });
    }
});