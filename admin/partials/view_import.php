<div class="wrap">
    <h2><?= $heading ?></h2>

    <form name="xlinks_add" method="post" enctype="multipart/form-data">
        <input name="action" value="xlinks_add" type="hidden">
        <table class="form-table">
            <tr class="top">
                <th><label for="import_file"><?= _e('Select CSV file in Windows-1251 codepage','xlinks')?></label></th>
                <td scope="row">
					<?=wp_nonce_field( 'xlinks_add' )?>
                    <input type="file" name="import_file" id="import_file" />
                </td>
            </tr>
            <tr valign="top">
                <th><label FOR="import_area"><?= _e('Or insert here the data block','xlinks') ?></label></th>
                <td>
                    <textarea placeholder="<?= _e('anchor;link;count','xlinks') ?>" name="import_area" id="import_area" rows="10" cols="70" style="padding:9px;"></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th><label FOR="delimiter"><?= _e('Field delimiter','xlinks') ?></label></th>
                <td>
                    <input type="text" class="regular-text" value="<?=$delimiter?>" name="delimiter" MAXLENGTH="1" SIZE="1"/>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="submit" id="action_import" class="button button-primary" value="<?= _e('Add new connections','xlinks') ?>">
            <input action="action" onclick="window.history.go(-1); return false;" class="button button-cancel" type="button" value="<?= _e('Back') ?>" />
        </p>
    </form>

</div>