<?php

/**
 * Plugin Name: MC Graph
 * Version: 1.0.0
 * Author: Junn Eric
 */
if (!defined('ABSPATH')) {
    exit;
}

function mc_graph_dashboard_widget()
{
    wp_add_dashboard_widget(
        'mc_graph_dashboard_widget_id',
        'Graph Widget',
        'mc_graph_dashboard_widget_content'
    );
}

add_action('wp_dashboard_setup', 'mc_graph_dashboard_widget');

function mc_graph_dashboard_widget_content()
{
    ?>
    <div class="inside">
        <div id="my-app"></div>
    </div>
    <?php
    wp_enqueue_script('mcg-app', plugins_url('/build/static/js/main.js', __FILE__), array(), null, true);
    wp_enqueue_style('mcg-app', plugins_url('/build/static/css/main.css', __FILE__));
}

function mc_graph_create_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mc_graphs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date date NOT NULL,
        value float NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);


    $current_date = strtotime('-1 month');
    $end_date = time();

    while ($current_date < $end_date) {
        $wpdb->insert(
            $table_name,
            array(
                'date' => date('Y-m-d', $current_date),
                'value' => rand(0, 20)
            )
        );
        $current_date = strtotime('+1 day', $current_date);
    }

}

register_activation_hook(__FILE__, 'mc_graph_create_table');



function mc_graph_delete_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mc_graphs';

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_deactivation_hook(__FILE__, 'mc_graph_delete_table');


function mc_graph_register_rest_route()
{
    register_rest_route('mc_graph/v1', '/data', array(
        'methods' => 'GET',
        'callback' => 'mc_graph_get_data',
        'args' => array(
            'days' => array(
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                }
            )
        ),
    ));
}

add_action('rest_api_init', 'mc_graph_register_rest_route');


function mc_graph_get_data($request)
{
    global $wpdb;

    $days = (int) $request['days'];
    $table_name = $wpdb->prefix .'mc_graphs';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT date, value FROM $table_name WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            $days
        ),
        ARRAY_A
    );

    return new WP_REST_Response($results, 200);
}
