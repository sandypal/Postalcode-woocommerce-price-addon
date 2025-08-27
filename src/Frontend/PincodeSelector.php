<?php
namespace BuyCement\WCAddon\Frontend;

use BuyCement\WCAddon\Database\Installer;

class PincodeSelector {
    public function __construct() {
        add_shortcode('bc_pincode_selector', [$this,'shortcode']);
        add_action('init', [$this,'maybe_handle_submit']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'inject_on_product']);
        add_action( 'wp_enqueue_scripts',[$this,'frontend_enqueue_scripts']);
    }

    public function frontend_enqueue_scripts(){
        if ( is_product() ) {
                wp_enqueue_style(
                    'my-frontend-style', 
                    plugins_url('/assets/frontend.css', dirname(__DIR__,2) . '/buycement-woocommerce-pricing-addon.php'),
                    [], 
                    '1.0.0'
                );

               
            }
    }

    public static function get_selected_pincode(): ?string {
        if ( isset($_POST['bc_wpa_pincode']) ) return preg_replace('/[^0-9]/','', $_POST['bc_wpa_pincode']);
        if ( isset($_GET['bc_wpa_pin']) ) return preg_replace('/[^0-9]/','', $_GET['bc_wpa_pin']);
        if ( function_exists('WC') && WC()->session ) return WC()->session->get('bc_wpa_pin');
        return isset($_COOKIE['bc_wpa_pin']) ? preg_replace('/[^0-9]/','', $_COOKIE['bc_wpa_pin']) : null;
    }

    public function maybe_handle_submit(){
        if ( isset($_POST['bc_wpa_pincode']) ) {
            $pin = preg_replace('/[^0-9]/','', $_POST['bc_wpa_pincode']);
            if ( function_exists('WC') && WC()->session ) WC()->session->set('bc_wpa_pin', $pin);
            setcookie('bc_wpa_pin', $pin, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
            if ( wp_get_referer() ) wp_safe_redirect( wp_get_referer() );
        }
    }

    public function shortcode(){
        return $this->render_form();
    }

    public function inject_on_product(){
        echo '<div class="bc-wpa-inline-selector">' . $this->render_form() . '</div>';
    }

    private function render_form(): string {
        global $wpdb; $pins_t = Installer::table_name('pincodes');
        $selected = self::get_selected_pincode();
        $options = $wpdb->get_col("SELECT DISTINCT pincode FROM $pins_t ORDER BY pincode");
        ob_start(); ?>
        <form method="post" class="bc-wpa-pin-form" style="margin:10px 0;">
             <div class="bc-pincode-row">
           <div class="bc-pincode-label">
                <label for="bc_pincode_input">Use pincode to check product price</label>
            </div>
            <div class="pincode_form_inline">
              <div class="bc-pincode-input">
                <img class="pin_marker" 
     src="<?php echo esc_url( plugins_url( 'assets/img/location-marker.png', dirname(__DIR__, 2) . '/buycement-woocommerce-pricing-add-on.php' ) ); ?>" 
     alt="Location Marker">

          <input list="bc-wpa-pins" id="bc_wpa_pincode" name="bc_wpa_pincode" value="<?php echo esc_attr($selected); ?>" placeholder="Enter a pin code" />
          <!-- <datalist id="bc-wpa-pins">
            < ?php foreach($options as $op){ echo '<option value="'.esc_attr($op).'">'; } ?>
          </datalist> -->
          </div>
          
          <button type="submit" class="button">Check</button>
          </div>
        <p id="pin_val" class="pin_valid text_small"></p>
          </div>
        </form>
        <?php return ob_get_clean();
    }
}