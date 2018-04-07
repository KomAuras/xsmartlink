<script type="text/javascript">
var xsl_per_page=<?=$per_page?>
</script>

<div class="wrap">
    <h1><?= $heading ?></h1>

    <? if ( $all_items || ( isset( $xlinks_search ) && $xlinks_search != '' ) ) { ?>
        <p>
            <a href="#" id="xl_relink_button" class="button button-primary"
               onclick="xsml_process_relinks_js1();"><?= _e( 'Relink all posts', $this->plugin_slug ) ?></a>
            <a href="#" id="xl_delete404_button" class="button button-default"
               onclick="xsml_process_relinks_js2();"><?= _e( 'Find links with error 404', $this->plugin_slug ) ?></a>
        </p>
        <div id="progressbar" style="display: none;">
            <div class="value"></div>
        </div>
        <div id="xlinks_progress"></div>
        <form method="post">
            <?= wp_nonce_field( 'delete_all' ) ?>
            <input name="action" value="delete_all" type="hidden">
            <div class="tablenav top">
                <?php if ( isset( $p ) ) {
                    $p->show();
                } ?>
                <div class="tablenav-pages one-page">
                    <input type="text" name="xlinks_search" id="xlinks_search"
                           value="<?php isset( $xlinks_search ) ? $xlinks_search : ""; ?>">
                    <button class="button"><?= _e( 'Search' ) ?></button>
                    <span class="displaying-num"><?= sprintf( _n( "%s item", "%s items", $all_items, $this->plugin_slug ), number_format_i18n( $all_items ) ); ?></span>
                </div>
            </div>
            <table class="widefat fixed page" cellspacing="0">
                <thead>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label
                                class="screen-reader-text" for="cb-select-all-1">Select all</label><input
                                id="cb-select-all-1" type="checkbox"></th>
                    <?= $this->build_header('word',__( 'Word', $this->plugin_slug ), $orderby, $order) ?>
                    <?= $this->build_header('link',__( 'Acceptor', $this->plugin_slug ), $orderby, $order) ?>
                    <th scope="col"><?= _e( 'Image', $this->plugin_slug ) ?></th>
                    <th scope="col"><?= _e( 'Donor', $this->plugin_slug ) ?></th>
                    <?= $this->build_header('req',__( 'Qty', $this->plugin_slug ), $orderby, $order) ?>
                    <th scope="col"><?= _e( 'Count', $this->plugin_slug ) ?></th>
                    <?= $this->build_header('error',__( 'Error', $this->plugin_slug ), $orderby, $order) ?>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label
                                class="screen-reader-text" for="cb-select-all-1">Select all</label><input
                                id="cb-select-all-1" type="checkbox"></th>
                    <?= $this->build_header('word',__( 'Word', $this->plugin_slug ), $orderby, $order) ?>
                    <?= $this->build_header('link',__( 'Acceptor', $this->plugin_slug ), $orderby, $order) ?>
                    <th scope="col"><?= _e( 'Image', $this->plugin_slug ) ?></th>
                    <th scope="col"><?= _e( 'Donor', $this->plugin_slug ) ?></th>
                    <?= $this->build_header('req',__( 'Qty', $this->plugin_slug ), $orderby, $order) ?>
                    <th scope="col"><?= _e( 'Count', $this->plugin_slug ) ?></th>
                    <?= $this->build_header('error',__( 'Error', $this->plugin_slug ), $orderby, $order) ?>
                </tr>
                </tfoot>
                <tbody id="the-comment-list" data-wp-lists="list:comment">
                <?php foreach ( $items as $item ) { ?>
                    <tr id="comment-1"
                        class="comment even thread-even depth-1 approved<?= $item['error404'] != "0" && $item['error404'] != "" ? " error_line" : ""; ?>">

                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-1">Select</label>
                            <input id="cb-select-<?= $item['id'] ?>" type="checkbox" name="delete_xlink[]"
                                   value="<?= $item['id'] ?>">
                        </th>

                        <td><strong><?= $item['anchor'] ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a
                                            href="?page=xsmartlink_list&amp;id=<?= $item['id'] ?>&amp;edit=true"><?= _e( 'Edit' ) ?></a> | </span>
                                <span class="delete"><a
                                            href="?page=xsmartlink_list&amp;_wpnonce=<?= wp_create_nonce( 'delete' ); ?>&amp;delete=<?= $item['id'] ?>"
                                            onclick="return confirm('<?= _e( 'Are you sure you want to delete this anchor?', $this->plugin_slug ) ?>');"><?= _e( 'Delete' ) ?></a></span>
                            </div>
                        </td>

                        <td><?php
                            $pos = mb_strpos($item['link'],$this->settings['local_domain']);
                            if ($pos!==false && $pos==0){
                                echo "&#x2605;";
                            } ?>
                            <a href="<?= $item['link'] ?>" target="_blank"><?= $item['link']; ?>
                                <?php if ($item['link_id'] != 0){ ?>
                                <div class="row-actions">
                                <span class="edit"><a
                                            href="/wp-admin/post.php?post=<?= $item['link_id'] ?>&amp;action=edit"><?= _e( 'Edit' ) ?></a></span>
                                </div>
                                <?php } ?>
                        </td>

                        <td>
                            <form action="" method="POST" class="ibenic_upload_form" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'anchor_image','anchor_image_once' ); ?>
                                <div id="ibenic_file_upload<?= $item['id'] ?>" data-link-id="<?= $item['id'] ?>" class="ibenic_file_upload"
                                    <?php if ($item['image'] !== ""){ ?>
                                        style="display:none;"
                                    <?php } ?>>
                                    <input type="file" id="ibenic_file_input<?= $item['id'] ?>" class="ibenic_file_input" style="display:none;"/>
                                    <label class="ibenic_file_label" for="ibenic_file_input<?= $item['id'] ?>"><div class="dashicons-before dashicons-format-image"></div></label>
                                </div>
                                <div id="ibenic_file_upload<?= $item['id'] ?>_preview" class="file-upload file-preview"
                                    <?php if ($item['image'] == ""){ ?>
                                     style="display:none;"
                                    <?php } ?>>
                                    <div class="ibenic_file_preview<?= $item['id'] ?>">
                                    <?php if ($item['image'] !== ""){ ?>
                                        <img src="<?= $item['image'] ?>" height="100">
                                    <?php } ?>
                                    </div>
                                    <?php if ($item['link_id'] == 0){ ?>
                                    <div class="row-actions">
                                        <span class="delete"><a href="#" id="ibenic_file_delete<?= $item['id'] ?>" data-link-id="<?= $item['id'] ?>" data-attachment-id="<?= $item['attachment_id'] ?>" class="ibenic_file_delete"><?= _e( 'Delete' ) ?></a></span>
                                    </div>
                                    <?php } ?>
                                </div>
                            </form>
                        </td>

                        <td class="more_posts"><?php
                            if (count($item['donors'])){
                                $count = 0;
                                $block = false;
                                foreach($item['donors'] as $donor ){
                                    if ($block == false && $count > 2){
                                        echo '<span class="more_posts_s"> ...</span><span class="more_posts_h" style="display:none">';
                                        $block = true;
                                    }
                                    echo "<a href='" . $donor['link'] . "' target='_blank'>" . $donor['ID'] . "</a> ";
                                    $count++;
                                }
                                if ($block == true){
                                    echo "</span>";
                                }
                            }else{
                                _e( "Not donors yet.", $this->plugin_slug );
                            }
                        ?></td>

                        <td><?= $item['req'] ?></td>

                        <td><?= $item['count'] ?></td>

                        <?php if ( $item['error404'] == 0 ) { ?>
                            <td></td>
                        <?php } else { ?>
                            <td>
                                <a href="https://ru.wikipedia.org/wiki/Список_кодов_состояния_HTTP#<?= $item['error404'] ?>"><?= $item['error404'] ?></a>
                            </td>
                        <?php } ?>

                    </tr>
                <?php } ?>
                </tbody>

                <tbody id="the-extra-comment-list" data-wp-lists="list:comment" style="display: none;">
                </tbody>
            </table>
            <p><input type="submit" name="delete_all" class="button action" value="<?= _e( 'Delete' ) ?>"/></p>
        </form>
    <? } else { ?>
        <p><?= _e( 'Not connections yet. You can add new.', $this->plugin_slug ) ?></p>
    <? } ?>

</div>
