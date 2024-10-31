<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_post_integration extends octolio_integration
{
    public $type = 'post';

    public $from = 'posts';

    public $table_alias = 'wp_posts';

    public $table_pk = 'ID';

    function get_displayed_name()
    {
        if (empty(get_post_type())) return;

        return ('octolio_bulkaction' == get_post_type() ? __('Posts') : __('Post'));
    }

    function init_filter_query($query_class)
    {
        $query_class->from = $this->from.' AS '.$this->table_alias;
        $query_class->where[] = $query_class->convertQuery($this->table_alias, 'post_type', '!=', 'octolio_bulkaction');

        return $query_class;
    }

    public function init_hooks()
    {
    }


    public function init_filters()
    {
        $this->filters[$this->type]['post_type'] = (object)['value' => 'post_type', 'text' => __('Post Type', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['post_status'] = (object)['value' => 'post_status', 'text' => __('Post Status', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['post_author'] = (object)['value' => 'post_author', 'text' => __('Post Author', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['post_content'] = (object)['value' => 'post_content', 'text' => __('Post Content', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['post_category'] = (object)['value' => 'post_category', 'text' => __('Post Category', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['post_tags'] = (object)['value' => 'post_tags', 'text' => __('Post Tags', 'octolio'), 'disable' => false];

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
        $this->actions[$this->type]['post_move_trash'] = (object)['value' => 'post_move_trash', 'text' => __('Move post to trash', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['post_delete'] = (object)['value' => 'post_delete', 'text' => __('Delete post', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['post_change_status'] = (object)['value' => 'post_change_status', 'text' => __('Change post status', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['post_change_author'] = (object)['value' => 'post_change_author', 'text' => __('Change post author', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['post_add_category'] = (object)['value' => 'post_add_category', 'text' => __('Add post category', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['post_add_tag'] = (object)['value' => 'post_add_tag', 'text' => __('Add post tag', 'octolio'), 'disable' => false];

        foreach ($this->actions as $one_type => $one_type_action) {
            foreach ($one_type_action as $one_action_name => $one_action_label) {
                if (!has_action('octolio_action_params_'.$one_action_name)) {
                    add_action('octolio_action_params_'.$one_action_name, [$this, 'get_action_params_'.$one_action_name], 10, 2);
                    add_action('octolio_do_action_'.$one_action_name, [$this, 'do_action_'.$one_action_name], 10, 2);
                }
            }
        }
    }

    /*************** Post status ***************/

    function get_filter_params_post_status($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_filter_params_post_status($filter_values, $filter_name, 'post_status');
    }

    function apply_filter_post_status($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_status_filter($query, $filter_values);
    }

    /*************** Post type ***************/

    function get_filter_params_post_type($filter_values = [], $filter_name = '')
    {
        $post_types = get_post_types();
        if (empty($post_types)) return __('No post type found on your website');


        //$post_types array has each key in lower case :-(
        //let's fix this to add an upper case to the first letter of each key
        $post_types = array_map('ucfirst', $post_types);

        $selected_type = empty($filter_values['type']) ? 'post' : $filter_values['type'];
        $checkbox_unlock = '<p><input type="checkbox" class="unlock_elements" autocomplete="off"> <label for="unlock">'.__('Unlock post type filter').'</label></p>';
        $filter_to_display = octolio_select($post_types, $filter_name.'[post_type][type]', $selected_type, 'disabled class="unlock"');

        $warning = '<div class="warning">'.__('<b>Please be careful about the way you use this filter.<br/>If you would like to do actions on blog posts you MUST keep the filter type set to "post".</b><br/>');
        $warning .= __('If you don\'t set any value all the WordPress post types will be selected (Custom post types included).').'</div>';


        return $warning.$checkbox_unlock.sprintf(__('Posts belonging to this type:<br/> %s'), $filter_to_display);
    }

    function apply_filter_post_type($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_posts_type_filter($query, $filter_values);
    }

    /*************** Post author ***************/

    function get_filter_params_post_author($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_filter_params_post_author($filter_values, $filter_name, 'post_author');
    }

    function apply_filter_post_author($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_author_filter($query, $filter_values);
    }

    /*************** Post content ***************/


    function get_filter_params_post_content($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_filter_params_post_content($filter_values, $filter_name, 'post_content');
    }

    function apply_filter_post_content($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->add_post_content_filter($query, $filter_values);
    }

    /*************** Post Tags ***************/

    function get_filter_params_post_tags($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[post_tags]', 'post_tag', 'tag');
    }

    function apply_filter_post_tags($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->add_post_taxonomy_filter($query, $filter_values, 'post_tag', 'tag');
    }

    /*************** Post Category ***************/

    function get_filter_params_post_category($filter_values = [], $filter_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->integration_type = $this->type;
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_filter_params_post_taxonomy($filter_values, $filter_name.'[post_category]', 'category', 'category');
    }

    function apply_filter_post_category($filter_values = [], $query = object)
    {
        $post_helper = octolio_get('helper.post');
        $query = $post_helper->add_post_taxonomy_filter($query, $filter_values, 'category', 'category');

        return $query;
    }

    /*
     *
     * ACTIONS
     *
     */

    /*************** Move post to trash ***************/

    function get_action_params_post_move_trash($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_move_trash($action_values, $action_name, 'post_move_trash');
    }

    function do_action_post_move_trash($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_move_trash($posts, $action_params);
    }

    /*************** Delete post ***************/

    function get_action_params_post_delete($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_delete($action_values, $action_name, 'post_delete');
    }

    function do_action_post_delete($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_delete($posts, $action_params);
    }

    /*************** Change post status ***************/

    function get_action_params_post_change_status($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_change_status($action_values, $action_name, 'post_change_status');
    }

    function do_action_post_change_status($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_change_status($posts, $action_params);
    }

    /*************** Change post author ***************/

    function get_action_params_post_change_author($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_change_author($action_values, $action_name, 'post_change_author');
    }

    function do_action_post_change_author($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_change_author($posts, $action_params);
    }

    /*************** Add post category ***************/

    function get_action_params_post_add_category($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'category', 'post_add_category');
    }

    function do_action_post_add_category($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_add_taxonomy($posts, $action_params, 'category');
    }

    /*************** Add post tags ***************/

    function get_action_params_post_add_tag($action_values = [], $action_name = '')
    {
        $post_helper = octolio_get('helper.post');

        return $post_helper->get_action_params_post_add_taxonomy($action_values, $action_name, 'post_tag', 'post_add_tag');
    }

    function do_action_post_add_tag($posts = [], $action_params = [])
    {
        $post_helper = octolio_get('helper.post');
        $post_helper->filter_displayed_name = __('Post');
        $post_helper->execute_post_action_add_taxonomy($posts, $action_params, 'post_tag');
    }
}