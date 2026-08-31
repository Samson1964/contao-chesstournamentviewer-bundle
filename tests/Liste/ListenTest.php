<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Liste;

use PHPUnit\Framework\TestCase;
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
     * Prüft, dass der Listenbauer die Reihenfolge des Verzeichnisses hält.
     *
     * Die Auswahl im Backend ist ein Kästchensatz und kommt in beliebiger
     * Reihenfolge zurück; die Ausgabe soll trotzdem immer gleich sortiert
     * sein.
     *
     * @return void
     */
    public function testReihenfolgeFolgtDemVerzeichnis(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::einzelturnier(),
            ['ergebnisse', 'teilnehmer', 'rangliste']
        );

        $this->assertSame(['teilnehmer', 'rangliste', 'ergebnisse'], array_column($listen, 'schluessel'));
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
            ['mannschaftsrangliste', 'kreuztabelle', 'rangliste']
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
            Listen::schluessel(),
            true
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

        $ohne = $this->finde($bauer->baue($turnier, ['mannschaften'], false), 'mannschaften');
        $mit = $this->finde($bauer->baue($turnier, ['mannschaften'], true), 'mannschaften');

        $this->assertSame([], $ohne['daten']['mannschaften'][0]['spieler']);
        $this->assertCount(2, $mit['daten']['mannschaften'][0]['spieler']);
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
