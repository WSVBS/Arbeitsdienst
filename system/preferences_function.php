<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins Arbeitsdienst
 * 
 * @copyright 2018-2021 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Parameters:
 *
 * form     - The name of the form preferences that were submitted.
 * 
 ***********************************************************************************************
 */

 use Admidio\Infrastructure\Utils\SecurityUtils;
 use Admidio\Infrastructure\Exception;

try {


    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');
    require_once (__DIR__ . '/../classes/configtable.php');

    // only authorized user are allowed to start this module
    if (!$gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }


    $pPreferences = new ConfigTablePAD();
    $pPreferences->read();

    // Initialize and check the parameters
    $getForm = admFuncVariableIsValid($_GET, 'form', 'string');

    // check the CSRF token of the form against the session token
    /*
    $arbeitsdienstConfigForm = $gCurrentSession->getFormObject($_POST['adm_csrf_token']);
    if ($_POST['adm_csrf_token'] !== $arbeitsdienstConfigForm->getCsrfToken()) {
        throw new Exception('Invalid or missing CSRF token!');
    }
    */
    switch ($getForm) {
        case 'configuration':
            unset($pPreferences->config['Alter']);
            $pPreferences->config['Alter']['AGEBegin'] = $_POST['AGEBegin'];
            $pPreferences->config['Alter']['AGEEnd'] = $_POST['AGEEnd'];

            unset($pPreferences->config['Stunden']);
            $pPreferences->config['Stunden']['WorkingHoursMan'] = $_POST['workinghoursman'];
            $pPreferences->config['Stunden']['WorkingHoursWoman'] = $_POST['workinghourswoman'];
            $pPreferences->config['Stunden']['Kosten'] = $_POST['workinghoursamount'];

            unset($pPreferences->config['Datum']);
            $pPreferences->config['Datum']['Stichtag'] = $_POST['dateaccounting'];

            unset($pPreferences->config['Ausnahme']);
            $pPreferences->config['Ausnahme']['passiveRolle'] = $_POST['exceptions_roleselection'];

            unset($pPreferences->config['SEPA']);
            $pPreferences->config['SEPA']['dateiname'] = $_POST['filename'];
            $pPreferences->config['SEPA']['reference'] = $_POST['reference'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'configuration'));
            break;

        case 'ageconfig':
            
            // neue EInträge der Konfigurationdaten für die Altersgrenzen ermitteln
            unset($pPreferences->config['Alter']);
            $pPreferences->config['Alter']['AGEBegin'] = $_POST['AGEBegin'];
            $pPreferences->config['Alter']['AGEEnd'] = $_POST['AGEEnd'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
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
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'hours'
            ));
            break;

        case 'dateaccounting':
            // neue EInträge der Konfigurationdaten für die Stundenanzahl ermitteln
            unset($pPreferences->config['Datum']);
            $pPreferences->config['Datum']['Stichtag'] = $_POST['dateaccounting'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'dateaccounting'
            ));
            break;

        case 'exceptions':
            // neue Einträge der Konfigurationdaten für die passive Mitgliedschaft ermitteln
            unset($pPreferences->config['Ausnahme']);

            $pPreferences->config['Ausnahme']['passiveRolle'] = $_POST['exceptions_roleselection'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'exceptions'
            ));
            break;

        case 'filename':
            unset($pPreferences->config['SEPA']);

            $pPreferences->config['SEPA']['dateiname'] = $_POST['filename'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'filename'
            ));
            break;

        case 'reference':
            unset($pPreferences->config['SEPA']);

            $pPreferences->config['SEPA']['reference'] = $_POST['reference'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'reference'
            ));
            break;

        default:
            $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
    $pPreferences->save();

    // weiterleiten an die letzte URL
    admRedirect($url);
} catch (Throwable $e) {
    handleException($e, true);
}



