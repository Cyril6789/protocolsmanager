<?php
include ('../../../inc/includes.php');
$PluginProtocolsmanagerGenerate = new PluginProtocolsmanagerGenerate();

if (isset($_REQUEST['generate'])) {
	$PluginProtocolsmanagerGenerate::makeProtocol();
	Html::back();
}

if (isset($_REQUEST['delete'])) {
	$PluginProtocolsmanagerGenerate::deleteDocs();
	Html::back();
}

if (isset($_REQUEST['send'])) {
	$PluginProtocolsmanagerGenerate::sendOneMail($id);
	Html::back();
}

// techniciens
// if (isset($_REQUEST['choiceuserfield'])) {
// 	$_SESSION['userfield'] = $_POST['userfield'];
// 	Html::back();
// }

?>