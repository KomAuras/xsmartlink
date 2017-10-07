<?php

namespace SmartLink;

/**
 * The class containing informatin about the plugin.
 */
class Info {
    /**
     * The plugin slug.
     *
     * @var string
     */
    const SLUG = 'xsmartlink';

    /**
     * The plugin version.
     *
     * @var string
     */
    const VERSION = '2.0.3';

    /**
     * The DB version.
     *
     * @var string
     */
    const DB_VERSION = '2';

    /**
     * The nae for the entry in the options table.
     *
     * @var string
     */
    const OPTION_NAME = 'xsmartlink';

    /**
     * The URL where your update server is located (uses wp-update-server).
     *
     * @var string
     */
    const UPDATE_URL = 'https://bitbucket.org/EvgenyStefanenko/';
    const CONSUMER_KEY = '9bPtc84wYkmtgXVPMh';
    const CONSUMER_SECRET = 'R4ce25jSmEPWU3vn6XerMqcgtZTCa5rW';

    //const XLINKS_PER_PAGE = 5;
    const XLINKS_PAGE_KEY = 'paged';
    const XLINKS_PER_RECORD = 20;
    const XLINKS_WITHOUT_LINK = false;

    /**
     * Retrieves the plugin title from the main plugin file.
     *
     * @return string The plugin title
     */
    public static function get_plugin_title() {
        $path = plugin_dir_path( dirname( __FILE__ ) ) . self::SLUG . '.php';

        return get_plugin_data( $path )['Name'];
    }
}
