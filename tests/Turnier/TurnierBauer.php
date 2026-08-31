<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier;

use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Baut Turniere für die Tests zusammen.
 *
 * Die Tests kommen bewusst ohne echte Turnierdateien aus. Eine SWT-Datei im
 * Paket wäre gut ein Viertelmegabyte groß, ließe sich schlecht abwandeln und
 * würde ohnehin nur den SWT-Leser prüfen — die Rückrechnung der
 * Mannschaftswertung und die Fortschrittstabelle arbeiten auf dem
 * formatunabhängigen Modell und lassen sich dort viel gezielter prüfen.
 */
final class TurnierBauer
{
    /**
     * Baut ein kleines Mannschaftsturnier mit vier Mannschaften.
     *
     * Aufbau: vier Mannschaften zu zwei Brettern, zwei Runden.
     *
     *   Runde 1: M1 gegen M2 (2:0), M3 gegen M4 (1:1)
     *   Runde 2: M1 gegen M3 (½:1½), M2 setzt aus, M4 setzt aus
     *
     * In Runde 2 gibt es nur einen Wettkampf; M2 und M4 haben also ein
     * Freilos. Punkte gibt es dafür keine — was in der Datei nicht steht,
     * wird nicht erfunden.
     *
     * @return Turnier Das zusammengebaute Turnier
     */
    public static function mannschaftsturnier(): Turnier
    {
        // Spieler 1+2 gehören zu Mannschaft 1, 3+4 zu Mannschaft 2 und so fort
        $spieler = [];

        foreach (range(1, 8) as $tnr) {
            $mannschaft = (int) ceil($tnr / 2);

            $spieler[$tnr] = [
                'tnr' => $tnr,
                'startnummer' => $tnr,
                'name' => 'Spieler '.$tnr,
                'titel' => '',
                'twz' => 1500 + $tnr,
                'elo' => 0,
                'dwz' => 0,
                'mannschaft' => 'Mannschaft '.$mannschaft,
                'mannschaftsnummer' => $mannschaft,
                'brett' => 1 + ($tnr - 1) % 2,
                'platz' => $tnr,
                'punkte' => 0.0,
                'siege' => 0,
                'remis' => 0,
                'niederlagen' => 0,
                'spielfrei' => false,
            ];
        }

        $mannschaften = [];

        foreach (range(1, 4) as $nummer) {
            $mannschaften[$nummer] = [
                'mnr' => $nummer,
                'name' => 'Mannschaft '.$nummer,
                'spielfrei' => false,
                'spieler' => [$nummer * 2 - 1, $nummer * 2],
                'spielerzahl' => 2,
                'platz' => $nummer,
                'brettpunkte' => null,
                'mannschaftspunkte' => null,
                'eloSchnitt' => 0,
                'dwzSchnitt' => 0,
            ];
        }

        // Runde 1: M1 (1,2) gegen M2 (3,4) — beide Bretter an M1
        $paarungen = [
            1 => [1 => self::satz(3, 'Weiß', 1.0, 1)],
            3 => [1 => self::satz(1, 'Schwarz', 0.0, 1)],
            2 => [1 => self::satz(4, 'Schwarz', 1.0, 2)],
            4 => [1 => self::satz(2, 'Weiß', 0.0, 2)],
            // Runde 1: M3 (5,6) gegen M4 (7,8) — 1:1
            5 => [1 => self::satz(7, 'Weiß', 1.0, 1)],
            7 => [1 => self::satz(5, 'Schwarz', 0.0, 1)],
            6 => [1 => self::satz(8, 'Schwarz', 0.0, 2)],
            8 => [1 => self::satz(6, 'Weiß', 1.0, 2)],
        ];

        // Runde 2: M1 (1,2) gegen M3 (5,6) — ½:1½
        $paarungen[1][2] = self::satz(5, 'Weiß', 0.5, 1);
        $paarungen[5][2] = self::satz(1, 'Schwarz', 0.5, 1);
        $paarungen[2][2] = self::satz(6, 'Schwarz', 0.0, 2);
        $paarungen[6][2] = self::satz(2, 'Weiß', 1.0, 2);

        // Die Wettkampfsätze in der Form, die der Format-Adapter liefert: mit
        // übersetzter Gegnernummer, Brettpunkten beider Seiten und den
        // Mannschaftspunkten, die sich daraus ergeben. Ein Gegner 0 steht für
        // eine spielfreie Runde.
        $mannschaftspaarungen = [
            1 => [
                1 => self::kampf(2, 2.0, 0.0, 2.0),
                2 => self::kampf(3, 0.5, 1.5, 0.0),
            ],
            2 => [
                1 => self::kampf(1, 0.0, 2.0, 0.0),
                2 => self::kampf(0, 0.0, 0.0, null),
            ],
            3 => [
                1 => self::kampf(4, 1.0, 1.0, 1.0),
                2 => self::kampf(1, 1.5, 0.5, 2.0),
            ],
            4 => [
                1 => self::kampf(3, 1.0, 1.0, 1.0),
                2 => self::kampf(0, 0.0, 0.0, null),
            ],
        ];

        // Beide Runden sind ausgelost; der Inhalt spielt für die Wertung keine
        // Rolle, nur dass die Runde nicht leer ist.
        $runden = [1 => [['brett' => 1]], 2 => [['brett' => 1]]];

        return new Turnier(
            'Prüfstand',
            [
                'name' => 'Prüfturnier',
                'mannschaftsturnier' => true,
                'mannschaftszahl' => 4,
                'bretter' => 2,
                'runden' => 2,
                'teilnehmerzahl' => 8,
                'modusText' => 'Schweizer System',
            ],
            $spieler,
            $mannschaften,
            $paarungen,
            array_values($spieler),
            $runden,
            null,
            [],
            $mannschaftspaarungen
        );
    }

    /**
     * Stellt einen Wettkampfsatz zusammen.
     *
     * @param int        $gegner            Mannschaftsnummer des Gegners, 0 bei spielfrei
     * @param float      $brettpunkte       Brettpunkte der eigenen Mannschaft
     * @param float      $gegenpunkte       Brettpunkte des Gegners
     * @param float|null $mannschaftspunkte Mannschaftspunkte, null wenn nicht gespielt
     *
     * @return array<string,mixed> Der Satz im Modellformat
     */
    private static function kampf(int $gegner, float $brettpunkte, float $gegenpunkte, ?float $mannschaftspunkte): array
    {
        return [
            'gegner' => $gegner,
            'gegnerName' => $gegner > 0 ? 'Mannschaft '.$gegner : '',
            'farbe' => 'Heim',
            'brettpunkte' => $brettpunkte,
            'brettpunkteGegner' => $gegenpunkte,
            'mannschaftspunkte' => $mannschaftspunkte,
            'amGruenenTisch' => false,
            'tisch' => 1,
        ];
    }

    /**
     * Baut ein Einzelturnier mit vier Teilnehmern über drei Runden.
     *
     * Teilnehmer 4 setzt in Runde 2 aus; dafür steht ein Platzhalter mit der
     * Nummer 5 bereit, wie ihn die Turnierprogramme bei ungerader
     * Teilnehmerzahl anlegen.
     *
     * @return Turnier Das zusammengebaute Turnier
     */
    public static function einzelturnier(): Turnier
    {
        $spieler = [];

        foreach (range(1, 4) as $tnr) {
            $spieler[$tnr] = [
                'tnr' => $tnr,
                'startnummer' => $tnr,
                'name' => 'Spieler '.$tnr,
                'titel' => '',
                'twz' => 1600 + $tnr,
                'mannschaft' => '',
                'mannschaftsnummer' => 0,
                'brett' => 0,
                'platz' => $tnr,
                'punkte' => 0.0,
                'siege' => 0,
                'remis' => 0,
                'niederlagen' => 0,
                'spielfrei' => false,
            ];
        }

        $spieler[5] = array_merge($spieler[1], ['tnr' => 5, 'name' => 'spielfrei', 'spielfrei' => true, 'platz' => 0]);

        $paarungen = [
            1 => [
                1 => self::satz(2, 'Weiß', 1.0, 1),
                2 => self::satz(3, 'Schwarz', 0.5, 1),
                3 => self::satz(5, 'Weiß', 1.0, 1),
            ],
            2 => [
                1 => self::satz(1, 'Schwarz', 0.0, 1),
                2 => self::satz(4, 'Weiß', 1.0, 2),
            ],
            3 => [
                1 => self::satz(4, 'Weiß', 1.0, 2),
                2 => self::satz(1, 'Weiß', 0.5, 1),
            ],
            4 => [
                1 => self::satz(3, 'Schwarz', 0.0, 2),
                2 => self::satz(2, 'Schwarz', 0.0, 2),
            ],
        ];

        return new Turnier(
            'Prüfstand',
            [
                'name' => 'Prüfturnier',
                'mannschaftsturnier' => false,
                'mannschaftszahl' => 0,
                'bretter' => 0,
                'runden' => 3,
                'teilnehmerzahl' => 5,
                'modusText' => 'Rundenturnier',
            ],
            $spieler,
            [],
            $paarungen,
            [$spieler[1], $spieler[2], $spieler[3], $spieler[4]],
            [1 => [["brett" => 1]], 2 => [["brett" => 1]], 3 => [["brett" => 1]]],
            null,
            []
        );
    }

    /**
     * Stellt einen einzelnen Paarungssatz zusammen.
     *
     * @param int    $gegner   Teilnehmernummer des Gegners
     * @param string $farbe    Farbbezeichnung, „Weiß" oder „Schwarz"
     * @param float  $ergebnis Punkte aus Sicht des betrachteten Spielers
     * @param int    $brett    Brettnummer innerhalb des Wettkampfs
     *
     * @return array<string,mixed> Der Paarungssatz im Modellformat
     */
    private static function satz(int $gegner, string $farbe, float $ergebnis, int $brett): array
    {
        return [
            'gegner' => $gegner,
            'gegnerName' => 'Spieler '.$gegner,
            'farbe' => $farbe,
            'ergebnis' => $ergebnis,
            'ergebnisText' => (string) $ergebnis,
            'tisch' => 0,
            'brett' => $brett,
            'status' => '',
        ];
    }
}
