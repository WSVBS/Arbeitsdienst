<?php
/*
 ***********************************************************************************************
 *
 * Arbeitsdienst
 *
 * Version 1.4.0
 *
 * Dieses Plugin berechnet Arbeitsstunden.
 *
 * Author: WSVBS
 *
 * Compatible with Admidio version 5.0.x (geprüft bis 5.0 Beta3)
 *
 * @copyright 2018-2025 WSVBS
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

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Admidio\Changelog\Service\ChangelogService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Fehlermeldungen anzeigen
// error_reporting(E_ALL);
require_once (__DIR__ . '/../../../system/common.php');
require_once (__DIR__ . '/../../../system/login_valid.php');
require_once (__DIR__ . '/../../../system/classes/HtmlForm.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/../classes/configtable.php');

$typeofoutput = NULL;


// script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
$_SESSION['pArbeitsdienst']['script_name'] = substr($_SERVER['SCRIPT_NAME'], 
                                                    strpos($_SERVER['SCRIPT_NAME'], 
                                                                   FOLDER_PLUGINS));

// Plugin kann gestartet werden, wenn Scriptname dem Menüeintrag im Menü entspricht
if (!isUserAuthorized())
{
    throw new Exception('SYS_NO_RIGHTS');   
}

// Initialize and check the parameters
$getUserId = admFuncVariableIsValid($_GET, 
                                    'user_id', 
                                    'int', 
                                    array('defaultValue' => (int) $gCurrentUser->getValue('usr_id')));
$getshowOption = admFuncVariableIsValid($_GET, 
                                    'show_option', 
                                    'string');
$getformoutput = admFuncVariableIsValid($_GET, 
                                    'formoutput', 
                                    'string');
$getdatefilterid = admFuncVariableIsValid($_GET, 
                                          'input_id_datefilter', 
                                          'int');
$getinputuser = admFuncVariableIsValid($_GET, 
                                       'input_user', 
                                       'string');
$getinputedit = admFuncVariableIsValid($_GET, 
                                       'input_edit', 
                                       'boolean');
//$getinputlistid = admFuncVariableIsValid($_GET, 'input_id_list', 'int');
$getinputpadid = admFuncVariableIsValid($_GET, 
                                        'pad_id', 
                                        'int');

if (empty($_POST['typeofoutput']))
{
    $gettypeofoutput = 'CSVALL';
}
else
{
    $gettypeofoutput = $_POST['typeofoutput'];
}


if (empty($getUserId))
{
	$getUserId = 0;
}
if (empty($getinputuser))
{
	$getinputuser = $getUserId;
}

if (empty($getshowOption))
{
    $getshowOption = 'main';
}

if (empty($getoutputfile))
{
    $getoutputfile = null;
}

$userdata = array();
$userdata['date'] = '';
$userdata['pad_name'] = '';
$userdata['pad_hours'] = '';


if ($getinputedit == true) {
    $sqledit = 'SELECT *, DATE_FORMAT (pad_date, \'%d.%m.%Y\') as date FROM adm_user_arbeitsdienst
               WHERE pad_id = ' . $getinputpadid;
    $listdata = array();
    $listdata = $gDb->query($sqledit);
    foreach ($listdata as $key => $item) {
        $userdata = $item;
    }
}

// Abrechnungsjahr bestimmen
$datefilter = array();
$datefilter = getdatefilter();

// initialisieren des Abrechnungsjahres auf das Vorjahr
if ($getdatefilterid == 0) {
    $getdatefilterid = 3;
}
$datefilteractual = $datefilter[$getdatefilterid];

$title = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE');
$headline = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE') . ' - ' . $datefilteractual;

$gNavigation->addStartUrl(CURRENT_URL, 
                         $headline, 
                         'bi-list-stars');

// create html page object
$page = PagePresenter::withHtmlIDAndHeadline('plg-arbeitsdienst-main');
$page->addTemplateFolder(ADMIDIO_URL . FOLDER_PLUGINS . 'arbeitsdienst/templates');

// Prüfen, ob Kategorie und User_Fields vorhanden sind oder installiert werden müssen
//if (DBcategoriesID('PAD_ARBEITSDIENST') == 0)
    
//{

$pPreferences = new ConfigTablePAD();
$pPreferences->init(); // prüfen, ob die Tabelle adm_user_arbeitsdienst vorhanden ist
$pPreferences->read(); // Konfigurationsdaten auslesen


// alle aktiven Mitglieder einlesen
$members = list_members($datefilteractual, 
                        array(
                            'FIRST_NAME',
                            'LAST_NAME',
                            'BIRTHDAY',
                            'GENDER'), 
                        array('Mitglied' => 0));

                     
// Informationen aller Mitglieder zum Arbeitsdienst einslesen
$membersworkinfo = list_members_workinfo($members, 
                                         $datefilteractual);

// Information der Gesamtstunden
$sumworking = sum_working($membersworkinfo, 
                          $pPreferences->config['Stunden']['Kosten']);


//#############################################################################
//  Ausgabe des Statistikdisplays
//  show Static Display in Header

if ($gCurrentUser->isAdministratorUsers()) 
{
    $formStaticDisplay = new FormPresenter(
                    'formStaticDisplay',
                    __DIR__ . '/../templates/arbeitsdienst_statisticdisplay.tpl',
                    '',
                    $page,
                    array(
                        'class' => "collapse navbar-collapse"));

    if ( $gCurrentUser->isAdministrator()) 
    {
        $formStaticDisplay->addDescription('plg_arbeitsdienst_total',
                                        $gL10n->get('PLG_ARBEITSDIENST_TOTAL') . ': ' . $sumworking['Sollstunden']);

        $formStaticDisplay->addDescription('plg_arbeitsdienst_working',
                                        $gL10n->get('PLG_ARBEITSDIENST_WORKING') . ': ' . $sumworking['Iststunden']);

        $formStaticDisplay->addDescription('plg_arbeitsdienst_missing',
                                        $gL10n->get('PLG_ARBEITSDIENST_MISSING') . ': ' . $sumworking['Fehlstunden']);

        $formStaticDisplay->addDescription('plg_arbeitsdienst_topay',
                                        $gL10n->get('PLG_ARBEITSDIENST_TOPAY') . ': ' . $sumworking['Kosten'] . '€');
        
        $formStaticDisplay->addToHtmlPage();
    }
}
//#############################################################################



//#############################################################################
//  Ausgabe der Menueschalter
//
//  hier muss noch geprüft werden, ob admin oder nicht --> funktioniert so noch nicht

 if ($gCurrentUser->isAdministrator()) 
{
    // show link to pluginpreferences
    $page->addPageFunctionsMenuItem(
        'admMenuItemPreferencesLists', 
        $gL10n->get('SYS_SETTINGS'), 
        ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/preferences.php?form=configuration',  
        'bi-gear-fill');

}
if ($getshowOption != 'main')
{
    // show link to pluginMain
    $page->addPageFunctionsMenuItem(
        'admMenuItemMainLists', 
        $gL10n->get('PLG_ARBEITSDIENST_TEMPLATE_EINGABE'), 
        ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/arbeitsdienst.php?show_option=main',  
        '');
}

if ($gCurrentUser->isAdministrator()) 
{
    if (($getshowOption != 'exportsepa') && ($getshowOption != 'controlexport') && ($getshowOption != 'export'))
    {
        // show link to pluginExport
        $page->addPageFunctionsMenuItem(
            'admMenuItemExportLists', 
            $gL10n->get('PLG_ARBEITSDIENST_EXPORT'), 
            ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/arbeitsdienst.php?show_option=export' .  
                                                                                        '&input_id_datefilter=' . $getdatefilterid .
                                                                                        '&input_user=' . $getinputuser,
            '');
    }

    if ($getshowOption != 'overview')
    {
        // show link to pluginExport
        $page->addPageFunctionsMenuItem(
            'admMenuItemOverviewLists', 
            $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW'), 
            ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/arbeitsdienst.php?show_option=overview',  
            '');
    }
}

//Plugin Kopf angeben
$page->setHeadline($headline);

$linkstring = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/arbeitsdienst.php', array('input_id_datefilter' => $getdatefilterid));                                                                                                                       
$javascriptCode = '
    // auf aktuellen User filtern
    $("#plg_arbeitsdienst_input_user").change(function () 
    { 
        var user = $(this).val();
        var link = "'. $linkstring .'&input_user=" + user;
        //alert(link);
        //alert(user);
        window.location.replace("'. $linkstring .'&input_user=" + user);

    });';
$page->addJavascript($javascriptCode,true);

$linkstring = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/arbeitsdienst.php', array('input_user' => $getinputuser));                                                                                                                       
$javascriptCode = '
    // auf aktuellen User filtern
    $("#plg_arbeitsdienst_input_year").change(function () 
    { 
        var datumjahr = $(this).val();
        var link = "'. $linkstring .'&input_id_datefilter=" + datumjahr;
        //alert(link);
        //alert(datumjahr);
        window.location.replace("'. $linkstring .'&input_id_datefilter=" + datumjahr);

    });';
$page->addJavascript($javascriptCode,true);
//#############################################################################
//  Ausgabe der Haupseite MAIN
//#############################################################################

if ($getshowOption == 'main')
{
    //#############################################################################
    //  Abrechnungjahr und Person auswählen
    //#############################################################################
    $form = new FormPresenter('input_form_zuordnung', 
                            __DIR__ . '/../templates/arbeitsdienst_input_year_name.tpl', 
                            '', 
                            $page,
                            array('class' => 'form-preferences'));

    $subHeadline = $gL10n->get('PLG_ARBEITSDIENST_TEMPLATE_EINGABE');
    $page->addHtml('<h5 class="admidio-content-subheader">' . $subHeadline . '</h5>');

    if ($gCurrentUser->isAdministrator()) {
        $form->addSelectBox('plg_arbeitsdienst_input_year', 
                            $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEFILTER'), 
                                                $datefilter, 
                                                array( 'defaultValue' => $getdatefilterid,
                                                                'showContextDependentFirstEntry' => false,
                                                                'multiselect' => FALSE ));
    }
    else
    {
        $form->addSelectBox('plg_arbeitsdienst_input_year', 
                            $gL10n->get('PLG_ARBEITSDIENST_INPUT_DATEFILTER'), 
                                                array($datefilteractual), 
                                                array( 'defaultValue' => $getdatefilterid,
                                                                'firstEntry' => $datefilteractual));
    }
    
    $calculationdate = date('Y-m-d', 
                            strtotime($datefilteractual . '-12-31'));
    $sqlDataUser['query'] = 'SELECT DISTINCT usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name, SUBSTRING(last_name.usd_value,1,1) AS letter
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
                                    ORDER BY name'; //last_name.usd_value, first_name.usd_value, usr_id';
        
    $sqlDataUser['params'] = array( $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
                                    $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
                                    ORG_ID,
                                    $calculationdate,
                                    $calculationdate);

    $tempname = $members[$getUserId]['LAST_NAME'] . ', ' . $members[$getUserId]['FIRST_NAME'];
    //$getinputuser = $getUserId;    
    if ($gCurrentUser->isAdministrator()) 
    {
        $form->addSelectBoxFromSql('plg_arbeitsdienst_input_user', 
                                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_USER'),
                                                        $gDb, 
                                                        $sqlDataUser, 
                                                        array( 'property' => HtmlForm::FIELD_REQUIRED,
                                                                        'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_USERSELECTION_DESC',
                                                                        'showContextDependentFirstEntry' => false,
                                                                        'firstEntry' => ' Bitte wählen ',
                                                                        'defaultValue' => $getinputuser,
                                                                        'multiselect' => FALSE));
            
    }
    else 
    {
        $tempname = $members[$getUserId]['LAST_NAME'] . ', ' . $members[$getUserId]['FIRST_NAME'];
        $getinputuser = $getUserId;
        $form->addSelectBox('plg_arbeitsdienst_input_user', 
                            $gL10n->get('PLG_ARBEITSDIENST_INPUT_USER'), 
                            array($tempname), 
                            array('defaultValue' => $getUserId,
                                            'showContextDependentFirstEntry' => false,
                                            'multiselect' => FALSE));
    }



    $form->addToHtmlPage();
    $gCurrentSession->addFormObject($form);

    //#############################################################################
    //  Eingabe der Arbeitsdienstdaten
    //#############################################################################
    $forminput = new FormPresenter('input_form_eingabe', 
                                    __DIR__ . '/../templates/arbeitsdienst_input_input.tpl', 
                                    ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/input.php?form=save' . 
                                                                                            '&input_edit=' . $getinputedit . 
                                                                                            '&input_user=' . $getinputuser . 
                                                                                            '&pad_id=' . $getinputpadid . 
                                                                                            '&input_id_datefilter=' . $getdatefilterid, 
                                    $page, 
                                    array('class' => 'form-preferences'));

    $forminput->addInput('date', 
                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGDATE'), 
                        $userdata['date'],
                        array('property' => FormPresenter::FIELD_REQUIRED,
                                        'type' => 'date',
                                        'maxLength' => 10));

    $sqlDataCat = 'SELECT DISTINCT cat_id, cat_name_intern
                    FROM ' . TBL_CATEGORIES . '
                    WHERE cat_type = \'ADC\'
                    AND cat_org_id = 1
                    ORDER BY cat_name_intern';

    if ($getinputedit == true) 
    {
        $forminput->addSelectBoxFromSql('cat_id', 
                                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'), 
                                        $gDb, 
                                        $sqlDataCat, 
                                        array('property' => FormPresenter::FIELD_REQUIRED,
                                                    'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_CATSELECTION_DESC',
                                                    'showContextDependentFirstEntry' => false,
                                                    'defaultValue' => $userdata['pad_cat_id'],
                                                    'multiselect' => FALSE));
    } 
    else 
    {
        $forminput->addSelectBoxFromSql('cat_id', 
                                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'), 
                                        $gDb, 
                                        $sqlDataCat, 
                                        array('property' => FormPresenter::FIELD_REQUIRED,
                                                    'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_CATSELECTION_DESC',
                                                    'showContextDependentFirstEntry' => true,
                                                    'multiselect' => FALSE));
    }

    $sqlDataPro = 'SELECT DISTINCT cat_id, cat_name_intern
                    FROM ' . TBL_CATEGORIES . '
                    WHERE cat_type = \'ADV\'
                    AND cat_org_id = 1
                    ORDER BY cat_name_intern';
                
    if (($getinputedit == true) and ($userdata['pad_pro_id'] != NULL)) {
        $forminput->addSelectBoxFromSql('pro_id', 
                                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'), 
                                        $gDb, 
                                        $sqlDataPro, 
                                        array( 'helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_PROJECTSELECTION_DESC',
                                                        'showContextDependentFirstEntry' => false,
                                                        'defaultValue' => $userdata['pad_pro_id'],
                                                        'multiselect' => FALSE));
    } 
    else {
        $forminput->addSelectBoxFromSql('pro_id', 
                                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'), 
                                        $gDb, 
                                        $sqlDataPro, 
                                        array('helpTextIdLabel' => 'PLG_ARBEITSDIENST_CHOOSE_PROJECTSELECTION_DESC',
                                                    'showContextDependentFirstEntry' => true,
                                                    'defaultValue' => $gL10n->get('PLG_ARBEITSDIENST_SYS_FIRST_ITEM'),
                                                    'multiselect' => FALSE));
    }

    $forminput->addInput('discription', 
                        $gL10n->get('PLG_ARBEITSDIENST_DISCRIPTION'), 
                        $userdata['pad_name'], 
                        array('maxLength' => 200,
                                        'property' => FormPresenter::FIELD_REQUIRED));
                
    $forminput->addInput('hours', 
                        $gL10n->get('PLG_ARBEITSDIENST_INPUT_HOURS'), 
                        $userdata['pad_hours'], 
                        array('maxLength' => 10,
                                        'type' => 'number',
                                        'step' => '0.5',
                                        'min' => '0',
                                        'max' => '20',
                                        'property' => FormPresenter::FIELD_REQUIRED,
                                        ''));

    if ($getinputedit == true) 
    {          
        $forminput->addSubmitButton('btn_input_save', 
                                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_CHANGE'), 
                                    array('icon' => 'bi-check-lg'));
    } 
    else {
        $forminput->addSubmitButton('btn_input_save', 
                                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), 
                                    array('icon' => 'bi-check-lg'));
    }   
    $forminput->addToHtmlPage();
    $gCurrentSession->addFormObject($forminput);


    //#############################################################################
    //  Auflisten der Arbeiten
    //#############################################################################

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

    $result = array();
    $result = $gDb->query($sqlDataOverview);

    foreach ($result as $key => $item) 
    {
        $lastcolumnedit = '';
        $lastcolumndelete = '';

        //$sqlresult[$key] = $item;
        
        $lastcolumnedit = '<a class="admidio-icon-link"	href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/input.php', array('form' => 'startedit',
                                                                                                                                                                'input_user' => $getinputuser,
                                                                                                                                                                'input_datefilter' => $datefilter,
                                                                                                                                                                'input_id_datefilter' => $getdatefilterid,
                                                                                                                                                                'pad_id' => $item['pad_id'])) 
                            . '">' . 
                            '<i class="bi bi-pencil-square" data-toggle="tooltip" title="' . $gL10n->get('PLG_ARBEITSDIENST_EDIT_LIST') . '" /></i>
                            </a>';			
        
        $lastcolumndelete = '<a class="admidio-icon-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/input.php', array('form' => 'delete',
                                                                                                                                                                'input_user' => $getinputuser,
                                                                                                                                                                'input_datefilter' => $datefilter,
                                                                                                                                                                'input_id_datefilter' => $getdatefilterid,
                                                                                                                                                                'pad_id' => $item['pad_id'])) 
                            . '">' . 
                            '<i class="bi bi-trash" data-toggle="tooltip" title="' . $gL10n->get('PLG_ARBEITSDIENST_DELETE_LIST') . '" /></i>
                            </a>';

        $item["schalter"] = $lastcolumnedit . '&nbsp;' . $lastcolumndelete;
        
        $sqlresult[$key] = $item;

    }

    if (empty($sqlresult))
    {
        $resultempty = true;
    }
    else
    {
        $resultempty = false;
    }

    $smarty = $page->createSmartyObject();

    $smarty->assign('pad_daten_vorhanden', $resultempty);
    //Header der Tabelle
    $smarty->assign('pad_id', 'pad_id');
    $smarty->assign('header_workingdate', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORKINGDATE'));
    $smarty->assign('header_cat', $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT'));
    $smarty->assign('header_project', $gL10n->get('PLG_ARBEITSDIENST_INPUT_PROJECT'));
    $smarty->assign('header_work', $gL10n->get('PLG_ARBEITSDIENST_INPUT_WORK'));
    $smarty->assign('header_hours_table', $gL10n->get('PLG_ARBEITSDIENST_INPUT_HOURS_TABLE'));

    if (!empty($sqlresult))
    {
        $smarty->assign('result', $sqlresult);
    }



    //#############################################################################
    //  Zusammenfassung
    //#############################################################################

    // zu zahlenden Betrag errechnen
    $workingtopay = 0;
    if ($getinputuser != 0)
    {
        $workingtopay = $membersworkinfo[$getinputuser]['Fehlstunden'] * $pPreferences->config['Stunden']['Kosten'];
    }
    $workingtopay = number_format($workingtopay, 2);
    $workingtopay = $workingtopay . ' €';

    //Tabellenkopf
    $smarty->assign('header_result_age', 
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_AGE'));
    $smarty->assign('header_result_passive', 
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_PASSIV'));
    $smarty->assign('header_result_target', 
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_TARGET'));
    $smarty->assign('header_result_actual',     
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_ACTUAL'));
    $smarty->assign('header_result_diff',                                                
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_DIFF'));
    $smarty->assign('header_result_missing',                                                
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_MISSING'));
    $smarty->assign('header_result_topay',                                                
                    $gL10n->get('PLG_ARBEITSDIENST_INPUT_RESULT_TOPAY'));

    //Ergebnisse ausgeben
    if ($getinputuser != 0)
    {
        
        $smarty->assign('overview_result_alter', $membersworkinfo[$getinputuser]['ALTER']);
        $smarty->assign('overview_result_passiv', $membersworkinfo[$getinputuser]['PASSIV']);
        $smarty->assign('overview_result_soll', $membersworkinfo[$getinputuser]['Sollstunden']);
        $smarty->assign('overview_result_ist', $membersworkinfo[$getinputuser]['Iststunden']);
        $smarty->assign('overview_result_diff', $membersworkinfo[$getinputuser]['Differenzstunden']);
        $smarty->assign('overview_result_fehl', $membersworkinfo[$getinputuser]['Fehlstunden']);
        $smarty->assign('overview_result_topay', $workingtopay);
    }
    else
    {
        $smarty->assign('overview_resul_alter', '');
        $smarty->assign('overview_result_passiv', '');
        $smarty->assign('overview_result_soll', '');
        $smarty->assign('overview_result_ist', '');
        $smarty->assign('overview_result_diff', '');
        $smarty->assign('overview_result_fehl', '');
        $smarty->assign('overview_result_topay', '');
    }


    $htmlTable = $smarty->fetch(__DIR__ . '/../templates/arbeitsdienst_input_overview.tpl');
    $page->addHtml($htmlTable);



    //#############################################################################
    //  Eingabe der Kategorie (nur Admin)
    //#############################################################################
    if ($gCurrentUser->isAdministrator()) 
    {
        $formInputCat = new FormPresenter('form_input_cat', 
                                        __DIR__ . '/../templates/arbeitsdienst_input_cat.tpl',
                                        ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/input.php?form=savecat'.
                                                                                                '&input_edit=' . $getinputedit . 
                                                                                                '&input_user=' . $getinputuser . 
                                                                                                '&pad_id=' . $getinputpadid . 
                                                                                                '&input_id_datefilter=' . $getdatefilterid, 
                                        $page);

        $formInputCat->addInput('input_cat', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_CAT_NEW'), 
                                '', 
                                array('maxLength' => 50,'' ));

        $sqlcat = 'SELECT cat_id, cat_name_intern as cat
                    FROM        ' . TBL_CATEGORIES . '
                    WHERE        cat_type = \'ADC\'
                    ORDER BY     cat_name_intern';
                    
        $formInputCat->addSelectBoxFromSql('show_cat', 
                                        '',
                                        $gDb, 
                                        $sqlcat,
                                        array('firstEntry' => '-- vorhandene Kategorien --'));
                                                                                    
        $formInputCat->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                                    'class' => ' offset-sm-3'));
                                                                                                                    
        $formInputCat->addToHtmlPage(false);
        $gCurrentSession->addFormObject($formInputCat);
    }


    //#############################################################################
    //  Eingabe der Bauvorhaben (nur Admin)
    //#############################################################################
    if ($gCurrentUser->isAdministrator()) 
    {
        $formInputbuild = new FormPresenter('form_input_build', 
                                        __DIR__ . '/../templates/arbeitsdienst_input_build.tpl',
                                        ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/input.php?form=savebuild'.
                                                                                                '&input_edit=' . $getinputedit . 
                                                                                                '&input_user=' . $getinputuser . 
                                                                                                '&pad_id=' . $getinputpadid . 
                                                                                                '&input_id_datefilter=' . $getdatefilterid, 
                                        $page);

        $formInputbuild->addInput('input_build', 
                                $gL10n->get('PLG_ARBEITSDIENST_INPUT_BUILD_NEW'), 
                                '', 
                                array('maxLength' => 50,'' ));

        $sqlbuild = 'SELECT cat_id, cat_name_intern as cat
                    FROM        ' . TBL_CATEGORIES . '
                    WHERE        cat_type = \'ADV\'
                    ORDER BY     cat_name_intern';
                    
        $formInputbuild->addSelectBoxFromSql('show_build', 
                                        '',
                                        $gDb, 
                                        $sqlbuild,
                                        array('firstEntry' => '-- vorhandene Bauvorhaben --'));
                                                                                    
        $formInputbuild->addSubmitButton('btn_input_save', $gL10n->get('PLG_ARBEITSDIENST_INPUT_SAVE'), array('icon' => 'fa-save',
                                                                                                                    'class' => ' offset-sm-3'));
                                                                                                                    
        $formInputbuild->addToHtmlPage(false);
        $gCurrentSession->addFormObject($formInputbuild);

    }
}



//#############################################################################
//  Ausgabe der Exportseite
//#############################################################################

if ($getshowOption == 'export')
{
    $subHeadline = 'Export';
    $page->addHtml('<h5 class="admidio-content-subheader">' . $subHeadline . '</h5>');

    if ($typeofoutput == NULL) 
    {
        $typeofoutput = 'CSVALL';
    }

    
    //#############################################################################
    //  Ausgabe Kontrolle
    //#############################################################################
    $formexport = new FormPresenter('export_form', 
                                    __DIR__ . '/../templates/arbeitsdienst_export_control.tpl', 
                                    ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/export.php?form=controlexport' .
                                                                                            '&datefilteractual=' . $datefilteractual , 
                                    $page, 
                                    array('class' => 'form-preferences'));

    $formexport->addDescription('arbeitsdienst_export_control_info',
                               $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_INFO'));

    $formexport->addRadioButton('typeofoutput',
                               '',
                               array( 'CSVALL' => $gL10n->get('PLG_ARBEITSDIENST_EXPORT_KONTROL_PRINT_CSVALL'),
                                              'CSVPAY' => $gL10n->get('PLG_ARBEITSDIENST_EXPORT_KONTROL_PRINT_CSVPAY')),
                                              array('defaultValue' => 'CSVALL'));
    
    $formexport->addSubmitButton('btn_export_control',
                                $gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_FILE'),
                                array('icon' => 'bi-check-lg' ));

    //$formexport->addHtml($formexport);
    $formexport->addToHtmlPage(false);
    $gCurrentSession->addFormObject($formexport);

   

    //#############################################################################
    //  Ausgabe SEPA
    //#############################################################################

    $formexportsepa = new FormPresenter('exportsepa_form', 
                                    __DIR__ . '/../templates/arbeitsdienst_export_sepa.tpl', 
                                    ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/export.php?form=exportsepa' . 
                                                                                            '&datefilteractual=' . $datefilteractual, 
                                    $page, 
                                    array('class' => 'form-preferences'));


    $formexportsepa->addDescription('arbeitsdienst_export_sepa_info',
                               $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_INFO'));

    $strdatumtemp = $pPreferences->config['Datum']['Stichtag'];
    // $strdatumtemp in timestamp umwandeln und mit dem heutigen Datum vergleichen.
    // Liegt das Datum in der Vergangenheit, dann kein Datum anzeigen, sondern Hinweis, dass
    // ein Fälligkeitsdatum gesetzt werden muss.
    
    $datumtemp = strtotime($strdatumtemp);
    $jetzt = strtotime('now');

    $strdatum = $strdatumtemp;

    

    $formexportsepa->addDescription('plg_arbeitsdienst_faelligkeitsdatum',
                               $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_DATE'));

    $formexportsepa->addDescription('plg_arbeitsdienst_faelligkeitsdatum_wert',
                                   $strdatum = $strdatumtemp);
;

    $formexportsepa->addRadioButton('typeofsepaoutput',
                               '',
                               array( 'FRST' => $gL10n->get('PLG_ARBEITSDIENST_EXPORT_KONTROL_SEPA_FRST'),
                                              'RCUR' => $gL10n->get('PLG_ARBEITSDIENST_EXPORT_KONTROL_SEPA_RCUR')),
                                              array('defaultValue' => 'RCUR'));

    $formexportsepa->addDescription('plg_arbeitsdienst_sequenztyp',
                               $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_SEQUENZTYP'));


    $formexportsepa->addSubmitButton('btn_export_sepa_xml',
                                $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_FILE'),
                                array('icon' => 'bi-check-lg' ));

    $formexportsepa->addToHtmlPage(false);
    $gCurrentSession->addFormObject($formexportsepa);   
}


//#############################################################################
//  Ausgabe der Zahlungsübersicht
//#############################################################################


if ($getshowOption == 'overview')
{
    $subHeadline = 'Zahlungsübersicht';
    $page->addHtml('<h5 class="admidio-content-subheader">' . $subHeadline . '</h5>');

    
    
    //#############################################################################
    //  Ausgabe aktuelle Zahlungen
    //#############################################################################
    

    $formpayment = new FormPresenter('form_overview', 
                                    __DIR__ . '/../templates/arbeitsdienst_overview_main_1.tpl', 
                                    ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/payments.php?show_option=overview_payment', 
                                    $page, 
                                    array('class' => 'form-preferences'));

    
    $formpayment->addDescription('arbeitsdienst_contribution_payments_desc',
                               $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_PAYMENTS_DESC'));

    $formpayment->addSubmitButton('btn_contribution_payment',
                                $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_PAYMENTS'),
                                array('icon' => 'bi-check-lg' ));

    $formpayment->addToHtmlPage(false);
    $gCurrentSession->addFormObject($formpayment);

    //#############################################################################
    //  Ausgabe Zahlungshistorie
    //  diese Funktionalität ist derzeit deaktiviert
    //  es ist unklar, was mit der Tabelle tbl_user_log passiert ist 
    //#############################################################################
/*
    $formhistory = new FormPresenter('form_overview', 
                                    __DIR__ . '/../templates/arbeitsdienst_overview_main_2.tpl', 
                                    ADMIDIO_URL . FOLDER_PLUGINS . '/arbeitsdienst/system/history.php', 
                                    $page, 
                                    array('class' => 'form-preferences'));

    $formhistory->addDescription('arbeitsdienst_contribution_history_desc',
                               $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_HISTORY_DESC'));

    $formhistory->addSubmitButton('btn_contribution_history',
                                $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_HISTORY_EDIT'),
                                array('icon' => 'bi-check-lg' ));

    $formhistory->addToHtmlPage(false);
    $gCurrentSession->addFormObject($formhistory);
*/
}

$page->show();