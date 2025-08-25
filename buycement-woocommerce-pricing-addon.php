<?php
/**
* Plugin Name: BuyCement WooCommerce Pricing Add-on
* Description: Per-pincode pricing by user-type (Retail/Trader/Bulker) with area → subarea → pincode hierarchy.
* Version: 1.0.0
* Author: Your Name
* Text Domain: buycement-woocommerce-pricing-addon
* Requires Plugins: woocommerce
*/


// File: buycement-woocommerce-pricing-addon.php


if ( ! defined('ABSPATH') ) { exit; }


// Composer autoload
$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
require_once $autoload;
} else {
// Fallback simple autoloader for dev without composer
spl_autoload_register(function($class){
if (strpos($class, 'BuyCement\\WCAddon') !== 0) return;
$path = __DIR__ . '/src/' . str_replace(['BuyCement\\WCAddon\\', '\\'], ['', '/'], $class) . '.php';
if ( file_exists($path) ) require_once $path;
});
}


use BuyCement\WCAddon\Core\Plugin;


add_action('plugins_loaded', function(){
if ( ! class_exists('WooCommerce') ) return; // require WooCommerce
Plugin::instance();
});



register_activation_hook(__FILE__, function(){
BuyCement\WCAddon\Database\Installer::activate();
});


register_deactivation_hook(__FILE__, function(){
// keep data by default, add cleanup if desired
});