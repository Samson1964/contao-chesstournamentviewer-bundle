<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\System;

/*
 * The settings fields have the same names as in the content element and carry
 * the same labels; they are taken from there instead of being written twice.
 */
System::loadLanguageFile('tl_content');

foreach (['ctvDatei', 'ctvFormat', 'ctvListe', 'ctvSpalten', 'ctvStand', 'ctvStandAus', 'ctvRunden', 'ctvRundenkopfAus', 'ctvMannschaftswahl', 'ctvMannschaftSpieler', 'ctvKreuzKurz', 'ctvDatum', 'ctvHinweise', 'ctv_legend', 'ctv_spalten_legend', 'ctv_runden_legend', 'ctv_ueberschrift_legend', 'ctv_mannschaft_legend', 'ctv_hinweis_legend'] as $feld) {
    if (isset($GLOBALS['TL_LANG']['tl_content'][$feld])) {
        $GLOBALS['TL_LANG']['tl_ctv_inserttag'][$feld] = $GLOBALS['TL_LANG']['tl_content'][$feld];
    }
}

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['titel'] = ['Title', 'For the back end listing only. It says what this output is meant for — for instance "Olympiad 2026, Germany, round 1".'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['alias'] = ['Key', 'The name inside the insert tag: the key "olympiad-ger-r1" becomes {{ctv::olympiad-ger-r1}}. Without an entry it is generated from the title.'];

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['ctv_titel_legend'] = 'Name and key';

$GLOBALS['TL_LANG']['tl_ctv_inserttag']['new'] = ['New tournament output', 'Create a tournament output for an insert tag'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['edit'] = ['Edit tournament output', 'Edit tournament output ID %s'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['copy'] = ['Duplicate tournament output', 'Duplicate tournament output ID %s'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['delete'] = ['Delete tournament output', 'Delete tournament output ID %s'];
$GLOBALS['TL_LANG']['tl_ctv_inserttag']['show'] = ['Show details', 'Show details of tournament output ID %s'];
