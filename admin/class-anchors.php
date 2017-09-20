<?php

namespace SmartLink;

class Anchors {
	private $plugin_slug;
	private $version;
	private $option_name;
	private $settings;
	private $import;

	public function __construct( $plugin_slug, $version, $option_name ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/metaboxes.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-import.php';

		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
		$this->option_name = $option_name;
		$this->settings    = get_option( $this->option_name );
		$this->import      = new Import($plugin_slug, $version, $option_name);
	}

	public function add_metaboxes() {
		return new MetaBoxes( __( 'Anchor list' ), $this );
	}

	public function setup_post_type() {
		global $post;
		if ( $post->post_type === 'post' ) {
			echo '<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">' . __( 'Post type:', $this->plugin_slug ) . ' ';
			echo '<input type="radio" name="post_link_type" id="post_link_type_d" value="donor" ' . checked( $post->post_link_type, 'donor', false ) . '/>';
			echo '<label for="for="post_link_type_d" class="select-it">' . __( 'Donor', $this->plugin_slug ) . '</label> ';
			echo '<input type="radio" name="post_link_type" id="post_link_type_a" value="acceptor" ' . checked( $post->post_link_type, 'acceptor', false ) . '/>';
			echo '<label for="post_link_type_a" class="select-it">' . __( 'Acceptor', $this->plugin_slug ) . '</label>';
			echo '</div>';
		}
	}

	// возвращает список ссылок массивом
	private function get_post_anchors( $post ) {
		global $wpdb;
		if ( $post->post_link_type == "donor" ) {
    		$data = $wpdb->get_results( "
            SELECT
                a.link,
                a.value as text
            FROM
                {$wpdb->prefix}xlinks t
                JOIN {$wpdb->prefix}xanchors a ON a.id = t.anchor_id
            WHERE
                t.post_id = {$post->ID}
            ");
            return $data;
    	}
    	return false;
	}

	public function get_post_anchor_list( $post ) {
		$result = "";
	   	$data = $this->get_post_anchors( $post );
	   	if (is_array($data) && count($data)){
			$result .= "<ul>";
	   		foreach($data as $row){
				$result .= '<li><a href="'.$row->link.'">'.$row->text.'</a></li>';
	   		}
			$result .= "</ul>";
	   	}
	   	return $result;
	}

    public function add_links_to_content( $content ){
        if( is_single() && $this->settings['insert_in_pages'] == 1 ){
            //load_plugin_textdomain($this->plugin_slug, false, plugin_dir_path( dirname( __FILE__ ) ) . '/languages' );
	   		$data = $this->get_post_anchors( get_post() );
	   		if (is_array($data) && count($data)){
                $template = "
                <hr>
                    <p style=\"text-align: justify;\">".__('See also:',$this->plugin_slug)."</p>
                    <ul>
                    	{interests_list}
                    </ul>
                <hr>";
                $result = '';
    	   		foreach($data as $row){
					if ( Info::XLINKS_WITHOUT_LINK == true ) {
    					$result .= '<li><b>'.$row->link.'</b>'.$row->text.'</li>';
    				} else {
    					$result .= '<li><a href="'.$row->link.'">'.$row->text.'</a></li>';
    				}
    	   		}
                $template = str_replace("{interests_list}", $result, $template );
            	$content = $content.$template;
            }
        }
        return $content;
    }


	public function on_save_post_type( $post_id, $post, $update ) {
		global $wpdb;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( ! isset( $_POST['post_link_type'] ) ) {
			return false;
		} else {
			$link_type = $_POST['post_link_type'] == "acceptor" ? "acceptor" : "donor";
			$wpdb->update( $wpdb->prefix . 'posts', array( 'post_link_type' => $link_type ), array( 'ID' => $post_id ), array(
				'%s',
				'%s'
			) );

			$post = get_post( $post_id );

			// вызывать нужно при update == true. в ином случае пермальинк будет как для ревизии
			if ($update == true && $this->settings['new_post_to_anchors'] == 1){
				// todo: подумать в каком случае нужно добавлять себя в anchors
				$this->import->post_to_anchor( $post_id );
			}
			if ( $link_type == "acceptor" ) {
				$this->on_delete_post( $post_id );
			} else {
				$this->relink( 0, 0, $post_id );
			}
		}
	}

	public function delete_post() {
		global $post;
		$this->on_delete_post( $post->ID );
	}

	public function on_delete_post( $post_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT anchor_id FROM {$wpdb->prefix}xlinks WHERE post_id = {$post_id}" );
		foreach ( $rows as $row ) {
			$ids[] = $row->anchor_id;
		}
		if ( isset( $ids ) && count( $ids ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id IN (" . implode( ", ", $ids ) . ")" );
		}

		return true;
	}

	public function on_resore_post( $post_id ) {
		$post_data = get_post( $post_id );
		if ( $post_data->post_link_type == 'donor' ) {
			$this->relink( 0, 0, $post_id );
		}
	}

	public function get_anchors_forprocess() {
		global $wpdb;
		$anchors = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}xanchors a" );

		return $anchors;
	}

	public function process_anchor( $offset, $limit = Info::XLINKS_PER_RECORD ) {
		global $wpdb;
		$anchors = $wpdb->get_results( "
        SELECT
            a.id,
            a.link
        FROM
            {$wpdb->prefix}xanchors a
        LIMIT {$offset},{$limit}
        " );
		foreach ( $anchors as $anchor ) {
			$error = (int) $this->check_anchor( $anchor->link );
			$wpdb->query( "
            UPDATE
                {$wpdb->prefix}xanchors
            SET
                error404={$error}
            WHERE
                id={$anchor->id}" );
		}
	}

	private function check_anchor( $link ) {
		$handle = curl_init( $link );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $handle, CURLOPT_TIMEOUT, 3000 );
		$response = curl_exec( $handle );
		$httpCode = curl_getinfo( $handle, CURLINFO_HTTP_CODE );
		if ( $httpCode == 0 ) {
			$httpCode = 404;
		}
		if ( $httpCode == 200 ) {
			$httpCode = 0;
		}
		curl_close( $handle );

		return $httpCode;
	}

	public function get_posts_forprocess( $getcnt, $offset = 0, $limit = Info::XLINKS_PER_RECORD, $one_id = 0 ) {
		global $wpdb;
		$q = "SELECT
            t.ID,
            t.g_count,
            t.l_count
        FROM
            {$wpdb->prefix}posts p
            JOIN (SELECT
                IFNULL(gl.count,0) g_count,
                IFNULL(ll.count,0) l_count,
                p.ID
            FROM
                {$wpdb->prefix}posts p
                LEFT JOIN (SELECT l.post_id, count(*) count FROM {$wpdb->prefix}xlinks l JOIN {$wpdb->prefix}xanchors a ON a.id = l.anchor_id WHERE a.link NOT LIKE '{$this->settings['local_domain']}%' GROUP BY l.post_id) gl ON gl.post_id = p.id
                LEFT JOIN (SELECT l.post_id, count(*) count FROM {$wpdb->prefix}xlinks l JOIN {$wpdb->prefix}xanchors a ON a.id = l.anchor_id WHERE a.link LIKE '{$this->settings['local_domain']}%' GROUP BY l.post_id) ll ON ll.post_id = p.id
            ) t ON t.ID = p.ID
        WHERE
            p.post_type = 'post'
            AND (p.post_status = 'publish' OR p.post_status = 'future')";
		//AND (t.g_count + t.l_count) <> {$options['global_req']}";
		if ( $one_id > 0 ) {
			$q .= " AND p.ID = {$one_id}";
		}
		if ( $getcnt == true ) {
			$q .= " LIMIT {$offset},{$limit}";
		}
		$posts = $wpdb->get_results( $q );
		foreach ( $posts as $post ) {
			_log('Forprocess: ' . $post->ID );
		}

		return $posts;
	}

	public function relink( $offset, $limit = Info::XLINKS_PER_RECORD, $one_id = 0 ) {
		global $wpdb;
		if ( $one_id > 0 ) {
			$posts = $this->get_posts_forprocess( false, $offset, $limit, $one_id );
		} else {
			$posts = $this->get_posts_forprocess( true, $offset, $limit );
			shuffle( $posts );
		}
		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post->ID );
			$anchors = $wpdb->get_results( "
            SELECT
                a.id,
                a.link
            FROM
                {$wpdb->prefix}xanchors a
                /* количество уже привязанных постов к ссылке */
                JOIN (SELECT
                        a.id,
                        IFNULL(COUNT(*),0) count
                    FROM
                        {$wpdb->prefix}xanchors a
                        LEFT JOIN {$wpdb->prefix}xlinks l ON l.anchor_id = a.id
                    GROUP BY
                        a.id) t ON t.id = a.id
            WHERE
                /* если к ссылке привязано постов меньше чем нужно */
                a.req > t.count
                /* если пост еще не привязан к ссылке */
                AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}xlinks l WHERE l.anchor_id=a.id AND l.post_id = {$post->ID})
                /* если пост еще не привязан к сслыке с таким же урлом (к примеру с другим словом) */
                AND NOT EXISTS (SELECT
                                    1
                                FROM
                                    {$wpdb->prefix}xanchors a1
                                    JOIN {$wpdb->prefix}xanchors a2 on a2.link = a1.link
                                    JOIN {$wpdb->prefix}xlinks l on l.anchor_id = a2.id
                                WHERE
                                    a1.id = a.id
                                    AND l.post_id = {$post->ID})
                /* если ссылка на пост не совпадает с урлом ссылки */
                AND a.link <> '".esc_sql($permalink)."'
            GROUP BY
                a.link
            " );
			shuffle( $anchors );
			//_log($anchors);
			//return;
			foreach ( $anchors as $anchor ) {
				if ( substr( strtoupper( $anchor->link ), 0, strlen( $this->settings['local_domain'] ) ) == strtoupper( $this->settings['local_domain'] ) ) {
					if ( $post->l_count < $this->settings['local_req'] ) {
						$post->l_count ++;
						$wpdb->query( "INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') " );
					}
				} else {
					if ( $post->g_count < ( $this->settings['global_req'] - $this->settings['local_req'] ) ) {
						$post->g_count ++;
						$wpdb->query( "INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') " );
					}
				}
				if ( $post->g_count >= ( $this->settings['global_req'] - $this->settings['local_req'] ) && $post->l_count >= $this->settings['local_req'] ) {
					break;
				}
			}
		}
	}
}