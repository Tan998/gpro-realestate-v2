/* global confirm, redux, redux_change */
jQuery(document).ready(function($) {
    var container = $('#custom_fields_wrapper');
    
    function updateOrderInputs() {
        container.find('.field_row').each(function(index){
            // Update both custom and ACF field order inputs
            $(this).find('input[name*="[add_field_order]"], input[name*="[acf_field_order]"]').val(index + 1);
        });
    }
    
    container.sortable({
        items: '.field_row',
        placeholder: 'sortable-placeholder',
        update: function(){
            updateOrderInputs();
        }
    });
    
    container.on('change', 'input[name*="[add_field_order]"], input[name*="[acf_field_order]"]', function(){
        var rows = container.children('.field_row').get();
        rows.sort(function(a,b){
            var orderInputA = $(a).find('input[name*="[add_field_order]"], input[name*="[acf_field_order]"]');
            var orderInputB = $(b).find('input[name*="[acf_field_order]"], input[name*="[add_field_order]"]');
            var valA = parseInt(orderInputA.val(), 10) || 0;
            var valB = parseInt(orderInputB.val(), 10) || 0;
            return valA - valB;
        });
        container.append(rows);
        updateOrderInputs();
    });
});