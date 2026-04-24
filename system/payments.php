<?php

/***********************************************************************************************
 * Verarbeiten der Menueeinstellungen des Admidio-Plugins Arbeitsstunden / Zahlungsübersicht
 *
 * @copyright 2018-2023 WSVBS,       The Admidio Team
 * @see       https://wsv-bs.de,     https://www.admidio.org/
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 *  Hinweis: Funktion vom Plugin Mitgliederbeitrag übernommen
 * 
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beitraegen
 *                    assign - Setzen eines Bezahlt-Datums
 * usr_id           : Id des Benutzers, fuer den das Bezahlt-Datum gesetzt/geloescht wird
 * datum_neu        : das neue Bezahlt-Datum
 * mem_show_choice  : 0 - (Default) Alle Benutzer anzeigen
 *                    1 - Nur Benutzer anzeigen, bei denen ein Bezahlt-Datum vorhanden ist
 *                    2 - Nur Benutzer anzeigen, bei denen kein Bezahlt-Datum vorhanden ist
 * full_screen      : 0 - Normalbildschirm
 *                    1 - Vollbildschirm
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Entity\Entity;
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

require_once (__DIR__ . '/../../../system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/payments_function.php');
require_once (__DIR__ . '/../classes/configtable.php');

try
{
    $getshowOption = admFuncVariableIsValid($_GET, 
                                        'show_option', 
                                        'string');
    $getUserId = admFuncVariableIsValid($_GET, 
                                        'usr_id', 
                                        'numeric', 
                                        array('defaultValue' => 0,'directOutput' => true));
    $getDatumNeu = admFuncVariableIsValid($_GET, 
                                          'datum_neu', 
                                          'date');
    $getMembersShow = admFuncVariableIsValid($_GET, 
                                            'mem_show_choice', 
                                            'numeric', 
                                            array('defaultValue' => 0));
    $getMode = admFuncVariableIsValid($_GET, 
                                      'mode', 
                                      'string', 
                                      array('defaultValue' => 'html','validValues' => array('html','assign')));
    $getform = admFuncVariableIsValid($_GET, 
                                      'form', 
                                      'string');

    // define title (html) and headline
    $title = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE');
    $headline = $gL10n->get('PLG_ARBEITSDIENST_HEADLINE');
    $subHeadline = $gL10n->get('PLG_ARBEITSDIENST_CONTRIBUTION_PAYMENTS');

    /*
    $gNavigation->addStartUrl(CURRENT_URL, 
                           $headline, 
                            'bi-list-stars');
*/
    $getdatefilteractual = date('Y');

    $pPreferences = new ConfigTablePAD();
    $pPreferences->read(); // Konfigurationsdaten auslesen

    $user = new User($gDb, $gProfileFields);
    $userField = new Entity($gDb, TBL_USER_FIELDS, 'usf'); 

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-arbeitsdienst-overview-payments');
    $page->addTemplateFolder(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/templates');

    // create html page object
    $page->setContentFullWidth();
    $page->setTitle($title);
    $page->setHeadline($headline);

    /*
    // Prüfen, ob Kategorie und User_Fields vorhanden sind oder installiert werden müssen
    if (DBcategoriesID('PAD_ARBEITSDIENST') == 0) {
        admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install.php');
    }
    */

    //#############################################################################
    //  Ausgabe der Menueschalter
    //
    //  hier muss noch geprüft werden, ob admin oder nicht --> funktioniert so noch nicht

    if (isUserAuthorizedForPreferences())
    {
        // show link to pluginpreferences
        $page->addPageFunctionsMenuItem(
            'admMenuItemPreferencesLists', 
            $gL10n->get('SYS_SETTINGS'), 
            ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php?form=configuration',  
            'bi-gear-fill');

    }
    if ($getshowOption != 'main')
    {
        // show link to pluginMain
        $page->addPageFunctionsMenuItem(
            'admMenuItemMainLists', 
            $gL10n->get('PLG_ARBEITSDIENST_TEMPLATE_EINGABE'), 
            ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/arbeitsdienst.php?show_option=main',  
            '');
    }

    if (($getshowOption != 'exportsepa') && ($getshowOption != 'controlexport') && ($getshowOption != 'export'))
    {
        // show link to pluginExport
        $page->addPageFunctionsMenuItem(
            'admMenuItemExportLists', 
            $gL10n->get('PLG_ARBEITSDIENST_EXPORT'), 
            ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/arbeitsdienst.php?show_option=export',  
            '');
    }

    if ($getshowOption != 'overview')
    {
        // show link to pluginExport
        $page->addPageFunctionsMenuItem(
            'admMenuItemOverviewLists', 
            $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW'), 
            ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/arbeitsdienst.php?show_option=overview',  
            '');
    }

    //Plugin Kopf angeben
    $page->setHeadline($headline);

    // create filter menu with elements for role
    $form = new FormPresenter(
        'adm_navbar_filter_form_category_report',
        'sys-template-parts/form.filter.tpl',
        '',
        $page,
        array('type' => 'navbar', 'setFocus' => false)
    );

    $datumtemp = \DateTime::createFromFormat('Y-m-d', DATE_NOW);
    $datum = $datumtemp->format($gSettingsManager->getString('system_date'));
    $form->addInput('datum', 
                    $gL10n->get('PLG_ARBEITSDIENST_DATE_PAID'), 
                    $datum, 
                    array('type' => 'date', 'helpTextIdLabel' => 'PLG_ARBEITSDIENST_DATE_PAID_DESC'));
    
    $selectBoxEntries = array('0' => $gL10n->get('SYS_SHOW_ALL'), 
                              '1' => $gL10n->get('PLG_ARBEITSDIENST_WITH_PAID'), 
                              '2' => $gL10n->get('PLG_ARBEITSDIENST_WITHOUT_PAID'));
    $form->addSelectBox('mem_show', 
                        $gL10n->get('PLG_ARBEITSDIENST_FILTER'), 
                        $selectBoxEntries, 
                        array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_ARBEITSDIENST_FILTER_DESC', 'showContextDependentFirstEntry' => false));

    $page->addHtml('<h5 class="admidio-content-subheader">' . $subHeadline . '</h5>');

    //$form->addButton('btn_save_date', 'Datum speichern', array('link' => 'javascript:assign_date()', 'class' => 'btn-primary')); 
    //$form->addButton('btn_delete_date', 'Datum löschen', array('link' => 'javascript:delete_date()', 'class' => 'btn-primary'));


    $PaymentTable = new DataTables($page, 'adm_lists_table');
    $PaymentTable->setRowsPerPage($gSettingsManager->getInt('contacts_per_page'));

    $columnAlign = array('center');
    $ColumnValuesHeader = array('<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="' . $gL10n->get('PLG_ARBEITSDIENST_DATE_PAID_CHANGE_ALL_DESC') . '"/>');
    
    if ($getMode == 'assign') 
    {
        $ret_text = 'ERROR';

           $userArray = array();
        if ($getUserId != 0) // Bezahlt-Datum nur fuer einen einzigen User aendern
        {
            $userArray[0] = $getUserId;
        } else // Alle aendern wurde gewaehlt
        {
            $userArray = $_SESSION['pMembershipFee']['payments_user'];
        }
        try
        {
            foreach ($userArray as $dummy => $data) 
            {
                $user = new User($gDb, $gProfileFields, $data);
                // zuerst mal sehen, ob bei diesem user bereits ein BEZAHLT-Datum vorhanden ist
                if (strlen($user->getValue('WORKPAID')) === 0) 
                {
                    // er hat noch kein BEZAHLT-Datum, deshalb ein neues eintragen
                    $user->setValue('WORKPAID', $getDatumNeu);
                    
                    // wenn Lastschrifttyp noch nicht gesetzt ist: als Folgelastschrift kennzeichnen
                    // BEZAHLT bedeutet, es hat bereits eine Zahlung stattgefunden
                    // die naechste Zahlung kann nur eine Folgelastschrift sein
                    // Lastschrifttyp darf aber nur geaendert werden, wenn der Einzug per SEPA stattfand, also ein Faelligkeitsdatum vorhanden ist
                    if (strlen($user->getValue('WORKSEQUENCETYPE')) === 0 && strlen($user->getValue('WORKDUEDATE')) !== 0) 
                    {
                        $user->setValue('WORKSEQUENCETYPE', 'RCUR');
                    }
            
                    // falls Daten von einer Mandatsaenderung vorhanden sind, diese loeschen
                    if (strlen($user->getValue('ORIG_MANDATEID' . ORG_ID)) !== 0) 
                    {
                        $user->setValue('ORIG_MANDATEID' . ORG_ID, '');
                    }
                    if (strlen($user->getValue('ORIG_IBAN')) !== 0) 
                    {
                        $user->setValue('ORIG_IBAN', '');
                    }
                    if (strlen($user->getValue('ORIG_DEBTOR_AGENT')) !== 0) 
                    {
                        $user->setValue('ORIG_DEBTOR_AGENT', '');
                    }
            
                    // das Faelligkeitsdatum loeschen (wird nicht mehr gebraucht, da ja bezahlt)
                    if (strlen($user->getValue('WORKDUEDATE')) !== 0) 
                    {
                        $user->setValue('WORKDUEDATE', '');
                    }
                }      
                else 
                {
                    // er hat bereits ein BEZAHLT-Datum, deshalb das vorhandene loeschen
                    $user->setValue('WORKPAID', '');
                }
                $user->save();
                $ret_text = 'success';
            } //foreach
        } //try
        catch (Throwable $e) 
        {
            handleException($e);
        }
        echo $ret_text;
    }
   
    else
    {
        $membersList = array();

        $membersListRols = 0;

        $membersListFields = $pPreferences->config['columnconfig']['payments_fields'];
        
        $membersListSqlCondition = 'AND mem_usr_id IN (SELECT DISTINCT usr_id
            FROM ' . TBL_USERS . '
            LEFT JOIN ' . TBL_USER_DATA . ' AS paid
            ON paid.usd_usr_id = usr_id
            AND paid.usd_usf_id = ' . $gProfileFields->getProperty('WORKPAID', 'usf_id') . '
            LEFT JOIN ' . TBL_USER_DATA . ' AS fee
            ON fee.usd_usr_id = usr_id
            AND fee.usd_usf_id = ' . $gProfileFields->getProperty('WORKFEE', 'usf_id') . '
                
            LEFT JOIN ' . TBL_MEMBERS . ' AS mem
            ON mem.mem_usr_id  = usr_id
                
        WHERE fee.usd_value IS NOT NULL ';

        if ($getMembersShow == 1) // Nur Benutzer anzeigen, bei denen ein Bezahlt-Datum vorhanden ist
        {
            $membersListSqlCondition .= ' AND paid.usd_value IS NOT NULL ) ';
        } elseif ($getMembersShow == 2) // Nur Benutzer anzeigen, bei denen kein Bezahlt-Datum vorhanden ist
        {
            $membersListSqlCondition .= ' AND paid.usd_value IS NULL ) ';
        } else // Alle Benutzer anzeigen
        {
            $membersListSqlCondition .= ' ) ';
        }
    
        $membersList = list_members($getdatefilteractual, $membersListFields, $membersListRols, $membersListSqlCondition);

        //Javascript erstellen
        //Filterauswahl asuführen
        $javascriptCode = '
            // Anzeige abhaengig vom gewaehlten Filter
            $("#mem_show").change(function () 
            {
                if($(this).val().length > 0) 
                {
                    window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php').'?mem_show_choice="+$(this).val());
                }
                else
                {
                    alert(data);
                    return false;  
                }
                return true;
            });';
        $page->addJavascript($javascriptCode,true);

        //Header-Checkbox um alle Daten auszuwählen
        $javascriptCode = '
            // if checkbox in header is clicked then change all data
            $("input[type=checkbox].change_checkbox").click(function()
            {
                var datum = $("#datum").val();
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php', array('mode' => 'assign')) .'&datum_neu=" + datum,
                    function(data){
                        // check if error occurs
                        if(data == "success") 
                        {
                        var mem_show = $("#mem_show").val();
                            window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php').'?mem_show_choice=" + mem_show);
                        }
                        else 
                        {
                            alert(data);
                            return false;
                        }
                        return true;
                    }
                );
            });';
        $page->addJavascript($javascriptCode,true);

        //Header-Checkbox um alle Daten auszuwählen
        $javascriptCode = '
            // if checkbox of the user is clicked then change this data
            $("input[type=checkbox].memlist_checkbox").click(function()
            {
                var datum = $("#datum").val();
                var checkbox = $(this);
                var row_id = $(this).parent().parent().attr("id");
                var pos = row_id.search("_");
                var userid = row_id.substring(pos+1);
                var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php', array('mode' => 'assign')) .'&datum_neu=" + datum + "&usr_id=" + userid,
                    function(data){
                        // check if error occurs
                        if(data == "success") 
                        {
                        var mem_show = $("#mem_show").val();
                            window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php').'?mem_show_choice=" + mem_show);
                        }
                        else 
                        {
                            alert(data);
                            return false;
                        }
                        return true;
                    }
                );
            });';
        $page->addJavascript($javascriptCode,true);

        $smarty = $page->createSmartyObject();
            
        //Filter schreiben
        $form->addToHtmlPage();
        
        //Header schreiben
        //Tabellenkopf ermitteln
        $ValueAlignHeader = getdataheader($membersList, $gProfileFields, $gL10n);
        $ColumnValuesHeader = $ValueAlignHeader['header'];
        $ColumnAlign = $ValueAlignHeader['align'];

        //Daten ermitteln
        $user = new User($gDb, $gProfileFields);

        $counter = 0;
        $columnValues = array();
        // user data
        foreach ($membersList as $member => $memberData) 
        {
            $counter++;

            if (strlen($memberData[$gProfileFields->getProperty('WORKPAID', 'usf_id')]) > 0) {
                $content = '<input type="checkbox" id="member_' . $member . '" name="member_' . $member . '" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_' . $member . '"></b>';
            } else {
                $content = '<input type="checkbox" id="member_' . $member . '" name="member_' . $member . '" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_' . $member . '"></b>';
            }

            $user->readDataById($member);

            $columnValue = getcolumndata($memberData, $member, $gProfileFields, $gL10n, $gDb, $gSettingsManager);
            //checkbox vorne einfügen
            $count = array_unshift($columnValue,$content);

            //$table->addRowByArray($columnValues, 'userid_' . $member, array('nobr' => 'true'));
            
            $jsonArray['recordTotal'] = $counter;
            $jsonArray['draw'] = 1;
            
            $columnValues[$counter] = $columnValue;

            //$userArray[] = $member;
            
        } // End-foreach User

        $PaymentTable->createJavascript(count($columnValues), 
                                    count($ColumnValuesHeader));

        
        //$PaymentTable->setColumnAlignByArray($ValueAlignHeader);
        $page->addHtml('<div class="table-responsive">');

        //Formatierung der Tabelle
        $classTable = 'table table-condensed table-hover';
        $smarty->assign('classTable', $classTable);

        //Ausrichtung, Header und Daten ausgeben
        $smarty->assign('columnAlign', $ColumnAlign);
        $smarty->assign('headers', $ColumnValuesHeader);
        $smarty->assign('rows', $columnValues);
        $smarty->assign('no_data_text', $gL10n->get('PLG_ARBEITSDIENST_OVERVIEW_PAYMENT_NO_DATA'));

        //Template einbinden
        $htmlTable = $smarty->fetch(__DIR__ . '/../templates/arbeitsdienst_overview_payment.tpl');
        $page->addHtml($htmlTable);

        $page->addHtml('</div>');

        //Seite ausgeben
        $page->show();
        
    }

} catch (Throwable $e) 
{
    handleException($e);
}