<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


/**
 * Generic class to generate Octolio listings
 */
class octolio_listing extends WP_List_Table
{

    public function __construct($args = [])
    {
        parent::__construct($args);
    }

    /**
     * Database column used to select users
     * @var string
     */
    private $primary_key;

    /**
     * Database column used to select users
     * @var string
     */
    private $primary_key_label;

    /**
     * Table columns
     * @var array
     */
    private $columns;

    /**
     * Sortable columns
     * @var array
     */
    private $sortable_columns;

    /**
     * Sortable columns
     * @var array
     */
    private $hidden_columns;

    /**
     * Data inserted into the table
     * @var array
     */
    private $data;

    /**
     * Number of entry per page
     * @var integer
     */
    private $per_page = 20;

    /**
     * @var array
     */
    private $listing_rows_format = [];


    /**
     * @var array
     */
    private $listing_bulk_actions = [];

    /**
     * @var array
     */
    private $listing_buttons = [];

    /**
     * @return string
     */
    public function get_primary_key()
    {
        return $this->primary_key;
    }

    /**
     * @param string $primary_key
     */
    public function set_primary_key($primary_key)
    {
        $this->primary_key = $primary_key;
    }

    /**
     * @return string
     */
    public function get_primary_key_label()
    {
        return $this->primary_key_label;
    }

    /**
     * @param string $primary_key_label
     */
    public function set_primary_key_label($primary_key_label)
    {
        $this->primary_key_label = $primary_key_label;
    }

    /**
     * @return array
     */
    public function get_columns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function set_columns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = [];

        foreach ($this->sortable_columns as $one_sortable_column_key => $one_sortable_column_name) {
            $sortable_columns[$one_sortable_column_key] = [$one_sortable_column_key, false];
        }

        return $sortable_columns;
    }

    /**
     * @param array $sortable_columns
     */
    public function set_sortable_columns($sortable_columns)
    {
        //Delete the checkbox column
        if (isset($sortable_columns['cb'])) unset($sortable_columns['cb']);

        $this->sortable_columns = $sortable_columns;
    }

    /**
     * @return array
     */
    public function get_hidden_columns()
    {
        return $this->hidden_columns;
    }

    /**
     * @param array $hidden_columns
     */
    public function set_hidden_columns($hidden_columns)
    {
        $this->hidden_columns = $hidden_columns;
    }

    /**
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function set_data($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function get_per_page()
    {
        return $this->per_page;
    }

    /**
     * @param int $per_page
     */
    public function set_per_page($per_page)
    {
        $this->per_page = $per_page;
    }

    /**
     * @return array
     */
    public function get_listing_rows_format()
    {
        return $this->listing_rows_format;
    }

    /**
     * @param array $listing_rows_format
     */
    public function set_listing_rows_format($listing_rows_format)
    {
        $this->listing_rows_format = $listing_rows_format;
    }

    /**
     * @return array
     */
    public function get_listing_bulk_actions()
    {
        return $this->listing_bulk_actions();
    }

    /**
     * @param array $listing_bulk_actions
     */
    public function set_listing_bulk_actions($listing_bulk_actions)
    {
        $this->listing_bulk_actions = $listing_bulk_actions;
    }

    /**
     * @return array
     */
    public function get_listing_buttons()
    {
        return $this->listing_buttons;
    }

    /**
     * @param array $listing_buttons
     */
    public function set_listing_buttons($listing_buttons)
    {
        $this->listing_buttons = $listing_buttons;
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        //Is there something to do before displaying entries?
        $current_action = $this->current_action();

        //Yes! Let's do it!
        if (!empty($current_action)) {

            //Some security checks
            $referer = octolio_get_var('string', '_wp_http_referer', '');

            if (isset($referer) && !empty($referer)) {

                $parts = parse_url($referer);
                parse_str($parts['query'], $query);

                //Request is coming from the page we're on ? Ok let's continue
                if (get_current_screen()->id == 'octolio_page_'.$query['page']) {

                    //We verify the nonce
                    $nonce = octolio_get_var('string', '_wpnonce', '');

                    if (isset($nonce) && !empty($nonce)) {

                        $action = 'bulk-'.$this->_args['plural'];
                        if (!wp_verify_nonce($nonce, $action)) wp_die('Nope! Security check failed!');

                        //Nonce is ok, we trigger bulk actions ;-)
                        $selected_entities = octolio_get_var('array', 'allentities', []);
                        if (!empty($selected_entities)) apply_filters('handle_octolio_bulk_actions-'.get_current_screen()->id, $current_action, $selected_entities);
                    }

                    //We remove referer & nonce from the URL. Maybe not clean but don't have a better idea.
                    wp_redirect(remove_query_arg(['_wp_http_referer', '_wpnonce'], wp_unslash($_SERVER['REQUEST_URI'])));
                }
            }
        }

        $columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();
        $hidden = $this->get_hidden_columns();

        $data = $this->get_data();
        usort($data, [&$this, 'sort_data']);
        $per_page = $this->get_per_page();
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page' => $per_page,
            ]
        );
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->_column_headers = [$this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns()];
        $this->items = $data;
    }


    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {

        // If orderby is set, use this as the sort column
        $orderby = octolio_get_var('order_by', 'orderby', $this->primary_key);

        // If order is set use this as the order
        $order = octolio_get_var('word', 'order', 'asc');

        $result = strcmp($a[$orderby], $b[$orderby]);
        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param Array  $item        Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        //We have a specific format for this row, let's apply it
        if (!empty($this->listing_rows_format[$column_name])) {

            $text_to_display = '';
            if ('date' == $this->listing_rows_format[$column_name]['row']['type']) {
                $text_to_display = octolio_to_date_format($item[$column_name], $this->listing_rows_format[$column_name]['row']['format']);
            } else {
                $text_to_display = $item[$column_name];
            }

            //Actions
            $actions = [];
            if (!empty($this->listing_rows_format[$column_name]['actions'])) {

                //All the additional actions
                $actions = $this->listing_rows_format[$column_name]['actions'];

                //Store all these actions be in the correct format
                $row_actions = [];

                //Current page
                $current_page = octolio_get_var('string', 'page', '');

                //Some actions needs the item primary key to be inserted dynamically? Let's do it!
                foreach ($actions as $one_label => $one_action) {

                    $row_actions[] = sprintf('<a href="?page=%s&action=%s&'.$this->primary_key.'=%s">%s</a>', $current_page, $one_action, $item[$this->listing_rows_format[$column_name]['primary_key']], $one_label);
                }
            }

            return sprintf('%1$s %2$s', $item[$column_name], $this->row_actions($actions));
        }

        return $item[$column_name];
    }

    /**
     * Handles the checkbox column output.
     *
     * @param $item row item
     *
     */
    public function column_cb($item)
    {
        ?>
		<label class="screen-reader-text" for="listing_<?php echo $this->primary_key; ?>"><?php echo sprintf(__('Select %s'), $item[$this->get_primary_key_label()]); ?></label>
		<input type="checkbox" id="listing_<?php echo esc_attr($item[$this->get_primary_key()]); ?>" name="allentities[]" value="<?php echo esc_attr($item[$this->get_primary_key()]); ?>" />
        <?php
    }

    /**
     * Displays the "screen options" option at the top of the listing page
     *
     */
    static function display_screen_options()
    {
        //Didn't find a better way to this... unfortunately
        $current_page = get_current_screen()->id;
        $splited_page_name = explode('-', $current_page);

        if (!empty($splited_page_name[1])) {

            add_screen_option(
                'per_page',
                [
                    'label' => __(
                        ucfirst($splited_page_name[1]).' per page',
                        'octolio'
                    ),
                    'option' => 'octolio_'.$splited_page_name[1].'_per_page',
                ]
            );
        }
    }

    /**
     * Displays bulk actions dropdown
     *
     */
    function get_bulk_actions()
    {

        return $this->listing_bulk_actions;
    }

}

?>