<?php
namespace BuyCement\WCAddon\Admin;

use BuyCement\WCAddon\Database\Installer;

class ProductPricing {
    public function __construct() {
        add_action('add_meta_boxes', [$this,'box']);
        add_action('admin_enqueue_scripts', [$this,'assets']);
        add_action('wp_ajax_bc_wcpa_save_prices', [$this,'ajax_save']);
    }

    public function box(){
        add_meta_box('bc-wcpa-pricing', 'Per-Pincode Pricing (Retail/Trader/Bulker)', [$this,'render'], 'product', 'normal', 'default');
    }

    public function assets($hook){
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
        wp_enqueue_script('bc-wcpa-admin', plugins_url('/assets/admin.js', dirname(__DIR__,2) . '/buycement-woocommerce-pricing-addon.php'), ['jquery'], '1.0.0', true);
        wp_localize_script('bc-wcpa-admin', 'BCWCPA', [
            'nonce' => wp_create_nonce('bc_wcpa_save'),
            'ajax'  => admin_url('admin-ajax.php')
        ]);
        wp_enqueue_style('bc-wcpa-admin', plugins_url('/assets/admin.css', dirname(__DIR__,2) . '/buycement-woocommerce-pricing-addon.php'), [], '1.0.0');
    }

    public function render( $post ){
        global $wpdb; $pins_t = Installer::table_name('pincodes'); $areas_t = Installer::table_name('areas'); $prices_t = Installer::table_name('prices');
        $pincodes = $wpdb->get_results("SELECT p.id,p.pincode,a.name as area FROM $pins_t p LEFT JOIN $areas_t a ON a.id=p.area_id ORDER BY a.name, p.pincode");
        $prices = $wpdb->get_results( $wpdb->prepare("SELECT pincode_id, role, price FROM $prices_t WHERE product_id=%d", $post->ID ), OBJECT_K );
        echo '<p>Set specific prices for each pincode and user role. Leave blank to use default product price.</p>';
        echo '<table class="widefat fixed"><thead><tr><th>Pincode</th><th>Retail</th><th>Trader</th><th>Bulker</th></tr></thead><tbody>';
        foreach($pincodes as $p){
            $r = $this->get_price_for($prices, $p->id, 'retail');
            $t = $this->get_price_for($prices, $p->id, 'trader');
            $b = $this->get_price_for($prices, $p->id, 'bulker');
            echo '<tr>';
            echo '<td>'.esc_html($p->pincode).'</td>';
            echo '<td><input type="number" step="0.01" name="price['.intval($p->id).'][retail]" value="'.esc_attr($r).'" /></td>';
            echo '<td><input type="number" step="0.01" name="price['.intval($p->id).'][trader]" value="'.esc_attr($t).'" /></td>';
            echo '<td><input type="number" step="0.01" name="price['.intval($p->id).'][bulker]" value="'.esc_attr($b).'" /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p><button type="button" class="button button-primary" id="bc-wcpa-save-prices" data-product="'.intval($post->ID).'">Save Pricing</button> <span id="bc-wcpa-status"></span></p>';
    }

    private function get_price_for($prices, $pincode_id, $role){
        foreach($prices as $obj){
            if ($obj->pincode_id == $pincode_id && $obj->role === $role) return $obj->price;
        }
        return '';
    }

    public function ajax_save(){
        if ( ! current_user_can('edit_products') || ! wp_verify_nonce($_POST['nonce'] ?? '', 'bc_wcpa_save') ) wp_send_json_error('unauthorized', 403);
        global $wpdb; $prices_t = Installer::table_name('prices');
        $product_id = (int)($_POST['product_id'] ?? 0);
        $prices = $_POST['prices'] ?? [];
        if (!$product_id) wp_send_json_error('missing product');

        foreach($prices as $pinId => $roles){
            $pinId = (int)$pinId; if ($pinId <= 0) continue;
            foreach(['retail','trader','bulker'] as $role){
                $val = isset($roles[$role]) && $roles[$role] !== '' ? wc_format_decimal($roles[$role]) : null;
                // delete if empty
                if ($val === null) {
                    $wpdb->delete($prices_t, ['product_id'=>$product_id,'pincode_id'=>$pinId,'role'=>$role], ['%d','%d','%s']);
                } else {
                    // upsert
                    $exists = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $prices_t WHERE product_id=%d AND pincode_id=%d AND role=%s", $product_id, $pinId, $role) );
                    if ($exists) {
                        $wpdb->update($prices_t, ['price'=>$val], ['id'=>$exists], ['%f'], ['%d']);
                    } else {
                        $wpdb->insert($prices_t, ['product_id'=>$product_id,'pincode_id'=>$pinId,'role'=>$role,'price'=>$val], ['%d','%d','%s','%f']);
                    }
                }
            }
        }
        wp_send_json_success(['ok'=>true]);
    }
}