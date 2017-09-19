<div class="wrap">
    <h1><?= $heading ?></h1>

	<? if ( $all_items || ( isset( $xlinks_search ) && $xlinks_search != '' ) ) { ?>
        <p>
            <a href="#" id="xl_relink_button" class="button button-primary"
               onclick="xsml_process_relinks_js1();"><?= _e( 'Relink all posts', 'xlinks' ) ?></a>
            <a href="#" id="xl_delete404_button" class="button button-primary"
               onclick="xsml_process_relinks_js2();"><?= _e( 'Find links with error 404', 'xlinks' ) ?></a>
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
                    <span class="displaying-num"><?= sprintf( _n( '1 item', '%s items', $all_items, 'xlinks' ), number_format_i18n( $all_items ) ); ?></span>
                </div>
            </div>
            <table class="widefat fixed page" cellspacing="0">
                <thead>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label
                                class="screen-reader-text" for="cb-select-all-1">Select all</label><input
                                id="cb-select-all-1" type="checkbox"></th>
                    <th scope="col"><span><?= _e( 'Word', 'xlinks' ) ?></span></a></th>
                    <th scope="col" width="30%"><?= _e( 'Acceptor', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Donor', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Qty', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Count', 'xlinks' ) ?></th>
                    <th scope="col" WIDTH="5%" NOWRAP><?= _e( 'Error', 'xlinks' ) ?></th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label
                                class="screen-reader-text" for="cb-select-all-1">Select all</label><input
                                id="cb-select-all-1" type="checkbox"></th>
                    <th scope="col"><span><?= _e( 'Word', 'xlinks' ) ?></span></a></th>
                    <th scope="col" width="30%"><?= _e( 'Acceptor', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Donor', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Qty', 'xlinks' ) ?></th>
                    <th scope="col"><?= _e( 'Count', 'xlinks' ) ?></th>
                    <th scope="col" WIDTH="5%" NOWRAP><?= _e( 'Error', 'xlinks' ) ?></th>
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
                                            onclick="return confirm('<?= _e( 'Are you sure you want to delete this anchor?', 'xlinks' ) ?>');"><?= _e( 'Delete' ) ?></a></span>
                            </div>
                        </td>
                        <td><a href="<?= $item['link'] ?>" target="_blank"><?= $item['link'] ?></td>

                        <td><?= $item['donors'] ?></td>
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
        <p><?= _e( 'Not connections yet. You can add new.', 'xlinks' ) ?></p>
	<? } ?>

</div>
