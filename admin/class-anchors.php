<?php

namespace SmartLink;

class Anchors
{
    private $plugin_slug;
    private $version;
    private $option_name;
    private $settings;
    private $import;
    private $hueman;

    public function __construct($plugin_slug, $version, $option_name)
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'classes/metaboxes.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-import.php';

        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->option_name = $option_name;
        $this->settings = get_option($this->option_name);
        $this->import = new Import($plugin_slug, $version, $option_name);
        $data = wp_get_theme();
        $this->hueman = $data->name == 'hueman'	|| $data->template == 'hueman';
    }

    // Add posts column
    function xsl_columns_head($posts_columns, $post_type)
    {
        if ($post_type == 'post')
            $posts_columns['anchors'] = __('Number of links', $this->plugin_slug);
        return $posts_columns;
    }

    // Show column data
    function xsl_columns_content($column_name, $post_ID)
    {
        global $wpdb;
        $result = "";
        if ($column_name == 'anchors') {
            $post = get_post($post_ID);
            $type = get_post_meta($post_ID, '_xsmartlink_type', true);
            $result .= $type == "a" ? __('Acceptor', $this->plugin_slug) : "";
            $all = 0;
            $local = 0;
            if ($type != "a") {
                $data = $wpdb->get_results("
                SELECT
                    a.link
                FROM
                    {$wpdb->prefix}xlinks t
                    JOIN {$wpdb->prefix}xanchors a ON a.id = t.anchor_id
                WHERE
                    t.post_id = {$post->ID}
                ");
                foreach ($data as $row) {
                    $pos = mb_strpos($row->link, $this->settings['local_domain']);
                    if ($pos !== false && $pos == 0) {
                        $local++;
                    }
                    $all++;
                }
                $result .= '<kbd>' . $all . ($local > 0 ? ' (&#x2605;' . $local . ')' : '') . '</kbd>';
            }
            echo $result;
        }
    }

    public function render()
    {
        global $wpdb;
        $donors = $wpdb->get_var("
          SELECT
            count(*) count
          FROM
            {$wpdb->prefix}posts p
            left join {$wpdb->prefix}postmeta m on m.post_id = p.ID AND m.meta_key = '_xsmartlink_type'
          WHERE
            p.post_type = 'post'
            AND (p.post_status = 'publish' OR p.post_status = 'future')
            AND (m.meta_id IS NULL OR m.meta_value = 'd')
        ");
        /** @noinspection PhpUnusedLocalVariableInspection */
        $acceptors = $wpdb->get_var("
          SELECT
            count(*) count
          FROM
            {$wpdb->prefix}posts p
            join {$wpdb->prefix}postmeta m on m.post_id = p.ID AND m.meta_key = '_xsmartlink_type' AND m.meta_value = 'a'
          WHERE
            p.post_type = 'post'
            AND (p.post_status = 'publish' OR p.post_status = 'future')
        ");
        $g_links = $wpdb->get_var("SELECT sum(gl.count) FROM {$wpdb->prefix}posts p JOIN (SELECT l.post_id, count(*) count FROM {$wpdb->prefix}xlinks l JOIN {$wpdb->prefix}xanchors a ON a.id = l.anchor_id WHERE a.link NOT LIKE '{$this->settings['local_domain']}%' GROUP BY l.post_id) gl ON gl.post_id = p.id");
        $l_links = $wpdb->get_var("SELECT ifnull(sum(gl.count),0) FROM {$wpdb->prefix}posts p JOIN (SELECT l.post_id, count(*) count FROM {$wpdb->prefix}xlinks l JOIN {$wpdb->prefix}xanchors a ON a.id = l.anchor_id WHERE a.link LIKE '{$this->settings['local_domain']}%' GROUP BY l.post_id) gl ON gl.post_id = p.id");
        $gl1 = $this->settings['global_req'] - $this->settings['local_req'];
        if ($gl1 > 0) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $need_g_links = ($donors - ($g_links / $gl1)) * ($this->settings['global_req'] - $this->settings['local_req']);
        } else {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $need_g_links = 0;
        }
        if ($this->settings['local_req'] > 0) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $need_l_links = ($donors - ($l_links / $this->settings['local_req'])) * $this->settings['local_req'];
        } else {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $need_l_links = 0;
        }

        // View
        /** @noinspection PhpUnusedLocalVariableInspection */
        $heading = __('Stat', $this->plugin_slug);
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/view_stat.php';
    }

    public function add_metaboxes()
    {
        return new MetaBoxes('xsm_links_meta_box', __('Anchor list', $this->plugin_slug), $this);
    }

    public function setup_post_type()
    {
        global $post;
        if ($post->post_type === 'post') {
            $type = get_post_meta($post->ID, '_xsmartlink_type', true);
            if ($type == '') {
                $type = 'd';
            }
            echo '<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">' . __('Post type:', $this->plugin_slug) . ' ';
            echo '<input type="radio" name="post_link_type" id="post_link_type_d" value="donor" ' . checked($type, 'd', false) . '/>';
            echo '<label for="for="post_link_type_d" class="select-it">' . __('Donor', $this->plugin_slug) . '</label> ';
            echo '<input type="radio" name="post_link_type" id="post_link_type_a" value="acceptor" ' . checked($type, 'a', false) . '/>';
            echo '<label for="post_link_type_a" class="select-it">' . __('Acceptor', $this->plugin_slug) . '</label>';
            echo '</div>';
        }
    }

    // возвращает список ссылок массивом
    private function get_post_anchors($post)
    {
        global $wpdb;
        $type = get_post_meta($post->ID, '_xsmartlink_type', true);
        if ($type != "a") {
            $data = $wpdb->get_results("
            SELECT
                a.link,
                a.value as text,
                a.attachment_id
            FROM
                {$wpdb->prefix}xlinks t
                JOIN {$wpdb->prefix}xanchors a ON a.id = t.anchor_id
            WHERE
                t.post_id = {$post->ID}
            ");
            $result = array();
            foreach ($data as $row) {
                $one['link'] = $row->link;
                $one['text'] = $row->text;
                // get images from link if exists
                $one['image'] = "";
                $link_id = url_to_postid($row->link);
                if ($link_id != 0) {
                    $thumbnail_id = get_post_thumbnail_id($link_id);
                    if ($this->hueman) {
                        $one['thumbnail'] = get_the_post_thumbnail( $link_id, 'thumbnail' );
                    }
                    if ($thumbnail_id != "")
                        $one['image'] = wp_get_attachment_thumb_url(get_post_thumbnail_id($link_id));
                    else
                        $one['image'] = plugin_dir_url(__FILE__) . 'img/noimage.png';

                } elseif ($row->attachment_id != 0) {
                    $one['image'] = wp_get_attachment_thumb_url($row->attachment_id);
                }
                $result[] = $one;
            }
            return $result;
        }
        return false;
    }

    public function get_post_anchor_list($post, $mark_local = true)
    {
        $result = "";
        $data = $this->get_post_anchors($post);
        if (is_array($data)) {
            $result .= "<ul>";
            foreach ($data as $row) {
                $local = "";
                if ($mark_local) {
                    $pos = mb_strpos($row['link'], $this->settings['local_domain']);
                    if ($pos !== false && $pos == 0) {
                        $local .= "&#x2605; ";
                    }
                }
                $result .= '<li>' . $local . '<a href="' . $row['link'] . '">' . $row['text'] . '</a></li>';
            }
            $result .= "</ul>";
        }
        return $result;
    }

    public function add_links_to_content($content)
    {
        if (is_single() && isset($this->settings['insert_in_pages']) && $this->settings['insert_in_pages'] == 1) {
            $img = isset($this->settings['image_enabled']) && $this->settings['image_enabled'] == 1;
            $data = $this->get_post_anchors(get_post());
            if (count($data)) {

                $template = "
                <hr style=\"clear:both\">
                    <p style=\"text-align: justify;\">" . __('See also:', $this->plugin_slug) . "</p>
                    <ul style='" . ($img ? "margin:0;padding:0" : "") . "'>
                        {interests_list}
                    </ul>
                <hr>";
                $result = '';

                foreach ($data as $row) {
                    if (Info::XLINKS_WITHOUT_LINK == true) {
                        $result .= '<li><b>' . $row['link'] . '</b>' . $row['text'] . '</li>';
                    } else {
                        if (!$img)
                            $result .= '<li><a href="' . $row['link'] . '">' . $row['text'] . '</a></li>';
                        else {
                            $im = '<img src="' . ($row['image'] == '' ? plugin_dir_url(__FILE__) . 'img/noimage.png' : $row['image']) . '" style="height:' . $this->settings['image_height'] . 'px">';
                            if (isset($row['thumbnail']))
                            	$im = $row['thumbnail'];
                            $result .= '
                            <li style="list-style-type:none">
                                <a href="' . $row['link'] . '">
                                <div style="margin-left:1px; float:left">
                                    <div>' . $im . '</div>
                                    <div style="width:' . $this->settings['image_height'] . 'px;">' . $row['text'] . '</div>
                                </div>
                                </a>
                            </li>';
                        }
                    }
                }
                if ($img)
                    $result .= "<div style='clear: both'></div>";

                $template = str_replace("{interests_list}", $result, $template);
                $content = $content . $template;
            }
        }

        return $content;
    }

    public function on_save_post_type($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (isset($_POST['post_link_type'])) {
            $type = $_POST['post_link_type'] == 'donor' ? 'd' : 'a';
            update_post_meta($post_id, '_xsmartlink_type', $type);
        } else {
            $type = get_post_meta($post_id, '_xsmartlink_type', true);
        }

        if ($type == '') {
            return false;
        } else {
            if ($post->post_status == 'trash' || $type == "a") {
                // удаляем связи у постов.
                // TODO: Нужно удалять ссылки в таблице при удалении постов.
                $this->on_delete_post($post_id);
                return;
            }
            // вызывать нужно при update == true. в ином случае пермалинк будет как для ревизии
            if ($update == true && isset($this->settings['new_post_to_anchors']) && $this->settings['new_post_to_anchors'] == 1) {
                // todo: подумать в каком случае нужно добавлять себя в anchors
                $this->import->post_to_anchor($post_id);
            }
            if ($post->post_type = 'post') {
                $this->relink(0, 0, $post_id);
            }
        }
    }

    public function on_delete_post($post_id)
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT anchor_id FROM {$wpdb->prefix}xlinks WHERE post_id = {$post_id}");
        foreach ($rows as $row) {
            $ids[] = $row->anchor_id;
        }
        if (isset($ids) && count($ids)) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}xlinks WHERE anchor_id IN (" . implode(", ", $ids) . ")");
        }

        return true;
    }

    public function on_resore_post($post_id)
    {
        $post = get_post($post_id);
        $type = get_post_meta($post_id, '_xsmartlink_type', true);
        if ($post->post_type = 'post' && $type != 'a') {
            $this->relink(0, 0, $post_id);
        }
    }

    public function get_anchors_forprocess()
    {
        global $wpdb;
        $anchors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}xanchors a");

        return $anchors;
    }

    public function process_anchor($offset, $limit = Info::XLINKS_PER_RECORD)
    {
        global $wpdb;
        $anchors = $wpdb->get_results("
        SELECT
            a.id,
            a.link
        FROM
            {$wpdb->prefix}xanchors a
        LIMIT {$offset},{$limit}
        ");
        foreach ($anchors as $anchor) {
            $error = (int)$this->check_anchor($anchor->link);
            $wpdb->query("
            UPDATE
                {$wpdb->prefix}xanchors
            SET
                error404={$error}
            WHERE
                id={$anchor->id}");
        }
    }

    private function check_anchor($link)
    {
        $handle = curl_init($link);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 3000);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode == 0) {
            $httpCode = 404;
        }
        if ($httpCode == 200) {
            $httpCode = 0;
        }
        curl_close($handle);

        return $httpCode;
    }

    /**
     * Получить список постов для перелинковки
     *
     * @param $use_limits bool учитывать лимиты
     * @param int $offset int
     * @param int $limit int
     * @param int $post_id int
     *
     * @return array|null|object
     */
    public function get_posts_forprocess($use_limits, $offset = 0, $limit = Info::XLINKS_PER_RECORD, $post_id = 0)
    {
        global $wpdb;

        // если грузим все (выполняется при полной перелинковке)
        if ($use_limits == false && $post_id == 0) {
            // удаляем оторванные ссылки
            $wpdb->query("DELETE l FROM {$wpdb->prefix}xlinks l LEFT JOIN {$wpdb->prefix}posts p ON p.ID = l.post_id WHERE p.ID IS NULL");
            // очищаем талицу сортировки
            $wpdb->query("TRUNCATE {$wpdb->prefix}xtempsort");
            // заполняем таблицу сортировки
            $wpdb->query("INSERT INTO {$wpdb->prefix}xtempsort (post_id, sort_num) SELECT ID, FLOOR(RAND() * 1000) from wp_posts p WHERE p.post_type = 'post'");
        }

        // запрос возвращает номер поста и количество привязанных к нему
        // ссылок, внешних и локальных. тип ссылок определяется по LIKE
        // WHERE
        // для опубликованнх или запланированнх постов
        // и если общее количество ссылок меньше максимального количества из настроек
        // TODO: На будушее нужно сделать возможность пересчитать количество привязанных ссылок в соответствии с настройками
        $q = "
        SELECT
            t.ID,
            t.g_count,
            t.l_count
        FROM
            {$wpdb->prefix}posts p";
        if ($use_limits == true) {
            // с лимитами линкуем таблицу сортировки
            $q .= " LEFT JOIN {$wpdb->prefix}xtempsort tl ON tl.post_id = p.ID ";
        }
        $q .= "
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
            AND (p.post_status = 'publish' OR p.post_status = 'future')
            AND (t.g_count + t.l_count) < {$this->settings['global_req']}
        ";
        if ($post_id > 0) {
            $q .= " AND p.ID = {$post_id}";
        }
        if ($use_limits == true) {
            $q .= " ORDER BY tl.sort_num LIMIT {$offset},{$limit}";
        }
        $posts = $wpdb->get_results($q);
        return $posts;
    }

    public function relink($offset, $limit = Info::XLINKS_PER_RECORD, $one_id = 0)
    {
        global $wpdb;
        if ($one_id > 0) {
            $posts = $this->get_posts_forprocess(false, $offset, $limit, $one_id);
        } else {
            $posts = $this->get_posts_forprocess(true, $offset, $limit);
            shuffle($posts);
        }
        foreach ($posts as $post) {
            $permalink = get_permalink($post->ID);
            $anchors = $wpdb->get_results("
            SELECT
                a.id,
                a.link,
                a.req,
                t.count
            FROM
                {$wpdb->prefix}xanchors a
                /* количество уже привязанных постов к ссылке */
                LEFT JOIN (SELECT
                        anchor_id as id,
                        COUNT(*) count
                    FROM
                        {$wpdb->prefix}xlinks
                    GROUP BY
                        anchor_id) t ON t.id = a.id
            WHERE
                /* если к ссылке привязано постов меньше чем нужно */
                a.req > IFNULL(t.count,0)
                /* если пост еще не привязан к ссылке */
                AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}xlinks l WHERE l.anchor_id=a.id AND l.post_id = {$post->ID})
                /* если пост еще не привязан к сcылке с таким же урлом (к примеру с другим словом) */
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
                AND a.link <> '" . esc_sql($permalink) . "'
            GROUP BY
                a.link
            ");
            shuffle($anchors);
            foreach ($anchors as $anchor) {
                if (substr(strtoupper($anchor->link), 0, strlen($this->settings['local_domain'])) == strtoupper($this->settings['local_domain'])) {
                    if ($post->l_count < $this->settings['local_req']) {
                        $post->l_count++;
                        $wpdb->query("INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') ");
                    }
                } else {
                    if ($post->g_count < ($this->settings['global_req'] - $this->settings['local_req'])) {
                        $post->g_count++;
                        $wpdb->query("INSERT INTO {$wpdb->prefix}xlinks (`post_id`, `anchor_id`) VALUES ('{$post->ID}','{$anchor->id}') ");
                    }
                }
                if ($post->g_count >= ($this->settings['global_req'] - $this->settings['local_req']) && $post->l_count >= $this->settings['local_req']) {
                    break;
                }
            }
        }
    }
}