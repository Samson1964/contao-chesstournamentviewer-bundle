<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;

/**
 * Prüft die Zusammenstellung der Mannschaftswertung.
 *
 * Die Zahlen kommen aus dem Format-Adapter; geprüft wird, was diese Klasse
 * daraus macht: die Zusammenführung der beiden Sichten eines Wettkampfs, die
 * Ausrichtung nach der Farbe am niedrigsten Brett, die Behandlung von
 * Freilosen und nicht ausgelosten Runden sowie die Summen der Tabelle.
 */
class MannschaftswertungTest extends TestCase
{
    /**
     * Prüft, dass aus den Einzelpartien die richtigen Wettkämpfe entstehen.
     *
     * In Runde 1 treten alle vier Mannschaften an, in Runde 2 nur zwei. Für
     * die beiden übrigen muss je ein Freilos entstehen — insgesamt also drei
     * Einträge in Runde 2.
     *
     * @return void
     */
    public function testWettkaempfeEntstehenAusDenEinzelpartien(): void
    {
        $kaempfe = Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier());

        $this->assertCount(2, $kaempfe, 'Es müssen zwei Runden entstehen.');
        $this->assertCount(2, $kaempfe[1], 'In Runde 1 treten alle vier Mannschaften an.');
        $this->assertCount(3, $kaempfe[2], 'In Runde 2 gibt es einen Wettkampf und zwei Freilose.');
    }

    /**
     * Prüft die Brettpunkte eines Wettkampfs.
     *
     * Mannschaft 1 gewinnt in Runde 1 beide Bretter gegen Mannschaft 2.
     *
     * @return void
     */
    public function testBrettpunkteEinesWettkampfs(): void
    {
        $kaempfe = Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier());
        $kampf = $this->findeKampf($kaempfe[1], 1, 2);

        $this->assertSame(1, $kampf['heim'], 'Mannschaft 1 führt an Brett 1 Weiß und steht deshalb links.');
        $this->assertSame(2.0, $kampf['brettpunkteHeim']);
        $this->assertSame(0.0, $kampf['brettpunkteGast']);
        $this->assertSame(2.0, $kampf['mannschaftspunkteHeim']);
        $this->assertSame(0.0, $kampf['mannschaftspunkteGast']);
        $this->assertCount(2, $kampf['partien'], 'Beide Bretter gehören zum Wettkampf.');
        $this->assertTrue($kampf['gespielt']);
    }

    /**
     * Prüft, dass ein unentschiedener Wettkampf je einen Punkt gibt.
     *
     * @return void
     */
    public function testUnentschiedenerWettkampf(): void
    {
        $kaempfe = Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier());
        $kampf = $this->findeKampf($kaempfe[1], 3, 4);

        $this->assertSame(1.0, $kampf['brettpunkteHeim']);
        $this->assertSame(1.0, $kampf['brettpunkteGast']);
        $this->assertSame(1.0, $kampf['mannschaftspunkteHeim']);
        $this->assertSame(1.0, $kampf['mannschaftspunkteGast']);
    }

    /**
     * Prüft, dass ein Freilos ohne Punkte bleibt.
     *
     * In der Datei steht zu einer spielfreien Runde nichts als das Fehlen
     * eines Gegners. Ob die Turnierleitung dafür einen kampflosen Sieg
     * gutgeschrieben hat, ist daraus nicht zu erkennen und wird deshalb nicht
     * angenommen.
     *
     * @return void
     */
    public function testFreilosBleibtOhnePunkte(): void
    {
        $kaempfe = Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier());
        $freilose = array_values(array_filter($kaempfe[2], static fn (array $k): bool => $k['spielfrei']));

        $this->assertCount(2, $freilose);

        foreach ($freilose as $freilos) {
            $this->assertNull($freilos['gast']);
            $this->assertSame(0.0, $freilos['brettpunkteHeim']);
            $this->assertNull($freilos['mannschaftspunkteHeim']);
            $this->assertFalse($freilos['gespielt']);
            $this->assertSame([], $freilos['partien']);
        }
    }

    /**
     * Prüft, dass eine nicht ausgeloste Runde kein Freilos ergibt.
     *
     * Nicht ausgeloste Runden stehen in der Datei genauso ohne Gegner wie
     * spielfreie. Beides gleich zu behandeln hieße, in einem laufenden
     * Turnier jeder Mannschaft für jede kommende Runde ein Freilos
     * einzutragen.
     *
     * @return void
     */
    public function testNichtAusgelosteRundeIstKeinFreilos(): void
    {
        $kaempfe = Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier());

        $this->assertArrayNotHasKey(3, $kaempfe, 'Es gibt nur zwei ausgeloste Runden.');
    }

    /**
     * Prüft die fertige Mannschaftstabelle.
     *
     * Erwartet wird:
     *   M1: Sieg 2:0, Niederlage ½:1½   → 2 MP, 2½ BP
     *   M2: Niederlage 0:2, Freilos     → 0 MP, 0 BP
     *   M3: Unentschieden 1:1, Sieg 1½:½ → 3 MP, 2½ BP
     *   M4: Unentschieden 1:1, Freilos  → 1 MP, 1 BP
     *
     * @return void
     */
    public function testTabelle(): void
    {
        $tabelle = Mannschaftswertung::tabelle(TurnierBauer::mannschaftsturnier());
        $nachNummer = [];

        foreach ($tabelle as $zeile) {
            $nachNummer[$zeile['nummer']] = $zeile;
        }

        $this->assertCount(4, $tabelle);

        $this->assertSame(2.0, $nachNummer[1]['mannschaftspunkte']);
        $this->assertSame(2.5, $nachNummer[1]['brettpunkte']);
        $this->assertSame(2, $nachNummer[1]['kaempfe']);
        $this->assertSame(1, $nachNummer[1]['siege']);
        $this->assertSame(1, $nachNummer[1]['niederlagen']);
        $this->assertSame(0, $nachNummer[1]['freilose']);

        $this->assertSame(0.0, $nachNummer[2]['mannschaftspunkte']);
        $this->assertSame(0.0, $nachNummer[2]['brettpunkte']);
        $this->assertSame(1, $nachNummer[2]['kaempfe'], 'Ein Freilos ist kein Wettkampf.');
        $this->assertSame(1, $nachNummer[2]['freilose']);
        $this->assertSame(0, $nachNummer[2]['siege']);

        $this->assertSame(3.0, $nachNummer[3]['mannschaftspunkte']);
        $this->assertSame(2.5, $nachNummer[3]['brettpunkte']);
        $this->assertSame(1, $nachNummer[3]['unentschieden']);

        $this->assertSame(1.0, $nachNummer[4]['mannschaftspunkte']);
        $this->assertSame(1.0, $nachNummer[4]['brettpunkte']);
        $this->assertSame(1, $nachNummer[4]['freilose']);

        // Reihenfolge: erst Mannschaftspunkte, dann Brettpunkte
        $this->assertSame(3, $tabelle[0]['nummer'], 'M3 führt mit 3 Mannschaftspunkten.');
        $this->assertSame(1, $tabelle[1]['nummer']);
        $this->assertSame(4, $tabelle[2]['nummer']);
        $this->assertSame(2, $tabelle[3]['nummer']);
    }

    /**
     * Prüft, dass die Kreuztabelle die Wettkämpfe beidseitig einträgt.
     *
     * @return void
     */
    public function testKreuztabelle(): void
    {
        $kreuz = Mannschaftswertung::kreuztabelle(TurnierBauer::mannschaftsturnier());
        $spalte = [];

        foreach ($kreuz['mannschaften'] as $index => $zeile) {
            $spalte[$zeile['nummer']] = $index;
        }

        $this->assertSame('2:0', $kreuz['zeilen'][$spalte[1]][$spalte[2]]);
        $this->assertSame('0:2', $kreuz['zeilen'][$spalte[2]][$spalte[1]]);
        $this->assertSame('**', $kreuz['zeilen'][$spalte[1]][$spalte[1]], 'Die Diagonale bleibt gekennzeichnet.');
        $this->assertSame('', $kreuz['zeilen'][$spalte[2]][$spalte[3]], 'M2 und M3 sind nicht aufeinandergetroffen.');
    }

    /**
     * Prüft, dass ein Einzelturnier keine Mannschaftswertung erzeugt.
     *
     * @return void
     */
    public function testEinzelturnierErgibtKeineWettkaempfe(): void
    {
        $this->assertSame([], Mannschaftswertung::kaempfe(TurnierBauer::einzelturnier()));
        $this->assertSame([], Mannschaftswertung::tabelle(TurnierBauer::einzelturnier()));
    }

    /**
     * Prüft die Schreibweise halber Punkte.
     *
     * @return void
     */
    public function testPunkteText(): void
    {
        $this->assertSame('0', Mannschaftswertung::punkteText(0.0));
        $this->assertSame('½', Mannschaftswertung::punkteText(0.5));
        $this->assertSame('3', Mannschaftswertung::punkteText(3.0));
        $this->assertSame('3½', Mannschaftswertung::punkteText(3.5));
    }

    /**
     * Sucht einen Wettkampf zwischen zwei Mannschaften.
     *
     * @param array<int,array<string,mixed>> $kaempfe Die Wettkämpfe einer Runde
     * @param int                            $eine    Nummer der einen Mannschaft
     * @param int                            $andere  Nummer der anderen Mannschaft
     *
     * @return array<string,mixed> Der gefundene Wettkampf
     */
    private function findeKampf(array $kaempfe, int $eine, int $andere): array
    {
        foreach ($kaempfe as $kampf) {
            $beteiligt = [$kampf['heim'], $kampf['gast']];

            if (\in_array($eine, $beteiligt, true) && \in_array($andere, $beteiligt, true)) {
                return $kampf;
            }
        }

        $this->fail(sprintf('Kein Wettkampf zwischen Mannschaft %d und %d gefunden.', $eine, $andere));
    }
}
