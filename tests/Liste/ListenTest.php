<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Liste;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Auswahl;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Listen;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\ListenBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier\TurnierBauer;

/**
 * Prüft das Listenverzeichnis und den Listenbauer.
 */
class ListenTest extends TestCase
{
    /**
     * Prüft, dass Mannschaftslisten nur bei Mannschaftsturnieren gelten.
     *
     * @return void
     */
    public function testMannschaftslistenNurBeiMannschaftsturnieren(): void
    {
        $einzel = TurnierBauer::einzelturnier();
        $mannschaft = TurnierBauer::mannschaftsturnier();

        $this->assertTrue(Listen::passt('rangliste', $einzel));
        $this->assertTrue(Listen::passt('rangliste', $mannschaft));
        $this->assertFalse(Listen::passt('mannschaftsrangliste', $einzel));
        $this->assertTrue(Listen::passt('mannschaftsrangliste', $mannschaft));
    }

    /**
     * Prüft, dass ein unbekannter Schlüssel abgewiesen wird.
     *
     * @return void
     */
    public function testUnbekannteListePasstNie(): void
    {
        $this->assertFalse(Listen::passt('gibtesnicht', TurnierBauer::einzelturnier()));
        $this->assertSame('', Listen::template('gibtesnicht'));
    }

    /**
     * Prüft, dass jede Liste im Verzeichnis ein Template nennt.
     *
     * @return void
     */
    public function testJedeListeHatEinTemplate(): void
    {
        foreach (Listen::schluessel() as $schluessel) {
            $template = Listen::template($schluessel);

            $this->assertNotSame('', $template, sprintf('Liste "%s" nennt kein Template.', $schluessel));
            $this->assertFileExists(
                __DIR__.'/../../src/Resources/contao/templates/'.$template.'.html5',
                sprintf('Das Template "%s" fehlt.', $template)
            );
        }
    }

    /**
     * Prüft, dass der Listenbauer die Reihenfolge der Auswahl übernimmt.
     *
     * Das Auswahlfeld im Backend lässt sich sortieren und speichert die
     * Reihenfolge; die Reiter sollen genauso stehen. Vor Fassung 1.5.0 galt
     * die feste Reihenfolge des Verzeichnisses.
     *
     * @return void
     */
    public function testReihenfolgeFolgtDerAuswahl(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::einzelturnier(),
            new Auswahl(['ergebnisse', 'teilnehmer', 'rangliste'])
        );

        $this->assertSame(['ergebnisse', 'teilnehmer', 'rangliste'], array_column($listen, 'schluessel'));
    }

    /**
     * Prüft, dass nicht passende und leere Listen übergangen werden.
     *
     * @return void
     */
    public function testNichtPassendeListenFallenWeg(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::einzelturnier(),
            new Auswahl(['mannschaftsrangliste', 'kreuztabelle', 'rangliste', 'gibtesnicht'])
        );

        // Die Kreuztabelle fällt weg, weil das Prüfturnier keine mitbringt.
        $this->assertSame(['rangliste'], array_column($listen, 'schluessel'));
    }

    /**
     * Prüft, dass bei einem Mannschaftsturnier alle Mannschaftslisten kommen.
     *
     * @return void
     */
    public function testMannschaftslistenWerdenGebaut(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::mannschaftsturnier(),
            new Auswahl(Listen::schluessel(), true)
        );

        $schluessel = array_column($listen, 'schluessel');

        $this->assertContains('mannschaften', $schluessel);
        $this->assertContains('mannschaftsrangliste', $schluessel);
        $this->assertContains('mannschaftspaarungen', $schluessel);
        $this->assertContains('mannschaftskreuztabelle', $schluessel);
    }

    /**
     * Prüft, dass die Aufstellungen nur auf Wunsch mitkommen.
     *
     * @return void
     */
    public function testAufstellungNurAufWunsch(): void
    {
        $bauer = new ListenBauer();
        $turnier = TurnierBauer::mannschaftsturnier();

        $ohne = $this->finde($bauer->baue($turnier, new Auswahl(['mannschaften'], false)), 'mannschaften');
        $mit = $this->finde($bauer->baue($turnier, new Auswahl(['mannschaften'], true)), 'mannschaften');

        $this->assertSame([], $ohne['daten']['mannschaften'][0]['spieler']);
        $this->assertCount(2, $mit['daten']['mannschaften'][0]['spieler']);
    }

    /**
     * Prüft, dass die Rundenauswahl nur die Rundenlisten beschneidet.
     *
     * Die Ergebnisliste soll genau die gewählten Runden zeigen; Tabelle und
     * Teilnehmerliste bleiben unberührt, denn für den Zeitpunkt ist das Feld
     * „Stand nach Runde" zuständig.
     *
     * @return void
     */
    public function testRundenauswahlWirktNurAufRundenlisten(): void
    {
        $turnier = TurnierBauer::einzelturnier();
        $listen = (new ListenBauer())->baue(
            $turnier,
            new Auswahl(['ergebnisse', 'rangliste'], false, false, 0, [2])
        );

        $ergebnisse = $this->finde($listen, 'ergebnisse');
        $rangliste = $this->finde($listen, 'rangliste');

        $this->assertSame([2], array_keys($ergebnisse['daten']['runden']));
        $this->assertCount(\count($turnier->getRangliste()), $rangliste['daten']['spieler']);
    }

    /**
     * Sucht eine Liste anhand ihres Schlüssels.
     *
     * @param array<int,array<string,mixed>> $listen     Die gebauten Listen
     * @param string                         $schluessel Der gesuchte Schlüssel
     *
     * @return array<string,mixed> Die gefundene Liste
     */
    private function finde(array $listen, string $schluessel): array
    {
        foreach ($listen as $liste) {
            if ($liste['schluessel'] === $schluessel) {
                return $liste;
            }
        }

        $this->fail(sprintf('Die Liste "%s" wurde nicht gebaut.', $schluessel));
    }
}
