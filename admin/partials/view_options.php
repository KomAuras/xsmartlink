<div class="wrap">
    <h2><?= $heading ?></h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <form action="options.php" method="post">
					<?php settings_fields( $settings_group ); ?>
					<?= $fields ?>
                    <div class="submit-wrap">
						<?php //submit_button( $submit_text ); ?>
                        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                                 value="<? echo $submit_text ?>"/>
                            <input action="action" onclick="window.history.go(-1); return false;"
                                   class="button button-cancel" type="button" value="<?= _e( 'Back' ) ?>"/>
                        <div class="spinner"></div>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        <br class="clear">
    </div>

</div>
