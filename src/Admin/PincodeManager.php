<?php
namespace BuyCement\WCAddon\Admin;

use BuyCement\WCAddon\Database\Installer;

class PincodeManager {

    public function __construct() {
        
      

        add_action('wp_ajax_bc_wcpa_postalcodes_list',  [$this, 'ajax_list']);
        add_action('wp_ajax_bc_wcpa_postalcode_add',    [$this, 'ajax_add']);
        add_action('wp_ajax_bc_wcpa_postalcode_edit',   [$this, 'ajax_edit']);
        add_action('wp_ajax_bc_wcpa_postalcode_delete', [$this, 'ajax_delete']);

        // Load dependent dropdowns
        add_action('wp_ajax_bc_wcpa_area_options',    [$this, 'ajax_area_options']);
        add_action('wp_ajax_bc_wcpa_subarea_options', [$this, 'ajax_subarea_options']);
    }

        public static  function render_table() {
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('Manage Postalcodes', 'buycement-woocommerce-pricing-addon'); ?></h1>
          <div id="bc-postalcode-messages"></div>
          <p><button id="bc-postal-add" class="button button-primary"><?php esc_html_e('Add Postalcode', 'buycement-woocommerce-pricing-addon'); ?></button></p>

          <table id="bc-postalcodes" class="display" style="width:100%">
            <thead>
              <tr>
                <th><?php esc_html_e('ID', 'buycement-woocommerce-pricing-addon'); ?></th>
                <th><?php esc_html_e('Postal Code', 'buycement-woocommerce-pricing-addon'); ?></th>
                <th><?php esc_html_e('Area', 'buycement-woocommerce-pricing-addon'); ?></th>
                <th><?php esc_html_e('Sub Area', 'buycement-woocommerce-pricing-addon'); ?></th>
                <th><?php esc_html_e('Actions', 'buycement-woocommerce-pricing-addon'); ?></th>
              </tr>
            </thead>
          </table>
        </div>

        <div class="bc-modal" id="bc-postal-modal" aria-hidden="true">
          <div class="bc-modal__card">
            <h2 id="bc-postal-modal-title"></h2>
            <div>
              <div class="bc-flex">
                <label style="min-width:120px;"><?php esc_html_e('Area', 'buycement-woocommerce-pricing-addon'); ?></label>
                <select required id="bc-postal-area"></select>
              </div>
              <div class="bc-flex" style="margin-top:8px;">
                <label style="min-width:120px;"><?php esc_html_e('Sub Area', 'buycement-woocommerce-pricing-addon'); ?></label>
                <select required id="bc-postal-subarea"></select>
              </div>
              <div class="bc-flex" style="margin-top:8px;">
                <label style="min-width:120px;"><?php esc_html_e('Postalcode', 'buycement-woocommerce-pricing-addon'); ?></label>
                <input required type="text" id="bc-postal-code" class="regular-text" />
              </div>
              <input type="hidden" id="bc-postal-id" />
            </div>
            <div class="bc-flex bc-right" style="margin-top:16px;">
              <button class="button" id="bc-postal-cancel"><?php esc_html_e('Cancel', 'buycement-woocommerce-pricing-addon'); ?></button>
              <button class="button button-primary" id="bc-postal-save"><?php esc_html_e('Save', 'buycement-woocommerce-pricing-addon'); ?></button>
            </div>
          </div>
        </div>
        <?php
    }

    /* ---------- AJAX ---------- */

    public function ajax_area_options() {
        $this->auth();
        global $wpdb;
        $a_t = Installer::table_name('areas');
        $rows = $wpdb->get_results("SELECT id, name FROM $a_t ORDER BY name ASC");
        wp_send_json_success(['options'=>$rows ?: []]);
    }

    public function ajax_subarea_options() {
        $this->auth();
        global $wpdb;
        $sub_t = Installer::table_name('sub_areas');
        $area_id = (int)($_POST['area_id'] ?? 0);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM $sub_t WHERE area_id=%d ORDER BY name ASC", $area_id));
        wp_send_json_success(['options'=>$rows ?: []]);
    }

    public function ajax_list() {
        $this->auth();
        global $wpdb;
        $p_t  = Installer::table_name('pincodes');
        $s_t  = Installer::table_name('sub_areas');
        $a_t  = Installer::table_name('areas');

        $rows = $wpdb->get_results("SELECT p.id, p.pincode, s.id AS sub_area_id, s.name AS sub_area_name, a.id AS area_id, a.name AS area_name
                                    FROM $p_t p
                                    LEFT JOIN $s_t s ON s.id=p.sub_area_id
                                    LEFT JOIN $a_t a ON a.id=s.area_id
                                    ORDER BY a.name ASC, s.name ASC, p.pincode ASC");
        wp_send_json(['data' => $rows ?: []]);
    }

    public function ajax_add() {
        $this->auth();
        global $wpdb;
        $p_t = Installer::table_name('pincodes');

        $sub_area_id = (int)($_POST['sub_area_id'] ?? 0);
        $postalcode     = preg_replace('/[^0-9]/', '', (string)($_POST['pincode'] ?? ''));
        if (!$sub_area_id || $postalcode==='') wp_send_json_error(['message'=>'Sub area and postalcode required'], 400);

        $ok = $wpdb->insert($p_t, ['sub_area_id'=>$sub_area_id, 'pincode'=>$postalcode], ['%d','%s']);
        if (!$ok) wp_send_json_error(['message'=>'DB insert failed (maybe duplicate?)'], 500);

        wp_send_json_success(['id'=>$wpdb->insert_id]);
    }

    public function ajax_edit() {
        $this->auth();
        global $wpdb;
        $p_t = Installer::table_name('pincodes');

        $id          = (int)($_POST['id'] ?? 0);
        $sub_area_id = (int)($_POST['sub_area_id'] ?? 0);
        $postalcode     = preg_replace('/[^0-9]/', '', (string)($_POST['pincode'] ?? ''));
        if (!$id || !$sub_area_id || $postalcode==='') wp_send_json_error(['message'=>'Bad data'], 400);

        $wpdb->update($p_t, ['sub_area_id'=>$sub_area_id, 'pincode'=>$postalcode], ['id'=>$id], ['%d','%s'], ['%d']);
        wp_send_json_success();
    }

    public function ajax_delete() {
        $this->auth();
        global $wpdb;
        $p_t = Installer::table_name('postalcodes');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error(['message'=>'Bad id'], 400);

        $wpdb->delete($p_t, ['id'=>$id], ['%d']);
        wp_send_json_success();
    }

    private function auth() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message'=>'Unauthorized'], 403);
        check_ajax_referer('bc_wcpa_nonce', 'nonce');
    }

    
}
