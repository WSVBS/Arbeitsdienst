<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin Arbeitsdienst
 *
 * @copyright 2019 WSVBS
 * @see https://wsv-bs.de/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */
global $gL10n, $gProfileFields;

// Standardwerte einer Neuinstallation

// Altersgrenzen
$config_default['Alter'] = array(
    'AGEBegin' => 16,
    'AGEEnd' => 66
);

// Anzahl Arbeitsstunden
$config_default['Stunden'] = array(
    'WorkingHoursWoman' => 5,
    'WorkingHoursMan' => 10,
    'Kosten' => 8
);

// Fälligkeitstag
$config_default['Datum'] = array(
    'Stichtag' => date('d.m.Y', strtotime((date('Y')) . '-09-15'))
);

// Ausnahmen für Arbeitsdienstpflichtig
$config_default['Ausnahme'] = array(
    'passiveRolle' => 0
);

// SEPA Informationen
$config_default['SEPA'] = array(
    'dateiname' => 'Arbeitsdienst',
    'reference' => 'Arbeitsdienst'
);

// Spalten fuer die Ansichtsdefinitionen
$config_default['columnconfig'] = array(
    'payments_fields_normal_screen' => array(
        'p' . $gProfileFields->getProperty('WORKPAID', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKDUEDATE', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKSEQUENCETYPE', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKFEE', 'usf_id'),
        'p' . $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        'p' . $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        'p' . $gProfileFields->getProperty('BIRTHDAY', 'usf_id')
    ),
    'payments_fields_full_screen' => array(
        'p' . $gProfileFields->getProperty('WORKPAID', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKDUEDATE', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKSEQUENCETYPE', 'usf_id'),
        'p' . $gProfileFields->getProperty('WORKFEE', 'usf_id'),
        'p' . $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        'p' . $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        'p' . $gProfileFields->getProperty('ORIG_MANDATEID' . ORG_ID, 'usf_id'),
        'p' . $gProfileFields->getProperty('ORIG_DEBTOR_AGENT', 'usf_id'),
        'p' . $gProfileFields->getProperty('DEBTOR', 'usf_id')
    )
);

/*
 * Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 * zu einem String zusammengefasst und in der Admidiodatenbank gespeichert.
 * Muessen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung
 * einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten
 * nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuaendern (z.B. in !-!)
 *
 * Achtung: Vor einer Aenderung muss eine Deinstallation durchgefuehrt werden!
 * Bereits gespeicherte Werte in der Datenbank koennen nach einer Aenderung nicht mehr eingelesen werden!
 */
$dbtoken = '#_#';
