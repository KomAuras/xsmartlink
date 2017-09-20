<?php

namespace SmartLink;

/**
 * The code used in the admin browse.
 */
class Import {
	private $plugin_slug;
	private $version;
	private $option_name;
	private $settings;
	private $settings_group;
	private $loader;

	public function __construct( $plugin_slug, $version, $option_name ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';

		$this->plugin_slug    = $plugin_slug;
		$this->version        = $version;
		$this->option_name    = $option_name;
		$this->settings       = get_option( $this->option_name );
		$this->settings_group = $this->option_name . '_group';
		$this->loader = new Loader();
		$this->define_hooks();
	}

	public function define_hooks() {
		// процесс экспорта
		$this->loader->add_action( 'wp_loaded', $this, 'export_process' );
		$this->loader->run();
	}


	public function render() {
		$this->show();
	}

	public function export() {

		// View
		$heading = __( 'Export', $this->plugin_slug );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_export.php';
	}

	public function export_process()
    {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'export_process' && isset( $_POST['submit'] ) ) {
            if( !current_user_can( 'manage_options' ) AND !check_admin_referer( "xlinks_export" ) ){
                die( 'No access!' );
            }
            global $wpdb;
            $anchors = $wpdb->get_results("
                SELECT
                    a.value, a.link, a.req
                FROM
                    {$wpdb->prefix}xanchors a
                ");
            header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header("Content-type: application/CSV");
            header("Content-Disposition: attachment; filename=export.csv");
            foreach ($anchors as $anchor){
                echo iconv('utf-8', 'windows-1251', $anchor->value).";";
                echo iconv('utf-8', 'windows-1251', $anchor->link).";";
                echo iconv('utf-8', 'windows-1251', $anchor->req)."\n";
            }
            exit;
        }
	}


	public function post_to_anchor( $post_id ) {
		$post = get_post( $post_id );
		$this->add_anchor(array($post->post_title, get_permalink( $post->ID ), $this->settings['new_req']), $flags, false);
	}

	public function add_anchor( $vals, &$flags, $update_req=true ) {
		global $wpdb;
    	$vals[0] = str_replace( '\"', '', $vals[0] );
    	$vals[0] = str_replace( '"', '', $vals[0] );
    	$count = $wpdb->get_var( "SELECT count(*) as count FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "'" );
    	if ( $count == 0 ) {
    		$sql = $this->return_sql( $vals );
    		if ( $sql ) {
				$wpdb->query( "INSERT INTO {$wpdb->prefix}xanchors  (`value`, `link`, `req`) VALUES {$sql}" );
				if (isset($flags)){
    				$flags['added']++;
    			}
    		}
    	} elseif($update_req) {
    		$id1 = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "' LIMIT 1" );
        	$count = isset($vals[2]) ? $vals[2] : $this->settings['new_req'];
    		if ( $id1 > 0 && $count > 0 ) {
    			$wpdb->query( "UPDATE {$wpdb->prefix}xanchors SET req = req + {$count} WHERE id={$id1}" );
				if (isset($flags)){
    				$flags['updated']++;
    			}
    		}
    	}
    }

    private function return_sql( $data ){
        $count = isset($data['2']) ? $data['2'] : $this->settings['new_req'];
        if( $data['0']!="" AND $data['1']!="" AND $count!="" ){
            $result =  '("'.esc_sql($data[0]).'","'.$data[1].'", "'.$count.'")';
            return $result;
        }
        return false;
    }

    function rem_entities( $str ) {
        if(substr_count($str, '&') && substr_count($str, ';')) {
          // Find amper
          $amp_pos = strpos($str, '&');
          //Find the ;
          $semi_pos = strpos($str, ';');
          // Only if the ; is after the &
          if($semi_pos > $amp_pos) {
            //is a HTML entity, try to remove
            $tmp = substr($str, 0, $amp_pos);
            $tmp = $tmp. substr($str, $semi_pos + 1, strlen($str));
            $str = $tmp;
            //Has another entity in it?
            if(substr_count($str, '&') && substr_count($str, ';'))
              $str = $this->rem_entities($tmp);
          }
        }
        return $str;
	}

	private function show() {
		global $wpdb;

		$delimiter = isset($_POST['delimiter']) ? $_POST['delimiter'] : ';';
		if ( isset( $_POST['submit'] ) ) {
			if ( ! current_user_can( 'manage_options' ) AND ! check_admin_referer( "xlinks_add" ) ) {
				die( 'No access!' );
			}
			$flags = array('updated'=>0,'added'=>0);
			// proccessing import file
			if ( $_FILES['import_file']['tmp_name'] ) {
				if ( ( $handle = fopen( $_FILES['import_file']['tmp_name'], "r" ) ) !== false ) {
					while ( ( $data = fgets( $handle ) ) !== false ) {
						$data = trim( $data );
						$data = iconv( 'windows-1251', 'utf-8', $data );
						$data = preg_replace( "/&#(\d+);/", "", $data );
						$data = $this->rem_entities( $data );
						if ( strlen( $data ) ) {
							$this->add_anchor( explode( $delimiter, $data ), $flags);
						}
					}
					fclose( $handle );
				}
			}
			// insert values from textarea
			if ( $_POST['import_area'] ) {
				$data = explode( "\n", $_POST['import_area'] );
				foreach ( $data as $row ) {
					$row = trim( $row );
					if ( strlen( $row ) ) {
						$this->add_anchor( explode( $delimiter, $row ), $flags);
					}
				}
			}

			if ( $flags['added'] || $flags['updated']) {
				if ($flags['added']){
					echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Connections add.', $this->plugin_slug ) . '</strong></p></div>';
				}
    			if ( $flags['updated'] ) {
    				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Some records updated.', $this->plugin_slug ) . '</strong></p></div>';
    			}
			} else {
				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Nothing to add.', $this->plugin_slug ) . '</strong></p></div>';
			}
		}

		// View
		$heading = __( 'Import', $this->plugin_slug );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_import.php';
	}
}
