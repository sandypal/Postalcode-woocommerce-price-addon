<?php
namespace BuyCement\WCAddon\Frontend;

use BuyCement\WCAddon\Database\Installer;

class PricingDisplay {
    public function __construct() {
        add_filter('woocommerce_product_get_price', [$this,'filter_price'], 20, 2);
        add_filter('woocommerce_product_get_regular_price', [$this,'filter_price'], 20, 2);
        add_filter('woocommerce_get_price_html', [$this,'maybe_note'], 20, 2);
    }

    public static function get_user_tier(): string {
        // Map WP roles to pricing tiers; adjust as needed
        $user = wp_get_current_user();
        $map = [
            'retail' => ['customer','subscriber'],
            'trader' => ['trader','shop_manager'],
            'bulker' => ['bulker']
        ];
        foreach($map as $tier => $roles){ if (array_intersect($roles, $user->roles)) return $tier; }
        return 'retail'; // guests/customers default
    }

    public function filter_price( $price, $product ){
        $pin = PincodeSelector::get_selected_pincode(); if ( ! $pin ) return $price;
        $tier = self::get_user_tier();
        global $wpdb; $pins_t = Installer::table_name('pincodes'); $prices_t = Installer::table_name('prices');
        $pincode_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $pins_t WHERE pincode=%s", $pin) );
        if ( ! $pincode_id ) return $price;
        $custom = $wpdb->get_var( $wpdb->prepare("SELECT price FROM $prices_t WHERE product_id=%d AND pincode_id=%d AND role=%s", $product->get_id(), $pincode_id, $tier) );
        if ( $custom !== null ) return $custom;
        return $price;
    }

    public function maybe_note( $html, $product ){
        $pin = PincodeSelector::get_selected_pincode(); if (!$pin) return $html;
        $tier = self::get_user_tier();
        return $html . sprintf('<small class="price-note" style="display:block;opacity:.7;">Pricing for pincode %s (%s)</small>', esc_html($pin), esc_html(ucfirst($tier)) );
    }
}