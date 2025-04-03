jQuery(document).ready(function($) {
    'use strict';
    // Initialize Select2 for product selection
    $('#ix-wpb-selected-products').select2({
        ajax: {
            url: ix_wpb_manager_form.ajax_url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    action: 'ix_wpb_manager_search_products',
                    nonce: ix_wpb_manager_form.nonce
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
                            text: product.text
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: ix_wpb_manager_form.i18n.select_products,
        minimumInputLength: 2,
        width: '100%'
    });
    
    // Initialize category select2
    $('.ix-wpb-category-select').select2({
        ajax: {
            url: ix_wpb_manager_form.ajax_url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    action: 'ix_wpb_manager_search_categories',
                    nonce: ix_wpb_manager_form.nonce
                };
            },
            processResults: function(data) {
                if (data.success) {
                    return {
                        results: data.data
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        templateResult: function(item) {
            return item.display || item.text;
        },
        minimumInputLength: 0,
        placeholder: ix_wpb_manager_form.i18n.select_categories
    });
    
    // Trigger initial load of all categories when dropdown opens
    $('.ix-wpb-category-select').on('select2:open', function() {
        if ($(this).find('option').length <= 1) {
            $(this).select2('trigger', 'select2:open');
        }
    });

    // Handle form submission
$('#ix-wpb-manager-form').on('submit', function(e) {
    e.preventDefault();
    
    var $form = $(this);
    var $message = $('.ix-wpb-form-message');
    var $submit = $form.find('button[type="submit"]');
    
    $message.removeClass('success error').html('');
    $submit.prop('disabled', true).text(ix_wpb_manager_form.i18n.saving);
    
    $.ajax({
        url: ix_wpb_manager_form.ajax_url,
        type: 'POST',
        data: {
            action: 'ix_wpb_manager_save_settings',
            nonce: ix_wpb_manager_form.nonce,
            image_source: $form.find('#ix-wpb-image-source').val(),
            image_size: $form.find('#ix-wpb-image-size').val(),                
            selected_products: $form.find('#ix-wpb-selected-products').val() || [],
            selected_categories: $form.find('#ix-wpb-selected-categories').val() || [],
            columns: $form.find('#ix-wpb-columns').val() || 4,
            limit: $form.find('#ix-wpb-limit').val() || 12,
            full_image: $form.find('#ix-wpb-full-image').is(':checked') ? 'yes' : 'no',
            show_title: $form.find('#ix-wpb-show-title').is(':checked') ? 'yes' : 'no',
            show_price: $form.find('#ix-wpb-show-price').is(':checked') ? 'yes' : 'no',
            show_button: $form.find('#ix-wpb-show-button').is(':checked') ? 'yes' : 'no',
            grid_style: $form.find('#ix-wpb-grid-style').val() || 'grid'
        },
        success: function(response) {
            if (response.success) {
                $message.addClass('success').text(response.data);
            } else {
                $message.addClass('error').text(response.data);
            }
        },
        error: function(xhr, status, error) {
            $message.addClass('error').text(ix_wpb_manager_form.i18n.error);
            console.error(error);
        },
        complete: function() {
            $submit.prop('disabled', false).text(ix_wpb_manager_form.i18n.save);
        }
     });
   });
});