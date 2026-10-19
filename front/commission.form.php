<?php

if (isset($_POST['generate_commission'])) {
	PluginProtocolsmanagerCommission::generateFromRequest();
}

Html::back();
