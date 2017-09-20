<div class="wrap">
    <h1><?= $heading ?></h1>

    <form name="xlinks_add" method="post" enctype="multipart/form-data">
        <input name="action" value="xlinks_add" type="hidden">
        <table class="form-table">
            <tr>
                <th><label for="import_file"><?= _e('Select CSV file in Windows-1251 codepage',$this->plugin_slug)?></label></th>
                <td>
					<?=wp_nonce_field( 'xlinks_add' )?>
                    <input type="file" name="import_file" id="import_file" />
                </td>
            </tr>
            <tr>
                <th><label FOR="import_area"><?= _e('Or insert here the data block',$this->plugin_slug) ?></label></th>
                <td>
                    <textarea placeholder="<?= _e('anchor;link;count',$this->plugin_slug) ?>" name="import_area" id="import_area" rows="10" cols="70" style="padding:9px;"></textarea>
                </td>
            </tr>
            <tr>
                <th><label FOR="delimiter"><?= _e('Field delimiter',$this->plugin_slug) ?></label></th>
                <td>
                    <input type="text" class="regular-text" value="<?=$delimiter?>" name="delimiter" MAXLENGTH="1" SIZE="1"/>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="submit" id="action_import" class="button button-primary" value="<?= _e('Add new connections',$this->plugin_slug) ?>">
            <input action="action" onclick="window.history.go(-1); return false;" class="button button-cancel"
                   type="button" value="<?= _e( 'Back', $this->plugin_slug ) ?>"/>
        </p>
    </form>

</div>