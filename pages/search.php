<?php
$csrfToken = rex_csrf_token::factory('fr_csvimport');

function getAddonInfo($jsonString, $addonName) {
    // JSON-String in ein assoziatives Array umwandeln
    $data = json_decode($jsonString, true);

    // Prüfen, ob das Feld 'rex_addons' existiert
    if (isset($data['rex_addons']) && is_array($data['rex_addons'])) {
        // Nach dem gewünschten Addon suchen
        if (isset($data['rex_addons'][$addonName])) {
            // Informationen des Addons zurückgeben
            return $data['rex_addons'][$addonName];
        }
    }

    // Wenn das Addon nicht gefunden wurde, eine entsprechende Nachricht zurückgeben
    return null;
}

function getDomainName($domainId) {
    // rex_sql verwenden, um die Domain anhand der domain_id abzurufen
    $sql = rex_sql::factory();
    $domain = $sql->setQuery('SELECT `domain` FROM `rex_project_manager_domain` WHERE `id` = ?', [$domainId])->getArray();

    // Wenn eine Domain gefunden wurde, zurückgeben
    if (!empty($domain)) {
        return $domain[0]['domain'];
    }

    // Wenn keine Domain gefunden wurde, eine Nachricht zurückgeben
    return 'Unbekannte Domain';
}

if (rex_request('searchquery', 'string')) {

    if (!$csrfToken->isValid()) {
        echo rex_view::error("Ein Fehler ist aufgetreten. Bitte wenden Sie sich an den Webmaster.");
        return;
    }

    $searchquery = rex_request('searchquery', 'string', '');
    //dd(get_defined_vars());

    // Addon, nach dem gesucht werden soll
    $addonName = $searchquery;

    // rex_sql verwenden, um die Einträge aus der Tabelle zu holen
    $sql = rex_sql::factory();
    $entries = $sql->setQuery('SELECT `id`, `raw`, `domain_id` FROM `rex_project_manager_logs`')->getArray();

    // Bootstrap-Tabelle erstellen
    echo '<table class="table table-striped table-bordered">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Domain</th>';
    echo '<th>Addon-Name</th>';
    echo '<th>Installiert</th>';
    echo '<th>Status</th>';
    echo '<th>Aktuelle Version</th>';
    echo '<th>Neueste Version</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($entries as $entry) {
        $id = $entry['id'];
        $raw = $entry['raw'];
        $domainId = $entry['domain_id'];

        // Domainnamen abrufen
        $domainName = getDomainName($domainId);

        // Die Funktion getAddonInfo ausführen
        $result = getAddonInfo($raw, $addonName);


        if ($result) {
            $currentVersion = htmlspecialchars($result['version_current']);
            $latestVersion = htmlspecialchars($result['version_latest']);

            // Bedingung für die rote Hervorhebung
            $versionClass = ($currentVersion !== $latestVersion) ? 'text-danger' : '';

            echo '<tr class="">';
            echo '<td>' . htmlspecialchars($id) . '</td>';
            echo '<td>' . htmlspecialchars($domainName) . '</td>';
            echo '<td>' . htmlspecialchars($result['name']) . '</td>';
            echo '<td>' . ($result['install'] ? 'Ja' : 'Nein') . '</td>';
            echo '<td>' . ($result['status'] ? 'Aktiv' : 'Inaktiv') . '</td>';
            echo '<td class="' . $versionClass . '">' . $currentVersion . '</td>';
            echo '<td>' . $latestVersion . '</td>';
            echo '</tr>';
        } else {
            echo '<tr class="mark">';
            echo '<td>' . htmlspecialchars($id) . '</td>';
            echo '<td>' . htmlspecialchars($domainName) . '</td>';
            echo '<td colspan="5">Addon \'' . htmlspecialchars($addonName) . '\' nicht gefunden.</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';

    return;
}


$sContent = '<div class="container-fluid">';
$sContent .= '<div class="row">';
$sContent .= '<div class="col-12"><strong>Suchbegriff: </strong><br><input class="form-control" type="text" name="searchquery" required="required"></div>';
$sContent .= '</div><br>';
$sContent .= '</div>';

$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save" type="submit" name="import" value="1">Addon suchen</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
' . $buttons . '
</fieldset>
';

$fragment = new rex_fragment();
$fragment->setVar("class", "edit");
$fragment->setVar('title', 'Suche', false);
$fragment->setVar('body', $sContent, false);
$fragment->setVar("buttons", $buttons, false);
$output = $fragment->parse('core/page/section.php');

$output = '<form action="' . rex_url::currentBackendPage() . '" method="post" enctype="multipart/form-data">'
    . $csrfToken->getHiddenField()
    . $output
    . '</form>';

echo $output;
