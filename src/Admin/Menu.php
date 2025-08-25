<?php
namespace BuyCement\WCAddon\Admin;

use BuyCement\WCAddon\Admin\AreaManager;
use BuyCement\WCAddon\Admin\SubAreaManager;
use BuyCement\WCAddon\Admin\PincodeManager;

class Menu {
public function __construct() {
add_action('admin_menu', [$this, 'register']);
 add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
}
public function register() {
add_menu_page(
__('BC Pricing', 'buycement-woocommerce-pricing-addon'),
__('BC Pricing', 'buycement-woocommerce-pricing-addon'),
'manage_woocommerce',
'bc-wcpa',
[$this,'render_main'],
'dashicons-admin-site',
56
);

}
 public static function enqueue_assets($hook) {
    // Only load on our plugin page
    //echo $hook;
    if ($hook !== 'toplevel_page_bc-wcpa') {
        return;
    }

    // DataTables CSS/JS (from CDN or local)
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
    wp_enqueue_style(
        'bc-admin-css',
        plugin_dir_url(__DIR__ . '/../') . 'assets/admin.css',
        [],
        rand(1,9999)
    );
    // Our custom JS
    wp_enqueue_script(
        'bc-admin-js',
        plugin_dir_url(__DIR__ . '/../') . 'assets/admin.js', // relative to plugin root
        ['jquery'],
        rand(1,9999), // cache-busting
        true
    );
    wp_localize_script('bc-admin-js', 'BC_WCPA', [
    'ajax'  => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('bc_wcpa_nonce') 
]);
}

public function render_main(){
 $active_tab = $_GET['tab'] ?? 'area';
        ?>
        <div class="wrap">
            <h1>BC Pricing Manager</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=bc-wcpa&tab=area" class="nav-tab <?php echo $active_tab == 'area' ? 'nav-tab-active' : ''; ?>">Area</a>
                <a href="?page=bc-wcpa&tab=subarea" class="nav-tab <?php echo $active_tab == 'subarea' ? 'nav-tab-active' : ''; ?>">Sub Area</a>
                <a href="?page=bc-wcpa&tab=pincode" class="nav-tab <?php echo $active_tab == 'pincode' ? 'nav-tab-active' : ''; ?>">Pincode</a>
            </h2>
        <?php
        switch ($active_tab) {
            case 'subarea':
                SubAreaManager::render_table();
                break;
            case 'pincode':
                PincodeManager::render_table();
                break;
            default:
                AreaManager::render_table();
        }
        echo '</div>';
    }
}