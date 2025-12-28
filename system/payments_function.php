<?php
/**
 ***********************************************************************************************
 * Funktionen zur Ausgabe der Datentabellen der Arbeitsdienstzahlungen
 * 
 * @copyright 2018-2025 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Parameters:
 *
 * form     - The name of the form preferences that were submitted.
 * 
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

try 
{
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');
    require_once (__DIR__ . '/../classes/configtable.php');

    $getform = admFuncVariableIsValid($_GET, 
                                      'form', 
                                      'string');

    $pPreferences = new ConfigTablePAD();
    $pPreferences->read(); // Konfigurationsdaten auslesen


    function getdataheader($membersList, $gProfileFields, $gL10n)
    {
        // headlines for columns
        $ColumnValuesHeader = array('<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="' . $gL10n->get('PLG_MITGLIEDSBEITRAG_DATE_PAID_CHANGE_ALL_DESC') . '"/>');
        $columnAlign = array();
        foreach ($membersList as $member => $memberData) {
            foreach ($memberData as $usfId => $dummy) {
                if (! is_int($usfId)) {
                    continue;
                }

                // Find name of the field
                $columnHeader = $gProfileFields->getPropertyById($usfId, 'usf_name');

                if ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX' || $gProfileFields->getPropertyById($usfId, 'usf_name_intern') === 'GENDER') {
                    $columnAlign[] = 'center';
                } elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'NUMBER' || $gProfileFields->getPropertyById($usfId, 'usf_type') === 'DECIMAL') {
                    $columnAlign[] = 'right';
                } else {
                    $columnAlign[] = 'left';
                }
                $ColumnValuesHeader[] = $columnHeader;
            } // End-Foreach
            break; // Abbruch nach dem ersten Mitglied, da nur die usfIds eines Mitglieds benoetigt werden um die headlines zu erzeugen
        }
        $output[] = array();
        $output['header'] = $ColumnValuesHeader;
        $output['align'] = $columnAlign;

        return $output;
    }

    function getcolumndata($memberData, $member, $gProfileFields, $gL10n, $gDb, $gSettingsManager)
    {
        $user = new User($gDb, $gProfileFields);
        $user->readDataById($member);

        foreach ($memberData as $usfId => $data) 
        {
            if (! is_int($usfId)) 
            {
                continue;
            }

            // fill content with data of database
            $content = $data;

            /**
             * **************************************************************
             */
            // in some cases the content must have a special output format
            /**
             * **************************************************************
             */
 
            if ($usfId === (int) $gProfileFields->getProperty('COUNTRY', 'usf_id')) {
                $content = $gL10n->getCountryByCode($data);
            } elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX') {
                if ($content != 1) {
                    $content = 0;
                }
            } elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'DATE') {
                if (strlen($data) > 0) {
                    // date must be formated
                    $date = DateTime::createFromFormat('Y-m-d', $data);
                    $content = $date->format($gSettingsManager->getString('system_date'));
                }
            }

            if ($usfId == $gProfileFields->getProperty('WORKPAID', 'usf_id')) {
                $content = '<div class="bezahlt_' . $member . '" id="bezahlt_' . $member . '">' . $content . '</div>';
            } elseif ($usfId == $gProfileFields->getProperty('WORKDUEDATE', 'usf_id')) {
                $content = '<div class="duedate_' . $member . '" id="duedate_' . $member . '">' . $content . '</div>';
            } elseif ($usfId == $gProfileFields->getProperty('WORKSEQUENCETYPE', 'usf_id')) {
                $content = '<div class="lastschrifttyp_' . $member . '" id="lastschrifttyp_' . $member . '">' . $data . '</div>';
            } elseif ($usfId == $gProfileFields->getProperty('ORIG_MANDATEID' . ORG_ID, 'usf_id')) {
                $content = '<div class="orig_mandateid_' . $member . '" id="orig_mandateid_' . $member . '">' . $data . '</div>';
            } elseif ($usfId == $gProfileFields->getProperty('ORIG_IBAN', 'usf_id')) {
                $content = '<div class="orig_iban_' . $member . '" id="orig_iban_' . $member . '">' . $data . '</div>';
            } elseif ($usfId == $gProfileFields->getProperty('ORIG_DEBTOR_AGENT', 'usf_id')) {
                $content = '<div class="orig_debtor_agent_' . $member . '" id="orig_debtor_agent_' . $member . '">' . $data . '</div>';
            }
            // firstname and lastname get a link to the profile
            
            if (($usfId === (int) $gProfileFields->getProperty('LAST_NAME', 'usf_id') || $usfId === (int) $gProfileFields->getProperty('FIRST_NAME', 'usf_id'))) {
                $htmlValue = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member);
                $columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$htmlValue.'</a>';
            } elseif (($usfId === (int) $gProfileFields->getProperty('EMAIL', 'usf_id') || $usfId === (int) $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id'))) {
                $columnValues[] = getEmailLink($data, $member);
            } else {
                // checkbox must set a sorting value
                if ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX') {
                    $columnValues[] = array(
                        'value' => $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member),
                        'order' => $content
                    );
                } else {
                    $columnValues[] = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member);
                }
            }
        }
        $columnValues[] = $member;
    return $columnValues;
    }

} catch (Throwable $e) {
    handleException($e, true);
}