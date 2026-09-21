<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Liste;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Ausgabe;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Auswahl;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Listen;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\ListenBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier\TurnierBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;

/**
 * Prüft die Ausgaben für Mannschaftsturniere aus Fassung 1.11.0.
 *
 * Dazu gehören die Auswahl, die nur übernimmt, was zur gewählten Liste passt,
 * die Beschränkung auf einzelne Mannschaften, die Brettnummern je Wettkampf,
 * die Schreibweise der Spielernamen und die Fortschrittstabelle der
 * Mannschaften.
 */
class MannschaftsausgabeTest extends TestCase
{
    /**
     * Prüft, dass ein alter Rundenstand nicht in eine andere Liste durchschlägt.
     *
     * Ein Element, das von der Mannschaftstabelle nach Runde 1 auf die
     * Mannschaftsliste umgestellt wird, behält „Stand nach Runde 1" im
     * Datensatz. Bis Fassung 1.10.0 blieb dadurch die Zeile „Stand nach Runde"
     * über der Mannschaftsliste und über den Ergebnissen stehen.
     *
     * @return void
     */
    public function testUnpassendeEinstellungenFallenWeg(): void
    {
        $tabelle = Auswahl::fuerListe('mannschaftsrangliste', true, true, 1, [2], [], [3]);
        $liste = Auswahl::fuerListe('mannschaften', true, true, 1, [2], [], [3]);
        $ergebnisse = Auswahl::fuerListe('ergebnisse', true, true, 1, [2], [], [3]);

        $this->assertSame(1, $tabelle->stand);
        $this->assertSame([], $tabelle->runden);
        $this->assertSame([], $tabelle->mannschaften);

        $this->assertSame(0, $liste->stand);
        $this->assertTrue($liste->mitSpielern);
        $this->assertFalse($liste->kreuzKurz);

        $this->assertSame(0, $ergebnisse->stand);
        $this->assertSame([2], $ergebnisse->runden);
        $this->assertSame([3], $ergebnisse->mannschaften);
        $this->assertFalse($ergebnisse->mitSpielern);
    }

    /**
     * Prüft das Ausblenden der Rundenüberschriften.
     *
     * Das Kästchen wirkt nur bei den Listen, die je Runde ausgeben; eine
     * Rangliste hat gar keine Rundenüberschrift. Der Wert geht bis in die
     * Daten der Liste durch, denn dort holt ihn das Template ab.
     *
     * @return void
     */
    public function testRundenueberschriftenLassenSichAusblenden(): void
    {
        $this->assertFalse(Auswahl::fuerListe('ergebnisse', false, false, 0, [], [], [], true)->rundenkopf);
        $this->assertTrue(Auswahl::fuerListe('ergebnisse', false, false, 0, [], [], [], false)->rundenkopf);
        $this->assertTrue(Auswahl::fuerListe('rangliste', false, false, 0, [], [], [], true)->rundenkopf);

        $bauer = new ListenBauer();
        $turnier = TurnierBauer::mannschaftsturnier();

        $ohne = $bauer->baue($turnier, Auswahl::fuerListe('ergebnisse', false, false, 0, [], [], [], true));
        $mit = $bauer->baue($turnier, Auswahl::fuerListe('mannschaftspaarungen', false, false, 0, [], [], [], false));

        $this->assertFalse($ohne[0]['daten']['rundenkopf']);
        $this->assertTrue($mit[0]['daten']['rundenkopf']);
    }

    /**
     * Prüft, dass die reinen Einzellisten bei Mannschaftsturnieren entfallen.
     *
     * @return void
     */
    public function testEinzellistenEntfallenBeiMannschaftsturnieren(): void
    {
        $mannschaft = TurnierBauer::mannschaftsturnier();
        $einzel = TurnierBauer::einzelturnier();

        foreach (['kreuztabelle', 'fortschritt', 'fortschrittohne'] as $schluessel) {
            $this->assertFalse(Listen::passt($schluessel, $mannschaft), $schluessel);
            $this->assertTrue(Listen::passt($schluessel, $einzel), $schluessel);
        }

        $this->assertTrue(Listen::passt('mannschaftsfortschritt', $mannschaft));
        $this->assertFalse(Listen::passt('mannschaftsfortschritt', $einzel));
    }

    /**
     * Prüft die Beschränkung der Ergebnisse auf eine Mannschaft.
     *
     * Mannschaft 4 spielt nur in Runde 1, in Runde 2 hat sie ein Freilos. Mit
     * ihr als Auswahl bleiben ihr Wettkampf gegen Mannschaft 3 und das
     * Freilos — der Gegner kommt mit, fremde Wettkämpfe nicht.
     *
     * @return void
     */
    public function testErgebnisseLassenSichAufMannschaftenBeschraenken(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::mannschaftsturnier(),
            Auswahl::fuerListe('ergebnisse', false, false, 0, [], [], [4])
        );

        $kaempfe = $listen[0]['daten']['kaempfe'];

        $this->assertCount(1, $kaempfe[1]);
        $this->assertSame([3, 4], [$kaempfe[1][0]['heim'], $kaempfe[1][0]['gast']]);
        $this->assertCount(1, $kaempfe[2]);
        $this->assertTrue($kaempfe[2][0]['spielfrei']);
    }

    /**
     * Prüft, dass die Bretter jedes Wettkampfs ab 1 zählen.
     *
     * Im Prüfturnier stehen die Bretter bereits als 1 und 2 in der Datei; die
     * Gegenprobe mit den Brettnummern 5 und 6 wie bei Swiss-Manager steckt in
     * der Olympiade-Datei, die nicht im Repository liegt. Geprüft wird hier,
     * dass jede Aufstellung lückenlos bei 1 beginnt.
     *
     * @return void
     */
    public function testBretterZaehlenJeWettkampfAbEins(): void
    {
        foreach (Mannschaftswertung::kaempfe(TurnierBauer::mannschaftsturnier()) as $kaempfe) {
            foreach ($kaempfe as $kampf) {
                if ([] === $kampf['partien']) {
                    continue;
                }

                $this->assertSame(range(1, \count($kampf['partien'])), array_column($kampf['partien'], 'brett'));
            }
        }
    }

    /**
     * Prüft die Schreibweise „Titel Vorname Nachname".
     *
     * @return void
     */
    public function testVollnameInLesereihenfolge(): void
    {
        $this->assertSame('IM Marcin Molenda', Ausgabe::vollname(['titel' => 'IM', 'name' => 'Molenda,Marcin', 'vorname' => 'Marcin', 'nachname' => 'Molenda']));
        $this->assertSame('Hans Müller', Ausgabe::vollname(['name' => 'Müller, Hans']));
        $this->assertSame('FM Hector Luis Fuentes De Feria', Ausgabe::vollname(['titel' => 'FM', 'name' => 'Fuentes De Feria,Hector Luis']));
        $this->assertSame('spielfrei', Ausgabe::vollname(['name' => 'spielfrei']));
    }

    /**
     * Prüft die Fortschrittstabelle der Mannschaften.
     *
     * Mannschaft 1 gewinnt Runde 1 mit 2:0 gegen Mannschaft 2 und verliert
     * Runde 2 mit ½:1½ gegen Mannschaft 3. Der Stand der Mannschaftspunkte
     * läuft also 2, 2. Mannschaft 2 hat in Runde 2 ein Freilos.
     *
     * @return void
     */
    public function testFortschrittDerMannschaften(): void
    {
        $zeilen = Mannschaftswertung::fortschritt(TurnierBauer::mannschaftsturnier());
        $nachNummer = array_column($zeilen, null, 'nummer');

        // Der Gegner steht mit seinem Platz, nicht mit seiner Startnummer —
        // so wie in der Endtabelle bei chess-results.
        $platz = array_column($zeilen, 'platz', 'nummer');

        $eins = $nachNummer[1]['runden'];
        $this->assertSame($platz[2], $eins[1]['gegner']);
        $this->assertSame('Mannschaft 2', $eins[1]['gegnerName']);
        $this->assertSame('w', $eins[1]['farbe']);
        $this->assertSame(2.0, $eins[1]['brettpunkte']);
        $this->assertSame($platz[3], $eins[2]['gegner']);
        $this->assertSame('w', $eins[2]['farbe']);
        $this->assertSame(0.5, $eins[2]['brettpunkte']);
        $this->assertArrayNotHasKey('stand', $eins[1]);

        // Die Gegenseite hatte am ersten Brett Schwarz.
        $this->assertSame('s', $nachNummer[2]['runden'][1]['farbe']);

        $this->assertTrue($nachNummer[2]['runden'][2]['spielfrei']);
        $this->assertSame(array_column(Mannschaftswertung::tabelle(TurnierBauer::mannschaftsturnier()), 'nummer'), array_column($zeilen, 'nummer'));
    }
}
