<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


class octolio_bulkaction_listing
{
    /**
     * Add / Reorder / Modify listing columns
     *
     * @param $columns
     *
     * @return array
     */
    function bulkaction_manage_columns($columns)
    {
        $new_columns = [
            'bulkaction_type' => __('Type', 'Octolio'),
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
    function bulkaction_register_sortable_columns($columns)
    {
        $columns['bulkaction_type'] = 'bulkaction_type';

        return $columns;
    }

    /**
     * Order results based on the column user clicked on
     *
     * @param $vars
     *
     * @return array
     */
    function bulkaction_type_column_orderby($vars)
    {
        if (isset($vars['orderby']) && 'bulkaction_type' == $vars['orderby']) {
            $vars = array_merge(
                $vars,
                [
                    'meta_key' => 'bulkaction_type',
                    'orderby' => 'meta_value_num',
                ]
            );
        }

        return $vars;
    }
}