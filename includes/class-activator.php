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
    public static function activate() {
        $option_name = INFO::OPTION_NAME;
        if (empty(get_option($option_name))) {
            $default_options = array(
                'show-links'=>1,
                'all-links'=>5,
                'local-links'=>1,
                'local-url'=>get_site_url(),
            );
            update_option($option_name, $default_options);
        }

        // Add two tables and field to wp_posts
    	global $wpdb;
        $sql = "
            CREATE TABLE `{$wpdb->prefix}xanchors` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `value` varchar(255) DEFAULT NULL,
                `link` varchar(255) NOT NULL,
                `req` int(11) DEFAULT NULL,
                `error404` int(11) DEFAULT NULL,
                PRIMARY KEY  (`id`),
                KEY `xlIndex3` (`id`),
                KEY `xlIndex4` (`link`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            "; #ENGINE=InnoDB
        dbDelta($sql);

        $sql = "
            CREATE TABLE `{$wpdb->prefix}xlinks` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anchor_id` int(12) NOT NULL,
                `post_id` int(11) DEFAULT NULL,
                PRIMARY KEY  (`id`),
                KEY `xlIndex1` (`anchor_id`),
                KEY `xlIndex2` (`post_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            ";
        dbDelta($sql);

        $sql =
            "ALTER TABLE `{$wpdb->prefix}posts`
            ADD `post_link_type` ENUM( 'acceptor', 'donor' ) NOT NULL DEFAULT 'donor';
            ";
        $wpdb->query( $sql );
    }
}
