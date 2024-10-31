<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


class octolio_bulkaction_class
{

    function __construct()
    {
        global $pagenow;

        if ('admin-ajax.php' == $pagenow) {
            if ('octolio' == octolio_get_var('string', 'plugin')) $this->handle_ajax_requests();
        } else {
            if ('edit.php' == $pagenow) {
                require_once dirname(dirname(__FILE__)).'/admin/views/bulkaction/listing.php';
                $bulkaction_listing = new octolio_bulkaction_listing();

                add_filter('manage_octolio_bulkaction_posts_columns', [$bulkaction_listing, 'bulkaction_manage_columns']);
                add_filter('manage_edit-octolio_bulkaction_sortable_columns', [$bulkaction_listing, 'bulkaction_register_sortable_columns']);
                add_filter('request', [$bulkaction_listing, 'bulkaction_type_column_orderby']);
            } elseif ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
                require_once dirname(dirname(__FILE__)).'/admin/views/bulkaction/metabox.php';
                $bulkaction_metabox = new bulkaction_metabox();
                add_action('add_meta_boxes', [$bulkaction_metabox, 'octolio_metabox_init']);
            }
        }
    }

    public function register_bulkaction_cpt()
    {
        $labels = [
            'name' => _x('Manual Actions', 'Post Type General Name', 'octolio'),
            'singular_name' => _x('Manual Action', 'Post Type Singular Name', 'octolio'),
            'menu_name' => __('Manual Actions', 'octolio'),
            'name_admin_bar' => __('Manual Actions', 'octolio'),
            'archives' => __('Item Manual Actions', 'octolio'),
            'attributes' => __('Item Attributes', 'octolio'),
            'parent_item_colon' => __('Parent Item:', 'octolio'),
            'all_items' => __('All Items', 'octolio'),
            'add_new_item' => __('New Manual Action', 'octolio'),
            'add_new' => __('Add New', 'octolio'),
            'new_item' => __('New Item', 'octolio'),
            'edit_item' => __('Edit Item', 'octolio'),
            'view_item' => __('View Item', 'octolio'),
            'view_items' => __('View Items', 'octolio'),
            'search_items' => __('Search Item', 'octolio'),
            'not_found' => __('Not found', 'octolio'),
            'not_found_in_trash' => __('Not found in Trash', 'octolio'),
            'featured_image' => __('Featured Image', 'octolio'),
            'set_featured_image' => __('Set featured image', 'octolio'),
            'remove_featured_image' => __('Remove featured image', 'octolio'),
            'use_featured_image' => __('Use as featured image', 'octolio'),
            'insert_into_item' => __('Insert into item', 'octolio'),
            'uploaded_to_this_item' => __('Uploaded to this item', 'octolio'),
            'items_list' => __('Items list', 'octolio'),
            'items_list_navigation' => __('Items list navigation', 'octolio'),
            'filter_items_list' => __('Filter items list', 'octolio'),
        ];

        $args = [
            'label' => __('Manual Actions', 'octolio'),
            'description' => __('List of bulk actions', 'octolio'),
            'labels' => $labels,
            'supports' => ['title', 'name'],
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => false,
        ];
        register_post_type('octolio_bulkaction', $args);
    }

    public function handle_ajax_requests()
    {
        $action = octolio_get_var('string', 'action', '');

        if ('save_db' == $action) {
            $db_class = octolio_get('class.database');
            $db_class->save_db();
            exit;
        }
    }

    function on_publish_bulkaction($new_status, $old_status, $post)
    {

        if ('publish' != $new_status || $post->post_type != 'octolio_bulkaction') return;

        if (is_admin() || is_network_admin()) $this->save_meta($post->ID);

        $query_class = octolio_get('class.query');
        $bulkaction_type = get_post_meta($post->ID, 'bulkaction_type', true);
        $bulkaction_filters = get_post_meta($post->ID, 'bulkaction_filters', true);
        $bulkaction_actions = get_post_meta($post->ID, 'bulkaction_actions', true);

        if (empty($bulkaction_type) || empty($bulkaction_filters)) return;

        $integration = octolio_get('integration.'.$bulkaction_type);
        $selected_entities = [];


        foreach ($bulkaction_filters as $or => $orValue) {
            $query_class->where = [];

            //Init where query based on the integration
            $integration->init_filter_query($query_class);
            if (empty($orValue)) {
                continue;
            }
            foreach ($orValue as $and => $andValue) {
                foreach ($andValue as $one_filter_type => $one_filter_params) {
                    apply_filters('octolio_apply_filters_'.$one_filter_type, $one_filter_params, $query_class);
                }
            }
            if (empty($query_class->errors)) {
                $selected_entities = array_unique(array_merge($selected_entities, octolio_load_result_array($query_class->getQuery([$integration->table_alias.'.'.$integration->table_pk]))));
            } else {
                array_unshift($query_class->errors, __('No action has been made since there was an issue'));
                octolio_enqueue_message($query_class->errors, 'error');

                return false;
            }
        }

        if (empty($selected_entities)) {
            octolio_enqueue_message(__('No action done as there is no entity corresponding to the selected filters'), 'warning');

            return false;
        }
        foreach ($bulkaction_actions as $one_action_number => $one_action) {
            $action_name = array_keys($one_action)[0];
            $action_params = array_shift($one_action);

            do_action('octolio_do_action_'.$action_name, $selected_entities, $action_params);
        }
    }

    private function save_meta($post_ID)
    {
        $bulkaction_type = octolio_get_var('string', 'bulkaction_type', '');
        update_post_meta($post_ID, 'bulkaction_type', $bulkaction_type);

        $bulkaction_filters = octolio_get_var('array', 'bulkaction_filters', []);
        update_post_meta($post_ID, 'bulkaction_filters', $bulkaction_filters);

        $bulkaction_actions = octolio_get_var('array', 'bulkaction_actions', []);
        update_post_meta($post_ID, 'bulkaction_actions', $bulkaction_actions);

        $new_db_backup = octolio_get_var('array', 'db_backup', []);
        $previous_db_backup = get_post_meta($post_ID, 'db_backup', true);
        if (empty($previous_db_backup)) $previous_db_backup = [];

        update_post_meta($post_ID, 'db_backup', array_merge($new_db_backup, $previous_db_backup));
    }
}