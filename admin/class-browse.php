<?php

namespace SmartLink;

/**
 * The code used in the admin browse.
 */
class Browse {
	private $plugin_slug;
	private $version;
	private $option_name;
	private $settings;
	private $settings_group;
	private $loader;
	private $idna;
	private $anchors;

	public function __construct( $plugin_slug, $version, $option_name ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/pagination.class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-anchors.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/idna_convert.class.php';

		$this->plugin_slug    = $plugin_slug;
		$this->version        = $version;
		$this->option_name    = $option_name;
		$this->settings       = get_option( $this->option_name );
		$this->settings_group = $this->option_name . '_group';
		$this->loader         = new Loader();
		$this->anchors        = new Anchors( $plugin_slug, $version, $option_name );
		$this->idna           = new idna_convert();
		$this->define_hooks();
	}

	public function define_hooks() {
		// удаление 1 записи
		$this->loader->add_action( 'wp_loaded', $this, 'delete' );
		// удаление отмеченных
		$this->loader->add_action( 'wp_loaded', $this, 'delete_all' );
		// перелинковка
		$this->loader->add_action( 'wp_ajax_process_ajax1', $this, 'process_ajax1' );
		// поиск 404
		$this->loader->add_action( 'wp_ajax_process_ajax2', $this, 'process_ajax2' );
		// при записи поста
		$this->loader->add_action( 'save_post', $this->anchors, 'on_save_post_type', 10, 3 );
		// при удалении поста или перемещении в корзину
		$this->loader->add_action( 'delete_post', $this->anchors, 'on_delete_post' );
		$this->loader->add_action( 'wp_trash_post', $this->anchors, 'on_delete_post' );
		// при восстановлении поста из корзины
		$this->loader->add_action( 'untrashed_post', $this->anchors, 'on_resore_post' );
		// добавляем к окну поста блок опций
		$this->loader->add_action( 'post_submitbox_misc_actions', $this->anchors, 'setup_post_type' );
		// добавляем списки линков на пост. если привязаны
		if ( is_admin() ) {
			$this->loader->add_action( 'load-post.php', $this->anchors, 'add_metaboxes' );
			$this->loader->add_action( 'load-post-new.php', $this->anchors, 'add_metaboxes' );
		}
		// добавляем ссылки в конец поста
		$this->loader->add_filter( 'the_content', $this->anchors, 'add_links_to_content' );
		// добавляем столбец в посты
		$this->loader->add_filter( 'manage_posts_columns', $this->anchors, 'xsl_columns_head');
		// показываем данные в столбце
		$this->loader->add_action( 'manage_posts_custom_column', $this->anchors, 'xsl_columns_content', 10, 2);
		$this->loader->run();
	}

	public function render() {
		if ( isset( $_GET['edit'] ) && $_GET['edit'] == 'true' ) {
			$this->edit();
		} else {
			$this->browse();
		}
	}

	private function browse() {
		global $wpdb;

		$where = "";
		if ( isset( $_POST['xlinks_search'] ) AND $_POST['xlinks_search'] != '' ) {
			$xlinks_search = $_POST['xlinks_search'];
			$where         = " where a.value like '%{$xlinks_search}%' OR a.link like '%{$xlinks_search}%' ";
		}

		$all_items = $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}xanchors a {$where}" );
		$limit     = "";
		if ( $all_items > 0 ) {

			$p = new pagination();
			$p->items( $all_items );
			$p->nextLabel( __( 'Forward', $this->plugin_slug ) );
			$p->prevLabel( __( 'Back', $this->plugin_slug ) );
			$p->limit( Info::XLINKS_PER_PAGE );
			$p->parameterName( Info::XLINKS_PAGE_KEY );
			$p->target( "?page=xsmartlink_list" );
			if ( ! isset( $_GET[ Info::XLINKS_PAGE_KEY ] ) ) {
				$p->currentPage( 1 );
			} else {
				$p->currentPage( (int) $_GET[ Info::XLINKS_PAGE_KEY ] );
			}
			$p->calculate();
			$limit = "LIMIT " . ( $p->page - 1 ) * $p->limit . ", {$p->limit}";
		}

		$anchors = $wpdb->get_results( "
            SELECT
                a.id,
                a.value,
                a.link,
                a.req,
                a.error404,
                IFNULL(l.count,0) count
            FROM
                {$wpdb->prefix}xanchors a
                LEFT JOIN (SELECT anchor_id, count(*) count FROM {$wpdb->prefix}xlinks GROUP BY anchor_id) l ON l.anchor_id = a.id {$where}
            ORDER BY
                a.error404 DESC, a.link ASC
            " . $limit );
		$items   = array();

		foreach ( $anchors as $anchor ) {
			$links = array();
			$data      = $wpdb->get_results( "
                SELECT
                    p.ID /* , p.guid, p.post_title */
                FROM
                    {$wpdb->prefix}xlinks l
                    LEFT JOIN {$wpdb->prefix}posts p ON p.ID = l.post_id
                WHERE
                    l.anchor_id={$anchor->id}
                " );
			foreach ( $data as $row ) {
				$links[] = array('ID'=>$row->ID, 'link'=>get_permalink( $row->ID ));
			}
			$items[] = array(
				'id'       => $anchor->id,
				'anchor'   => $anchor->value,
				'link'     => urldecode( $this->idna->decode( $anchor->link ) ),
				'donors'   => $links,
				'req'      => $anchor->req,
				'count'    => $anchor->count,
				'error404' => $anchor->error404,
			);
		}

		// View
		$heading = __( 'Manage links', $this->plugin_slug );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_browse.php';
	}

	private function edit() {
		global $wpdb;
		$id = (int) $_GET['id'];
		if ( isset( $_POST['submit'] ) ) {
			if ( ! current_user_can( 'manage_options' ) AND ! check_admin_referer( "xlink_edit_form" ) ) {
				die( 'No access!' );
			}
			if ( isset( $_POST['anchor_id'] ) ) {
				$edit_id = (int) $_POST['anchor_id'];
			}
			if ( isset( $_POST['anchor_value'] ) ) {
				$edit_value = (string) $_POST['anchor_value'];
			}
			if ( isset( $_POST['anchor_link'] ) ) {
				$edit_link = (string) $_POST['anchor_link'];
			}
			if ( isset( $_POST['anchor_required'] ) ) {
				$edit_required = (int) $_POST['anchor_required'];
			}
			if ( $edit_required == '' ) {
				$edit_required = 0;
			}
			$edit_row = $wpdb->get_row( "
                SELECT
                    a.id,
                    a.value,
                    a.link,
                    a.req,
                    IFNULL(l.count,0) count
                FROM
                    {$wpdb->prefix}xanchors a
                    LEFT JOIN (SELECT anchor_id, count(*) count FROM {$wpdb->prefix}xlinks GROUP BY anchor_id) l ON l.anchor_id = a.id
                WHERE
                    a.id={$id}"
			);
			if ( $edit_id && $edit_value && $edit_link && $edit_required > 0 ) {
				if ( $edit_required < $edit_row->req ) {
					// если Req после уменьшения остается меньше чем уже связано
					if ( $edit_required < $edit_row->count ) {
						// нужно некоторые связи удалить
						$to_remove = $edit_row->count - $edit_required;
					}
					if ( isset( $to_remove ) ) {
						// получаем список линков вкоторые нужно удалить
						$rows = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}xlinks WHERE anchor_id={$edit_id} limit 0,{$to_remove}" );
						foreach ( $rows as $row ) {
							$wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE id={$row->id}" );
						}
					}
				}
				// записать изменения
				$wpdb->query( "
                    UPDATE
                        {$wpdb->prefix}xanchors
                    SET
                        req={$edit_required},
                        value='{$edit_value}',
                        link='{$edit_link}'
                    WHERE
                        id={$edit_id}" );
				//todo: сделать правильный редирект на список
				echo '<script type="text/javascript">window.location = "' . admin_url( '/admin.php?page=xsmartlink_list' ) . '"</script>';
				exit;
			} else {
				$error           = 1;
				$edit_row->value = $edit_value;
				$edit_row->link  = $edit_link;
				$edit_row->req   = $edit_required;
				echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Error on save. Please refer to admin!', $this->plugin_slug ) . '</strong></p></div>';
			}
		} else {
			$edit_row = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}xanchors WHERE id={$id}" );
		}
		// View
		$heading = __( 'Edit anchor', $this->plugin_slug );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_browse_edit.php';
	}

	public function delete_all() {
		if ( isset( $_POST['delete_xlink'] ) && isset( $_POST['delete_all'] ) ) {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( "delete_all" ) ) {
				die( 'No access!' );
			}
			if ( count( $_POST['delete_xlink'] ) ) {
				global $wpdb;
				foreach ( $_POST['delete_xlink'] as $value ) {
					$ids[] = (int) $value;
				}
				if ( count( $ids ) ) {
					$wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id IN (" . implode( ",", $ids ) . ")" );
					$wpdb->query( "DELETE FROM {$wpdb->prefix}xanchors WHERE id IN (" . implode( ",", $ids ) . ")" );
					wp_redirect( admin_url( '/admin.php?page=xsmartlink_list' ) );
					exit;
				}
			}
		}
	}

	public function delete() {
		if ( isset( $_GET['page'] ) && isset( $_GET['delete'] ) && $_GET['page'] == 'xsmartlink_list' ) {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'delete' ) ) {
				die( 'No access!' );
			}
			if ( isset( $_GET['delete'] ) ) {
				global $wpdb;
				$id = (int) $_GET['delete'];
				$wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id={$id}" );
				$wpdb->query( "DELETE FROM {$wpdb->prefix}xanchors WHERE id={$id}" );
				wp_redirect( admin_url( '/admin.php?page=xsmartlink_list' ) );
				exit;
			}
		}
	}

	/**
	 *  перелинковка всех записей
	 */
	public function process_ajax1() {
		$records = intval( $_POST['records'] );
		if ( $records == 0 ) {
			$posts = $this->anchors->get_posts_forprocess( false );
			_log($posts);
			echo count( $posts );
			die();
		} else {
			$offset = intval( $_POST['offset'] );
			$this->anchors->relink( $offset );
		}
		die();
	}

	/**
	 *  Проверка всех ссылок на наличие и выставление ошибки
	 */
	public function process_ajax2() {
		$records = intval( $_POST['records'] );
		if ( $records == 0 ) {
			$anchors = $this->anchors->get_anchors_forprocess();
			echo count( $anchors );
			die();
		} else {
			$offset = intval( $_POST['offset'] );
			$this->anchors->process_anchor( $offset );
		}
		die();
	}
}
