<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\System;

/*
 * Die Einstellungsfelder heißen hier genauso wie am Inhaltselement und tragen
 * dieselben Beschriftungen. Sie werden deshalb von dort übernommen: Zwei
 * Fassungen desselben Textes gehen mit der Zeit auseinander, und geändert
 * würde erfahrungsgemäß nur eine davon.
 */
System::loadLanguageFile('tl_content');

foreach (['ctvDatei', 'ctvFormat', 'ctvListe', 'ctvSpalten', 'ctvStand', 'ctvStandAus', 'ctvRunden', 'ctvRundenkopfAus', 'ctvMannschaftswahl', 'ctvMannschaftSpieler', 'ctvKreuzKurz', 'ctvDatum', 'ctvHinweise', 'ctv_legend', 'ctv_spalten_legend', 'ctv_runden_legend', 'ctv_ueberschrift_legend', 'ctv_mannschaft_legend', 'ctv_hinweis_legend'] as $feld) {
    if (isset($GLOBALS['TL_LANG']['tl_content'][$feld])) {
        $GLOBALS['TL_LANG']['tl_ctv_inserttag'][$feld] = $GLOBALS['TL_LANG']['tl_content'][$feld];
    }
}

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['titel'] = ['Titel', 'Nur für die Übersicht im Backend. Er sagt, wofür diese Ausgabe gedacht ist — etwa „Olympiade 2026, Deutschland, Runde 1".'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['alias'] = ['Kennung', 'Der Name im Inserttag: Aus der Kennung „olympiade-ger-r1" wird {{ctv::olympiade-ger-r1}}. Ohne Eingabe entsteht sie aus dem Titel.'];

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['ctv_titel_legend'] = 'Name und Kennung';

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['new'] = ['Neue Turnierausgabe', 'Eine Turnierausgabe für einen Inserttag anlegen'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['edit'] = ['Turnierausgabe bearbeiten', 'Turnierausgabe ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['copy'] = ['Turnierausgabe duplizieren', 'Turnierausgabe ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['delete'] = ['Turnierausgabe löschen', 'Turnierausgabe ID %s löschen'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['show'] = ['Details anzeigen', 'Details der Turnierausgabe ID %s anzeigen'];
