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
$GLOBALS['TL_LANG']['ctv']['listen']['fortschrittohne'] = 'Fortschritt ohne Punktestand';
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

// Kurzformen fuer schmale Spalten. Wo eine Tabelle viele Spalten traegt,
// bestimmt der Kopf die Breite, nicht der Inhalt — dort steht die Kurzform.
$GLOBALS['TL_LANG']['ctv']['spalte']['platzKurz'] = 'Pl.';
$GLOBALS['TL_LANG']['ctv']['spalte']['punkteKurz'] = 'Pkt.';
$GLOBALS['TL_LANG']['ctv']['spalte']['brettKurz'] = 'Br.';
$GLOBALS['TL_LANG']['ctv']['spalte']['ergebnisKurz'] = '-';
$GLOBALS['TL_LANG']['ctv']['spalte']['wertung'] = 'TWZ';

// Bezeichnung der Wertungszahl, die im Turnier den Ausschlag gibt
$GLOBALS['TL_LANG']['ctv']['wertung']['twz'] = 'TWZ';
$GLOBALS['TL_LANG']['ctv']['wertung']['elo'] = 'Elo';
$GLOBALS['TL_LANG']['ctv']['wertung']['nwz'] = 'NWZ';

// Kurzformen der Feinwertungen. Der Schluessel ist die Bezeichnung, wie sie
// aus der Turnierdatei kommt; fehlt ein Eintrag, bleibt der volle Name stehen.
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Sonneborn-Berger'] = 'SoBe';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Buchholzwertung'] = 'Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Mittlere Buchholz'] = 'Ø Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Buchholzsumme'] = 'Σ Buchholz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Mannschaftspunkte'] = 'MP';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Brettpunkte'] = 'BP';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Summenwertung'] = 'Summe';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Gegner-NWZ/Elo-Mittel'] = 'Ø Gegner';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Rating-Leistung (NWZ/TWZ)'] = 'Leistung';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Rating-Differenz (NWZ/TWZ)'] = 'Differenz';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Drei-Punkte-Wertung'] = '3 Punkte';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Drei-Punkte-Farbwertung'] = '3 Punkte Farbe';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Berliner Wertung'] = 'Berliner';
$GLOBALS['TL_LANG']['ctv']['feinwertungKurz']['Schmuljan-Wertung'] = 'Schmuljan';
