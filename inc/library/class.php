<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_class
{

    /**
     * @var string
     */
    var $table = '';

    /**
     * @var string
     */
    var $pkey = '';

    /**
     * @var string
     */
    var $namekey = '';

    /**
     * @var array
     */
    var $errors = [];

    /**
     * @var array
     */
    var $messages = [];


    /**
     * @param $elements
     *
     * @return bool|int|null
     */
    public function delete($elements)
    {
        if (!is_array($elements)) {
            $elements = [$elements];
        }

        if (empty($elements)) {
            return 0;
        }

        $column = is_numeric(reset($elements)) ? $this->pkey : $this->namekey;

        foreach ($elements as $key => $val) {
            $elements[$key] = octolio_escape_DB($val);
        }

        if (empty($column) || empty($this->pkey) || empty($this->table) || empty($elements)) {
            return false;
        }

        $query = 'DELETE FROM #__octolio_'.octolio_secure_db_column($this->table).' WHERE '.octolio_secure_db_column($column).' IN ('.implode(',', $elements).')';
        $result = acym_query($query);

        if (!$result) {
            return false;
        }

        return $result;
    }
}

