<div class="wrap">
    <h1><?= $heading ?></h1>

	<table class="form-table">
     <TR>
      <TD COLSPAN="2"><B><?=__('Posts','xlinks')?></B></TD>
     </TR>
     <TR>
      <TD width=20%><?=__('Donors','xlinks')?></TD>
      <TD><?=$donors?></TD>
     </TR>
     <TR>
      <TD><?=__('Acceptors','xlinks')?></TD>
      <TD><?=$acceptors?></TD>
     </TR>
     <TR>
      <TD COLSPAN="2"><B><?=__('Request','xlinks')?></B></TD>
     </TR>
     <TR <?php if ($need_g_links) {?>STYLE="color:red;"<?php } ?>>
      <TD><?=__('Need outer links','xlinks')?></TD>
      <TD><?=$need_g_links?></TD>
     </TR>
     <TR <?php if ($need_l_links) {?>STYLE="color:red;"<?php } ?>>
      <TD><?=__('Need local links','xlinks')?></TD>
      <TD><?=$need_l_links?></TD>
     </TR>
    </TABLE>

</div>
