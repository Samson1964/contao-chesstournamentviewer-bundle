<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Gruppe der Schach-Inhaltselemente. Denselben Schlüssel benutzen die
 * übrigen Schach-Bundles, damit alle Elemente im Backend beieinanderstehen.
 */
$GLOBALS['TL_LANG']['CTE']['schach'] = 'Schach-Elemente';
$GLOBALS['TL_LANG']['CTE']['chesstournamentviewer'] = ['Turnier-Betrachter', 'Gibt eine Turnierdatei einer Schachturnierverwaltung als Tabellen aus.'];

// Bezeichnungen der Listen, zugleich Beschriftung der Reiter im Frontend
$GLOBALS['TL_LANG']['ctv']['listen']['turnierdaten'] = 'Turnierdaten';
$GLOBALS['TL_LANG']['ctv']['listen']['teilnehmer'] = 'Teilnehmer';
$GLOBALS['TL_LANG']['ctv']['listen']['rangliste'] = 'Rangliste';
$GLOBALS['TL_LANG']['ctv']['listen']['kreuztabelle'] = 'Kreuztabelle';
$GLOBALS['TL_LANG']['ctv']['listen']['fortschritt'] = 'Fortschrittstabelle';
$GLOBALS['TL_LANG']['ctv']['listen']['paarungen'] = 'Paarungen';
$GLOBALS['TL_LANG']['ctv']['listen']['ergebnisse'] = 'Ergebnisse';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaften'] = 'Mannschaften';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftsrangliste'] = 'Mannschaftstabelle';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftspaarungen'] = 'Wettkämpfe';
$GLOBALS['TL_LANG']['ctv']['listen']['mannschaftskreuztabelle'] = 'Kreuztabelle der Mannschaften';

// Auswahl im Feld „Format der Turnierdatei"
$GLOBALS['TL_LANG']['ctv']['formate']['auto'] = 'Automatisch erkennen';
$GLOBALS['TL_LANG']['ctv']['formate']['swt'] = 'SWT (Swiss-Chess)';

// Beschriftungen in der Liste der Turnierdaten
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['name'] = 'Turnier';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['untertitel'] = 'Untertitel';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['ort'] = 'Ort';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['zeitraum'] = 'Zeitraum';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['modusText'] = 'Turnierform';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['runden'] = 'Runden';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['teilnehmerzahl'] = 'Teilnehmer';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['mannschaftszahl'] = 'Mannschaften';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['bretter'] = 'Bretter je Wettkampf';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['zeitkontrolle'] = 'Bedenkzeit';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['feinwertung1Text'] = 'Erste Feinwertung';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['feinwertung2Text'] = 'Zweite Feinwertung';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['twzErmittlungText'] = 'TWZ-Ermittlung';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['schiedsrichter'] = 'Schiedsrichter';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['organisator'] = 'Turnierorganisator';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['bemerkung'] = 'Bemerkung';
$GLOBALS['TL_LANG']['ctv']['turnierdaten']['format'] = 'Dateiformat';

// Spaltenköpfe der Tabellen
$GLOBALS['TL_LANG']['ctv']['spalte']['nr'] = 'Nr.';
$GLOBALS['TL_LANG']['ctv']['spalte']['platz'] = 'Platz';
$GLOBALS['TL_LANG']['ctv']['spalte']['titel'] = 'Titel';
$GLOBALS['TL_LANG']['ctv']['spalte']['name'] = 'Name';
$GLOBALS['TL_LANG']['ctv']['spalte']['twz'] = 'TWZ';
$GLOBALS['TL_LANG']['ctv']['spalte']['elo'] = 'Elo';
$GLOBALS['TL_LANG']['ctv']['spalte']['dwz'] = 'DWZ';
$GLOBALS['TL_LANG']['ctv']['spalte']['verein'] = 'Verein';
$GLOBALS['TL_LANG']['ctv']['spalte']['mannschaft'] = 'Mannschaft';
$GLOBALS['TL_LANG']['ctv']['spalte']['land'] = 'Land';
$GLOBALS['TL_LANG']['ctv']['spalte']['punkte'] = 'Punkte';
$GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung1'] = 'Feinwertung 1';
$GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung2'] = 'Feinwertung 2';
$GLOBALS['TL_LANG']['ctv']['spalte']['bilanz'] = 'S/R/N';
$GLOBALS['TL_LANG']['ctv']['spalte']['partien'] = 'Partien';
$GLOBALS['TL_LANG']['ctv']['spalte']['brett'] = 'Brett';
$GLOBALS['TL_LANG']['ctv']['spalte']['tisch'] = 'Tisch';
$GLOBALS['TL_LANG']['ctv']['spalte']['weiss'] = 'Weiß';
$GLOBALS['TL_LANG']['ctv']['spalte']['schwarz'] = 'Schwarz';
$GLOBALS['TL_LANG']['ctv']['spalte']['ergebnis'] = 'Ergebnis';
$GLOBALS['TL_LANG']['ctv']['spalte']['runde'] = 'Runde';
$GLOBALS['TL_LANG']['ctv']['spalte']['gegner'] = 'Gegner';
$GLOBALS['TL_LANG']['ctv']['spalte']['stand'] = 'Stand';
$GLOBALS['TL_LANG']['ctv']['spalte']['summe'] = 'Summe';
$GLOBALS['TL_LANG']['ctv']['spalte']['mannschaftspunkte'] = 'MP';
$GLOBALS['TL_LANG']['ctv']['spalte']['brettpunkte'] = 'BP';
$GLOBALS['TL_LANG']['ctv']['spalte']['kaempfe'] = 'Wettkämpfe';
$GLOBALS['TL_LANG']['ctv']['spalte']['freilose'] = 'Freilose';
$GLOBALS['TL_LANG']['ctv']['spalte']['spieler'] = 'Spieler';
$GLOBALS['TL_LANG']['ctv']['spalte']['schnitt'] = 'Ø TWZ';
$GLOBALS['TL_LANG']['ctv']['spalte']['wettkampf'] = 'Wettkampf';

// Einzelne Wörter und Wendungen in der Ausgabe
$GLOBALS['TL_LANG']['ctv']['runde'] = 'Runde %s';
$GLOBALS['TL_LANG']['ctv']['spielfrei'] = 'spielfrei';
$GLOBALS['TL_LANG']['ctv']['freilos'] = 'Freilos';
$GLOBALS['TL_LANG']['ctv']['unsicher'] = '(Bezeichnung unsicher)';
$GLOBALS['TL_LANG']['ctv']['weiss'] = 'Weiß';
$GLOBALS['TL_LANG']['ctv']['schwarz'] = 'Schwarz';
$GLOBALS['TL_LANG']['ctv']['keineDaten'] = 'Zu dieser Liste liegen keine Daten vor.';
$GLOBALS['TL_LANG']['ctv']['fehler'] = 'Die Turnierdatei konnte nicht gelesen werden: %s';
