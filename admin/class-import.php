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

	public function __construct( $plugin_slug, $version, $option_name ) {
		$this->plugin_slug    = $plugin_slug;
		$this->version        = $version;
		$this->option_name    = $option_name;
		$this->settings       = get_option( $this->option_name );
		$this->settings_group = $this->option_name . '_group';
	}

	public function render() {
		$this->show();
	}

	private function show() {
		global $wpdb;

		$delimiter = "";
		if ( $delimiter == "" ) {
			$delimiter = ";";
		}
		if ( isset( $_POST['submit'] ) ) {
			if ( ! current_user_can( 'manage_options' ) AND ! check_admin_referer( "xlinks_add" ) ) {
				die( 'No access!' );
			}
			if ( isset( $_POST['delimiter'] ) ) {
				$delimiter = $_POST['delimiter'];
			}
			$updated = 0;
			// proccessing import file
			if ( $_FILES['import_file']['tmp_name'] ) {
				if ( ( $handle = fopen( $_FILES['import_file']['tmp_name'], "r" ) ) !== false ) {
					while ( ( $data = fgets( $handle ) ) !== false ) {
						$data = trim( $data );
						$data = iconv( 'windows-1251', 'utf-8', $data );
						$data = preg_replace( "/&#(\d+);/", "", $data );
						$data = xl_remEntities( $data );
						if ( isset( $data ) ) {
							$vals    = explode( $delimiter, $data );
							$vals[0] = str_replace( '\"', '', $vals[0] );
							$vals[0] = str_replace( '"', '', $vals[0] );
							//$vals[1] = 'http://' . str_replace('http://', '', $vals[1]);
							$count = $wpdb->get_var( "SELECT count(*) as count FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "'" );
							if ( $count == 0 ) {
								$sql_string = xl_return_sql( $vals );
								if ( $sql_string ) {
									$sql[] = $sql_string;
								}
							} else {
								$id1 = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "' LIMIT 1" );
								if ( $id1 > 0 && isset( $vals[2] ) && $vals[2] > 0 ) {
									$wpdb->query( "UPDATE {$wpdb->prefix}xanchors SET req = req + {$vals[2]} WHERE id={$id1}" );
									$updated ++;
								}
							}
						}
					}
					fclose( $handle );
				}
			}
			// insert values from textarea
			if ( $_POST['import_area'] ) {
				$import_text = explode( "\n", $_POST['import_area'] );
				foreach ( $import_text as $import_text_one ) {
					$import_text_one = trim( $import_text_one );
					if ( strlen( $import_text_one ) ) {
						$vals    = explode( $delimiter, $import_text_one );
						$vals[0] = str_replace( '\"', '', $vals[0] );
						$vals[0] = str_replace( '"', '', $vals[0] );
						#$vals[0] = mysqli_real_escape_string($vals[0]);
						//$vals[1] = 'http://' . str_replace('http://', '', $vals[1]);
						$count = $wpdb->get_var( "SELECT count(*) as count FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "'" );
						if ( $count == 0 ) {
							$sql_string = xl_return_sql( $vals );
							if ( $sql_string ) {
								$sql[] = $sql_string;
							}
						} else {
							$id1 = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}xanchors WHERE `value` = '" . esc_sql( $vals[0] ) . "' AND `link` = '" . $vals[1] . "' LIMIT 1" );
							if ( $id1 > 0 && isset( $vals[2] ) && $vals[2] > 0 ) {
								$wpdb->query( "UPDATE {$wpdb->prefix}xanchors SET req = req + {$vals[2]} WHERE id={$id1}" );
								$updated ++;
							}
						}
					}
				}

			}

			if ( isset( $sql ) && count( $sql ) ) {
				$sql = implode( ", ", $sql );
				$wpdb->query( "INSERT INTO {$wpdb->prefix}xanchors  (`value`, `link`, `req`) VALUES {$sql}" );
				echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Connections add.', 'xlinks' ) . '</strong></p></div>';
			} else {
				echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Nothing to add.', 'xlinks' ) . '</strong></p></div>';
			}
			if ( $updated > 0 ) {
				echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Some records updated.', 'xlinks' ) . '</strong></p></div>';
			}
		}

		// View
		$heading = __( 'Import', 'xlinks' );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_import.php';
	}
}
