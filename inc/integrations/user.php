<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_user_integration extends octolio_integration
{
    public $type = 'user';

    public $from = 'users';

    public $table_alias = 'wp_users';

    public $table_pk = 'ID';

    function get_displayed_name()
    {
        if (empty(get_post_type())) return;

        return ('octolio_bulkaction' == get_post_type() ? __('Users') : __('User'));
    }

    function init_filter_query($query_class)
    {
        $query_class->from = $this->from.' AS '.$this->table_alias;

        return $query_class;
    }

    public function init_hooks()
    {
        $this->hooks[$this->type]['hook_user_register'] = __('Registers', 'octolio');
        $this->hooks[$this->type]['hook_profile_update'] = __('Updates his profile', 'octolio');
        $this->hooks[$this->type]['hook_wp_login'] = __('Logs In', 'octolio');
        $this->hooks[$this->type]['hook_password_reset'] = __('Resets his password', 'octolio');
    }

    public function init_filters()
    {
        $this->filters[$this->type]['user_fields'] = (object)['value' => 'user_fields', 'text' => __('User Fields', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['user_role'] = (object)['value' => 'user_role', 'text' => __('User Role', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['user_registration_date'] = (object)['value' => 'user_registration_date', 'text' => __('User Registration Date', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['user_last_login'] = (object)['value' => 'user_last_login', 'text' => __('User Last Login Date', 'octolio'), 'disable' => false];
        $this->filters[$this->type]['user_meta'] = (object)['value' => 'user_role', 'text' => __('User Meta', 'octolio'), 'disable' => false];

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
        $this->actions[$this->type]['user_delete'] = (object)['value' => 'user_delete', 'text' => __('Delete User', 'octolio'), 'disable' => false];
        $this->actions[$this->type]['user_change_role'] = (object)['value' => 'user_change_role', 'text' => __('Change User Role', 'octolio'), 'disable' => false];

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

    /*
    *
    * FILTERS
    *
    */

    /*************** User Fields ***************/

    /**
     * Return user filters
     * /**
     * @return array
     */
    function get_filter_params_user_fields($filter_values = [], $filter_name = '')
    {
        $filter_prefix = 'user_fields';
        $operator_type = octolio_get('type.operator');

        global $wpdb;
        $table_name = $wpdb->prefix.'users';
        $user_columns = $wpdb->get_col("DESC $table_name", 0);

        //We have array[0] = column_name
        //We need this format array[column_name]
        //Let's do it
        $user_columns = array_combine($user_columns, $user_columns);
        unset($user_columns['user_pass']);

        //Remove user_pass from the array
        $name = $filter_name.'['.$filter_prefix.']';

        $filter_field = empty($filter_values['field']) ? '' : $filter_values['field'];
        $filter_to_display = octolio_select($user_columns, $name.'[field]', $filter_field);

        $filter_operator = empty($filter_values['operator']) ? '' : $filter_values['operator'];
        $filter_to_display .= $operator_type->display($name.'[operator]', $filter_operator);

        $filter_value = !isset($filter_values['value']) ? '' : $filter_values['value'];
        $filter_to_display .= '<input type="text" name="'.$name.'[value]" value="'.$filter_value.'">';

        return sprintf(__('Users belonging to this filter:<br/> %s'), $filter_to_display);
    }

    function apply_filter_user_fields($filter_values = [], $query = object)
    {
        if (!isset($filter_values['value']) || empty($filter_values['operator']) || empty($filter_values['field'])) return $query->break_query(__('There was an issue with user_fields filter value'));

        $query->where[] = $query->convertQuery($this->table_alias, $filter_values['field'], $filter_values['operator'], $filter_values['value']);

        return true;
    }

    /*************** User Role ***************/

    function get_filter_params_user_role($filter_values = [], $filter_name = '')
    {
        global $wp_roles;
        $roles = $wp_roles->roles;
        if (empty($roles)) return __('No roles found on your website.');
        $roles_filter = [];

        foreach ($roles as $one_role_value => $one_role) {
            $roles_filter[$one_role_value] = $one_role['name'];
        }

        return sprintf(__('Users set as : %s'), octolio_select($roles_filter, $filter_name.'[user_role]', $filter_values));
    }

    function apply_filter_user_role($filter_values = [], $query = object)
    {
        if (empty($filter_values)) return $query->break_query(__('There was an issue with user_role filter value'));

        global $wpdb;

        $query->join['wp_usermeta'] = $wpdb->prefix.'usermeta AS wp_usermeta ON wp_users.ID = wp_usermeta.user_id';

        $query->where[] = $query->convertQuery('wp_usermeta', 'meta_key', '=', 'wp_capabilities');
        $query->where[] = $query->convertQuery('wp_usermeta', 'meta_value', 'CONTAINS', $filter_values);

        return true;
    }

    /*************** User Registration Date ***************/

    function get_filter_params_user_registration_date($filter_values = [], $filter_name = '')
    {
        $select_values = [];
        $select_values[] = octolio_select_option('>', __('More'));
        $select_values[] = octolio_select_option('<', __('Less'));

        $time_value = empty($filter_values['time_value']) ? '' : $filter_values['time_value'];
        $nb_days = empty($filter_values['nb_days']) ? '' : $filter_values['nb_days'];

        $select = octolio_select($select_values, $filter_name.'[user_registration_date][time_value]', $time_value);

        $input = '<input class="octolio_input" name="'.$filter_name.'[user_registration_date][nb_days]" value="'.$nb_days.'"/>';

        $first_part = sprintf(__('Users registered on the website for %1$s than %2$s'), strtolower($select), $input).' '.strtolower(__('Days'));

        $separator = '<br/> OR <br/>';

        $start_date = '<input class="octolio_input datepicker" name="'.$filter_name.'[user_registration_date][start_date]"/>';
        $end_date = '<input class="octolio_input datepicker" name="'.$filter_name.'[user_registration_date][end_date]"/>';

        $second_part = sprintf(__('Users registered in this interval %1$s - %2$s'), $start_date, $end_date);

        return $first_part.$separator.$second_part;
    }

    function apply_filter_user_registration_date($filter_values = [], $query = object)
    {

        if (empty($filter_values['time_value']) || !in_array($filter_values['time_value'], ['>', '<'])) return $query->break_query(__('There was an issue with user_registration_date filter value'));

        if (empty($filter_values['nb_days']) && (empty($filter_values['start_date']) || empty($filter_values['end_date']))) return $query->break_query(__('There was an issue with user_registration_date filter value'));

        $nb_days_condition = '';
        $timeframe_condition = '';

        if (!empty($filter_values['nb_days'] && ctype_digit($filter_values['nb_days']))) {
            $operator = ('more' == $filter_values['time_value']) ? '<' : '>';
            $date = date("Y-m-d H:i:s", strtotime('-'.$filter_values['nb_days'].' days'));

            $nb_days_condition = $query->convertQuery($this->table_alias, 'user_registered', $operator, $date);
        }

        if (!empty($filter_values['start_date'] && !empty($filter_values['end_date']))) {
            $start_date = date("Y-m-d H:i:s", strtotime($filter_values['start_date']));
            $end_date = date("Y-m-d H:i:s", strtotime($filter_values['end_date']));

            $timeframe_condition = '('.$query->convertQuery($this->table_alias, 'user_registered', '>', $start_date).' AND '.$query->convertQuery($this->table_alias, 'user_registered', '<', $end_date).')';
        }


        $or_condition = (!empty($nb_days_condition) && !empty($timeframe_condition) ? ' OR ' : '');

        $query->where[] = '('.$nb_days_condition.$or_condition.$timeframe_condition.')';

        return true;
    }

    /*************** User Last Login Date ***************/


    function get_filter_params_user_last_login($filter_values = [], $filter_name = '')
    {
        $select_values = [];
        $select_values[] = octolio_select_option('logged', __('logged'));
        $select_values[] = octolio_select_option('didnt_log', __('didn\'t log'));

        $time_value = empty($filter_values['time_value']) ? '' : $filter_values['time_value'];
        $nb_days = empty($filter_values['nb_days']) ? '' : $filter_values['nb_days'];

        $select = octolio_select($select_values, $filter_name.'[user_last_login][time_value]', $time_value);

        $input = '<input class="octolio_input" name="'.$filter_name.'[user_last_login][nb_days]" value="'.$nb_days.'"/>';

        return sprintf(__('Users who  %1$s into the website in the last %2$s days'), strtolower($select), $input);
    }

    function apply_filter_user_last_login($filter_values = [], $query = object)
    {
        if (empty($filter_values['time_value']) || !in_array($filter_values['time_value'], ['didnt_log', 'logged']) || empty($filter_values['nb_days']) || !ctype_digit($filter_values['nb_days'])) {
            return $query->break_query(__('There was an issue with user_last_login filter value'));
        }

        global $wpdb;
        $query->join['wp_usermeta'] = $wpdb->prefix.'usermeta AS wp_usermeta ON wp_users.ID = wp_usermeta.user_id';

        $date = time() - ($filter_values['nb_days'] * 3600);
        $operator = ('didnt_log' == $filter_values['time_value']) ? '<' : '>';

        $query->where[] = $query->convertQuery('wp_usermeta', 'meta_key', '=', 'session_tokens');
        $query->where[] = 'SUBSTRING(meta_value, -13, 10) '.$operator.' '.$date;

        //$query->where[] = $query->convertQuery('wp_usermeta', 'meta_key', '=', 'when_last_login');
        //$query->where[] = $query->convertQuery('wp_usermeta', 'meta_value', $operator, $date);

        return true;
    }


    /*************** User Meta ***************/

    function get_filter_params_user_meta($filter_values = [], $filter_name = '')
    {
        $operator_type = octolio_get('type.operator');

        global $wpdb;

        $keys = $wpdb->get_col(
            "
            SELECT meta_key
            FROM $wpdb->usermeta
            GROUP BY meta_key
            ORDER BY meta_key"
        );

        if (empty($keys)) return;


        $keys = array_combine($keys, $keys);

        //Remove user_pass from the array
        $filter_field = empty($filter_values['field']) ? '' : $filter_values['field'];
        $filter_params = octolio_select($keys, $filter_name.'[user_meta][field]', $filter_field);

        $filter_operator = empty($filter_values['operator']) ? '' : $filter_values['operator'];
        $filter_params .= $operator_type->display($filter_name.'[user_meta][operator]', $filter_operator);

        $filter_value = !isset($filter_values['value']) ? '' : $filter_values['value'];
        $filter_params .= '<input type="text" name="'.$filter_name.'[user_meta][value]" value="'.$filter_value.'">';

        return sprintf(__('Users who "Meta Keys" belong to this filter :<br/> %s'), $filter_params);
    }

    function apply_filter_user_meta($filter_values = [], $query = object)
    {
        global $wpdb;
        $keys = octolio_load_result_array(
            "
            SELECT meta_key
            FROM $wpdb->usermeta
            GROUP BY meta_key
            ORDER BY meta_key"
        );

        //We get SQL results displayed as result[0] = meta_key
        //We need it to be result[meta_key] = meta_key
        $keys = array_combine($keys, $keys);

        if (!isset($filter_values['value']) || empty($filter_values['operator']) || empty($filter_values['field']) || !in_array($filter_values['field'], $keys)) {
            return $query->break_query(__('There was an issue with user_meta filter value'));
        }

        global $wpdb;
        $query->join['wp_usermeta'] = $wpdb->prefix.'usermeta AS wp_usermeta ON wp_users.ID = wp_usermeta.user_id';

        $query->where[] = $query->convertQuery('wp_usermeta', 'meta_key', '=', $filter_values['field']);
        $query->where[] = $query->convertQuery('wp_usermeta', 'meta_value', $filter_values['operator'], $filter_values['value']);

        return true;
    }


    /*
     *
     * ACTIONS
     *
     */

    function get_action_params_user_delete($action_values = [], $action_name = '')
    {
        $selected = empty($action_values['reassign_to']) ? -1 : $action_values['reassign_to'];

        $defaults = [
            'show_option_none' => '----',
            'multi' => 0,
            'echo' => 0,
            'name' => $action_name.'[user_delete][reassign_to]',
            'selected' => $selected,
        ];

        return sprintf(__('Re-assign posts created by those users to:<br/> %s'), wp_dropdown_users($defaults));
    }

    function do_action_user_delete($users = [], $action_params = [])
    {
        $reassign_to = empty($action_params['reassign_to'] ? null : $action_params['reassign_to']);
        $current_user = get_current_user_id();


        $nb_user_deleted = 0;
        foreach ($users as $one_user_id) {

            //We're going to reassign post to this user, so you probably don't want to delete it
            //And obviously, we don't want to delete current user
            if ($one_user_id != $reassign_to || $one_user_id != $current_user) {
                if (wp_delete_user($one_user_id, $reassign_to)) $nb_user_deleted++;
            }
        }
        octolio_enqueue_message(sprintf(__('%s Users deleted'), $nb_user_deleted), 'error');
    }

    function get_action_params_user_change_role($action_values = [], $action_name = '')
    {
        global $wp_roles;
        $roles = $wp_roles->roles;
        if (empty($roles)) return;
        $roles_filter = [];

        foreach ($roles as $one_role_value => $one_role) {
            $roles_filter[$one_role_value] = $one_role['name'];
        }

        $blank_value = octolio_select_option('', '-------');

        array_unshift($roles_filter, $blank_value);


        $role_to_add = (empty($action_values['add'])) ? '' : $action_values['add'];
        $role_to_delete = (empty($action_values['delete'])) ? '' : $action_values['delete'];

        $action_params = __('Add a role').' '.octolio_select($roles_filter, $action_name.'[user_change_role][add]', $role_to_add);
        $action_params .= ' <br/>';
        $action_params .= __('Remove a role').' '.octolio_select($roles_filter, $action_name.'[user_change_role][delete]', $role_to_delete);

        return $action_params;
    }


    function do_action_user_change_role($users = [], $action_params = [])
    {
        global $wp_roles;
        $roles = $wp_roles->roles;
        if (empty($action_params['add']) && empty($action_params['delete'])) {
            octolio_enqueue_message(__('There was an error while trying to change user role'));

            return;
        } else {
            foreach ($action_params as $one_action) {
                if (!empty($one_action) && !array_key_exists($one_action, $roles)) {

                    octolio_enqueue_message(__('There was an error while trying to change use role'));

                    return;
                }
            }
        }

        $nb_user_role_added = 0;
        $nb_user_role_removed = 0;
        $nb_user_role_already_set = 0;
        $nb_user_role_not_set = 0;
        foreach ($users as $one_user_id) {
            $wp_user = new WP_User($one_user_id);

            if (!empty($action_params['add'])) {
                if (in_array($action_params['add'], $wp_user->roles)) {
                    $nb_user_role_already_set++;
                } else {
                    $wp_user->add_role($action_params['add']);
                    $nb_user_role_added++;
                }
            }
            if (!empty($action_params['delete'])) {
                if (in_array($action_params['delete'], $wp_user->roles)) {
                    $wp_user->remove_role($action_params['delete']);
                    $nb_user_role_removed++;
                } else {
                    $nb_user_role_not_set++;
                }
            }
        }

        if (!empty($action_params['add'])) {
            $added_text = '';
            $added_text .= sprintf(_n(' %1$s user set as %2$s', ' %1$s users set as %2$s', (0 == $nb_user_role_added) ? 1 : $nb_user_role_added), $nb_user_role_added, ucfirst($action_params['add']));
            if (!empty($nb_user_role_already_set)) {
                $added_text .= ' <br/>';
                $added_text .= sprintf(_n(' %1$s user was already set as %2$s', ' %1$s users were already set as %2$s', (0 == $nb_user_role_already_set) ? 1 : $nb_user_role_already_set), $nb_user_role_already_set, ucfirst($action_params['add']));
            }
            octolio_enqueue_message($added_text);
        }

        if (!empty($action_params['delete'])) {
            $deleted_text = '';
            $deleted_text = sprintf(_n(' %1$s user unset as %2$s', ' %1$s users unset as %2$s', (0 == $nb_user_role_removed) ? 1 : $nb_user_role_removed), $nb_user_role_removed, ucfirst($action_params['delete']));
            if (!empty($nb_user_role_not_set)) {
                $deleted_text .= ' <br/>';
                $deleted_text .= sprintf(_n(' %1$s user was not set as %2$s', ' %1$s users were not set as %2$s', (0 == $nb_user_role_not_set) ? 1 : $nb_user_role_not_set), $nb_user_role_not_set, ucfirst($action_params['delete']));
            }
            octolio_enqueue_message($deleted_text);
        }
    }

    /*
     *
     * Hook actions
     *
     */

    function register_hook_action_user_register()
    {
        if (!has_action('user_register', 'execute_user_register_hook')) {

            function execute_user_register_hook($user_id)
            {
                if (!empty($user_id)) {
                    do_action('execute_octolio_hook_actions', 'user_register', $user_id);
                }
            }

            add_action(
                'user_register',
                'execute_user_register_hook',
                10,
                1
            );
        }
    }

    function register_hook_action_profile_update()
    {

        if (!has_action('profile_update', 'execute_profile_update_hook')) {
            function execute_profile_update_hook($user_id, $old_user_data)
            {
                if (!empty($user_id)) {
                    do_action('execute_octolio_hook_actions', 'profile_update', $user_id);
                }
            }

            add_action(
                'profile_update',
                'execute_profile_update_hook',
                10,
                2
            );
        }
    }

    function register_hook_action_wp_login()
    {

        if (!has_action('wp_login', 'execute_wp_login_hook')) {
            function execute_wp_login_hook($user_login, $user)
            {
                if (!empty($user->ID)) {
                    do_action('execute_octolio_hook_actions', 'wp_login', $user->ID);
                }
            }

            add_action(
                'wp_login',
                'execute_wp_login_hook',
                10,
                2
            );
        }
    }

    function register_hook_action_password_reset()
    {
        if (!has_action('password_reset', 'execute_password_reset_hook')) {
            function execute_password_reset_hook($user, $new_pass)
            {
                if (!empty($user_id)) {
                    do_action('execute_octolio_hook_actions', 'password_reset', $user->ID);
                }
            }

            add_action(
                'password_reset',
                'execute_password_reset_hook',
                10,
                2
            );
        }
    }
    /*function register_hook_action_delete_user()
        {
            add_action('delete_user', [$workflow_class, 'execute_hook_actions']);
        }

        function register_hook_action_set_current_user()
        {
            add_action('wp', [$workflow_class, 'execute_hook_actions']);
        }*/
}
