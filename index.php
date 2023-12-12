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

require_once plugin_dir_path(__FILE__) . '/include/Helpers.php';

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Split_Traffic_A_B_Testing class is class for controlling Split traffic A/B testing logics
 */

if (!class_exists('Split_Traffic_A_B_Testing')) {

    class Split_Traffic_A_B_Testing
    {

        use Helpers;

        private $table_version = 1;

        public function __construct()
        {

            $this->define_constants();

            add_action('plugins_loaded',  [$this, 'split_traffic_a_b_testing_plugin_load_textdomain'], 100, 0);

            add_action('admin_menu', [$this, 'split_traffic_a_b_testing_plugin_menu'], 100, 0);

            // Add settings link to plugin row
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'my_plugin_settings_link']);

            // Add settings link to plugin details
            add_filter('plugin_row_meta', [$this, 'my_plugin_info_settings_link'], 10, 2);



            if (
                self::get_page_slug() === 'control-djukovic' ||
                self::get_page_slug() === 'experiment-a-djukovic' ||
                self::get_page_slug() === 'admin.php?page=split_traffic_a_b_testing' ||
                self::get_page_slug() === 'admin-ajax.php'
            ) {




                if (self::get_page_slug() === 'control-djukovic') {
                    add_action('init', [$this, 'redirect_a_b']);
                }

                add_action('wp', [$this, 'page_traffic_counter']);

                add_action('wp_ajax_conversation_counter_fetch', [$this, 'conversation_counter_fetch'], 100, 0);

                add_action('wp',  [$this, 'setWpAdminAjaxCookie'], 100, 0);

                if (self::get_page_slug() === 'control-djukovic' || self::get_page_slug() === 'experiment-a-djukovic') {
                    // adding stylesheet and script
                    add_action('wp_enqueue_scripts', [$this, 'add_split_traffic_a_b_testing_stylesheet'], 100, 0);
                    add_action('wp_enqueue_scripts', [$this, 'add_split_traffic_a_b_testing_javascript'], 100, 0);

                    add_action('the_content', [$this, 'add_page_template'], 100, 1);
                }

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




                if (self::get_page_slug() === 'admin.php?page=split_traffic_a_b_testing') {
                    add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_admin_styles'], 100, 0);
                    add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_admin_scripts'], 100, 0);
                }
            }
        }
        /**
         * my_plugin_info_settings_link is adding settings link row list to plugin info
         */

        public function my_plugin_info_settings_link($links, $file)
        {
            if (plugin_basename(__FILE__) === $file) {
                $settings_link = '<a href="admin.php?page=split_traffic_a_b_testing">' . __('Settings', 'split_traffic_a_b_testing') . '</a>';
                $links[] = $settings_link;
            }

            return $links;
        }

        /**
         * my_plugin_settings_link is adding settings link plugin action links
         */
        public function my_plugin_settings_link($links)
        {
            $settings_link = '<a href="admin.php?page=split_traffic_a_b_testing">' . __('Settings', 'split_traffic_a_b_testing') . '</a>';
            array_push($links, $settings_link);
            return $links;
        }

        /**
         * define_constants is used for difining needed contstants for plugin
         */
        private function define_constants()
        {
            define('SPLIT_TRAFFIC_A_B_TESTING_PATH', plugin_dir_path(__FILE__));
            define('SPLIT_TRAFFIC_A_B_TESTING_URL', plugin_dir_url(__FILE__));
            define('SPLIT_TRAFFIC_A_B_TESTING_VERSION', '1.0.0');
            define('SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION', 1);
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME', 'split_traffic_a_b_testing');
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY', __('A/B Split Traffic Testing', 'split_traffic_a_b_testing'));
            define('SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2', __('A/B Split Testing', 'split_traffic_a_b_testing'));
            define('SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING', 'Djukovic');
        }

        /**
         * add_page_template is used for adding page templte, both control and experimental pages have same templates, 
         * only difference is the passed value which is used for conversations counting
         */
        function add_page_template($content)
        {

            ob_start();

            if (self::get_page_slug() === 'control-djukovic') {
                $args = [
                    'pointer' => 'control'
                ];
            } else  if (self::get_page_slug() === 'experiment-a-djukovic') {
                $args = [
                    'pointer' => 'experiment'
                ];
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


        /**
         * enqueue_custom_admin_scripts is adding admin javascript to head
         */
        public function enqueue_custom_admin_scripts()
        {

            $js_path = plugins_url('assets/js/Admin_App.js', __FILE__);
            wp_register_script('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script', $js_path, [], SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_script('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script');
        }

        /**
         * enqueue_custom_admin_scripts is adding admin css to head
         */

        public function enqueue_custom_admin_styles()
        {

            $stylesheet_path = plugins_url('assets/css/admin-styles.css', __FILE__);
            wp_register_style('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles', $stylesheet_path, [], SPLIT_TRAFFIC_A_B_TESTING_VERSION, 'all');
            wp_enqueue_style('admin-' . SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles');
        }


        public function split_traffic_a_b_testing_plugin_menu()
        {

            // getting content of svg file
            // it is apsolutly not needed we can use dashboard icons, but as example, i added custom svg icon
            $svgContent = file_get_contents(plugin_dir_path(__FILE__) . '/assets/images/a-b-test-abtest-testing-ab-variant-svgrepo-com.svg');

            // Encode the SVG content to base64
            $base64EncodedSVG = 'data:image/svg+xml;base64,' . base64_encode($svgContent);

            // This function takes a capability which will be used to determine whether or not a page is included in the menu
            // The function which is hooked in to handle the output of the page must check that the user has the required capability as well.

            add_menu_page(
                SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2, // html page title 
                SPLIT_TRAFFIC_A_B_TESTING_NAME_PRETTY_2, // menu item title
                'manage_options', // permision needed for accesing plugin settings page
                SPLIT_TRAFFIC_A_B_TESTING_NAME, // menu slug
                [$this, 'split_traffic_a_b_testing_plugin_options'], // main callback function for menu page
                $base64EncodedSVG, //'dashicons-image-flip-horizontal'
                100 // order in menu
            );
        }

        public function split_traffic_a_b_testing_plugin_options()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Define the field you want to retrieve

            $db_return_values = $this->return_database_results(
                [
                    'desired_field' =>  'control_traffic_counter, experiment_traffic_counter, control_conversation_counter, experiment_conversation_counter, control_unique_conversation_counter, experiment_unique_conversation_counter, amount_for_unique_expiry, unit_for_unique_expiry',
                    'method' => 'get_results'
                ]
            );

            $result         = $db_return_values->result;

            $args = [
                'amount_for_unique_expiry'                  => $result[0]->amount_for_unique_expiry,
                'unit_for_unique_expiry'                    => $result[0]->unit_for_unique_expiry,
                'unit_for_unique_expiry_types'              => [
                    'seconds', 'minutes', 'hours', 'days', 'weeks', 'months'
                ],
                'control_traffic_counter'                   => $result[0]->control_traffic_counter,
                'experiment_traffic_counter'                => $result[0]->experiment_traffic_counter,
                'control_conversation_counter'              => $result[0]->control_conversation_counter,
                'experiment_conversation_counter'           => $result[0]->experiment_conversation_counter,
                'control_unique_conversation_counter'       => $result[0]->control_unique_conversation_counter,
                'experiment_unique_conversation_counter'    => $result[0]->experiment_unique_conversation_counter
            ];

            include_once plugin_dir_path(__FILE__) . 'templates/admin-template.php';

            if (isset($_POST['submit'])  && !empty($_POST['submit'])) {
                // Handle form submission
                $this->handle_form_submission();
            }
        }

        private function handle_form_submission()
        {


            if (
                (isset($_POST['amount_for_unique_expiry'])  && !empty($_POST['amount_for_unique_expiry'])) &&
                (isset($_POST['unit_for_unique_expiry'])  && !empty($_POST['unit_for_unique_expiry']))
            ) {
                global $wpdb;

                // Define your custom table name
                $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;

                // Validate and sanitize form data
                $data_to_update = [
                    'amount_for_unique_expiry'  => sanitize_text_field($_POST['amount_for_unique_expiry']),
                    'unit_for_unique_expiry'    => sanitize_text_field($_POST['unit_for_unique_expiry']),
                ];



                $this->update_database(['table_name' => 1,  'data_to_update' => $data_to_update]);


                $db_return_values = $this->return_database_results(
                    [
                        'desired_field' => '*',
                        'method' => 'get_results',
                        'condition' => [
                            'table_verison' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
                        ],
                        'unique_pointer' => true
                    ]
                );

                $results = $db_return_values->results;

                foreach ($results as $result) {

                    $this->update_database(
                        [
                            'table_name' => 2,
                            'data_to_update' => [
                                'expiration_date'  => date('Y-m-d H:i:s', strtotime($result->created_at . ' +' . sanitize_text_field($_POST['amount_for_unique_expiry']) . ' ' . sanitize_text_field($_POST['unit_for_unique_expiry'])))
                            ],
                            'where_condition' => [
                                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                                'id' =>  $result->id
                            ]
                        ]
                    );
                }

                wp_redirect(admin_url('admin.php?page=split_traffic_a_b_testing'));
                exit();
            }
        }

        /**
         * setWpAdminAjaxCookie function is setting cookie for wp admin ajax
         */
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

            wp_register_style(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles', $stylesheet_path, [], SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_style(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-styles');
        }

        /**
         * add_split_traffic_a_b_testing_javascript is adding JavaScript file to head
         */

        public function add_split_traffic_a_b_testing_javascript()
        {

            $js_path = plugins_url('assets/js/App.js', __FILE__);
            wp_register_script(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script', $js_path, [], SPLIT_TRAFFIC_A_B_TESTING_VERSION);
            wp_enqueue_script(SPLIT_TRAFFIC_A_B_TESTING_NAME . '-script');
        }

        public function conversation_counter_fetch()
        {

            if (isset($_POST['conversation_pointer']) && !empty($_POST['conversation_pointer'])) {
                // Your form processing logic goes here
                $form_data = sanitize_text_field($_POST['conversation_pointer']);

                // Perform actions with $form_data

                $username = wp_get_current_user()->user_login;

                $db_return_values = $this->return_database_results(
                    [
                        'desired_field' =>  'control_conversation_counter, experiment_conversation_counter, table_version, control_unique_conversation_counter, experiment_unique_conversation_counter, amount_for_unique_expiry, unit_for_unique_expiry',
                        'method' => 'get_results'
                    ]
                );

                $result         = $db_return_values->result;


                $amount_for_unique_expiry = $result[0]->amount_for_unique_expiry;

                $unit_for_unique_expiry = $result[0]->unit_for_unique_expiry;


                $control_conversation_counter = $result[0]->control_conversation_counter;

                $experiment_conversation_counter = $result[0]->experiment_conversation_counter;


                $control_unique_conversation_counter = $result[0]->control_unique_conversation_counter;

                $experiment_unique_conversation_counter = $result[0]->experiment_unique_conversation_counter;


                if ($form_data === 'control') {

                    $data_to_update = [
                        'control_conversation_counter' => $control_conversation_counter + 1
                    ];
                } else if ($form_data === 'experiment') {

                    $data_to_update = [
                        'experiment_conversation_counter' => $experiment_conversation_counter + 1
                    ];
                }

                $this->update_database(
                    [
                        'table_name' => 1,
                        'data_to_update' => $data_to_update
                    ]
                );

                $db_return_values_unique = $this->return_database_results(
                    [
                        'desired_field' =>  'username, expiration_date, created_at',
                        'method' => 'get_results',
                        'condition' => ['table_verison' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION, 'username' => $username],
                        'unique_pointer' => true
                    ]
                );

                $result_unique         = $db_return_values_unique->result;

                $currentDateTime = date('Y-m-d H:i:s');

                if (empty($result_unique)) {

                    $this->insert_to_database(
                        [
                            'table_name' => 2,
                            'data_to_insert' => [
                                'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                                'username' => $username,
                                'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                                'created_at' => $currentDateTime

                            ]
                        ]
                    );

                    if ($form_data === 'control') {

                        $data_to_update = [
                            'control_unique_conversation_counter' => $control_unique_conversation_counter + 1
                        ];
                    } else if ($form_data === 'experiment') {

                        $data_to_update = [
                            'experiment_unique_conversation_counter' => $experiment_unique_conversation_counter + 1
                        ];
                    }

                    $this->update_database(
                        [
                            'table_name' => 1,
                            'data_to_update' => $data_to_update
                        ]
                    );
                } else {

                    $expiration_date = strtotime($result_unique[0]->expiration_date);
                    $currentTimestamp = time();

                    if ($expiration_date < $currentTimestamp) {

                        if ($form_data === 'control') {

                            $data_to_update = [
                                'control_unique_conversation_counter' => $control_unique_conversation_counter + 1
                            ];

                            $data_to_update_unique = [
                                'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                                'created_at' => $currentDateTime
                            ];
                        } else if ($form_data === 'experiment') {

                            $data_to_update = [
                                'experiment_unique_conversation_counter' => $experiment_unique_conversation_counter + 1
                            ];

                            $data_to_update_unique = [
                                'expiration_date' => date('Y-m-d H:i:s', strtotime($currentDateTime . ' +' . $amount_for_unique_expiry . ' ' . $unit_for_unique_expiry)),
                                'created_at' => $currentDateTime
                            ];
                        }

                        $this->update_database(
                            [
                                'table_name' => 1,
                                'data_to_update' => $data_to_update
                            ]
                        );

                        $this->update_database(
                            [
                                'table_name' => 2,
                                'data_to_update' => $data_to_update_unique,
                                'where_condition' => [
                                    'username' => $username,
                                    'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
                                ]
                            ]
                        );
                    }
                }

                wp_die();
            }
        }

        public function page_traffic_counter()
        {

            $db_return_values = $this->return_database_results(
                [
                    'desired_field' => 'control_traffic_counter, experiment_traffic_counter',
                    'method'        => 'get_results'
                ]
            );

            $result = $db_return_values->result[0];

            $control_traffic_counter    = $result->control_traffic_counter;
            $experiment_traffic_counter = $result->experiment_traffic_counter;

            if (self::get_page_slug() === 'control-djukovic') {
                $data_to_update = [
                    'control_traffic_counter' => $control_traffic_counter + 1
                ];
            } else if (self::get_page_slug() === 'experiment-a-djukovic') {
                $data_to_update = [
                    'experiment_traffic_counter' => $experiment_traffic_counter + 1
                ];
            }

            $this->update_database(
                [
                    'table_name' => 1,
                    'data_to_update' => $data_to_update
                ]
            );
        }

        public function redirect_a_b()
        {

            $db_return_values = $this->return_database_results(
                [
                    'desired_field' => 'next_redirect',
                    'method' => 'get_var'
                ]
            );

            $result = $db_return_values->result;

            if ($result === 'Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING) {
                $data_to_update = [
                    'next_redirect' => 'Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                ];
            } else if ($result === 'Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING) {
                $data_to_update = [
                    'next_redirect' => 'Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                ];
            }

            $this->update_database(
                [
                    'table_name' => 1,
                    'data_to_update' => $data_to_update
                ]
            );

            $query = self::prepare_query_args(self::get_page_slug());

            if ($query->post->post_title === $result) {

                $page = get_page_by_path('Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING);

                $page_url = get_permalink($page->ID);

                wp_redirect($page_url);
                exit();
            }
        }

        public static function activate()
        {

            self::create_page('Control - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING);
            self::create_page('Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING);
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
// Check if the class 'Split_Traffic_A_B_Testing' exists before proceeding
if (class_exists('Split_Traffic_A_B_Testing')) {

    // Register activation hook to execute 'activate' method when the plugin is activated
    register_activation_hook(__FILE__, ['Split_Traffic_A_B_Testing', 'activate']);

    // Register deactivation hook to execute 'deactivate' method when the plugin is deactivated
    register_deactivation_hook(__FILE__, ['Split_Traffic_A_B_Testing', 'deactivate']);

    // Register uninstall hook to execute 'uninstall' method when the plugin is uninstalled
    register_uninstall_hook(__FILE__, ['Split_Traffic_A_B_Testing', 'uninstall']);

    // Instantiate the 'Split_Traffic_A_B_Testing' class to initialize the plugin
    $Split_Traffic_A_B_Testing = new Split_Traffic_A_B_Testing();
}
