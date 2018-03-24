<?php

namespace SmartLink;

/**
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator
{
    /**
     * Sets the default options in the options table on activation.
     */
    public static function activate()
    {
        global $wpdb;
        _log('запустили activate');
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        if (empty(get_option(INFO::OPTION_NAME))) {
            $default_options = array(
                'insert_in_pages' => 1,
                'global_req' => 5,
                'local_req' => 1,
                'local_domain' => get_site_url(),
                'new_post_to_anchors' => 0,
                'new_req' => 3,
                'image_enabled' => 0,
            );
            update_option(INFO::OPTION_NAME, $default_options);
        }

        $sql = "CREATE TABLE `{$wpdb->prefix}xanchors` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `value` varchar(255) DEFAULT NULL,
            `link` varchar(255) NOT NULL,
            `req` int(11) DEFAULT NULL,
            `error404` int(11) DEFAULT NULL,
            `attachment_id` int(11) DEFAULT NULL,
            PRIMARY KEY  (`id`),
            KEY `xlIndex3` (`id`),
            KEY `xlIndex4` (`link`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            ";
        dbDelta($sql);

        $sql = "CREATE TABLE `{$wpdb->prefix}xlinks` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `anchor_id` int(12) NOT NULL,
            `post_id` int(11) DEFAULT NULL,
            PRIMARY KEY  (`id`),
            KEY `xlIndex1` (`anchor_id`),
            KEY `xlIndex2` (`post_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            ";
        dbDelta($sql);

        $sql = "CREATE TABLE `{$wpdb->prefix}xtempsort` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` int(11) unsigned NOT NULL,
            `sort_num` int(11) unsigned NOT NULL,
            PRIMARY KEY  (`id`),
            KEY `xlIndex5` (`sort_num`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            ";
        dbDelta($sql);

        $existing_columns = $wpdb->get_col("DESC `{$wpdb->prefix}posts`", 0);
        if (!in_array('post_link_type', $existing_columns)) {
            $sql = "ALTER TABLE `{$wpdb->prefix}posts`
            ADD `post_link_type` ENUM( 'acceptor', 'donor' ) NOT NULL DEFAULT 'donor';
            ";
            $wpdb->query($sql);
        }

        update_option(INFO::OPTION_NAME . '_db_version', INFO::DB_VERSION);
    }
}
