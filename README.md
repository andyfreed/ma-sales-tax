# MA Sales Tax

A WordPress plugin for tracking and exporting Massachusetts sales tax data by quarter and year.

## Description

MA Sales Tax is a simple yet powerful WordPress plugin that helps you track sales tax collected from Massachusetts customers through WooCommerce. Perfect for quarterly tax reporting and record-keeping.

## Features

- **Quarter & Year Selection**: Easily select any quarter and year to view sales data
- **Sales Summary**: View total orders, total sales, and total tax collected for Massachusetts
- **Detailed Order List**: See individual order details including date, amounts, and city
- **CSV Export**: Download complete sales data in CSV format for your records or accounting software
- **WooCommerce Integration**: Works seamlessly with your existing WooCommerce store

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- WooCommerce plugin installed and active

## Installation

### From GitHub

1. Download the plugin files from this repository
2. Upload the `ma-sales-tax` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to the 'MA Sales Tax' menu item in your WordPress admin sidebar

### Manual Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/andyfreed/ma-sales-tax.git
   ```
2. Upload the folder to your WordPress plugins directory
3. Activate the plugin in WordPress

## Usage

1. After activation, you'll find a new menu item called "MA Sales Tax" in your WordPress admin sidebar
2. Select the desired quarter (Q1, Q2, Q3, or Q4) and year
3. Click "View Report" to see the sales summary
4. Review the summary showing:
   - Total number of orders from Massachusetts
   - Total sales amount
   - Total tax collected
5. View detailed order information in the table below
6. Click "Download CSV" to export the data for your records

## What Data is Included?

The plugin tracks all WooCommerce orders with:
- Billing state set to "MA" (Massachusetts)
- Order status of "Completed" or "Processing"
- Order date within the selected quarter

Each report includes:
- Order ID (with link to order details)
- Order date
- Subtotal
- Tax amount
- Total amount
- Billing city

## CSV Export Format

The exported CSV file includes:
- Individual order details (Order ID, Date, Subtotal, Tax, Total, City, State)
- Summary totals row at the bottom
- UTF-8 encoding for Excel compatibility

## Screenshots

The plugin provides:
- Clean, intuitive admin interface
- Quarter and year selection dropdowns
- Summary statistics in an easy-to-read format
- Detailed order table
- One-click CSV export

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/andyfreed/ma-sales-tax).

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Quarter and year selection
- Sales summary display
- Detailed order listing
- CSV export functionality
- WooCommerce integration

## Author

Andy Freed

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

