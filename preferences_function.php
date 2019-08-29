<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins Arbeitsdienst
 * 
 * @copyright 2018-2019 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Parameters:
 *
 * form     - The name of the form preferences that were submitted.
 * 
 ***********************************************************************************************
 */
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (! $gCurrentUser->isAdministrator()) {
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}


$pPreferences = new ConfigTablePAD();
$pPreferences->read();

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

try {
    switch ($getForm) {
        case 'ageconfig':
            // neue EInträge der Konfigurationdaten für die Altersgrenzen ermitteln
            unset($pPreferences->config['Alter']);
            $pPreferences->config['Alter']['AGEBegin'] = $_POST['AGEBegin'];
            $pPreferences->config['Alter']['AGEEnd'] = $_POST['AGEEnd'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'agetowork'
            ));
            break;

        case 'workinghoursconfig':
            // neue EInträge der Konfigurationdaten für die Stundenanzahl ermitteln
            unset($pPreferences->config['Stunden']);
            $pPreferences->config['Stunden']['WorkingHoursMan'] = $_POST['WorkingHoursMan'];
            $pPreferences->config['Stunden']['WorkingHoursWoman'] = $_POST['WorkingHoursWoman'];
            $pPreferences->config['Stunden']['Kosten'] = $_POST['WorkingHoursAmount'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'hours'
            ));
            break;

        case 'dateaccounting':
            // neue EInträge der Konfigurationdaten für die Stundenanzahl ermitteln
            unset($pPreferences->config['Datum']);
            $pPreferences->config['Datum']['Stichtag'] = $_POST['dateaccounting'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'dateaccounting'
            ));
            break;

        case 'exceptions':
            // neue Einträge der Konfigurationdaten für die passive Mitgliedschaft ermitteln
            unset($pPreferences->config['Ausnahme']);

            $pPreferences->config['Ausnahme']['passiveRolle'] = $_POST['exceptions_roleselection'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'exceptions'
            ));
            break;

        case 'filename':
            unset($pPreferences->config['SEPA']);

            $pPreferences->config['SEPA']['dateiname'] = $_POST['filename'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'filename'
            ));
            break;

        case 'reference':
            unset($pPreferences->config['SEPA']);

            $pPreferences->config['SEPA']['reference'] = $_POST['reference'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'reference'
            ));
            break;

        default:
            $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
    $pPreferences->save();

    // weiterleiten an die letzte URL
    admRedirect($url);
} catch (AdmException $e) {
    $e->showText();
}



