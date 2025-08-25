<?php
namespace BuyCement\WCAddon\Admin;

use BuyCement\WCAddon\Database\Installer;

class AreaManager {

    public function __construct() {
       add_action('admin_enqueue_scripts',   [$this, 'enqueue_assets']);

        // AJAX
        add_action('wp_ajax_bc_wcpa_areas_list',  [$this, 'ajax_list']);
        add_action('wp_ajax_bc_wcpa_area_add',    [$this, 'ajax_add']);
        add_action('wp_ajax_bc_wcpa_area_edit',   [$this, 'ajax_edit']);
        add_action('wp_ajax_bc_wcpa_area_delete', [$this, 'ajax_delete']);
    }

   
    public function enqueue_assets($hook) {
        if ($hook !== 'woocommerce_page_bc-wcpa-areas') { return; }

        // DataTables from CDN
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], '1.13.6');
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], '1.13.6', true);

        // Minimal admin UI styles
        wp_add_inline_style('datatables', '.bc-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:100000}.bc-modal__card{background:#fff;max-width:520px;margin:10vh auto;padding:20px;border-radius:8px}.bc-flex{display:flex;gap:8px;align-items:center}.bc-right{justify-content:flex-end}');

        // Page script
        wp_register_script('bc-wcpa-areas', '', ['jquery','datatables'], '1.0.0', true);
        wp_add_inline_script('bc-wcpa-areas', $this->page_js());
        wp_localize_script('bc-wcpa-areas', 'BC_WCPA', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bc_wcpa_nonce'),
        ]);
        wp_enqueue_script('bc-wcpa-areas');
    }

    public static function render_table() {
       global $wpdb;
   ?>
         <div class="wrap">

            <h2><?php esc_html_e('Manage Areas', 'buycement-woocommerce-pricing-addon'); ?></h2>
              <div id="bc-area-messages"></div>
            <p><button data-bc-open="bc-area-modal" id="bc-area-add" class="button button-primary"><?php esc_html_e('Add Area', 'buycement-woocommerce-pricing-addon'); ?></button></p>

            <table id="bc-areas" class="display bc-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'buycement-woocommerce-pricing-addon'); ?></th>
                        <th><?php esc_html_e('Name', 'buycement-woocommerce-pricing-addon'); ?></th>
                        <th><?php esc_html_e('Actions', 'buycement-woocommerce-pricing-addon'); ?></th>
                    </tr>
                </thead>
                
            </table>
        </div>

        <!-- Modal -->
        <div class="bc-modal" id="bc-area-modal" aria-hidden="true">
  <div class="bc-modal__overlay" data-bc-close></div>
  <div class="bc-modal__card" role="dialog" aria-modal="true" aria-labelledby="bc-area-modal-title">
    
    <!-- Modal Header -->
    <div class="bc-modal__header">
      <h2 id="bc-area-modal-title"><?php esc_html_e('Add Area', 'buycement-woocommerce-pricing-addon'); ?></h2>
      <button type="button" class="bc-modal__close" data-bc-close aria-label="Close">&times;</button>
    </div>

    <!-- Modal Body -->
    <div class="bc-modal__body">
      <form id="bc-area-form">
        <input id="bc_wcpa_action" type="hidden" name="action" value="bc_wcpa_area_add">
        <input type="hidden" id="bc-area-id" name="id" />

        <div class="bc-flex">
          <label for="bc-area-name"><?php esc_html_e('Name', 'buycement-woocommerce-pricing-addon'); ?></label>
          <input required type="text" name="name" id="bc-area-name" class="regular-text" />
        </div>
      </form>
    </div>

    <!-- Modal Footer -->
    <div class="bc-modal__footer">
      <button type="button" class="button" data-bc-close><?php esc_html_e('Cancel', 'buycement-woocommerce-pricing-addon'); ?></button>
      <button type="submit" form="bc-area-form" class="button button-primary"><?php esc_html_e('Save', 'buycement-woocommerce-pricing-addon'); ?></button>
    </div>
  </div>
</div>

        <?php
    }

    /* ---------- AJAX ---------- */

   

    public function ajax_list() {
        
        global $wpdb;
        $t = Installer::table_name('areas');
        $rows = $wpdb->get_results("SELECT id, name, slug FROM $t ORDER BY name ASC");
        wp_send_json(['data' => $rows ?: []]);
    }

    public function ajax_add() {
       
        global $wpdb;
        $t = Installer::table_name('areas');

        $name = sanitize_text_field($_POST['name'] ?? '');
        if ($name === '') wp_send_json_error(['message' => 'Name required'], 400);
        $slug = sanitize_title($_POST['slug'] ?? $name);

        $ok = $wpdb->insert($t, ['name'=>$name, 'slug'=>$slug], ['%s','%s']);
        if (!$ok) wp_send_json_error(['message'=>'DB insert failed'], 500);

        wp_send_json_success(['id'=>$wpdb->insert_id]);
    }

    public function ajax_edit() {
        global $wpdb;
    $t = Installer::table_name('areas');

    $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = sanitize_title($_POST['slug'] ?: $name);

    if (!$name) {
        wp_send_json_error('Name is required');
    }

    if ($id > 0) {
        // Update existing area
        $wpdb->update($t, ['name' => $name, 'slug' => $slug], ['id' => $id]);
        wp_send_json_success('Area updated successfully!');
    } else {
        // Add new area
        $wpdb->insert($t, ['name' => $name, 'slug' => $slug]);
        wp_send_json_success('Area added successfully!');
    }
    }

    public function ajax_delete() {
        
        global $wpdb;
        $t = Installer::table_name('areas');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error(['message'=>'Bad id'], 400);

        // optional: guard if sub_areas exist
        $sub_t = Installer::table_name('sub_areas');
        $has_children = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $sub_t WHERE area_id=%d", $id));
        if ($has_children) {
            wp_send_json_error(['message'=>'This area has sub areas. Delete/move them first.'], 409);
        }

        $wpdb->delete($t, ['id'=>$id], ['%d']);
        wp_send_json_success();
    }

    private function auth() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message'=>'Unauthorized'], 403);
        check_ajax_referer('bc_wcpa_nonce', 'nonce');
    }

}
