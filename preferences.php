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
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences()) 
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$showOption = admFuncVariableIsValid($_GET, 'show_option', 'string');

$pPreferences = new ConfigTablePAD();
$pPreferences->read(); // auslesen der gespeicherten Einstellparameter

$rols = allerollen_einlesen();
$selectBoxEntriesAlleRollen = array();

$selectBoxEntriesAlleRollen[0] = '--- Rolle wählen ---';

foreach ($rols as $key => $data) {
    $selectBoxEntriesAlleRollen[$key] = array(
        $key,
        $data['rolle']
    );
}

$headline = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE');


// add current url to navigation stack if last url was not the same page
if ($showOption == '') 
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// create html page object
$page = new HtmlPage('plg-arbeitsdienst-preferences', $headline);
$page->addCssFile(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/css/arbeitsdienst.css');


if ($showOption != '') {
    if (in_array($showOption, array(
        'agetowork',
        'hours',
        'dateaccounting'
    )) == true) {
        $navOption = 'management';
    } else {
        $navOption = 'management';
    }
    
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "nav-link active");
        $("#tabs-preferences").attr("class", "tab-pane fade show active");
        $("#collapse_' . $showOption . '").attr("class", "collapse show");
        location.hash = "#" + "panel_' . $showOption . '";', true);
} else {
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "nav-link active");
        $("#tabs-preferences").attr("class", "tab-pane active");
        ', true);
}


// create module menu with back link
        $headerMenu = new HtmlNavbar('navbar_menu_preferences');
        $headerMenu->addItem('menu_item_update', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences.php', $gL10n->get('SYS_UPDATE'), 'fa-redo', 'right');
$page->addHtml($headerMenu->show(false));


//#############################################################################
// TAB: preferences

$page->addHtml('
    <ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a id="tabs_nav_preferences" class="nav-link" href="#tabs-preferences" data-toggle="tab" role="tab">' . $gL10n->get('PLG_ARBEITSDIENST_SETTINGS') . '</a>
        </li>
    </ul>
    
    <div class="tab-content">
        <div class="tab-pane fade" id="tabs-preferences" role="tabpanel">
            <div class="accordion" id="accordion_preferences">');

    
    //#############################################################################
    //  Panel: agetowork
    // Eingabe der Altersgrenzen
            $form = new HtmlForm('input_form_setting_age', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'ageconfig')), $page, array('class' => 'form-preferences'));
            
            // Eingabe des Alters, ab wann der Arbeitsdienst verpflichtend ist
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_DATA_AGE_BEGIN_INFO'));
            $form->addInput('AGEBegin', $gL10n->get('PLG_ARBEITSDIENST_INPUT_AGE_BEGIN'), $pPreferences->config['Alter']['AGEBegin'], array(
                'type' => 'number',
                'minNumber' => 16,
                'maxNumber' => 100,
                'step' => 1
            ));
            $form->addLine();
            
            // Eingabe des Alters, ab wann der Arbeitsdienst verpflichtend ist
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_DATA_AGE_END_INFO'));
            // Eingabe des Alters, ab wann kein Arbeitsdienst mehr geleistet werden muss
            $form->addInput('AGEEnd', $gL10n->get('PLG_ARBEITSDIENST_INPUT_AGE_END'), $pPreferences->config['Alter']['AGEEnd'], array(
                'type' => 'number',
                'minNumber' => 60,
                'maxNumber' => 100,
                'step' => 1
            ));
            $form->addLine();
            $form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                        'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'agetowork', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_AGE_TO_WORK'), 'fas fa-user-clock', $form->show()));
   
    //#############################################################################
    //  Panel: hours
    //  Eingabe der Anzahl der zu leistender Arbeitsstunden und des Stundensatzes
            $form = new HtmlForm('input_form_setting_hour', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'workinghoursconfig')), $page, array('class' => 'form-preferences'));

            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_MAN'));
            $form->addInput('WorkingHoursMan', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_MAN'), $pPreferences->config['Stunden']['WorkingHoursMan'], array('type' => 'number',
                                                                                                                                                                   'minNumber' => 1,
                                                                                                                                                                   'maxNumber' => 100,
                                                                                                                                                                   'step' => 1 ));
            $form->addLine();
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_WOMAN'));
            $form->addInput('WorkingHoursWoman', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_WOMAN'), $pPreferences->config['Stunden']['WorkingHoursWoman'], array('type' => 'number',
                                                                                                                                                                         'minNumber' => 1,
                                                                                                                                                                         'maxNumber' => 100,
                                                                                                                                                                         'step' => 1));
            $form->addLine();
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_AMOUNT'));
            $form->addInput('WorkingHoursAmount', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_AMOUNT'), $pPreferences->config['Stunden']['Kosten'], array('type' => 'number',
                                                                                                                                                                'minNumber' => 0,
                                                                                                                                                                'maxNumber' => 100,
                                                                                                                                                                'step' => 0.1));
            $form->addLine();
            $form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                        'class' => ' offset-sm-3'));
            
    $page->addHtml(getMenuePanel('preferences', 'hours', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_HOURS'), 'fas fa-clock', $form->show()));
            
    //#############################################################################
    //  Panel: dateaccounting
    //  Eingabe des Tages, an dem die Gelder eingezogen werden
            $form = new HtmlForm('input_form_setting_dateaccounting', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'dateaccounting')), $page, array('class' => 'form-preferences'));
            
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_DATEACCOUNTING'));
            $form->addInput('dateaccounting', $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEACCOUNTING'), $pPreferences->config['Datum']['Stichtag'], array('type' => 'date'));
            $form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                        'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'dateaccounting', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_DATEACCOUNTING'), 'fas fa-calendar-day', $form->show()));
  
    //#############################################################################
    //  Panel: exceptions
    //  Eingabe von Rollen, bei denen nicht gearbeitet werden muss
            $form = new HtmlForm('input_form_setting_exceptions', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'exceptions')), $page, array('class' => 'form-preferences'));;
            
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_EXCEPTION'));
            $form->addSelectBox('exceptions_roleselection', $gL10n->get('PLG_ARBEITSDIENST_ROLE_SELECTION'), $selectBoxEntriesAlleRollen, array('multiselect' => true,
                                                                                                                                                'defaultValue' => $pPreferences->config['Ausnahme']['passiveRolle'],
                                                                                                                                                'showContextDependentFirstEntry' => FALSE));
            $form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                        'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'exceptions', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_EXCEPTION'), 'fas fa-border-none', $form->show()));
    
    //#############################################################################
    //  Panel: filename
    //  Eingabe des Dateinamens
            $form = new HtmlForm('input_form_setting_filename', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'filename')), $page, array('class' => 'form-preferences'));
            
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_FILENAME'));
            $form->addInput('filename', $gL10n->get('PLG_ARBEITSDIENST_INPUT_FILENAME'), $pPreferences->config['SEPA']['dateiname'], array('type' => 'text'));
            $form->addSubmitButton('btn_input_save_filename', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                                 'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'filename', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_FILENAME'), 'fas fa-file-signature', $form->show()));

    
    //#############################################################################
    //  Panel: reference
    //  Eingabe des Verwendungszweck für die SEPA Abrechnung
            $form = new HtmlForm('input_form_setting_reference', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php', array('form' => 'reference')), $page, array('class' => 'form-preferences'));
            
            // Eingabe des Tages, an dem die Gelder eingezogen werden
            $form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_REFERENCE'));
            $form->addInput('reference', $gL10n->get('PLG_ARBEITSDIENST_INPUT_REFERENCE'), $pPreferences->config['SEPA']['reference'], array('type' => 'text'));
            $form->addSubmitButton('btn_input_save_reference', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                                  'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'reference', 'accordion_preferences', $gL10n->get('PLG_ARBEITSDIENST_REFERENCE'), 'fas fa-file-invoice-dollar', $form->show()));
    
    //#############################################################################
    //  Panel: reference
    //  Eingabe des Verwendungszweck für die SEPA Abrechnung
    $form = new HtmlForm('plugin_informations_preferences_form', null, $page, array('class' => 'form-preferences'));
    $form->addStaticControl('plg_name', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_NAME'), $gL10n->get('PLG_Arbeitsdienst_MEMBERSHIP_FEE'));
    $form->addStaticControl('plg_version', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
    $form->addStaticControl('plg_date', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
    
    $page->addHtml(getMenuePanel('preferences', 'plugin_informations', 'accordion_preferences', $gL10n->get('PLG_Arbeitsdienst_PLUGIN_INFORMATION'), 'fas fa-info', $form->show()));
    
    $page->addHtml('
        </div>
    </div>
</div>');
    
$page->show();