<?php

namespace SmartLink;

/**
 * The code used in the admin browse.
 */
class Browse
{
    private $plugin_slug;
    private $version;
    private $option_name;
    private $settings;
    private $settings_group;
    private $idna;

    public function __construct($plugin_slug, $version, $option_name) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->option_name = $option_name;
        $this->settings = get_option($this->option_name);
        $this->settings_group = $this->option_name.'_group';

        require_once plugin_dir_path(dirname(__FILE__)) . 'classes/pagination.class.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'classes/idna_convert.class.php';
		$this->idna = new idna_convert();
    }

    public function render() {
    	if (isset($_GET['edit']) && $_GET['edit'] == 'true'){
    		$this->edit();
    	}
  		$this->browse();
  	}

    private function browse() {
    	global $wpdb, $xlinks_idn;

        $where = "";
        if (isset($_POST['xlinks_search']) AND $_POST['xlinks_search'] != ''){
            $xlinks_search = $_POST['xlinks_search'];
	        _log($xlinks_search);
            $where = " where a.value like '%{$xlinks_search}%' OR a.link like '%{$xlinks_search}%' ";
        }

        $all_items=$wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}xanchors a {$where}" );
        $limit = "";
        if($all_items > 0) {

            $p = new pagination();
            $p->items($all_items);
            $p->nextLabel(__('Forward','xlinks'));
            $p->prevLabel(__('Back','xlinks'));
            $p->limit(Info::XLINKS_PER_PAGE);
            $p->parameterName(Info::XLINKS_PAGE_KEY);
            $p->target("?page=xsmartlink_list");
            if(!isset($_GET[Info::XLINKS_PAGE_KEY])) {
                $p->currentPage(1);
            } else {
                $p->currentPage((int)$_GET[Info::XLINKS_PAGE_KEY]);
            }
            $p->calculate();
            $limit = "LIMIT ".($p->page-1)*$p->limit.", {$p->limit}";
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
            ".$limit );
        $items = array();

        foreach($anchors as $anchor){
            $donors_html = "";
            $donors = $wpdb->get_results( "
                SELECT
                    p.ID, p.guid, p.post_title
                FROM
                    {$wpdb->prefix}xlinks l
                    LEFT JOIN {$wpdb->prefix}posts p ON p.ID = l.post_id
                WHERE
                    l.anchor_id={$anchor->id}
                " );
            foreach( $donors as $donor ){
                $donors_html .= "<a href='".get_permalink( $donor->ID )."' target='_blank'>".$donor->ID. "</a> ";
            }
            if( !$donors_html ) $donors_html = __("Not donors yet.",'xlinks');
            $items[] = array(
                'id'=>$anchor->id,
                'anchor'=>$anchor->value,
                'link'=>urldecode($this->idna->decode($anchor->link)),
                'donors'=>$donors_html,
                'req'=>$anchor->req,
                'count'=>$anchor->count,
                'error404'=>$anchor->error404,
            );
        }

        // View
        $heading = Info::get_plugin_title();
        require_once plugin_dir_path(dirname(__FILE__)).'admin/partials/view_browse.php';
        exit;
    }

    private function edit() {
        global $wpdb;
        $id = (int)$_GET['id'];
        if( isset( $_POST['submit'] ) ){
            if( function_exists( "current_user_can" ) AND !current_user_can( 8 ) ){
                die( 'No access!' );
            }
            if( function_exists( "check_admin_referer" ) AND !check_admin_referer( "xlink_edit_form" ) ){
                die( 'No access!' );
            }
            if( isset( $_POST['anchor_id'] ) ){
                $edit_id = (int)$_POST['anchor_id'];
            }
            if( isset( $_POST['anchor_value'] ) ){
                $edit_value = (string)$_POST['anchor_value'];
            }
            if( isset( $_POST['anchor_link'] ) ){
                $edit_link = (string)$_POST['anchor_link'];
            }
            if( isset( $_POST['anchor_required'] ) ){
                $edit_required = (int)$_POST['anchor_required'];
            }
            if ($edit_required ==''){$edit_required=0;}
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
            if ($edit_id && $edit_value && $edit_link && $edit_required > 0){
                if ($edit_required < $edit_row->req){
                    // если Req после уменьшения остается меньше чем уже связано
                    if ($edit_required < $edit_row->count){
                        // нужно некоторые связи удалить
                        $to_remove = $edit_row->count - $edit_required;
                    }
                    if (isset($to_remove)){
                        // получаем список линков вкоторые нужно удалить
                        $rows = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}xlinks WHERE anchor_id={$edit_id} limit 0,{$to_remove}" );
                        foreach( $rows as $row ){
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
                        id={$edit_id}");
                // сделать редирект на список
                $this->browse();
                $edit_row->value = $edit_value;
                $edit_row->link = $edit_link;
                $edit_row->req = $edit_required;
                echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>'.__('Saved!','xlinks').'</strong></p></div>';
            }else{
                $error = 1;
                $edit_row->value = $edit_value;
                $edit_row->link = $edit_link;
                $edit_row->req = $edit_required;
                echo '<br /><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>'.__('Error on save. Please refer to admin!','xlinks').'</strong></p></div>';
            }
        }else{
            $edit_row = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}xanchors WHERE id={$id}");
        }
        // View
        $heading =  _e('Edit anchor','xlinks');
        require_once plugin_dir_path(dirname(__FILE__)).'admin/partials/view_browse_edit.php';
        exit;
    }

    function delete_all(){
    	_log($_GET);
    	_log($_GET['page'] . "-" . $_GET['delete_all']);
    	if( isset($_GET['page']) && isset($_GET['delete_all']) && $_GET['page'] == 'xsmartlink_list' ) {
            if( !current_user_can( 'manage_options' ) || !check_admin_referer( "delete_all" )){
                die( 'No access!' );
            }
            if( isset( $_POST['delete_all'] ) && isset($_POST['delete_xlink']) && count( $_POST['delete_xlink'] ) ){
            	global $wpdb;
                foreach( $_POST['delete_xlink'] as $value ){
                    $ids[] = (int)$value;
                }
                if (count($ids)){
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id IN (".implode( ",", $ids ).")");
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}xanchors WHERE id IN (".implode( ",", $ids ).")");
                    wp_redirect(admin_url('/admin.php?page=xsmartlink_list'));
            		exit;
                }
            }
        }
    }

    function delete(){
    	if( isset($_GET['page']) && isset($_GET['delete']) && $_GET['page'] == 'xsmartlink_list' ) {
            if( !current_user_can( 'manage_options' ) || !check_admin_referer( 'delete' )) {
                die( 'No access!' );
            }
            if( isset($_GET['delete']) ){
            	global $wpdb;
                $id = (int)$_GET['delete'];
                $wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id={$id}" );
                $wpdb->query( "DELETE FROM {$wpdb->prefix}xanchors WHERE id={$id}" );
                wp_redirect(admin_url('/admin.php?page=xsmartlink_list'));
         		exit;
            }
    	}
    }
}
