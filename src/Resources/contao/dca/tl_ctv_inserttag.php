<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Controller;
use Contao\DC_Table;

/*
 * Die Turnierausgaben des Backend-Moduls „Turnier-Inserttags".
 *
 * Ein Datensatz ist dasselbe wie ein Inhaltselement „Turnierausgabe", nur
 * ohne festen Platz auf einer Seite: Er wird über den Inserttag
 * `{{ctv::kennung}}` eingebunden — in einem Textelement, in einer Nachricht,
 * in einem Template. So lassen sich einzelne Tabellen in eine Box setzen, in
 * der schon anderer Text steht.
 *
 * Die Einstellungsfelder sind dieselben wie am Inhaltselement und werden von
 * dort übernommen, statt sie ein zweites Mal zu schreiben: Zwei Fassungen
 * derselben Felder gingen über kurz oder lang auseinander. Die Rückrufe der
 * Maske hängen als Dienst an `MaskeListener`, angemeldet für beide Tabellen.
 */
Controller::loadDataContainer('tl_content');

$GLOBALS['TL_DCA']['tl_ctv_inserttag'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['titel'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        // Die Beschriftung baut ein Rückruf: Der Inserttag steht dort mit
        // maskierten Klammern, damit ihn das Backend nicht selbst ersetzt.
        'label' => [
            'fields' => ['titel', 'alias'],
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(this.getAttribute(\'data-message\')))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    /*
     * Dieselbe Abfolge wie am Inhaltselement: erst die Datei, nach dem
     * Speichern die Ausgabe, dann deren Einstellungen. Gekürzt wird die
     * Palette vom Rückruf, nicht hier.
     */
    'palettes' => [
        'default' => '{ctv_titel_legend},titel,alias;'
            .'{ctv_legend},ctvDatei,ctvFormat,ctvListe;'
            .'{ctv_spalten_legend},ctvSpalten;'
            .'{ctv_runden_legend},ctvStand,ctvRunden;'
            .'{ctv_ueberschrift_legend},ctvStandAus,ctvRundenkopfAus;'
            .'{ctv_mannschaft_legend},ctvMannschaftswahl,ctvMannschaftSpieler,ctvKreuzKurz;'
            .'{ctv_hinweis_legend},ctvDatum,ctvHinweise',
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'titel' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        /*
         * Die Kennung steht im Inserttag. Sie wird aus dem Titel gebildet,
         * lässt sich aber überschreiben: `{{ctv::olympiade-ger-r1}}` liest
         * sich besser als `{{ctv::7}}` und bleibt gleich, wenn der Datensatz
         * einmal kopiert und neu angelegt wird.
         */
        'alias' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'alias',
                'unique' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
    ],
];

/*
 * Die Einstellungsfelder aus `tl_content` übernehmen. Das alte Feld
 * `ctvListen` bleibt außen vor: Es rettet nur die erste Liste eines
 * Inhaltselements aus der Zeit vor Fassung 1.8.0 und hat in einer neuen
 * Tabelle nichts zu suchen.
 */
foreach (['ctvDatei', 'ctvFormat', 'ctvListe', 'ctvSpalten', 'ctvStand', 'ctvStandAus', 'ctvRunden', 'ctvRundenkopfAus', 'ctvMannschaftswahl', 'ctvMannschaftSpieler', 'ctvKreuzKurz', 'ctvDatum', 'ctvHinweise'] as $feld) {
    if (isset($GLOBALS['TL_DCA']['tl_content']['fields'][$feld])) {
        $GLOBALS['TL_DCA']['tl_ctv_inserttag']['fields'][$feld] = $GLOBALS['TL_DCA']['tl_content']['fields'][$feld];
    }
}
