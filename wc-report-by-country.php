<?php
	include_once( WP_PLUGIN_DIR . '/woocommerce/includes/admin/reports/class-wc-admin-report.php');
	
	if ( !class_exists( 'GoWCCustomReports' ) ) {
		class GoWCCustomReports {

			function activate() {
				add_filter( 'woocommerce_admin_reports', array($this,'my_custom_woocommerce_admin_reports'), 10, 1 );
			}

			function my_custom_woocommerce_admin_reports( $reports ) {
				$sales_by_country = array(
					'sales_by_country' => array(
						'title'         => 'Sales by Country',
						'description'   => '',
						'hide_title'    => true,
						'callback'      => array($this,'sales_by_country_callback'),
					),
				);
				// This can be: orders, customers, stock or taxes, based on where we want to insert our new reports page
                if(is_array($reports['orders']['reports'])) {
	                $reports[ 'orders' ][ 'reports' ] = array_merge( $reports[ 'orders' ][ 'reports' ], $sales_by_country );
                }
				return $reports;
			}

			function sales_by_country_callback() {
				$report = new WC_Report_Sales_By_Country();
				$report->output_report();
			}
		}

		$lgpd_woocommerce_custom_reports = new GoWCCustomReports();
		$lgpd_woocommerce_custom_reports->activate();
	}

	class WC_Report_Sales_By_Country extends WC_Admin_Report {

		/**
		 * Output the report.
		 */
		public function output_report() {
			$ranges = array(
				'year'         => __( 'Year', 'woocommerce' ),
				'last_month'   => __( 'Last month', 'woocommerce' ),
				'month'        => __( 'This month', 'woocommerce' ),
			);
			$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'month';
			if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', '7day' ) ) ) {
				$current_range = 'month';
			}
			$this->check_current_range_nonce( $current_range );
			$this->calculate_current_range( $current_range );
			$hide_sidebar = true;
			include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
		}

		/**
		 * Get the main chart.
		 */
		public function get_main_chart() {
			global $wpdb;

			$query_data = array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders',
					'distinct' => true,
				),
				'_billing_country' => array(
					'type'      => 'meta',
					'function'  => '',
					'name'      => 'country'
				),
				'_order_total'   => array(
					'type'      => 'meta',
					'function'  => 'SUM',
					'name'      => 'order_total'
				),
			);

			$sales_by_country_orders = $this->get_order_report_data( array(
				'data'                  => $query_data,
				'query_type'            => 'get_results',
				'group_by'              => 'country',
				'filter_range'          => true,
				'order_types'           => wc_get_order_types( 'sales-reports' ),
				'order_status'          => array( 'completed' ),
				'parent_order_status'   => false,
			) );
			?>
			<table class="widefat">
				<thead>
				<tr>
					<th><strong>Country</strong></th>
					<th><strong>Sales</strong></th>
					<th><strong>Total Sales</strong></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach( $sales_by_country_orders as $order ) {
					?>
					<tr>
						<td><?php echo WC()->countries->countries[$order->country]; ?></td>
						<td><?php echo $order->total_orders; ?></td>
						<td><?php echo wc_price($order->order_total); ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php

		}
	}