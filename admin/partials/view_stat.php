<div class="wrap">
    <h1><?= $heading ?></h1>

    <table class="form-table">
        <TR>
            <Th COLSPAN="2"><h2><?= _e( 'Posts', $this->plugin_slug ) ?></h2></Th>
        </TR>
        <TR>
            <Th><?= _e( 'Donors', $this->plugin_slug ) ?></Th>
            <TD><?= $donors ?></TD>
        </TR>
        <TR>
            <Th><?= _e( 'Acceptors', $this->plugin_slug ) ?></Th>
            <TD><?= $acceptors ?></TD>
        </TR>
        <TR>
            <Th COLSPAN="2"><h2><?= _e( 'Request', $this->plugin_slug ) ?></h2></Th>
        </TR>
        <TR <?php if ( $need_g_links ) { ?>STYLE="color:red;"<?php } ?>>
            <Th><?= _e( 'Need outer links', $this->plugin_slug ) ?></Th>
            <TD><?= $need_g_links ?></TD>
        </TR>
        <TR <?php if ( $need_l_links ) { ?>STYLE="color:red;"<?php } ?>>
            <Th><?= _e( 'Need local links', $this->plugin_slug ) ?></Th>
            <TD><?= $need_l_links ?></TD>
        </TR>
    </TABLE>

</div>
