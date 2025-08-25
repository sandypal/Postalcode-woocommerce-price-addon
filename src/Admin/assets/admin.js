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

  // Model Js Code
jQuery(document).ready(function ($) {
  function openModal(modalId) {
    $('#' + modalId).addClass('is-active').attr('aria-hidden', 'false');
  }

  function closeModal(modal) {
    $(modal).removeClass('is-active').attr('aria-hidden', 'true');
  }

  // Open modal triggers (example: add button with data-bc-open="bc-area-modal")
  $(document).on('click', '[data-bc-open]', function (e) {
    e.preventDefault();
    const modalId = $(this).data('bc-open');
    openModal(modalId);
  });

  // Close modal (overlay or button with data-bc-close)
  $(document).on('click', '[data-bc-close]', function (e) {
    e.preventDefault();
    closeModal($(this).closest('.bc-modal'));
  });

  // Escape key support
  $(document).on('keydown', function (e) {
    if (e.key === "Escape") {
      $('.bc-modal.is-active').each(function () {
        closeModal(this);
      });
    }
  });
});

  //End Model Js Code

   console.log("BC Pricing admin.js loaded ✅");

   var table = $('#bc-areas').DataTable({
        ajax: ajaxurl + '?action=bc_wcpa_areas_list',
        columns: [
            { data: 'id' },
            { data: 'name' },
            {
                data: null,
                render: function (data, type, row) {
        return `<a href="#" class="bc-area-edit" data-id="${row.id}" data-name="${row.name}" data-slug="${row.slug}">Edit</a>  <a href="#" class="bc-delete-area" data-id="${row.id}">Delete</a>`;
    }
            }
        ]
    });

    // Example: Handle Add Area Form submission (AJAX ready)
    $(document).on("submit", "#bc-area-form", function (e) {
    e.preventDefault();
    let formData = $(this).serialize();

    $.post(ajaxurl, formData, function (response) {
      let $messages = $("#bc-area-messages");
      $messages.empty(); // clear previous messages

      if (response.success) {
        $messages.append(
          '<div class="notice notice-success is-dismissible"><p>' +
            ("Area Added successfully!") +
          "</p></div>"
        );

        // refresh datatable (if you use DataTables)
         table.ajax.reload(null, false);
       $('#bc-area-modal').removeClass('is-active');
        // OR fallback: reload page after short delay
        //setTimeout(() => location.reload(), 1500);

      } else {
        $messages.append(
          '<div class="notice notice-error is-dismissible"><p>' +
            (response.data || "Something went wrong!") +
          "</p></div>"
        );
      }
    });
  });

  $(document).on('click', '.bc-area-edit', function (e) {
        e.preventDefault();

        var id   = $(this).data('id');
        var name = $(this).data('name');

        // Populate modal fields
        $('#bc-area-id').val(id);
        $('#bc-area-name').val(name);
        $('#bc_wcpa_action').val('bc_wcpa_area_edit');

        // Change modal title
        $('#bc-area-modal-title').text('Edit Area');

        // Open modal
        $('#bc-area-modal').addClass('is-active').attr('aria-hidden', 'false');
    });

    // Reset modal when adding a new area
    $(document).on('click', '#bc-area-add', function () {
        $('#bc-area-id').val('');
        $('#bc-area-name').val('');
        $('#bc_wcpa_action').val('bc_wcpa_area_add');
        $('#bc-area-modal-title').text('Add Area');
    });



    // Example: Delete button
    $(document).on("click", ".bc-delete-area", function (e) {
        e.preventDefault();
        if (!confirm("Are you sure you want to delete this?")) return;

        let id = $(this).data("id");
        $.post(ajaxurl, { action: "bc_wcpa_area_delete", id: id }, function (response) {
            let $messages = $("#bc-area-messages");
      $messages.empty(); // clear previous messages

      if (response.success) {
        $messages.append(
          '<div class="notice notice-success is-dismissible"><p>' +
            ("Area Deleted successfully!") +
          "</p></div>"
        );

        // refresh datatable (if you use DataTables)
         table.ajax.reload(null, false);
       $('#bc-area-modal').removeClass('is-active');
        // OR fallback: reload page after short delay
        //setTimeout(() => location.reload(), 1500);

      } else {
        $messages.append(
          '<div class="notice notice-error is-dismissible"><p>' +
            (response.data || "Something went wrong!") +
          "</p></div>"
        );
      }
        });
    });

    

    // Initialize DataTables
    if ($(".bc-datatable").length) {
        $(".bc-datatable").DataTable();
    }

//Sub Area code Start
jQuery(function($){
    nonce = BC_WCPA.nonce;
    const $modal = $('#bc-subarea-modal');
    const $title = $('#bc-subarea-modal-title');
    const $id    = $('#bc-subarea-id');
    const $area  = $('#bc-subarea-area');
    const $name  = $('#bc-subarea-name');
    
    const $subareamessages = $("#bc-subarea-messages");

    // Function to load areas in dropdown
    function loadAreas(selectVal){
        return $.post(ajaxurl, { action:'bc_wcpa_area_options', nonce }).done(res=>{
            $area.empty();
            (res.data?.options || []).forEach(o=>{
                $area.append('<option value="'+o.id+'">'+o.name+'</option>');
            });
            if (selectVal) $area.val(String(selectVal));
        });
    }

    // Initialize DataTable
    const table = $('#bc-subareas').DataTable({
        ajax: { url: ajaxurl, type: 'POST', data: { action:'bc_wcpa_subareas_list', nonce } },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'area_name' },
            {
                data: null, orderable:false, render: row => `
                    <button class="button bc-subarea-edit"
                        data-id="${row.id}" data-name="${row.name}" data-slug="${row.slug}" data-area="${row.area_id}">Edit</button>
                    <button class="button bc-subarea-del" data-id="${row.id}">Delete</button>`
            }
        ]
    });

    // Add Sub Area
    $('#bc-subarea-add').on('click', function(){
        $id.val('');
        $name.val('');
        $title.text('Add Sub Area');
        loadAreas().then(()=>$modal.show());
    });

    // Edit Sub Area
    $(document).on('click', '.bc-subarea-edit', function(){
        $id.val($(this).data('id'));
        $name.val($(this).data('name'));
        const areaId = $(this).data('area');
        $title.text('Edit Sub Area');
        loadAreas(areaId).then(()=>$modal.show());
    });

    // Cancel modal
    $('#bc-subarea-cancel').on('click', ()=>$modal.hide());

    // Save Sub Area (Add/Edit)
    $('#bc-subarea-save').on('click', function(){
        const id = $id.val();
        $subareamessages.empty();
        let displayMesg = 'Sub Area Added Sucessfully';
        const data = {
            nonce, name: $name.val(), area_id: $area.val()
        };
        if (id) { data.action='bc_wcpa_subarea_edit'; data.id=id; displayMesg = 'Sub Area Updated Sucessfully'}
        else    { data.action='bc_wcpa_subarea_add'; }

        $.post(ajaxurl, data).done(()=>{
            table.ajax.reload();
            $modal.hide();
            $subareamessages.append(
                '<div class="notice notice-success is-dismissible"><p>'+displayMesg+'</p></div>'
            );

            // Auto-hide after 3 seconds (3000 ms)
            $subareamessages.find('.notice').last().delay(3000).fadeOut(500, function(){
                $(this).remove();
            });
        }).fail(res=>{
            alert(res.responseJSON?.data?.message || 'Error');
        });
    });

    // Delete Sub Area
    $(document).on('click', '.bc-subarea-del', function(){
    if (!confirm('Delete this sub area?')) return;

     // container for notices
    $subareamessages.empty(); // clear previous messages

    $.post(ajaxurl, { action:'bc_wcpa_subarea_delete', nonce, id: $(this).data('id') })
      .done(()=> {
          // Reload DataTable
          table.ajax.reload();

          // Show success notice
          const $notice = $('<div class="notice notice-success is-dismissible"><p>Sub Area deleted successfully!</p></div>');
          $subareamessages.append($notice);

          // Auto-hide after 3 seconds
          $notice.delay(3000).fadeOut(500, function(){ $(this).remove(); });
      })
      .fail(res=> {
          const message = res.responseJSON?.data?.message || 'Error deleting sub area';
          const $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
          $subareamessages.append($notice);
          $notice.delay(5000).fadeOut(500, function(){ $(this).remove(); });
      });
});

});

//Sub Area code end

    // Postal Code Start
jQuery(function($){
  // ----- Postal Block -----
  const ajaxPostal = BC_WCPA.ajax,
        noncePostal = BC_WCPA.nonce;

  const $postalModal = $('#bc-postal-modal'),
        $postalTitle = $('#bc-postal-modal-title'),
        $postalId    = $('#bc-postal-id'),
        $postalArea  = $('#bc-postal-area'),
        $postalSub   = $('#bc-postal-subarea'),
        $postalCode  = $('#bc-postal-code');

  // Load areas into dropdown
  function loadPostalAreas(selectVal){
    return $.post(ajaxPostal, { action:'bc_wcpa_area_options', nonce: noncePostal })
      .done(res => {
        $postalArea.empty();
        (res.data?.options || []).forEach(o => $postalArea.append('<option value="'+o.id+'">'+o.name+'</option>'));
        if(selectVal) $postalArea.val(String(selectVal));
      });
  }

  // Load subareas based on selected area
  function loadPostalSubAreas(areaId, selectVal){
    return $.post(ajaxPostal, { action:'bc_wcpa_subarea_options', nonce: noncePostal, area_id: areaId })
      .done(res => {
        $postalSub.empty();
        (res.data?.options || []).forEach(o => $postalSub.append('<option value="'+o.id+'">'+o.name+'</option>'));
        if(selectVal) $postalSub.val(String(selectVal));
      });
  }

  // Initialize DataTable
  const postalTable = $('#bc-postalcodes').DataTable({
    ajax: { url: ajaxPostal, type: 'POST', data: { action:'bc_wcpa_postalcodes_list', nonce: noncePostal } },
    columns: [
      { data: 'id' },
      { data: 'pincode' },
      { data: 'area_name' },
      { data: 'sub_area_name' },
      {
        data: null,
        orderable: false,
        render: function(row){
          return `
            <button class="button bc-postal-edit"
              data-id="${row.id}"
              data-postal="${row.pincode}"
              data-area="${row.area_id}"
              data-sub="${row.sub_area_id}">Edit</button>
            <button class="button bc-postal-del" data-id="${row.id}">Delete</button>
          `;
        }
      }
    ]
  });

  // Add new Postal
  $('#bc-postal-add').on('click', function(){
    $postalId.val('');
    $postalCode.val('');
    $postalTitle.text('Add Postal');
    loadPostalAreas().then(()=>loadPostalSubAreas($postalArea.val()).then(()=> $postalModal.show()));
  });

  // Update subareas when area changes
  $postalArea.on('change', function(){
    loadPostalSubAreas($(this).val());
  });

  // Edit Postal
  $(document).on('click', '.bc-postal-edit', function(){
    $postalId.val($(this).data('id'));
    $postalCode.val($(this).data('postal'));
    const areaId = $(this).data('area');
    const subId  = $(this).data('sub');
    $postalTitle.text('Edit Postal');
    loadPostalAreas(areaId).then(()=> loadPostalSubAreas(areaId, subId)).then(()=> $postalModal.show());
  });

  // Cancel modal
  $('#bc-postal-cancel').on('click', function(){
    $postalModal.hide();
  });

  // Save Postal (Add/Edit)
  $('#bc-postal-save').on('click', function(){
    const id = $postalId.val();
    const data = {
      nonce: noncePostal,
      pincode: $postalCode.val().replace(/[^0-9]/g,''),
      sub_area_id: $postalSub.val()
    };

    if(id){
      data.action = 'bc_wcpa_postalcode_edit';
      data.id = id;
    } else {
      data.action = 'bc_wcpa_postalcode_add';
    }

    $.post(ajaxPostal, data)
      .done(function(){
        postalTable.ajax.reload();
        $postalModal.hide();
      })
      .fail(function(res){
        alert(res.responseJSON?.data?.message || 'Error');
      });
  });

  // Delete Postal
  $(document).on('click', '.bc-postal-del', function(){
    if(!confirm('Delete this postal?')) return;

    $.post(ajaxPostal, { action:'bc_wcpa_postal_delete', nonce: noncePostal, id: $(this).data('id') })
      .done(function(){
        postalTable.ajax.reload();
      })
      .fail(function(res){
        alert(res.responseJSON?.data?.message || 'Error');
      });
  });
});
// Postal code End

});