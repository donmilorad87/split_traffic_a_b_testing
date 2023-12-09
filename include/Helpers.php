<?php

trait Helpers
{
 
    private static function return_database_results($desired_field, $method, $condition = 1, $unique_pointer = false)
    {
        
        global $wpdb;
        $pointer = '';
        if ($unique_pointer) {
            $pointer = '_unique_users';
        }

        // Define your custom table name
        $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . $pointer;

        // Your SQL query to retrieve the field value
        $sql = $wpdb->prepare("SELECT $desired_field FROM $table_name WHERE %s", $condition);


        // Get the result from the database
        if ($method === 'get_var') {
            $result = $wpdb->get_var($sql);
        } else if ($method === 'get_results') {
            $result = $wpdb->get_results($sql, OBJECT);
        }



        return [$wpdb, $table_name, $sql, $result];
    }

    private static function prepare_query_args($name)
    {   
       
        $query = new WP_Query(
            [
                'post_type'              => 'page',
                'name'                   => $name,
                'post_status'            => 'any',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'orderby'                => 'post_date ID',
                'order'                  => 'ASC',
            ]
        );

        return $query;
    }

    private static function create_page(string $page_title)
    {


        if (self::get_page_by_name($page_title)) {
            $page_args = [
                'post_title'    =>  $page_title,
                'post_content'  =>  '',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'page_template' => ''
            ];
        }


        // Insert the page into the database
        wp_insert_post($page_args);
    }

    private static function get_page_by_name($name)
    {

        $query = self::prepare_query_args($name);

        if (!empty($query->post)) {
            return false;
        }

        return true;
    }

    private static function create_database_table()
    {


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $sql = "CREATE TABLE $table_name (
                        table_version int(9) DEFAULT 1 NOT NULL,
                        next_redirect text NOT NULL,
                        control_traffic_counter int(9) DEFAULT 0 NOT NULL,
                        experiment_traffic_counter int(9) DEFAULT 0 NOT NULL,
                        control_conversation_counter int(9) DEFAULT 0 NOT NULL,
                        experiment_conversation_counter int(9) DEFAULT 0 NOT NULL,
                        control_unique_conversation_counter int(9) DEFAULT 0 NOT NULL,
                        experiment_unique_conversation_counter int(9) DEFAULT 0 NOT NULL,
                        amount_for_unique_expiry INT DEFAULT 30 CHECK (amount_for_unique_expiry >= 0 AND amount_for_unique_expiry <= 60),
                        unit_for_unique_expiry text DEFAULT 'days' NOT NULL,
                        PRIMARY KEY (table_version)
                    ) $charset_collate;";

            dbDelta($sql);

            $wpdb->insert(
                $table_name,
                [
                    'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                    'next_redirect' => 'Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                ]
            );
        }

        $table_name_unique = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . '_unique_users';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_unique'") != $table_name_unique) {

            $sql_unique = "CREATE TABLE $table_name_unique (
                    id int(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    table_version int NOT NULL,
                    username text NOT NULL,
                    expiration_date TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (table_version) REFERENCES $table_name(table_version)
                ) $charset_collate;";

            dbDelta($sql_unique);
        }
    }

    private static function delete_page_by_name($name)
    {


        $query = self::prepare_query_args($name);

        if ($query->have_posts()) {
            try {
                wp_delete_post($query->post->ID, true);
            } catch (\Throwable $th) {
                return false;
            }
        }
    }

    private static function remove_database_table()
    {
        global $wpdb;

        $table_name_unique = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . '_unique_users';
        $sql_unique = "DROP TABLE IF EXISTS $table_name_unique";
        $wpdb->query($sql_unique);


        $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }
}