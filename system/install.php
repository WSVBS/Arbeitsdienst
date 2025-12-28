<?php
/**
 ***********************************************************************************************
 * Installationsroutine fuer das Admidio-Plugin Arbeitsdienst
 *
 * @copyright 2019 WSVBS
 * @see https://wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ************************************************************************************************
 */
use Admidio\Infrastructure\Entity\Entity;
use Admidio\Infrastructure\Utils\SecurityUtils;

require_once (__DIR__ . '/../../../system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/../classes/configtable.php');

$getStart = admFuncVariableIsValid($_GET, 'start', 'string');

//########################################################################
//# Prüfen, ob Einträge in den Tabellen tbl_categories und tbl_user_fields
//# vorhanden sind. Wenn nicht müssen diese angelegt werden
//########################################################################
// only authorized user are allowed to start this module
if (! $gCurrentUser->isAdministrator()) {
    throw new Exception('SYS_NO_RIGHTS');
}

$gNavigation->addStartUrl(CURRENT_URL);

// Kategorien erstellen, wenn noch nicht vorhanden
if (DBcategoriesID('PAD_ARBEITSDIENST') == 0) {
    // Kategorie "Arbeitsdienst" der Tabelle adm_categories hinzufügen
    $nextCatSequence = getNextCatSequencePAD('USF');
    $arr = array('cat_org_id' => $GLOBALS['gCurrentOrgId'],
                 'cat_type'   => 'USF',
                 'cat_name'   => 'PAD_ARBEITSDIENST',
                 'cat_name_intern' => 'ARBEITSDIENST',
                 'cat_system' => 0);
    setCategory($arr, $nextCatSequence);

}

// die nächsten beiden Zeilen müssen noch angepasst werden
$cat_id_arbeitsdienst = getCat_IDPAD('ARBEITSDIENST');
$nextFieldSequence = getNextFieldSequencePAD($cat_id_arbeitsdienst);

// USER Fields erstellen, wenn noch nicht vorhanden
if (DBuserfieldID('WORKPAID') == 0) {
    $arr = array('usf_name'   => 'PAD_PAID',
                 'usf_name_intern'   => 'WORKPAID',
                 'usf_type' => 'DATE',
                 'usf_system' => 0,
                 'usf_hidden' => 1,
                 'usf_required_input' => 0,
                 'usf_description' => $gL10n->get('PAD_PAID'));
    setUserField($cat_id_arbeitsdienst, $arr,  $nextFieldSequence);
}

if (DBuserfieldID('WORKFEE') == 0) {
    $arr = array('usf_name'   => 'PAD_FEE',
                 'usf_name_intern'   => 'WORKFEE',
                 'usf_type' => 'DECIMAL',
                 'usf_system' => 0,
                 'usf_hidden' => 1,
                 'usf_required_input' => 0,
                 'usf_description' => $gL10n->get('PAD_FEE'));
    setUserField($cat_id_arbeitsdienst, $arr,  $nextFieldSequence);
}

if (DBuserfieldID('WORKREFERENCE') == 0) {
    $arr = array('usf_name'   => 'PAD_REFERENCE',
                 'usf_name_intern'   => 'WORKREFERENCE',
                 'usf_type' => 'TEXT',
                 'usf_system' => 0,
                 'usf_hidden' => 1,
                 'usf_required_input' => 0,
                 'usf_description' => $gL10n->get('PAD_REFERENCE'));
    setUserField($cat_id_arbeitsdienst, $arr,  $nextFieldSequence);
}

if (DBuserfieldID('WORKSEQUENCETYPE') == 0) {
    $arr = array('usf_name'   => 'PAD_SEQUENCETYPE',
                 'usf_name_intern'   => 'WORKSEQUENCETYPE',
                 'usf_type' => 'TEXT',
                 'usf_system' => 0,
                 'usf_hidden' => 1,
                 'usf_required_input' => 0,
                 'usf_description' => $gL10n->get('PAD_SEQUENCETYPE'));
    setUserField($cat_id_arbeitsdienst, $arr,  $nextFieldSequence);
}

if (DBuserfieldID('WORKDUEDATE') == 0) {
    $arr = array('usf_name'   => 'PAD_DUEDATE',
                 'usf_name_intern'   => 'WORKDUEDATE',
                 'usf_type' => 'DATE',
                 'usf_system' => 0,
                 'usf_hidden' => 1,
                 'usf_required_input' => 0,
                 'usf_description' => $gL10n->get('PAD_DUEDATE'));
    setUserField($cat_id_arbeitsdienst, $arr,  $nextFieldSequence);
}

// weiterleiten zur Datei arbeitsdienst.php
//$testfolder = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php&start=run'; 
admRedirect(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php', array('start' => 'run')));
     

// Funktionen, die nur in diesem Script benoetigt werden
//übernommen aus dem Plugin Mitgliederbeitrag
function getNextCatSequencePAD($cat_type)
{
    global $gDb;

    $sql = 'SELECT cat_type, cat_sequence
                FROM ' . TBL_CATEGORIES . '
                WHERE cat_type = \'' . $cat_type . '\'
                AND (  cat_org_id  = ' . ORG_ID . '
                    OR cat_org_id IS NULL )
                ORDER BY cat_sequence ASC';

    $statement = $gDb->query($sql);

    while ($row = $statement->fetch()) {
        $sequence = $row['cat_sequence'];
    }
    return $sequence + 1;
}

/**
 * Erzeugt den naechsten freien Wert fuer usf_sequence
 * übernommen 
 *
 * @param int $usf_cat_id
 *            Cat_Id
 * @return int Der naechste freie Wert fuer usf_sequence
 */
function getNextFieldSequencePAD($usf_cat_id)
{
    $sequence = 0;

    $sql = 'SELECT usf_cat_id, usf_sequence
                FROM ' . TBL_USER_FIELDS . '
                WHERE usf_cat_id = \'' . $usf_cat_id . '\'
                ORDER BY usf_sequence ASC';

    $statement = $GLOBALS['gDb']->query($sql);

    while ($row = $statement->fetch()) {
        $sequence = $row['usf_sequence'];
    }
    return $sequence + 1;
}

/**
 * Erzeugt eine Kategorie in der Tabelle TBL_CATEGORIES
 * übernommen aus dem Plugin Mitgliederbeitrag
 *
 * @param array $arr
 *            Array mit Werten für die Spalten
 * @param int $sequence
 *            Neue Sequence der anzulegenden Kategorie
 * @return void
 */
function setCategory($arr, $sequence)
{
    // $newCategory = new TableAccess($GLOBALS['gDb'], TBL_CATEGORIES, 'cat');
    $newCategory = new Entity($GLOBALS['gDb'], TBL_CATEGORIES, 'cat');
    $newCategory->setValue('cat_sequence', $sequence);

    foreach ($arr as $key => $value) {
        $newCategory->setValue($key, $value);
    }

    $newCategory->save();
}

/**
 * Erzeugt ein Profilfeld in der Tabelle TBL_USER_FIELDS
 * übernommen aus dem Plugin Mitgliederbeitrag
 *
 * @param int $cat_id
 *            Cat_Id der Kategorie, in der das Profilfeld angelegt wird
 * @param array $arr
 *            Array mit Werten für die Spalten
 * @param int $sequence
 *            Neue Sequence des anzulegenden Profilfeldes
 * @return void
 */
function setUserField($cat_id, $arr, $sequence)
{
    // $newUserField = new TableAccess($GLOBALS['gDb'], TBL_USER_FIELDS, 'usf');
    $newUserField = new Entity($GLOBALS['gDb'], TBL_USER_FIELDS, 'usf');
    $newUserField->setValue('usf_cat_id', $cat_id);
    $newUserField->setValue('usf_sequence', $sequence);

    foreach ($arr as $key => $value) {
        $newUserField->setValue($key, $value);
    }

    $newUserField->save();
}  