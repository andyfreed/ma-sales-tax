<?php
/**
 * Plugin Name: MA Sales Tax
 * Plugin URI: https://github.com/andyfreed/ma-sales-tax
 * Description: Track and export Massachusetts sales tax data by quarter and year
 * Version: 1.0.0
 * Author: Andy Freed
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ma-sales-tax
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MA_Sales_Tax {
    
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_csv_export'));
        add_action('admin_notices', array($this, 'check_woocommerce'));
    }
    
    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('MA Sales Tax requires WooCommerce to be installed and active.', 'ma-sales-tax'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            __('MA Sales Tax', 'ma-sales-tax'),
            __('MA Sales Tax', 'ma-sales-tax'),
            'manage_woocommerce',
            'ma-sales-tax',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            56
        );
    }
    
    /**
     * Get date range for quarter
     */
    private function get_quarter_date_range($quarter, $year) {
        $quarters = array(
            1 => array('01-01', '03-31'),
            2 => array('04-01', '06-30'),
            3 => array('07-01', '09-30'),
            4 => array('10-01', '12-31')
        );
        
        if (!isset($quarters[$quarter])) {
            return false;
        }
        
        return array(
            'start' => $year . '-' . $quarters[$quarter][0] . ' 00:00:00',
            'end' => $year . '-' . $quarters[$quarter][1] . ' 23:59:59'
        );
    }
    
    /**
     * Get MA sales data
     */
    private function get_ma_sales_data($quarter, $year) {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $date_range = $this->get_quarter_date_range($quarter, $year);
        if (!$date_range) {
            return array();
        }
        
        global $wpdb;
        
        // Query orders with MA billing state
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('wc-completed', 'wc-processing'),
            'date_created' => $date_range['start'] . '...' . $date_range['end'],
            'billing_state' => 'MA',
            'return' => 'ids'
        ));
        
        $sales_data = array();
        $total_sales = 0;
        $total_tax = 0;
        
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                continue;
            }
            
            $order_total = $order->get_total();
            $order_tax = $order->get_total_tax();
            
            $sales_data[] = array(
                'order_id' => $order_id,
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'subtotal' => $order->get_subtotal(),
                'tax' => $order_tax,
                'total' => $order_total,
                'billing_city' => $order->get_billing_city(),
                'billing_state' => $order->get_billing_state(),
            );
            
            $total_sales += $order_total;
            $total_tax += $order_tax;
        }
        
        return array(
            'orders' => $sales_data,
            'total_sales' => $total_sales,
            'total_tax' => $total_tax,
            'order_count' => count($sales_data)
        );
    }
    
    /**
     * Handle CSV export
     */
    public function handle_csv_export() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'export_ma_sales_csv') {
            return;
        }
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'export_ma_sales_csv')) {
            wp_die(__('Security check failed', 'ma-sales-tax'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'ma-sales-tax'));
        }
        
        $quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 1;
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        $data = $this->get_ma_sales_data($quarter, $year);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ma-sales-tax-q' . $quarter . '-' . $year . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            'Order ID',
            'Date',
            'Subtotal',
            'Tax',
            'Total',
            'City',
            'State'
        ));
        
        // Add data rows
        foreach ($data['orders'] as $order) {
            fputcsv($output, array(
                $order['order_id'],
                $order['date'],
                number_format($order['subtotal'], 2, '.', ''),
                number_format($order['tax'], 2, '.', ''),
                number_format($order['total'], 2, '.', ''),
                $order['billing_city'],
                $order['billing_state']
            ));
        }
        
        // Add summary row
        fputcsv($output, array());
        fputcsv($output, array(
            'TOTAL',
            $data['order_count'] . ' orders',
            '',
            number_format($data['total_tax'], 2, '.', ''),
            number_format($data['total_sales'], 2, '.', ''),
            '',
            ''
        ));
        
        fclose($output);
        exit;
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'ma-sales-tax'));
        }
        
        $current_quarter = ceil(date('n') / 3);
        $current_year = date('Y');
        
        $quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : $current_quarter;
        $year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
        
        $data = array();
        if (class_exists('WooCommerce')) {
            $data = $this->get_ma_sales_data($quarter, $year);
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('MA Sales Tax Report', 'ma-sales-tax'); ?></h1>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Select Period', 'ma-sales-tax'); ?></h2>
                
                <form method="get" action="">
                    <input type="hidden" name="page" value="ma-sales-tax">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="quarter"><?php _e('Quarter', 'ma-sales-tax'); ?></label>
                            </th>
                            <td>
                                <select name="quarter" id="quarter">
                                    <option value="1" <?php selected($quarter, 1); ?>>Q1 (Jan - Mar)</option>
                                    <option value="2" <?php selected($quarter, 2); ?>>Q2 (Apr - Jun)</option>
                                    <option value="3" <?php selected($quarter, 3); ?>>Q3 (Jul - Sep)</option>
                                    <option value="4" <?php selected($quarter, 4); ?>>Q4 (Oct - Dec)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="year"><?php _e('Year', 'ma-sales-tax'); ?></label>
                            </th>
                            <td>
                                <select name="year" id="year">
                                    <?php
                                    for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                        echo '<option value="' . $y . '"' . selected($year, $y, false) . '>' . $y . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('View Report', 'ma-sales-tax'), 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <?php if (!empty($data['orders'])): ?>
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php printf(__('Q%d %d - Massachusetts Sales Summary', 'ma-sales-tax'), $quarter, $year); ?></h2>
                    
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Metric', 'ma-sales-tax'); ?></th>
                                <th style="text-align: right;"><?php _e('Value', 'ma-sales-tax'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Total Orders', 'ma-sales-tax'); ?></strong></td>
                                <td style="text-align: right;"><?php echo number_format($data['order_count']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Total Sales', 'ma-sales-tax'); ?></strong></td>
                                <td style="text-align: right;">$<?php echo number_format($data['total_sales'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Total Tax Collected', 'ma-sales-tax'); ?></strong></td>
                                <td style="text-align: right;">$<?php echo number_format($data['total_tax'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p style="margin-top: 20px;">
                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'export_ma_sales_csv', 'quarter' => $quarter, 'year' => $year), admin_url('admin.php')), 'export_ma_sales_csv'); ?>" class="button button-primary">
                            <?php _e('Download CSV', 'ma-sales-tax'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Order Details', 'ma-sales-tax'); ?></h2>
                    
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Order ID', 'ma-sales-tax'); ?></th>
                                <th><?php _e('Date', 'ma-sales-tax'); ?></th>
                                <th style="text-align: right;"><?php _e('Subtotal', 'ma-sales-tax'); ?></th>
                                <th style="text-align: right;"><?php _e('Tax', 'ma-sales-tax'); ?></th>
                                <th style="text-align: right;"><?php _e('Total', 'ma-sales-tax'); ?></th>
                                <th><?php _e('City', 'ma-sales-tax'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['orders'] as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('post.php?post=' . $order['order_id'] . '&action=edit'); ?>">
                                            #<?php echo $order['order_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['date'])); ?></td>
                                    <td style="text-align: right;">$<?php echo number_format($order['subtotal'], 2); ?></td>
                                    <td style="text-align: right;">$<?php echo number_format($order['tax'], 2); ?></td>
                                    <td style="text-align: right;">$<?php echo number_format($order['total'], 2); ?></td>
                                    <td><?php echo esc_html($order['billing_city']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (class_exists('WooCommerce')): ?>
                <div class="notice notice-info inline" style="margin-top: 20px; max-width: 800px;">
                    <p><?php _e('No orders found for the selected period.', 'ma-sales-tax'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the plugin
new MA_Sales_Tax();

