<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


/*
 * Allows to load classes / helpers easily
 *  @param string $settings
 */
/**
 * @param $path
 *
 * @return null
 */
function octolio_get($path)
{
    list($group, $class) = explode('.', $path);

    $className = 'octolio_'.$class.'_'.$group;

    if (!class_exists($className)) {
        if ($group == 'class') {
            $group = 'classes';
        } elseif ($group == 'helper') $group = 'helpers';
        elseif ('integration' == $group) $group = 'integrations';

        include(WP_PLUGIN_DIR.'/octolio/inc/'.$group.'/'.$class.'.php');
    }
    if (!class_exists($className)) {
        return null;
    }

    return new $className();
}

/**
 * Escape string before using it in an SQL query
 *
 * @param string $value
 *
 * @return string
 */

function octolio_escape_DB($value)
{
    return "'".esc_sql($value)."'";
}

/**
 * Change a timestamp to a date format
 *
 * @param int    $value
 * @param String $format
 *
 * @return String
 */
function octolio_to_date_format($value, $format)
{
    return date($format, $value);
}

/**
 * Clean var based on its type
 *
 * @param String $var
 * @param String $type
 * @param String $mask
 * @param String $format
 *
 * @return String
 */
function octolio_clean_var($var, $type, $mask)
{
    if (is_array($var)) {
        foreach ($var as $i => $val) {
            $var[$i] = octolio_clean_var($val, $type, $mask);
        }

        return $var;
    }

    switch ($type) {
        case 'email':
            $var = sanitize_email($var);
            break;
        case 'file':
            $var = sanitize_file_name($var);
            break;
        case 'html_class':
            $var = sanitize_html_class($var);
            break;
        case 'key':
            $var = sanitize_key($var);
            break;
        case 'meta':
            $var = sanitize_meta($var);
            break;
        case 'order_by':
            $var = sanitize_sql_orderby((string)$var);
            break;
        case 'user':
            $var = sanitize_user((string)$var);
            break;
        case 'int':
            $var = (int)$var;
            break;
        case 'float':
            $var = (float)$var;
            break;
        case 'boolean':
            $var = (boolean)$var;
            break;
        case 'word':
            $var = preg_replace('#[^a-zA-Z_]#', '', $var);
            break;
        case 'cmd':
            $var = preg_replace('#[^a-zA-Z0-9_\.-]#', '', $var);
            $var = ltrim($var, '.');
            break;
        default:
            break;
    }

    if (!is_string($var)) {
        return $var;
    }

    $var = trim($var);

    if ($mask & 2) {
        return $var;
    }

    if (!preg_match('//u', $var)) {
        $var = htmlspecialchars_decode(htmlspecialchars($var, ENT_IGNORE, 'UTF-8'));
    }

    if (!($mask & 4)) {
        $var = preg_replace('#<[a-zA-Z/]+[^>]*>#Uis', '', $var);
    }

    return $var;
}

/**
 * Un-quotes a quoted string
 *
 * @param string $element
 *
 * @return element
 */
function octolio_stripslashes($element)
{
    if (is_array($element)) {
        foreach ($element as &$oneCell) {
            $oneCell = octolio_stripslashes($oneCell);
        }
    } elseif (is_string($element)) {
        $element = stripslashes($element);
    }

    return $element;
}


/**
 *
 * Collect var from request / file / cookie and secure it.
 *
 * @param        $type
 * @param        $name
 * @param null   $default
 * @param string $hash
 * @param int    $mask
 *
 * @return String|null
 */
function octolio_get_var($type, $name, $default = null, $hash = 'REQUEST', $mask = 0)
{
    $hash = strtoupper($hash);

    switch ($hash) {
        case 'GET':
            $input = &$_GET;
            break;
        case 'POST':
            $input = &$_POST;
            break;
        case 'FILES':
            $input = &$_FILES;
            break;
        case 'COOKIE':
            $input = &$_COOKIE;
            break;
        case 'ENV':
            $input = &$_ENV;
            break;
        case 'SERVER':
            $input = &$_SERVER;
            break;
        default:
            $hash = 'REQUEST';
            $input = &$_REQUEST;
            break;
    }

    if (!isset($input[$name])) {
        return $default;
    }

    $result = $input[$name];
    unset($input);
    if ($type == 'array') {
        $result = (array)$result;
    }

    if (in_array($hash, ['POST', 'REQUEST', 'GET', 'COOKIE'])) {
        $result = octolio_stripslashes($result);
    }

    return octolio_clean_var($result, $type, $mask);
}

function octolio_get_option($option_name)
{

    if (empty($option_name)) return;

    $user = get_current_user_id();
    $screen = get_current_screen();

    $option = $screen->get_option('per_page', 'option');
    $per_page = get_user_meta($user, $option, true);

    if (empty ($per_page) || $per_page < 1) {
        $per_page = $screen->get_option('per_page', 'default');
    }

    return $per_page;
}

/**
 * Save the option value
 */

function save_screen_option($status, $option, $value)
{
    if (preg_match('/^octolio_(.*)_per_page$/', $option)) return $value;

    return $status;
}


/**
 * Make sure we only have integers into the Array
 *
 * @param $array
 */
function octolio_array_to_integer(&$array)
{
    if (is_array($array)) {
        $array = array_map('intval', $array);
    } else {
        $array = [];
    }
}


/**
 * Secure SQL
 *
 * @param string $fieldName
 *
 * @return string
 */
function octolio_secure_db_column($fieldName = '')
{
    if (!is_string($fieldName) || preg_match('|[^a-z0-9#_.-]|i', $fieldName) !== 0) {
        die('field, table or database "'.octolio_escape($fieldName).'" not secured');
    }

    return $fieldName;
}


/**
 * Escape string
 *
 * @param string $text
 * @param bool   $isURL
 *
 * @return string
 */
function octolio_escape($text = '', $isURL = false)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function octolio_select($data, $name = '', $selected = null, $attribs = 'null', $optKey = 'value', $optText = 'text', $idtag = false, $translate = false)
{
    $idtag = str_replace(['[', ']', ' '], '', empty($idtag) ? $name : $idtag);
    $dropdown = '<select id="'.octolio_escape($idtag).'"  autocomplete="off" name="'.octolio_escape($name).'" '.(empty($attribs) ? '' : $attribs).'>';

    if (empty($data)) return;
    foreach ($data as $key => $oneOption) {
        $disabled = false;
        if (is_object($oneOption)) {
            $value = $oneOption->$optKey;
            $text = $oneOption->$optText;
            if (isset($oneOption->disable)) {
                $disabled = $oneOption->disable;
            }
        } else {
            $value = $key;
            $text = $oneOption;
        }

        if ($translate) {
            $text = octolio_translation($text);
        }

        if (strtolower($value) == '<optgroup>') {
            $dropdown .= '<optgroup label="'.octolio_escape($text).'">';
        } elseif (strtolower($value) == '</optgroup>') {
            $dropdown .= '</optgroup>';
        } else {
            $cleanValue = octolio_escape($value);
            $cleanText = octolio_escape($text);
            $dropdown .= '<option value="'.$cleanValue.'"'.($value == $selected ? ' selected="selected"' : '').($disabled ? ' disabled="disabled"' : '').'>'.$cleanText.'</option>';
        }
    }

    $dropdown .= '</select>';

    return $dropdown;
}


function octolio_select_option($value, $text = '', $optKey = 'value', $optText = 'text', $disable = false)
{
    $option = new stdClass();
    $option->$optKey = $value;
    $option->$optText = __($text);
    $option->disable = $disable;

    return $option;
}

function octolio_select_multiple($data, $name, $selected = [], $attribs = [], $optValue = "value", $optText = "text", $translate = false)
{
    if (substr($name, -2) !== '[]') {
        $name .= '[]';
    }

    $attribs['multiple'] = 'multiple';

    $dropdown = '<select name="'.octolio_escape($name).'"';
    foreach ($attribs as $attrib_key => $attrib_value) {
        $dropdown .= ' '.$attrib_key.'="'.addslashes($attrib_value).'"';
    }
    $dropdown .= '>';

    foreach ($data as $one_data_key => $one_data_value) {
        $disabled = '';

        if (is_object($one_data_value)) {
            $value = $one_data_value->$optValue;
            $text = $one_data_value->$optText;

            if (!empty($one_data_value->disable)) {
                $disabled = ' disabled="disabled"';
            }
        } else {
            $value = $one_data_key;
            $text = $one_data_value;
        }

        if ($translate) {
            $text = acym_translation($text);
        }

        if (strtolower($value) == '<optgroup>') {
            $dropdown .= '<optgroup label="'.octolio_escape($text).'">';
        } elseif (strtolower($value) == '</optgroup>') {
            $dropdown .= '</optgroup>';
        } else {
            $text = octolio_escape($text);
            $dropdown .= '<option value="'.octolio_escape($value).'"'.(in_array($value, $selected) ? ' selected="selected"' : '').$disabled.'>'.$text.'</option>';
        }
    }

    $dropdown .= '</select>';

    return $dropdown;
}

function octolio_get_columns($table, $acyTable = true, $putPrefix = true)
{
    if ($putPrefix) {
        global $wpdb;
        $table_name = $wpdb->prefix.$table;
    }

    return $wpdb->get_col('SHOW COLUMNS FROM '.octolio_secure_db_column($table));
}


function octolio_load_object($query)
{
    global $wpdb;

    return $wpdb->get_row($query);
}

function octolio_load_result($query)
{
    global $wpdb;

    return $wpdb->get_var($query);
}

function octolio_load_result_array($query)
{
    global $wpdb;

    return $wpdb->get_col($query);
}

function octolio_loadObjectList($query, $key = '', $offset = null, $limit = null)
{
    global $wpdb;

    if (isset($offset)) {
        $query .= ' LIMIT '.intval($offset).','.intval($limit);
    }

    $results = $wpdb->get_results($query);
    if (empty($key)) {
        return $results;
    }

    $sorted = [];
    foreach ($results as $oneRes) {
        $sorted[$oneRes->$key] = $oneRes;
    }

    return $sorted;
}

function octolio_session()
{
    $session_id = session_id();
    if (empty($session_id)) {
        @session_start();
    }
}

function octolio_enqueue_message($message, $type = 'success')
{
    $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
    $message = is_array($message) ? implode('<br/>', $message) : $message;

    $handledTypes = ['info', 'warning', 'error', 'success'];

    if (in_array($type, $handledTypes)) {
        octolio_session();
        if (empty($_SESSION['octolio_message'.$type]) || !in_array($message, $_SESSION['octolio_message'.$type])) {
            $_SESSION['octolio_message'.$type][] = $message;
        }
    }

    return true;
}


function octolio_display($messages, $type = 'success', $is_dismissible = true, $display_area = 'screen')
{
    if (empty($messages)) return;
    if (!is_array($messages)) $messages = [$messages];

    if ('logs' === $display_area) $log_message = '';
    $is_dismissible = (empty($is_dismissible) ? '' : 'is-dismissible');

    foreach ($messages as $one_message) {

        if ('logs' === $display_area) {
            if (is_array($one_message)) $one_message = implode("\r\n", $one_message);
            $log_message .= $one_message;
            continue;
        }
        echo '<div class="notice notice-'.$type.' '.$is_dismissible.'">';

        if (is_array($one_message)) $one_message = implode('</p><p>', $one_message);

        echo '<div><p>'.$one_message.'</p></div>';

        echo '</div>';
    }
    if ('logs' === $display_area && !empty($display_area)) error_log($log_message);
}

function octolio_display_messages()
{
    $types = ['success', 'info', 'warning', 'error'];
    octolio_session();

    foreach ($types as $id => $type) {
        if (empty($_SESSION['octolio_message'.$type])) continue;

        octolio_display($_SESSION['octolio_message'.$type], $type);
        unset($_SESSION['octolio_message'.$type]);
    }
}

function octolio_log_enqueued_messages()
{
    if (false === WP_DEBUG) false;

    $types = ['success', 'info', 'warning', 'error'];
    octolio_session();

    foreach ($types as $id => $type) {
        if (empty($_SESSION['octolio_message'.$type])) continue;

        error_log(octolio_display($_SESSION['octolio_message'.$type], $type, false, 'logs'));


        unset($_SESSION['octolio_message'.$type]);
    }
}

function octolio_load_assets($cpt_type = '')
{
    if ("octolio_bulkaction" != get_post_type() && "octolio_workflow" != get_post_type()) return;

    //Add script
    wp_register_script('octolio_bulkaction_script', plugins_url('/admin/assets/js/octolio_cpt_script.js', dirname(__FILE__)), ['jquery', 'wp-i18n'], 1.0, false);
    $octolio_global_var = [
        'plugin_url' => plugins_url('', dirname(__FILE__)),
        'cpt_type' => $cpt_type,
    ];

    wp_localize_script('octolio_bulkaction_script', 'octolio_global_var', $octolio_global_var);
    wp_enqueue_script('octolio_bulkaction_script');

    wp_enqueue_script('jquery-ui-datepicker');


    //Include CSS
    wp_enqueue_style('font', plugins_url('/admin/assets/css/font.css', dirname(__FILE__)), [], '1.0');
    wp_enqueue_style('octolio-admin', plugins_url('/admin/assets/css/octolio.css', dirname(__FILE__)), [], '1.0');
    wp_enqueue_style('jquery-css', plugins_url('/admin/assets/lib/jquery/jquery-ui.min.css', dirname(__FILE__)), [], '1.0');

    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', ['jquery']);
    // please create also an empty JS file in your theme directory and include it too
}








