<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_LANG']['tl_content']['ctvDatei'] = ['Tournament file', 'The file from the file manager that will be evaluated. After saving you can choose what to output from it.'];
$GLOBALS['TL_LANG']['tl_content']['ctvFormat'] = ['File format', 'With "Detect automatically" the format is determined from the file content. Choosing a format by hand only helps if a file is not recognised although it belongs to that format.'];
$GLOBALS['TL_LANG']['tl_content']['ctvListe'] = ['List to display', 'One element shows exactly one list. Several lists as tabs are created by placing several elements between "Wrapper start" and "Wrapper end".'];
$GLOBALS['TL_LANG']['tl_content']['ctvSpalten'] = ['Columns', 'Only columns the chosen file can fill are offered. The order can be dragged and is the order of the output.'];
$GLOBALS['TL_LANG']['tl_content']['ctvStand'] = ['Standings after round', 'Rolls the output back to the standings after this round. "Current standings" uses the stored figures of the file and therefore means the last round played. For an earlier round the figures are recalculated from the games; a tie-break is only shown if its calculation could be confirmed against the final standings in the file.'];
$GLOBALS['TL_LANG']['tl_content']['ctvRunden'] = ['Rounds to display', 'Restricts the output to individual rounds. Without a selection all rounds are shown.'];
$GLOBALS['TL_LANG']['tl_content']['ctvHinweise'] = ['Show notes on the figures', 'Displays a note above the table when the stored figures of the tournament file do not match the entered results — for instance because the standings are older than the last round.'];
$GLOBALS['TL_LANG']['tl_content']['ctvMannschaftSpieler'] = ['Include players', 'Shows the line-ups in the team list and the individual games in the team matches. This makes the output very long for large tournaments.'];
$GLOBALS['TL_LANG']['tl_content']['ctvKreuzKurz'] = ['Shorten cross table', 'Shows only the own board points in each cell ("3½") instead of both sides ("3½:½"). The opposing figure appears mirrored in the opponent\'s cell.'];

$GLOBALS['TL_LANG']['tl_content']['ctv_legend'] = 'Tournament file and output';
$GLOBALS['TL_LANG']['tl_content']['ctv_spalten_legend'] = 'Columns';
$GLOBALS['TL_LANG']['tl_content']['ctv_runden_legend'] = 'Point in time and rounds';
$GLOBALS['TL_LANG']['tl_content']['ctv_mannschaft_legend'] = 'Teams';
$GLOBALS['TL_LANG']['tl_content']['ctv_hinweis_legend'] = 'Notes';
$GLOBALS['TL_LANG']['tl_content']['ctvDatum'] = ['Show file date', 'Puts the date of the last change to the tournament file below the table. The modification date in the file manager is used — tournament organisers upload a new version after each round.'];
