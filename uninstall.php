<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete tables and remove field from wp_posts
global $wpdb;
$sql = "DROP  TABLE `{$wpdb->prefix}xlinks`";
$wpdb->query( $sql );

$sql = "DROP  TABLE `{$wpdb->prefix}xanchors`";
$wpdb->query( $sql );

$sql = "DROP  TABLE `{$wpdb->prefix}xtempsort`";
$wpdb->query( $sql );

$sql = "ALTER TABLE `{$wpdb->prefix}posts` DROP `post_link_type`;";
$wpdb->query( $sql );

// Delete options
delete_option('xsmartlink');

// Delete options in Multisite
delete_site_option('xsmartlink');
