<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_LANG']['tl_content']['ctvDatei'] = ['Tournament file', 'The file from the file manager that will be evaluated.'];
$GLOBALS['TL_LANG']['tl_content']['ctvFormat'] = ['File format', 'With "Detect automatically" the format is determined from the file content. Choosing a format by hand only helps if a file is not recognised although it belongs to that format.'];
$GLOBALS['TL_LANG']['tl_content']['ctvListen'] = ['Lists to display', 'With more than one list the lists are shown as tabs. Team lists are silently skipped for individual tournaments.'];
$GLOBALS['TL_LANG']['tl_content']['ctvMannschaftSpieler'] = ['Include players', 'Shows the line-ups in the team list and the individual games in the team matches. This makes the output very long for large tournaments.'];

$GLOBALS['TL_LANG']['tl_content']['ctv_legend'] = 'Tournament viewer';
$GLOBALS['TL_LANG']['tl_content']['ctv_mannschaft_legend'] = 'Team tournaments';

$GLOBALS['TL_LANG']['tl_content']['ctvHinweise'] = ['Show notes on the figures', 'Displays a note above the tables when the stored figures of the tournament file do not match the entered results — for instance because the standings are older than the last round.'];
$GLOBALS['TL_LANG']['tl_content']['ctvKreuzKurz'] = ['Shorten team cross table', 'Shows only the own board points in each cell ("3½") instead of both sides ("3½:½"). The opposing figure appears mirrored in the opponent\'s cell.'];
$GLOBALS['TL_LANG']['tl_content']['ctvStand'] = ['Standings after round', 'Rolls the whole element back to the standings after this round — table, cross table and progress table then show how it looked at the time. The figures are recalculated from the games; a tie-break is only shown if its calculation could be confirmed against the final standings in the file.'];
$GLOBALS['TL_LANG']['tl_content']['ctvRunden'] = ['Rounds to display', 'Restricts the pairing, result and team match lists to individual rounds. Without a selection all rounds are shown. The field does not affect the table or cross table — that is what "Standings after round" is for.'];

$GLOBALS['TL_LANG']['tl_content']['ctv_runden_legend'] = 'Point in time and rounds';
