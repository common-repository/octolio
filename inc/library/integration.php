<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_integration
{
    public $type = '';

    public $from = '';

    public $table_alias = '';

    public $table_pk = '';

    public $filters = [];

    public $actions = [];

    public $hooks = [];

    function __construct()
    {
        if (!$this->is_plugin_active()) return;

        add_filter('octolio_get_integrations', [$this, 'get_integration']);

        add_filter('octolio_get_hooks', [$this, 'get_hooks'], 10, 2);
        add_filter('octolio_get_filters', [$this, 'get_filters'], 10, 2);
        add_filter('octolio_get_actions', [$this, 'get_actions'], 10, 2);

        add_action('octolio_add_hook_actions', [$this, 'add_hook_actions'], 10);

        $this->init_hooks();
        $this->init_filters();
        $this->init_actions();
    }

    /**
     * Check if integration plugin is active
     *
     * @return boolean
     */
    function is_plugin_active()
    {
        return true;
    }

    /**
     * Returns integrations type
     *
     * @param $integration
     *
     * @return mixed
     */
    function get_integration($integration)
    {
        if (!empty($this->get_displayed_name())) $integration[$this->type] = $this->get_displayed_name();

        return $integration;
    }

    function add_hook_actions()
    {
        return;
    }

    function get_hooks($hooks, $integration)
    {
        if (empty($this->hooks[$integration])) return $hooks;

        return array_merge($hooks, $this->hooks[$integration]);
    }

    function init_hooks()
    {
        $this->hooks = [];
    }

    function get_filters($filters, $integration)
    {
        if (empty($this->filters[$integration])) return $filters;

        return array_merge($filters, $this->filters[$integration]);
    }

    function init_filters()
    {
        $this->filters = [];
    }

    function init_filter_query($query_class)
    {
        $query_class->from = $this->from.' AS '.$this->table_alias;

        return $query_class;
    }

    function get_actions($actions, $integration)
    {
        if (empty($this->actions[$integration])) return $actions;

        return array_merge($actions, $this->actions[$integration]);
    }

    function init_actions()
    {
        $this->actions = [];
    }

    function clean_action($workflow_actions)
    {
        return;
    }

}

