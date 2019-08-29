<?php

/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin Arbeitsstunden
 *
 * @copyright 2004-2019 WSVBS
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
if (! $gCurrentUser->isAdministrator()) {
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
if (! admStrContains($gNavigation->getUrl(), 'preferences.php')) {
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// create html page object
$page = new HtmlPage($headline);
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

    $page->addJavascript('$("#tabs_nav_' . $navOption . '").attr("class", "active");
        $("#tabs-' . $navOption . '").attr("class", "tab-pane active");
        $("#collapse_' . $showOption . '").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_' . $showOption . '";', true);
} else {
    $page->addJavascript('$("#tabs_nav_management").attr("class", "active");
        $("#tabs-management").attr("class", "tab-pane active");
        ', true);
}
// create module menu with back link
$headerMenu = new HtmlNavbar('menu_preferences', $headline, $page);
$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/arbeitsdienst.php', $gL10n->get('SYS_BACK'), 'back.png');

$page->addHtml($headerMenu->show(false));

$page->addHtml('
    <ul class="nav nav-tabs" id="preferences_tabs">
        <li id="tabs_nav_management"><a href="#tabs-management" data-toggle="tab">' . $gL10n->get('PLG_ARBEITSDIENST_SETTINGS') . '</a></li>
    </ul>;

    <div class="tab-content">  
        <div class="tab-pane" id="tabs-management">
            <div class="panel-group" id="accordion_management">');
$page->addHtml('
                    <div class="panel panel-default" id="panel_agetowork">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_agetowork">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_AGE_TO_WORK') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_AGE_TO_WORK') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_AGE_TO_WORK') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_agetowork" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form
// Eingabe der Altersgrenzen
$form = new HtmlForm('input_form_setting_age', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=ageconfig', $page, array(
    'class' => 'form-preferences'
));

$form->openGroupBox('input_age');
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
$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_age

$page->addHtml($form->show(false));

$page->addHtml('
                            </div>
                        </div>
                    </div>     
                ');
$page->addHtml('
                    <div class="panel panel-default" id="panel_hours">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_hours">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_HOURS') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_HOURS') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_HOURS') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_hours" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form
// Eingabe der Anzahl zu leidtender Arbeitsstunden
$form = new HtmlForm('input_form_setting_hours', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=workinghoursconfig', $page);

$form->openGroupBox('input_hours');
// Eingabe der Stunden für Frauen und Männer
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_MAN'));
$form->addInput('WorkingHoursMan', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_MAN'), $pPreferences->config['Stunden']['WorkingHoursMan'], array(
    'type' => 'number',
    'minNumber' => 1,
    'maxNumber' => 100,
    'step' => 1
));
$form->addLine();
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_WOMAN'));
$form->addInput('WorkingHoursWoman', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_WOMAN'), $pPreferences->config['Stunden']['WorkingHoursWoman'], array(
    'type' => 'number',
    'minNumber' => 1,
    'maxNumber' => 100,
    'step' => 1
));
$form->addLine();
$form->addLine();
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_WORKINGHOURS_AMOUNT'));
$form->addInput('WorkingHoursAmount', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGHOURS_AMOUNT'), $pPreferences->config['Stunden']['Kosten'], array(
    'type' => 'number',
    'minNumber' => 0,
    'maxNumber' => 100,
    'step' => 0.1
));
$form->addLine();
$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_hours

$page->addHtml($form->show(false));
$page->addHtml('
                            </div>
                        </div>
                    </div>    
                ');
$page->addHtml('
                    <div class="panel panel-default" id="panel_dateaccounting">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_dateaccounting">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_DATEACCOUNTING') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_DATEACCOUNTING') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_DATEACCOUNTING') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_dateaccounting" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form

$form = new HtmlForm('input_form_setting_dateaccounting', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=dateaccounting', $page);

$form->openGroupBox('input_dateaccounting');
// Eingabe des Tages, an dem die Gelder eingezogen werden
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_DATEACCOUNTING'));
$form->addInput('dateaccounting', $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEACCOUNTING'), $pPreferences->config['Datum']['Stichtag'], array(
    'type' => 'date'
));
$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_dateaccounting

$page->addHtml($form->show(false));
$page->addHtml('
                            </div>
                        </div>
                    </div>   
                ');
$page->addHtml('
                    <div class="panel panel-default" id="panel_exceptions">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_exceptions">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_EXCEPTION') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_EXCEPTION') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_EXCEPTION') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_exceptions" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form
// Eingabe der Anzahl zu leidtender Arbeitsstunden
$form = new HtmlForm('input_form_setting_exceptions', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=exceptions', $page);

$form->openGroupBox('input_exceptions');
// Eingabe des Tages, an dem die Gelder eingezogen werden
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_EXCEPTION'));
$form->addSelectBox('exceptions_roleselection', $gL10n->get('PLG_ARBEITSDIENST_ROLE_SELECTION'), $selectBoxEntriesAlleRollen, array(
    'multiselect' => true,
    'defaultValue' => $pPreferences->config['Ausnahme']['passiveRolle'],
    'showContextDependentFirstEntry' => FALSE
));
$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_dateaccounting

$page->addHtml($form->show(false));
$page->addHtml('
                            </div>
                        </div>
                    </div>
                    ');
$page->addHtml('
                    <div class="panel panel-default" id="panel_filename">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_filename">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_FILENAME') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_FILENAME') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_FILENAME') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_filename" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form
// Eingabe der Anzahl zu leidtender Arbeitsstunden
$form = new HtmlForm('input_form_setting_exceptions', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=filename', $page);

$form->openGroupBox('input_exceptions');
// Eingabe des Tages, an dem die Gelder eingezogen werden
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_FILENAME'));
$form->addInput('filename', $gL10n->get('PLG_ARBEITSDIENST_INPUT_FILENAME'), $pPreferences->config['SEPA']['dateiname'], array(
    'type' => 'text'
));
$form->addSubmitButton('btn_input_save_filename', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_dateaccounting

$page->addHtml($form->show(false));
$page->addHtml('
                            </div>
                        </div>
                    </div>
                    ');
$page->addHtml('
                    <div class="panel panel-default" id="panel_reference">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_reference">
                                    <img src="' . THEME_URL . '/icons/options.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_REFERENCE') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_REFERENCE') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_REFERENCE') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_reference" class="panel-collapse collapse">
                            <div class="panel-body">');
// show form
// Eingabe der Anzahl zu leidtender Arbeitsstunden
$form = new HtmlForm('input_form_setting_exceptions', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences_function.php?form=reference', $page);

$form->openGroupBox('input_reference');
// Eingabe des Tages, an dem die Gelder eingezogen werden
$form->addDescription($gL10n->get('PLG_ARBEITSDIENST_INFO_REFERENCE'));
$form->addInput('reference', $gL10n->get('PLG_ARBEITSDIENST_INPUT_REFERENCE'), $pPreferences->config['SEPA']['reference'], array(
    'type' => 'text'
));
$form->addSubmitButton('btn_input_save_reference', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
    'icon' => THEME_URL . '/icons/edit.png',
    'class' => ' col-sm-offset-3'
));
$form->closeGroupBox(); // input_dateaccounting

$page->addHtml($form->show(false));
$page->addHtml('
                            </div>
                        </div>
                    </div>
            </div>
        </div>    
    </div>
');

$page->show();