<?php
namespace BuyCement\WCAddon\Admin;

use BuyCement\WCAddon\Database\Installer;

class SubAreaManager {

    public function __construct() {
        
       

        add_action('wp_ajax_bc_wcpa_subareas_list',  [$this, 'ajax_list']);
        add_action('wp_ajax_bc_wcpa_subarea_add',    [$this, 'ajax_add']);
        add_action('wp_ajax_bc_wcpa_subarea_edit',   [$this, 'ajax_edit']);
        add_action('wp_ajax_bc_wcpa_subarea_delete', [$this, 'ajax_delete']);
        add_action('wp_ajax_bc_wcpa_area_options',   [$this, 'ajax_area_options']); // for select dropdown
    }

   

    public static function render_table() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Manage Sub Areas', 'buycement-woocommerce-pricing-addon'); ?></h1>
            <div id="bc-subarea-messages"></div>
            <p><button data-bc-open="bc-subarea-modal" id="bc-subarea-add" class="button button-primary"><?php esc_html_e('Add Sub Area', 'buycement-woocommerce-pricing-addon'); ?></button></p>

            <table id="bc-subareas" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'buycement-woocommerce-pricing-addon'); ?></th>
                        <th><?php esc_html_e('Sub Area', 'buycement-woocommerce-pricing-addon'); ?></th>
                        <th><?php esc_html_e('Parent Area', 'buycement-woocommerce-pricing-addon'); ?></th>
                        <th><?php esc_html_e('Actions', 'buycement-woocommerce-pricing-addon'); ?></th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Modal -->
        <div class="bc-modal" id="bc-subarea-modal" aria-hidden="true">
          <div class="bc-modal__overlay" data-bc-close></div>
            <div class="bc-modal__card" role="dialog" aria-modal="true" aria-labelledby="bc-area-modal-title">
            <!-- Modal Header -->
    <div class="bc-modal__header">
      <h2 id="bc-subarea-modal-title"><?php esc_html_e('Add Sub Area', 'buycement-woocommerce-pricing-addon'); ?></h2>
      <button type="button" class="bc-modal__close" data-bc-close aria-label="Close">&times;</button>
    </div>
            <div class="bc-modal__body">
      <form id="bc-subarea-form">
              <div class="bc-flex">
                <label style="min-width:120px;"><?php esc_html_e('Parent Area', 'buycement-woocommerce-pricing-addon'); ?></label>
                <select required id="bc-subarea-area"></select>
              </div>
              <div class="bc-flex" style="margin-top:8px;">
                <label style="min-width:120px;"><?php esc_html_e('Sub Area Name', 'buycement-woocommerce-pricing-addon'); ?></label>
                <input required type="text" id="bc-subarea-name" class="regular-text" />
              </div>
              <input type="hidden" id="bc-subarea-id" />
      </form>
            </div>
           <div class="bc-modal__footer">
              <button class="button" id="bc-subarea-cancel"><?php esc_html_e('Cancel', 'buycement-woocommerce-pricing-addon'); ?></button>
              <button type="button" form="bc-subarea-form" class="button button-primary" id="bc-subarea-save"><?php esc_html_e('Save', 'buycement-woocommerce-pricing-addon'); ?></button>
            </div>
          </div>
        </div>
        <?php
    }

    /* ---------- AJAX ---------- */

    public function ajax_area_options() {
        $this->auth();
        global $wpdb;
        $t = Installer::table_name('areas');
        $rows = $wpdb->get_results("SELECT id, name FROM $t ORDER BY name ASC");
        wp_send_json_success(['options' => $rows ?: []]);
    }

    public function ajax_list() {
        $this->auth();
        global $wpdb;
        $sub_t = Installer::table_name('sub_areas');
        $a_t   = Installer::table_name('areas');
        $rows  = $wpdb->get_results("SELECT s.id, s.name, s.slug, s.area_id, a.name AS area_name
                                     FROM $sub_t s
                                     LEFT JOIN $a_t a ON a.id=s.area_id
                                     ORDER BY a.name ASC, s.name ASC");
        wp_send_json(['data' => $rows ?: []]);
    }

    public function ajax_add() {
        $this->auth();
        global $wpdb;
        $sub_t = Installer::table_name('sub_areas');

        $area_id = (int)($_POST['area_id'] ?? 0);
        $name    = sanitize_text_field($_POST['name'] ?? '');
        if (!$area_id || $name==='') wp_send_json_error(['message'=>'Area and name required'], 400);
        $slug = sanitize_title($_POST['slug'] ?? $name);

        $ok = $wpdb->insert($sub_t, ['area_id'=>$area_id,'name'=>$name,'slug'=>$slug], ['%d','%s','%s']);
        if (!$ok) wp_send_json_error(['message'=>'DB insert failed'], 500);

        wp_send_json_success(['id'=>$wpdb->insert_id]);
    }

    public function ajax_edit() {
        $this->auth();
        global $wpdb;
        $sub_t = Installer::table_name('sub_areas');

        $id      = (int)($_POST['id'] ?? 0);
        $area_id = (int)($_POST['area_id'] ?? 0);
        $name    = sanitize_text_field($_POST['name'] ?? '');
        $slug    = sanitize_title($_POST['slug'] ?? $name);
        if (!$id || !$area_id || $name==='') wp_send_json_error(['message'=>'Bad data'], 400);

        $wpdb->update($sub_t, ['area_id'=>$area_id,'name'=>$name,'slug'=>$slug], ['id'=>$id], ['%d','%s','%s'], ['%d']);
        wp_send_json_success();
    }

    public function ajax_delete() {
        $this->auth();
        global $wpdb;
        $sub_t = Installer::table_name('sub_areas');
        $pin_t = Installer::table_name('pincodes');

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error(['message'=>'Bad id'], 400);

        $has_pins = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $pin_t WHERE sub_area_id=%d", $id));
        if ($has_pins) wp_send_json_error(['message'=>'This sub area has pincodes. Delete/move them first.'], 409);

        $wpdb->delete($sub_t, ['id'=>$id], ['%d']);
        wp_send_json_success();
    }

    private function auth() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message'=>'Unauthorized'], 403);
        check_ajax_referer('bc_wcpa_nonce', 'nonce');
    }

  }
