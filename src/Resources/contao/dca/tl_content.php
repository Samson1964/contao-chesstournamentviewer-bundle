<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Paletten der drei Inhaltselemente.
 *
 * **Ein Element gibt genau eine Liste aus.** Wer mehrere Listen als Reiter
 * zeigen will, legt sie einzeln an und klammert sie mit „Umschlag Anfang" und
 * „Umschlag Ende" ein — so, wie Contao es mit Akkordeon und Slider hält.
 *
 * Die Palette der Turnierausgabe ist die vollständige; ein Rückruf streicht
 * daraus alles, was noch nicht an der Reihe ist. Der Redakteur sieht dadurch
 * nacheinander: erst die Dateiauswahl, nach dem Speichern die Auswahl der
 * Ausgabe, und nach deren Wahl die Einstellungen genau dieser Ausgabe.
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'] =
    '{type_legend},type,headline;'
    .'{ctv_legend},ctvDatei,ctvFormat,ctvListe;'
    .'{ctv_spalten_legend},ctvSpalten;'
    .'{ctv_runden_legend},ctvStand,ctvRunden;'
    .'{ctv_ueberschrift_legend},ctvUeberschriftenAus;'
    .'{ctv_mannschaft_legend},ctvMannschaftswahl,ctvMannschaftSpieler,ctvKreuzKurz;'
    .'{ctv_hinweis_legend},ctvDatum,ctvHinweise;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewerStart'] =
    '{type_legend},type,headline;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewerStop'] =
    '{type_legend},type;'
    .'{template_legend:hide},customTpl;'
    .'{invisible_legend:hide},invisible,start,stop';

/*
 * Die Dateiendungen der Dateiauswahl, die Formatauswahl, die Auswahl der
 * Ausgabe, die Spalten und die Runden tragen Rückrufe nach: Sie alle hängen
 * an Angaben, die erst zur Laufzeit feststehen — an den registrierten
 * Formaten und am Inhalt der gewählten Datei. Die Rückrufe hängen als
 * Dienst-Tag `contao.callback` an MaskeListener und stehen deshalb nicht
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
 * Die auszugebende Liste. Ein Auswahlfeld und keine Mehrfachauswahl: Ein
 * Element gibt eine Liste aus. `submitOnChange` schickt die Maske ab, damit
 * die Einstellungen dieser Liste sofort erscheinen.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvListe'] = [
    'exclude' => true,
    'inputType' => 'select',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['listen'],
    'eval' => [
        'mandatory' => true,
        'includeBlankOption' => true,
        'submitOnChange' => true,
        'tl_class' => 'w50',
    ],
    'sql' => "varchar(32) NOT NULL default ''",
];

/*
 * Die Spalten der gewählten Liste. Sortierbar: Die gespeicherte Reihenfolge
 * ist die der Ausgabe. Angeboten wird nur, was die gewählte Datei hergibt;
 * vorangehakt sind die gebräuchlichen.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvSpalten'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['spaltenwahl'],
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

/*
 * Die Auswahl beider Rundenfelder entsteht aus der Turnierdatei; die
 * Beschriftungen schreibt derselbe Rückruf in die Sprachdatei, auf die hier
 * verwiesen wird.
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

/*
 * Blendet die automatischen Überschriften aus: die Zeile „Stand nach Runde"
 * über der Ausgabe und die Überschriften „Runde 1", „Runde 2" über den
 * Tabellen. Ein Kästchen für beide — es sind zwei Ausprägungen derselben
 * Sache, und wer die eine nicht will, will in aller Regel auch die andere
 * nicht. Standard sind die Überschriften sichtbar: Eine zurückgesetzte
 * Tabelle ohne Angabe sähe aus wie die Endtabelle, und eine Liste über
 * mehrere Runden wäre ohne Rundenüberschriften nicht zu lesen.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvUeberschriftenAus'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
];

/*
 * Beschränkt Paarungen, Ergebnisse und Wettkämpfe auf einzelne Mannschaften.
 * Die Auswahl entsteht aus der Datei; gespeichert werden die
 * Mannschaftsnummern.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvMannschaftswahl'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['mannschaftswahl'],
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr ctv-mannschaftswahl',
    ],
    'sql' => 'blob NULL',
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

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvDatum'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
    ],
    'sql' => "char(1) NOT NULL default ''",
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

/*
 * Bis Fassung 1.14.0 gab es zwei Kästchen für die beiden automatischen
 * Überschriften. Die Felder bleiben in der Datenbank und wirken weiter,
 * damit ein Element, das eine der beiden Überschriften abgeschaltet hatte,
 * sie nicht wieder zeigt; in der Maske erscheinen sie nicht mehr.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvStandAus'] = [
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvRundenkopfAus'] = [
    'sql' => "char(1) NOT NULL default ''",
];

/*
 * Bis Fassung 1.7.0 führte ein Element mehrere Listen und baute die Reiter
 * selbst. Das Feld bleibt in der Datenbank, damit ein bestehendes Element
 * seine erste Liste behält; in der Maske erscheint es nicht mehr.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['ctvListen'] = [
    'sql' => 'blob NULL',
];
