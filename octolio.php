<?php
/*
Plugin Name: Octolio
Description: WooCommerce smart coupons & bulk actions.
Version: 0.0.6
Author: Octolio bulk actions team
Author URI: https://www.octolio.com
License: GPLv2
Text Domain: octolio
Domain Path: /languages
*/

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


/*
 * Init the plugin
 */

function octolio_init($hook)
{
    require_once dirname(__FILE__).'/inc/helpers/helper.php';
    require_once dirname(__FILE__).'/inc/library/integration.php';
    require_once dirname(__FILE__).'/inc/library/class.php';

    $workflow_class = octolio_get('class.workflow');
    add_action('init', [$workflow_class, 'register_hook_actions'], 10);

    if (is_admin() || is_network_admin()) {

        require_once dirname(__FILE__).'/inc/admin/admin.php';
        require_once dirname(__FILE__).'/inc/classes/bulkaction.php';

        octolio_display_messages();

        //Register CPT + Hook on status change
        $bulkaction_class = octolio_get('class.bulkaction');

        add_action('transition_post_status', [$bulkaction_class, 'on_publish_bulkaction'], 10, 3);
        add_action('transition_post_status', [$workflow_class, 'on_publish_workflow'], 10, 3);

        add_action('init', [$bulkaction_class, 'register_bulkaction_cpt'], 10);
        add_action('init', [$workflow_class, 'register_workflow_cpt'], 10);

        if (!get_option('octolio_init', false)) {
            $post_data = [
                'post_title' => wp_strip_all_tags(__('Delete user who didn\'t log since 90 days ')),
                'post_type' => 'octolio_bulkaction',
            ];
            @wp_insert_post($post_data, false);

            $post_data = [
                'post_title' => wp_strip_all_tags(__('My first conditionnal WooCommerce coupon')),
                'post_type' => 'octolio_workflow',
            ];
            @wp_insert_post($post_data, false);
            update_option('octolio_init', '1');
        }
    }


    load_plugin_textdomain('octolio', false, dirname(plugin_basename(__FILE__)).'/languages/');

    //////////////PROOOOO//////////////
    add_action('post_submitbox_start', 'pro_add_savedb_button');

    function pro_add_savedb_button()
    {
        if ("octolio_bulkaction" != get_post_type()) return;

        ?>

		<div class="octolio_save_db">
			<span><?php echo __('Click to save your database before executing Bulk Actions'); ?></span>
            <?php submit_button($text = 'Save database (Pro version)', $type = 'primary large', $name = 'save_db_button', $wrap = true, $other_attributes = 'disabled '); ?>
			<span id="save_db_result"></span>
		</div>
        <?php
    }
}


add_action('plugins_loaded', 'octolio_init', 999);

register_activation_hook(__FILE__, 'block_direct_access');
function block_direct_access()
{
    // Get path to main .htaccess for WordPress
    $htaccess = get_home_path().".htaccess";
    $lines = ["RedirectMatch 403 .*\/octolio\/(.(?!.*\.css$|.*\.js|.*\.svg|.*\.eot|.*\.ttf|.*\.woff|.*\.png|.*\.gif))*$"];
    insert_with_markers($htaccess, "Octolio plugin", $lines);
}
