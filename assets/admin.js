// File: assets/admin.js
jQuery(function($){
  $('#bc-wcpa-save-prices').on('click', function(){
    const $btn = $(this); const product = $btn.data('product');
    const data = { action: 'bc_wcpa_save_prices', nonce: BCWCPA.nonce, product_id: product, prices: {} };
    $('input[name^="price["]').each(function(){
      const m = this.name.match(/price\[(\d+)\]\[(retail|trader|bulker)\]/); if(!m) return;
      const pin = m[1]; const role = m[2]; const val = $(this).val();
      data.prices[pin] = data.prices[pin] || {}; data.prices[pin][role] = val;
    });
    $btn.prop('disabled', true); $('#bc-wcpa-status').text('Saving...');
    $.post(BCWCPA.ajax, data).done(function(){ $('#bc-wcpa-status').text('Saved'); })
      .fail(function(){ $('#bc-wcpa-status').text('Error'); })
      .always(function(){ $btn.prop('disabled', false); setTimeout(()=>$('#bc-wcpa-status').text(''), 2000); });
  });

   console.log("BC Pricing admin.js loaded ✅");

    // Example: Handle Add Area Form submission (AJAX ready)
    $(document).on("submit", "#bc-area-form", function (e) {
        e.preventDefault();
        let formData = $(this).serialize();

        $.post(ajaxurl, formData, function (response) {
            if (response.success) {
                alert("Area saved successfully!");
                location.reload(); // Reload table after success
            } else {
                alert(response.data || "Something went wrong!");
            }
        });
    });

    // Example: Delete button
    $(document).on("click", ".bc-delete-area", function (e) {
        e.preventDefault();
        if (!confirm("Are you sure you want to delete this?")) return;

        let id = $(this).data("id");
        $.post(ajaxurl, { action: "bc_delete_area", id: id }, function (response) {
            if (response.success) {
                alert("Deleted successfully!");
                location.reload();
            } else {
                alert(response.data || "Error deleting!");
            }
        });
    });

    // Initialize DataTables
    if ($(".bc-datatable").length) {
        $(".bc-datatable").DataTable();
    }

    
});