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
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Laender;
use Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier\TurnierBauer;

/**
 * Prüft die Zuordnung der FIDE-Kennungen und die Übersetzung der Ländernamen.
 */
class LaenderTest extends TestCase
{
    /**
     * Prüft die Abbildung der FIDE-Kennungen auf ISO-Kennungen.
     *
     * Die Kennungen weichen oft ab — GER ist DE, SUI ist CH —, und Verbände
     * wie die IBCA haben gar kein Land. Kleinschreibung und Leerzeichen aus
     * der Datei dürfen die Zuordnung nicht verhindern.
     *
     * @return void
     */
    public function testFideKennungenWerdenAufIsoAbgebildet(): void
    {
        $this->assertSame('DE', Laender::iso('GER'));
        $this->assertSame('CH', Laender::iso(' sui '));
        $this->assertSame('SZ', Laender::iso('SWZ'));
        $this->assertNull(Laender::iso('IBCA'));
        $this->assertNull(Laender::iso(''));
    }

    /**
     * Prüft die Übersetzung von Nationalmannschaften.
     *
     * Eine angehängte Nummer bleibt stehen, ältere englische Namen wie „Czech
     * Republic" und „Turkey" werden ebenso erkannt wie die heutigen.
     *
     * @return void
     */
    public function testNationalmannschaftenWerdenUebersetzt(): void
    {
        $this->assertSame('Polen', Laender::mannschaftsname('Poland', 'POL', 'de'));
        $this->assertSame('Usbekistan 2', Laender::mannschaftsname('Uzbekistan 2', 'UZB', 'de'));
        $this->assertSame('Tschechien', Laender::mannschaftsname('Czech Republic', 'CZE', 'de'));
        $this->assertSame('Deutschland 2', Laender::mannschaftsname('Germany 2', 'GER', 'de'));
        $this->assertSame(Laender::mannschaftsname('Turkiye', 'TUR', 'de'), Laender::mannschaftsname('Turkey', 'TUR', 'de'));
        $this->assertSame('Poland', Laender::mannschaftsname('Poland', 'POL', 'en'));
    }

    /**
     * Prüft, dass Namen ohne Länderbezug unverändert bleiben.
     *
     * Ein Verein führt die Föderation seines Landes, ist aber kein Land; ein
     * Verband wie die IBCA hat gar keines. Beide dürfen nicht übersetzt
     * werden.
     *
     * @return void
     */
    public function testVereineUndVerbaendeBleibenUnveraendert(): void
    {
        $this->assertSame('SK Deizisau', Laender::mannschaftsname('SK Deizisau', 'GER', 'de'));
        $this->assertSame('IBCA', Laender::mannschaftsname('IBCA', 'IBCA', 'de'));
        $this->assertSame('Poland', Laender::mannschaftsname('Poland', 'GER', 'de'));
    }

    /**
     * Prüft die Übersetzung eines ganzen Turniers.
     *
     * Der Name der Gegenseite in den Wettkämpfen muss dem Namen in der
     * Mannschaftsliste folgen, sonst hieße dieselbe Mannschaft an zwei
     * Stellen verschieden.
     *
     * @return void
     */
    public function testTurnierUebersetztAuchDieGegnernamen(): void
    {
        $turnier = TurnierBauer::mannschaftsturnier();
        $mannschaften = $turnier->getMannschaften();
        $mannschaften[1] = array_merge($mannschaften[1], ['name' => 'Poland', 'land' => 'POL']);
        $mannschaften[2] = array_merge($mannschaften[2], ['name' => 'Hungary', 'land' => 'HUN']);
        $turnier = $turnier->mitMannschaften($mannschaften, $turnier->getMannschaftspaarungen());

        $uebersetzt = Laender::uebersetzeMannschaften($turnier, 'de');

        $this->assertSame('Polen', $uebersetzt->getMannschaften()[1]['name']);
        $this->assertSame('Ungarn', $uebersetzt->getMannschaften()[2]['name']);
        $this->assertSame('Mannschaft 3', $uebersetzt->getMannschaften()[3]['name']);

        foreach ($uebersetzt->getMannschaftspaarungen() as $runden) {
            foreach ($runden as $satz) {
                $gegner = (int) ($satz['gegner'] ?? 0);

                if (isset($uebersetzt->getMannschaften()[$gegner])) {
                    $this->assertSame($uebersetzt->getMannschaften()[$gegner]['name'], $satz['gegnerName']);
                }
            }
        }

        // Das Ausgangsturnier bleibt unberührt.
        $this->assertSame('Poland', $turnier->getMannschaften()[1]['name']);
    }

    /**
     * Prüft den Titel am Flaggenfeld.
     *
     * Er nennt Ländername und Code; ohne Flagge bleibt der Code als Text.
     *
     * @return void
     */
    public function testFlaggeTraegtLaendernamenAlsTitel(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';

        $this->assertStringContainsString('title="Polen (POL)"', Ausgabe::flagge('POL'));
        $this->assertSame('FID', Ausgabe::flagge('FID'));
    }
}
