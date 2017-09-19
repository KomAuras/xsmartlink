<?php

namespace SmartLink;

class Anchors {
	private $plugin_slug;
	private $version;
	private $option_name;
	private $settings;

	public function __construct( $plugin_slug, $version, $option_name ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/metaboxes.php';
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
		$this->option_name = $option_name;
		$this->settings    = get_option( $this->option_name );
	}

	public function add_metaboxes() {
		return new MetaBoxes( __( 'Anchor list' ), $this );
	}

	public function setup_post_type() {
		global $post;
		if ( $post->post_type === 'post' ) {
			echo '<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">' . __( 'Post type:', 'xlinks' ) . ' ';
			echo '<input type="radio" name="post_link_type" id="post_link_type_d" value="donor" ' . checked( $post->post_link_type, 'donor', false ) . '/>';
			echo '<label for="for="post_link_type_d" class="select-it">' . __( 'Donor', 'xlinks' ) . '</label> ';
			echo '<input type="radio" name="post_link_type" id="post_link_type_a" value="acceptor" ' . checked( $post->post_link_type, 'acceptor', false ) . '/>';
			echo '<label for="post_link_type_a" class="select-it">' . __( 'Acceptor', 'xlinks' ) . '</label>';
			echo '</div>';
		}
	}

	public function get_list( $post ) {
		global $wpdb;
		$xlinks = $wpdb->get_results( "
        SELECT
            a.link,
            a.value
        FROM
            {$wpdb->prefix}xlinks t
            JOIN {$wpdb->prefix}xanchors a ON a.id = t.anchor_id
        WHERE
            t.post_id = {$post->ID}
        " );
		if ( count( $xlinks ) ) {
			$xlinks_links = "";
			foreach ( $xlinks as $xlink ) {
				if ( Info::XLINKS_WITHOUT_LINK == true ) {
					$xlinks_links .= "<li><b>{$xlink->value}</b> {$xlink->link}</li>";
				} else {
					$xlinks_links .= "<li><a href='{$xlink->link}' title='{$xlink->value}'>{$xlink->value}</a></li>";
				}
			}

			return array( $xlinks_links, count( $xlinks ) );
		} else {
			return false;
		}
	}

	public function on_save_post_type( $post_id ) {
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
			_log( 'Forprocess: ' . $post->ID );
		}

		return $posts;
	}

	public function relink( $offset, $limit = Info::XLINKS_PER_RECORD, $one_id = 0 ) {
		global $wpdb;
		if ( $one_id > 0 ) {
			$posts = $this->get_posts_forprocess( false, $offset, $limit, $one_id );
		} else {
			$posts = $this->get_posts_forprocess( true, $offset, $limit );
		}
		#echo "<pre>";
		shuffle( $posts );
		#print_r($posts);
		foreach ( $posts as $post ) {
			#echo $post->ID."<br>";
			$anchors = $wpdb->get_results( "
            SELECT
                a.id,
                a.link
            FROM
                {$wpdb->prefix}xanchors a
                JOIN (SELECT
                        a.id,
                        IFNULL(COUNT(*),0) count
                    FROM
                        {$wpdb->prefix}xanchors a
                        LEFT JOIN {$wpdb->prefix}xlinks l ON l.anchor_id = a.id
                    GROUP BY
                        a.id) t ON t.id = a.id
            WHERE
                a.req > t.count
                AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}xlinks l WHERE l.anchor_id=a.id AND l.post_id = {$post->ID})
                AND NOT EXISTS (SELECT
                                    1
                                FROM
                                    {$wpdb->prefix}xanchors a1
                                    JOIN {$wpdb->prefix}xanchors a2 on a2.link = a1.link
                                    JOIN {$wpdb->prefix}xlinks l on l.anchor_id = a2.id
                                WHERE
                                    a1.id = a.id
                                    AND l.post_id = {$post->ID})
            GROUP BY
                a.link
            " );
//            ORDER BY
//                RAND()
			#echo "<pre>";
			shuffle( $anchors );
			#echo count($anchors);
			#print_r($anchors);
			#exit;
			foreach ( $anchors as $anchor ) {
				if ( substr( strtoupper( $anchor->link ), 0, strlen( $this->settings['local_domain'] ) ) == strtoupper( $this->settings['local_domain'] ) ) {
#echo strtoupper($anchor->link)."<br>";
#echo strtoupper($options['local_domain'])."<br>";
#echo $post->l_count."<br>";
#echo $options['local_req']."<br>";
#echo $options['global_req']."<br>";
					#error_log('============ '.$post->ID.' =============');
					#error_log($post->l_count);
					#error_log($options['local_req']);
					if ( $post->l_count < $this->settings['local_req'] ) {
						$post->l_count ++;
						$wpdb->query( "INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') " );
						#echo "Add local<br>";
					}
				} else {
					if ( $post->g_count < ( $this->settings['global_req'] - $this->settings['local_req'] ) ) {
						$post->g_count ++;
						$wpdb->query( "INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') " );
						#echo "Add global<br>";
					}
				}
				if ( $post->g_count >= ( $this->settings['global_req'] - $this->settings['local_req'] ) && $post->l_count >= $this->settings['local_req'] ) {
					break;
				}
			}
		}
		//wp_redirect(admin_url('/admin.php?page=xlinks'));
		//exit;
	}
}