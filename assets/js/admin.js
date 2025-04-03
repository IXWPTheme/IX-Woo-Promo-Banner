/*IX Woo Pro Banner Settings Promotional Products*/
(function($) {
    'use strict';

    $(document).ready(function() {
        const $productSelect = $('#ix_wpb_selected_products');
        
        $productSelect.select2({			
            ajax: {
                url: ix_wpb_admin.ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        action: 'ix_wpb_search_products',
                        nonce: ix_wpb_admin.nonce
                    };
                },
                processResults: function(data) {
                    if (!data.success) {
                        console.error('AJAX error:', data.data);
                        return { results: [] };
                    }
                    
                    return {
                        results: data.data.map(function(product) {
                            return {
                                id: product.id,
                                text: product.text,
                                display: product.display
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: ix_wpb_admin.i18n.search_placeholder,
            minimumInputLength: 2,			
            escapeMarkup: function(markup) {
                return markup;
            },
            templateResult: function(product) {
                if (product.loading) {
                    return ix_wpb_admin.i18n.loading;
                }
                var $container = $('<div class="ix-wpb-product-result"></div>');
                $container.html(product.display || product.text);
                return $container;
            },
            templateSelection: function(product) {
                return product.text ? (product.text + ' (ID: ' + product.id + ')') : '';
            },
            width: '50%', // Changed to 50% width             
    		dropdownAutoWidth: false,
            dropdownCssClass: 'select2-container'
        });

        // Optional: Adjust width on resize
        $(window).on('resize', function() {
            $productSelect.select2('width', '50%');
        });
    });

})(jQuery);