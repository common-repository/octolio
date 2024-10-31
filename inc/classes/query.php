<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


class octolio_query_class extends octolio_class
{
    var $from = '';
    var $leftjoin = [];
    var $join = [];
    var $where = [];
    var $orderBy = '';
    var $limit = '';


    public function convertQuery($table, $column, $operator, $value, $type = '')
    {
        $operator = str_replace(['&lt;', '&gt;'], ['<', '>'], $operator);

        if ($operator == 'CONTAINS' || ($type == 'phone' && $operator == '=')) {
            $operator = 'LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator == 'BEGINS') {
            $operator = 'LIKE';
            $value = $value.'%';
        } elseif ($operator == 'END') {
            $operator = 'LIKE';
            $value = '%'.$value;
        } elseif ($operator == 'NOTCONTAINS' || ($type == 'phone' && $operator == '!=')) {
            $operator = 'NOT LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator == 'IN') {
            if (!is_array($value)) {
                $values = [$value];
            } else $values = $value;
            foreach ($values as $key => $val) {
                $values[$key] = octolio_escape_DB($val);
            }
            $value = '('.implode(',', $values).')';
        } elseif ($operator == 'REGEXP') {
            if ($value === '') return '1 = 1';
        } elseif ($operator == 'NOT REGEXP') {
            if ($value === '') return '0 = 1';
        } elseif (!in_array($operator, ['IS NULL', 'IS NOT NULL', 'NOT LIKE', 'LIKE', '=', '!=', '>', '<', '>=', '<=', 'IN'])) {
            die(__('UNKNOWN_OPERATOR', $operator));
        }
        /*if (strpos($value, '[time]') !== false) {
            $value = acym_replaceDate($value);
            $value = strftime('%Y-%m-%d %H:%M:%S', $value);
        }*/
        if ($operator !== 'IN' && (!is_numeric($value) || in_array($operator, ['REGEXP', 'NOT REGEXP', 'NOT LIKE', 'LIKE', '=', '!=']))) {
            $value = octolio_escape_DB($value);
        }

        if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = '';
        }

        if ($type == 'datetime' && in_array($operator, ['=', '!='])) {
            return 'DATE_FORMAT('.octolio_secure_db_column($table).'.`'.octolio_secure_db_column($column).'`, "%Y-%m-%d") '.$operator.' '.'DATE_FORMAT('.$value.', "%Y-%m-%d")';
        }
        if ($type == 'timestamp' && in_array($operator, ['=', '!='])) {
            return 'FROM_UNIXTIME('.octolio_secure_db_column($table).'.`'.octolio_secure_db_column($column).'`, "%Y-%m-%d") '.$operator.' '.'FROM_UNIXTIME('.$value.', "%Y-%m-%d")';
        }

        return octolio_secure_db_column($table).'.`'.octolio_secure_db_column($column).'` '.$operator.' '.$value;
    }

    public function count($table_alias, $table_pk)
    {
        global $wpdb;
        $query = $this->getQuery(['COUNT(DISTINCT '.$table_alias.'.'.$table_pk.')']);

        return $wpdb->get_var($query);
    }

    public function getQuery($select = [])
    {
        global $wpdb;
        $query = '';
        if (!empty($select)) $query .= ' SELECT DISTINCT '.implode(',', $select);
        if (!empty($this->from)) $query .= ' FROM '.$wpdb->prefix.$this->from;
        if (!empty($this->join)) $query .= ' JOIN '.implode(' JOIN ', $this->join);
        if (!empty($this->leftjoin)) $query .= ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftjoin);
        if (!empty($this->where)) $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        if (!empty($this->orderBy)) $query .= ' ORDER BY '.$this->orderBy;
        if (!empty($this->limit)) $query .= ' LIMIT '.$this->limit;

        return $query;
    }

    public function break_query($message)
    {
        $this->where[] = '0=1';
        $this->errors[] = $message;

        return false;
    }

}

?>