<?php
namespace BuyCement\WCAddon\Database;

class Installer {
    const DB_VERSION = '1.0.0';

    /**
     * Run on plugin activation
     */
    public static function activate() {
        self::create_tables();
        update_option('bc_wcpa_db_version', self::DB_VERSION);
    }

    /**
     * Run if DB schema needs update
     */
    public static function maybe_update() {
        $version = get_option('bc_wcpa_db_version');
        if ($version !== self::DB_VERSION) {
            self::create_tables();
            update_option('bc_wcpa_db_version', self::DB_VERSION);
        }
    }

    /**
     * Generate prefixed table name
     */
    public static function table_name(string $key): string {
        global $wpdb;
        return $wpdb->prefix . 'bc_wcpa_' . $key;
    }

    /**
     * Create plugin tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $areas     = self::table_name('areas');
        $sub_areas = self::table_name('sub_areas');
        $pincodes  = self::table_name('pincodes');
        $prices    = self::table_name('prices');

        // Main Areas
        $sql_areas = "CREATE TABLE $areas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            parent_id BIGINT UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY slug (slug),
            KEY parent (parent_id)
        ) $charset;";

        // Sub Areas
        $sql_sub_areas = "CREATE TABLE $sub_areas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            area_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            PRIMARY KEY (id),
            KEY area (area_id),
            KEY slug (slug)
        ) $charset;";

        // Pincodes
        $sql_pincodes = "CREATE TABLE $pincodes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sub_area_id BIGINT UNSIGNED NOT NULL,
            pincode VARCHAR(12) NOT NULL,
            UNIQUE KEY sub_pin (sub_area_id,pincode),
            PRIMARY KEY (id),
            KEY sub_area (sub_area_id)
        ) $charset;";

        // Prices (per product, per pincode, per role)
        $sql_prices = "CREATE TABLE $prices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            pincode_id BIGINT UNSIGNED NOT NULL,
            role ENUM('retail','trader','bulker') NOT NULL,
            price DECIMAL(18,4) NOT NULL,
            UNIQUE KEY uniq_prod_pin_role (product_id,pincode_id,role),
            KEY product (product_id),
            PRIMARY KEY (id)
        ) $charset;";

        // Run queries
        dbDelta($sql_areas);
        dbDelta($sql_sub_areas);
        dbDelta($sql_pincodes);
        dbDelta($sql_prices);
    }
}
