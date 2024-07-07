<?php
class DMG_Read_More_CLI {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_commands'));
    }

    public static function register_commands() {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('dmg-read-more', array(__CLASS__, 'search_command'));
        }
    }

    public static function search_command($args, $assoc_args) {
        global $wpdb;

        $development_mode = isset($assoc_args['dev']);

        //----- PERFORMANCE improvement: Show X results at a time; default 100000 (prevents timeout and memory usage)
        if (isset($assoc_args['batch'])) {
            $batch_size = intval($assoc_args['batch']);
            if ($batch_size <= 0) {
                WP_CLI::error('Invalid batch size. Please provide a positive integer greater than 0.');
                return;
            }
        } else {
            $batch_size = 100000;
        }

        /***** DEVELOPMENT: extra info *****/
        if ($development_mode) {
            $start_time = microtime(true);
            $start_memory = memory_get_usage();
        }

        $date_before = isset($assoc_args['date-before']) ? $assoc_args['date-before'] : date('Y-m-d', current_time('timestamp'));
        $date_after = isset($assoc_args['date-after']) ? $assoc_args['date-after'] : date('Y-m-d', strtotime('-30 days', current_time('timestamp')));

        if (!self::validate_date($date_before) || !self::validate_date($date_after)) {
            WP_CLI::error('Invalid date format. Use YYYY-MM-DD.');
            return;
        }

        $paged = 1;
        $found_posts = 0;
        $retried = false;

        //----- PERFORMANCE improvement: Resume from the last processed post ID to avoid reprocessing
        $last_processed_post_id = get_option('dmg_last_processed_post_id', 0);

        // Keeps the script running even with poor and even losing internet connection
        $max_retries = 6; // Max number of retries: after 1, 2, 4, 8, 16, 32 seconds
        $retry_delay = 1; // Seconds

        do {
            $retry_count = 0;
            $query_successful = false;

            do {
                try {
                    $query_args = array(
                        'post_type'      => 'post',
                        'post_status'    => 'publish',
                        'date_query'     => array(
                            array(
                                'after'     => $date_after,
                                'before'    => $date_before,
                                'inclusive' => true,
                            ),
                        ),
                        's'              => '<!-- wp:dmg/read-more ', // Searching for the Gutenberg block
                        'fields'         => 'ids', //----- PERFORMANCE improvement: Return only post IDs for better performance
                        'posts_per_page' => $batch_size,
                        'paged'          => $paged,
                        'orderby'        => 'ID',
                        'order'          => 'ASC',
                        'post__not_in'   => $last_processed_post_id ? array($last_processed_post_id) : array(),
                    );

                    $query = new WP_Query($query_args);

                    if ($query->have_posts()) {

                        //----- PERFORMANCE improvement: Log all post IDs in the batch at once
                        $results = implode(PHP_EOL, $query->posts);
                        WP_CLI::log($results);
                        $found_posts += count($query->posts);

                        // Update the checkpoint with the last post ID in the batch
                        $last_processed_post_id = end($query->posts);
                        update_option('dmg_last_processed_post_id', $last_processed_post_id);

                        $query_successful = true;
                    } else {
                        $query_successful = true;
                        break;
                    }

                    wp_reset_postdata();
                } catch (Exception $e) {
                    $retry_count++;
                    if ($retry_count > $max_retries) {
                        WP_CLI::error("Error executing query: " . $e->getMessage());
                        break;
                    }
                    WP_CLI::warning("Query failed. Retrying in {$retry_delay} seconds... (Attempt {$retry_count}/{$max_retries})");

                    $retried = true;
                    sleep($retry_delay);
                    $retry_delay *= 2; //----- PERFORMANCE improvement: Exponential backoff to handle temporary failures including connection failures
                }
            } while (!$query_successful && $retry_count <= $max_retries);

            if (!$query_successful) {
                break;
            }

            $paged++;
        } while ($query->max_num_pages >= $paged);

        if ($found_posts === 0) {
            WP_CLI::log('No posts found.');
        } else {
            delete_option('dmg_last_processed_post_id');
        }

        /***** DEVELOPMENT: extra info *****/
        if ($development_mode) {
            $end_time = microtime(true);
            $end_memory = memory_get_usage();

            $execution_time = $end_time - $start_time;
            $memory_used = $end_memory - $start_memory;

            WP_CLI::log(" "); // Empty line for better readability
            WP_CLI::log("Number of posts found: " . $found_posts);
            WP_CLI::log("Execution time: " . round($execution_time, 2) . " seconds");
            WP_CLI::log("Memory used: " . round($memory_used / (1024 * 1024), 2) . " MB");
        }

        if ($retried) {
            WP_CLI::log("If you experience network issues, consider using --max_execution_time=0 to set an infinite timeout.");
        }
    }

    private static function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

DMG_Read_More_CLI::init();
?>
