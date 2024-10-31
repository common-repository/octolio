<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_woocommerce_order_integration extends octolio_integration
{
    public $type = 'woocommerce_order';

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
        if ('octolio_workflow' != get_post_type()) {
            return;
        }

        return ('octolio_bulkaction' == get_post_type() ? __('WooCommerce Order') : __('WooCommerce Order'));
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
        $this->hooks[$this->type]['hook_cart_displayed'] = __('Is being created', 'octolio');
        //$this->hooks['user']['hook_user_complete_purchase'] = __('Confirm an order', 'octolio');
    }

    public function init_filters()
    {
        $this->filters[$this->type]['woocommerce_order_total'] = (object)['value' => 'woocommerce_order_total', 'text' => __('Order Total', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_order_subtotal'] = (object)['value' => 'woocommerce_order_subtotal', 'text' => __('Order Subtotal', 'octolio'), 'disable' => false];

        $this->filters[$this->type]['woocommerce_order_billing_country'] = (object)['value' => 'woocommerce_order_billing_country', 'text' => __('Order Billing Country', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_order_billing_state'] = (object)['value' => 'woocommerce_order_billing_state', 'text' => __('Order Billing State', 'octolio'), 'disable' => false];

        $this->filters[$this->type]['woocommerce_order_shipping_country'] = (object)['value' => 'woocommerce_order_shipping_country', 'text' => __('Order Shipping Country', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['woocommerce_order_shipping_state'] = (object)['value' => 'woocommerce_order_shipping_state', 'text' => __('Order Shipping State', 'octolio'), 'disable' => false];

        $this->filters[$this->type]['woocommerce_order_shipping_method'] = (object)['value' => 'woocommerce_order_shipping_method', 'text' => __('Order Shipping Method (Pro Version)', 'octolio'), 'disable' => true];

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
        $this->actions[$this->type]['woocommerce_order_apply_coupon'] = (object)['value' => 'woocommerce_order_apply_coupon', 'text' => __('Apply Coupon', 'octolio'), 'disable' => false];

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

    function clean_action($workflow_actions)
    {
        foreach ($workflow_actions as $one_action_number => $one_action) {
            $action_name = array_keys($one_action)[0];
            if ('woocommerce_order_apply_coupon' == $action_name) {
                $action_params = array_shift($one_action);
                global $woocommerce;
                if ($woocommerce->cart->has_discount(strtolower($action_params['coupon']))) $woocommerce->cart->remove_coupon(sanitize_text_field($action_params['coupon']));
            }
        }
    }

    /*
     *
     * FILTERS
     *
     */

    /*************** Order Total ***************/
    function get_filter_params_woocommerce_order_total($filter_values = [], $filter_name = '')
    {
        $operator_type = octolio_get('type.operator');

        $filter_operator = empty($filter_values['operator']) ? '' : $filter_values['operator'];
        $filter_to_display = $operator_type->display($filter_name.'[woocommerce_order_total][operator]', $filter_operator, true);

        $filter_value = !isset($filter_values['value']) ? '' : $filter_values['value'];
        $filter_to_display .= '<input type="text" name="'.$filter_name.'[woocommerce_order_total][value]" value="'.$filter_value.'">';

        return sprintf(__('Order total is %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_total($filter_values = [], $query = object)
    {
        if (!isset($filter_values['value']) || empty($filter_values['operator'])) {
            return false;
        }
        global $woocommerce;
        if (version_compare($woocommerce->cart->total, $filter_values['value'], $filter_values['operator'])) return true;

        return false;
    }

    /*************** Order Subtotal ***************/
    function get_filter_params_woocommerce_order_subtotal($filter_values = [], $filter_name = '')
    {
        $operator_type = octolio_get('type.operator');

        $filter_operator = empty($filter_values['operator']) ? '' : $filter_values['operator'];
        $filter_to_display = $operator_type->display($filter_name.'[woocommerce_order_subtotal][operator]', $filter_operator, true);

        $filter_value = !isset($filter_values['value']) ? '' : $filter_values['value'];
        $filter_to_display .= '<input type="text" name="'.$filter_name.'[woocommerce_order_subtotal][value]" value="'.$filter_value.'">';

        return sprintf(__('Order subtotal is %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_subtotal($filter_values = [], $query = object)
    {
        if (!isset($filter_values['value']) || empty($filter_values['operator'])) {
            return false;
        }
        global $woocommerce;
        if (!version_compare($woocommerce->cart->subtotal, $filter_values['value'], $filter_values['operator'])) return false;

        return true;
    }

    /*************** Order Shipping Method ***************/
    function get_filter_params_woocommerce_order_shipping_method($filter_values = [], $filter_name = '')
    {
        $shipping_methods = WC()->shipping->get_shipping_methods();

        $shipping_method_dropdown_options = [octolio_select_option('', '-----')];
        foreach ($shipping_methods as $shipping_method) {
            $shipping_method_dropdown_options[] = octolio_select_option($shipping_method->id, $shipping_method->method_title);
        }

        $selected_method = empty($filter_values['shipping_method']) ? '' : $filter_values['shipping_method'];
        $filter_to_display = octolio_select($shipping_method_dropdown_options, $filter_name.'[woocommerce_order_shipping_method][shipping_method]', $selected_method, 'octolio__select');

        return sprintf(__('Shipping method is %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_shipping_method($filter_values = [], $query = object)
    {
        if (empty($filter_values['shipping_method'])) return false;

        //WC()->session->get('chosen_shipping_methods') format is : ['shipping_label_title:1']. So reset / Explode / reset is needed
        $selected_shipphing_label_array = WC()->session->get('chosen_shipping_methods');
        $exploded_selected_shipphing_label = explode(':', reset($selected_shipphing_label_array));

        if ($filter_values['shipping_method'] != reset($exploded_selected_shipphing_label)) return false;

        return true;
    }

    /*************** Order Shipping Country ***************/
    function get_filter_params_woocommerce_order_shipping_country($filter_values = [], $filter_name = '')
    {
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_countries();
        $countries = array_map(
            'html_entity_decode',
            $countries
        );

        $selected_countries = empty($filter_values['countries']) ? [] : $filter_values['countries'];

        $filter_to_display = octolio_select_multiple(
            $countries,
            $filter_name.'[woocommerce_order_shipping_country][countries]',
            $selected_countries
        );

        return sprintf(__('Shipping country is in: %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_shipping_country($filter_values = [], $query = object)
    {
        if (empty($filter_values['countries'])) return false;

        if (!in_array(WC()->customer->get_shipping_country(), $filter_values['countries'])) return false;

        return true;
    }

    /*************** Order Shipping Country ***************/
    function get_filter_params_woocommerce_order_shipping_state($filter_values = [], $filter_name = '')
    {
        $countries_obj = new WC_Countries();
        $states = $countries_obj->get_allowed_country_states();

        $state_options = [];

        foreach ($states as $one_state_value => $one_state_list) {
            if (empty(sizeof($one_state_list))) continue;

            $new_option = new stdClass();
            $new_option->value = '<optgroup>';
            $new_option->text = $one_state_value;
            $state_options[] = $new_option;

            $one_state_list = array_map(
                'html_entity_decode',
                $one_state_list
            );
            $state_options = array_merge($state_options, $one_state_list);

            $new_option = new stdClass();
            $new_option->value = '</optgroup>';
            $new_option->text = '';
            $state_options[] = $new_option;
        }

        $selected_states = empty($filter_values['states']) ? [] : $filter_values['states'];
        $filter_to_display = octolio_select_multiple(
            $state_options,
            $filter_name.'[woocommerce_order_shipping_state][states]',
            $selected_states
        );

        return sprintf(__('Shipping state is in: %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_shipping_state($filter_values = [], $query = object)
    {
        if (empty($filter_values['states'])) return false;

        if (!in_array(WC()->customer->get_shipping_state(), $filter_values['states'])) return false;

        return true;
    }

    /*************** Order Shipping Country ***************/
    function get_filter_params_woocommerce_order_billing_country($filter_values = [], $filter_name = '')
    {
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_countries();
        $countries = array_map(
            'html_entity_decode',
            $countries
        );

        $selected_countries = empty($filter_values['countries']) ? [] : $filter_values['countries'];

        $filter_to_display = octolio_select_multiple(
            $countries,
            $filter_name.'[woocommerce_order_billing_country][countries]',
            $selected_countries
        );

        return sprintf(__('Billing country is in: %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_billing_country($filter_values = [], $query = object)
    {
        if (empty($filter_values['countries'])) return false;

        if (!in_array(WC()->customer->get_billing_country(), $filter_values['countries'])) return false;

        return true;
    }

    /*************** Order Shipping Country ***************/
    function get_filter_params_woocommerce_order_billing_state($filter_values = [], $filter_name = '')
    {
        $countries_obj = new WC_Countries();
        $states = $countries_obj->get_allowed_country_states();

        $state_options = [];

        foreach ($states as $one_state_value => $one_state_list) {
            if (empty(sizeof($one_state_list))) continue;

            $new_option = new stdClass();
            $new_option->value = '<optgroup>';
            $new_option->text = $one_state_value;
            $state_options[] = $new_option;

            $one_state_list = array_map(
                'html_entity_decode',
                $one_state_list
            );
            $state_options = array_merge($state_options, $one_state_list);

            $new_option = new stdClass();
            $new_option->value = '</optgroup>';
            $new_option->text = '';
            $state_options[] = $new_option;
        }

        $selected_states = empty($filter_values['states']) ? [] : $filter_values['states'];
        $filter_to_display = octolio_select_multiple(
            $state_options,
            $filter_name.'[woocommerce_order_billing_state][states]',
            $selected_states
        );

        return sprintf(__('Billing state is in: %s'), $filter_to_display);
    }

    function apply_filter_woocommerce_order_billing_state($filter_values = [], $query = object)
    {
        if (empty($filter_values['states'])) return false;

        if (!in_array(WC()->customer->get_billing_state(), $filter_values['states'])) return false;

        return true;
    }


    /*
     *
     * ACTIONS
     *
     */

    /*************** Apply Coupon ***************/

    function get_action_params_woocommerce_order_apply_coupon($action_values = [], $action_name = '')
    {
        $args = [
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'asc',
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
        ];
        $coupons = get_posts($args);
        if (empty($coupons)) return __('No coupon found on your website... Please create one from the WooCommerce coupon interface first');

        $coupon_names = [octolio_select_option('', '-----')];

        foreach ($coupons as $coupon) {
            $coupon_names[] = octolio_select_option($coupon->post_title, $coupon->post_title);
        }

        $selected_coupon = empty($action_values['coupon']) ? '' : $action_values['coupon'];
        $filter_to_display = octolio_select($coupon_names, $action_name.'[woocommerce_order_apply_coupon][coupon]', $selected_coupon, 'octolio__select');

        return sprintf('Apply this coupon on checkout page: %s', $filter_to_display);
    }

    function do_action_woocommerce_order_apply_coupon($products = [], $action_params = [])
    {
        if (empty($action_params['coupon'])) return;

        global $woocommerce;
        if (!$woocommerce->cart->has_discount(strtolower($action_params['coupon']))) {
            $woocommerce->cart->add_discount(sanitize_text_field($action_params['coupon']));
        }
    }

    /*
    *
    * Hook actions
    *
    */

    function register_hook_action_cart_displayed($workflow_class = object)
    {
        function execute_display_cart_hook_actions()
        {
            do_action('execute_octolio_hook_actions', 'cart_displayed');
        }

        if (!has_action('woocommerce_before_cart_table', 'execute_display_cart_hook_actions')) {
            add_action(
                'woocommerce_before_cart_table',
                'execute_display_cart_hook_actions',
                10,
                1
            );
        }
        /*
                if (!has_action('woocommerce_before_checkout_form', 'execute_display_cart_hook_actions')) {
                    add_action(
                        'woocommerce_before_checkout_form',
                        'execute_display_cart_hook_actions',
                        10,
                        1
                    );
                }
        */
        if (!has_action('woocommerce_review_order_before_shipping', 'execute_display_cart_hook_actions')) {
            add_action(
                'woocommerce_review_order_before_shipping',
                'execute_display_cart_hook_actions',
                10,
                1
            );
        }
        /*
        if (!has_action('woocommerce_calculate_totals', 'execute_display_cart_hook_actions')) {
            add_action(
                'woocommerce_calculate_totals',
                'execute_display_cart_hook_actions',
                10,
                1
            );
        }*/
    }
}