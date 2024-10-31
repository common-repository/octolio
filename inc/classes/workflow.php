<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


class octolio_workflow_class
{

    function __construct()
    {
        global $pagenow;

        if ('admin-ajax.php' == $pagenow) {
            if ('octolio' == octolio_get_var('string', 'plugin')) $this->handle_ajax_requests();
        } elseif ('edit.php' == $pagenow) {
            require_once dirname(dirname(__FILE__)).'/admin/views/workflow/listing.php';
            $workflow_listing = new octolio_workflow_listing();

            add_filter('manage_octolio_workflow_posts_columns', [$workflow_listing, 'workflow_manage_columns']);
            add_filter('manage_edit-octolio_workflow_sortable_columns', [$workflow_listing, 'workflow_register_sortable_columns']);
            add_filter('request', [$workflow_listing, 'workflow_type_column_orderby']);
        } elseif ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
            require_once dirname(dirname(__FILE__)).'/admin/views/workflow/metabox.php';
            $workflow_metabox = new workflow_metabox();
            add_action('add_meta_boxes', [$workflow_metabox, 'octolio_metabox_init']);
        }
    }


    public function register_workflow_cpt()
    {
        $labels = [
            'name' => _x('Automatic Actions', 'Post Type General Name', 'octolio'),
            'singular_name' => _x('Automatic Action', 'Post Type Singular Name', 'octolio'),
            'menu_name' => __('Automatic Actions', 'octolio'),
            'name_admin_bar' => __('Automatic Actions', 'octolio'),
            'archives' => __('Item Workflows', 'octolio'),
            'attributes' => __('Item Attributes', 'octolio'),
            'parent_item_colon' => __('Parent Item:', 'octolio'),
            'all_items' => __('All Items', 'octolio'),
            'add_new_item' => __('New Automatic Actions', 'octolio'),
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
            'label' => __('Automatic Actions', 'octolio'),
            'description' => __('List of Automatic Actions', 'octolio'),
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
        register_post_type('octolio_workflow', $args);
    }

    public function register_hook_actions()
    {
        if (!has_action('profile_update', 'execute_profile_update_hook')) {
            add_action('execute_octolio_hook_actions', [$this, 'execute_hook_actions'], 10, 2);
        }

        $integration_helper = octolio_get('helper.integration');
        $integration_helper->add_hook_actions();
    }

    function execute_hook_actions($wp_hook_triggered = '', $entity_id = 0)
    {

        $posts = get_posts(
            [
                'post_type' => 'octolio_workflow',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]
        );

        if (empty($posts)) return;

        foreach ($posts as $one_post) {
            $query_class = octolio_get('class.query');
            $workflow_type = get_post_meta($one_post->ID, 'workflow_type', true);
            $workflow_hook = get_post_meta($one_post->ID, 'workflow_hook', true);
            $workflow_filters = get_post_meta($one_post->ID, 'workflow_filters', true);
            $workflow_actions = get_post_meta($one_post->ID, 'workflow_actions', true);


            if (empty($workflow_type) || empty($workflow_filters)) return;
            if ($workflow_hook != "hook_".$wp_hook_triggered) return;

            $integration = octolio_get('integration.'.$workflow_type);
            $selected_entities = [];
            $result = true;
            foreach ($workflow_filters as $or => $orValue) {

                if (empty($orValue)) continue;

                //!empty(entity_id) ? That means we will execute actions on entities stored in BD (users / posts / orders)
                if (!empty($entity_id)) {
                    $query_class->where = [];

                    //We want to apply actions on the entity so we make sure only this one will be selected
                    $query_class->where[] = $query_class->convertQuery($integration->table_alias, $integration->table_pk, '=', intval($entity_id));

                    //Init where query based on the integration
                    $integration->init_filter_query($query_class);

                    foreach ($orValue as $and => $andValue) {
                        foreach ($andValue as $one_filter_type => $one_filter_params) {
                            $result = apply_filters('octolio_apply_filters_'.$one_filter_type, $one_filter_params, $query_class);
                        }
                    }
                    if (empty($query_class->errors)) {
                        $selected_entities = array_unique(array_merge($selected_entities, octolio_load_result_array($query_class->getQuery([$integration->table_alias.'.'.$integration->table_pk]))));
                    } else {
                        array_unshift($query_class->errors, __('No action has been made since there was an issue'));
                        octolio_enqueue_message($query_class->errors, 'error');
                        octolio_log_enqueued_messages();

                        return false;
                    }
                } else {
                    foreach ($orValue as $and => $andValue) {
                        foreach ($andValue as $one_filter_type => $one_filter_params) {
                            $result = apply_filters('octolio_apply_filters_'.$one_filter_type, $one_filter_params, $query_class);
                        }
                    }
                }
            }

            if (!$result) {
                $integration->clean_action($workflow_actions);
                octolio_enqueue_message(__('No action done as there is no entity corresponding to the selected filters'), 'warning');
                octolio_log_enqueued_messages();

                return false;
            }
            foreach ($workflow_actions as $one_action_number => $one_action) {
                $action_name = array_keys($one_action)[0];
                $action_params = array_shift($one_action);

                do_action('octolio_do_action_'.$action_name, $selected_entities, $action_params);
            }
        }
        octolio_log_enqueued_messages();
    }

    function on_publish_workflow($new_status, $old_status, $post)
    {

        if ('publish' != $new_status || $post->post_type != 'octolio_workflow') return;

        if (is_admin() || is_network_admin()) $this->save_meta($post->ID);
    }


    private function save_meta($post_ID)
    {
        $workflow_type = octolio_get_var('string', 'workflow_type', '');
        update_post_meta($post_ID, 'workflow_type', $workflow_type);

        $workflow_hook = octolio_get_var('string', 'workflow_hook', '');
        update_post_meta($post_ID, 'workflow_hook', $workflow_hook);

        $workflow_filters = octolio_get_var('array', 'workflow_filters', []);
        update_post_meta($post_ID, 'workflow_filters', $workflow_filters);

        $workflow_actions = octolio_get_var('array', 'workflow_actions', []);
        update_post_meta($post_ID, 'workflow_actions', $workflow_actions);
    }
}