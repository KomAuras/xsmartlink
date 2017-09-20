<div class="wrap">
    <h1><?= $heading ?></h1>

    <form name="xlinks_export" method="post" enctype="multipart/form-data">
        <input name="action" value="export_process" type="hidden">
        <?=wp_nonce_field( 'xlinks_export' )?>
        <th><label for="export_file"><?= _e('Export all links to CSV file with Windows-1251 codepage','xlinks')?></label></th>
        <p><input type="submit" name="submit" id="action_export" class="button button-primary" value="<?= _e('Export','xlinks') ?>"></p>
    </form>

</div>
