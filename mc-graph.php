<?php
/**
 * Plugin Name: MC Graph
 * Version: 1.0.0
 * Author: Junn Eric
 *
 * @package MC_Graph
 */

namespace Juntyms\McGraph;

define( 'MC_GRAPH_PLUGIN_FILENAME', __FILE__ );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/src/plugin.php';
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Initialize the plugin object
 *
 * @return void
 */
function mc_graph_plugin_init() {
	new Mc_Graph();
}

add_action( 'plugin_loaded', __NAMESPACE__ . '\mc_graph_plugin_init' );

register_activation_hook( __FILE__, __NAMESPACE__ . '\Mc_Graph::mc_graph_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\Mc_Graph::mc_graph_uninstall' );
