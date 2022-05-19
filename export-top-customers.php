<?php
/**
* Plugin Name: Export Top Customers
* Description: Queries all customer on your site, and exports a csv file of the chosen number of customers, organized by the total amount they have spent 
* Version: 1.0.0
* Author: Ben HartLenn
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: export_top_customers
*/

// Helper function to export top customers array to a temporary csv file available for download
function export_top_customers_csv($etc_top_customers) {
	ob_clean();
	ob_start();
	
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename=top-" . count($etc_top_customers) . "-customers.csv" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
		
	$fp = fopen('php://output', 'w');
	
	// insert headings like original points csv export file
	fputcsv($fp, ['User Email', 'Total Spent']);
	
	foreach($etc_top_customers as $etc_top_customer) {
		
		fputcsv($fp, $etc_top_customer);
		
	}
	
	fclose($fp);
	
	ob_end_flush();
	
	exit();
}

// Add menu item and admin page
add_action("admin_menu", "etc_plugin_menu");
function etc_plugin_menu() {

	add_submenu_page(
		"tools.php",
	   	"Export Top Customers", 
		"Export Top Customers", 
	   	"manage_options", 
		"export-top-customers",
		"etc_plugin_page"
	);

}

// load plugin stylesheet
add_action( 'admin_enqueue_scripts', 'etc_plugin_css' );
function etc_plugin_css($hook) {
	if( $hook == "tools_page_export-top-customers" ) {
		$plugin_url = plugin_dir_url( __FILE__ );
    	wp_enqueue_style( 'etc-style', $plugin_url . 'css/etc-style.css' );  
	}
}

// display plugin page function
function etc_plugin_page() {
?>
<div id="etc-form-container">
	<h1>Export Top Customers</h1>
	<!-- Form -->
	<form method='post' action='<?= $_SERVER['REQUEST_URI']; ?>' enctype='multipart/form-data'>
		<label>Number of Customers to Export <input type="number" name="num_of_customers" ><em>default is 100</em></label>
		<br>
		<input type="submit" name="etc_export" value="Export Users to CSV">
	</form>
</div>
<?php
	
	// if form submitted
	if( isset($_POST['etc_export']) ){
		
		// set up basic query to get all customer users from db
		$etc_args = [
			'role' => 'customer',
			'orderby' => 'meta_value_num',
			'meta_key' => '_money_spent',
			'order' => 'DESC',
		];
		
		// if number of users is entered
		if( is_numeric($_POST['num_of_customers']) && $_POST['num_of_customers'] > 0 ) {
			$num_of_customers = $_POST['num_of_customers'];
		}
		else {
			$num_of_customers = 100; // else default to exporting top 100 customers
		}
		
		$etc_args['number'] = $num_of_customers;
		
		$etc_user_query = new WP_USER_Query($etc_args);
		
		$etc_top_customers = [];
		
		// User Loop
		if ( ! empty( $etc_user_query->get_results() ) ) {
			foreach ( $etc_user_query->get_results() as $etc_user ) {
				$etc_top_customers[] = [
					$etc_user->user_email,
					wc_get_customer_total_spent($etc_user->ID),
				];
			}
			
			//echo "Top " . count($etc_top_customers) . " customers exported successfully!";
			
			export_top_customers_csv($etc_top_customers);			
			
		} else {
			echo 'No users found.';
		}
		
		
	} // end check if form submitted
	
} // end function etc_plugin_page



?>