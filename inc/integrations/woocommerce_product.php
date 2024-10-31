<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_woocommerce_product_integration extends octolio_integration
{
    public $type = 'woocommerce_product';

    public $from = 'posts';

    public $table_alias = 'wp_posts';

    public $table_pk = 'ID';


    function is_plugin_active()
    {
        return is_plugin_active('woocommerce/woocommerce.php');
    }

    function get_displayed_name()
    {
        if (empty(get_post_type())) return;

        return ('octolio_bulkaction' == get_post_type() ? __('WooCommerce Products') : __('WooCommerce Products'));
    }

    function init_filter_query($query_class)
    {
        global $wpdb;

        $query_class->from = $this->from.' AS '.$this->table_alias;
        $query_class->join['wp_postmeta'] = $wpdb->prefix.'postmeta AS wp_postmeta ON wp_posts.id=wp_postmeta.post_id';
        $query_class->where[] = $query_class->convertQuery($this->table_alias, 'post_type', '!=', 'octolio_bulkaction');
        $query_class->where[] = $query_class->convertQuery($this->table_alias, 'post_type', '=', 'product');

        return $query_class;
    }

    public function init_hooks()
    {
        // $this->hooks[$this->type]['hook_woocommerce_product_create_order'] = __('Is created', 'octolio');

        $this->hooks['user']['hook_user_complete_purchase'] = __('Purchase a product', 'octolio');
    }

    public function init_filters()
    {
        $this->filters[$this->type]['woocommerce_product_regular_price'] = (object)['value' => 'woocommerce_product_regular_price', 'text' => __('Product Regular Price', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_product_sale_price'] = (object)['value' => 'woocommerce_product_sale_price', 'text' => __('Product Sale Price', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_product_tags'] = (object)['value' => 'woocommerce_product_tags', 'text' => __('Product Tags', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_product_category'] = (object)['value' => 'woocommerce_product_category', 'text' => __('Product Category', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_product_stock_status'] = (object)['value' => 'woocommerce_product_stock_status', 'text' => __('Product Stock Status', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_product_stock'] = (object)['value' => 'woocommerce_product_stock', 'text' => __('Product Stock', 'octolio'), 'disable' => false];

        $this->filters[$this->type]['user_purchase_products'] = (object)['value' => 'user_purchase_products', 'text' => __('Users Purchases (Products)', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['user_purchase_categories'] = (object)['value' => 'user_purchase_categories', 'text' => __('Users Purchases (Categories)', 'octolio'), 'disable' => false];

        $this->filters[$this->type]['woocommerce_product_SKU'] = (object)['value' => 'woocommerce_product_SKU', 'text' => __('Product SKU (Pro version)', 'octolio'), 'disable' => true];
        $this->filters[$this->type]['woocommerce_product_weight'] = (object)['value' => 'woocommerce_product_weight', 'text' => __('Product Weight  (Pro version)', 'octolio'), 'disable' => true];
        $this->filters[$this->type]['woocommerce_product_visibility'] = (object)['value' => 'woocommerce_product_visibility', 'text' => __('Product Visibility (Pro version)', 'octolio'), 'disable' => true];
        $this->filters[$this->type]['woocommerce_product_featured'] = (object)['value' => 'woocommerce_product_featured', 'text' => __('Product "Featured" option value (Pro version)', 'octolio'), 'disable' => true];
        $this->filters[$this->type]['woocommerce_product_url'] = (object)['value' => 'woocommerce_product_url', 'text' => __('Product URL (Pro version)', 'octolio'), 'disable' => true];


        foreach ($this->filters as $one_type => $one_type_filters) {
            foreach ($one_type_filters as $one_filter_type => $one_filter_label) {
                if (!has_filter('octolio_filter_params_'.$one_filter_type)) {
                    add_filter('octolio_filter_params_'.$one_filter_type, [$this, 'get_filter_params_'.$one_filter_type], 10, 2);
                    add_filter('octolio_apply_filters_'.$one_filter_type, [$this, 'apply_filter_'.$one_filter_type], 10, 3);
                }
            }
        }
    }

    public function init_actions()
    {
        $this->actions[$this->type]['woocommerce_product_move_trash'] = (object)['value' => 'woocommerce_product_move_trash', 'text' => __('Move product to trash', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['woocommerce_product_delete'] = (object)['value' => 'woocommerce_product_delete', 'text' => __('Delete product', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['woocommerce_product_add_tags'] = (object)['value' => 'woocommerce_product_add_tags', 'text' => __('Add tag', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['woocommerce_product_add_category'] = (object)['value' => 'woocommerce_product_add_category', 'text' => __('Add Category', 'octolio'), 'disable' => false];

        $this->actions[$this->type]['woocommerce_product_set_price'] = (object)['value' => 'woocommerce_product_set_price', 'text' => __('Change product price (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_stock_status'] = (object)['value' => 'woocommerce_product_set_stock_status', 'text' => __('Change product stock status (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_weight'] = (object)['value' => 'woocommerce_product_set_weight', 'text' => __('Change product weight (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_dimensions'] = (object)['value' => 'woocommerce_product_set_dimensions', 'text' => __('Change product dimensions (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_visibility'] = (object)['value' => 'woocommerce_product_set_visibility', 'text' => __('Change product visibility (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_featured'] = (object)['value' => 'woocommerce_product_set_featured', 'text' => __('Change product "featured" option (Pro Version)', 'octolio'), 'disable' => true];
        $this->actions[$this->type]['woocommerce_product_set_stock'] = (object)['value' => 'woocommerce_product_set_stock', 'text' => __('Change product stock (Pro Version)', 'octolio'), 'disable' => true];


        foreach ($this->actions as $one_type => $one_type_action) {
            foreach ($one_type_action as $one_action_name => $one_action_label) {
                if (!has_action('octolio_action_params_'.$one_action_name)) {
                    add_action('octolio_action_params_'.$one_action_name, [$this, 'get_action_params_'.$one_action_name], 10, 2);
                    add_action('octolio_do_action_'.$one_action_name, [$this, 'do_action_'.$one_action_name], 10, 2);
                }
            }
        }
    }

    function add_hook_actions()
    {
        foreach ($this->hooks as $one_type => $one_hook_type) {
            foreach ($one_hook_type as $one_hook_type => $one_hook_label) {
                $hook_function_name = 'register_hook_action_'.str_replace('hook_', '', $one_hook_type);
                $this->$hook_function_name();
            }
        }
    }

    /*************** Product regular price ***************/

    function get_filter_params_woocommerce_product_regular_price($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product regular price');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_regular_price');
    }

    function apply_filter_woocommerce_product_regular_price($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_regular_price', $filter_values['operator'], $filter_values['value'], true);

        return true;
    }

    /*************** Product sale price ***************/

    function get_filter_params_woocommerce_product_sale_price($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product sale price');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_sale_price');
    }

    function apply_filter_woocommerce_product_sale_price($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_price', $filter_values['operator'], $filter_values['value'], true);

        return true;
    }

    /*************** Product stock status ***************/

    function get_filter_params_woocommerce_product_stock_status($filter_values = [], $filter_name = '')
    {

        $stock_values[] = octolio_select_option('instock', 'In stock');
        $stock_values[] = octolio_select_option('outofstock', 'Out of stock');
        $stock_values[] = octolio_select_option('onbackorder', 'On backorder');

        $filter_stock_status = empty($filter_values['status']) ? 'instock' : $filter_values['status'];
        $filter_to_display = octolio_select($stock_values, $filter_name.'[woocommerce_product_stock_status][status]', $filter_stock_status, 'octolio__select');

        return sprintf(__('Product stock status is: %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_product_stock_status($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_stock_status', ' = ', $filter_values['status']);

        return true;
    }

    /*************** Product SKU ***************/

    function get_filter_params_woocommerce_product_SKU($filter_values = [], $filter_name = '')
    {

        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product SKU');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_SKU');
    }

    function apply_filter_woocommerce_product_SKU($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->add_post_meta_filter($query, '_sku', $filter_values['operator'], $filter_values['value'], false);

        return true;
    }

    /*************** Product weight ***************/

    function get_filter_params_woocommerce_product_weight($filter_values = [], $filter_name = '')
    {

        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product weight');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_weight');
    }

    function apply_filter_woocommerce_product_weight($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_weight', $filter_values['operator'], $filter_values['value'], true);

        return true;
    }

    /*************** Product Visibility ***************/

    function get_filter_params_woocommerce_product_visibility($filter_values = [], $filter_name = '')
    {
        $visibility_values[] = octolio_select_option('visible', __('Shop and search results'));
        $visibility_values[] = octolio_select_option('catalog', __('Shop only'));
        $visibility_values[] = octolio_select_option('search', __('Search results only'));
        $visibility_values[] = octolio_select_option('hidden', __('Hidden'));

        $visibility = empty($filter_values['visibility']) ? 'visible' : $filter_values['visibility'];
        $filter_to_display = octolio_select($visibility_values, $filter_name.'[woocommerce_product_visibility][visibility]', $visibility, 'octolio__select');

        return sprintf('Product visibility is: %s', $filter_to_display);
    }

    function apply_filter_woocommerce_product_visibility($filter_values = [], $query = object)
    {

        if (empty($filter_values['visibility'])) return $query->break_query(__('There was an issue with product_visibility filter value'));

        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Product');


        //Catalog is visibility NOT 6 and NOT 7
        if ('visible' == $filter_values['visibility']) {
            $query->where[] = 'NOT EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE(term_taxonomy_id=6 OR term_taxonomy_id=7) AND wp_posts.id=wp_term_relationships.object_id)';
        } //Catalog is visibility = 6 and NOT 7
        elseif ('catalog' == $filter_values['visibility']) {
            $query->where[] = 'NOT EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE term_taxonomy_id=7 AND wp_posts.id=wp_term_relationships.object_id)';
        } //Search is visibility = 7 and NOT 6
        elseif ('search' == $filter_values['visibility']) {
            $query->where[] = 'NOT EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE term_taxonomy_id=6 AND wp_posts.id=wp_term_relationships.object_id)';
        } //Search is visibility = 7 and NOT 6
        elseif ('hidden' == $filter_values['visibility']) {
            //Hidden means status 6 and 7
            $query->where[] = 'EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE term_taxonomy_id=6 AND wp_posts.id=wp_term_relationships.object_id)';
            $query->where[] = 'EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE term_taxonomy_id=7 AND wp_posts.id=wp_term_relationships.object_id)';
        } else {
            return $query->break_query(__('There was an issue with product_visibility filter value'));
        }

        return true;
    }

    /*************** Product Featured ***************/

    function get_filter_params_woocommerce_product_featured($filter_values = [], $filter_name = '')
    {
        $featured_values[] = octolio_select_option('featured', __('Featured'));
        $featured_values[] = octolio_select_option('notfeatured', __('Not Featured'));

        $featured = empty($filter_values['featured']) ? 'featured' : $filter_values['featured'];
        $filter_to_display = octolio_select($featured_values, $filter_name.'[woocommerce_product_featured][featured]', $featured, 'octolio__select');

        return sprintf('Product is: %s', $filter_to_display);
    }

    function apply_filter_woocommerce_featured($filter_values = [], $query = object)
    {
        if (empty($filter_values['featured'])) return $query->break_query(__('There was an issue with product_featured filter value'));

        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Product');

        $filter = [];

        if ('notfeatured' == $filter_values['featured']) {
            $query->where[] = 'NOT EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE(term_taxonomy_id=8) AND wp_posts.id=wp_term_relationships.object_id)';
        } elseif ('featured' == $filter_values['featured']) {
            $filter['visibility'] = '8';
            $query = $post_helper->add_post_taxonomy_filter($query, $filter, 'product_visibility', 'visibility');
            $query->where[] = 'EXISTS(SELECT wp_term_relationships.object_id FROM wp_term_relationships WHERE term_taxonomy_id=8 AND wp_posts.id=wp_term_relationships.object_id)';
        } else {
            return $query->break_query(__('There was an issue with product_visibility filter value'));
        }

        return true;
    }

    /*************** Product stock ***************/

    function get_filter_params_woocommerce_product_stock($filter_values = [], $filter_name = '')
    {

        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product stock');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_stock');
    }

    function apply_filter_woocommerce_product_stock($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_stock', $filter_values['operator'], $filter_values['value'], true);

        return true;
    }

    /*************** Product URL ***************/

    function get_filter_params_woocommerce_product_url($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product URL');

        return $post_helper->get_filter_params_postmeta($filter_values, $filter_name, 'woocommerce_product_url');
    }

    function apply_filter_woocommerce_product_url($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_meta_filter($query, '_product_url', $filter_values['operator'], $filter_values['value'], false);

        return true;
    }

    /*************** Product Tags ***************/

    function get_filter_params_woocommerce_product_tags($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[woocommerce_product_tags]', 'product_tag', 'tag');
    }

    function apply_filter_woocommerce_product_tags($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Product');
        $query = $post_helper->add_post_taxonomy_filter($query, $filter_values, 'product_tag', 'tag');

        return true;
    }

    /*************** Product Category ***************/

    function get_filter_params_woocommerce_product_category($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product');

        return $post_helper->get_action_params_post_add_taxonomy($filter_values, $filter_name.'[woocommerce_product_category]', 'product_cat', 'category');
    }

    function apply_filter_woocommerce_product_category($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_taxonomy_filter($query, $filter_values, 'product_cat', 'category');

        return true;
    }

    /*************** User - Purchases (Article) ***************/

    function get_filter_params_user_purchase_products($filter_values = [], $filter_name = '')
    {
        $products = wc_get_products(['limit' => ' - 1']);
        if (empty($products)) return __('No product found on your website.');

        $selected_products = empty($filter_values['products']) ? [] : $filter_values['products'];
        $filter_to_display = '<select id="octolio_select2_products" class="octolio_select2" name="'.$filter_name.'[user_purchase_products][products][]'.'" multiple="multiple" > ';
        foreach ($products as $one_product) {
            $selected = (in_array($one_product->get_id(), $selected_products) ? ' selected = "selected"' : '');
            $filter_to_display .= '<option value="'.$one_product->get_id().'"'.$selected.' > '.$one_product->get_name().'</option>';
        }
        $filter_to_display .= '<select></p>';

        return sprintf(__('User who purchase one of these products:<br /> %s'), $filter_to_display);
    }

    function apply_filter_user_purchase_products($filter_values = [], $query = object)
    {
        global $wpdb;

        $products = wc_get_products(['limit' => ' - 1', 'return ' => 'ids']);

        if (empty($products) || empty($filter_values['products'])) {
            return $query->break_query(__('There was an issue with user_purchase filter value'));
        }


        foreach ($filter_values['products'] as $one_product_id) {
            if (!in_array($one_product_id, $products)) return $query->break_query(__('There was an issue with user_purchase filter value'));
        }


        $query->join['wp_postmeta'] = $wpdb->prefix.'postmeta AS wp_postmeta ON wp_users.id=wp_postmeta.meta_value';
        $query->join['wp_posts'] = $wpdb->prefix.'posts AS wp_posts ON wp_posts.id=wp_postmeta.post_id';


        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        //Add WooCommerce post Type condition
        $filter_values = ['type' => wc_get_order_types()];
        $query = $post_helper->add_post_type_filter($query, $filter_values);

        //Add WooCommerce Paid Status condition
        //don't know why but post status are stored as wp - [paid_status]
        //but this function only returns [paid_status]
        //example : post_status = "wp-processed" but this functions return "processed"
        $paid_status = wc_get_is_paid_statuses();
        foreach ($paid_status as $key => $one_paid_status) {
            $paid_status[$key] = 'wc-'.$one_paid_status;
        }
        $filter_values = ['status' => $paid_status];
        $query = $post_helper->add_post_status_filter($query, $filter_values);

        //Add postmeta _customer_user
        $query->where[] = $query->convertQuery('wp_postmeta', 'meta_key', '=', '_customer_user');


        return true;
    }

    /*************** User - Purchases (Category) ***************/

    function get_filter_params_user_purchase_categories($filter_values = [], $filter_name = '')
    {

        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Users who purchased at least one product');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[user_purchase_categories][category]', 'product_cat', 'category');
    }

    function apply_filter_user_purchase_categories($filter_values = [], $query = object)
    {
        global $wpdb;

        $query->join['wp_postmeta'] = $wpdb->prefix.'postmeta AS wp_postmeta ON wp_users.id=wp_postmeta.meta_value';

        //First join on posts. This one will allow to apply filters based on the order
        $query->join['wp_posts_order'] = $wpdb->prefix.'posts AS wp_posts_order ON wp_posts_order.id=wp_postmeta.post_id';

        $query->join['wp_order_items'] = $wpdb->prefix.'woocommerce_order_items AS wp_order_items ON wp_postmeta.post_id=wp_order_items.order_id';
        $query->join['wp_itemmeta'] = $wpdb->prefix.'woocommerce_order_itemmeta AS wp_itemmeta ON wp_itemmeta.order_item_id=wp_order_items.order_item_id';

        //Second join on posts. This one will allow to apply filters based on products the order contains
        $query->join['wp_posts'] = $wpdb->prefix.'posts AS wp_posts ON wp_posts.id=wp_itemmeta.meta_value';


        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        //Add WooCommerce post Type condition
        $post_type = ['type' => wc_get_order_types()];
        $query = $post_helper->add_post_type_filter($query, $post_type, 'wp_posts_order');

        //Add WooCommerce Paid Status condition
        //don't know why but post status are stored as wp-[paid_status]
        //but this function only returns [paid_status]
        //example : post_status = "wp-processed" but this functions return "processed"
        $paid_status = wc_get_is_paid_statuses();
        foreach ($paid_status as $key => $one_paid_status) {
            $paid_status[$key] = 'wc-'.$one_paid_status;
        }
        $post_status = ['status' => $paid_status];
        $query = $post_helper->add_post_status_filter($query, $post_status, 'wp_posts_order');

        //Add postmeta _customer_user
        $query->where[] = $query->convertQuery('wp_postmeta', 'meta_key', '=', '_customer_user');

        $query = $post_helper->add_post_taxonomy_filter($query, $filter_values, 'product_cat', 'category');

        return true;
    }

    /*
     *
     * ACTIONS
     *
     */

    /*************** Move post to trash ***************/

    function get_action_params_woocommerce_product_move_trash($action_values = [], $action_name = '')
    {
        return '<input type="hidden" name="'.$action_name.'[woocommerce_product_move_trash]"/>';
    }

    function do_action_woocommerce_product_move_trash($products = [], $action_params = [])
    {
        $nb_post_deleted = 0;
        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(__('There was an error while trying to move product to trash'), 'error');

                return;
            }

            $product->delete();
            $result = 'trash' === $product->get_status();


            if (!$result) {
                octolio_enqueue_message(__('There was an error while trying to move product to trash'), 'error');
            } else {
                $nb_post_deleted++;
            }

            // Delete parent product transients.
            if ($parent_id = wp_get_post_parent_id($one_product)) {
                wc_delete_product_transients($parent_id);
            }
        }
        octolio_enqueue_message(sprintf(__('%s %s moved to trash'), $nb_post_deleted, __('Products'), 'success'));
    }

    /*************** Delete Post ***************/

    function get_action_params_woocommerce_product_delete($action_values = [], $action_name = '')
    {
        return '<input type="hidden" name="'.$action_name.'[woocommerce_product_delete]"/>';
    }

    function do_action_woocommerce_product_delete($products = [], $action_params = [])
    {
        $nb_post_deleted = 0;
        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $child_id) {
                    $child = wc_get_product($child_id);
                    $child->delete(true);
                }
            } elseif ($product->is_type('grouped')) {
                foreach ($product->get_children() as $child_id) {
                    $child = wc_get_product($child_id);
                    $child->set_parent_id(0);
                    $child->save();
                }
            }

            $product->delete(true);
            $result = $product->get_id() > 0 ? false : true;

            if (!$result) {
                octolio_enqueue_message(__('There was an error while trying to delete product'), 'error');
            } else {
                $nb_post_deleted++;
            }

            // Delete parent product transients.
            if ($parent_id = wp_get_post_parent_id($one_product)) {
                wc_delete_product_transients($parent_id);
            }
        }
        octolio_enqueue_message(sprintf(__('%s %s deleted'), $nb_post_deleted, __('Products'), 'success'));
    }

    /*************** Set price **************/


    function get_action_params_woocommerce_product_set_price($action_values = [], $action_name = '')
    {
        $sale_price = !isset($action_values['sale_price']) ? '' : $action_values['sale_price'];
        $sale_price_element = sprintf(__('Sale price: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_price][sale_price]" value="'.$sale_price.'"/>');

        $regular_price = !isset($action_values['regular_price']) ? '' : $action_values['regular_price'];
        $regular_price_element = sprintf(__('Regular price: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_price][regular_price]" value="'.$regular_price.'"/>');


        return $regular_price_element.'<br/>'.$sale_price_element;
    }

    function do_action_woocommerce_product_set_price($products = [], $action_params = [])
    {
        if (empty($action_params['sale_price']) && empty($action_params['regular_price'])) {
            octolio_enqueue_message(__('There was an error while trying to set product price. New prices are empty'), 'error');

            return;
        }

        $nb_product_updated = 0;
        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);


            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product price. No product with ID: '), $product->get_id()), 'error');

                continue;
            }

            if (isset($action_params['regular_price'])) {
                if (!is_numeric((float)$action_params['regular_price'])) {
                    octolio_enqueue_message(sprintf(__('There was an error while trying to set product regular price for product ID: '), $product->get_id()), 'error');

                    continue;
                }
                $product->set_regular_price($action_params['regular_price']);
            }

            if (isset($action_params['sale_price'])) {
                if (!is_numeric((float)$action_params['sale_price'])) {
                    octolio_enqueue_message(sprintf(__('There was an error while trying to set product regular price for product ID: '), $product->get_id()), 'error');

                    continue;
                }
                if ($action_params['sale_price'] >= $product->get_regular_price()) {
                    octolio_enqueue_message(sprintf(__('There was an error while trying to set product sale price for product ID: '), $product->get_id()), 'error');

                    continue;
                }
                $product->set_sale_price($action_params['sale_price']);
            }

            $product->save();


            if (!empty($action_params['sale_price']) && $action_params['sale_price'] != $product->get_sale_price()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product regular price for product ID: '), $product->get_id()), 'error');

                continue;
            } elseif (!empty($action_params['regular_price']) && $action_params['regular_price'] != $product->get_regular_price()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product sale price for product ID: '), $product->get_id()), 'error');

                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set product stock status **************/

    function get_action_params_woocommerce_product_set_stock_status($action_values = [], $action_name = '')
    {
        $stock_values[] = octolio_select_option('instock', 'In stock');
        $stock_values[] = octolio_select_option('outofstock', 'Out of stock');
        $stock_values[] = octolio_select_option('onbackorder', 'On backorder');

        $action_stock_status = empty($action_values['status']) ? 'instock' : $action_values['status'];
        $filter_to_display = octolio_select($stock_values, $action_name.'[woocommerce_product_set_stock_status][status]', $action_stock_status, 'octolio__select');

        return sprintf('Change product status to: %s', $filter_to_display);
    }

    function do_action_woocommerce_product_set_stock_status($products = [], $action_params = [])
    {
        if (empty($action_params['status'])) {
            octolio_enqueue_message(__('There was an error while trying to set product status. New status is empty'), 'error');

            return;
        }

        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            //No way to set status to external products
            if ('external' == $product->get_type()) continue;

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product status. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            $product->set_stock_status($action_params['status']);
            $product->save();

            if ($action_params['status'] != $product->get_stock_status()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product stock status for product ID: '), $product->get_id()), 'error');
                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set weight **************/

    function get_action_params_woocommerce_product_set_weight($action_values = [], $action_name = '')
    {
        $weight = !isset($action_values['weight']) ? '' : $action_values['weight'];

        return sprintf(__('New weight: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_weight][weight]" value="'.$weight.'"/>');
    }

    function do_action_woocommerce_product_set_weight($products = [], $action_params = [])
    {
        if (empty($action_params['weight']) || !is_numeric($action_params['weight'])) {
            octolio_enqueue_message(__('There was an error while trying to set new weight. New weight is empty or not numeric'), 'error');

            return;
        }

        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product weight. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            $product->set_weight($action_params['weight']);
            $product->save();

            if ($action_params['weight'] != $product->get_weight()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set new weight for product ID: '), $product->get_id()), 'error');
                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set dimensions **************/

    function get_action_params_woocommerce_product_set_dimensions($action_values = [], $action_name = '')
    {
        $length = !isset($action_values['length']) ? '' : $action_values['length'];
        $length_element = sprintf(__('Length: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_dimensions][length]" value="'.$length.'"/>');

        $width = !isset($action_values['width']) ? '' : $action_values['width'];
        $width_element = sprintf(__('Width: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_dimensions][width]" value="'.$width.'"/>');

        $height = !isset($action_values['height']) ? '' : $action_values['height'];
        $heigt_element = sprintf(__('Height: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_dimensions][height]" value="'.$height.'"/>');

        return $length_element.'<br/>'.$width_element.'<br/>'.$heigt_element;
    }

    function do_action_woocommerce_product_set_dimensions($products = [], $action_params = [])
    {
        $dimension_values = ['length', 'width', 'height'];

        $one_value_set = false;

        foreach ($dimension_values as $one_dimension_value) {
            if (empty($action_params[$one_dimension_value]) || !is_numeric($action_params[$one_dimension_value])) {

                octolio_enqueue_message(sprintf(__('There was an error while trying to set new weight. %s is empty or not numeric'), $one_dimension_value), 'error');
                continue;
            }
            $one_value_set = true;
        }

        if (empty($one_value_set)) return;


        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product dimensions. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            if (!empty($action_params['length']) && is_numeric($action_params['length'])) $product->set_length($action_params['length']);
            if (!empty($action_params['width']) && is_numeric($action_params['width'])) $product->set_width($action_params['width']);
            if (!empty($action_params['height']) && is_numeric($action_params['height'])) $product->set_height($action_params['height']);

            $product->save();

            $success = true;

            if ($action_params['length'] != $product->get_length()) $success = false;
            if ($action_params['width'] != $product->get_width()) $success = false;
            if ($action_params['height'] != $product->get_height()) $success = false;

            if (!$success) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set new dimensions for product ID: %s'), $product->get_id()), 'error');

                continue;
            }
            $nb_product_updated++;
        }

        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set product visibility **************/

    function get_action_params_woocommerce_product_set_visibility($action_values = [], $action_name = '')
    {
        $visibility = empty($action_values['visibility']) ? 'visible' : $action_values['visibility'];
        $filter_to_display = octolio_select(wc_get_product_visibility_options(), $action_name.'[woocommerce_product_set_visibility][visibility]', $visibility, 'octolio__select');

        return sprintf(__('Change product(s) visibility to: %s'), $filter_to_display);
    }

    function do_action_woocommerce_product_set_visibility($products = [], $action_params = [])
    {
        if (empty($action_params['visibility'])) {
            octolio_enqueue_message(__('There was an error while trying to set product status. New status is empty'), 'error');

            return;
        }

        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product visibility. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            $product->set_catalog_visibility($action_params['visibility']);
            $product->save();

            if ($action_params['visibility'] != $product->get_catalog_visibility()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product stock status for product ID: '), $product->get_id()), 'error');
                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set product as featured **************/

    function get_action_params_woocommerce_product_set_featured($action_values = [], $action_name = '')
    {
        $featured_values = [];
        $featured_values[] = octolio_select_option('featured', __('Featured'));
        $featured_values[] = octolio_select_option('notfeatured', __('Not featured'));

        $featured = empty($action_values['featured']) ? 'featured' : $action_values['featured'];
        $filter_to_display = octolio_select($featured_values, $action_name.'[woocommerce_product_set_featured][featured]', $featured, 'octolio__select');

        return sprintf(__('Set product(s) as: %s'), $filter_to_display);
    }

    function do_action_woocommerce_product_set_featured($products = [], $action_params = [])
    {
        if (empty($action_params['featured']) || ('featured' == $action_params) || ('notfeatured' == $action_params)) {
            octolio_enqueue_message(__('There was an error while trying to set product as featured.'), 'error');

            return;
        }

        $featured = ('featured' == $action_params['featured'] ? true : false);

        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product as (not?) featured. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            $product->set_featured($featured);
            $product->save();

            if ($featured != $product->get_featured()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set as (not?) featured product with ID: '), $product->get_id()), 'error');
                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Set stock **************/

    function get_action_params_woocommerce_product_set_stock($action_values = [], $action_name = '')
    {
        $weight = !isset($action_values['stock']) ? '' : $action_values['stock'];

        return sprintf(__('New stock: %s'), '<input class="octolio_input" name="'.$action_name.'[woocommerce_product_set_stock][stock]" value="'.$weight.'"/>');
    }

    function do_action_woocommerce_product_set_stock($products = [], $action_params = [])
    {
        if (empty($action_params['stock']) || !is_numeric($action_params['stock'])) {
            octolio_enqueue_message(__('There was an error while trying to set new stock. New stock is empty or not numeric'), 'error');

            return;
        }

        $nb_product_updated = 0;

        foreach ($products as $one_product) {
            $product = wc_get_product($one_product);

            if (empty($product)) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set product stock. No product with ID: '), $product->get_id()), 'error');
                continue;
            }

            $product->set_stock_quantity($action_params['stock']);
            $product->save();

            if ($action_params['stock'] != $product->get_stock_quantity()) {
                octolio_enqueue_message(sprintf(__('There was an error while trying to set new stock for product ID: '), $product->get_id()), 'error');
                continue;
            }
            $nb_product_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated'), $nb_product_updated, __('Products'), 'success'));
    }

    /*************** Add product tag **************/

    function get_action_params_woocommerce_product_add_tags($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'product_tag', 'woocommerce_product_add_tags');
    }

    function do_action_woocommerce_product_add_tags($products = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        $post_helper->execute_post_action_add_taxonomy($products, $action_params, 'product_tag');
    }

    /*************** Add product category **************/

    function get_action_params_woocommerce_product_add_category($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Product');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'product_cat', 'woocommerce_product_add_category');
    }

    function do_action_woocommerce_product_add_category($products = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_add_taxonomy($products, $action_params, 'product_cat', 'product');
    }

    /*
    *
    * Hook actions
    *
    */

    function register_hook_action_user_complete_purchase($workflow_class = object)
    {
        if (!has_action('woocommerce_product_order_status_completed', 'execute_user_purchase_hook')) {

            function execute_user_purchase_hook($order_id)
            {
                $order = new WC_Order($order_id);
                $user_id = $order->get_user_id();
                if (!empty($user_id)) {
                    do_action('execute_octolio_hook_actions', 'user_register', $user_id);
                }
            }

            add_action(
                'woocommerce_order_status_completed',
                'execute_user_purchase_hook',
                10,
                1
            );
        }
    }
}