<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');


//Save every screen option parameters
add_filter('set-screen-option', 'save_screen_option', 10, 3);


function octolio_init_menu()
{
    add_menu_page('Octolio', 'Octolio', 'manage_options', 'edit.php?post_type=octolio_bulkaction', null, '', 44);
    add_submenu_page('edit.php?post_type=octolio_bulkaction', __('Manual Actions', 'octolio'), __('Manual Actions', 'octolio'), 'manage_options', 'edit.php?post_type=octolio_bulkaction');
    add_submenu_page('edit.php?post_type=octolio_bulkaction', __('Automatic Actions', 'octolio'), __('Automatic Actions', 'octolio'), 'manage_options', 'edit.php?post_type=octolio_workflow');

    //add_submenu_page('octolio-options', __('Dashboard', 'octolio'), __('Dashboard', 'octolio'), 'manage_options', 'octolio-options');
    //add_submenu_page('octolio-options', __('Mass Actions', 'octolio'), __('Mass Actions', 'octolio'), 'manage_options', 'edit.php?post_type=octolio_bulkaction', null);
    //add_submenu_page('octolio-options', __('Configuration', 'octolio'), __('Configuration', 'octolio'), 'manage_options', 'octolio-config');
}

add_action('admin_menu', 'octolio_init_menu');