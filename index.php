<?php

/*
 * Plugin Name:       Split Traffic A/B Testing
 * Plugin URI:        https://blazingsun.space/ -> its fake url
 * Description:       Split Traffic A/B Testing plugin is used to split website traffic between two versions of a page and count traffic, conversations and unique conversations.
 * Version:           1.0.0
 * Requires at least: 6.4.1
 * Requires PHP:      8.2
 * Author:            Milorad Đuković
 * Author URI:        https://blazingsun.space/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://blazingsun.space/update -> its fake url
 * Text Domain:       split_traffic_a_b_testing
 * Domain Path:       /languages/
*/



if (!defined('ABSPATH')) {
    exit;
}


/**
 * Split_Traffic_A_B_Testing class is class for controlling Split traffic A/B testing logics
 */

if (!class_exists('Split_Traffic_A_B_Testing')) {

    require_once plugin_dir_path(__FILE__) . '/include/Helpers.php';

    class Split_Traffic_A_B_Testing
    {

        use Helpers;
        
        private $table_version = 1;

        public function __construct()
        {
            $this->define_constants();
            add_action('wp_ajax_conversation_counter_fetch', [$this, 'conversation_counter_fetch'], 100, 0);

            if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'control-djukovic') {
                add_action('init', [$this, 'redirect_a_b']);
            }
            if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'control-djukovic' || basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'experiment-a-djukovic') {
                // adding stylesheet and script
                add_action('wp_enqueue_scripts', [$this, 'add_split_traffic_a_b_testing_stylesheet'], 100, 0);
                add_action('wp_enqueue_scripts', [$this, 'add_split_traffic_a_b_testing_javascript'], 100, 0);

                add_action('the_content', [$this, 'my_function_on_page_load'], 100, 1);
            }
            add_action('wp', [$this, 'page_traffic_counter']);



            // because plugin is using OOP javascript type="module" is needed. When JS is using type="module" (OOP) it becomes hard to control script from developer panel
            add_filter('script_loader_tag', function ($tag, $handle, $src) {

                switch ($handle) {
                    case SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script':
                        return '<script type="module" src="' . esc_url($src) . '"></script>';
                        break;
                    case 'admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script':
                        return '<script type="module" src="' . esc_url($src) . '"></script>';
                        break;
                    default:
                        return $tag;
                        break;
                }
            }, 10, 3);



            add_action('wp',  [$this, 'setWpAdminAjaxCookie'], 100, 0);

            add_action('admin_menu', [$this, 'split_traffic_a_b_testing_plugin_menu'], 100, 0);

            add_action('plugins_loaded',  [$this, 'split_traffic_a_b_testing_plugin_load_textdomain'], 100, 0);

            if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'admin.php?page=split_traffic_a_b_testing') {
                add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_admin_styles'], 100, 0);
                add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_admin_scripts'], 100, 0);
            }
            // Add settings link to plugin row
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'my_plugin_settings_link']);

            // Add settings link to plugin details
            add_filter('plugin_row_meta', [$this, 'my_plugin_info_settings_link'], 10, 2);
        }



        public function my_plugin_info_settings_link($links, $file)
        {
            if (plugin_basename(__FILE__) === $file) {
                $settings_link = '<a href="admin.php?page=split_traffic_a_b_testing">Settings</a>';
                $links[] = $settings_link;
            }

            return $links;
        }




        public function my_plugin_settings_link($links)
        {
            $settings_link = '<a href="admin.php?page=split_traffic_a_b_testing">Settings</a>';
            array_push($links, $settings_link);
            return $links;
        }

        private function define_constants()
        {
            define('SPLIT_TRAFFIC_A_B_TESTING_PATH', plugin_dir_path(__FILE__));
            define('SPLIT_TRAFFIC_A_B_TESTING_URL', plugin_dir_url(__FILE__));
            define('SPLIT_TRAFFIC_A_B_TESTING_VERSION', '1.0.0');
            define('SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION', 1);
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME', 'split_traffic_a_b_testing');
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY', 'Split Traffic A/B Testing');
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2', 'A/B Split Testing');
            define('SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING', 'Djukovic');
        }

        function my_function_on_page_load($content)
        {

            ob_start();

            if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'control-djukovic') {
                $args = array(
                    'pointer' => 'control'
                );
            } else  if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'experiment-a-djukovic') {
                $args = array(
                    'pointer' => 'experiment'
                );
            }

            include_once(plugin_dir_path(__FILE__) . 'templates/page-template.php');
            $content = ob_get_contents();

            ob_end_clean();
            return $content;
        }


        /**
         * split_traffic_a_b_testing is loading text domain so plugin can use wordpress translations
         */

        public function split_traffic_a_b_testing_plugin_load_textdomain()
        {
            load_plugin_textdomain('split_traffic_a_b_testing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public function enqueue_custom_admin_scripts()
        {

            $js_path = plugins_url('assets/js/Admin_App.js', __FILE__);
            wp_register_script('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script', $js_path, array(), SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_script('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script');
        }



        public function enqueue_custom_admin_styles()
        {
            // Replace 'admin-styles' with your unique handle

            $stylesheet_path = plugins_url('assets/css/admin-styles.css', __FILE__);

            wp_register_style('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles', $stylesheet_path, array(), SPLIT_TRAFFIC_A_B_TESTING_VERSION, 'all');

            wp_enqueue_style('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles');
        }


        public function split_traffic_a_b_testing_plugin_menu()
        {

            $svgFilePath = plugin_dir_path(__FILE__) . '/assets/images/a-b-test-abtest-testing-ab-variant-svgrepo-com.svg';

            $svgContent = file_get_contents($svgFilePath);

            // Encode the SVG content to base64
            $base64EncodedSVG = base64_encode($svgContent);

            // Output the base64-encoded SVG string


            add_menu_page(
                SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2,
                SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2,
                'manage_options',
                SPLIT_TRAFFIC_A_B_TESTING_NAME,
                [$this, 'split_traffic_a_b_testing_plugin_options'],
                'data:image/svg+xml;base64,' . $base64EncodedSVG, //'dashicons-image-flip-horizontal'
                100
            );
        }

        public function split_traffic_a_b_testing_plugin_options()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Define the field you want to retrieve
            $desired_field = 'control_traffic_counter, experiment_traffic_counter, control_conversation_counter, experiment_conversation_counter, control_unique_conversation_counter, experiment_unique_conversation_counter, amount_for_unique_expiry, unit_for_unique_expiry';

            $db_return_values = $this->return_database_results($desired_field, 'get_results');


            $wpdb           = $db_return_values[0];
            $table_name     = $db_return_values[1];
            $sql            = $db_return_values[2];
            $result         = $db_return_values[3];

            $amount_for_unique_expiry = $result[0]->amount_for_unique_expiry;

            $unit_for_unique_expiry = $result[0]->unit_for_unique_expiry;


            $control_traffic_counter = $result[0]->control_traffic_counter;

            $experiment_traffic_counter = $result[0]->experiment_traffic_counter;


            $control_conversation_counter = $result[0]->control_conversation_counter;

            $experiment_conversation_counter = $result[0]->experiment_conversation_counter;


            $control_unique_conversation_counter = $result[0]->control_unique_conversation_counter;

            $experiment_unique_conversation_counter = $result[0]->experiment_unique_conversation_counter;


            $args = array(
                'amount_for_unique_expiry'                  => $amount_for_unique_expiry,
                'unit_for_unique_expiry'                    => $unit_for_unique_expiry,
                'unit_for_unique_expiry_types'              => [
                    'seconds', 'minutes', 'hours', 'days', 'weeks', 'months'
                ],
                'control_traffic_counter'                    => $control_traffic_counter,
                'experiment_traffic_counter'                 => $experiment_traffic_counter,
                'control_conversation_counter'              => $control_conversation_counter,
                'experiment_conversation_counter'           => $experiment_conversation_counter,
                'control_unique_conversation_counter'       => $control_unique_conversation_counter,
                'experiment_unique_conversation_counter'    => $experiment_unique_conversation_counter
            );

            include_once plugin_dir_path(__FILE__) . 'templates/admin-template.php';

            if (isset($_POST['submit'])) {
                // Handle form submission
                $this->handle_form_submission();
            }
        }

        private function handle_form_submission()
        {

            global $wpdb;

            // Define your custom table name
            $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;

            // Validate and sanitize form data
            $data_to_update = [
                'amount_for_unique_expiry'  => sanitize_text_field($_POST['amount_for_unique_expiry']),
                'unit_for_unique_expiry'    => sanitize_text_field($_POST['unit_for_unique_expiry']),

            ];

            $where_condition = array(
                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
            );

            global $wpdb;

            $table_name_unique = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . '_unique_users';

            $wpdb->update($table_name, $data_to_update, $where_condition);


            if ($wpdb->last_error === '') {
                $update_of_expiry_values = [
                    'success' => true,
                    'data' => [
                        'updated' => $data_to_update
                    ]
                ];
            } else {

                $update_of_expiry_values = [
                    'success' => false,
                    'data' => [
                        'updated' => $data_to_update
                    ]
                ];
            }


            $sql = $wpdb->prepare("SELECT * FROM $table_name_unique WHERE `table_version` = ".SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION );

            $results = $wpdb->get_results($sql, OBJECT);

            $update_inputed_expiry_times_array = [];

            foreach ($results as $result) {

                $currentDateTime = $result->created_at;

                $data_to_update_unique = array(
                    'expiration_date'  => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . sanitize_text_field($_POST['amount_for_unique_expiry']) . ' ' . sanitize_text_field($_POST['unit_for_unique_expiry'])))
                    // Add more fields as needed
                );

                $where_condition_unique = array(
                    'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                    'id' =>  $result->id
                );
                $wpdb->update($table_name_unique, $data_to_update_unique, $where_condition_unique);

                if ($wpdb->last_error === '') {
                    array_push($update_inputed_expiry_times_array, [
                        'success' => true,
                        'data' => [
                            'updated' => [
                                'condition' => $where_condition_unique,
                                'updated_data' => $data_to_update_unique
                            ]
                        ]
                    ]);
                } else {

                    array_push($update_inputed_expiry_times_array, [
                        'success' => false,
                        'data' => [
                            'updated' => [
                                'condition' => $where_condition_unique,
                                'updated_data' => $data_to_update_unique
                            ]
                        ]
                    ]);
                }
            }

            wp_send_json([
                'update_of_expiry_values'               => $update_of_expiry_values ?? ['not_present' => true],
                'update_inputed_expiry_times_array'     => $update_inputed_expiry_times_array ?? ['not_present' => true]
            ]);
        }

        public function setWpAdminAjaxCookie()
        {

            setcookie('wpAdminAjaxUrl', admin_url('admin-ajax.php'), time() + (86400 * 21), '/');
        }

        /**
         * add_split_traffic_a_b_testing_javascript is adding CSS file to head
         */
        public function add_split_traffic_a_b_testing_stylesheet()
        {

            $stylesheet_path = plugins_url('assets/css/style.css', __FILE__);

            wp_register_style(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles', $stylesheet_path, array(), SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_style(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles');
        }

        /**
         * add_split_traffic_a_b_testing_javascript is adding JavaScript file to head
         */

        public function add_split_traffic_a_b_testing_javascript()
        {

            $js_path = plugins_url('assets/js/App.js', __FILE__);
            wp_register_script(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script', $js_path, array(), SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_script(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script');
        }


        public function conversation_counter_fetch()
        {
            // Your form processing logic goes here
            $form_data = sanitize_text_field($_POST['conversation_pointer']);

            // Perform actions with $form_data

            $page_pointer = 'control_conversation_counter';

            global $wpdb;


            // Define the field you want to retrieve
            $desired_field = 'control_conversation_counter, experiment_conversation_counter, table_version, control_unique_conversation_counter, experiment_unique_conversation_counter, amount_for_unique_expiry, unit_for_unique_expiry';

            $db_return_values = $this->return_database_results($desired_field, 'get_results');


            $wpdb           = $db_return_values[0];
            $table_name     = $db_return_values[1];
            $sql            = $db_return_values[2];
            $result         = $db_return_values[3];


            $amount_for_unique_expiry = $result[0]->amount_for_unique_expiry;

            $unit_for_unique_expiry = $result[0]->unit_for_unique_expiry;


            $control_conversation_counter = $result[0]->control_conversation_counter;

            $experiment_conversation_counter = $result[0]->experiment_conversation_counter;


            $control_unique_conversation_counter = $result[0]->control_unique_conversation_counter;

            $experiment_unique_conversation_counter = $result[0]->experiment_unique_conversation_counter;


            if ($form_data === 'control') {
                $data_to_update = array(
                    'control_conversation_counter' => $control_conversation_counter + 1
                );
            } else if ($form_data === 'experiment') {
                $page_pointer = 'experiment_conversation_counter';
                $data_to_update = array(
                    'experiment_conversation_counter' => $experiment_conversation_counter + 1
                );
            }

            $where_condition = array(
                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
            );

            $wpdb->update($table_name, $data_to_update, $where_condition);
            // Check if the update was successful

            if ($wpdb->last_error === '') {
                $regular_conversation_array = [
                    'success' => true,
                    'data' => [
                        'updated' => $page_pointer
                    ]
                ];
            } else {

                $regular_conversation_array = [
                    'success' => false
                ];
            }

            // Define the field you want to retrieve
            $desired_field_unique = 'username, expiration_date, created_at';
            $username = wp_get_current_user()->user_login;
            $condition = "table_version=" . SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION . " AND username='" . $username . "'";
    
            $db_return_values_unique = $this->return_database_results($desired_field_unique, 'get_results', $condition, true);

            $wpdb_unique           = $db_return_values_unique[0];
            $table_name_unique     = $db_return_values_unique[1];
            $sql_unique            = $db_return_values_unique[2];
            $result_unique         = $db_return_values_unique[3];

            $currentDateTime = date('Y-m-d H:i:s');
            print_r($result_unique);
            if (empty($result_unique)) {

                $wpdb->insert(
                    $table_name_unique,
                    array(
                        'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                        'username' => $username,
                        'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                        'created_at' => $currentDateTime

                    )
                );

                if ($form_data === 'control') {
                    $page_pointer = 'unique_empty_control_conversation_counter';
                    $data_to_update = array(
                        'control_unique_conversation_counter' => $control_unique_conversation_counter + 1
                    );
                } else if ($form_data === 'experiment') {
                    $page_pointer = 'unique_empty_experiment_conversation_counter';
                    $data_to_update = array(
                        'experiment_unique_conversation_counter' => $experiment_unique_conversation_counter + 1
                    );
                }

                $where_condition = array(
                    'table_version' =>  SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
                );

                $wpdb->update($table_name, $data_to_update, $where_condition);

                if ($wpdb->last_error === '') {
                    $empty_unique_conversation_array = [
                        'success' => true,
                        'data' => [
                            'updated' => $page_pointer
                        ]
                    ];
                } else {

                    $empty_unique_conversation_array = [
                        'success' => false
                    ];
                }
            } else {

                $expiration_date = strtotime($result_unique[0]->expiration_date);
                $currentTimestamp = time();

                if ($expiration_date < $currentTimestamp) {

                    if ($form_data === 'control') {
                        $page_pointer = 'unique_not_empty_control_conversation_counter';
                        $data_to_update = array(
                            'control_unique_conversation_counter' => $control_unique_conversation_counter + 1
                        );

                        $data_to_update_unique = array(
                            'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                            'created_at' => $currentDateTime
                        );
                    } else if ($form_data === 'experiment') {
                        $page_pointer = 'unique_not_empty_experiment_conversation_counter';
                        $data_to_update = array(
                            'experiment_unique_conversation_counter' => $experiment_unique_conversation_counter + 1
                        );

                        $data_to_update_unique = array(
                            'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                            'created_at' => $currentDateTime
                        );
                    }

                    $where_condition = array(
                        'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
                    );

                    $wpdb->update($table_name, $data_to_update, $where_condition);

                    $where_condition_unique = array(
                        'username' => $username,
                        'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
                    );
                    $wpdb->update($table_name_unique, $data_to_update_unique, $where_condition_unique);

                    if ($wpdb->last_error === '') {
                        $not_empty_unique_conversation_array = [
                            'success' => true,
                            'data' => [
                                'updated' => $page_pointer
                            ]
                        ];
                    } else {

                        $not_empty_unique_conversation_array = [
                            'success' => false
                        ];
                    }
                }
            }

            wp_send_json([
                'regular_conversation_array'            => $regular_conversation_array ?? ['not_present' => true],
                'empty_unique_conversation_array'       => $empty_unique_conversation_array ?? ['not_present' => true],
                'not_empty_unique_conversation_array'   => $not_empty_unique_conversation_array ?? ['not_present' => true]
            ]);

            wp_die();
        }

        public function page_traffic_counter()
        {

            $page_pointer = 'control_traffic_counter';

            $desired_field = 'control_traffic_counter, experiment_traffic_counter';

            $db_return_values = $this->return_database_results($desired_field, 'get_results');

            $wpdb           = $db_return_values[0];
            $table_name     = $db_return_values[1];
            $sql            = $db_return_values[2];
            $result         = $db_return_values[3];

            $control_traffic_counter = $result[0]->control_traffic_counter;
            $experiment_traffic_counter = $result[0]->experiment_traffic_counter;

            if (basename(esc_url(home_url($_SERVER['REQUEST_URI']))) === 'control-djukovic') {
                $data_to_update = array(
                    'control_traffic_counter' => $control_traffic_counter + 1
                );
            } else {
                $page_pointer = 'experiment_traffic_counter';
                $data_to_update = array(
                    'experiment_traffic_counter' => $experiment_traffic_counter + 1
                );
            }

            $where_condition = array(
                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
            );

            $wpdb->update($table_name, $data_to_update, $where_condition);

            // Check if the update was successful
            if ($wpdb->last_error === '') {
                //echo 'Field ' . $page_pointer . ' updated successfully.';
            } else {
                echo 'Error updating ' . $page_pointer . ' field: ' . $wpdb->last_error;
            }
        }

        public function redirect_a_b()
        {


            $query = self::prepare_query_args(basename(esc_url(home_url($_SERVER['REQUEST_URI']))));

            $desired_field = 'next_redirect';

            $db_return_values = $this->return_database_results($desired_field, 'get_var');


            $wpdb           = $db_return_values[0];
            $table_name     = $db_return_values[1];
            $sql            = $db_return_values[2];
            $result         = $db_return_values[3];


            if ($result === 'Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING) {
                $data_to_update = array(
                    $desired_field => 'Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                );
            } else {
                $data_to_update = array(
                    $desired_field => 'Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                );
            }

            $where_condition = array(
                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
            );

            $wpdb->update($table_name, $data_to_update, $where_condition);


            // Check if the update was successful
            if ($wpdb->last_error === '') {
                //echo 'Field next_redirect updated successfully.';
            } else {
                echo 'Error updating next_redirect field: ' . $wpdb->last_error;
            }

            if ($query->post->post_title === $result) {
                $page = get_page_by_path($wpdb->get_var($sql));

                $page_url = get_permalink($page->ID);

                wp_redirect($page_url);
                exit();
            } 
        }



        public static function activate()
        {

            self::create_page('Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING, 'control');
            self::create_page('Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING, 'experiment');
            self::create_database_table();
        }

        
        public static function deactivate()
        {
            self::delete_page_by_name('Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING);
            self::delete_page_by_name('Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING);
        }



        public static function uninstall()
        {
            self::remove_database_table();
        }
    }
}

// Register activation, deactivation, and uninstall hooks
if (class_exists('Split_Traffic_A_B_Testing')) {
    
    register_activation_hook(__FILE__, array('Split_Traffic_A_B_Testing', 'activate'));
    register_deactivation_hook(__FILE__, array('Split_Traffic_A_B_Testing', 'deactivate'));
    register_uninstall_hook(__FILE__, array('Split_Traffic_A_B_Testing', 'uninstall'));

    $Split_Traffic_A_B_Testing = new Split_Traffic_A_B_Testing();
}
