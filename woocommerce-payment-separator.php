<?php
/**
 * Plugin Name: WooCommerce Ödeme Yöntemine Göre Kazanç Raporu
 * Plugin URI: #
 * Description: Ödeme yönteminize göre kazanç raporunuzu oluşturun.
 * Version: 1.0.5
 * Author: SWRNET
 * Author URI: #
 * Text Domain: woocommerce-odeme-yontemine-gore-kazanç-raporu
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('WooCommerce_Payment_Separator')) {

class WooCommerce_Payment_Separator {

           public function __construct() {
            add_action('admin_menu', array($this, 'wc_payment_separator_add_reports_page'));
        }

        public function wc_payment_separator_add_reports_page() {
            add_submenu_page('woocommerce', __('Ödeme Yöntemine Göre Kazanç Raporu', 'woocommerce-payment-separator'), __('Ödeme Yöntemine Göre Kazanç Raporu', 'woocommerce-payment-separator'), 'manage_woocommerce', 'wc-payment-method-earnings', array($this, 'wc_payment_separator_earnings_callback_with_dates'));
        }

        public function wc_payment_separator_show_date_filter() {
            echo '<form method="get" action="">';
            echo '<input type="hidden" name="page" value="wc-payment-method-earnings" />';
            echo '<label for="from_date">' . __('Başlangıç Tarihi:', 'woocommerce-payment-separator') . '</label>';
            echo '<input type="date" name="from_date" value="' . (isset($_GET['from_date']) ? $_GET['from_date'] : '') . '">';
            echo '<label for="to_date">' . __('Bitiş Tarihi:', 'woocommerce-payment-separator') . '</label>';
            echo '<input type="date" name="to_date" value="' . (isset($_GET['to_date']) ? $_GET['to_date'] : '') . '">';
            echo '<input type="submit" class="button" value="' . __('Filtrele', 'woocommerce-payment-separator') . '">';
            echo '</form>';
        }


        public function wc_payment_separator_earnings_callback_with_dates() {
            global $wpdb;

/*            $this->wc_payment_separator_show_date_filter(); Bu kısım fazla oldu  ne olur ne olmaz silmeyelim*/

            $payment_methods = WC()->payment_gateways->payment_gateways();
            $earnings = [];
            $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
            $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

            foreach ($payment_methods as $method_id => $method) {
                $date_query = '';

                if ($from_date || $to_date) {
    $date_query .= ' AND p.post_id IN (
        SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = "shop_order"
        AND post_status NOT IN ("wc-cancelled", "trash")';

    if ($from_date) {
        $date_query .= $wpdb->prepare(' AND post_date >= %s', $from_date);
    }
    if ($to_date) {
        $date_query .= $wpdb->prepare(' AND post_date <= %s', $to_date);
    }

    $date_query .= ')';
            }

            $total = $wpdb->get_var($wpdb->prepare("
    SELECT SUM(meta_value)
    FROM {$wpdb->postmeta} p
    WHERE meta_key = '_order_total'
    AND post_id IN (
        SELECT post_id
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_payment_method'
        AND meta_value = %s
    )" . $date_query
, $method_id));


            $earnings[$method_id] = !empty($total) ? $total : 0;
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Ödeme Yöntemine Göre Kazanç Raporu', 'woocommerce-payment-separator') . '</h1>';

         $this->wc_payment_separator_show_date_filter(); 

		echo '<style>.payment-separator-table { margin-top: 20px; }</style>';
        echo '<table class="widefat fixed striped payment-separator-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Ödeme Yöntemleri', 'woocommerce-payment-separator') . '</th>';
        echo '<th>' . __('Kazanç', 'woocommerce-payment-separator') . '</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        foreach ($earnings as $method_id => $earning) {
            echo '<tr>';
            echo '<td>' . $payment_methods[$method_id]->get_title() . '</td>';
            echo '<td>' . wc_price($earning) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';

        echo '<tfoot>';
        echo '<tr>';
        echo '<th>' . __('Ödeme Yöntemleri', 'woocommerce-payment-separator') . '</th>';
        echo '<th>' . __('Kazanç', 'woocommerce-payment-separator') . '</th>';
        echo '</tr>';
        echo '</tfoot>';

        echo '</table>';
        echo '</div>';
    }
}
}
new WooCommerce_Payment_Separator();