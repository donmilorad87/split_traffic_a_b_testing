<?php



trait Helpers
{


    private static function insert_to_database(array $args)
    {
        global $wpdb;

        if ($args['table_name'] === 1) {
            $table_name =  $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;
        } else if ($args['table_name'] === 2) {
            $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . '_unique_users';
        }

        $data_to_insert = $args['data_to_insert'];

        try {
            $wpdb->query('START TRANSACTION');
            $wpdb->insert(
                $table_name,
                $data_to_insert
            );
            $wpdb->query('COMMIT');
        } catch (\Throwable $th) {
            $wpdb->query('ROLLBACK');
            throw $th;
        }
    }

    private static function update_database(array $args)
    {
        global $wpdb;

        if ($args['table_name'] === 1) {
            $table_name =  $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME;
        } else if ($args['table_name'] === 2) {
            $table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . '_unique_users';
        }

        $data_to_update = $args['data_to_update'] ?? '';
        $where_condition = $args['where_condition'] ?? [
            'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION
        ];

        try {

            $wpdb->query('START TRANSACTION');
            $wpdb->update($table_name, $data_to_update, $where_condition);


            // Commit the transaction
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {

            $wpdb->query('ROLLBACK');

            throw $e;
        }
    }

    private static function return_database_results(array $args)
    {

        global $wpdb;

        $desired_field          = $args['desired_field'];
        $method                 = $args['method'];
        $condition              = $args['condition'] ?? 1;
        $unique_pointer         = $args['unique_pointer'] ?? false;

        $obj = new stdClass();

        // Add properties to the object
        $obj->wpdb = $wpdb;

        $pointer = '';
        if ($unique_pointer) {
            $pointer = '_unique_users';
        }

        // Define your custom table name
        $obj->table_name = $wpdb->prefix . SPLIT_TRAFFIC_A_B_TESTING_NAME . $pointer;
        // Your SQL query to retrieve the field value
        if ($condition !== 1) {
            $obj->sql = $wpdb->prepare("SELECT $desired_field FROM $obj->table_name WHERE table_version=%d AND username='%s'", $condition['table_verison'], $condition['username']);
        } else {
            $obj->sql = $wpdb->prepare("SELECT $desired_field FROM $obj->table_name WHERE %d", $condition);
        }


        // Get the result from the database
        if ($method === 'get_var') {
            $obj->result = $wpdb->get_var($obj->sql);
        } else if ($method === 'get_results') {
            $obj->result = $wpdb->get_results($obj->sql, OBJECT);
        }

        //return json_encode([$wpdb, $table_name, $sql, $result]);

        return $obj;
    }

    private static function get_page_slug()
    {
        return basename(esc_url(home_url($_SERVER['REQUEST_URI'])));
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

            self::insert_to_database(
                [
                    'table_name' => 1,
                    'data_to_insert' => [
                        'table_version' => SPLIT_TRAFFIC_A_B_TESTING_TABLE_VERSION,
                        'next_redirect' => 'Experiment A - ' . SPLIT_TRAFFIC_A_B_TESTING_LASTNAME_STRING
                    ]
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
