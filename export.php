<?php

/***********************************************************************************************
 * Verarbeiten der Menueeinstellungen des Admidio-Plugins Arbeitsstunden / Export
 *
 * @copyright 2018-2021 WSVBS
 * @see https://wsv-bs.de
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Grundgerüst aus Plugin Mitgliederbeitrag
 *
 * Parameters:
 *
 * form         		: The name of the form preferences that were submitted.
 * datefilteractual		: year for to make the calculation
 * typeofoutput			: 'PDFPAY' - PDF output only the members who have to pay (actual not running)
 * 						  'CSVALL' - CSV output from all members
 * 						  'CSVPAY' - CSV output only the members who have to pay	
 *
 ***********************************************************************************************
 */
require_once (__DIR__ . '/../../adm_program/system/common.php');
require_once (__DIR__ . '/common_function.php');
require_once (__DIR__ . '/classes/configtable.php');

$now = time();
$format1 = 'Y-m-d';
$format2 = 'H:i:s';

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');
$getdatefilteractual = admFuncVariableIsValid($_GET, 'datefilteractual', 'string');
// $gettypeofoutput = admFuncVariableIsValid($_GET, 'typeofoutput', 'string');
$gettypeofoutput = $_POST['typeofoutput'];

$pPreferences = new ConfigTablePAD();
$pPreferences->read(); // Konfigurationsdaten auslesen

// alle aktiven Mitglieder einlesen
$members = list_members($getdatefilteractual, array(
    'FIRST_NAME',
    'LAST_NAME',
    'BIRTHDAY',
    'GENDER',
    'DEBITOR_STREET',
    'DEBITOR_CITY'
), array(
    'Mitglied' => 0
));

// Informationen aller Mitglieder zum Arbeitsdienst einslesen
$membersworkinfo = list_members_workinfo($members, $getdatefilteractual);

// Information der Gesamtstunden
$sumworking = sum_working($membersworkinfo, $pPreferences->config['Stunden']['Kosten']);

// Kontoinformationen jedes Mitgieds auslesen
$membersaccount = list_members($datefilteractual, array(
    'WORKDUEDATE',
    'WORKSEQUENCETYPE',
    'SEQUENCETYPE' . ORG_ID,
    'WORKREFERENCE',
    'WORKPAID',
    'WORKFEE',
    'MANDATEID' . ORG_ID,
    'MANDATEDATE' . ORG_ID,
    'IBAN',
    'BIC'
), 0);

// weiterleiten zur letzten URL
$gNavigation->deleteLastUrl();
$datavalue = array();

try {
    switch ($getForm) {
        case controlexport:
            if (isset($_POST['btn_export_control']) && ($gettypeofoutput == '')) // Ausgabe im PDF Format
            {
                $gMessage->show($gL10n->get('WSV_ARBEISDIENST_EXPORT_NO_DATA'));

                // Sprung-url mit den Sprungoptionen speichern
                $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                    'show_option' => 'controlexport'
                ));
                break;
            }

            if (isset($_POST['btn_export_control']) && ($gettypeofoutput == 'PDFPAY')) // Ausgabe im PDF Format
            {
                // Sprung-url mit den Sprungoptionen speichern
                $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                    'show_option' => 'controlexport'
                ));
                $gMessage->setForwardUrl($url);
                $gMessage->show($gL10n->get('PLG_ARBEITSDIENST_EXPORT_CONTROL_PDFPAY_NOTVALID'));

                break;
            }

            if (isset($_POST['btn_export_control'])) // Ausgabe im CSV Format
            {
                // Ausgabe eine CSV Kontrolldatei
                $inhalt = '';
                $AnzahlMitglieder = 0;

                // Dateityp, der immer abgespeichert wird
                header('Content-Type: application/octet-stream');

                // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
                header('Cache-Control: private');

                // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
                header('Content-Transfer-Encoding: binary');

                // Zwischenspeichern auf Proxies verhindern
                header('Cache-Control: post-check=0, pre-check=0');

                if ($gettypeofoutput == 'CSVALL') {
                    header('Content-Disposition: attachment; filename="Arbeitsdienst_alle_' . $getdatefilteractual . '.csv"');
                }

                if ($gettypeofoutput == 'CSVPAY') {
                    header('Content-Disposition: attachment; filename="Arbeitsdienst_Zahler_' . $getdatefilteractual . '.csv"');
                }

                foreach ($members as $member => $memberdata) {
                    $output = TRUE;
                    $test = $membersworkinfo[$member]['Fehlstunden'];
                    if (($gettypeofoutput == 'CSVPAY') && ($membersworkinfo[$member]['Fehlstunden'] == 0)) {
                        $output = FALSE;
                    }
                    if ($output == TRUE) {
                        $AnzahlMitglieder ++;
                        if ($AnzahlMitglieder == 1) {
                            echo 'Nr;Nachname;Vorname;Alter;Passiv;Geschlecht;Sollstunden;Iststunden;Differenzstunden;Fehlstunden; zu zahlen' . "\n";
                        }
                        echo $AnzahlMitglieder . ';' . $members[$member]['LAST_NAME'] . ';' . $members[$member]['FIRST_NAME'] . ';' . $membersworkinfo[$member]['ALTER'] . ';' . $membersworkinfo[$member]['PASSIV'] . ';' . $members[$member]['GENDER'] . ';' . $membersworkinfo[$member]['Sollstunden'] . ';' . $membersworkinfo[$member]['Iststunden'] . ';' . $membersworkinfo[$member]['Differenzstunden'] . ';' . $membersworkinfo[$member]['Fehlstunden'] . ';' . $membersworkinfo[$member]['Kosten'] . "\n";
                    }
                }
                exit();
            }

            break;

        case exportsepa:

            if (isset($_POST['btn_export_sepa_date'])) {}

            if (isset($_POST['btn_export_sepa_xml'])) {
                if ($pPreferences->config['Datum']['Stichtag'] == NULL) {
                    exit();
                }
                $message_id = substr('Message-ID-' . replace_sepadaten($gCurrentOrganization->getValue('org_shortname')), 0, 35); // SEPA Message-ID (max. 35)
                $message_datum = date($format1, $now) . 'T' . date($format2, $now) . '.000Z'; // SEPA Message-Datum z.B.: 2010-11-21T09:30:47.000Z
                $message_initiator_name = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70); // SEPA Message Initiator Name
                $paystring = $pPreferences->config['Datum']['Stichtag'];
                list($d, $m, $y) = explode(".",$paystring);
                $paydate = $y."-".$m."-".$d;
                $payment_id = $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_FILE_PAYMENTID'); // SEPA Payment_ID (max. 35)
                $payment_end2end_id = 'NOTPROVIDED'; // SEPA Payment_EndToEndIdentification

                // Zahlungsempfänger -> Verein
                $zempf['name'] = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70); // SEPA Zahlungsempfaenger Kontoinhaber
                $zempf['iban'] = strtoupper(str_replace(' ', '', $pPreferences->config['Kontodaten']['iban'])); // SEPA Zahlungsempfaenger IBAN
                $zempf['bic'] = strtoupper($pPreferences->config['Kontodaten']['bic']);
                $zempf['ci'] = $pPreferences->config['Kontodaten']['ci']; // Organisation SEPA_ID (Glaeubiger-ID Bundesdbank)

                /**
                 * ****************************************************************************
                 * Schreibt Lastschriften in einen XML-String
                 * ***************************************************************************
                 */

                $xmlfile = '';
                $xmlfile .= "<?xml version='1.0' encoding='UTF-8'?>\n";

                // DFÜ-Abkommen Version 3.1
                // Pain 008.001.002
                // ########## Document ###########
                $xmlfile .= "<Document xmlns='urn:iso:std:iso:20022:tech:xsd:pain.008.001.02'";
                $xmlfile .= "          xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'";
                $xmlfile .= "          xsi:schemaLocation='urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd'>\n";

                // ########## Customer Direct Debit Initiation ###########
                $xmlfile .= "   <CstmrDrctDbtInitn>\n";

                // ########## Group-Header ###########
                $xmlfile .= "   <GrpHdr>\n";
                $xmlfile .= "       <MsgId>$message_id</MsgId>\n"; // MessageIdentification
                $xmlfile .= "       <CreDtTm>$message_datum</CreDtTm>\n"; // Datum & Zeit
                $xmlfile .= "       <NbOfTxs>" . $sumworking['AnzahlZahler'] . "</NbOfTxs>\n"; // NumberOfTransactions
                $xmlfile .= "       <CtrlSum>" . $sumworking['Kosten'] . "</CtrlSum>\n"; // ControlSum
                $xmlfile .= "       <InitgPty>\n";
                $xmlfile .= "           <Nm>$message_initiator_name</Nm>\n";
                $xmlfile .= "       </InitgPty>\n";
                $xmlfile .= "   </GrpHdr>\n";

                // ########## Payment Information ###########
                $xmlfile .= "   <PmtInf>\n";
                $xmlfile .= "       <PmtInfId>$payment_id</PmtInfId>\n"; // Payment-ID
                $xmlfile .= "       <PmtMtd>DD</PmtMtd>\n"; // Payment-Methode, Lastschrift: DD
                $xmlfile .= "       <BtchBookg>true</BtchBookg>\n"; // BatchBooking, Sammelbuchung (true) oder eine Einzelbuchung handelt (false)
                $xmlfile .= "       <NbOfTxs>" . $sumworking['AnzahlZahler'] . "</NbOfTxs>\n"; // Number of Transactions
                $xmlfile .= "       <CtrlSum>" . $sumworking['Kosten'] . "</CtrlSum>\n"; // Control Summe
                $xmlfile .= "       <ReqdColltnDt>" . $paydate . "</ReqdColltnDt>\n"; // RequestedCollectionDate, Faelligkeitsdatum der Lastschrift
                $xmlfile .= "       <Cdtr>\n"; // Creditor
                $xmlfile .= '           <Nm>' . $zempf['name'] . "</Nm>\n"; // Name, max. 70 Zeichen
                $xmlfile .= "       </Cdtr>\n";
                $xmlfile .= "       <CdtrAcct>\n"; // CreditorAccount, Creditor-Konto
                $xmlfile .= "           <Id>\n";
                $xmlfile .= '               <IBAN>' . $zempf['iban'] . "</IBAN>\n";
                $xmlfile .= "           </Id>\n";
                $xmlfile .= "       </CdtrAcct>\n";
                $xmlfile .= "       <CdtrAgt>\n"; // CreditorAgent, Creditor-Bank
                $xmlfile .= "           <FinInstnId>\n"; // FinancialInstitutionIdentification
                if (strlen($zempf['bic']) !== 0) // ist ein BIC vorhanden?
                {
                    $xmlfile .= '               <BIC>' . $zempf['bic'] . "</BIC>\n";
                } else {
                    $xmlfile .= "           <Othr>\n";
                    $xmlfile .= "               <Id>NOTPROVIDED</Id>\n";
                    $xmlfile .= "           </Othr>\n";
                }
                $xmlfile .= "           </FinInstnId>\n";
                $xmlfile .= "       </CdtrAgt>\n";
                $xmlfile .= "       <ChrgBr>SLEV</ChrgBr>\n"; // ChargeBearer, Entgeltverrechnungsart, immer SLEV
                                                              // ########## CREDITOR, Zahlungsempfaenger ##############
                $xmlfile .= "       <CdtrSchmeId>\n"; // CreditorSchemeIdentification, Identifikation des Zahlungsempfaengers
                $xmlfile .= "           <Id>\n"; // Eindeutiges Identifizierungmerkmal einer Organisation oder Person
                $xmlfile .= "               <PrvtId>\n"; // PrivateIdentification, Personenidentifikation
                $xmlfile .= "                   <Othr>\n"; // OtherIdentification
                $xmlfile .= "                       <Id>" . $zempf['ci'] . "</Id>\n"; // Eindeutiges Identifizierungsmerkmal des Glaeubigers
                $xmlfile .= "                       <SchmeNm>\n"; // SchemeName, Name des Identifikationsschemas
                $xmlfile .= "                           <Prtry>SEPA</Prtry>\n"; // Proprietary, immer SEPA
                $xmlfile .= "                       </SchmeNm>\n";
                $xmlfile .= "                   </Othr>\n";
                $xmlfile .= "               </PrvtId>\n";
                $xmlfile .= "           </Id>\n";
                $xmlfile .= "       </CdtrSchmeId>\n";

                // ######### Direct Debit Transaction Information, Lastschriften ##############

                foreach ($members as $member => $memberdata) // Schleife über alle Mitglieder
                {
                    if ($membersworkinfo[$member]['Fehlstunden'] > 0) {
                        $user = new User($gDb, $gProfileFields, $member);

                        // Zahlungspflichtiger -> Mitglied
                        $zpflgt['duedate'] = $pPreferences->config['Datum']['Stichtag']; // Fälligkeitstag

                        // Es werden nur Zahlungspflichtige aufgenommen
                        // Ermitteln von Mitglieder relevanten Angaben
                        // Sequenztyp
                        if (empty($membersaccount[$member]['SEQUENCETYPE' . ORG_ID])) {
                            $zpflgt['sequencetyp'] = 'FRST';
                        } else {
                            $zpflgt['sequencetyp'] = $membersaccount[$member]['SEQUENCETYPE' . ORG_ID];
                        }

                        // zu überweisenden Betrag
                        $zpflgt['fee'] = str_replace(',', '.', $membersworkinfo[$member]['Kosten']);
                        if (strpos($zpflgt['fee'], '.') !== false) {
                            $zpflgt['fee'] = substr($zpflgt['fee'], 0, strpos($zpflgt['fee'], '.') + 3);
                        }

                        $zpflgt['mandat_id'] = $membersaccount[$member]['MANDATEID' . ORG_ID];
                        $zpflgt[mandat_datum] = $membersaccount[$member]['MANDATEDATE' . ORG_ID];

                        $zpflgt['name'] = $members[$member]['LAST_NAME'] . ", " . $members[$member]['FIRST_NAME'];
                        $zpflgt['bic'] = $membersaccount[$member]['BIC'];
                        $zpflgt['iban'] = strtoupper(str_replace(' ', '', $membersaccount[$member]['IBAN']));

                        $zpflgt['land'] = substr($membersaccount[$member]['IBAN'], 0, 2);
                        $zpflgt['street'] = $membersaccount[$member]['DEBITOR_STREET'];
                        $zpflgt['ort'] = $membersaccount[$member]['DEBITOR_CITY'];

                        $zpflgt['end2end_id'] = substr(replace_sepadaten($gCurrentOrganization->getValue('org_shortname')) . '-' . $membersaccount[$member]['MANDATEID' . ORG_ID] . '-' . date($format1, $now), 0, 35);
                        $zpflgt['reference'] = $pPreferences->config['SEPA']['reference'] . "-" . $membersworkinfo[$member]['Fehlstunden'] . " " . $gL10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_FILE_STD') . "-" . $members[$member]['LAST_NAME'] . ", " . $members[$member]['FIRST_NAME'];

                        // xmlfile weiterbauen
                        $xmlfile .= "       <DrctDbtTxInf>\n"; // DirectDebitTransactionInformation
                        $xmlfile .= "           <PmtId>\n"; // PaymentIdentification, Referenzierung einer einzelnen Transaktion
                        $xmlfile .= "               <EndToEndId>" . $zpflgt['end2end_id'] . "</EndToEndId>\n"; // SEPA End2End-ID (max. 35)."</EndToEndId>\n"; //EndToEndIdentification
                        $xmlfile .= "           </PmtId>\n";
                        $xmlfile .= "           <PmtTpInf>\n"; // PaymentTypeInformation
                        $xmlfile .= "               <SvcLvl>\n"; // ServiceLevel
                        $xmlfile .= "                   <Cd>SEPA</Cd>\n"; // Code, immer SEPA
                        $xmlfile .= "               </SvcLvl>\n";
                        $xmlfile .= "               <LclInstrm>\n"; // LocalInstrument, Lastschriftart
                        $xmlfile .= "                   <Cd>CORE</Cd>\n"; // CORE (Basislastschrift oder B2B (Firmenlastschrift)
                        $xmlfile .= "               </LclInstrm>\n";
                        $xmlfile .= "               <SeqTp>" . $zpflgt['sequencetyp'] . "</SeqTp>\n"; // SequenceType
                                                                                                      // Der SequenceType gibt an, ob es sich um eine Erst-, Folge-,
                                                                                                      // Einmal- oder letztmalige Lastschrift handelt.
                                                                                                      // Zulaessige Werte: FRST, RCUR, OOFF, FNAL
                                                                                                      // Wenn <OrgnlDbtrAcct> = SMNDA und <Amdmnt-Ind> = true
                                                                                                      // dann muss dieses Feld mit FRST belegt sein.
                        $xmlfile .= "           </PmtTpInf>\n";
                        $xmlfile .= "           <InstdAmt Ccy=\"EUR\">" . $zpflgt['fee'] . "</InstdAmt>\n"; // InstructedAmount (Dezimalpunkt)
                        $xmlfile .= "           <DrctDbtTx>\n"; // DirectDebitTransaction, Angaben zum Lastschriftmandat
                        $xmlfile .= "               <MndtRltdInf>\n"; // MandateRelated-Information, mandatsbezogene Informationen
                        $xmlfile .= "                   <MndtId>" . $zpflgt['mandat_id'] . "</MndtId>\n"; // eindeutige Mandatsreferenz
                        $xmlfile .= "                   <DtOfSgntr>" . $zpflgt[mandat_datum] . "</DtOfSgntr>\n"; // Datum, zu dem das Mandat unterschrieben wurde

                        // Kennzeichnet, ob das Mandat veraendert wurde, ist derzeit nicht aktiv

                        $xmlfile .= "                   <AmdmntInd>false</AmdmntInd>\n"; // AmendmentIndicator "false", Mandat wurde nicht verändert
                        $xmlfile .= "               </MndtRltdInf>\n";
                        $xmlfile .= "           </DrctDbtTx>\n";
                        // ## Kreditinstitut des Zahlers (Zahlungspflichtigen)
                        $xmlfile .= "           <DbtrAgt>\n"; // DebtorAgent, Kreditinstitut des Zahlers (Zahlungspflichtigen)
                        $xmlfile .= "               <FinInstnId>\n"; // FinancialInstitutionIdentification
                        if (strlen($zpflgt['bic']) !== 0) // ist ein BIC vorhanden?
                        {
                            $xmlfile .= "                   <BIC>" . $zpflgt['bic'] . "</BIC>\n";
                        } else {
                            $xmlfile .= "                   <Othr>\n";
                            $xmlfile .= "                       <Id>NOTPROVIDED</Id>\n";
                            $xmlfile .= "                   </Othr>\n";
                        }
                        $xmlfile .= "               </FinInstnId>\n";
                        $xmlfile .= "           </DbtrAgt>\n";
                        $xmlfile .= "           <Dbtr>\n"; // Zahlungspflichtiger
                        $xmlfile .= "               <Nm>" . $zpflgt['name'] . "</Nm>\n"; // Name (70)
                        if (! substr($zpflgt['iban'], 0, 2)) {
                            $xmlfile .= "               <PstlAdr>\n";
                            $xmlfile .= "                   <Ctry>" . $zpflgt['land'] . "</Ctry>\n"; // Zahlungspflichtigen-Adresse ist Pflicht
                            $xmlfile .= "                   <AdrLine>" . $zpflgt['street'] . "</AdrLine>\n"; // bei Lastschriften ausserhalb EU/EWR
                            $xmlfile .= "                   <AdrLine>" . $zpflgt['ort'] . "</AdrLine>\n";
                            $xmlfile .= "               </PstlAdr>\n";
                        }
                        $xmlfile .= "           </Dbtr>\n";

                        $xmlfile .= "           <DbtrAcct>\n";
                        $xmlfile .= "               <Id>\n";
                        $xmlfile .= "                   <IBAN>" . $zpflgt['iban'] . "</IBAN>\n"; // IBAN des Zahlungspflichtigen
                        $xmlfile .= "               </Id>\n";
                        $xmlfile .= "           </DbtrAcct>\n";
                        $xmlfile .= "           <RmtInf>\n"; // Remittance Information, Verwendungszweck
                        $xmlfile .= "               <Ustrd>" . $zpflgt['reference'] . "</Ustrd>\n"; // Unstructured, unstrukturierter Verwendungszweck(max. 140 Zeichen))
                        $xmlfile .= "           </RmtInf>\n";
                        $xmlfile .= "       </DrctDbtTxInf>\n";

                        // Eintragen der Daten in die Userdaten
                        $user->setValue('WORKPAID', '');
                        $user->setValue('WORKFEE', $zpflgt['fee']);
                        $user->setValue('WORKDUEDATE', $zpflgt['duedate']);
                        $user->setValue('WORKSEQUENCETYPE', $zpflgt['sequencetyp']);
                        $user->setValue('WORKREFERENCE', $zpflgt['reference']);
                        $user->save();
                    }
                }

                $xmlfile .= "   </PmtInf>\n";
                $xmlfile .= "</CstmrDrctDbtInitn>\n"; // Ende Customer Direct Debit Transfer Initiation

                $xmlfile .= "</Document>\n"; // Ende Document
                /**
                 * ****************************************************************************
                 * Schreibt XML-Datei
                 * ***************************************************************************
                 */

                header('content-type: text/xml');
                header('Cache-Control: private'); // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
                header('Content-Transfer-Encoding: binary'); // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
                header('Cache-Control: post-check=0, pre-check=0'); // Zwischenspeichern auf Proxies verhindern
                header('Content-Disposition: attachment; filename="' . $pPreferences->config['SEPA']['dateiname'] . "-" . $getdatefilteractual . '.xml"');

                echo $xmlfile;

                die();
            }
            $url = SecurityUtils::encodeUrl($gNavigation->getUrl(), array(
                'show_option' => 'exportsepa'
            ));
            break;
    }
    // weiterleiten an die letzte URL
    admRedirect($url);

    // => EXIT
} catch (Exception $ex) {}