<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


class octolio_workflow_listing
{
    /**
     * Add / Reorder / Modify listing columns
     *
     * @param $columns
     *
     * @return array
     */
    function workflow_manage_columns($columns)
    {
        $new_columns = [
            'workflow_type' => __('Type', 'Octolio'),
        ];

        return array_merge($columns, $new_columns);
    }


    /**
     * Define sortable columns
     *
     * @param $columns
     *
     * @return mixed
     */
    function workflow_register_sortable_columns($columns)
    {
        $columns['workflow_type'] = 'workflow_type';

        return $columns;
    }

    /**
     * Order results based on the column user clicked on
     *
     * @param $vars
     *
     * @return array
     */
    function workflow_type_column_orderby($vars)
    {
        if (isset($vars['orderby']) && 'workflow_type' == $vars['orderby']) {
            $vars = array_merge(
                $vars,
                [
                    'meta_key' => 'workflow_type',
                    'orderby' => 'meta_value_num',
                ]
            );
        }

        return $vars;
    }
}