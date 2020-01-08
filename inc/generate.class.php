<?php

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
require_once $autoload;


use Spipu\Html2Pdf\Html2Pdf;


class PluginProtocolsmanagerGenerate extends CommonDBTM
{
	
		function getTabNameForItem(CommonGLPI $item, $withtemplate=0)
		{
			return self::createTabEntry('Protocols manager');
		}
		
		
		static function getAllForUser($id) {
			global $DB, $CFG_GLPI;
			$exports = [];
			$doc_counter = 0;
			
			foreach ($DB->request(
				'glpi_plugin_protocolsmanager_protocols',
				['user_id' => $id ]) as $export_data => $exports) {
					
					
					echo "<tr class='tab_bg_1'>";
					
					echo "<td class='center'>";
					echo "<input type='checkbox' name='docnumber[]' value='".$exports['document_id']."' class='docchild'>";
					echo "</td>";
					
					echo "<td class='center'>";
					echo $exports['document_type'];
					echo "</td>";
					
					echo "<td class='center'>";
					echo $exports['gen_date'];
					echo "</td>";
					
					$Doc = new Document();
					$Doc->getFromDB($exports['document_id']);
					echo "<td class='center'>";
					echo $Doc->getDownloadLink();
					echo "</td>";
					
					echo "<td class='center'>";
					echo $exports['author'];
					echo "</td>";
					
					echo "</tr>";

					$doc_counter++;

				}
		}
		

		static function deleteDocs() {
			global $DB, $CFG_GLPI;
			
			$docnumber = $_POST['docnumber'];
			
			foreach ($docnumber as $del_key) {
				
				$DB->delete(
					'glpi_plugin_protocolsmanager_protocols', [
						'document_id' => $del_key
					]
				);
				
				$doc = new Document();
				$doc->getFromDB($del_key);
				$doc->delete(['id' => $del_key], true);
			}
		}
		
		
		static function createDoc($doc_name) {
			$input = [];
			$doc = new Document();
			$input["entities_id"] = $_SESSION['glpiactive_entity'];
			$input["name"] = date('mdY_Hi');
			$input["upload_file"] = $doc_name;
			$input["documentcategories_id"] = 0;
			$input["mime"] = "application/pdf";
			$input["date_mod"] = date("Y-m-d H:i:s");
			$input["users_id"] = Session::getLoginUserID();
			$doc->check(-1, CREATE, $input);
			$document_id = $doc->add($input);
			return $document_id;
		}
		
		static function getDocNumber() {
			global $DB;
			
			$req = $DB->request('SELECT MAX(id) as max FROM glpi_plugin_protocolsmanager_protocols');
			if ($row = $req->next()) {
				$nextnum = $row["max"];
				if (!$nextnum) {
					return 1;
				}
				else {
					$nextnum++;
					return $nextnum;
				}
			}
		}
			
		
		static function makeProtocol() {
			
			global $DB, $CFG_GLPI;
			
			$number = $_POST['number'];
			$type_name = $_POST['type_name'];
			$man_name = $_POST['man_name'];
			$mod_name = $_POST['mod_name'];
			$serial = $_POST['serial'];
			$item_name = $_POST['item_name'];
			$owner = $_POST['owner'];
			$author = $_POST['author'];
			$doc_no = $_POST['list'];
			$id = $_POST['user_id'];
			
			$prot_num = self::getDocNumber();
			
			$req = $DB->request(
				'glpi_plugin_protocolsmanager_config',
				['id' => $doc_no ]);
				
			if ($row = $req->next()) {
				$content = nl2br($row["content"]);
				$footer = nl2br($row["footer"]);
				$title = $row["name"];
				$full_img_name = $row["logo"];
				$font = $row["font"];
				$city = $row["city"];
			}
			

			$comments = $_POST['comments'];
		
			
			if (!isset($font) || empty($font)) {
				$font = 'dejavusans';
			}	
			
			if (!isset($city) || empty($city)) {
				$city = '';
			}
			
			if (!isset($full_img_name) || empty($full_img_name)) {
				$backtop = "20mm";
				$islogo = 0;
			} else {
				$logo = GLPI_ROOT.'/files/_pictures/'.$full_img_name;
				$backtop = "40mm";
				$islogo = 1;
			}
			
			ob_start();
			include dirname(__FILE__).'/template.php';
			$html = ob_get_clean();

			$html2pdf = new Html2Pdf('P', 'A4');
			$html2pdf->setDefaultFont($font);
			$html2pdf->writeHTML($html);
			$doc_name = $prot_num."-".date('mdY').'.pdf';			
			$html2pdf->Output(GLPI_UPLOAD_DIR .'/'.$doc_name, 'F');
			
			$doc_id = self::createDoc($doc_name);
			
			$gen_date = date('Y-m-d H:i:s');
			
			
			$DB->insert('glpi_plugin_protocolsmanager_protocols', [
				'name' => $doc_name,
				'gen_date' => $gen_date,
				'author' => $author,
				'user_id' => $id,
				'document_id' => $doc_id,
				'document_type' => $title
				]
			);
				
		}


		static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0)
		{
			global $DB, $CFG_GLPI;
			
		//function showing items from user.class.php
		
		$id = $item->getField('id');
		$type_user   = $CFG_GLPI['linkuser_types'];
        $field_user  = 'users_id';
		$rand = mt_rand();
		
		
		$counter=0;
		echo "<form method='post' name='protocolsmanager_form$rand' id='protocolsmanager_form$rand'	action=\"" . $CFG_GLPI["root_doc"] . "/plugins/protocolsmanager/front/generate.form.php\">";
		echo "<table class='tab_cadre_fixe'><td style ='width:30%'></td><td class='center' style ='width:20%'>";
		echo "<select name='list'>";
			foreach ($doc_types = $DB->request('glpi_plugin_protocolsmanager_config', 
			['FIELDS' => ['glpi_plugin_protocolsmanager_config' => ['id', 'name']]]) as $uid => $list) {
				echo '<option value="';
				echo $list["id"];
				echo '">';
				echo $list["name"];
				echo '</option>';
			}
		echo "</select></td><td style='width:10%'>";
		echo "<input type='submit' name='generate' class='submit' value='".__('Create')."'>";
		echo "</td><td style='width:30%'></td></table>";
		echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
		$header = "<th><input type='checkbox' class='checkall'></th>";
		$header .= "<th>".__('Type')."</th>";
		$header .= "<th>".__('Manufacturer');
		$header .= " ".__('Model')."</th>";
		$header .= "<th>".__('Name')."</th>";
		$header .= "<th>".__('Serial number')."</th>";
		$header .= "<th>".__('Comments')."</th></tr>";
		echo $header;
		foreach ($type_user as $itemtype) 
		{
			if (!($item = getItemForItemtype($itemtype))) 
			{
				continue;
			}
			if ($item->canView()) 
			{
				$itemtable = getTableForItemType($itemtype);
				$iterator_params = [
				   'FROM'   => $itemtable,
				   'WHERE'  => [$field_user => $id]
				];
				if ($item->maybeTemplate()) 
				{
				   $iterator_params['WHERE']['is_template'] = 0;
				}
				if ($item->maybeDeleted()) 
				{
				   $iterator_params['WHERE']['is_deleted'] = 0;
				}
				$item_iterator = $DB->request($iterator_params);
				$type_name = $item->getTypeName();
				while ($data = $item_iterator->next()) 
				{
					$cansee = $item->can($data["id"], READ);
					   $link   = $data["name"];
						if ($cansee) 
						{
							$link_item = $item::getFormURLWithID($data['id']);
							if ($_SESSION["glpiis_ids_visible"] || empty($link)) 
							{
							 $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
							}
							$link = "<a href='".$link_item."'>".$link."</a>";
						}
						$linktype = "";
						if ($data[$field_user] == $id) 
						{
							$linktype = self::getTypeName(1);
						}
						
						echo "<tr class='tab_bg_1'>";
						echo "<td width='10'>";
						echo "<input type='checkbox' name='number[]' value='$counter' class='child'>";
						echo "</td>";	

					   
						echo "<td class='center'>$type_name</td>";
						echo "<td class='center'>";
						
						if (isset($data["manufacturers_id"]) && !empty($data["manufacturers_id"])) 
						{
							$man_id = $data["manufacturers_id"];
														
							$req = $DB->request(
								'glpi_manufacturers',
								['id' => $man_id ]);
							
							if ($row = $req->next()) {
								$man_name = $row["name"];
							}
							$modeltypes = array("computer", "phone", "monitor", "networkequipment", "printer", "peripheral");
							$mod_name = '';
							foreach($modeltypes as $prefix) {
								if(isset($data[$prefix.'models_id']) && !empty($data[$prefix.'models_id'])) {
									$mod_id = $data[$prefix.'models_id'];
									
									$req2 = $DB->request(
										'glpi_'.$prefix.'models',
										['id' => $mod_id ]);
										
									if ($row2 = $req2->next()) {
										$mod_name = $row2["name"];
									}
								}
							}
							
							$man_name = explode(' ',trim($man_name))[0];
							echo $man_name.' '.$mod_name;
							
						} 
						else 
						{
							echo '&nbsp;';
							$man_name = '';
							$mod_name = '';
						}
						echo "</td>";
						echo "<td class='center'>$link</td>";
						echo "<td class='center'>";
						
						if (isset($data["serial"]) && !empty($data["serial"])) 
						{
							$serial = $data["serial"];
							echo $serial;
						} else 
						{
							if (isset($data["otherserial"]) && !empty($data["otherserial"])) 
							{
								$serial = $data["otherserial"];
								echo $serial;
							} 	else 
							{
								echo '&nbsp;';
								$serial = '';
							}
						}
						
						if (isset($data["name"]) && !empty($data["name"])) {
							$item_name = $data["name"];
						}
						else
							$item_name = '';
						
						$Owner = new User();
						$Owner->getFromDB($id);
						$Author = new User();
						$Author->getFromDB(Session::getLoginUserID());
						$owner = $Owner->getRawName();
						$author = $Author->getRawName();
						
						echo "<input type='hidden' name='owner' value ='$owner'>";
						echo "<input type='hidden' name='author' value ='$author'>";
						echo "<input type='hidden' name='type_name[]' value='$type_name'>";
						echo "<input type='hidden' name='man_name[]' value='$man_name'>";
						echo "<input type='hidden' name='mod_name[]' value='$mod_name'>";
						echo "<input type='hidden' name='serial[]' value='$serial'>";
						echo "<input type='hidden' name='item_name[]' value='$item_name'>";
						echo "<input type='hidden' name='user_id' value='$id'>";
						
						echo "</td>";
						echo "<td class='center'><input type='text' name='comments[]'></td>";
						echo "</tr>";  
					$counter++;
				}
				
			}
			
		}
				
			echo "</table>";
			Html::closeForm();
			echo "</div><br>";

			echo "<div class='spaced'>";
			echo "<form method='post' name='docs_form' action='".$CFG_GLPI["root_doc"]."/plugins/protocolsmanager/front/generate.form.php'>";
			echo "<table class='tab_cadre_fixe'><td style='width:5%'><img src='../pics/arrow-left-top.png'></td><td style='width:5%'>";
			echo "<input type='submit' name='delete' class='submit' value=".__('Delete').">";
			echo "</td><td style='width:90%'></table>";
			echo "<table class='tab_cadre_fixehov'>";
			echo "<th width='10'><input type='checkbox' class='checkalldoc'></th>";
			$header2 = "<th>".__('Type')."</th>";
			$header2 .= "<th>".__('Date');
			$header2 .= "<th>".__('File')."</th>";
			$header2 .= "<th>".__('Creator')."</th></tr>";
			echo $header2;
			self::getAllForUser($id);
			echo "</table>";
			Html::closeForm();
			echo "</div>";
	  
			return true;
			
		}

}


?>

<script>
$(function(){
    $('.checkall').on('click', function() {
        $('.child').prop('checked', this.checked)
    });
});
$(function(){
    $('.checkalldoc').on('click', function() {
        $('.docchild').prop('checked', this.checked)
    });
});
</script>