<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_operator_type
{
    var $values = [];
    var $class = 'octolio__select';
    var $extra = '';

    public function init_select_options($number_operator_only = false)
    {
        $this->values[] = octolio_select_option('=', '=');
        $this->values[] = octolio_select_option('!=', '!=');
        $this->values[] = octolio_select_option('>', '>');
        $this->values[] = octolio_select_option('<', '<');
        $this->values[] = octolio_select_option('>=', '>=');
        $this->values[] = octolio_select_option('<=', '<=');

        if (!$number_operator_only) {
            $this->values[] = octolio_select_option('BEGINS', 'BEGINS_WITH');
            $this->values[] = octolio_select_option('END', 'ENDS_WITH');
            $this->values[] = octolio_select_option('CONTAINS', 'CONTAINS');
            $this->values[] = octolio_select_option('NOTCONTAINS', 'NOT_CONTAINS');
            $this->values[] = octolio_select_option('LIKE', 'LIKE');
            $this->values[] = octolio_select_option('NOT LIKE', 'NOT LIKE');
            $this->values[] = octolio_select_option('REGEXP', 'REGEXP');
            $this->values[] = octolio_select_option('NOT REGEXP', 'NOT REGEXP');
            $this->values[] = octolio_select_option('IS NULL', 'IS NULL');
            $this->values[] = octolio_select_option('IS NOT NULL', 'IS NOT NULL');
        }
    }

    public function display($name, $valueSelected = '', $number_operator_only = false, $class = '')
    {
        $this->init_select_options($number_operator_only);

        return octolio_select($this->values, $name, $valueSelected, $this->extra.' class="'.$this->class.' '.$class.'"');
    }
}

