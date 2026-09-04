<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_LANG']['CTE']['schach'] = 'Chess elements';
$GLOBALS['TL_LANG']['CTE']['chesstournamentviewer'] = ['Chess tournament viewer – output', 'Displays one list from a tournament file: players, standings, cross table, results and more.'];
$GLOBALS['TL_LANG']['CTE']['chesstournamentviewerStart'] = ['Chess tournament viewer – wrapper start', 'Opens a wrapper. The tournament outputs that follow until the wrapper end appear as tabs.'];
$GLOBALS['TL_LANG']['CTE']['chesstournamentviewerStop'] = ['Chess tournament viewer – wrapper end', 'Closes the wrapper.'];

$GLOBALS['TL_LANG']['ctv']['listen']['turnierdaten'] = 'Tournament data';
$GLOBALS['TL_LANG']['ctv']['listen']['teilnehmer'] = 'Players';
$GLOBALS['TL_LANG']['ctv']['listen']['rangliste'] = 'Standings';
$GLOBALS['TL_LANG']['ctv']['listen']['kreuztabelle'] = 'Cross table';
$GLOBALS['TL_LANG']['ctv']['listen']['fortschritt'] = 'Progress table';
$GLOBALS['TL_LANG']['ctv']['listen']['fortschrittohne'] = 'Progress without running total';
$GLOBALS['TL_LANG']['ctv']['listen']['paarungen'] = 'Pairings';
$GLOBALS['TL_LANG']['ctv']['listen']['ergebnisse'] = 'Results';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaften'] = 'Teams';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftsrangliste'] = 'Team standings';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftspaarungen'] = 'Team matches';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftskreuztabelle'] = 'Team cross table';

$GLOBALS['TL_LANG']['ctv']['formate']['auto'] = 'Detect automatically';
$GLOBALS['TL_LANG']['ctv']['formate']['swt'] = 'SWT (Swiss-Chess)';
$GLOBALS['TL_LANG']['ctv']['formate']['swissmanager'] = 'Swiss-Manager';

$GLOBALS['TL_LANG']['ctv']['turnierdaten']['name'] = 'Tournament';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['untertitel'] = 'Subtitle';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['ort'] = 'Venue';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['zeitraum'] = 'Dates';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['modusText'] = 'System';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['runden'] = 'Rounds';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['teilnehmerzahl'] = 'Players';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['mannschaftszahl'] = 'Teams';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['bretter'] = 'Boards per match';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['zeitkontrolle'] = 'Time control';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['feinwertung1Text'] = 'First tie-break';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['feinwertung2Text'] = 'Second tie-break';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['twzErmittlungText'] = 'Rating used';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['schiedsrichter'] = 'Arbiter';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['organisator'] = 'Organiser';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['bemerkung'] = 'Remark';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['format'] = 'File format';

$GLOBALS['TL_LANG']['ctv']['spalte']['nr'] = 'No.';
$GLOBALS['TL_LANG']['ctv']['spalte']['platz'] = 'Rank';
$GLOBALS['TL_LANG']['ctv']['spalte']['titel'] = 'Title';
$GLOBALS['TL_LANG']['ctv']['spalte']['name'] = 'Name';
$GLOBALS['TL_LANG']['ctv']['spalte']['twz'] = 'Rating';
$GLOBALS['TL_LANG']['ctv']['spalte']['elo'] = 'Elo';
$GLOBALS['TL_LANG']['ctv']['spalte']['dwz'] = 'DWZ';
$GLOBALS['TL_LANG']['ctv']['spalte']['verein'] = 'Club';
$GLOBALS['TL_LANG']['ctv']['spalte']['mannschaft'] = 'Team';
$GLOBALS['TL_LANG']['ctv']['spalte']['land'] = 'Country';
$GLOBALS['TL_LANG']['ctv']['spalte']['punkte'] = 'Points';
$GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung1'] = 'Tie-break 1';
$GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung2'] = 'Tie-break 2';
$GLOBALS['TL_LANG']['ctv']['spalte']['bilanz'] = 'W/D/L';
$GLOBALS['TL_LANG']['ctv']['spalte']['partien'] = 'Games';
$GLOBALS['TL_LANG']['ctv']['spalte']['brett'] = 'Board';
$GLOBALS['TL_LANG']['ctv']['spalte']['tisch'] = 'Table';
$GLOBALS['TL_LANG']['ctv']['spalte']['weiss'] = 'White';
$GLOBALS['TL_LANG']['ctv']['spalte']['schwarz'] = 'Black';
$GLOBALS['TL_LANG']['ctv']['spalte']['ergebnis'] = 'Result';
$GLOBALS['TL_LANG']['ctv']['spalte']['runde'] = 'Round';
$GLOBALS['TL_LANG']['ctv']['spalte']['gegner'] = 'Opponent';
$GLOBALS['TL_LANG']['ctv']['spalte']['stand'] = 'Total';
$GLOBALS['TL_LANG']['ctv']['spalte']['summe'] = 'Sum';
$GLOBALS['TL_LANG']['ctv']['spalte']['mannschaftspunkte'] = 'MP';
$GLOBALS['TL_LANG']['ctv']['spalte']['brettpunkte'] = 'BP';
$GLOBALS['TL_LANG']['ctv']['spalte']['kaempfe'] = 'Matches';
$GLOBALS['TL_LANG']['ctv']['spalte']['freilose'] = 'Byes';
$GLOBALS['TL_LANG']['ctv']['spalte']['spieler'] = 'Players';
$GLOBALS['TL_LANG']['ctv']['spalte']['schnitt'] = 'Avg. rating';
$GLOBALS['TL_LANG']['ctv']['spalte']['wettkampf'] = 'Match';

$GLOBALS['TL_LANG']['ctv']['runde'] = 'Round %s';
$GLOBALS['TL_LANG']['ctv']['spielfrei'] = 'bye';
$GLOBALS['TL_LANG']['ctv']['freilos'] = 'Bye';
$GLOBALS['TL_LANG']['ctv']['unsicher'] = '(designation uncertain)';
$GLOBALS['TL_LANG']['ctv']['weiss'] = 'White';
$GLOBALS['TL_LANG']['ctv']['schwarz'] = 'Black';
$GLOBALS['TL_LANG']['ctv']['keineDaten'] = 'No data available for this list.';
$GLOBALS['TL_LANG']['ctv']['fehler'] = 'The tournament file could not be read: %s';

$GLOBALS['TL_LANG']['ctv']['spalte']['platzKurz'] = 'Rk.';
$GLOBALS['TL_LANG']['ctv']['spalte']['punkteKurz'] = 'Pts.';
$GLOBALS['TL_LANG']['ctv']['spalte']['brettKurz'] = 'Bd.';
$GLOBALS['TL_LANG']['ctv']['spalte']['ergebnisKurz'] = '-';
$GLOBALS['TL_LANG']['ctv']['spalte']['wertung'] = 'Rtg';

$GLOBALS['TL_LANG']['ctv']['wertung']['twz'] = 'Rtg';
$GLOBALS['TL_LANG']['ctv']['wertung']['elo'] = 'Elo';
$GLOBALS['TL_LANG']['ctv']['wertung']['nwz'] = 'NWZ';

$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Sonneborn-Berger'] = 'SB';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Buchholzwertung'] = 'Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Mittlere Buchholz'] = 'Ø Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Buchholzsumme'] = 'Σ Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Mannschaftspunkte'] = 'MP';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Brettpunkte'] = 'BP';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Summenwertung'] = 'Sum';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Gegner-NWZ/Elo-Mittel'] = 'Ø Opp.';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Rating-Leistung (NWZ/TWZ)'] = 'Perf.';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Rating-Differenz (NWZ/TWZ)'] = 'Diff.';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Drei-Punkte-Wertung'] = '3 points';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Drei-Punkte-Farbwertung'] = '3 points colour';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Berliner Wertung'] = 'Berlin';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Schmuljan-Wertung'] = 'Schmuljan';

$GLOBALS['TL_LANG']['ctv']['ohneMannschaft'] = 'Without team';
$GLOBALS['TL_LANG']['ctv']['uploadWarnung'] = 'Missing under "Settings → Allowed file types": %s. Tournament files therefore cannot be uploaded to the file manager.';

$GLOBALS['TL_LANG']['ctv']['standGanz'] = 'Current standings (last round)';
$GLOBALS['TL_LANG']['ctv']['standRunde'] = 'Standings after round %d';
$GLOBALS['TL_LANG']['ctv']['rundeNummer'] = 'Round %d';
$GLOBALS['TL_LANG']['ctv']['standTitel'] = 'Standings after round %d';
$GLOBALS['TL_LANG']['ctv']['hinweisSchnitt'] = 'These are the standings after round %d. Points and placings are recalculated from the games, not taken from the tournament file.';
$GLOBALS['TL_LANG']['ctv']['hinweisOhneFeinwertung'] = 'The tie-break %s is omitted: its calculation could not be confirmed against the final standings of the tournament.';

$GLOBALS['TL_LANG']['ctv']['spalte']['geburtsjahr'] = 'Born';
$GLOBALS['TL_LANG']['ctv']['spalte']['fideId'] = 'FIDE ID';
$GLOBALS['TL_LANG']['ctv']['spalte']['gruppe'] = 'Group';

$GLOBALS['TL_LANG']['ctv']['aktualisiert'] = 'Tournament file as of %s';
