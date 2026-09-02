<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Baut die Kreuztabelle eines Turniers aus Rangliste und Paarungen.
 *
 * Im Regelfall liefert das Format die Kreuztabelle selbst; diese Klasse wird
 * für den Rundenschnitt gebraucht, wo die Tabelle mit veränderter Rangliste
 * und gekürzten Paarungen neu entstehen muss. Sie kommt ohne Wissen über das
 * Quellformat aus und taugt deshalb auch als Rückfallebene für Formate, die
 * keine eigene Kreuztabelle mitbringen.
 *
 * Dass sie dasselbe liefert wie der SWT-Leser, ist nachgewiesen: über den
 * Prüfbestand verglichen, Feld für Feld.
 */
final class Kreuztabelle
{
    /**
     * Baut die Kreuztabelle für eine gegebene Rangliste.
     *
     * Zeilen und Spalten folgen der übergebenen Rangliste; die Diagonale
     * trägt `**` und wird im Template als Blindfeld dargestellt. Spielt ein
     * Paar mehrfach gegeneinander — Rundenturniere mit Hin- und Rückrunde —,
     * stehen die Symbole durch ein Leerzeichen getrennt im selben Feld.
     *
     * Paarungen ohne Ergebnis bleiben leer. Das ist wichtig für doppelrundige
     * Turniere: Dort steht die Paarung der Rückrunde schon in der Datei,
     * bevor sie gespielt ist, und ein leerer Eintrag würde das Ergebnis der
     * Hinrunde wieder auslöschen.
     *
     * @param array<int,array<string,mixed>>            $rangliste Teilnehmer in der Reihenfolge der Tabelle
     * @param array<int,array<int,array<string,mixed>>> $paarungen Paarungen als [Teilnehmernummer][Runde]
     * @param int|null                                  $bis       Höchste zu berücksichtigende Runde,
     *                                                             null für alle
     *
     * @return array{spieler:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}
     *         Rangliste und Symbolzeilen; beide leer, wenn die Rangliste leer ist
     */
    public static function baue(array $rangliste, array $paarungen, int $bis = null): array
    {
        if ([] === $rangliste) {
            return ['spieler' => [], 'zeilen' => []];
        }

        $spalte = [];

        foreach ($rangliste as $index => $spieler) {
            $spalte[(int) $spieler['tnr']] = $index;
        }

        $zeilen = [];

        foreach ($rangliste as $index => $spieler) {
            $zeile = array_fill(0, \count($rangliste), '');
            $zeile[$index] = '**';

            foreach ($paarungen[(int) $spieler['tnr']] ?? [] as $runde => $satz) {
                if (null !== $bis && $runde > $bis) {
                    continue;
                }

                $gegner = (int) ($satz['gegner'] ?? 0);

                if (!isset($spalte[$gegner])) {
                    continue;
                }

                $symbol = self::symbol($satz);

                if ('' === $symbol) {
                    continue;
                }

                $ziel = $spalte[$gegner];
                $zeile[$ziel] = '' === $zeile[$ziel] ? $symbol : $zeile[$ziel].' '.$symbol;
            }

            $zeilen[$index] = $zeile;
        }

        return ['spieler' => $rangliste, 'zeilen' => $zeilen];
    }

    /**
     * Wandelt einen Paarungssatz in das Symbol eines Kreuztabellenfeldes.
     *
     * Kampflos entschiedene Partien bekommen `+` und `-`, damit sie sich von
     * gespielten unterscheiden; alles Übrige übernimmt den Ergebnistext des
     * Formats (`1`, `½`, `0`).
     *
     * @param array<string,mixed> $satz Ein Paarungssatz
     *
     * @return string Das Symbol, oder eine leere Zeichenkette ohne Ergebnis
     */
    private static function symbol(array $satz): string
    {
        $ergebnis = $satz['ergebnis'] ?? null;
        $kampflos = \in_array((string) ($satz['status'] ?? ''), ['kampflos', 'nicht eingesetzt'], true);

        if ($kampflos && null !== $ergebnis) {
            if (1.0 === (float) $ergebnis) {
                return '+';
            }

            if (0.0 === (float) $ergebnis) {
                return '-';
            }
        }

        return (string) ($satz['ergebnisText'] ?? '');
    }
}
