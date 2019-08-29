<?php

/******************************************************************************
 * Klasse verwaltet die Arbeitsdiensttabelle "adm_arbeitsdienst"
 *
 * Folgende Methoden stehen zur Verfuegung:
 *
 * init()                       :   prueft, ob die Tabelle adm_arbeitsdienst existiert,
 *                                  legt sie ggf. an.
 * save()                       :   schreibt die Konfiguration in die Datenbank
 * read()                       :   liest die Konfigurationsdaten aus der Datenbank
 * checkforupdate()             :   vergleicht die Angaben in der Datei version.php
 *                                  mit den Daten in der DB
 * initconfig()                 :   prüft, ob die Konfiguration vorhanden ist
 *                                  und Einträge existieren
 * delete_config_data()         :   loescht Konfigurationsdaten in der Datenbank
 * delete_member_data           :   loescht Nutzerdaten in der Datenbank
 * delete_mail_data             :   loescht Mail-Texte  in der Datenbank
 *
 *****************************************************************************/
class ConfigTablePAD
{

    public $config = array();

    // /< Array mit allen Konfigurationsdaten
    protected $table_name;

    protected static $shortcut1 = 'PAD';

    protected static $shortcut2 = 'PMB';

    protected static $version;

    protected static $stand;

    protected static $dbtoken;

    public $config_default = array();

    /**
     * ************************************************************************
     * ConfigTablePAD constructor
     * /************************************************************************
     */
    public function __construct()
    {
        global $g_tbl_praefix;

        require_once (__DIR__ . '/../version.php');
        include (__DIR__ . '/../configdata.php');

        $this->table_name = $g_tbl_praefix . '_user_arbeitsdienst';
        $this->configtable_name = $g_tbl_praefix . '_plugin_preferences';

        if (isset($plugin_version)) {
            self::$version = $plugin_version;
        }
        if (isset($plugin_stand)) {
            self::$stand = $plugin_stand;
        }
        if (isset($dbtoken)) {
            self::$dbtoken = $dbtoken;
        }
        $this->config_default = $config_default;
    }

    /**
     * ************************************************************************
     * Prüft, ob die Tabelle Arbeitsdienst bereits existiert.
     * Ggf.
     * wird sie angelegt
     *
     * @return void /************************************************************************
     */
    public function init()
    {
        global $gDb;
        $datavalue = array();

        // pruefen, ob es die Tabelle bereits gibt
        $sql = 'SHOW TABLES LIKE \'' . $this->table_name . '\' ';
        $statement = $gDb->query($sql);

        // Tabelle anlegen, wenn es sie noch nicht gibt
        if (! $statement->rowCount()) {
            // Tabelle ist nicht vorhanden --> anlegen
            $sql = 'CREATE TABLE ' . $this->table_name . ' (
                pad_id          integer         unsigned not null AUTO_INCREMENT,
                pad_org_id      integer         unsigned not null,
                pad_user_id     integer         unsigned not null,
                pad_cat_id      integer         unsigned not null,
                pad_pro_id      integer         unsigned,                
                pad_date        date,        
                pad_name        varchar(255)    not null,
                pad_hours       float           unsigned not null,
                primary key (pad_id) )
                engine = InnoDB
                auto_increment = 1
                default character set = utf8
                collate = utf8_unicode_ci';
            $gDb->query($sql);
        }

        // pruefen, ob es die Tabelle für Pluginkonfigurationen bereits gibt
        $sql = 'SHOW TABLES LIKE \'' . $this->configtable_name . '\' ';
        $statement = $gDb->query($sql);

        // Tabelle anlegen, wenn es sie noch nicht gibt
        if (! $statement->rowCount()) {
            // Tabelle ist nicht vorhanden --> anlegen
            $sql = 'CREATE TABLE ' . $this->configtable_name . ' (
                plp_id      integer     unsigned not null AUTO_INCREMENT,
                plp_org_id  integer     unsigned not null,
                plp_name    varchar(255) not null,
                plp_value   text,
                primary key (plp_id) )
                engine = InnoDB
                auto_increment = 1
                default character set = utf8
                collate = utf8_unicode_ci';
            $gDb->query($sql);
        }

        $this->read();

        // die eingelesenen Konfigurationsdaten in ein Arbeitsarray kopieren
        $config_ist = $this->config;

        // Prüfen ob die einzelnen Einträge vorhanden sind. Wenn nicht, dann
        // müssen diese mit Defaultwerten angelegt werden.
        foreach ($this->config_default as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value) {
                // gibt es diese Sektion bereits in der config?
                if (isset($config_ist[$section][$key])) {
                    // wenn ja, diese Sektion in der Ist-config loeschen
                    unset($config_ist[$section][$key]);
                } else {
                    // wenn nicht, diese Sektion in der config anlegen und mit den Standardwerten aus der Soll-config befuellen
                    $this->config[$section][$key] = $value;
                }
            }
            // leere Abschnitte (=leere Arrays) loeschen
            if ((isset($config_ist[$section]) && count($config_ist[$section]) === 0)) {
                unset($config_ist[$section]);
            }
        }
        // die Ist-config durchlaufen
        // jetzt befinden sich hier nur noch die DB-Eintraege, die nicht verwendet werden und deshalb:
        // 1. in der DB geloescht werden koennen
        // 2. in der normalen config geloescht werden koennen
        foreach ($config_ist as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value) {
                $plp_name = self::$shortcut1 . '__' . $section . '__' . $key;
                $sql = 'DELETE FROM ' . $this->table_name . '
                        WHERE plp_name = \'' . $plp_name . '\'
                        AND plp_org_id = ' . ORG_ID . ' ';
                $gDb->query($sql);
                unset($this->config[$section][$key]);
            }
            // leere Abschnitte (=leere Arrays) loeschen
            if (count($this->config[$section]) === 0) {
                unset($this->config[$section]);
            }
        }
        // die aktualisierten und bereinigten Konfigurationsdaten in die DB schreiben
        $this->save();
    }

    /**
     * Schreibt die Konfigurationsdaten in die Datenbank
     *
     * @return void
     */
    public function save()
    {
        global $gDb;

        foreach ($this->config as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value)
                if (is_array($value)) {
                    // um diesen Datensatz in der Datenbank als Array zu kennzeichnen, wird er von Doppelklammern eingeschlossen
                    $value = '((' . implode(self::$dbtoken, $value) . '))';
                }
            {
                if ($section == 'Kontodaten') {
                    $plp_name = self::$shortcut2 . '__' . $section . '__' . $key;
                } else {
                    $plp_name = self::$shortcut1 . '__' . $section . '__' . $key;
                }

                $sql = ' SELECT plp_id
                        FROM ' . $this->configtable_name . '
                        WHERE plp_name = \'' . $plp_name . '\'
                        AND (  plp_org_id = ' . ORG_ID . '
                        OR plp_org_id IS NULL ) ';
                $statement = $gDb->query($sql);
                $row = $statement->fetchObject();

                // Gibt es den Datensatz bereits?
                // wenn ja: UPDATE des bestehende Datensatzes
                if (isset($row->plp_id) && strlen($row->plp_id) > 0) {
                    $sql = 'UPDATE ' . $this->configtable_name . '
                            SET plp_value = \'' . $value . '\'
                            WHERE plp_id = ' . $row->plp_id;

                    $gDb->query($sql);
                } // wenn nicht: INSERT eines neuen Datensatzes
                else {
                    $sql = 'INSERT INTO ' . $this->configtable_name . ' (plp_org_id, plp_name, plp_value)
                            VALUES (\'' . ORG_ID . '\' ,\'' . self::$shortcut1 . '__' . $section . '__' . $key . '\' ,\'' . $value . '\')';
                    $gDb->query($sql);
                }
            }
        }
    }

    /**
     * ************************************************************************
     * Liest die Konfigurationsdaten aus der Datenbank
     *
     * @return void /************************************************************************
     */
    public function read()
    {
        global $gDb;

        $sql = 'SELECT plp_id, plp_name, plp_value
                FROM ' . $this->configtable_name . '
                WHERE plp_name LIKE \'' . self::$shortcut1 . '__%\'
                OR    plp_name LIKE \'' . self::$shortcut2 . '__Kontodaten__%\'
                AND (  plp_org_id = ' . ORG_ID . '
                    OR plp_org_id IS NULL ) ';
        $statement = $gDb->query($sql);

        while ($row = $statement->fetch()) {
            $array = explode('__', $row['plp_name']);
            // wenn plp_value von (( )) eingeschlossen ist, dann ist es als Array einzulesen
            if ((substr($row['plp_value'], 0, 2) == '((') && (substr($row['plp_value'], - 2) == '))')) {
                $row['plp_value'] = substr($row['plp_value'], 2, - 2);
                $this->config[$array[1]][$array[2]] = explode(self::$dbtoken, $row['plp_value']);
            } else {
                $this->config[$array[1]][$array[2]] = $row['plp_value'];
            }
        }
    }
}