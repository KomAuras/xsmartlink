<div class="wrap">
    <h1><?= $heading ?></h1>

    <form name="xlink_edit_form" method="post">
        <input type="hidden" id="anchor_id" name="anchor_id" value="<?= $edit_row->id; ?>"/>
		<?= wp_nonce_field( "xlink_edit_form" ); ?>
        <table class="form-table">
            <tr>
                <th><label for="anchor_value"><?= _e( 'Anchor word', $this->plugin_slug ) ?></label></th>
                <td><input style="width:50%" type="text" name="anchor_value" id="anchor_value"
                           value="<?= $edit_row->value; ?>" class="regular-text code"/>
                    <p class="description"><?= _e( '"Word" for link. Can be of several words.', $this->plugin_slug ) ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="anchor_link"><?= _e( 'Acceptor', $this->plugin_slug ) ?></label></th>
                <td><input style="width:50%" type="text" name="anchor_link" id="anchor_link"
                           value="<?= $edit_row->link; ?>" class="regular-text code" READONLY/></td>
            </tr>
            <tr>
                <th><label for="anchor_required"><?= _e( 'Required qty', $this->plugin_slug ) ?> <span
                                class="description"><?php _e( '(required)' ); ?></span></label></th>
                <td><input style="width:50px" type="text" name="anchor_required" id="anchor_required"
                           value="<?= $edit_row->req; ?>" class="regular-text code"/>
                    <p class="description"><?= _e( 'Many links will be connected with donors.', $this->plugin_slug ) ?></p>
                </td>
            </tr>
        </table>
        <p>
        	<input type="submit" name="submit" id="submit" class="button button-primary" value="<?= _e( 'Save' ) ?>">
            <input action="action" onclick="window.history.go(-1); return false;" class="button button-cancel" type="button" value="<?= _e('Back',$this->plugin_slug) ?>" />
        </p>
    </form>

</div>
