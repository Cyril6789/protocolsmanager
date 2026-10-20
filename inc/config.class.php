<?php

class PluginProtocolsmanagerConfig extends CommonDBTM {
	
	
	function showFormProtocolsmanager() {
		global $CFG_GLPI, $DB;
		$plugin_conf = self::checkRights();

		if ($plugin_conf == 'w') {	
			self::displayContent();	
		} else {
			echo "<div align='center'><br><img src='".$CFG_GLPI['root_doc']."/pics/warning.png'><br>".__("Access denied")."</div>";
		}
	}
	
	
	static function checkRights() {
		global $DB;
		$active_profile = $_SESSION['glpiactiveprofile']['id'];
		
		// Updated DB->request syntax
		$req = $DB->request([
			'FROM' => 'glpi_plugin_protocolsmanager_profiles',
			'WHERE' => ['profile_id' => $active_profile]
		]);
					
        if($row = $req->current()) {
            $plugin_conf = $row['plugin_conf'];
        }
        else{
            $plugin_conf = "";
        }

        return $plugin_conf;


	}
	
	static function displayContent() {
		$section = (string)($_GET['section'] ?? $_POST['section'] ?? 'inventory');
		$sections = ['inventory', 'commission', 'disposal', 'branding', 'email'];
		if (!in_array($section, $sections, true)) {
			$section = 'inventory';
		}
		self::displaySettingsNavigation($section);
		switch ($section) {
			case 'commission':
			case 'disposal':
				self::displayPvTemplateSettings($section);
				break;
			case 'branding':
				self::displayBrandingSettings();
				break;
			case 'email':
				self::displayContentEmail();
				break;
			default:
				self::displayContentConfig();
		}
		echo '</div>';
	}

	private static function displaySettingsNavigation(string $active): void
	{
		$sections = [
			'inventory' => __('Modèles d’inventaire', 'protocolsmanager'),
			'commission' => __('PV de mise en service', 'protocolsmanager'),
			'disposal' => __('PV de mise au rebut', 'protocolsmanager'),
			'branding' => __('Identité visuelle', 'protocolsmanager'),
			'email' => __('E-mails', 'protocolsmanager'),
		];
		echo "<div class='container-fluid px-0 protocolsmanager-settings'>";
		echo "<div class='mb-3'><h1 class='h2 mb-1'>" . htmlescape(__('Configuration de Protocols Manager', 'protocolsmanager')) . "</h1>";
		echo "<p class='text-muted mb-0'>" . htmlescape(__('Gérez les modèles d’inventaire, les procès-verbaux et leur présentation.', 'protocolsmanager')) . '</p></div>';
		echo "<nav class='nav nav-tabs mb-4' aria-label='" . htmlescape(__('Sections de configuration', 'protocolsmanager')) . "'>";
		foreach ($sections as $key => $label) {
			$class = $key === $active ? 'nav-link active' : 'nav-link';
			$current = $key === $active ? " aria-current='page'" : '';
			echo "<a class='" . $class . "' href='config.form.php?section=" . rawurlencode($key) . "'" . $current . '>' . htmlescape($label) . '</a>';
		}
		echo '</nav>';
	}

	private static function displayPvTemplateSettings(string $type): void
	{
		$template = PluginProtocolsmanagerPvtemplate::load($type);
		$defaults = PluginProtocolsmanagerPvtemplate::defaults($type);
		$template = array_replace_recursive($defaults, $template);
		$title = $type === 'commission' ? __('Mise en service', 'protocolsmanager') : __('Mise au rebut', 'protocolsmanager');
		$help = __('Ces réglages déterminent les champs proposés à la création et les chapitres du PDF généré.', 'protocolsmanager');
		echo "<div class='mb-3'><h2 class='h3 mb-1'>" . htmlescape($title) . "</h2><p class='text-muted'>" . htmlescape($help) . '</p></div>';
		echo "<form action='config.form.php?section=" . htmlescape($type) . "' method='post' class='protocolsmanager-settings-form'>";
		if (method_exists(Session::class, 'getNewCSRFToken')) {
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
		}
		echo Html::hidden('section', ['value' => $type]);
		echo "<section class='card mb-3'><div class='card-header'><h3 class='h5 mb-0'>" . htmlescape(__('Présentation du document', 'protocolsmanager')) . '</h3></div><div class="card-body">';
		echo "<label class='form-label' for='pv-title'>" . htmlescape(__('Titre du document', 'protocolsmanager')) . "</label><input class='form-control' id='pv-title' name='template[title]' maxlength='255' required value='" . htmlescape($template['title'] ?? '') . "'>";
		echo '</div></section>';

		echo "<section class='card mb-3'><div class='card-header'><h3 class='h5 mb-0'>" . htmlescape(__('Formulaire de création', 'protocolsmanager')) . '</h3></div><div class="card-body">';
		$form_fields = $template['form'] ?? [];
		$simple_fields = $type === 'commission'
			? ['effective_date', 'planned_use', 'city', 'observations']
			: ['disposal_date', 'reason', 'method'];
		foreach ($simple_fields as $key) {
			$field = $form_fields[$key] ?? ['label' => $key, 'enabled' => true];
			$id = 'pv-field-' . $key;
			echo "<div class='row align-items-center g-2 py-2 border-bottom'><div class='col-md-3'><label class='form-label mb-md-0' for='" . htmlescape($id) . "'>" . htmlescape(self::pvFieldTitle($key)) . '</label></div>';
			echo "<div class='col-md-7'><input class='form-control' id='" . htmlescape($id) . "' name='template[form][" . htmlescape($key) . "][label]' maxlength='160' value='" . htmlescape($field['label'] ?? '') . "'></div>";
			echo "<div class='col-md-2'><input type='hidden' name='template[form][" . htmlescape($key) . "][enabled]' value='0'><label class='form-check'><input class='form-check-input' type='checkbox' name='template[form][" . htmlescape($key) . "][enabled]' value='1'" . (!empty($field['enabled']) ? ' checked' : '') . '> <span class="form-check-label">' . htmlescape(__('Afficher', 'protocolsmanager')) . '</span></label></div></div>';
		}

		if ($type === 'commission') {
			self::displayCheckboxListEditor($type, 'states', $form_fields['states'] ?? []);
			echo "<h4 class='h6 mt-4'>" . htmlescape(__('Lieux proposés', 'protocolsmanager')) . '</h4><div class="row g-3">';
			foreach (($form_fields['cities'] ?? []) as $index => $city) {
				$path = 'template[form][cities][' . (int)$index . ']';
				echo '<div class="col-md-6"><label class="form-label">' . htmlescape(__('Lieu', 'protocolsmanager')) . '</label><input type="hidden" name="' . htmlescape($path . '[key]') . '" value="' . htmlescape($city['key'] ?? '') . '"><input class="form-control" maxlength="100" name="' . htmlescape($path . '[label]') . '" value="' . htmlescape($city['label'] ?? '') . '"><input type="hidden" name="' . htmlescape($path . '[enabled]') . '" value="0"><label class="form-check mt-1"><input class="form-check-input" type="checkbox" name="' . htmlescape($path . '[enabled]') . '" value="1"' . (!empty($city['enabled']) ? ' checked' : '') . '> <span class="form-check-label">' . htmlescape(__('Proposer dans le formulaire', 'protocolsmanager')) . '</span></label></div>';
			}
			echo '</div>';
		} else {
			$list = $form_fields['methods'] ?? [];
			echo "<h4 class='h6 mt-4'>" . htmlescape(__('Modes proposés', 'protocolsmanager')) . '</h4><div class='table-responsive'><table class='table align-middle'><thead><tr><th>" . htmlescape(__('Libellé', 'protocolsmanager')) . '</th><th>' . htmlescape(__('Actif', 'protocolsmanager')) . '</th></tr></thead><tbody>';
			foreach ($list as $index => $entry) {
				$path = 'template[form][methods][' . (int)$index . ']';
				echo '<tr><td><input type="hidden" name="' . htmlescape($path . '[key]') . '" value="' . htmlescape($entry['key'] ?? '') . '"><input class="form-control" maxlength="160" aria-label="' . htmlescape(__('Libellé', 'protocolsmanager')) . '" name="' . htmlescape($path . '[label]') . '" value="' . htmlescape($entry['label'] ?? '') . '"></td><td><input type="hidden" name="' . htmlescape($path . '[enabled]') . '" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="' . htmlescape($path . '[enabled]') . '" value="1"' . (!empty($entry['enabled']) ? ' checked' : '') . '> <span class="visually-hidden">' . htmlescape(__('Actif', 'protocolsmanager')) . '</span></label></td></tr>';
			}
			echo '</tbody></table></div><p class="form-text">' . htmlescape(__('Le mode « Autre » conserve son champ de précision obligatoire.', 'protocolsmanager')) . '</p>';
			self::displayCheckboxListEditor($type, 'checks', $form_fields['checks'] ?? []);
		}
		echo '</div></section>';

		echo "<section class='card mb-3'><div class='card-header'><h3 class='h5 mb-0'>" . htmlescape(__('Chapitres du PDF', 'protocolsmanager')) . '</h3></div><div class="card-body">';
		echo '<p class="form-text">' . htmlescape(__('Activez ou masquez chaque chapitre, puis modifiez son titre et le texte introductif. Les tableaux et valeurs dynamiques restent calculés à partir du formulaire et du matériel.', 'protocolsmanager')) . '</p>';
		foreach (($template['chapters'] ?? []) as $index => $chapter) {
			$path = 'template[chapters][' . (int)$index . ']';
			echo "<fieldset class='border rounded p-3 mb-3'><legend class='float-none w-auto px-2 fs-6'>" . htmlescape(__('Chapitre', 'protocolsmanager') . ' ' . ((int)$index + 1)) . '</legend>';
			echo '<input type="hidden" name="' . htmlescape($path . '[key]') . '" value="' . htmlescape($chapter['key'] ?? '') . '">';
			echo '<label class="form-label" for="chapter-title-' . (int)$index . '">' . htmlescape(__('Titre', 'protocolsmanager')) . '</label><input class="form-control mb-2" id="chapter-title-' . (int)$index . '" name="' . htmlescape($path . '[title]') . '" maxlength="180" value="' . htmlescape($chapter['title'] ?? '') . '">';
			echo '<label class="form-label" for="chapter-content-' . (int)$index . '">' . htmlescape(__('Texte et instructions', 'protocolsmanager')) . '</label><textarea class="form-control mb-2" id="chapter-content-' . (int)$index . '" name="' . htmlescape($path . '[content]') . '" rows="3" maxlength="5000">' . htmlescape($chapter['content'] ?? '') . '</textarea>';
			echo '<input type="hidden" name="' . htmlescape($path . '[enabled]') . '" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="' . htmlescape($path . '[enabled]') . '" value="1"' . (!empty($chapter['enabled']) ? ' checked' : '') . '> <span class="form-check-label">' . htmlescape(__('Inclure dans le PDF', 'protocolsmanager')) . '</span></label></fieldset>';
		}
		echo '</div></section>';
		echo "<div class='d-flex justify-content-end mb-4'><button class='btn btn-primary' type='submit' name='save_pv_template' value='1'><i class='ti ti-device-floppy me-1'></i>" . htmlescape(__('Enregistrer le modèle', 'protocolsmanager')) . '</button></div></form>';
	}

	private static function displayCheckboxListEditor(string $type, string $list_key, array $entries): void
	{
		$title = $type === 'commission' ? __('Cases de mise en service', 'protocolsmanager') : __('Cases de contrôle de mise au rebut', 'protocolsmanager');
		$add_id = 'add-pv-check-' . $type;
		$list_id = 'pv-check-list-' . $type;
		$base = 'template[form][' . $list_key . ']';
		$next_index = $entries === [] ? 0 : max(array_map('intval', array_keys($entries))) + 1;
		echo "<div class='mt-4'><div class='d-flex align-items-center justify-content-between flex-wrap gap-2'><h4 class='h6 mb-0'>" . htmlescape($title) . '</h4>';
		echo '<button class="btn btn-sm btn-outline-primary" type="button" id="' . htmlescape($add_id) . '" data-pv-add-row="' . htmlescape($list_id) . '"><i class="ti ti-plus me-1"></i>' . htmlescape(__('Ajouter une case', 'protocolsmanager')) . '</button></div>';
		echo '<div class="table-responsive mt-2"><table class="table align-middle"><thead><tr><th>' . htmlescape(__('Libellé de la case', 'protocolsmanager')) . '</th><th>' . htmlescape(__('Afficher', 'protocolsmanager')) . '</th><th>' . htmlescape(__('Cochée par défaut', 'protocolsmanager')) . '</th><th><span class="visually-hidden">' . htmlescape(__('Action', 'protocolsmanager')) . '</span></th></tr></thead><tbody id="' . htmlescape($list_id) . '" data-next-index="' . $next_index . '">';
		foreach ($entries as $index => $entry) {
			$path = $base . '[' . (int)$index . ']';
			self::displayCheckboxRow($path, (string)($entry['key'] ?? ''), (string)($entry['label'] ?? ''), !empty($entry['enabled']), !empty($entry['checked']));
		}
		echo '</tbody></table></div>';
		echo '<template id="template-' . htmlescape($list_id) . '">';
		self::displayCheckboxRow($base . '[__INDEX__]', 'custom___INDEX__', '', true, false);
		echo '</template></div>';
	}

	private static function displayCheckboxRow(string $path, string $key, string $label, bool $enabled, bool $checked): void
	{
		$path = htmlescape($path);
		echo '<tr data-pv-config-row><td><input type="hidden" name="' . $path . '[key]" value="' . htmlescape($key) . '"><input class="form-control" maxlength="160" name="' . $path . '[label]" value="' . htmlescape($label) . '" aria-label="' . htmlescape(__('Libellé de la case', 'protocolsmanager')) . '"></td>';
		echo '<td><input type="hidden" name="' . $path . '[enabled]" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="' . $path . '[enabled]" value="1"' . ($enabled ? ' checked' : '') . '> <span class="visually-hidden">' . htmlescape(__('Afficher', 'protocolsmanager')) . '</span></label></td>';
		echo '<td><input type="hidden" name="' . $path . '[checked]" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="' . $path . '[checked]" value="1"' . ($checked ? ' checked' : '') . '> <span class="visually-hidden">' . htmlescape(__('Cochée par défaut', 'protocolsmanager')) . '</span></label></td>';
		echo '<td><button class="btn btn-sm btn-outline-danger" type="button" data-pv-remove-row aria-label="' . htmlescape(__('Supprimer cette case', 'protocolsmanager')) . '"><i class="ti ti-trash" aria-hidden="true"></i></button></td></tr>';
	}

	private static function pvFieldTitle(string $key): string
	{
		$labels = [
			'effective_date' => __('Date effective de mise en service', 'protocolsmanager'),
			'planned_use' => __('Description de l’utilisation prévue', 'protocolsmanager'),
			'city' => __('Lieu d’établissement du document', 'protocolsmanager'),
			'observations' => __('Observations éventuelles', 'protocolsmanager'),
			'disposal_date' => __('Date de mise au rebut', 'protocolsmanager'),
			'reason' => __('Motif de la mise au rebut', 'protocolsmanager'),
			'method' => __('Mode de mise au rebut', 'protocolsmanager'),
		];
		return $labels[$key] ?? $key;
	}

	private static function displayBrandingSettings(): void
	{
		$branding = PluginProtocolsmanagerPvtemplate::load('branding');
		$color = $branding['accent_color'] ?? '#8A6428';
		if (!is_string($color) || !preg_match('/^#[0-9a-f]{6}$/i', $color)) {
			$color = '#8A6428';
		}
		echo "<div class='mb-3'><h2 class='h3 mb-1'>" . htmlescape(__('Identité visuelle globale', 'protocolsmanager')) . "</h2><p class='text-muted'>" . htmlescape(__('Le logo et la couleur d’accentuation seront partagés par les trois familles de documents.', 'protocolsmanager')) . '</p></div>';
		echo "<form action='config.form.php?section=branding' method='post' enctype='multipart/form-data' class='protocolsmanager-settings-form'>";
		if (method_exists(Session::class, 'getNewCSRFToken')) {
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
		}
		echo Html::hidden('section', ['value' => 'branding']);
		echo "<section class='card mb-3'><div class='card-header'><h3 class='h5 mb-0'>" . htmlescape(__('Logo des PDF', 'protocolsmanager')) . '</h3></div><div class="card-body">';
		if (!empty($branding['logo'])) {
			$path = GLPI_PICTURE_DIR . '/' . basename((string)$branding['logo']);
			if (is_file($path) && is_readable($path)) {
				$mime = mime_content_type($path) ?: 'image/png';
				echo '<div class="mb-3"><img class="img-thumbnail" style="max-height:100px;max-width:240px" alt="' . htmlescape(__('Logo actuel', 'protocolsmanager')) . '" src="data:' . htmlescape($mime) . ';base64,' . base64_encode((string)file_get_contents($path)) . '"></div>';
			}
		}
		echo '<label class="form-label" for="global-pdf-logo">' . htmlescape(__('Choisir un logo PNG ou JPEG (2 Mo maximum)', 'protocolsmanager')) . '</label><input class="form-control" type="file" id="global-pdf-logo" name="global_logo" accept="image/png,image/jpeg">';
		if (!empty($branding['logo'])) {
			echo '<input type="hidden" name="remove_logo" value="0"><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_logo" value="1"> <span class="form-check-label">' . htmlescape(__('Supprimer le logo global', 'protocolsmanager')) . '</span></label>';
		}
		echo '</div></section>';
		echo "<section class='card mb-3'><div class='card-header'><h3 class='h5 mb-0'>" . htmlescape(__('Couleur d’accentuation', 'protocolsmanager')) . '</h3></div><div class="card-body"><label class="form-label" for="global-accent">' . htmlescape(__('Couleur appliquée aux titres et repères des PDF', 'protocolsmanager')) . '</label><div class="d-flex align-items-center gap-3"><input class="form-control form-control-color" type="color" id="global-accent" name="accent_color" value="' . htmlescape($color) . '"><code>' . htmlescape($color) . '</code></div></div></section>';
		echo "<div class='d-flex justify-content-end mb-4'><button class='btn btn-primary' type='submit' name='save_branding' value='1'><i class='ti ti-device-floppy me-1'></i>" . htmlescape(__('Enregistrer l’identité visuelle', 'protocolsmanager')) . '</button></div></form>';
	}

	public static function savePvTemplate(): bool
	{
		$type = (string)($_POST['section'] ?? '');
		if (!in_array($type, ['commission', 'disposal'], true)) {
			return false;
		}
		$defaults = PluginProtocolsmanagerPvtemplate::defaults($type);
		$posted = is_array($_POST['template'] ?? null) ? $_POST['template'] : [];
		$template = PluginProtocolsmanagerPvtemplate::load($type);
		$template = array_replace_recursive($defaults, $template);
		$template['title'] = self::settingText($posted['title'] ?? '', 255) ?: $defaults['title'];
		$posted_form = is_array($posted['form'] ?? null) ? $posted['form'] : [];
		foreach (array_keys($defaults['form']) as $key) {
			if (!is_array($defaults['form'][$key])) {
				continue;
			}
			if ($key === 'states' || $key === 'checks') {
				$template['form'][$key] = self::sanitizeCheckboxEntries($posted_form[$key] ?? []);
				continue;
			}
			if (in_array($key, ['states', 'cities', 'methods'], true)) {
				$source = is_array($posted_form[$key] ?? null) ? $posted_form[$key] : [];
				foreach ($defaults['form'][$key] as $index => $default_entry) {
					$entry = is_array($source[$index] ?? null) ? $source[$index] : [];
					$template['form'][$key][$index] = $default_entry;
					$template['form'][$key][$index]['label'] = self::settingText($entry['label'] ?? $default_entry['label'], 160);
					$template['form'][$key][$index]['enabled'] = ($entry['enabled'] ?? '0') === '1';
					if (array_key_exists('checked', $default_entry)) {
						$template['form'][$key][$index]['checked'] = ($entry['checked'] ?? '0') === '1';
					}
				}
			} else {
				$entry = is_array($posted_form[$key] ?? null) ? $posted_form[$key] : [];
				$template['form'][$key]['label'] = self::settingText($entry['label'] ?? $template['form'][$key]['label'], 160);
				$template['form'][$key]['enabled'] = ($entry['enabled'] ?? '0') === '1';
			}
		}
		$posted_chapters = is_array($posted['chapters'] ?? null) ? $posted['chapters'] : [];
		foreach ($defaults['chapters'] as $index => $chapter) {
			$entry = is_array($posted_chapters[$index] ?? null) ? $posted_chapters[$index] : [];
			$template['chapters'][$index] = $chapter;
			$template['chapters'][$index]['title'] = self::settingText($entry['title'] ?? $chapter['title'], 180) ?: $chapter['title'];
			$template['chapters'][$index]['content'] = self::settingText($entry['content'] ?? $chapter['content'], 5000);
			$template['chapters'][$index]['enabled'] = ($entry['enabled'] ?? '0') === '1';
		}
		return PluginProtocolsmanagerPvtemplate::save($type, $template);
	}

	private static function sanitizeCheckboxEntries($source): array
	{
		if (!is_array($source)) {
			return [];
		}
		$entries = [];
		$keys = [];
		foreach ($source as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$key = (string)($entry['key'] ?? '');
			if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key) || isset($keys[$key])) {
				continue;
			}
			$label = self::settingText($entry['label'] ?? '', 160);
			if ($label === '') {
				continue;
			}
			$keys[$key] = true;
			$entries[] = [
				'key' => $key,
				'label' => $label,
				'enabled' => ($entry['enabled'] ?? '0') === '1',
				'checked' => ($entry['checked'] ?? '0') === '1',
			];
			if (count($entries) >= 100) {
				break;
			}
		}
		return $entries;
	}

	public static function saveBranding(): bool
	{
		$branding = PluginProtocolsmanagerPvtemplate::load('branding');
		$color = $_POST['accent_color'] ?? '';
		if (!is_string($color) || !preg_match('/^#[0-9a-f]{6}$/i', $color)) {
			Session::addMessageAfterRedirect(__('La couleur indiquée est invalide.', 'protocolsmanager'), false, ERROR);
			return false;
		}
		$branding['accent_color'] = $color;
		if (($_POST['remove_logo'] ?? '0') === '1') {
			$branding['logo'] = '';
		}
		$file = $_FILES['global_logo'] ?? null;
		if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
			if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)($file['size'] ?? 0) > 2097152) {
				Session::addMessageAfterRedirect(__('Le logo dépasse 2 Mo ou le téléversement a échoué.', 'protocolsmanager'), false, ERROR);
				return false;
			}
			$image = @getimagesize($file['tmp_name']);
			$mime = is_array($image) ? ($image['mime'] ?? '') : '';
			$extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
			if (!isset($extensions[$mime])) {
				Session::addMessageAfterRedirect(__('Le logo doit être une image PNG ou JPEG valide.', 'protocolsmanager'), false, ERROR);
				return false;
			}
			$name = 'protocolsmanager-global-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
			$destination = GLPI_PICTURE_DIR . '/' . $name;
			if (!move_uploaded_file($file['tmp_name'], $destination)) {
				Session::addMessageAfterRedirect(__('Le logo n’a pas pu être enregistré.', 'protocolsmanager'), false, ERROR);
				return false;
			}
			$branding['logo'] = $name;
		}
		return PluginProtocolsmanagerPvtemplate::save('branding', $branding);
	}

	private static function settingText($value, int $limit): string
	{
		if (!is_string($value)) {
			return '';
		}
		return mb_substr(trim(strip_tags($value)), 0, $limit);
	}
	
	static function displayContentConfig() {
		global $CFG_GLPI, $DB;
		
		if (isset($_POST["edit_id"])) {
			
			$edit_id = $_POST['edit_id'];
			$mode = $edit_id;
			
			// Updated DB->request syntax
			$req = $DB->request([
				'FROM' => 'glpi_plugin_protocolsmanager_config',
				'WHERE' => ['id' => $edit_id ]
			]);
				
			if ($row = $req->current()) {
				$template_uppercontent = $row["upper_content"];
				$template_content = $row["content"];
				$template_footer = $row["footer"];
				$template_name = $row["name"];
				$title = $row["title"];
				$font = $row["font"];
				$fontsize = $row["fontsize"];
				$city = $row["city"];
				$logo = $row["logo"];
				$serial_mode = $row["serial_mode"];
				$orientation = $row["orientation"];
				$breakword = $row["breakword"];
				$email_mode = $row["email_mode"];
				$email_template = $row["email_template"];
				$author_name = $row["author_name"];
				$author_state = $row["author_state"];
	
				// Récupérer width/height du logo si présents
				$logo_width = isset($row["logo_width"]) ? $row["logo_width"] : '';
				$logo_height = isset($row["logo_height"]) ? $row["logo_height"] : '';
				$theme_color = isset($row["theme_color"]) ? $row["theme_color"] : '#8A6428';
				$header_text_color = isset($row["header_text_color"]) ? $row["header_text_color"] : '#FFFFFF';
			}
			
		} else {
			$template_uppercontent = '';
			$template_content = '';
			$template_footer = '';
			$template_name = '';
			$title = '';
			$font = '';
			$fontsize = '9';
			$city = '';
			$mode = 0; //if mode 0 then you creating new template instead of edit
			$serial_mode = 1;
			$orientation = "p";
			$breakword = 1;
			$email_mode = 2;
			$email_template = 1;
			$author_name = '';
			$author_state = 1;
			$logo_width = '';
			$logo_height = '';
			$theme_color = '#8A6428';
			$header_text_color = '#FFFFFF';
		}
		if (!is_string($theme_color) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $theme_color)) {
			$theme_color = '#8A6428';
		}
		if (!is_string($header_text_color) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $header_text_color)) {
			$header_text_color = '#FFFFFF';
		}
		
		$fonts = array('Courier' => 'Courier',
						'Helvetica' => 'Helvetica', 
						'Times' => 'Times',
						'Istok' => 'Istok',
						'UbuntuMono' => 'UbuntuMono',
						'Roboto' => 'Roboto',
						'Liberation-Sans' => 'Liberation-Sans',
						'DroidSerif' => 'DroidSerif',
						'DejaVu Sans' => 'DejaVu Sans');
						
		$fontsizes = array('7' => '7',
							'8' => '8',
							'9' => '9',
							'10' => '10',
							'11' => '11',
							'12' => '12');
						
		$orientations = array('Portrait' => 'Portrait',
							'Landscape' => 'Landscape');
		
		if (!isset($font)) {
			$font='freesans';
		}
		
		echo "<div class='center'>";
		echo "<form name='form' action='config.form.php' method='post' enctype='multipart/form-data' class='protocolsmanager-settings-form'>";
		echo Html::hidden('section', ['value' => 'inventory']);
		echo "<input type='hidden' name='MAX_FILE_SIZE' value=1948000>";
		// Added htmlescape for XSS protection
		echo "<input type='hidden' name='mode' value='" . htmlescape($mode) . "'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='3'>".__('Create')." ".__('template')."<a href='https://github.com/mateusznitka/protocolsmanager/wiki/Using-the-plugin' target='_blank'><img src='../img/help.png' width='20px' height='20px' align='right'></a></th></tr>";
		// Added htmlescape for XSS protection
		echo "<tr><td>".__('Template name')."*</td><td colspan='2'><input type='text' name='template_name' style='width:80%;' value='" . htmlescape($template_name) . "'></td></tr>";
		echo "<tr><td>".__('Document title', 'protocolsmanager')."*</td><td colspan='2'><input type='text' name='title' style='width:80%;' value='" . htmlescape($title) . "'></td></tr>";
		echo "<tr><td></td><td colspan='2'><small class='text-muted'>".__('You can use {owner} here.', 'protocolsmanager')."</small></td></tr>";

		echo "<tr><td>Font</td><td colspan='2'><select name='font' style='width:150px'>";
			foreach($fonts as $code => $fontname) {
				echo "<option value='".$code."' ";
				if ($code == $font) {
					echo " selected";
				}
				echo ">".$fontname."</option>";
			}
		echo "</select></td></tr>";
		
		echo "<tr><td>Font size</td><td colspan='2'><select name='fontsize' style='width:150px'";
			foreach($fontsizes as $fsize => $fsizes) {
				echo "<option value='".$fsize."' ";
				if ($fsize == $fontsize) {
					echo " selected";
				}
				echo ">".$fsizes."</option>";
			}
			
		echo "<tr><td>Word breaking</td><td><label><input type='radio' name='breakword' value=1 ";
		if ($breakword == 1)
			echo "checked='checked'";
		echo "> On</label></td>";
		echo "<td><label><input type='radio' name='breakword' value=0 ";
		if ($breakword == 0)
			echo "checked='checked'";
		echo "> Off</label></td></tr>";
		echo "<tr><td colspan='3'><div class='alert alert-info mb-0'>" . htmlescape(__('Le logo et la couleur sont désormais réglés globalement dans Identité visuelle.', 'protocolsmanager')) . " <a href='config.form.php?section=branding'>" . htmlescape(__('Ouvrir ces réglages', 'protocolsmanager')) . '</a></div></td></tr>';
		
		// Added htmlescape for XSS protection
		echo "<tr><td>".__('City')."</td><td colspan='2'><input type='text' name='city' style='width:80%;' value='" . htmlescape($city) . "'></td></tr>";
		echo "<tr><td>".__('Upper Content')."</td><td colspan='2' class='middle'><textarea style='width:80%; height:100px;' cols='50' rows'8' name='template_uppercontent'>" . htmlescape($template_uppercontent) . "</textarea></td></tr>";
		echo "<tr><td></td><td colspan='2'><small class='text-muted'>".__('You can use {owner} or {admin} here.', 'protocolsmanager')."</small></td></tr>";
		echo "<tr><td>".__('Content')."</td><td colspan='2' class='middle'><textarea style='width:80%; height:100px;' cols='50' rows'8' name='template_content'>" . htmlescape($template_content) . "</textarea></td></tr>";
		echo "<tr><td></td><td colspan='2'><small class='text-muted'>".__('You can use {owner} or {admin} here.', 'protocolsmanager')."</small></td></tr>";
		echo "<tr><td>".__('Footer')."</td><td class='middle' colspan='2'><textarea style='width:80%; height:100px;' cols='45' rows'4' name='footer_text'>" . htmlescape($template_footer) . "</textarea></td></tr>";	
		echo "<tr><td>".__('Orientation')."</td><td colspan='2'><select name='orientation' style='width:150px'>";
			foreach($orientations as $vals => $valname) {
				echo "<option value='".$vals."' ";
				if ($vals == $orientation) {
					echo " selected";
				}
				echo ">".$valname."</option>";
			}	
		echo "</select></td></tr>";
		echo "<tr><td>".__('Serial number')."</td><td><label><input type='radio' name='serial_mode' value='1' ";
		if ($serial_mode == 1)
			echo "checked='checked'";
		echo "> serial and inventory number in separate columns</label></td>";
		echo "<td><label><input type='radio' name='serial_mode' value='2' ";
		if ($serial_mode == 2)
			echo "checked='checked'";
		echo "> serial or inventory number if serial doesn't exists</label></td></tr>";
		// ... suite du formulaire ...
		echo "<tr><td>".__('Enable email autosending')."</td><td><label><input type='radio' name='email_mode' value='1'";
		if ($email_mode == 1)
			echo "checked='checked'";
		echo "> ON</label></td>";
		echo "<td><label><input type='radio' name='email_mode' value='2'";
		if ($email_mode == 2)
			echo "checked='checked'";
		echo "> OFF</label></td></tr>";
		echo "<tr><td>".__('Email template')."</td><td colspan='2'><select name='email_template' required style='width:150px'>";
			
			// Updated DB->request syntax
			foreach ($DB->request(['FROM' => 'glpi_plugin_protocolsmanager_emailconfig']) as $uid => $list) {
				echo '<option value=';
				// Added htmlescape for XSS protection
				echo htmlescape($list["id"]);
				if ($uid == $email_template) {
					echo ' selected';
				}
				echo '>';
				// Added htmlescape for XSS protection
				echo htmlescape($list["tname"]);
				echo '</option>';
			}
		echo "</select></td></tr>";
		/**
		 *
		 */
		echo "<tr><td>".__('Select who should generate the pdf', 'protocolsmanager')."</td>";
		
		if($author_state == 2)
		{
			echo "<td><label><input type='radio' name='author_state' value='1'> ".__('The user who generates the document', 'protocolsmanager')."</label></td>";
			// Added htmlescape for XSS protection
			echo "<td><input type='radio' name='author_state' value='2' checked='checked'> <input type='text' name='author_name' value='" . htmlescape($author_name) . "'/></td>";

		}
		else {
			echo "<td><label><input type='radio' name='author_state' value='1' checked='checked'> ".__('The user who generates the document', 'protocolsmanager')."</label></td>";
			// Added htmlescape for XSS protection
			echo "<td><input type='radio' name='author_state' value='2'> <input type='text' name='author_name' value='" . htmlescape($author_name) . "'/></td>";
		}

		echo "</tr>";
		/**
		*
		*/
	
		echo "</table>";
		echo "<table class='tab_cadre_fixe'><td style='text-align:right;'><input type='submit' name='save' class='submit'></td>";
		Html::closeForm();
		echo "<form name='cancelform' action='config.form.php' method='post'><td style='text-align:left;'><input type='submit' class='submit' name='cancel' value=".__('Cancel')."></td></table>";
		Html::closeForm();
		echo "</div>";
		echo "<br>";
		self::showConfigs();
	}
	
	
	static function DisplayContentEmail() {
		global $DB, $CFG_GLPI;
		
		if (isset($_POST["email_edit_id"])) {
			
			$email_edit_id = $_POST['email_edit_id'];

			// Updated DB->request syntax
			$req = $DB->request([
				'FROM' => 'glpi_plugin_protocolsmanager_emailconfig',
				'WHERE' => ['id' => $email_edit_id ]
			]);			
				
			if ($row = $req->current()) {
				$tname = $row["tname"];
				$send_user = $row["send_user"];
				$email_subject = $row["email_subject"];
				$email_content = $row["email_content"];
				$recipients = $row["recipients"];
			}
		} else {
			$tname = '';
			$send_user = 2;
			$email_subject = '';
			$email_content = '';
			$recipients = '';
			$email_edit_id = 0;
		}
		
		//email template edit
		echo "<div class='center'>";
		echo "<form name ='email_template_edit' action='config.form.php' method='post' enctype='multipart/form-data' class='protocolsmanager-settings-form'>";
		echo Html::hidden('section', ['value' => 'email']);
		
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='3'>".__('Create')." ".__('email template')."<a href='https://github.com/mateusznitka/protocolsmanager/wiki/Email-sending-configuration' target='_blank'><img src='../img/help.png' width='20px' height='20px' align='right'></a></th></tr>";
		// Added htmlescape for XSS protection
		echo "<tr><td>".__('Template name')."*</td><td colspan='2' class='middle'><input type='text' class='eboxes' name='tname' style='width:80%;' value='" . htmlescape($tname) . "'></td></tr>";
		echo "<tr><td>".__('Send to user')."</td><td><label><input type='radio' name='send_user' value='1' class='eboxes' ";
		if ($send_user == 1)
			echo "checked='checked'";
		echo "> send to user</label></td>";
		echo "<td><label><input type='radio' name='send_user' value='2' class='eboxes' ";
		if ($send_user == 2)
			echo "checked='checked'";
		echo "> don't send to user</label></td></tr>";
		// Added htmlescape for XSS protection
		echo "<tr><td>".__('Email content')."*</td><td colspan='2' class='middle'><textarea style='width:80%; height:100px;' class='eboxes' cols='50' rows'8' name='email_content'>" . htmlescape($email_content) . "</textarea></td></tr>";
		echo "<tr><td>".__('Email subject')."*</td><td colspan='2' class='middle'><input type='text' class='eboxes' name='email_subject' style='width:80%;' value='" . htmlescape($email_subject) . "'></td></tr>";
		echo "<tr><td>".__('Add emails - use ; to separate')."*</td><td colspan='2' class='middle'><textarea style='width:80%; height:100px;' class='eboxes' cols='50' rows '8' name='recipients'>" . htmlescape($recipients) . "</textarea></td></tr>";
		echo "</table>";
		// Added htmlescape for XSS protection
		echo "<input type='hidden' name='email_edit_id' value='" . htmlescape($email_edit_id) . "'>";
		echo "<table class='tab_cadre_fixe'><td style='text-align:right;'><input type='submit' name='save_email' class='submit' id='email_submit'></td>";
		Html::closeForm();
		echo "<form name='cancelform' action='config.form.php' method='post'><td style='text-align:left;'><input type='submit' class='submit' name='cancel_email' value=".__('Cancel')."></td></table>";
		Html::closeForm();
		echo "</div>";
		echo "<br>";
		self::showEmailConfigs();
	}
	
	static function saveConfigs() {
		global $DB, $CFG_GLPI;
		
		if (empty($_POST["template_name"]) || empty($_POST["email_template"]) || empty($_POST["title"])) {
			Session::AddMessageAfterRedirect('Fill mandatory fields', 'WARNING', true);
		} else {

			// No addslashes() needed here. GLPI 11 DB layer handles sanitization.
			$template_name = $_POST['template_name'];
			$title = $_POST['title'];
			$font = $_POST["font"];
			$fontsize = $_POST["fontsize"];
			$serial_mode = $_POST["serial_mode"];
			$orientation = $_POST["orientation"];
			$posted_theme_color = $_POST['theme_color'] ?? '';
			$theme_color = is_string($posted_theme_color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $posted_theme_color)
				? $posted_theme_color
				: '#8A6428';
			$posted_header_text_color = $_POST['header_text_color'] ?? '';
			$header_text_color = is_string($posted_header_text_color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $posted_header_text_color)
				? $posted_header_text_color
				: '#FFFFFF';

			if(isset($_POST["template_uppercontent"])) {
				$template_uppercontent = $_POST['template_uppercontent'];
			}
			
			if(isset($_POST["template_content"])) {
				$template_content = $_POST['template_content'];
			}

			if(isset($_POST["footer_text"])) {
				$template_footer = $_POST['footer_text'];
			}

			if(isset($_POST["city"])) {
				$city = $_POST["city"];
			}

			if(isset($_POST["mode"])) {
				$mode = $_POST["mode"];
			}

			if(isset($_POST["breakword"])) {
				$breakword = $_POST["breakword"];
			}

			if(isset($_POST["email_mode"])) {
				$email_mode = $_POST["email_mode"];
			}

			if(isset($_POST["email_template"])) {
				$email_template = $_POST["email_template"];
			}
			
			$full_img_name = self::uploadImage();

			if (isset($_POST['img_delete'])) {
				
				$DB->update('glpi_plugin_protocolsmanager_config', [
						'logo' => $full_img_name
					], [
						'id' => $mode
					]
				);
			}

			if (isset($_POST["author_name"])) {
				$author_name = $_POST["author_name"];

			}
			if (isset($_POST["author_state"])) {
				$author_state = $_POST["author_state"];

			}


			$logo_width = isset($_POST["logo_width"]) ? max(0, (int)$_POST["logo_width"]) : null;
			$logo_height = isset($_POST["logo_height"]) ? max(0, (int)$_POST["logo_height"]) : null;


			// TODO : concaténé quand les champs sont vides
			
			//if new template
			if ($mode == 0) {
				
				$DB->insert('glpi_plugin_protocolsmanager_config', [
					'name' => $template_name,
					'title' => $title,
					'upper_content' => $template_uppercontent,
					'content' => $template_content,
					'footer' => $template_footer,
					'logo' => $full_img_name,
					'logo_width'    => $logo_width,
					'logo_height'   => $logo_height,
					'font' => $font,
					'fontsize' => $fontsize,
					'city' => $city,
					'serial_mode' => $serial_mode,
					'orientation' => $orientation,
					'breakword' => $breakword,
					'email_mode' => $email_mode,
					'email_template' => $email_template,
					'author_name' => $author_name,
					'author_state' => $author_state,
					'theme_color' => $theme_color,
					'header_text_color' => $header_text_color
					]
				);
			}
			
			//if edit template
			if ($mode != 0) {


				// quand c'est séléctionner et qu'on refais sauvegardé
				// la valeur est égale à "X\"" au lieu de "X"

				// idéalement checker la value de $email_template
				// This sanitization is fine to keep
				$email_template = preg_replace('/[^A-Za-z0-9\-]/','',$email_template);


				//if logo is uploaded
				if (isset($full_img_name)) {
					
					$DB->update('glpi_plugin_protocolsmanager_config', [
							'name' => $template_name,
							'title' => $title,
							'content' => $template_content,
							'upper_content' => $template_uppercontent,
							'footer' => $template_footer,
							'logo' => $full_img_name,
							'logo_width'    => $logo_width,
							'logo_height'   => $logo_height,
							'font' => $font,
							'fontsize' => $fontsize,
							'city' => $city,
							'serial_mode' => $serial_mode,
							'orientation' => $orientation,
							'breakword' => $breakword,
							'email_mode' => $email_mode,
							'email_template' => $email_template,
							'author_name' => $author_name,
							'author_state' => $author_state,
							'theme_color' => $theme_color,
							'header_text_color' => $header_text_color
						], [
							'id' => $mode
						]
					);
				} else {
					if(!$city)
					{
						$city = '';
					}
					if(!$template_uppercontent)
					{
						$template_uppercontent = '';
					}
					if(!$template_content)
					{
						$template_content = '';
					}
					if(!$template_footer)
					{
						$template_footer = '';
					}
					if(!isset($email_mode))
					{
						$DB->update('glpi_plugin_protocolsmanager_config', [
							'name' => $template_name,
							'title' => $title,
							'content' => $template_content,
							'upper_content' => $template_uppercontent,
							'footer' => $template_footer,
							'logo_width'    => $logo_width,
							'logo_height'   => $logo_height,
							'font' => $font,
							'fontsize' => $fontsize,
							'city' => $city,
							'serial_mode' => $serial_mode,
							'orientation' => $orientation,
							'breakword' => $breakword,
							'email_template' => $email_template,
							'author_name' => $author_name,
							'author_state' => $author_state,
							'theme_color' => $theme_color,
							'header_text_color' => $header_text_color
						], [
							'id' => $mode
						]
					);
					}
					else {
						$DB->update('glpi_plugin_protocolsmanager_config', [
							'name' => $template_name,
							'title' => $title,
							'content' => $template_content,
							'upper_content' => $template_uppercontent,
							'footer' => $template_footer,
							'logo_width'    => $logo_width,
							'logo_height'   => $logo_height,
							'font' => $font,
							'fontsize' => $fontsize,
							'city' => $city,
							'serial_mode' => $serial_mode,
							'orientation' => $orientation,
							'breakword' => $breakword,
							'email_mode' => $email_mode,
							'email_template' => $email_template,
							'author_name' => $author_name,
							'author_state' => $author_state,
							'theme_color' => $theme_color,
							'header_text_color' => $header_text_color
						], [
							'id' => $mode
						]
					);
					}
				}
			}
			
			Session::AddMessageAfterRedirect('Config saved');
		}		
	
	}
	
	static function saveEmailConfigs() {
		global $DB, $CFG_GLPI;
		
		if (empty($_POST["email_subject"]) || empty($_POST["email_content"]) || empty($_POST["recipients"]) || empty($_POST["tname"])) {
			Session::AddMessageAfterRedirect('Fill mandatory fields', 'WARNING', true);
		} else {
			
			// No addslashes() needed here
			$tname = $_POST["tname"];
			$send_user = $_POST["send_user"];
			$email_subject = $_POST["email_subject"];
			$email_content = $_POST["email_content"];
			$recipients = $_POST["recipients"];
			$email_edit_id = $_POST["email_edit_id"];
			
			if($email_edit_id == 0) {
				
				$DB->insert('glpi_plugin_protocolsmanager_emailconfig', [
					'tname' => $tname,
					'send_user' => $send_user,
					'email_subject' => $email_subject,
					'email_content' => $email_content,
					'recipients' => $recipients
					]
				);
			}
				
			if($email_edit_id != 0) {
				
				$DB->update('glpi_plugin_protocolsmanager_emailconfig', [
					'tname' => $tname,
					'send_user' => $send_user,
					'email_subject' => $email_subject,
					'email_content' => $email_content,
					'recipients' => $recipients
					], [
					'id' => $email_edit_id
					]
				);
				
			}
			
			Session::AddMessageAfterRedirect('Config saved');
		}

	}
	

	static function showConfigs() {
		global $DB, $CFG_GLPI;
		$configs = [];
		
		echo "<div class='spaced' id='show_configs'>";
		echo "<table class='tab_cadre_fixehov' style='width:90%;'>";
		echo "<tr class='tab_bg_1'><th colspan='3'>".__('Templates')."</th></tr>";
		echo "<tr class='tab_bg_1'><td class='center'><b>".__('Name')."</b></td>";
		echo "<td class='center' colspan=2'><b>".__('Action')."</b></td></tr>";
		
		// Updated DB->request syntax
		foreach ($DB->request(['FROM' => 'glpi_plugin_protocolsmanager_config']) as $config_data => $configs) {
				
				echo "<tr class='tab_bg_1'><td class='center'>";
				// Added htmlescape for XSS protection
				echo htmlescape($configs['name']);
				echo "</td>";
				$conf_id = $configs['id'];
				// Added htmlescape for XSS protection
				echo "<td class='center' width='7%'>
						<form method='post' action='config.form.php'><input type='hidden' value='" . htmlescape($conf_id) . "' name='edit_id'><input type='submit' name='edit' value=".__('Edit')." class='submit'></td>";
						echo "<input type='hidden' name='menu_mode' value='t'>";
						Html::closeForm();	
						echo "<td class='center' width='7%'><form method='post' action='config.form.php'><input type='hidden' value='" . htmlescape($conf_id) . "' name='conf_id'><input type='submit' name='delete' value=".__('Delete')." class='submit'></td></tr>";
						Html::closeForm();				
			}
		echo "</table></div>";
	}
	
	static function showEmailConfigs() {
		global $DB, $CFG_GLPI;
		$emailconfigs = [];
		
		echo "<div class='spaced' id='show_emailconfigs'>";
		echo "<table class='tab_cadre_fixehov' style='width:90%;'>";
		echo "<tr class='tab_bg_1'><th colspan='4'>".__('Templates')."</th></tr>";
		echo "<tr class='tab_bg_1'><td class='center'><b>".__('Name')."</b></td>";
		echo "<td class='center'><b>".__('Recipients')."</b></td>";
		echo "<td class='center' colspan=2'><b>".__('Action')."</b></td></tr>";
		
		// Updated DB->request syntax
		foreach ($DB->request(['FROM' => 'glpi_plugin_protocolsmanager_emailconfig']) as $configs_data => $emailconfigs) {
				
				echo "<tr class='tab_bg_1'><td class='center'>";
				// Added htmlescape for XSS protection
				echo htmlescape($emailconfigs['tname']);
				echo "</td>";
				echo "<td class='center'>";
				// Added htmlescape for XSS protection
				echo htmlescape($emailconfigs['recipients']);
				echo "</td>";
				$email_conf_id = $emailconfigs['id'];
				// Added htmlescape for XSS protection
				echo "<td class='center' width='7%'>
						<form method='post' action='config.form.php'><input type='hidden' value='" . htmlescape($email_conf_id) . "' name='email_edit_id'><input type='submit' name='email_edit' value=".__('Edit')." class='submit'></td>";
						echo "<input type='hidden' name='menu_mode' value='e'>";
						Html::closeForm();	
						echo "<td class='center' width='7%'><form method='post' action='config.form.php'><input type='hidden' value='" . htmlescape($email_conf_id) . "' name='email_conf_id'><input type='submit' name='delete_email' value=".__('Delete')." class='submit'></td></tr>";
						Html::closeForm();				
			}
		echo "</table></div>";
	}
	
	static function uploadImage() {
		global $DB, $CFG_GLPI;
		if (!isset($_FILES['logo']) || !is_array($_FILES['logo']) || empty($_FILES['logo']['name'])) {
			return null;
		}
		if($_FILES['logo']['name']) {
			
			if($_FILES['logo']['error'] != UPLOAD_ERR_FORM_SIZE) {
			
				if (!$_FILES['logo']['error']) {
					
					if ($_FILES['logo']['type'] == 'image/jpeg' || $_FILES['logo']['type'] == 'image/png' || $_FILES['logo']['type'] == 'image/jpg') {
						
						$img_name = "logo".time();
						$ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
						$full_img_name = $img_name.'.'.$ext;
						$img_path = GLPI_PICTURE_DIR . '/' . $full_img_name;
						
						move_uploaded_file($_FILES['logo']['tmp_name'], $img_path);
						
						return $full_img_name;
						
					} else {
						Session::addMessageAfterRedirect('Wrong file type. Only .jpg and .png files accepted', 'WARNING', true);
					}
				} else {
					Session::addMessageAfterRedirect(__('Unknown error'), 'WARNING', true);
				}
			} else {
				Session::addMessageAfterRedirect('File size too large', 'WARNING', true);
			}
		}

	}
	
	static function deleteConfigs() {
		global $DB;
		
		$conf_id = $_POST['conf_id'];
		
		$DB->delete(
			'glpi_plugin_protocolsmanager_config', [
				'id' => $conf_id
			]
		);
		
	}
	
	
	static function deleteEmailConfigs() {
		global $DB;
		
		$email_conf_id = $_POST['email_conf_id'];
		
		$DB->delete(
			'glpi_plugin_protocolsmanager_emailconfig', [
				'id' => $email_conf_id
			]
		);	
		
	}

}

?>
