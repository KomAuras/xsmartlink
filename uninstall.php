<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete tables and remove field from wp_posts
global $wpdb;
$sql1  = "DROP  TABLE `{$wpdb->prefix}xlinks`";
$sql2  = "DROP  TABLE `{$wpdb->prefix}xanchors`";
$sql3  = "ALTER TABLE `{$wpdb->prefix}posts` DROP `post_link_type`;";
$wpdb->query( $sql1 );
$wpdb->query( $sql2 );
$wpdb->query( $sql3 );

// Delete options
delete_option('xsmartlink');

// Delete options in Multisite
delete_site_option('xsmartlink');
