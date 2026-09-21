<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Wertungen und Rangfolge der Mannschaften.
 *
 * Welche Wertungen ein Mannschaftsturnier führt und in welcher Reihenfolge,
 * legt die Turnierleitung fest; Swiss-Manager legt die Liste als Schlüssel in
 * der Datei ab (siehe SWISS-MANAGER.md, Abschnitt `95`). Diese Klasse rechnet
 * die Wertungen nach, deren Rechenweise belegt ist, und ordnet danach.
 *
 * Belegt heißt: gegen eine Endtabelle von chess-results geprüft. Für die
 * Olympiade für Menschen mit Behinderung 2026 (tnr1470206, 41 Mannschaften,
 * sieben Runden) stimmen alle vier Wertungen bei jeder Mannschaft:
 *
 * | Schlüssel | Wertung |
 * | --- | --- |
 * | `0x0D` | Matchpunkte 2/1/0 |
 * | `0x28` | Matchpunkte 3/1/0 |
 * | `0x01` | Brettpunkte |
 * | `0x4A` | Olympiade-Sonneborn-Berger mit einer Streichung (Chennai) |
 * | `0x4B` | Summe der adjustierten Matchpunkte der Gegner mit einer Streichung (Chennai) |
 *
 * Unbekannte Schlüssel bleiben außen vor: Eine geratene Wertung, die eine
 * Rangfolge entscheidet, wäre schlimmer als keine. Führt die Datei gar keine
 * Liste — SWT-Dateien etwa —, gelten Matchpunkte und Brettpunkte.
 */
final class Mannschaftsfeinwertung
{
    /**
     * Die bekannten Schlüssel und die Wertung, die sie bezeichnen.
     *
     * @var array<int,string>
     */
    public const SCHLUESSEL = [
        0x0D => 'mannschaftspunkte',
        0x28 => 'mannschaftspunkte',
        0x01 => 'brettpunkte',
        0x4A => 'osb',
        0x4B => 'mpsumme',
    ];

    /**
     * Die Wertungen, die ohne Angabe in der Datei gelten.
     */
    public const VORGABE = ['mannschaftspunkte', 'brettpunkte'];

    /**
     * Nennt die Wertungen des Turniers in ihrer Reihenfolge.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return string[] Die Schlüssel der Wertungen, die sich berechnen lassen;
     *                  jede höchstens einmal
     */
    public static function kriterien(Turnier $turnier): array
    {
        $schluessel = $turnier->kopf('mannschaftsWertungen', []);

        if (!\is_array($schluessel) || [] === $schluessel) {
            return self::VORGABE;
        }

        $kriterien = [];

        foreach ($schluessel as $code) {
            $name = self::SCHLUESSEL[(int) $code] ?? null;

            if (null !== $name && !\in_array($name, $kriterien, true)) {
                $kriterien[] = $name;
            }
        }

        // Ohne Matchpunkte ist keine Mannschaftstabelle zu ordnen; sie stehen
        // deshalb vorn, auch wenn die Liste sie nicht nennt.
        if (!\in_array('mannschaftspunkte', $kriterien, true)) {
            array_unshift($kriterien, 'mannschaftspunkte');
        }

        return $kriterien;
    }

    /**
     * Berechnet alle bekannten Wertungen für jede Mannschaft.
     *
     * Gezählt werden die Runden mit Ergebnis, einschließlich der Freilose,
     * sofern das Format sie gewertet hat. Ein ausgeloster, aber noch nicht
     * gespielter Kampf zählt nicht.
     *
     * **Olympiade-Sonneborn-Berger:** Summe aus den Matchpunkten jedes Gegners
     * mal den eigenen Brettpunkten gegen ihn. Gestrichen wird der Gegner mit
     * den wenigsten Matchpunkten; bei Gleichstand der kleinere Beitrag.
     *
     * **Summe der Matchpunkte der Gegner:** ebenso, nur ohne die Brettpunkte.
     *
     * Ein Freilos zählt in beiden als Gegner mit null Matchpunkten und wird
     * damit zuerst gestrichen. So trifft es die Endtabelle bei chess-results
     * — die Lesarten „Freilos zählt die eigenen Matchpunkte" trafen nur 37
     * und 38 von 41 Mannschaften.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array<string,float>> Je Mannschaftsnummer die Werte
     *         unter `mannschaftspunkte`, `brettpunkte`, `osb` und `mpsumme`
     */
    public static function werte(Turnier $turnier): array
    {
        $paarungen = $turnier->getMannschaftspaarungen();
        $mannschaftspunkte = [];
        $brettpunkte = [];

        foreach ($turnier->getMannschaften() as $nummer => $mannschaft) {
            if ($mannschaft['spielfrei'] ?? false) {
                continue;
            }

            $mannschaftspunkte[(int) $nummer] = 0.0;
            $brettpunkte[(int) $nummer] = 0.0;

            foreach ($paarungen[$nummer] ?? [] as $satz) {
                if (null === ($satz['mannschaftspunkte'] ?? null)) {
                    continue;
                }

                $mannschaftspunkte[(int) $nummer] += (float) $satz['mannschaftspunkte'];
                $brettpunkte[(int) $nummer] += (float) ($satz['brettpunkte'] ?? 0.0);
            }
        }

        $werte = [];

        foreach ($mannschaftspunkte as $nummer => $punkte) {
            $beitraege = [];

            foreach ($paarungen[$nummer] ?? [] as $satz) {
                if (null === ($satz['mannschaftspunkte'] ?? null)) {
                    continue;
                }

                $gegner = (int) ($satz['gegner'] ?? 0);
                $gegnerpunkte = $mannschaftspunkte[$gegner] ?? 0.0;
                $beitraege[] = [$gegnerpunkte, $gegnerpunkte * (float) ($satz['brettpunkte'] ?? 0.0)];
            }

            // Die Streichung: der Gegner mit den wenigsten Matchpunkten, bei
            // Gleichstand der kleinere Beitrag. Mit einer einzigen Runde bliebe
            // nichts übrig — dann wird nicht gestrichen.
            usort($beitraege, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

            if (\count($beitraege) > 1) {
                array_shift($beitraege);
            }

            $werte[$nummer] = [
                'mannschaftspunkte' => $punkte,
                'brettpunkte' => $brettpunkte[$nummer],
                'osb' => array_sum(array_column($beitraege, 1)),
                'mpsumme' => array_sum(array_column($beitraege, 0)),
            ];
        }

        return $werte;
    }
}
