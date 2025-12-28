<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin Arbeitsdienst
 *
 * @copyright 2018-2023 WSVBS
 * @see https://wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Hinweis: Funktionen vom Plugin Mitgliederbeitrag übernommen
 * 
 ***********************************************************************************************
 */

 use Admidio\Components\Entity\Component;
use Admidio\Infrastructure\Database;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Roles\Entity\Role;
use Admidio\Roles\Entity\RolesRights;
use Admidio\Users\Entity\User;
use Plugins\MembershipFee\classes\Config\ConfigTable;
use Smarty\Data;

require_once (__DIR__ . '/../../../system/common.php');
//require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/../classes/configtable.php');


if(!defined('PLUGIN_FOLDER'))
{
    define('PLUGIN_FOLDER', '/'.substr(dirname(__DIR__),strrpos(dirname(__DIR__),DIRECTORY_SEPARATOR)+1));
}


if (! defined('ORG_ID')) {
    define('ORG_ID', (int) $gCurrentOrganization->getValue('org_id'));
}

/**
 * Übernommen vom Plugin Mitgliederbeitrag
 * 
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin auszuführen.
 * 
 * In Admidio im Modul Menü kann über 'Sichtbar für' die Sichtbarkeit eines Menüpunkts eingeschränkt werden.
 * Der Zugriff auf die darunter liegende Seite ist von dieser Berechtigung jedoch nicht betroffen.
 * 
 * Mit Admidio 5 werden alle Startcripte meiner Plugins umbenannt zu index.php
 * Um die index.php auszuführen, kann die bei einem Menüpunkt angegebene URL wie folgt angegeben sein:
 * /adm_plugins/<Installationsordner des Plugins>
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/<Dateiname.php>
 * 
 * Das Installationsscript des Plugins erstellt automatisch einen Menüpunkt in der Form: /adm_plugins/<Installationsordner des Plugins>/index.php
 * Standardmäßig wird deshalb für die Prüfung index.php als <Dateiname.php> verwendet, alternativ die übergebene Datei ($scriptname).
 * 
 * Diese Funktion ermittelt nur die Menüpunkte, die einen Dateinamen am Ende (index.php oder $scriptname) aufweisen, liest bei diesen Menüpunkten
 * die unter 'Sichtbar für' eingetragenen Rollen ein und prüft, ob der angemeldete Benutzer Mitglied mindestens einer dieser Rollen ist.
 * Wenn ja, ist der Benutzer berechtigt, das Plugin auszuführen (auch, wenn es weitere Menüpunkte ohne Dateinamen am Ende gibt).
 * Wichtiger Hinweis: Sind unter 'Sichtbar für' keine Rollen angegeben, so darf jeder Benutzer das Plugin ausführen
 * 
 * @param   string  $scriptName   Der Scriptname des Plugins (default: 'index.php')
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized( string $scriptname = '')
{
    global $gDb, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menIds = array();
    
    $menuItemURL = FOLDER_PLUGINS. PLUGIN_FOLDER. '/'. ((strlen($scriptname) === 0) ? 'index.php' : $scriptname);
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $menuItemURL';
    
    $menuStatement = $gDb->queryPrepared($sql, array($menuItemURL));
    
    if ( $menuStatement->rowCount() !== 0 )
    {
        while ($row = $menuStatement->fetch())
        {
            $menIds[] = (int) $row['men_id'];
        }
        
        foreach ($menIds as $menId)
        {
            // read current roles rights of the menu
            $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
            
            // check for right to show the menu
            if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
            {
                $userIsAuthorized = true;
            }
        }
    }
    return $userIsAuthorized;
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur PrÃ¼fung werden die Einstellungen von 'Modulrechte' und 'Sichtbar fÃ¼r'
 * verwendet, die im Modul MenÃ¼ fÃ¼r dieses Plugin gesetzt wurden.
 *
 * @param string $scriptName
 *            Der Scriptname des Plugins
 * @return bool true, wenn der User berechtigt ist
 */


/**
 * Funktion prueft, ob der Nutzer berechtigt ist, das Modul Preferences aufzurufen.
 * @param   none
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorizedForPreferences()
{
    global $gCurrentUser, $pPreferences;
    
    $userIsAuthorized = false;
    
    if ($gCurrentUser->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($gCurrentUser->isMemberOfRole((int) $roleId))
            {
                $userIsAuthorized = true;
                continue;
            }
        }
    }
    
    return $userIsAuthorized;
}

function getdatefilter()
{
    // gibt ein Array mit mÃ¶glichen Abrechnungsjahren zurÃ¼ck
    // @param: keine
    // @return: Abrechnungsjahr als array
    $filteryear = array();
    $filteryear['1'] = strval(date('Y') - 2);
    $filteryear['2'] = strval(date('Y') - 1);
    $filteryear['3'] = date('Y');

    $datefilter = $filteryear;

    return $datefilter;
}

// die Funktion gibt die Summe aller im Abrechnungsjahr geleisteten
// Arbeitsstunden wieder
// @param: $workingyear Abrechnungsjahr
// @return: Summe des Abrechnungsjahres in Stunden
function getsumallworkinghours($workingyear)
{
    global $gDb;

    $sql = "SELECT sum(pad_hours) as sumall FROM adm_user_arbeitsdienst
            WHERE YEAR(pad_date) = '" . $workingyear . "'";

    // $result = array();
    $resultStatement = $gDb->query($sql);
    $sumall = $resultStatement->fetch(PDO::FETCH_ASSOC);

    // $result = 615 in 2017;
    return $sumall['sumall'];
}

// die Funktion gibt die Summe aller im Abrechnungsjahr geleisteten
// Arbeitsstunden eines Mitglieds wieder
// @param: $workingyear Abrechnungsjahr
// $usr_id ID des Abgefragten Mitglieds
// @return: Summe des Abrechnungsjahres in Stunden
function getsumworkinghours($workingyear, $usr_id)
{
    global $gDb;

    $sql = "SELECT sum(pad_hours) as sumall FROM adm_user_arbeitsdienst
            WHERE YEAR(pad_date) = '" . $workingyear . "'
            AND pad_user_id = '" . $usr_id . "'";

    // $result = array();
    $resultStatement = $gDb->query($sql);
    $sumall = $resultStatement->fetch(PDO::FETCH_ASSOC);
    if (isset($sumall['sumall']) && strlen($sumall['sumall']) > 0) {
        return $sumall['sumall'];
    } else {
        return 0;
    }

    // return $sumall['sumall'];
}

function allerollen_einlesen($rollenwahl = '', $with_members = array())
{
    global $gDb, $pPreferences;
    $rollen = array();

    // alle Rollen einlesen
    $sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_timestamp_create, rol_description
            FROM ' . TBL_ROLES . ', ' . TBL_CATEGORIES . '
            WHERE rol_valid  = 1
            AND rol_cat_id = cat_id
            AND (  cat_org_id = ' . ORG_ID . '
                OR cat_org_id IS NULL ) ';

    $statement = $gDb->query($sql);

    while ($row = $statement->fetch()) {
        $rollen[$row['rol_id']] = array(
            'rolle' => $row['rol_name'],
            'rol_cost' => $row['rol_cost'],
            'rol_cost_period' => $row['rol_cost_period'],
            'rol_timestamp_create' => $row['rol_timestamp_create'],
            'rol_description' => $row['rol_description'],
            'von' => 0,
            'bis' => 0,
            'rollentyp' => ''
        );
    }
    return $rollen;
}

/*
 * Diese Funktion liefert als Rueckgabe die usr_ids von Rollenangehoerigen.<br/>
 * moegliche Aufrufe:<br/>
 * list_members(array('usf_name_intern1','usf_name_intern2'), array('Rollenname1' => Schalter aktiv/ehem) )<br/>
 * oder list_members(array('usf_name_intern1','usf_name_intern2'), 'Rollenname' )<br/>
 * oder list_members(array('usf_name_intern1','usf_name_intern2'), Schalter aktiv/ehem )<br/>
 * oder list_members(array('p1','p2'), Schalter aktiv/ehem )<br/>
 *
 * Schalter aktiv/ehem: 0 = aktive Mitglieder, 1 = ehemalige Mitglieder, ungleich 1 oder 0: alle Mitglieder <br/>
 *
 * Aufruf: z.B. list_members(array('FIRST_NAME','LAST_NAME'), array('Mitglied' => 0,'Administrator' => 0));
 *
 * @param array $fields Array mit usf_name_intern oder p+usfID, z.B. array('FIRST_NAME','p2')
 * @param $calculationyear Jahr, von der dem die Abrechnung gemacht wird
 * @param array/string/bool $rols Array mit Rollen, z.B. <br/>
 * array('Rollenname1' => Schalter aktiv/ehem) )<br/>
 * oder 'Rollenname' <br/>
 * oder Schalter aktiv/ehem <br/>
 * @param string $conditions SQL-String als zusaetzlicher Filter von $members, z.B. 'AND usd_usf_id = 78'
 * @return array $members
 */
function list_members($calculationyear, $fields, $rols = array(), $conditions = '')
{
    global $gDb, $gProfileFields;

    $members = array();
    $calculationdate = date('Y-m-d', strtotime($calculationyear . '-12-31'));

    $sql = 'SELECT DISTINCT mem_usr_id, mem_begin, mem_end
            FROM ' . TBL_MEMBERS . ', ' . TBL_ROLES . ', ' . TBL_CATEGORIES . ' ';

    if (is_string($rols)) {
        $sql .= ' WHERE mem_rol_id = ' . getRole_IDPAD($rols) . ' ';
    } elseif (is_int($rols) && ($rols == 0)) {
        // nur aktive Mitglieder
        $sql .= ' WHERE mem_begin <= \'' . $calculationdate . '\' ';
        $sql .= ' AND mem_end >= \'' . $calculationdate . '\' ';
    } elseif (is_int($rols) && ($rols == 1)) {
        // nicht-aktive Mitglieder ALT:nur ehemalige Mitglieder
        $sql .= ' WHERE ( (mem_begin > \'' . $calculationdate . '\') OR (mem_end < \'' . $calculationdate . '\') )';
    } elseif (is_array($rols)) {
        $firstpass = true;
        foreach ($rols as $rol => $rol_switch) {
            if ($firstpass) {
                $sql .= ' WHERE (( ';
            } else {
                $sql .= ' OR ( ';
            }
            $sql .= 'mem_rol_id = ' . getRole_IDPAD($rol) . ' ';

            if ($rol_switch == 0) {
                // aktive Mitglieder
                $sql .= ' AND mem_begin <= \'' . $calculationdate . '\' ';
                $sql .= ' AND mem_end >= \'' . $calculationdate . '\' ';
            } elseif ($rol_switch == 1) {
                // nicht aktive Mitglieder ALT: ehemalige Mitglieder
                $sql .= ' AND ( (mem_begin > \'' . $calculationdate . '\') OR (mem_end < \'' . $calculationdate . '\') )';
            }
            $sql .= ' ) ';
            $firstpass = false;
        }
        $sql .= ' ) ';
    }

    $sql .= $conditions;
    $sql .= ' AND mem_rol_id = rol_id
              AND rol_valid  = 1
              AND rol_cat_id = cat_id
              AND (  cat_org_id = ' . ORG_ID . '
              OR cat_org_id IS NULL )
              ORDER BY mem_usr_id ASC ';

    $statement = $gDb->query($sql);
    $anzahl_members = $statement -> rowCount();
    while ($row = $statement->fetch()) {
        // mem_begin und mem_end werden nur in der recalculation.php ausgewertet
        // wird fuer anteilige Beitragsberechnung verwendet
        $members[$row['mem_usr_id']] = array(
            'mem_begin' => $row['mem_begin'],
            'mem_end' => $row['mem_end']
        );
    }

    foreach ($members as $member => $dummy) {
        foreach ($fields as $field => $data) {
            $key = $data;
            $usfID = $gProfileFields->getProperty($data, 'usf_id');

            if (substr($data, 0, 1) == 'p') {
                $usfID = substr($data, 1);
                $key = $usfID;
            }

            $sql = 'SELECT usd_value
                      FROM ' . TBL_USER_DATA . '
                     WHERE usd_usr_id = ' . $member . '
                       AND usd_usf_id = ' . $usfID . ' ';
            $statement = $gDb->query($sql);
            $row = $statement->fetch();
            if ($row == false)
            {
                $members[$member][$key] = '';
            }
            else
            {
                $members[$member][$key] = $row['usd_value'];
            }
        }
    }
    return $members;
}

/*
 * Funktion liefert ein Array der Mitglieder mit Informationen zum geleisteten Arbeitsdienst im Abrechnungsjahr
 * @param array $members Informationen aller Mitglieder aus der Funktion list_members
 * string $datefilteractual Abrechnungsjahr
 * @return array $membersworkinfo Array Ã¼ber alle Mitglieder mit folgenden Inhalten:
 * - Alter
 * - Passiv
 * - Sollstunden
 * - Iststunden
 * - Differenzstunden
 */
function list_members_workinfo($members, $datefilteractual)
{
    global $gDb, $gProfileFields;
    $membersworkinfo = array();

    $pPreferences = new ConfigTablePAD();
    $pPreferences->read(); // Konfigurationsdaten auslesen

    foreach ($members as $member => $memberdata) {
        // Alter jedes Mitglieds berechnen und im Array speichern
        $dt1 = new DateTime($memberdata['BIRTHDAY']);
        $dt2 = new DateTime(date('d.m.Y', strtotime($datefilteractual . '-12-31')));

        $membersworkinfo[$member]['ALTER'] = $dt1->diff($dt2)->y;

        // Soll-Arbeitsstunden und Passivmitgliedschaft ermitteln und im Array speichern
        // ermitteln, ob mehrere Rollen eingetragen sind
        $rolename = array();
        foreach ($pPreferences->config['Ausnahme']['passiveRolle'] as $ausnahme => $data) {
            $rolename[$ausnahme] = rolname($data);
            $passiv = list_members($datefilteractual, array(
                'FIRST_NAME',
                'LAST_NAME'
            ), array(
                $rolename[$ausnahme] => 0
            ), 'AND mem_usr_id = ' . $member);
            IF (! empty($passiv)) {
                // wird ein Eintrag gefunden --> Mitglied ist Beitragsbefreit --> Abbruch
                break;
            }
        }
        if (empty($passiv)) {
            $membersworkinfo[$member]['PASSIV'] = 'nein';
            if (($membersworkinfo[$member]['ALTER'] >= $pPreferences->config['Alter']['AGEBegin']) && ($membersworkinfo[$member]['ALTER'] < $pPreferences->config['Alter']['AGEEnd'])) {
                if ($memberdata['GENDER'] == 1) {
                    // MÃ¤nner
                    $membersworkinfo[$member]['Sollstunden'] = $pPreferences->config['Stunden']['WorkingHoursMan'];
                } else {
                    // Frauen
                    $membersworkinfo[$member]['Sollstunden'] = $pPreferences->config['Stunden']['WorkingHoursWoman'];
                }
            } else {
                $membersworkinfo[$member]['Sollstunden'] = 0;
            }
        } else {
            $membersworkinfo[$member]['PASSIV'] = 'ja';
            $membersworkinfo[$member]['Sollstunden'] = 0;
        }

        // geleistete Arbeitsstunden des Abrechnungsjahres ermitteln und im Array speichern
        $membersworkinfo[$member]['Iststunden'] = 0;
        $membersworkinfo[$member]['Iststunden'] = getsumworkinghours($datefilteractual, $member);

        // Differenz Ist zu Soll ermitteln und im Array speichern
        $membersworkinfo[$member]['Differenzstunden'] = ($membersworkinfo[$member]['Iststunden'] - $membersworkinfo[$member]['Sollstunden']);

        // Fehlstunden ermitteln und im Array speichern
        $membersworkinfo[$member]['Fehlstunden'] = 0;
        if (($membersworkinfo[$member]['Differenzstunden']) < 0) 
        {
            $membersworkinfo[$member]['Fehlstunden'] = - $membersworkinfo[$member]['Differenzstunden'];
            $membersworkinfo[$member]['Kosten'] = $membersworkinfo[$member]['Fehlstunden'] * $pPreferences->config['Stunden']['Kosten'];
        }
        else
        {
            $membersworkinfo[$member]['Kosten'] = 0;
        }
    }
    return $membersworkinfo;
}

/*
 * Die Funktion berechnet die Gesamtsummen der Arbeitsstunden aller Mitglieder und gibt diese in einnem Array zurÃ¼ck
 * @param array $membersworkinfo Informationen aller Mitglieder aus der Funktion list_members
 * @return array $membersworkinginfo Ausgabearray:
 * - Mitgliederanzahl
 * - Summe Soll-Arbeitsstunden
 * - Summe Ist-Arbeitsstunden
 * - Summe Fehlstunden
 */
function sum_working($membersworkinfo, $costshour)
{
    $AnzahlMitglieder = 0;
    $AnzahlZahler = 0;
    $targethours = 0;
    $workinghours = 0;
    $missinghours = 0;
    $sumworking = 0;
    $sumworking = array();

    foreach ($membersworkinfo as $member => $memberdata) {
        $AnzahlMitglieder ++;

        $targethours = $targethours + $membersworkinfo[$member]['Sollstunden'];
        $workinghours = $workinghours + $membersworkinfo[$member]['Iststunden'];
        $missinghours = $missinghours + $membersworkinfo[$member]['Fehlstunden'];
        if ($membersworkinfo[$member]['Fehlstunden'] > 0) {
            $AnzahlZahler ++;
        }
    }
    $sumworking['Mitgliederanzahl'] = $AnzahlMitglieder;
    $sumworking['AnzahlZahler'] = $AnzahlZahler;
    $sumworking['Sollstunden'] = $targethours;
    $sumworking['Iststunden'] = $workinghours;
    $sumworking['Fehlstunden'] = $missinghours;
    $sumworking['Kosten'] = $missinghours * $costshour;
    if ($sumworking['Kosten'] == 0) {
        $sumworking['Kosten'] = '';
    } else {
        $sumworking['Kosten'] = number_format($sumworking['Kosten'], 2);
        //$sumworking['Kosten'] = $sumworking['Kosten'];
    }
    return $sumworking;
}

/**
 * Gibt zu einem Kategorienamen die entsprechende Cat_ID zurueck
 * übernommen aus dem Plugin Mitgliederbeitrag
 *
 * @param string $cat_name_intern
 *            Name der zu pruefenden Kategorie
 * @return int cat_id Cat_id der Kategorie
 */
function getCat_IDPAD($cat_name_intern)
{
    $sql = ' SELECT cat_id
            FROM ' . TBL_CATEGORIES . '
            WHERE cat_name_intern = \'' . $cat_name_intern . '\'
            AND cat_type = \'USF\'
            AND (  cat_org_id = ' . $GLOBALS['gCurrentOrgId'] . '
                OR cat_org_id IS NULL ) ';

    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();

    return $row->cat_id;
}

/*
 * Funktion liest die Role-ID einer Rolle aus
 * @param string $role_name Name der zu pruefenden Rolle
 * @return int rol_id Rol_id der Rolle, 0, wenn nicht gefunden
 */
function getRole_IDPAD($role_name)
{
    global $gDb;

    $sql = 'SELECT rol_id
                 FROM ' . TBL_ROLES . ', ' . TBL_CATEGORIES . '
                 WHERE rol_name   = \'' . $role_name . '\'
                 AND rol_valid  = 1
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = ' . ORG_ID . '
                 OR cat_org_id IS NULL ) ';

    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
    if (isset($row->rol_id) && strlen($row->rol_id) > 0) {
        return $row->rol_id;
    } else {
        return 0;
    }
}

/*
 * Funktion liest den Rollennamen einer Rolle aus
 * @param string $role_id Rol_id der Rolle
 * @return int rol_name Name der Rolle, 0, wenn sie nicht gefunden wird
 */
function rolname($rol_id)
{
    global $gDb;

    $sql = 'SELECT rol_name
                 FROM ' . TBL_ROLES . ', ' . TBL_CATEGORIES . '
                 WHERE rol_id   = \'' . $rol_id . '\'
                 AND rol_valid  = 1
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = ' . ORG_ID . '
                 OR cat_org_id IS NULL ) ';

    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
    if (isset($row->rol_name) && strlen($row->rol_name) != NULL) {
        return $row->rol_name;
    } else {
        return 0;
    }
}

function DBcategoriesID($catname)
{
    global $gDb;

    // ÃœberprÃ¼fen, ob es die Kategorie Arbeitsdienst gibt, ansonsten anlegen
    $sql = 'SELECT cat_id
            FROM ' . TBL_CATEGORIES . '
            WHERE cat_type = \'USF\'
            AND cat_name = \'' . $catname . '\'
            AND (  cat_org_id = ' . ORG_ID . '
            OR cat_org_id IS NULL ) ';

    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
    if (isset($row->cat_id) && ($row->cat_id > 0)) {
        return $row->cat_id;
    } else {
        return 0;
    }
}

function DBuserfieldID($usfnameintern)
{
    global $gDb;

    $sql = 'SELECT usf_id
            FROM ' . TBL_USER_FIELDS . '
            WHERE usf_name_intern = \'' . $usfnameintern . '\'';
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
    if (isset($row->usf_id) && ($row->usf_id > 0)) {
        return $row->usf_id;
    } else {
        return 0;
    }
}

/*
 * Funktion gibt die CSV Exportdaten aus
 * 
 * @param string  $name
 * @return array
 */

function csvdaten($members, $membersworkinfo, $gettypeofoutput)
{
    $separator        = ',';   // a CSV file should have a comma
    $valueQuotes      = '"';   // all values should be set with quotes
    $AnzahlMitglieder = 0;
    $csvStr           = '';

    //erstellen der CSV Datei
    foreach ($members as $member => $memberdata) {
        $output = TRUE;
        $test = $membersworkinfo[$member]['Fehlstunden'];
        if (($gettypeofoutput == 'CSVPAY') && ($membersworkinfo[$member]['Fehlstunden'] == 0)) {
            $output = FALSE;
        }
        if ($output == TRUE) {
            $AnzahlMitglieder ++;

            //Header der CSV Datei erstellen
            if ($AnzahlMitglieder == 1) {
                $csvStr = $csvStr . $valueQuotes . 'Nr' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Nachname' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Vorname' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Alter' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Passiv' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Geschlecht' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Sollstunden' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Iststunden' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Differenzstunden' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'Fehlstunden' . $valueQuotes . $separator;
                $csvStr = $csvStr . $valueQuotes . 'zu zahlen' .$valueQuotes . "\n";
            }

            //Daten der CSV Datei erstellen
            $csvStr .= $valueQuotes . $AnzahlMitglieder . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $members[$member]['LAST_NAME'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $members[$member]['FIRST_NAME'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['ALTER'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['PASSIV'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $members[$member]['GENDER'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['Sollstunden'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['Iststunden'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['Differenzstunden'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['Fehlstunden'] . $valueQuotes . $separator;
            $csvStr .= $valueQuotes . $membersworkinfo[$member]['Kosten'] . $valueQuotes . "\n";
        }
    }
    return $csvStr;
}



/**
 * Ersetzt und entfernt unzulaessige Zeichen in der SEPA-XML-Datei
 *
 * @param string $tmptext
 * @return string $ret
 */
function replace_sepadaten($tmptext)
{
    /*
     * Zulaessige Zeichen
     * Fuer die Erstellung von SEPA-Nachrichten sind die folgenden Zeichen in der
     * Kodierung gemaess UTF-8 bzw. ISO-885933 zugelassen.
     * ---------------------------------------------------
     * Zugelassener Zeichencode| Zeichen | Hexcode
     * Numerische Zeichen | 0 bis 9 | X'30' bis X'39'
     * GroÃŸbuchstaben | A bis Z | X'41' bis X'5A'
     * Kleinbuchstaben | a bis z | X'61' bis 'X'7A'
     * Apostroph | ' | X'27
     * Doppelpunkt | : | X'3A
     * Fragezeichen | ? | X'3F
     * Komma | , | X'2C
     * Minus | - | X'2D
     * Leerzeichen | | X'20
     * Linke Klammer | ( | X'28
     * Pluszeichen | + | X'2B
     * Punkt | . | X'2E
     * Rechte Klammer | ) | X'29
     * Schraegstrich | / | X'2F
     */
    $charMap = array(
        'Ã„' => 'Ae',
        'Ã¤' => 'ae',
        'Ã€' => 'A',
        'Ã ' => 'a',
        'Ã�' => 'A',
        'Ã¡' => 'a',
        'Ã‚' => 'A',
        'Ã¢' => 'a',
        'Ã†' => 'AE',
        'Ã¦' => 'ae',
        'Ãƒ' => 'A',
        'Ã£' => 'a',
        'Ã…' => 'A',
        'Ã¥' => 'a',
        'Ã‡' => 'C',
        'Ã§' => 'c',
        'Ã‹' => 'E',
        'Ã«' => 'e',
        'Ãˆ' => 'E',
        'Ã¨' => 'e',
        'Ã‰' => 'E',
        'Ã©' => 'e',
        'ÃŠ' => 'E',
        'Ãª' => 'e',
        'Ã�' => 'I',
        'Ã¯' => 'i',
        'ÃŒ' => 'I',
        'Ã¬' => 'i',
        'Ã�' => 'I',
        'Ã­' => 'i',
        'ÃŽ' => 'I',
        'Ã®' => 'i',
        'ÃŸ' => 'ss',
        'Ã‘' => 'N',
        'Ã±' => 'n',
        'Å’' => 'OE',
        'Å“' => 'oe',
        'Ã–' => 'Oe',
        'Ã¶' => 'oe',
        'Ã’' => 'O',
        'Ã²' => 'o',
        'Ã“' => 'O',
        'Ã³' => 'o',
        'Ã”' => 'O',
        'Ã´' => 'o',
        'Ã•' => 'O',
        'Ãµ' => 'o',
        'Ã˜' => 'O',
        'Ã¸' => 'o',
        'Ãœ' => 'Ue',
        'Ã¼' => 'ue',
        'Ã™' => 'U',
        'Ã¹' => 'u',
        'Ãš' => 'U',
        'Ãº' => 'u',
        'Ã›' => 'U',
        'Ã»' => 'u',
        'Ã¿' => 'y',
        'Ã�' => 'Y',
        'Ã½' => 'y',
        'â‚¬' => 'EUR',
        '*' => '.',
        '$' => '.',
        '%' => '.',
        '&' => '+'
    );

    $ret = str_replace(array_keys($charMap), array_values($charMap), $tmptext);

    for ($i = 0; $i < strlen($ret); $i ++) {
        if (preg_match('/[^A-Za-z0-9\'\:\?\,\-\(\+\.\)\/]/', substr($ret, $i, 1))) {
            $ret = substr_replace($ret, ' ', $i, 1);
        }
    }
    return $ret;
}

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @param string $body
 * @return string
 */
function getMenuePanel($group, $id, $parentId, $title, $icon, $body)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#' . $parentId . '">
                <div class="card-body">
                    ' . $body . '
                </div>
            </div>
        </div>
    ';
    return $html;
}

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @return string
 */
function getMenuePanelHeaderOnly($group, $id, $parentId, $title, $icon)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#' . $parentId . '">
                <div class="card-body">
    ';
    return $html;
}

/**
 * @param none
 * @return string
 */
function getMenuePanelFooterOnly()
{
    return '</div></div></div>';
}

/**
 * @param string $group
 * @return string
 */
function openMenueTab($group, $parentId)
{
    $html = '
        <div class="tab-pane fade" id="tabs-' . $group . '" role="tabpanel">
            <div class="accordion" id="' . $parentId . '">
    ';
    return $html;
}

/**
 * @param none
 * @return string
 */
function closeMenueTab()
{
    return '</div></div>';
}

/**
 * Add a new groupbox to the page. This could be used to group some elements
 * together. There is also the option to set a headline to this group box.
 * @param string $id       Id the the groupbox.
 * @param string $headline (optional) A headline that will be shown to the user.
 * @param string $class    (optional) An additional css classname for the row. The class **admFieldRow**
 *                         is set as default and need not set with this parameter.
 */
function openGroupBox($id, $headline = null, $class = '')
{
    $html = '<div id="' . $id . '" class="card admidio-field-group ' . $class . '">';
    // add headline to groupbox
    if ($headline !== null)
    {
        $html .= '<div class="card-header">' . $headline . '</div>';
    }
    $html .= '<div class="card-body">';
    return $html;
}

/**
 * Close the groupbox that was created before.
 */
function closeGroupBox()
{
    return '</div></div>';
}

/**
 * Shows a test result and, depending an the size, a scroll bar
 * @param array $testResult       array with test result
 * @return string
 */
function showTestResultWithScrollbar($testResult)
{
    $size = sizeof($testResult);
    $html = '';
    
    if ($size > 8)
    {
        $html .= '<div style="width:100%; height:200px; overflow:auto; border:20px;">';
    }
    foreach ($testResult as $data)
    {
        $html .= $data.'<br />';
    }
    if ($size > 8)
    {
        $html .= '</div>';
    }
    return $html;
}


