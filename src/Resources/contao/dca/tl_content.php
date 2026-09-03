<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Palette des Inhaltselements „Turnier-Betrachter".
 *
 * Die Palette enthält bewusst kein Feld `guests`: Es gibt dieses Feld nur in
 * Contao 4.13, unter Contao 5 wurde es entfernt. `protected` mit der
 * Unterpalette `groups` gibt es in beiden Fassungen.
 *
 * Die Palette ist die vollständige; ein Rückruf streicht daraus, was zur
 * gewählten Datei und zu den gewählten Listen nicht passt — die Feldgruppe
 * „Mannschaftsturniere" bei einem Einzelturnier, die Rundenauswahl bei einem
 * einrundigen Turnier, jede Einstellung, deren Liste nicht gewählt ist.
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'] =
    '{type_legend},type,headline;'
    .'{ctv_legend},ctvDatei,ctvFormat,ctvListen,ctvHinweise;'
    .'{ctv_runden_legend},ctvStand,ctvRunden;'
    .'{ctv_spalten_legend},ctvSpaltenTeilnehmer,ctvSpaltenRangliste;'
    .'{ctv_mannschaft_legend},ctvMannschaftSpieler,ctvKreuzKurz;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

/*
 * Die Dateiendungen der Dateiauswahl, die Formatauswahl, die Listenauswahl
 * und die Anpassung an die Turnierart tragen Rückrufe nach: Alle vier hängen
 * an Angaben, die erst zur Laufzeit feststehen — an den registrierten
 * Formaten und am Inhalt der gewählten Datei. Die Rückrufe hängen als
 * Dienst-Tag `contao.callback` an TlContentListener und stehen deshalb nicht
 * hier. Bis sie greifen, steht `swt` als Rückfallebene im Feld — wäre es
 * leer, böte die Dateiauswahl jede beliebige Datei an.
 */

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvDatei'] = [
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'mandatory' => true,
        'extensions' => 'swt',
        'tl_class' => 'clr',
    ],
    'sql' => 'binary(16) NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvFormat'] = [
    'exclude' => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['formate'],
    'eval' => [
        'mandatory' => true,
        'tl_class' => 'w50',
    ],
    'default' => 'auto',
    'sql' => "varchar(32) NOT NULL default 'auto'",
];

/*
 * Die Listenauswahl ist ein `checkboxWizard` und kein einfaches
 * Kästchenfeld: Der Wizard lässt sich sortieren und speichert die
 * Reihenfolge, und genau in dieser Reihenfolge erscheinen später die Reiter.
 * Den Widget gibt es in Contao 4.13 wie in Contao 5.
 *
 * `submitOnChange` schickt die Maske beim Anhaken ab, damit die
 * dazugehörigen Einstellungen sofort erscheinen und die überflüssigen
 * verschwinden. Bei Kästchen wirkt das — anders als bei der Dateiauswahl, wo
 * der Dateiwähler den Wert per Skript setzt und dabei kein Ereignis auslöst.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvListen'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['listen'],
    'eval' => [
        'multiple' => true,
        'mandatory' => true,
        'submitOnChange' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

/*
 * Die Auswahl beider Rundenfelder entsteht aus der Turnierdatei; die
 * Beschriftungen schreibt derselbe Rückruf in die Sprachdatei, auf die hier
 * verwiesen wird. Deshalb steht in beiden Feldern keine feste Liste.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvStand'] = [
    'exclude' => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['stand'],
    'eval' => [
        'includeBlankOption' => false,
        'submitOnChange' => true,
        'tl_class' => 'w50 clr',
    ],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvRunden'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['runden'],
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

/*
 * Die Spaltenauswahl von Teilnehmerliste und Rangliste. Beide sind
 * sortierbare Kästchenfelder: Die gespeicherte Reihenfolge ist die der
 * Spalten in der Ausgabe. Angeboten wird nur, was die gewählte Datei
 * hergibt — eine Elo-Spalte in einem Turnier ohne Elo-Zahlen wäre ein
 * Kästchen ohne Wirkung.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvSpaltenTeilnehmer'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['spaltenwahl'],
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvSpaltenRangliste'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['spaltenwahl'],
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvHinweise'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvMannschaftSpieler'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvKreuzKurz'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
