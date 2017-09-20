<div class="wrap">
    <h1><?= $heading ?></h1>

    <table class="form-table">
        <TR>
            <TD COLSPAN="2"><B><?= _e( 'Posts', $this->plugin_slug ) ?></B></TD>
        </TR>
        <TR>
            <TD width=20%><?= _e( 'Donors', $this->plugin_slug ) ?></TD>
            <TD><?= $donors ?></TD>
        </TR>
        <TR>
            <TD><?= _e( 'Acceptors', $this->plugin_slug ) ?></TD>
            <TD><?= $acceptors ?></TD>
        </TR>
        <TR>
            <TD COLSPAN="2"><B><?= _e( 'Request', $this->plugin_slug ) ?></B></TD>
        </TR>
        <TR <?php if ( $need_g_links ) { ?>STYLE="color:red;"<?php } ?>>
            <TD><?= _e( 'Need outer links', $this->plugin_slug ) ?></TD>
            <TD><?= $need_g_links ?></TD>
        </TR>
        <TR <?php if ( $need_l_links ) { ?>STYLE="color:red;"<?php } ?>>
            <TD><?= _e( 'Need local links', $this->plugin_slug ) ?></TD>
            <TD><?= $need_l_links ?></TD>
        </TR>
    </TABLE>

</div>
