<?php
namespace BuyCement\WCAddon\Core;


use BuyCement\WCAddon\Database\Installer;
use BuyCement\WCAddon\Admin\Menu;
use BuyCement\WCAddon\Admin\AreaManager;
use BuyCement\WCAddon\Admin\SubAreaManager;
use BuyCement\WCAddon\Admin\PincodeManager;
use BuyCement\WCAddon\Admin\ProductPricing;
use BuyCement\WCAddon\Frontend\PincodeSelector;
use BuyCement\WCAddon\Frontend\PricingDisplay;


class Plugin {
private static ?Plugin $instance = null;


public static function instance(): Plugin {
if (!self::$instance) { self::$instance = new self(); }
return self::$instance;
}


private function __construct() {
// Ensure tables
add_action('init', [Installer::class, 'maybe_update']);


if ( is_admin() ) {
new Menu();
new AreaManager();
 new SubAreaManager();
    new PincodeManager();
new ProductPricing();
}


new PincodeSelector();
new PricingDisplay();
}


/** Helper: get plugin url */
public static function url( string $path = '' ): string {
return plugins_url( $path, dirname(__DIR__, 2) . '/buycement-woocommerce-pricing-addon.php' );
}


/** Helper: get plugin dir */
public static function dir( string $path = '' ): string {
return plugin_dir_path( dirname(__DIR__, 2) . '/buycement-woocommerce-pricing-addon.php' ) . ltrim($path, '/');
}
}