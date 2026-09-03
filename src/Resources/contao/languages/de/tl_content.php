<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Beschriftungen der Felder
$GLOBALS['TL_LANG']['tl_content']['ctvDatei'] = ['Turnierdatei', 'Die Datei aus der Dateiverwaltung, die ausgewertet werden soll. Nach dem Speichern lässt sich auswählen, was aus ihr ausgegeben wird.'];
$GLOBALS['TL_LANG']['tl_content']['ctvFormat'] = ['Format der Turnierdatei', 'Bei „Automatisch erkennen" wird das Format am Inhalt der Datei bestimmt. Ein Format von Hand zu wählen hilft nur, wenn eine Datei nicht erkannt wird, obwohl sie zum Format gehört.'];
$GLOBALS['TL_LANG']['tl_content']['ctvListe'] = ['Auszugebende Liste', 'Ein Element gibt genau eine Liste aus. Mehrere Listen als Reiter entstehen, indem mehrere Elemente zwischen „Umschlag Anfang" und „Umschlag Ende" gestellt werden.'];
$GLOBALS['TL_LANG']['tl_content']['ctvSpalten'] = ['Spalten', 'Angeboten wird, was die gewählte Datei hergibt. Die Reihenfolge lässt sich ziehen und ist die der Ausgabe.'];
$GLOBALS['TL_LANG']['tl_content']['ctvStand'] = ['Stand nach Runde', 'Versetzt die Ausgabe auf den Stand nach dieser Runde zurück. „Aktueller Stand" nimmt die gespeicherten Zahlen der Datei und meint damit die zuletzt gespielte Runde. Für eine frühere Runde werden die Zahlen aus den Partien nachgerechnet; eine Feinwertung erscheint dann nur, wenn sich ihre Rechenweise am Endstand der Datei bestätigen ließ.'];
$GLOBALS['TL_LANG']['tl_content']['ctvRunden'] = ['Angezeigte Runden', 'Beschränkt die Ausgabe auf einzelne Runden. Ohne Auswahl erscheinen alle.'];
$GLOBALS['TL_LANG']['tl_content']['ctvHinweise'] = ['Hinweise zu den Zahlen anzeigen', 'Zeigt über der Tabelle, wenn die gespeicherten Zahlen der Turnierdatei mit den eingetragenen Ergebnissen nicht zusammengehen — etwa weil die Rangliste älter ist als die letzte Runde.'];
$GLOBALS['TL_LANG']['tl_content']['ctvMannschaftSpieler'] = ['Spieler mit ausgeben', 'Zeigt in der Mannschaftsliste die Aufstellungen und in den Wettkämpfen die Einzelpartien. Bei großen Turnieren wird die Ausgabe dadurch sehr lang.'];
$GLOBALS['TL_LANG']['tl_content']['ctvKreuzKurz'] = ['Kreuztabelle kürzen', 'Zeigt in jeder Zelle nur die eigenen Brettpunkte („3½") statt beider Seiten („3½:½"). Die Gegenzahl steht gespiegelt in der Zelle des Gegners.'];

// Überschriften der Feldgruppen
$GLOBALS['TL_LANG']['tl_content']['ctv_legend'] = 'Turnierdatei und Ausgabe';
$GLOBALS['TL_LANG']['tl_content']['ctv_spalten_legend'] = 'Spalten';
$GLOBALS['TL_LANG']['tl_content']['ctv_runden_legend'] = 'Zeitpunkt und Runden';
$GLOBALS['TL_LANG']['tl_content']['ctv_mannschaft_legend'] = 'Mannschaften';
$GLOBALS['TL_LANG']['tl_content']['ctv_hinweis_legend'] = 'Hinweise';
