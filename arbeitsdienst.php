<?php
/**
 ***********************************************************************************************
 * Arbeitsdienst
 *
 * Version 0.3.0
 *
 * Dieses Plugin berechnet Arbeitsstunden.
 *
 * Author: WSVBS
 *
 * Compatible with Admidio version 3.3.4
 *
 * @copyright 2018-2019 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Parameter:
 * 
 * show_option
 * input_id_datefilter		'3' actual year for the calculation
 * 							'2' actual year -1 for the calculation
 * 							'1' actual year -2 for the calculation
 * input_user				ID of the actual user
 * input_edit				true if an input is actual done
 * 							false for no input is done
 * pad_id					ID of the actual input on the tbl_arbeitsdienst
 ***********************************************************************************************
 */

// Fehlermeldungen anzeigen
// error_reporting(E_ALL);
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/../../adm_program/system/login_valid.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

// script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
$_SESSION['pMembershipFee']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

// only authorized user are allowed to start this module
if (! isUserAuthorized($_SESSION['pMembershipFee']['script_name'])) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getUserId = admFuncVariableIsValid($_GET, 'user_id', 'int', array('defaultValue' => (int) $gCurrentUser->getValue('usr_id')));
$getshowOption = admFuncVariableIsValid($_GET, 'show_option', 'string');
$getdatefilterid = admFuncVariableIsValid($_GET, 'input_id_datefilter', 'int');
$getinputuser = admFuncVariableIsValid($_GET, 'input_user', 'string');
$getinputedit = admFuncVariableIsValid($_GET, 'input_edit', 'boolean');
//$getinputlistid = admFuncVariableIsValid($_GET, 'input_id_list', 'int');
$getinputpadid = admFuncVariableIsValid($_GET, 'pad_id', 'int');

if ($getinputedit == true) {
    $sqledit = 'SELECT *, DATE_FORMAT (pad_date, \'%d.%m.%Y\') as date FROM adm_user_arbeitsdienst
               WHERE pad_id = ' . $getinputpadid;
    $listdata = array();
    $listdata = $gDb->query($sqledit);
    foreach ($listdata as $key => $item) {
        $userdata = $item;
    }
}

$gNavigation->addStartUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/arbeitsdienst.php', $headline);

// Abrechnungsjahr bestimmen
$datefilter = array();
$datefilter = getdatefilter();

// initialisieren des Abrechnungsjahres auf das Vorjahr
if ($getdatefilterid == 0) {
    $getdatefilterid = 2;
}
$datefilteractual = $datefilter[$getdatefilterid];

// Prüfen, ob Kategorie und User_Fields vorhanden sind oder installiert werden müssen
if (DBcategoriesID('PAD_ARBEITSDIENST') == 0) {
    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/' . 'install.php');
}

$pPreferences = new ConfigTablePAD();
$pPreferences->init(); // prüfen, ob die Tabelle adm_user_arbeitsdienst vorhanden ist
$pPreferences->read(); // Konfigurationsdaten auslesen

// alle aktiven Mitglieder einlesen
$members = list_members($datefilteractual, array(
    'FIRST_NAME',
    'LAST_NAME',
    'BIRTHDAY',
    'GENDER'
), array(
    'Mitglied' => 0
));

// Informationen aller Mitglieder zum Arbeitsdienst einslesen
$membersworkinfo = list_members_workinfo($members, $datefilteractual);

// Information der Gesamtstunden
$sumworking = sum_working($membersworkinfo, $pPreferences->config['Stunden']['Kosten']);

$headline = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE') . ' ' . $datefilteractual;

// create html page object
$page = new HtmlPage($headline);

$page->addCssFile(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/css/arbeitsdienst.css');
if ($getshowOption != '') {
    if (in_array($getshowOption, array(
        'input_work',
        'input_year',
        'input_cat',
    )) == true) {
        $navOption = 'management';
    } elseif (in_array($getshowOption, array(
        'export',
        'controlexport',
        'exportsepa'
    )) == true) {
        $navOption = 'export';
    } elseif (in_array($getshowOption, array(
        'overview',
        'overviewpayment',
        'overviewhistory'
    )) == true) {
        $navOption = 'overview';
    } else {
        $navOption = 'management';
    }

    $page->addJavascript('$("#tabs_nav_' . $navOption . '").attr("class", "active");
        $("#tabs-' . $navOption . '").attr("class", "tab-pane active");
        $("#collapse_' . $getshowOption . '").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_' . $getshowOption . '";', true);
} else {
    $page->addJavascript('$("#tabs_nav_management").attr("class", "active");
        $("#tabs-management").attr("class", "tab-pane active");
        ', true);
}

$page->addJavascript('$("#user_id").change(function () {
        $("#input_form_user").submit();
    });', true);

$page->addJavascript('$("#datefilter").change(function () {
        $("#input_form_date").submit();
    });', true);

// create module menu with back link
$headerMenu = new HtmlNavbar('menu_header', $headline, $page);

$form = new HtmlForm('navbar_static_display', '', $page, array(
    'type' => 'navbar',
    'setFocus' => false
));

if ( $gCurrentUser->isAdministrator()) {
	$form->addCustomContent('', '<table class="table table-condensed">
	            <tr>
	                <td style="text-align: right;"></td>
	                <td style="text-align: right;">' . $gL10n->get('PLG_ARBEITSDIENST_TOTAL') . ':</td>
	                <td style="text-align: right;">' . $sumworking['Sollstunden'] . '</td>    
	                <td>&#160;&#160;&#160;&#160; </td>
	                <td style="text-align: right;">' . $gL10n->get('PLG_ARBEITSDIENST_WORKING') . ':</td>
	                <td style="text-align: right;">' . $sumworking['Iststunden'] . '</td>
	                <td>&#160;&#160;&#160;&#160;</td>
	                <td style="text-align: right;">' . $gL10n->get('PLG_ARBEITSDIENST_MISSING') . ':</td>
	                <td style="text-align: right;">' . $sumworking['Fehlstunden'] . '</td>
	                <td>&#160;&#160;&#160;&#160;</td>
	                <td style="text-align: right;">' . $gL10n->get('PLG_ARBEITSDIENST_TOPAY') . ':</td>
	                <td style="text-align: right;">' . $sumworking['Kosten'] . ' €</td>
	            </tr>
	            <tr>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	                <td> </td>
	            </tr>
	        </table>');

	$headerMenu->addForm($form->show(false));
}

if ($gCurrentUser->isAdministrator()) {
    // show link to pluginpreferences
    $headerMenu->addItem('admMenuItemPreferencesLists', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/preferences.php', $gL10n->get('SYS_SETTINGS'), 'options.png', 'right');
}

$page->addHtml($headerMenu->show(false));

if ($gCurrentUser->isAdministrator()) {
	$page->addHtml('
	    <ul class="nav nav-tabs" id="preferences_tabs">
	        <li id="tabs_nav_management"><a href="#tabs-management" data-toggle="tab">' . $gL10n->get('PLG_ARBEITSDIENST_MANAGEMENT') . '</a></li>
	        <li id="tabs_nav_export"><a href="#tabs-export" data-toggle="tab">' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT') . '</a></li>
	        <li id="tabs_nav_overview"><a href="#tabs-overview" data-toggle="tab">' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW') . '</a></li>
	    </ul>');
}
else {
	$page->addHtml('
	    <ul class="nav nav-tabs" id="preferences_tabs">
	        <li id="tabs_nav_management"><a href="#tabs-management" data-toggle="tab">' . $gL10n->get('PLG_ARBEITSDIENST_MANAGEMENT') . '</a></li>
	    </ul>');
}

    
$page->addHtml('
	<div class="tab-content">  
        <div class="tab-pane" id="tabs-management">
            <div class="panel-group" id="accordion_management">');

				$page->addHtml('
                    <div class="panel panel-default" id="panel_input_year">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_input_year">
                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_YEAR') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_YEAR') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_YEAR') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_input_year" class="panel-collapse collapse">
                            <div class="panel-body">');
								// show form

								// Eingabe des abzurechnenden Jahres
								$form = new HtmlForm('input_form_date', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php?form=savedatefilter
																														&input_user=' . $getinputuser, $page);

								$form->openGroupBox('input_year');

								$form->addSelectBox('datefilter', $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEFILTER'), $datefilter, array(
									'defaultValue' => $getdatefilterid,
									'showContextDependentFirstEntry' => false,
									'multiselect' => FALSE
								));

								$form->closeGroupBox(); // input_year
								$page->addHtml($form->show(false));
								$page->addHtml('
                            </div>
                        </div>
                    </div>      
                ');
				$page->addHtml('
                <div class="panel panel-default" id="panel_input_work">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_input_work">
                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORK') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORK') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORK') . '
                                </a>
                            </h4>
                        </div>  
                        <div id="collapse_input_work" class="panel-collapse collapse">
                            <div class="panel-body">');
								// show form

								// Eingabe des Users
								$form = new HtmlForm('input_form_user', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php?form=saveuser
																																								 &input_id_datefilter=' . $getdatefilterid, $page);
								$calculationdate = date('Y-m-d', strtotime($datefilteractual . '-12-31'));
								
								$sqlDataUser['query'] = 'SELECT DISTINCT 
																usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name, SUBSTRING(last_name.usd_value,1,1) AS letter
															  FROM ' . TBL_MEMBERS . '
														INNER JOIN ' . TBL_ROLES . '
																ON rol_id = mem_rol_id
														INNER JOIN ' . TBL_CATEGORIES . '
																ON cat_id = rol_cat_id
														INNER JOIN ' . TBL_USERS . '
																ON usr_id = mem_usr_id
														 LEFT JOIN ' . TBL_USER_DATA . ' AS last_name
																ON last_name.usd_usr_id = usr_id
															   AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
														 LEFT JOIN ' . TBL_USER_DATA . ' AS first_name
																ON first_name.usd_usr_id = usr_id
															   AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
															 WHERE usr_valid  = 1
															   AND cat_org_id = ? -- ORG_ID
															   AND mem_begin <= ? -- $calculationdate
															   AND mem_end   >= ? -- $calculationdate
														  ORDER BY last_name.usd_value, first_name.usd_value, usr_id';

								$sqlDataUser['params'] = array( $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																ORG_ID,
																$calculationdate,
																$calculationdate);
																
								$form->openGroupBox('input_user');

								if ($gCurrentUser->isAdministrator()) {
									$form->addSelectBoxFromSql('user_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_USER'), $gDb, $sqlDataUser, array('property' => FIELD_REQUIRED,
																																				'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_USERSELECTION_DESC',
																																				'showContextDependentFirstEntry' => false,
																																				'firstEntry' => ' Bitte wählen ',
																																				'defaultValue' => $getinputuser,
																																				'multiselect' => FALSE));
								}
								else {
									$tempname = $members[$getUserId][LAST_NAME] . ', ' . $members[$getUserId][FIRST_NAME];
									$getinputuser = $getUserId;
									$form->addSelectBox('user_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_USER'), array($tempname), array(
											'defaultValue' => $getUserId,
											'showContextDependentFirstEntry' => false,
											'multiselect' => FALSE
									));
								}
								$form->closeGroupBox(); // input_user
								$page->addHtml($form->show(false));

								$form = new HtmlForm('input_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php?form=save' . '&input_edit=' . $getinputedit . '&input_user=' . $getinputuser . '&pad_id=' . $getinputpadid . '&input_id_datefilter=' . $getdatefilterid, $page);

								$form->openGroupBox('input_work');
								$form->addInput('date', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGDATE'), $userdata['date'], array(
									'property' => FIELD_REQUIRED,
									'type' => 'date',
									'maxLength' => 10
								));

								$sqlDataCat = 'SELECT DISTINCT cat_id, cat_name_intern 
											  FROM ' . TBL_CATEGORIES . ' 
											  WHERE cat_type = \'ADC\' 
											  AND cat_org_id = 1
											  ORDER BY cat_name_intern';

								if ($getinputedit == true) {
									$form->addSelectBoxFromSql('cat_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'), $gDb, $sqlDataCat, array(
										'property' => FIELD_REQUIRED,
										'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_CATSELECTION_DESC',
										'showContextDependentFirstEntry' => false,
										'defaultValue' => $userdata['pad_cat_id'],
										'multiselect' => FALSE
									));
								} else {
									$form->addSelectBoxFromSql('cat_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'), $gDb, $sqlDataCat, array(
										'property' => FIELD_REQUIRED,
										'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_CATSELECTION_DESC',
										'showContextDependentFirstEntry' => true,
										'defaultValue' => $gL10n->get('PLG_ARBEITSDIENST_SYS_FIRST_ITEM'),
										'multiselect' => FALSE
									));
								}

								$sqlDataPro = 'SELECT DISTINCT cat_id, cat_name_intern 
											   FROM ' . TBL_CATEGORIES . ' 
											   WHERE cat_type = \'ADV\' 
											   AND cat_org_id = 1
											   ORDER BY cat_name_intern';

								if (($getinputedit == true) and ($userdata['pad_pro_id'] != NULL)) {
									$form->addSelectBoxFromSql('pro_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'), $gDb, $sqlDataPro, array(
										'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_PROJECTSELECTION_DESC',
										'showContextDependentFirstEntry' => false,
										'defaultValue' => $userdata['pad_pro_id'],
										'multiselect' => FALSE
									));
								} else {
									$form->addSelectBoxFromSql('pro_id', $gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'), $gDb, $sqlDataPro, array(
										'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_PROJECTSELECTION_DESC',
										'showContextDependentFirstEntry' => true,
										'defaultValue' => $gL10n->get('PLG_ARBEITSDIENST_SYS_FIRST_ITEM'),
										'multiselect' => FALSE
									));
								}

								$form->addInput('discription', $gL10n->get('PLG_ARBEITSDIENST_DISCRIPTION'), $userdata['pad_name'], array(
									'maxLength' => 200,
									'property' => FIELD_REQUIRED
								));

								$form->addInput('hours', $gL10n->get('PLG_ARBEITSDIENST_INPUT_HOURS'), $userdata['pad_hours'], array(
									'maxLength' => 10,
									'type' => 'number',
									'step' => '0.5',
									'min' => '0',
									'max' => '20',
									'property' => FIELD_REQUIRED,
									''
								));
								if ($getinputedit == true) {

									$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_CHANGE'), array(
										'icon' => THEME_URL . '/icons/edit.png',
										'class' => ' col-sm-offset-3'
									));
								} else {
									$form->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array(
										'icon' => THEME_URL . '/icons/edit.png',
										'class' => ' col-sm-offset-3'
									));
								}

								$form->closeGroupBox(); // input_work

								$form->openGroupBox('input_overview');
								$sqlDataOverview = 'SELECT pad_id,
														   pad_user_id as user, 
														   categorie.cat_name_intern as cat,
														   project.cat_name_intern as proj, 
														   DATE_FORMAT (pad_date, \'%d.%m.%Y\') as date, 
														   pad_name as discription,
														   pad_hours as hours
													FROM        adm_user_arbeitsdienst
													INNER JOIN  adm_categories as categorie
													ON          categorie.cat_id = pad_cat_id
													LEFT JOIN   adm_categories as project
													ON          project.cat_id = pad_pro_id
													WHERE       pad_USER_id = ' . $getinputuser . ' 
													AND         year(pad_date) = \'' . $datefilter[$getdatefilterid] . '\'
													ORDER BY    pad_date';
								$datatable = false;
								$hoverRows = true;
								$classTable = 'table table-input_work';

								$table = new HtmlTable('table_input_work', $page, $hoverRows, $datatable, $classTable);
								$table->setColumnAlignByArray(array(
									'center',
									'center',
									'left',
									'left',
									'left',
									'right',
									'left'
								));

								$table->addRowHeadingByArray(array(
									'pad_id',
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGDATE'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_WORK'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_HOURS_TABLE'),
									'&nbsp;'
								));

								$result = array();
								$result = $gDb->query($sqlDataOverview);

								foreach ($result as $key => $item) {
									$sqlresult[$key] = $item;
									$lastcolumnedit = '<a class="admidio-icon-link" 
																							href="' . safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php', array(
										'form' => 'startedit',
										'input_user' => $getinputuser,
										'input_datefilter' => $datefilter,
										'input_id_datefilter' => $getdatefilterid,
										'pad_id' => $item['pad_id']
									)) . '">' . '<img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_EDIT_LIST') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_EDIT_LIST') . '" /></a>';

									$lastcolumndelete = '<a class="admidio-icon-link" 
																							href="' . safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php', array(
										'form' => 'delete',
										'input_user' => $getinputuser,
										'input_datefilter' => $datefilter,
										'input_id_datefilter' => $getdatefilterid,
										'pad_id' => $item['pad_id']
									)) . '">' . '<img src="' . THEME_URL . '/icons/delete.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_DELETE_LIST') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_DELETE_LIST') . '" /></a>';

									$table->addRowByArray(array(
										$item['pad_id'],
										$item['date'],
										$item['cat'],
										$item['proj'],
										$item['discription'],
										$item['hours'],
										$lastcolumnedit . '&nbsp;' . $lastcolumndelete
									));
								}
								$form->addHtml($table->show(false));
								$form->closeGroupBox(); // input_overview

								$form->openGroupBox('input_result');
								$datatable = false;
								$hoverRows = true;
								$classTable = 'tableresult table-input_result';

								$table = new HtmlTable('table_input_work', $page, $hoverRows, $datatable, $classTable);
								$table->setColumnAlignByArray(array(
									'center',
									'center',
									'center',
									'center',
									'center',
									'center',
									'right'
								));

								$table->addRowHeadingByArray(array(
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_AGE'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_PASSIV'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_TARGET'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_ACTUAL'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_DIFF'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_MISSING'),
									$gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_TOPAY')
								));
								// zu zahlenden Betrag errechnen
								$workingtopay = 0;
								$workingtopay = $membersworkinfo[$getinputuser]['Fehlstunden'] * $pPreferences->config['Stunden']['Kosten'];
								$workingtopay = number_format($workingtopay, 2);
								$workingtopay = $workingtopay . ' €';

								$table->addRowByArray(array(
									$membersworkinfo[$getinputuser]['ALTER'],
									$membersworkinfo[$getinputuser]['PASSIV'],
									$membersworkinfo[$getinputuser]['Sollstunden'],
									$membersworkinfo[$getinputuser]['Iststunden'],
									$membersworkinfo[$getinputuser]['Differenzstunden'],
									$membersworkinfo[$getinputuser]['Fehlstunden'],
									$workingtopay
								));
								$form->addHtml($table->show(false));
								$form->closeGroupBox(); // input_result

								$page->addHtml($form->show(false));
								$page->addHtml('
                            </div>
                        </div>
                    </div>      
                ');
				// only authorized user are allowed to start this module
				if ( $gCurrentUser->isAdministrator()) {
						
					
					$page->addHtml('
	                    <div class="panel panel-default" id="panel_input_cat">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_input_cat">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT') . '
	                                </a>
	                            </h4>
	                        </div>
	                      
	                        <div id="collapse_input_cat" class="panel-collapse collapse">
	                            <div class="panel-body">');
									// show form
									$form = new HtmlForm('input_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php?form=savecat', $page);
	
									$form->openGroupBox('input_hours');
	
									$form->addInput('input_cat', $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'), '', array(
										'maxLength' => 50,
										''
									));
	
									$datatable = false;
									$hoverRows = true;
									$classTable = 'table table-input_cat';
	
									$table = new HtmlTable('table_input_cat', $page, $hoverRows, $datatable, $classTable);
									$table->setColumnAlignByArray(array(
										'center',
										'center'
									));
	
									$table->addRowHeadingByArray(array(
										$gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT')
									));
	
									$sqlcat = 'SELECT       cat_name_intern as cat
											   FROM        ' . TBL_CATEGORIES . '                                                
											   WHERE        cat_type = \'ADC\'
											   ORDER BY     cat_name_intern';
	
									$result = array();
									$result = $gDb->query($sqlcat);
	
									foreach ($result as $key => $item) {
										$sqlresult = $item;
										$table->addRowByArray(array(
											$item['pad_id'],
											$item['cat']
										));
									}
									$form->addHtml($table->show(false));
									$form->closeGroupBox(); // input_hours
									$page->addHtml($form->show(false));
									$page->addHtml('
	                            </div>
	                        </div>
	                    </div>
					');
									$page->addHtml('
	                    <div class="panel panel-default" id="panel_input_build">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_management" href="#collapse_input_build">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD') . '
	                                </a>
	                            </h4>
	                        </div>
											
	                        <div id="collapse_input_build" class="panel-collapse collapse">
	                            <div class="panel-body">');
									// show form
									$form = new HtmlForm('input_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/input.php?form=savebuild', $page);
									
									$form->openGroupBox('input_build');
									
									$form->addInput('input_build', $gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD'), '', array(
											'maxLength' => 50,
											''
									));
									
									$datatable = false;
									$hoverRows = true;
									$classTable = 'table table-input_cat';
									
									$table = new HtmlTable('table_input_build', $page, $hoverRows, $datatable, $classTable);
									$table->setColumnAlignByArray(array(
											'center',
											'center'
									));
									
									$table->addRowHeadingByArray(array(
											$gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD')
									));
									
									$sqlbuild = 'SELECT       cat_name_intern as cat
											     FROM        ' . TBL_CATEGORIES . '
											     WHERE        cat_type = \'ADV\'
											     ORDER BY     cat_name_intern';
									
									$result = array();
									$result = $gDb->query($sqlbuild);
									
									foreach ($result as $key => $item) {
										$sqlresult = $item;
										$table->addRowByArray(array(
												$item['pad_id'],
												$item['cat']
										));
									}
									$form->addHtml($table->show(false));
									$form->closeGroupBox(); // input_build
									$page->addHtml($form->show(false));
									$page->addHtml('
	                            </div>
	                        </div>
	                    </div>
					');
				}
				$page->addHtml('
            </div>
        </div>');
		if ( $gCurrentUser->isAdministrator()) {
			$page->addHtml('
	        <div class="tab-pane" id="tabs-export">
	            <div class="panel-group" id="accordion_export">
	                    <div class="panel panel-default" id="panel_controlexport">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_export" href="#collapse_controlexport">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL') . '
	                                </a>
	                            </h4>
							</div>
							<div id="collapse_controlexport" class="panel-collapse collapse">
								<div class="panel-body">');
									// show form
									if ($typeofoutput == NULL) {
										$typeofoutput = 'CSVALL';
									}
									// Hier soll die Ausgabe der Kontrolldateien erfolgen
									$form = new HtmlForm('export_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/export.php?form=controlexport' . '&datefilteractual=' . $datefilteractual, $page);
									$form->openGroupBox('controlexport');
									$form->addCustomContent('', $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_INFO'));
									$htmlTable = '<table class="table table-export">
													<thead>
														<tr>
															<th> </th>
															<th style="text-align: center;font-weight:bold;">CSV (alle)</th>
															<th style="text-align: center;font-weight:bold;">CSV (Zahler)</th>
															<th style="text-align: center;font-weight:bold;">PDF (Zahler)</th>
														</tr>
													</thead>';
									$htmlTable .= ' <tbody>
														<td> </td>
														<td style="text-align: center"><input type="radio" name="typeofoutput" value="CSVALL" checked></td>
														<td style="text-align: center"><input type="radio" name="typeofoutput" value="CSVPAY" </td>
														<td style="text-align: center"><input type="radio" name="typeofoutput" value="PDFPAY" </td>
													</tbody>
												</table>';
									$form->addCustomContent($gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_CHECKBOX'), $htmlTable);
									$form->addSubmitButton('btn_export_control', $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_FILE'), array(
										'icon' => THEME_URL . '/icons/download.png',
										'class' => ' col-sm-offset-3'
									));
									$form->closeGroupBox(); // controlexport
									$page->addHtml($form->show(false));
									$page->addHtml('
								</div>
	                        </div>
	                    </div>
	                    <div class="panel panel-default" id="panel_exportsepa">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_export" href="#collapse_exportsepa">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA') . '
	                                </a>
	                            </h4>
							</div>
							<div id="collapse_exportsepa" class="panel-collapse collapse">
								<div class="panel-body">');
									// show form
									// HIer soll die Ausgabe der SEPA Dateien erfolgen
									$form = new HtmlForm('export_sepa_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/export.php?form=exportsepa' . '&datefilteractual=' . $datefilteractual, $page);
									$form->openGroupBox('exportsepa');
									$htmlTable = '<table class="table table-export">
													<thead>
														<tr>
															<th> </th>
															<th style="text-align: center;font-weight:bold;">' . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_DATE') . '</th>
															<th style="text-align: center;font-weight:bold;">FRST</th>
															<th style="text-align: center;font-weight:bold;">RCUR</th>
														</tr>
													</thead>';
									$strdatumtemp = $pPreferences->config['Datum']['Stichtag'];
									// $strdatumtemp in timestamp umwandeln und mit dem heutigen Datum vergleichen.
									// Liegt das Datum in der Vergangenheit, dann kein Datum anzeigen, sondern Hinweis, dass
									// ein Fälligkeitsdatum gesetzt werden muss.
									$datumtemp = strtotime($strdatumtemp);
									$jetzt = strtotime('now');
									/*
									if ($datumtemp < $jetzt) {
										$strdatum = $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_DATE_MESSAGE');
									} else {
										$strdatum = $strdatumtemp;
									}
									*/
									$strdatum = $strdatumtemp;
									$htmlTable .= ' <tbody>
														<td> </td>
														<td style="text-align: center;">' . $strdatum . '</td>
														<td style="text-align: center"><input type="radio" name="typeofoutput" value="CSVPAY" </td>
														<td style="text-align: center"><input type="radio" name="typeofoutput" value="PDFPAY" checked</td>
													</tbody>
												</table>';
									$form->addCustomContent($gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_CHECKBOX'), $htmlTable);
	
									$form->addCustomContent('', $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_INFO'));
									$form->addSubmitButton('btn_export_sepa_xml', $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_FILE'), array(
										'icon' => THEME_URL . '/icons/download.png',
										'class' => ' col-sm-offset-3'
									));
									$form->closeGroupBox(); // controlexport
									$page->addHtml($form->show(false));
									$page->addHtml('
								</div>
	                        </div>
	                    </div>
	            </div>        
	        </div>
	        ');
		
	        $page->addHtml(' 
	        <div class="tab-pane" id="tabs-overview">
	            <div class="panel-group" id="accordion_overview">
	                    <div class="panel panel-default" id="panel_overviewpayment">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_overview" href="#collapse_overviewpayment">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_PAYMENT') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_PAYMENT') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_PAYMENT') . '
	                                </a>
	                            </h4>
							</div>
							<div id="collapse_overviewpayment" class="panel-collapse collapse">
								<div class="panel-body">');
									// show form
									$form = new HtmlForm('overview_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/payments.php', $page);
									// Hier sollen z,B. Prüfungen gemacht werden
									$form->openGroupBox('overviewpayment');
	
									$form->addSubmitButton('btn_payments', $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_PAYMENTS'), array(
										'icon' => THEME_URL . '/icons/edit.png',
										'class' => ' col-sm-offset-3'
									));
									$form->addCustomContent('', '<br/>' . $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_PAYMENTS_DESC'));
	
									$form->closeGroupBox(); // options
									$page->addHtml($form->show(false));
									$page->addHtml('
								</div>
	                        </div>
	                    </div>
	                    ');
						$page->addHtml('
	                    <div class="panel panel-default" id="panel_overviewhistory">
	                        <div class="panel-heading">
	                            <h4 class="panel-title">
	                                <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_overview" href="#collapse_overviewhistory">
	                                    <img src="' . THEME_URL . '/icons/edit.png" alt="' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_HISTORY') . '" title="' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_HISTORY') . '" />' . $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_HISTORY') . '
	                                </a>
	                            </h4>
							</div>
							<div id="collapse_overviewhistory" class="panel-collapse collapse">
								<div class="panel-body">');
									// show form
									$form = new HtmlForm('overview_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/history.php', $page);
									// Hier sollen z,B. Prüfungen gemacht werden
									$form->openGroupBox('overviewhistory');
	
									$form->addSubmitButton('btn_payments', $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_HISTORY_EDIT'), array(
										'icon' => THEME_URL . '/icons/edit.png',
										'class' => ' col-sm-offset-3'
									));
									$form->addCustomContent('', '<br/>' . $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_HISTORY_DESC'));
	
									$form->closeGroupBox(); // options
									$page->addHtml($form->show(false));
									$page->addHtml('
								</div>
	                        </div>
	                    </div>
	                </div>
	                
	        </div>
			');
			}
			$page->addHtml('
    </div>
');

$page->show();