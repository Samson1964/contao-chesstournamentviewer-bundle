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
 * Die Feldgruppe „Mannschaftsturniere" entfernt ein Rückruf wieder, sobald
 * die gewählte Datei ein Einzelturnier ist.
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'] =
    '{type_legend},type,headline;'
    .'{ctv_legend},ctvDatei,ctvFormat,ctvListen,ctvHinweise;'
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

$GLOBALS['TL_DCA']['tl_content']['fields']['ctvListen'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['ctv']['listen'],
    'eval' => [
        'multiple' => true,
        'mandatory' => true,
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
