<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

/**
 * Generic class to handle WordPress posts
 */
class octolio_post_helper
{

    public $filter_displayed_name = '';

    /*************** Post status ***************/

    function get_filter_params_post_status($filter_values = [], $filter_name = '', $filter_type = '')
    {
        $post_statuses = get_post_statuses();
        if (empty($post_statuses)) return __('No statuses found on your website.');


        $selected_status = empty($filter_values['status']) ? '' : $filter_values['status'];
        $filter_to_display = octolio_select($post_statuses, $filter_name.'['.$filter_type.'][status]', $selected_status);

        return sprintf(__('%s belonging to this status:<br/> %s'), $this->filter_displayed_name, $filter_to_display);
    }

    function add_post_status_filter($query = object, $filter_values = [], $post_table_prefix = 'wp_posts')
    {
        global $wpdb;
        $post_statuses = get_post_statuses();

        if (is_plugin_active('woocommerce/woocommerce.php')) $post_statuses = array_merge(wc_get_order_statuses(), $post_statuses);

        if (empty($filter_values['status'])) return $query->break_query(__('There was an issue with post_status filter value'));

        if (is_array($filter_values['status'])) {
            foreach ($filter_values['status'] as $one_status) {
                if (!key_exists($one_status, $post_statuses)) return $query->break_query(__('There was an issue with post_status filter value'));
            }
            $query->where[] = $query->convertQuery($post_table_prefix, 'post_status', 'IN', $filter_values['status']);

            return $query;
        }

        if (!key_exists($filter_values['status'], $post_statuses)) return $this->break_query(__('There was an issue with post_status filter value'));

        $query->where[] = $query->convertQuery($post_table_prefix, 'post_status', '=', $filter_values['status']);

        return $query;
    }

    /*************** Post Type ***************/

    function add_post_type_filter($query = object, $filter_values = [], $post_table_prefix = 'wp_posts')
    {
        global $wpdb;
        $post_types = get_post_types();

        if (empty($filter_values['type'])) return $query->break_query(__('There was an issue with post_type filter value'));

        if (is_array($filter_values['type'])) {
            foreach ($filter_values['type'] as $one_type) {
                if (!key_exists($one_type, $post_types)) return $query->break_query(__('There was an issue with post_type filter value'));
            }
            $query->where[] = $query->convertQuery($post_table_prefix, 'post_type', 'IN', $filter_values['type']);

            return $query;
        }

        if (!key_exists($filter_values['status'], $post_types)) return $this->break_query(__('There was an issue with post_type filter value'));

        $query->where[] = $query->convertQuery($post_table_prefix, 'post_type', '=', $filter_values['type']);

        return $query;
    }


    /*************** Post Author ***************/

    function get_filter_params_post_author($filter_values = [], $filter_name = '', $filter_type = '')
    {
        $post_type = ('page_author' == $filter_type) ? 'page' : 'post';


        $authors = octolio_loadObjectList(
            "
            SELECT DISTINCT post_author, COUNT(posts.ID) AS count, users.display_name
            FROM wp_posts AS posts
            
            JOIN wp_users AS users 
            ON posts.post_author = users.ID
            
            WHERE post_type = '$post_type'
            
            GROUP BY post_author"
        );
        if (empty($authors)) return __('No authors found on your website');


        $wp_authors = [];
        foreach ($authors as $one_author) {
            $wp_authors[$one_author->post_author] = ucfirst($one_author->display_name).' ('.$one_author->count.')';
        }

        $selected_author = empty($filter_values['author']) ? '' : $filter_values['author'];
        $filter_to_display = octolio_select($wp_authors, $filter_name.'['.$filter_type.'][author]', $selected_author);

        return sprintf(__('%s created by :<br/> %s'), $this->filter_displayed_name, $filter_to_display);
    }

    function add_post_author_filter($filter_values = [], $query = object)
    {
        global $wpdb;
        //Let's add a small "security" check
        $authors = octolio_load_result_array(
            "
            SELECT DISTINCT post_author, COUNT(posts.ID) AS count, users.display_name
            FROM wp_posts AS posts
            
            JOIN wp_users AS users 
            ON posts.post_author = users.ID
            
            WHERE post_type = 'post'
            
            GROUP BY post_author"
        );

        if (empty($filter_values['author']) || !in_array($filter_values['author'], $authors)) return $query->break_query(__('There was an issue with post_author filter value.'));

        $query->where[] = $query->convertQuery('wp_posts', 'post_author', '=', $filter_values['author']);

        return $query;
    }


    /*************** Post content ***************/

    function get_filter_params_post_content($filter_values = [], $filter_name = '', $filter_type = '')
    {
        $title = !isset($filter_values['title']) ? '' : $filter_values['title'];
        $input_title = '<input class="octolio_input" name="'.$filter_name.'['.$filter_type.'][title]" value="'.$title.'"/>';
        $filter_title = sprintf(__('All the %s for whom title contains: %s'), $this->filter_displayed_name, $input_title);

        $content = !isset($filter_values['content']) ? '' : $filter_values['content'];
        $input_title = '<input class="octolio_input" name="'.$filter_name.'['.$filter_type.'][content]" value="'.$content.'"/>';
        $filter_content = sprintf(__('All the %s for whom content contains: %s'), $this->filter_displayed_name, $input_title);

        return $filter_title.'<br/>'.$filter_content;
    }

    function add_post_content_filter($filter_values = [], $query = object)
    {
        global $search_term;
        $search_term = $filter_values;

        if (!isset($filter_values['title']) && !isset($filter_values['content'])) return $query->break_query(__('There was an issue with Content filter value.'));

        function post_content_filter($where, $wp_query)
        {
            global $search_term;
            global $wpdb;

            if (isset($search_term['title'])) $condition_1 = $wpdb->posts.'.post_title LIKE '.octolio_escape_DB('%'.$search_term['title'].'%');
            if (isset($search_term['content'])) $condition_2 = $wpdb->posts.'.post_content LIKE '.octolio_escape_DB('%'.$search_term['content'].'%');

            if (!empty($condition_1) && !empty($condition_2)) return ' AND ('.$condition_1.' AND '.$condition_2.')';
            if (!empty($condition_1)) return ' AND '.$condition_1;
            if (!empty($condition_2)) return ' AND '.$condition_2;

            return $where;
        }

        $args = [
            'post_type' => 'post',
            'posts_per_page' => -1,
        ];

        add_filter('posts_where', 'post_content_filter', 10, 2);
        $wp_query = new WP_Query($args);
        remove_filter('posts_where', 'post_content_filter', 10, 2);

        if (empty($wp_query->posts)) {
            //No post matching these "content" conditions so we need to break the final query.
            //If we don't do this then filter won't be applied and we will get bad results
            return $query->break_query(__('There was an issue with Content filter value.'));
        }

        $post_ids = [];
        foreach ($wp_query->posts as $one_post) {
            $post_ids[] = intval($one_post->ID);
        }

        $query->where[] = 'wp_posts.ID IN ('.implode(', ', $post_ids).')';

        return $query;
    }


    /*************** Post Meta ***************/

    function get_filter_params_postmeta($filter_values = [], $filter_name = '', $filter_type = '')
    {

        $numeric_only = ('woocommerce_weight' == $filter_type) ? true : false;
        $operator_type = octolio_get('type.operator');

        $filter_operator = empty($filter_values['operator']) ? '' : $filter_values['operator'];
        $filter_to_display = $operator_type->display($filter_name.'['.$filter_type.'][operator]', $filter_operator, $numeric_only);

        $filter_value = !isset($filter_values['value']) ? '' : $filter_values['value'];
        $filter_to_display .= '<input type="text" name="'.$filter_name.'['.$filter_type.'][value]" value="'.$filter_value.'">';

        return sprintf($this->filter_displayed_name.$filter_to_display);
    }


    public function add_post_meta_filter($query = object, $field = '', $operator = '', $value = '', $numeric_only = false)
    {
        global $wpdb;
        if (!isset($value) || empty($field) || empty($operator) || ($numeric_only && !is_numeric($value))) return $query->break_query(sprintf('There was an issue with post meta %s filter value', $field));

        $query->where[] = $query->convertQuery('wp_postmeta', 'meta_key', '=', $field);
        $query->where[] = $query->convertQuery('wp_postmeta', 'meta_value', $operator, $value);
    }


    /*************** Post Taxonomy ***************/

    public function get_filter_params_post_taxonomy($filter_values = [], $filter_name = '', $taxonomy = '', $taxonomy_label = '')
    {

        $params = ['hide_empty' => false];
        $params['taxonomy'] = $taxonomy;
        $terms = get_terms($params);

        if (empty($terms) || !empty($terms->errors)) return sprintf(__('No %s found on your website... Please create it from the %s interface first'), $taxonomy_label, $taxonomy_label);

        $all_terms = [];
        foreach ($terms as $one_term) {
            $all_terms[$one_term->term_id] = $one_term->name;
        }

        $selected_term = empty($filter_values[$taxonomy_label]) ? '' : $filter_values[$taxonomy_label];
        $filter_to_display = octolio_select($all_terms, $filter_name.'['.$taxonomy_label.']', $selected_term);

        return sprintf(__('%s belonging to this %s:<br/> %s'), $this->filter_displayed_name, $taxonomy_label, $filter_to_display);
    }


    function add_post_taxonomy_filter($query = object, $filter_values = [], $taxonomy = '', $taxonomy_label = '', $operator = '=')
    {
        global $wpdb;

        if (empty($filter_values[$taxonomy_label])) return $query->break_query(sprintf(__('There was an issue with %s filter value. %s is empty or invalid'), octolio_escape($this->filter_displayed_name), octolio_escape(ucfirst($taxonomy_label))));

        $params = ['hide_empty' => false];
        $params['taxonomy'] = $taxonomy;
        $terms = get_terms($params);

        if (empty($terms)) return $query->break_query(sprintf(__('There was an issue with %s filter value. %s is empty or invalid'), octolio_escape($this->filter_displayed_name), octolio_escape(ucfirst($taxonomy_label))));

        foreach ($terms as $one_term) {
            $terms_array[] = $one_term->term_id;
        }

        if (is_array($filter_values[$taxonomy_label])) {
            foreach ($filter_values[$taxonomy_label] as $one_term) {
                if (!in_array($one_term, $terms_array)) return $query->break_query(sprintf(__('There was an issue with %s filter value. %s is empty or invalid'), octolio_escape($this->filter_displayed_name), octolio_escape(ucfirst($taxonomy_label))));

                $query->where[] = 'wp_term_taxonomy.term_id IN ('.implode(', ', $filter_values[$taxonomy_label]).')';
            }
        } else {
            if (!in_array($filter_values[$taxonomy_label], $terms_array)) {
                return $query->break_query(sprintf(__('There was an issue with %s filter value. %s is empty or invalid'), octolio_escape($this->filter_displayed_name), octolio_escape(ucfirst($taxonomy_label))));
            }
            $query->where[] = $query->convertQuery('wp_term_taxonomy', 'term_id', $operator, $filter_values[$taxonomy_label]);
        }


        if (empty($query->join['wp_term_relationships'])) {
            $query->join['wp_term_relationships'] = $wpdb->prefix.'term_relationships AS wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id';
        }
        if (empty($query->join['wp_term_taxonomy'])) {
            $query->join['wp_term_taxonomy'] = $wpdb->prefix.'term_taxonomy AS wp_term_taxonomy ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id';
        }

        $query->where[] = $query->convertQuery('wp_term_taxonomy', 'taxonomy', '=', $taxonomy);

        return $query;
    }


    /*
     *
     * Actions
     *
     */

    /*************** Move post to trash ***************/

    function get_action_params_post_move_trash($action_values = [], $action_name = '', $action_type = '')
    {
        return '<input type="hidden" name="'.$action_name.'['.$action_type.']"/>';
    }

    function execute_post_action_move_trash($posts = [], $action_params = [])
    {
        $nb_post_deleted = 0;
        foreach ($posts as $one_post_id) {
            if (wp_delete_post($one_post_id, false)) $nb_post_deleted++;
        }
        octolio_enqueue_message(sprintf(__('%s %s moved to trash'), $nb_post_deleted, $this->filter_displayed_name), 'success');
    }

    /*************** Delete post ***************/

    function get_action_params_post_delete($action_values = [], $action_name = '', $action_type = '')
    {
        return '<input type="hidden" name="'.$action_name.'['.$action_type.']"/>';
    }

    function execute_post_action_delete($posts = [], $action_params = [])
    {
        $nb_post_deleted = 0;
        foreach ($posts as $one_post_id) {
            if (wp_delete_post($one_post_id, true)) $nb_post_deleted++;
        }
        octolio_enqueue_message(sprintf(__('%s %s deleted'), $nb_post_deleted, $this->filter_displayed_name), 'success');
    }

    /*************** Change post status ***************/

    function get_action_params_post_change_status($action_values = [], $action_name = '', $action_type = '')
    {
        $post_statuses = get_post_statuses();

        $selected_status = empty($action_values['status']) ? '' : $action_values['status'];
        $action_params = octolio_select($post_statuses, $action_name.'['.$action_type.'][status]', $selected_status);

        return sprintf(__('Change %s statuses to :<br/> %s'), $this->filter_displayed_name, $action_params);
    }


    function execute_post_action_change_status($posts = [], $action_params = [])
    {
        $post_statuses = get_post_statuses();

        if (!array_key_exists($action_params['status'], $post_statuses)) {
            octolio_enqueue_message(__('There was an error while trying to update posts statuses'), 'error');

            return;
        }

        $nb_post_updated = 0;

        foreach ($posts as $one_post_id) {

            $updated_post = [
                'ID' => $one_post_id,
                'post_status' => $action_params['status'],
            ];

            if (wp_update_post($updated_post, false)) $nb_post_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated '), $this->filter_displayed_name, $nb_post_updated), 'success');
    }

    /*************** Change post author ***************/

    function get_action_params_post_change_author($action_values = [], $action_name = '', $action_type = '')
    {

        $selected = empty($action_values['author']) ? -1 : $action_values['author'];

        $defaults = [
            'show_option_none' => '----',
            'multi' => 0,
            'echo' => 0,
            'name' => $action_name.'['.$action_type.'][author]',
            'selected' => $selected,
        ];

        return sprintf(__('New author:<br/> %s'), wp_dropdown_users($defaults));
    }

    function execute_post_action_change_author($posts = [], $action_params = [])
    {
        global $wpdb;
        //Let's add a small "security" check
        $users = octolio_load_result_array(
            "
            SELECT DISTINCT(users.ID)
            FROM wp_users AS users"
        );

        if (!in_array($action_params['author'], $users)) {
            octolio_enqueue_message(__('There was an error while trying to update author'), 'error');

            return;
        }

        $nb_post_updated = 0;
        foreach ($posts as $one_post_id) {

            $updated_post = [
                'ID' => $one_post_id,
                'post_author' => $action_params['author'],
            ];

            if (wp_update_post($updated_post, false)) $nb_post_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated '), $nb_post_updated, $this->filter_displayed_name), 'success');
    }

    /*************** Add post category ***************/

    function get_action_params_post_add_taxonomy($action_values = [], $action_name = '', $taxonomy = '', $taxonomy_label = '')
    {

        $params = ['hide_empty' => false];
        $params['taxonomy'] = $taxonomy;
        $terms = get_terms($params);

        if (empty($terms) || !empty($terms->errors)) return sprintf(__('No %s found on your website... Please create it from the %s interface first'), $taxonomy_label, $taxonomy_label);

        $all_terms = [];
        foreach ($terms as $one_term) {
            $all_terms[$one_term->term_id] = $one_term->name;
        }

        $selected_term = empty($action_values[$taxonomy_label]->value) ? '' : $action_values[$taxonomy_label]->value;

        $filter_to_display = octolio_select($all_terms, $action_name.'['.$taxonomy_label.']['.$taxonomy.']', $selected_term);

        return $filter_to_display;
    }

    function execute_post_action_add_taxonomy($posts = [], $action_params = [], $taxonomy = '')
    {

        if (empty($action_params[$taxonomy])) {
            octolio_enqueue_message(_('There was an error while trying to add new taxonomy', 'error'));

            return;
        }

        $params = ['hide_empty' => false];
        $params['taxonomy'] = $taxonomy;
        $terms = get_terms($params);

        if (empty($terms)) {
            octolio_enqueue_message(sprintf('There was an error while trying to add %s. No terms found.', $taxonomy), 'error');

            return;
        }

        foreach ($terms as $one_term) {
            $terms_array[$one_term->term_id] = $one_term->name;
        }

        if (!array_key_exists($action_params[$taxonomy], $terms_array)) {
            octolio_enqueue_message(sprintf('There was an error while trying to add %s. Unknown term.', $taxonomy), 'error');

            return;
        }
        $nb_post_updated = 0;

        foreach ($posts as $one_post_id) {
            $result = wp_set_post_terms($one_post_id, [intval($action_params[$taxonomy])], $taxonomy, true);
            if (!empty($result->errors)) {
                octolio_enqueue_message(__('There was an error while updating taxonomy'), 'error');

                return;
            }
            $nb_post_updated++;
        }
        octolio_enqueue_message(sprintf(__('%s %s updated '), $nb_post_updated, $this->filter_displayed_name), 'success');
    }
}