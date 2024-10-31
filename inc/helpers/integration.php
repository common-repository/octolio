<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

/**
 * Generic class to handle Octolio integration
 */
class octolio_integration_helper
{
    private $integrations = [];
    private $filters = [];
    private $actions = [];
    private $hooks = [];

    public function __construct()
    {
        //Import integrations
        $integrations = array_diff(scandir(dirname(__DIR__).'/integrations'), ['.', '..', '.DS_Store']);

        foreach ($integrations as $one_integration) {
            octolio_get('integration.'.str_replace(".php", "", $one_integration));
        }
    }

    /**
     * Get list of available integration
     * @return mixed|void
     */
    public function get_available_integrations()
    {
        return apply_filters('octolio_get_integrations', $this->integrations);
    }

    /**
     * Get list of available integration
     * @return mixed|void
     */
    public function get_filters_list($selected_integration = '')
    {
        return apply_filters('octolio_get_filters', $this->filters, $selected_integration);
    }

    /**
     * Display filter HTML
     *
     * @param string $filter_type
     * @param array  $filter_values
     */
    function display_filter_params($filter_type = '', $filter_values = [], $filter_name = '')
    {
        if (empty($filter_type)) return;
        echo $this->get_filter_params($filter_type, $filter_values, $filter_name);

        return;
    }

    function get_filter_params($filter_type = '', $filter_values = [], $filter_name = '')
    {
        return apply_filters('octolio_filter_params_'.$filter_type, $filter_values, $filter_name);
    }

    /**
     *
     * Actions
     */

    /**
     * Get list of available integration
     * @return mixed|void
     */
    public function get_actions_list($selected_integration = '')
    {
        return apply_filters('octolio_get_actions', $this->actions, $selected_integration);
    }

    /**
     * Display action HTML
     *
     * @param string $action_type
     * @param array  $action_values
     */
    function display_action_params($action_type = '', $action_values = [], $action_name = '')
    {
        if (empty($action_type)) return;
        echo $this->get_action_params($action_type, $action_values, $action_name);

        return;
    }

    function get_action_params($action_type = '', $action_values = [], $action_name = '')
    {
        return apply_filters('octolio_action_params_'.$action_type, $action_values, $action_name);
    }

    /**
     *
     * Hooks
     *
     */

    /**
     * Get list of available hooks
     * @return mixed|void
     */
    public function get_hooks_list($selected_integration = '')
    {

        return apply_filters('octolio_get_hooks', $this->hooks, $selected_integration);
    }

    public function add_hook_actions()
    {
        do_action('octolio_add_hook_actions');
    }
}