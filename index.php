<?php
/*
 ***********************************************************************************************
 *
 * Arbeitsdienst Startdatei
 *
 * Version 2.0.0
 *
 * Dies ist die Startdatei für das Plugin Arbeitsdienst.
 *
 * Author: WSVBS
 *
 * Compatible with Admidio version 5.0.x
 *
 * @copyright 2018-2025 WSVBS
 * @see https://www.wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Utils\SecurityUtils;

require_once (__DIR__ . '/../../system/common.php');
require_once (__DIR__ . '/../../system/login_valid.php');
require_once (__DIR__ . '/system/common_function.php');

$getStart = admFuncVariableIsValid($_GET, 'start', 'string');

$gNavigation->addStartUrl(CURRENT_URL);

if (($getStart == '') && ($gCurrentUser->isAdministrator()))
{
    $testfolder = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/install.php';
    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/install.php');
}
else
{
    admRedirect(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/arbeitsdienst.php', array('show_option' => 'main')));
}


