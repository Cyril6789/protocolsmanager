<?php
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

require_once dirname(__DIR__) . '/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/** Procès-verbaux de mise en service, distincts des inventaires utilisateurs. */
class PluginProtocolsmanagerCommission extends CommonDBTM
{
	private const ITEM_TYPES = [
		'Computer', 'Monitor', 'Printer', 'Peripheral', 'Phone', 'NetworkEquipment'
	];

	public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
	{
		if (version_compare(GLPI_VERSION, '12.0.0', '>=')) {
			return self::createTabEntry(__('Procès-verbal de mise en service', 'protocolsmanager'), icon: 'ti ti-file-check');
		}
		return self::createTabEntry(__('Procès-verbal de mise en service', 'protocolsmanager'));
	}

	public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
	{
		if (!self::hasAccess()) {
			echo "<div class='alert alert-warning'>" . htmlescape(__('Accès refusé')) . '</div>';
			return true;
		}
		if (!Session::haveRight('document', CREATE)) {
			echo "<div class='alert alert-warning'>" . htmlescape(__('Vous n’avez pas le droit de créer des documents GLPI.')) . '</div>';
			return true;
		}
		self::renderCommissionForm($item);
		return true;
	}

	private static function hasAccess(): bool
	{
		global $DB;
		$profile_id = (int)($_SESSION['glpiactiveprofile']['id'] ?? 0);
		if ($profile_id <= 0 || !$DB->tableExists('glpi_plugin_protocolsmanager_profiles')) {
			return false;
		}
		$result = $DB->request([
			'FROM' => 'glpi_plugin_protocolsmanager_profiles',
			'WHERE' => ['profile_id' => $profile_id],
		]);
		$row = $result->current();
		return $row && ($row['tab_access'] ?? '') === 'w';
	}

	private static function renderCommissionForm(CommonGLPI $item): void
	{
		global $CFG_GLPI, $DB;
		$itemtype = get_class($item);
		$item_id = (int)$item->getID();
		if (!in_array($itemtype, self::ITEM_TYPES, true) || $item_id <= 0 || !$item->can($item_id, READ)) {
			echo "<div class='alert alert-warning'>" . htmlescape(__('Accès refusé')) . '</div>';
			return;
		}

		$seed = self::loadAsset($itemtype, $item_id);
		if (!$seed || $seed['immo_number'] === '') {
			echo "<div class='alert alert-info'>" . htmlescape(__('Aucun numéro immo n’est renseigné pour ce matériel.', 'protocolsmanager')) . '</div>';
			return;
		}
		$group = self::findAssetGroup($seed['immo_number']);
		if ($group === null) {
			echo "<div class='alert alert-warning'>" . htmlescape(__('Au moins un matériel portant ce numéro immo n’est pas accessible. Le PV groupé ne peut pas être généré.', 'protocolsmanager')) . '</div>';
			return;
		}
		if ($group === []) {
			echo "<div class='alert alert-warning'>" . htmlescape(__('Aucun matériel n’a été trouvé pour ce numéro immo.', 'protocolsmanager')) . '</div>';
			return;
		}

		echo "<div class='spaced'><h3>" . htmlescape(__('Procès-verbal de mise en service', 'protocolsmanager')) . '</h3>';
		echo "<form method='post' action='" . htmlescape($CFG_GLPI['root_doc'] . '/plugins/protocolsmanager/front/commission.form.php') . "'>";
		if (method_exists(Session::class, 'getNewCSRFToken')) {
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
		}
		echo Html::hidden('immo_number', ['value' => $seed['immo_number']]);
		echo "<table class='tab_cadre_fixe'><tr><th colspan='3'>" . htmlescape(__('Matériels portant le numéro immo', 'protocolsmanager')) . ' ' . htmlescape($seed['immo_number']) . '</th></tr>';
		echo '<tr><th>' . htmlescape(__('Matériel', 'protocolsmanager')) . '</th><th>' . htmlescape(__('Numéro d’inventaire', 'protocolsmanager')) . '</th><th>' . htmlescape(__('Numéro de série', 'protocolsmanager')) . '</th></tr>';
		foreach ($group as $asset) {
			$key = $asset['itemtype'] . ':' . $asset['id'];
			echo '<tr><td><label><input type="checkbox" name="items[]" value="' . htmlescape($key) . '" checked> ';
			echo htmlescape($asset['type'] . ' — ' . ($asset['name'] !== '' ? $asset['name'] : '#' . $asset['id'])) . '</label></td>';
			echo '<td>' . htmlescape($asset['inventory_number']) . '</td><td>' . htmlescape($asset['serial']) . '</td></tr>';
		}
		echo "<tr><td colspan='3'><label>" . htmlescape(__('Modèle PDF (logo et couleurs)', 'protocolsmanager')) . " <select name='config_id'>";
		$configs = $DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'glpi_plugin_protocolsmanager_config', 'ORDER' => ['id ASC']]);
		foreach ($configs as $config) {
			echo '<option value="' . htmlescape($config['id']) . '">' . htmlescape($config['name'] ?? '') . '</option>';
		}
		echo '</select></label></td></tr>';
		$effective_date = self::commonPurchaseDate($group);
		echo "<tr><td colspan='3'><label>" . htmlescape(__('Date effective de mise en service', 'protocolsmanager')) . " <input type='date' name='effective_date' value='" . htmlescape($effective_date) . "'></label></td></tr>";
		echo "<tr><td colspan='3'><strong>" . htmlescape(__('Je soussigné(e) certifie que l’immobilisation décrite ci-dessous a été :', 'protocolsmanager')) . '</strong><br>';
		$states = [
			'installation' => __('Installée', 'protocolsmanager'),
			'tested' => __('Testée', 'protocolsmanager'),
			'accepted' => __('Réceptionnée conforme', 'protocolsmanager'),
			'operational' => __('Mise en exploitation', 'protocolsmanager'),
		];
		foreach ($states as $key => $label) {
			echo '<label style="margin-right:1em"><input type="checkbox" name="states[]" value="' . htmlescape($key) . '" checked> ' . htmlescape($label) . '</label>';
		}
		echo '</td></tr>';
		echo "<tr><td colspan='3'><label>" . htmlescape(__('Description de l’utilisation prévue', 'protocolsmanager')) . "<br><textarea name='planned_use' rows='3' style='width:100%'></textarea></label></td></tr>";
		echo "<tr><td colspan='3'><label>" . htmlescape(__('Observations éventuelles', 'protocolsmanager')) . "<br><textarea name='observations' rows='5' style='width:100%'></textarea></label></td></tr>";
		echo "<tr><td colspan='3'><strong>" . htmlescape(__('Lieu d’établissement du document', 'protocolsmanager')) . '</strong> ';
		foreach (['Eichhoffen', 'Barr'] as $city) {
			echo '<label style="margin-right:1em"><input type="radio" name="city" value="' . htmlescape($city) . '"' . ($city === 'Eichhoffen' ? ' checked' : '') . '> ' . htmlescape($city) . '</label>';
		}
		echo "</td></tr><tr><td colspan='3'><button class='submit' type='submit' name='generate_commission' value='1'>" . htmlescape(__('Générer le PDF', 'protocolsmanager')) . '</button></td></tr></table>';
		echo '</form></div>';
	}

	private static function loadAsset(string $itemtype, int $id): ?array
	{
		global $DB;
		if (!in_array($itemtype, self::ITEM_TYPES, true)) {
			return null;
		}
		$item = getItemForItemtype($itemtype);
		if (!$item || !$item->getFromDB($id) || !$item->can($id, READ)) {
			return null;
		}
		$table = getTableForItemType($itemtype);
		$asset_result = $DB->request(['FROM' => $table, 'WHERE' => ['id' => $id]]);
		$fields = $asset_result->current();
		if (!$fields) {
			return null;
		}
		$info_result = $DB->request(['FROM' => 'glpi_infocoms', 'WHERE' => ['itemtype' => $itemtype, 'items_id' => $id]]);
		$info = $info_result->current() ?: [];
		$location = self::locationName((int)($fields['locations_id'] ?? 0));
		$supplier = self::supplierName((int)($info['suppliers_id'] ?? 0));
		return [
			'id' => $id,
			'itemtype' => $itemtype,
			'type' => $item->getTypeName(),
			'name' => (string)($fields['name'] ?? ''),
			'immo_number' => trim((string)($info['immo_number'] ?? '')),
			'inventory_number' => (string)($fields['otherserial'] ?? ''),
			'serial' => (string)($fields['serial'] ?? ''),
			'supplier' => $supplier,
			'buy_date' => (string)($info['buy_date'] ?? ''),
			'bill' => (string)($info['bill'] ?? ''),
			'value' => $info['value'] ?? null,
			'location' => $location,
		];
	}

	private static function findAssetGroup(string $immo_number): ?array
	{
		global $DB;
		$group = [];
		foreach (self::ITEM_TYPES as $itemtype) {
			$rows = $DB->request([
				'FROM' => 'glpi_infocoms',
				'WHERE' => ['itemtype' => $itemtype, 'immo_number' => $immo_number],
			]);
			foreach ($rows as $info) {
				$item = getItemForItemtype($itemtype);
				$item_id = (int)$info['items_id'];
				if (!$item || !$item->getFromDB($item_id)) {
					continue;
				}
				if (!$item->can($item_id, READ)) {
					return null;
				}
				$asset = self::loadAsset($itemtype, $item_id);
				if ($asset && $asset['immo_number'] === $immo_number) {
					$group[] = $asset;
				}
			}
		}
		return $group;
	}

	private static function commonPurchaseDate(array $assets): string
	{
		$dates = array_map(static fn($asset) => $asset['buy_date'], $assets);
		if ($dates === [] || in_array('', $dates, true) || count(array_unique($dates)) !== 1) {
			return '';
		}
		return $dates[0];
	}

	private static function displayDate(string $date): string
	{
		if ($date === '') {
			return '';
		}
		$timestamp = strtotime($date);
		return $timestamp === false ? '' : date('d/m/Y', $timestamp);
	}

	private static function locationName(int $location_id): string
	{
		if ($location_id <= 0) {
			return '';
		}
		$location = new Location();
		if (!$location->getFromDB($location_id)) {
			return '';
		}
		$name = (string)($location->fields['name'] ?? '');
		$parent_id = (int)($location->fields['locations_id'] ?? 0);
		$seen = [$location_id => true];
		while ($parent_id > 0 && !isset($seen[$parent_id])) {
			$seen[$parent_id] = true;
			$parent = new Location();
			if (!$parent->getFromDB($parent_id)) {
				break;
			}
			$name = (string)($parent->fields['name'] ?? $name);
			$parent_id = (int)($parent->fields['locations_id'] ?? 0);
		}
		return $name;
	}

	private static function supplierName(int $supplier_id): string
	{
		if ($supplier_id <= 0) {
			return '';
		}
		$supplier = new Supplier();
		return $supplier->getFromDB($supplier_id) ? (string)($supplier->fields['name'] ?? '') : '';
	}

	public static function generateFromRequest(): bool
	{
		global $DB;
		if (!self::hasAccess()) {
			Session::addMessageAfterRedirect(__('Accès refusé'), false, ERROR);
			return false;
		}
		if (!Session::haveRight('document', CREATE)) {
			Session::addMessageAfterRedirect(__('Vous n’avez pas le droit de créer des documents GLPI.'), false, ERROR);
			return false;
		}
		$tokens = $_POST['items'] ?? [];
		if (!is_array($tokens) || $tokens === []) {
			Session::addMessageAfterRedirect(__('Sélectionnez au moins un matériel.', 'protocolsmanager'), false, ERROR);
			return false;
		}
		$immo = trim((string)($_POST['immo_number'] ?? ''));
		$assets = [];
		foreach ($tokens as $token) {
			if (!is_string($token) || !preg_match('/^([A-Za-z][A-Za-z0-9]*)\:(\d+)$/', $token, $matches)) {
				continue;
			}
			$asset = self::loadAsset($matches[1], (int)$matches[2]);
			if (!$asset || $asset['immo_number'] === '' || $asset['immo_number'] !== $immo) {
				Session::addMessageAfterRedirect(__('Les matériels sélectionnés doivent être lisibles et porter le même numéro immo.', 'protocolsmanager'), false, ERROR);
				return false;
			}
			$assets[$asset['itemtype'] . ':' . $asset['id']] = $asset;
		}
		if ($assets === []) {
			Session::addMessageAfterRedirect(__('Aucun matériel valide n’a été sélectionné.', 'protocolsmanager'), false, ERROR);
			return false;
		}
		$submitted_date = (string)($_POST['effective_date'] ?? '');
		$effective_date = '';
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $submitted_date, $date_parts)
			&& checkdate((int)$date_parts[2], (int)$date_parts[3], (int)$date_parts[1])) {
			$effective_date = $submitted_date;
		}
		$states = array_intersect(['installation', 'tested', 'accepted', 'operational'], (array)($_POST['states'] ?? []));
		$city = in_array($_POST['city'] ?? '', ['Eichhoffen', 'Barr'], true) ? $_POST['city'] : 'Eichhoffen';
		$planned_use = self::cleanText($_POST['planned_use'] ?? '', 5000);
		$observations = self::cleanText($_POST['observations'] ?? '', 10000);
		$config_id = max(0, (int)($_POST['config_id'] ?? 0));
		return self::createPdf(array_values($assets), $immo, $effective_date, $states, $city, $planned_use, $observations, $config_id);
	}

	private static function cleanText($value, int $limit): string
	{
		if (!is_string($value)) {
			return '';
		}
		$value = strip_tags($value);
		return mb_substr(trim($value), 0, $limit);
	}

	private static function createPdf(array $assets, string $immo, string $effective_date, array $states, string $city, string $planned_use, string $observations, int $config_id): bool
	{
		global $DB;
		$config_query = $config_id > 0
			? $DB->request(['FROM' => 'glpi_plugin_protocolsmanager_config', 'WHERE' => ['id' => $config_id]])
			: $DB->request(['FROM' => 'glpi_plugin_protocolsmanager_config', 'ORDER' => ['id ASC'], 'LIMIT' => 1]);
		$config = $config_query->current() ?: [];
		$accent = $config['theme_color'] ?? '#8A6428';
		$header_text_color = $config['header_text_color'] ?? '#FFFFFF';
		if (!is_string($accent) || !preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
			$accent = '#8A6428';
		}
		if (!is_string($header_text_color) || !preg_match('/^#[0-9a-f]{6}$/i', $header_text_color)) {
			$header_text_color = '#FFFFFF';
		}
		$logo_path = !empty($config['logo']) ? GLPI_PICTURE_DIR . '/' . basename($config['logo']) : '';
		$logo_html = '';
		$logo_width = isset($config['logo_width']) && (int)$config['logo_width'] > 0 ? (int)$config['logo_width'] : 160;
		$logo_height = isset($config['logo_height']) && (int)$config['logo_height'] > 0 ? (int)$config['logo_height'] : 160;
		if ($logo_path !== '' && is_file($logo_path) && is_readable($logo_path)) {
			$mime = function_exists('mime_content_type') ? mime_content_type($logo_path) : 'image/png';
			if (!is_string($mime) || !in_array($mime, ['image/png', 'image/jpeg'], true)) {
				$mime = 'image/png';
			}
			$data = base64_encode((string)file_get_contents($logo_path));
			$logo_html = '<img class="logo" style="max-width:' . $logo_width . 'px;max-height:' . $logo_height . 'px" src="data:' . htmlescape($mime) . ';base64,' . $data . '" alt="">';
		}
		$user = new User();
		$user_id = (int)Session::getLoginUserID();
		$user->getFromDB($user_id);
		$signer = trim((string)($user->fields['firstname'] ?? '') . ' ' . (string)($user->fields['realname'] ?? ''));
		$purchase_total = 0.0;
		$all_amounts_present = true;
		foreach ($assets as $asset) {
			if ($asset['value'] === null || $asset['value'] === '') {
				$all_amounts_present = false;
			} else {
				$purchase_total += (float)$asset['value'];
			}
		}
		$state_labels = [
			'installation' => __('Installée', 'protocolsmanager'),
			'tested' => __('Testée', 'protocolsmanager'),
			'accepted' => __('Réceptionnée conforme', 'protocolsmanager'),
			'operational' => __('Mise en exploitation', 'protocolsmanager'),
		];
		$escape = static fn($value) => htmlescape((string)$value);
		ob_start();
?>
<!doctype html><html><head><meta charset="utf-8"><style>
@page { margin: 20mm 16mm 18mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
h1 { color: <?= $escape($accent) ?>; text-align:center; font-size:17pt; margin: 0 0 5mm; }
h2 { color:<?= $escape($header_text_color) ?>; background:<?= $escape($accent) ?>; padding:6px 8px; font-size:11pt; margin:7mm 0 3mm; }
.logo { display:block; margin:0 auto 5mm; }
.immo { text-align:center; font-size:12pt; font-weight:bold; color:<?= $escape($accent) ?>; margin-bottom:5mm; }
table { width:100%; border-collapse:collapse; margin:2mm 0 4mm; }
th,td { border:1px solid #999; padding:5px; vertical-align:top; }
th { background:#f1f1f1; text-align:left; }
.asset { page-break-inside:avoid; margin-bottom:4mm; }
.field { width:28%; font-weight:bold; }
.signature td { height:18mm; }
.free { white-space:pre-wrap; min-height:9mm; }
</style></head><body>
<?= $logo_html ?>
<h1><?= $escape(__('Procès-verbal de mise en service d’une immobilisation', 'protocolsmanager')) ?></h1>
<div class="immo"><?= $escape(__('Numéro immo', 'protocolsmanager')) ?> : <?= $escape($immo) ?></div>
<h2>1. <?= $escape(__('Identification de l’immobilisation', 'protocolsmanager')) ?></h2>
<?php foreach ($assets as $asset): ?>
<div class="asset"><table>
<tr><th colspan="2"><?= $escape($asset['name']) ?> (<?= $escape($asset['type']) ?>)</th></tr>
<?php
$rows = [
	__('Numéro immo', 'protocolsmanager') => $asset['immo_number'],
	__('Désignation', 'protocolsmanager') => trim($asset['name'] . ' (' . $asset['type'] . ')'),
	__('Numéro d’inventaire', 'protocolsmanager') => $asset['inventory_number'],
	__('Numéro de série', 'protocolsmanager') => $asset['serial'],
	__('Fournisseur', 'protocolsmanager') => $asset['supplier'],
	__('Date d’acquisition', 'protocolsmanager') => self::displayDate($asset['buy_date']),
	__('Numéro de facture', 'protocolsmanager') => $asset['bill'],
	__('Valeur d’acquisition HT', 'protocolsmanager') => $asset['value'] === null || $asset['value'] === '' ? '' : number_format((float)$asset['value'], 2, ',', ' ') . ' €',
	__('Site d’affectation', 'protocolsmanager') => $asset['location'],
];
foreach ($rows as $label => $value): if ((string)$value === '') { continue; } ?>
<tr><td class="field"><?= $escape($label) ?></td><td><?= $escape($value) ?></td></tr>
<?php endforeach; ?></table></div>
<?php endforeach; ?>
<?php if (count($assets) > 1 && $all_amounts_present): ?><p><strong><?= $escape(__('Montant total d’acquisition HT', 'protocolsmanager')) ?> : <?= $escape(number_format($purchase_total, 2, ',', ' ')) ?> €</strong></p><?php endif; ?>
<h2>2. <?= $escape(__('Mise en service', 'protocolsmanager')) ?></h2>
<p><?= $escape(__('Je soussigné(e)', 'protocolsmanager')) ?> <strong><?= $escape($signer) ?></strong>, <?= $escape(__('certifie que l’immobilisation décrite ci-dessous a été :', 'protocolsmanager')) ?></p>
<p><?php foreach ($state_labels as $key => $label): ?><?= in_array($key, $states, true) ? '☒' : '☐' ?> <?= $escape($label) ?>&nbsp;&nbsp;<?php endforeach; ?></p>
<p><strong><?= $escape(__('Date effective de mise en service', 'protocolsmanager')) ?> :</strong> <?= $escape(self::displayDate($effective_date)) ?></p>
<p><strong><?= $escape(__('Description de l’utilisation prévue', 'protocolsmanager')) ?> :</strong></p><div class="free"><?= $escape($planned_use) ?></div>
<h2>3. <?= $escape(__('Observations éventuelles', 'protocolsmanager')) ?></h2><div class="free"><?= $escape($observations) ?></div>
<h2>4. <?= $escape(__('Validations', 'protocolsmanager')) ?></h2>
<table class="signature"><tr><th><?= $escape(__('Fonction', 'protocolsmanager')) ?></th><th><?= $escape(__('Nom', 'protocolsmanager')) ?></th><th><?= $escape(__('Signature', 'protocolsmanager')) ?></th><th><?= $escape(__('Date', 'protocolsmanager')) ?></th></tr>
<?php foreach ([__('Responsable utilisateur', 'protocolsmanager'), __('Responsable technique (si applicable)', 'protocolsmanager'), __('Comptabilité', 'protocolsmanager'), __('Direction / Responsable hiérarchique', 'protocolsmanager')] as $role): ?><tr><td><?= $escape($role) ?></td><td></td><td></td><td></td></tr><?php endforeach; ?></table>
<p><?= $escape(__('Document établi le', 'protocolsmanager')) ?> : <?= $escape(date('d/m/Y')) ?>, <?= $escape(__('lieu', 'protocolsmanager')) ?> <?= $escape($city) ?></p>
</body></html>
<?php
		$html = ob_get_clean();
		$options = new Options();
		$options->set('defaultFont', 'DejaVu Sans');
		$pdf = new Dompdf($options);
		$pdf->loadHtml($html, 'UTF-8');
		$pdf->setPaper('A4', 'portrait');
		$pdf->render();
		$filename = 'PV_mise_en_service_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $immo) . '_' . date('Ymd_His') . '.pdf';
		$path = GLPI_UPLOAD_DIR . '/' . $filename;
		if (file_put_contents($path, $pdf->output()) === false) {
			Session::addMessageAfterRedirect(__('Le PDF n’a pas pu être enregistré.', 'protocolsmanager'), false, ERROR);
			return false;
		}
		$document = new Document();
		$document_id = $document->add([
			'name' => $filename,
			'upload_file' => $filename,
			'documentcategories_id' => 0,
			'mime' => 'application/pdf',
			'users_id' => $user_id,
			'entities_id' => Session::getActiveEntity(),
			'comment' => __('Procès-verbal de mise en service', 'protocolsmanager') . ' — ' . $immo,
		]);
		if (!$document_id) {
			@unlink($path);
			Session::addMessageAfterRedirect(__('Le document PDF n’a pas pu être enregistré dans GLPI.', 'protocolsmanager'), false, ERROR);
			return false;
		}
		foreach ($assets as $asset) {
			$link = new Document_Item();
			$link->add(['documents_id' => $document_id, 'itemtype' => $asset['itemtype'], 'items_id' => $asset['id']]);
		}
		Session::addMessageAfterRedirect(__('Le procès-verbal de mise en service a été généré.', 'protocolsmanager'), true, INFO);
		return true;
	}
}
