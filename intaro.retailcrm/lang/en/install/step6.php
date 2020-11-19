<?php

$MESS ['INTAROCRM_INFO'] = '
<h2>Further actions</h2>
<p>
If you uploaded orders in step 3, these orders are already available in your CRM and
after a while analytical reports will be calculated for these orders and ready in KPI Panel.
</p>
<p>
New orders will be send by agent <span style="font-family: Courier New;">RCrmActions::uploadOrdersAgent();</span>
to retailCRM every 10 minutes (interval can be changed in section <a href="/bitrix/admin/agent_list.php">Agents</a>).
</p>
<p>
If you selected "Export catalog now" option in step 4, your catalog will be generated.
If you did not select this option, you can generate catalog file by «retailCRM» export function
in section Store > Settings > <a href="/bitrix/admin/cat_export_setup.php">Export data</a>.
retailCRM checks and downloads this catalog file every 3 hours.
</p>
';
