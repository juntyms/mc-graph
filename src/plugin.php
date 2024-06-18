<?php
/**
 * Plugin Name: MC Graph
 * Version: 1.0.0
 * Author: Junn Eric
 *
 * @package MC_Graph
 */

namespace Juntyms\McGraph;

/**
 * Mc_Graph_Plugin
 */
class Mc_Graph {
	/**
	 * The Table name in the Database
	 *
	 * @var string $table_name Table name with WordPress prefix.
	 */
	private $table_name;

	/**
	 * Constructor for the Mc_Graph plugin.
	 *
	 * Initializes the plugin by setting up database table name,
	 * registering hooks for deactivation, REST API routes, dashboard widgets,
	 * and enqueuing scripts and styles.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mc_graph';

		register_deactivation_hook( MC_GRAPH_PLUGIN_FILENAME, array( $this, 'mc_graph_delete_table' ) );
		add_action( 'rest_api_init', array( $this, 'mc_graph_register_rest_route' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'mc_graph_dashboard_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mc_graph_enqueue_scripts' ) );
	}

	/**
	 * Activate the MC Graph plugin.
	 *
	 * @return void
	 */
	public static function mc_graph_activate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		$mc_graph = new self();
		$mc_graph->mc_graph_create_table();
	}

	/**
	 * Create the database table for MC Graph plugin.
	 *
	 * @return void
	 */
	public function mc_graph_create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		date date NOT NULL,
		value float NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$my_date  = strtotime( '-1 month' );
		$end_date = time();

		while ( $my_date < $end_date ) {
			$wpdb->insert(
				$this->table_name,
				array(
					'date'  => date( 'Y-m-d', $my_date ),
					'value' => rand( 0, 20 ),
				)
			);
			$my_date = strtotime( '+1 day', $my_date );
		}
	}

	/**
	 * Delete the database table for MC Graph plugin.
	 *
	 * @return void
	 */
	public function mc_graph_delete_table() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
	}

	/**
	 * Handles plugin uninstall
	 *
	 * @return void
	 */
	public static function mc_graph_uninstall() {

		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}

	/**
	 * Register REST API route for fetching data.
	 *
	 * @return void
	 */
	public function mc_graph_register_rest_route() {
		register_rest_route(
			'mc_graph/v1',
			'/data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'mc_graph_get_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'days' => array(
						'required'          => true,
						'validate_callback' => function ( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Callback function to fetch data for the REST API endpoint.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error Response object or error if no data found.
	 */
	public function mc_graph_get_data( $request ) {
		global $wpdb;
		$days = (int) $request->get_param( 'days' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date, value FROM {$this->table_name} WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				$days
			),
			ARRAY_A
		);

		if ( null === $results ) {
			return new WP_Error( 'no_data', 'No data found', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $results );
	}

	/**
	 * Add dashboard widget for MC Graph plugin.
	 *
	 * @return void
	 */
	public function mc_graph_dashboard_widget() {
		wp_add_dashboard_widget(
			'mc_graph_dashboard_widget_id',
			__( 'Graph Widget', 'mc-graph-option' ),
			array( $this, 'mc_graph_dashboard_widget_content' )
		);
	}

	/**
	 * Dashboard widget content for MC Graph plugin.
	 *
	 * @return void
	 */
	public function mc_graph_dashboard_widget_content() {
		?>
		<div class='inside'>				
			<div id='myapp'></div>
		</div>
		<?php
		wp_enqueue_script( 'mcg-app', plugins_url( '/build/index.js', MC_GRAPH_PLUGIN_FILENAME ), array(), null, true );
		wp_enqueue_style( 'mcg-app', plugins_url( '/build/index.css', MC_GRAPH_PLUGIN_FILENAME ) );
	}

	/**
	 * Enqueue scripts and styles for MC Graph plugin.
	 *
	 * @return void
	 */
	public function mc_graph_enqueue_scripts() {
		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script(
			'mcg-app',
			plugins_url( '/build/index.js', MC_GRAPH_PLUGIN_FILENAME ),
			array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
			null,
			true
		);

		wp_localize_script(
			'mcg-app',
			'mcGraphData',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'i18n'     => array(
					'last_7_days'  => __( 'Last 7 days', 'mc-graph-option' ),
					'last_15_days' => __( 'Last 15 days', 'mc-graph-option' ),
					'last_1_month' => __( 'Last 1 month', 'mc-graph-option' ),
				),
			)
		);
	}
}
