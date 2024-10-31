<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_page_integration extends octolio_integration
{
    public $type = 'page';

    public $from = 'posts';

    public $table_alias = 'wp_posts';

    public $table_pk = 'ID';

    function get_displayed_name()
    {
        if (empty(get_post_type())) return;

        return ('octolio_bulkaction' == get_post_type() ? __('Pages') : __('Page'));
    }

    function init_filter_query($query_class)
    {
        $query_class->from = $this->from.' AS '.$this->table_alias;
        $query_class->where[] = $query_class->convertQuery($this->table_alias, 'post_type', '=', 'page');

        return $query_class;
    }

    public function init_hooks()
    {
    }

    public function init_filters()
    {
        $this->filters[$this->type]['page_status'] = (object)['value' => 'page_status', 'text' => __('Page Status', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['page_author'] = (object)['value' => 'page_author', 'text' => __('Page Author', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['page_content'] = (object)['value' => 'page_content', 'text' => __('Page Content', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['page_category'] = (object)['value' => 'page_category', 'text' => __('Page Category', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['page_tags'] = (object)['value' => 'page_tags', 'text' => __('Page Tags', 'octolio'), 'disable' => false];

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
        $this->actions[$this->type]['page_move_trash'] = (object)['value' => 'page_move_trash', 'text' => __('Move page to trash', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['page_delete'] = (object)['value' => 'page_delete', 'text' => __('Delete page', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['page_change_status'] = (object)['value' => 'page_change_status', 'text' => __('Change page status', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['page_change_author'] = (object)['value' => 'page_change_author', 'text' => __('Change page author', 'octolio'), 'disable' => false];

        /*$this->actions['page_add_category'] = __('Add page category', 'octolio');
        $this->actions['page_add_tag'] = __('Add page tag', 'octolio');*/

        foreach ($this->actions as $one_type => $one_type_action) {
            foreach ($one_type_action as $one_action_name => $one_action_label) {
                if (!has_action('octolio_action_params_'.$one_action_name)) {
                    add_action('octolio_action_params_'.$one_action_name, [$this, 'get_action_params_'.$one_action_name], 10, 2);
                    add_action('octolio_do_action_'.$one_action_name, [$this, 'do_action_'.$one_action_name], 10, 2);
                }
            }
        }
    }

    /*************** Page status ***************/

    function get_filter_params_page_status($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->get_filter_params_post_status($filter_values, $filter_name, 'page_status');
    }

    function apply_filter_page_status($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_status_filter($query, $filter_values);
    }

    /*************** Page author ***************/

    function get_filter_params_page_author($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->get_filter_params_post_author($filter_values, $filter_name, 'page_author');
    }

    function apply_filter_page_author($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_author_filter($query, $filter_values);
    }

    /*************** Page content ***************/

    function get_filter_params_page_content($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->get_filter_params_post_content($filter_values, $filter_name, 'page_content');
    }

    function apply_filter_page_content($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_content_filter($query, $filter_values);
    }

    /*************** Page Tags ***************/

    function get_filter_params_page_tags($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[page_tags]', 'post_tag', 'tag');
    }

    function apply_filter_page_tags($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->add_post_taxonomy_filter($query, $filter_values, 'post_tag', 'tag');
    }

    /*************** Page Category ***************/

    function get_filter_params_page_category($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Page');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[page_category]', 'category', 'category');
    }

    function apply_filter_page_category($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_taxonomy_filter($query, $filter_values, 'category', 'category');

        return true;
    }

    /*
     *
     * ACTIONS
     *
     */

    /*************** Move page to trash ***************/

    function get_action_params_page_move_trash($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_move_trash($action_values, $action_name, 'page_move_trash');
    }

    function do_action_page_move_trash($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_move_trash($posts, $action_params);
    }

    /*************** Delete page ***************/

    function get_action_params_page_delete($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_delete($action_values, $action_name, 'page_delete');
    }

    function do_action_page_delete($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_delete($posts, $action_params);
    }

    /*************** Change page status ***************/

    function get_action_params_page_change_status($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_change_status($action_values, $action_name, 'page_change_status');
    }

    function do_action_page_change_status($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_change_status($posts, $action_params);
    }

    /*************** Change page author ***************/

    function get_action_params_page_change_author($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_change_author($action_values, $action_name, 'page_change_author');
    }

    function do_action_page_change_author($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_change_author($posts, $action_params);
    }

    /*************** Add page category ***************/

    function get_action_params_page_add_category($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'category', 'page_add_category');
    }

    function do_action_page_add_category($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_add_taxonomy($posts, $action_params);
    }

    /*************** Add page tags ***************/

    function get_action_params_page_add_tag($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'post_tag', 'page_add_tag');
    }

    function do_action_page_add_tag($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Page');
        $post_helper->execute_post_action_add_taxonomy($posts, $action_params);
    }
}