<?php

/***********************************************************************************************
 * Verarbeiten der Menueeinstellungen des Admidio-Plugins Arbeitsstunden / Eingaben
 *
 * @copyright 2018 WSVBS
 * @see https://wsv-bs.de
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form         			: The name of the form preferences that were submitted.
 * input_id_datefilter		: '3' actual year for the calculation
 * 							  '2' actual year -1 for the calculation
 * 							   1' actual year -2 for the calculation
 * input_user				: ID of the actual user
 * input_edit				: true if an input is actual done
 * 							  false for no input is done
 * pad_id					: ID of the actual input on the tbl_arbeitsdienst
 ***********************************************************************************************
 */
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');
$getinputUser = admFuncVariableIsValid($_GET, 'input_user', 'string');
$getdatefilterid = admFuncVariableIsValid($_GET, 'input_id_datefilter', 'int');
//$getlistid = admFuncVariableIsValid($_GET, 'input_id_list', 'int');
$getinputedit = admFuncVariableIsValid($_GET, 'input_edit', 'bool');
$getinputpadid = admFuncVariableIsValid($_GET, 'pad_id', 'int');

// only authorized user are allowed to start this module
if (! isUserAuthorized($_SESSION['pMembershipFee']['script_name'])) {
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}


// weiterleiten zur letzten URL
$gNavigation->deleteLastUrl();
$datavalue = array();
try {
    switch ($getForm) {
        case 'savedatefilter':
            $datefilterid = $_POST['datefilter'];

            $inputyear = array();
            $inputyear['1'] = strval(date('Y') - 2);
            $inputyear['2'] = strval(date('Y') - 1);
            $inputyear['3'] = date('Y');

            $datefilter = $inputyear[$datefilterid];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_year',
                'input_user' => $getinputUser,
                'input_id_datefilter' => $datefilterid
            ));
            break;

        case 'saveuser':
            $datavalue['user_id'] = $_POST['user_id'];

            $inputuser = $datavalue['user_id'];

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_work',
                'input_user' => $inputuser,
                'input_id_datefilter' => $getdatefilterid
            ));
            break;

        case 'save':
            $datavalue['org_id'] = $orgId;
            // $datavalue['pad_id'] = $getinputpadid;
            $datavalue['user_id'] = $getinputUser;
            $datavalue['cat_id'] = $_POST['cat_id'];
            $datavalue['pro_id'] = $_POST['pro_id'];
            if (empty($datavalue['pro_id'])) {
                $datavalue['pro_id'] = 'NULL';
            }
            $datavalue['date'] = date('Y-m-d', strtotime($_POST['date']));
            $datavalue['name'] = $_POST['discription'];
            $datavalue['hours'] = $_POST['hours'];

            if ($getinputedit == true) {
                $sql = 'UPDATE adm_user_arbeitsdienst
                        SET pad_cat_id = ' . $datavalue['cat_id'] . ', 
                            pad_pro_id = ' . $datavalue['pro_id'] . ',
                            pad_date = ' . "'" . $datavalue['date'] . "'" . ', 
                            pad_name = ' . "'" . $datavalue['name'] . "'" . ', 
                            pad_hours = ' . $datavalue['hours'] . '
                        WHERE pad_id = ' . $getinputpadid;
            } else {
                $sql = 'INSERT INTO adm_user_arbeitsdienst
                        ( pad_org_id, pad_user_id, pad_cat_id, pad_pro_id, pad_date, pad_name, pad_hours)
                        VALUES (1,' . $datavalue['user_id'] . ', ' . $datavalue['cat_id'] . ', ' . $datavalue['pro_id'] . ', ' . "'" . $datavalue['date'] . "'" . ', ' . "'" . $datavalue['name'] . "'" . ', ' . $datavalue['hours'] . ')';
            }
            $gDb->query($sql);

            // Sprung-url mit den Sprungoptionen speichern
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_work',
                'input_edit' => false,
                'input_user' => $getinputUser,
                'input_id_datefilter' => $getdatefilterid
            ));

            break;

        case 'startedit':
            // Umschalten auf <editieren
            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_work',
                'input_edit' => true,
                'pad_id' => $getinputpadid,
                'input_user' => $getinputUser,
                'input_id_datefilter' => $getdatefilterid
            ));
            break;

        case 'delete':
            $sql = 'DELETE FROM adm_user_arbeitsdienst
                    WHERE pad_id = ' . $getinputpadid;

            $gDb->query($sql);

            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_work',
                'input_edit' => false,
                'pad_id' => $getinputpadid,
                'input_user' => $getinputUser,
                'input_id_datefilter' => $getdatefilterid
            ));
            break;

        case 'savecat':
            $datavalue['org_id'] = $orgId;
            $datavalue['cat_name_intern'] = $_POST['input_cat'];
            $catname = strtolower($datavalue['cat_name_intern']);

            $sqlcat = 'SELECT       cat_name_intern as cat
                       FROM        ' . TBL_CATEGORIES . '                                                
                       WHERE        cat_type = \'ADC\'
                       ORDER BY     cat_name_intern';

            $result = array();
            $result = $gDb->query($sqlcat);

            $catsequenz = $result->rowCount();

            $timestamp = time();
            $datum = date('Y-m-d H:i:s', $timestamp);

            $sql = 'INSERT INTO ' . TBL_CATEGORIES . ' 
                    (cat_org_id, cat_type, cat_name_intern, cat_name, cat_system, cat_default, cat_sequence, cat_usr_id_create, cat_timestamp_create)
                    VALUES (1, \'ADC\', \'' . $datavalue['cat_name_intern'] . '\', 
                                    \'' . $catname . '\',
                                     0,
                                     0,
                                    ' . ($catsequenz + 1) . ',
                                     2,
                                    \'' . $datum . '\' )';

            $gDb->query($sql);

            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'input_cat'
            ));
            break;
            
        case 'savebuild':
        	$datavalue['org_id'] = $orgId;
        	$datavalue['cat_name_intern'] = $_POST['input_build'];
        	$catname = strtolower($datavalue['cat_name_intern']);
        	
        	$sqlbuild = 'SELECT       cat_name_intern as cat
                         FROM        ' . TBL_CATEGORIES . '
                         WHERE        cat_type = \'ADV\'
                         ORDER BY     cat_name_intern';
        	
        	$result = array();
        	$result = $gDb->query($sqlbuild);
        	
        	$catsequenz = $result->rowCount();
        	
        	$timestamp = time();
        	$datum = date('Y-m-d H:i:s', $timestamp);
        	
        	$sql = 'INSERT INTO ' . TBL_CATEGORIES . '
                    (cat_org_id, cat_type, cat_name_intern, cat_name, cat_system, cat_default, cat_sequence, cat_usr_id_create, cat_timestamp_create)
                    VALUES (1, \'ADV\', \'' . $datavalue['cat_name_intern'] . '\',
                                    \'' . $catname . '\',
                                     0,
                                     0,
                                    ' . ($catsequenz + 1) . ',
                                     2,
                                    \'' . $datum . '\' )';
        	
        	$gDb->query($sql);
        	
        	$url = safeUrl($gNavigation->getUrl(), array(
        			'show_option' => 'input_build'
        	));
        	break;

        case 'calculation':

            $url = safeUrl($gNavigation->getUrl(), array(
                'show_option' => 'calculation'
            ));
            break;
    }

    // weiterleiten an die letzte URL
    admRedirect($url);

    // => EXIT
} catch (Exception $ex) {}

