<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Beschriftungen der Felder
$GLOBALS['TL_LANG']['tl_content']['ctvDatei'] = ['Turnierdatei', 'Die Datei aus der Dateiverwaltung, die ausgewertet werden soll.'];
$GLOBALS['TL_LANG']['tl_content']['ctvFormat'] = ['Format der Turnierdatei', 'Bei „Automatisch erkennen" wird das Format am Inhalt der Datei bestimmt. Ein Format von Hand zu wählen hilft nur, wenn eine Datei nicht erkannt wird, obwohl sie zum Format gehört.'];
$GLOBALS['TL_LANG']['tl_content']['ctvListen'] = ['Auszugebende Listen', 'Bei mehr als einer Liste erscheinen die Listen als Reiter. Listen für Mannschaftsturniere werden bei einem Einzelturnier stillschweigend übergangen.'];
$GLOBALS['TL_LANG']['tl_content']['ctvMannschaftSpieler'] = ['Spieler mit ausgeben', 'Zeigt in der Mannschaftsliste die Aufstellungen und in den Wettkämpfen die Einzelpartien. Bei großen Turnieren wird die Ausgabe dadurch sehr lang.'];

// Überschriften der Feldgruppen
$GLOBALS['TL_LANG']['tl_content']['ctv_legend'] = 'Turnier-Betrachter';
$GLOBALS['TL_LANG']['tl_content']['ctv_mannschaft_legend'] = 'Mannschaftsturniere';

$GLOBALS['TL_LANG']['tl_content']['ctvHinweise'] = ['Hinweise zu den Zahlen anzeigen', 'Zeigt über den Tabellen, wenn die gespeicherten Zahlen der Turnierdatei mit den eingetragenen Ergebnissen nicht zusammengehen — etwa weil die Rangliste älter ist als die letzte Runde.'];
$GLOBALS['TL_LANG']['tl_content']['ctvKreuzKurz'] = ['Kreuztabelle der Mannschaften kürzen', 'Zeigt in jeder Zelle nur die eigenen Brettpunkte („3½") statt beider Seiten („3½:½"). Die Gegenzahl steht gespiegelt in der Zelle des Gegners.'];
$GLOBALS['TL_LANG']['tl_content']['ctvStand'] = ['Stand nach Runde', 'Versetzt das ganze Element auf den Stand nach dieser Runde zurück — Tabelle, Kreuztabelle und Fortschrittstabelle zeigen dann, wie es damals aussah. Die Zahlen werden dafür aus den Partien nachgerechnet; eine Feinwertung erscheint nur, wenn sich ihre Rechenweise am Endstand der Datei bestätigen ließ.'];
$GLOBALS['TL_LANG']['tl_content']['ctvRunden'] = ['Angezeigte Runden', 'Beschränkt Paarungs-, Ergebnis- und Wettkampfliste auf einzelne Runden. Ohne Auswahl erscheinen alle. Auf Tabelle und Kreuztabelle wirkt das Feld nicht — dafür ist „Stand nach Runde" da.'];

$GLOBALS['TL_LANG']['tl_content']['ctv_runden_legend'] = 'Zeitpunkt und Runden';
