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
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (! $gCurrentUser->isAdministrator()) {
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}


if (DBcategoriesID('PAD_ARBEITSDIENST') == 0) {
    // Kategorie "Arbeitsdienst" der Tabelle adm_categories hinzufügen
    $nextCatSequence = getNextCatSequence('USF');

    $sql = 'INSERT INTO ' . TBL_CATEGORIES . '
            (cat_org_id, cat_type, cat_name_intern, cat_name, cat_system,
             cat_default, cat_sequence, cat_usr_id_create)
             VALUES (' . ORG_ID . ',
                      \'USF\',
                      \'Arbeitsdienst\',
                      \'PAD_ARBEITSDIENST\',
                      0,
                      0,
                      ' . $nextCatSequence . ',
                      ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

// Prüfen, ob die notwendigen user_fields vorhanden sind
if (DBuserfieldID('WORKPAID') == 0) {
    $sql = 'INSERT INTO ' . TBL_USER_FIELDS . '
           (usf_cat_id, usf_type, usf_name_intern, usf_name,
            usf_description, usf_system, usf_disabled, usf_hidden,
            usf_mandatory, usf_registration, usf_sequence, usf_usr_id_create )
            Values (' . DBcategoriesID('PAD_ARBEITSDIENST') . ' ,
                    \'DATE\',
                    \'WORKPAID\',
                    \'PAD_PAID\',
                    \'' . $gL10n->get('PAD_PAID') . '\',
                    0,
                    1,
                    1,
                    0,
                    0,
                    1,
                    ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

if (DBuserfieldID('WORKFEE') == 0) {
    $sql = 'INSERT INTO ' . TBL_USER_FIELDS . '
           (usf_cat_id, usf_type, usf_name_intern, usf_name,
            usf_description, usf_system, usf_disabled, usf_hidden,
            usf_mandatory, usf_registration, usf_sequence, usf_usr_id_create )
            Values (' . DBcategoriesID('PAD_ARBEITSDIENST') . ' ,
                    \'DECIMAL\',
                    \'WORKFEE\',
                    \'PAD_FEE\',
                    \'' . $gL10n->get('PAD_FEE') . '\',
                    0,
                    1,
                    1,
                    0,
                    0,
                    2,
                    ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

if (DBuserfieldID('WORKREFERENCE') == 0) {
    $sql = 'INSERT INTO ' . TBL_USER_FIELDS . '
           (usf_cat_id, usf_type, usf_name_intern, usf_name,
            usf_description, usf_system, usf_disabled, usf_hidden,
            usf_mandatory, usf_registration, usf_sequence, usf_usr_id_create )
            Values (' . DBcategoriesID('PAD_ARBEITSDIENST') . ' ,
                    \'TEXT\',
                    \'WORKREFERENCE\',
                    \'PAD_REFERENCE\',
                    \'' . $gL10n->get('PAD_REFERENCE') . '\',
                    0,
                    1,
                    1,
                    0,
                    0,
                    3,
                    ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

if (DBuserfieldID('WORKSEQUENCETYPE') == 0) {
    $sql = 'INSERT INTO ' . TBL_USER_FIELDS . '
           (usf_cat_id, usf_type, usf_name_intern, usf_name,
            usf_description, usf_system, usf_disabled, usf_hidden,
            usf_mandatory, usf_registration, usf_sequence, usf_usr_id_create )
            Values (' . DBcategoriesID('PAD_ARBEITSDIENST') . ' ,
                    \'TEXT\',
                    \'WORKSEQUENCETYPE\',
                    \'PAD_SEQUENCETYPE\',
                    \'' . $gL10n->get('PAD_SEQUENCETYPE') . '\',
                    0,
                    1,
                    1,
                    0,
                    0,
                    4,
                    ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

if (DBuserfieldID('WORKDUEDATE') == 0) {
    $sql = 'INSERT INTO ' . TBL_USER_FIELDS . ' 
           (usf_cat_id, usf_type, usf_name_intern, usf_name,
            usf_description, usf_system, usf_disabled, usf_hidden,
            usf_mandatory, usf_registration, usf_sequence, usf_usr_id_create )
            Values (' . DBcategoriesID('PAD_ARBEITSDIENST') . ' ,
                    \'DATE\',
                    \'WORKDUEDATE\',
                    \'PAD_DUEDATE\',
                    \'' . $gL10n->get('PAD_DUEDATE') . '\',
                    0,
                    1,
                    1,
                    0,
                    0,
                    5,
                    ' . $gCurrentUser->getValue('usr_id') . ')';
    $statement = $gDb->query($sql);
}

// weiterleiten zur letzten URL
$url = safeUrl($gNavigation->getUrl(), array());

admRedirect($url);

// Funktionen, die nur in diesem Script benoetigt werden
function getNextCatSequence($cat_type)
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