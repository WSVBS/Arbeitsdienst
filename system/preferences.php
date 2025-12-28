<?php

/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin Arbeitsstunden
 *
 * @copyright 2004-2021 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * show_option
 ***********************************************************************************************
 
 */
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Infrastructure\Exception;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');
    require_once (__DIR__ . '/../classes/configtable.php');
    require_once(__DIR__ . '/../../../system/login_valid.php');

    // only authorized user are allowed to start this module
        if (!$gCurrentUser->isAdministrator()) {
            throw new Exception('SYS_NO_RIGHTS');
        }

    // Initialize and check the parameters
    $showOption = admFuncVariableIsValid($_GET, 'show_option', 'string');

    if (empty($showoption))
    {
        $showOption = 'configuration';
    }
    $pPreferences = new ConfigTablePAD();
    $pPreferences->read(); // auslesen der gespeicherten Einstellparameter

    $headline = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE'). ' - ' . $gL10n->get('SYS_CONFIGURATIONS');

    
    $rols = allerollen_einlesen();
    $selectBoxEntriesAlleRollen = array();

    $selectBoxEntriesAlleRollen[0] = '--- Rolle wählen ---';

    foreach ($rols as $key => $data) {
        $selectBoxEntriesAlleRollen[$key] = array(
            $key,
            $data['rolle']
        );
    }

    $gNavigation->addUrl(CURRENT_URL, $gL10n->get('SYS_CONFIGURATIONS'));

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-arbeitsdienst-preferences', $headline);
    //$page->addCssFile(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/css/arbeitsdienst.css');

    //#############################################################################
    //  Eingabe für Altergrenzen
    //#############################################################################

    $formConfigurations = new FormPresenter(
                                'input_form_setting', 
                                __DIR__ . '/../templates/arbeitsdienst.config.tpl',                                      
                                SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/preferences_function.php', array('form' => 'configuration')), 
                                $page, 
                                array('class' => 'form-preferences'));
            
            
    // Eingabe des Alters, ab wann der Arbeitsdienst verpflichtend ist
    $formConfigurations->addDescription('Data_Age_Info',
                                        $gL10n->get('PLG_ARBEITSDIENST_DATA_AGE_BEGIN_INFO'));

    $formConfigurations->addInput('AGEBegin', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_AGE_BEGIN'), 
                                $pPreferences->config['Alter']['AGEBegin'], 
                                array(
                                            'type' => 'number',
                                            'minNumber' => 16,
                                            'maxNumber' => 100,
                                            'step' => 1 ));
                                                
    // Eingabe des Alters, ab wann kein Arbeitsdienst mehr geleistet werden muss
    $formConfigurations->addInput('AGEEnd', 
                    $gL10n->get(textId: 'PLG_ARBEITSDIENST_INPUT_AGE_END'), 
                    $pPreferences->config['Alter']['AGEEnd'], 
                    array(
                                'type' => 'number',
                                'minNumber' => 60,
                                'maxNumber' => 100,
                                'step' => 1 ));

//#############################################################################
//  Eingabe für Stunden
//#############################################################################
   
    $formConfigurations->addDescription('Data_hours_Info', 
                                        $gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_MAN'));
    
    $formConfigurations->addInput('workinghoursman', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_MAN'), 
                                $pPreferences->config['Stunden']['WorkingHoursMan'], 
                                array('type' => 'number',
                                                'minNumber' => 1,
                                                'maxNumber' => 100,
                                                'step' => 1 ));

    $formConfigurations->addInput('workinghourswoman', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_WOMAN'), 
                                $pPreferences->config['Stunden']['WorkingHoursWoman'], 
                                array('type' => 'number',
                                                'minNumber' => 1,
                                                'maxNumber' => 100,
                                                'step' => 1 ));
           
    $formConfigurations->addInput('workinghoursamount', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_AMOUNT'), 
                                $pPreferences->config['Stunden']['Kosten'], 
                                array('type' => 'number',
                                                        'minNumber' => 0,
                                                        'maxNumber' => 100,
                                                        'step' => 0.1));        
            
//#############################################################################
//  Eingabe Fälligkeitsdatum
//#############################################################################            
            
    $formConfigurations->addInput('dateaccounting', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEACCOUNTING'), 
                                $pPreferences->config['Datum']['Stichtag'], 
                                array('type' => 'date'));        
            
//#############################################################################
//  Eingabe passive Rollen
//#############################################################################               
    
    $formConfigurations->addSelectBox('exceptions_roleselection', $gL10n->get('PLG_ARBEITSDIENST_ROLE_SELECTION'),
                                      $selectBoxEntriesAlleRollen, array('multiselect' => true,
                                                                                            'defaultValue' => $pPreferences->config['Ausnahme']['passiveRolle'],
                                                                                            'showContextDependentFirstEntry' => FALSE));       

//#############################################################################
//  Eingabe Dateiname
//############################################################################# 
    


    $formConfigurations->addInput('filename', 
                                 $gL10n->get('PLG_ARBEITSDIENST_INPUT_FILENAME'), 
                                 $pPreferences->config['SEPA']['dateiname'], 
                                 array('type' => 'text'));

//#############################################################################
//  Eingabe Verwendungszweck
//############################################################################# 

    $formConfigurations->addInput('reference', 
                                  $gL10n->get('PLG_ARBEITSDIENST_INPUT_REFERENCE'), 
                                  $pPreferences->config['SEPA']['reference'], 
                                  array('type' => 'text'));

//#############################################################################
//  Ausgabe Plugininformationen
//############################################################################# 
    /*
    $formConfigurations->addStatic('plg_name', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_NAME'), $gL10n->get('PLG_Arbeitsdienst_MEMBERSHIP_FEE'));
    $formConfigurations->addStaticControl('plg_version', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
    $formConfigurations->addStaticControl('plg_date', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
                        */

//#############################################################################
//  Speicherbutton
//#############################################################################             

    $formConfigurations->addSubmitButton('btn_input_save_reference', 
                            $gL10n->get('SYS_SAVE'), 
                            array('icon' => 'bi-check-lg',
                                           'class' => 'offset-sm-0'));
    
    $formConfigurations->addToHtmlPage();
    $gCurrentSession->addFormObject($formConfigurations);
   
    $page->show();

} catch (Throwable $e) {
    handleException($e);
}

