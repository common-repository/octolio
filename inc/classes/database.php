<?php

// To prevent calling the plugin directly
defined('ABSPATH') or die('There is no way to do this. Sorry ...');

class octolio_database_class
{

    public function save_db()
    {
        $result = new stdClass();
        $result->status = true;
        $result->error = '';
        $result->backup_file = '';
        $backup_path = WP_PLUGIN_DIR.'/octolio/backup';


        $db_helper = octolio_get('helper.database');
        $db_helper->init('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);

        if (!is_dir($backup_path)) {
            if (!is_writable(WP_PLUGIN_DIR.'/octolio/')) {
                $result->status = false;
                $result->error = __('Can\'t write in Octolio folder to create backup folder');

                echo json_encode($result);
            }
            if (!mkdir($backup_path)) {
                $result->status = false;
                $result->error = __('There was an error while trying to create the Octolio backup folder');
            }
        }

        $rand = substr(md5(microtime()), rand(0, 26), 5);

        $result->backup_file = 'database-backup-'.date('Y-m-d_H:i').'_'.$rand;
        $db_helper->start($backup_path."/".$result->backup_file.'.sql');

        echo json_encode($result);
        exit;
    }
}