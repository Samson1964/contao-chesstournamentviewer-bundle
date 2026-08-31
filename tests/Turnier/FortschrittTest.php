<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Fortschritt;

/**
 * Prüft die Fortschrittstabelle.
 */
class FortschrittTest extends TestCase
{
    /**
     * Prüft, dass der Punktestand über die Runden mitläuft.
     *
     * Teilnehmer 1 gewinnt Runde 1, spielt Runde 2 remis und gewinnt Runde 3
     * kampflos gegen den Platzhalter. Der Stand muss 1, 1½ und 2½ lauten.
     *
     * @return void
     */
    public function testPunktestandLaeuftMit(): void
    {
        $zeilen = Fortschritt::zeilen(TurnierBauer::einzelturnier());
        $erste = $zeilen[0];

        $this->assertSame(1.0, $erste['runden'][1]['stand']);
        $this->assertSame(1.5, $erste['runden'][2]['stand']);
        $this->assertSame(2.5, $erste['runden'][3]['stand']);
        $this->assertSame(2.5, $erste['summe']);
    }

    /**
     * Prüft, dass eine Partie gegen den Platzhalter als spielfrei gilt.
     *
     * Der Platzhalterteilnehmer darf nicht als Gegner erscheinen — er steht
     * in keiner Teilnehmerliste, seine Nummer wäre für den Leser nicht
     * aufzulösen.
     *
     * @return void
     */
    public function testPlatzhalterGiltAlsSpielfrei(): void
    {
        $zeilen = Fortschritt::zeilen(TurnierBauer::einzelturnier());
        $zelle = $zeilen[0]['runden'][3];

        $this->assertTrue($zelle['spielfrei']);
        $this->assertNull($zelle['gegner']);
        $this->assertSame('', $zelle['gegnerName']);
    }

    /**
     * Prüft, dass die Farbe auf einen Buchstaben gekürzt wird.
     *
     * @return void
     */
    public function testFarbeWirdGekuerzt(): void
    {
        $zeilen = Fortschritt::zeilen(TurnierBauer::einzelturnier());

        $this->assertSame('w', $zeilen[0]['runden'][1]['farbe']);
        $this->assertSame('s', $zeilen[0]['runden'][2]['farbe']);
    }

    /**
     * Prüft, dass eine Runde ohne Paarung als leere Zelle erscheint.
     *
     * Teilnehmer 2 hat nur zwei Runden gespielt; die dritte muss als leer
     * gekennzeichnet sein und den bisherigen Stand weiterreichen.
     *
     * @return void
     */
    public function testFehlendeRundeErgibtLeereZelle(): void
    {
        $zeilen = Fortschritt::zeilen(TurnierBauer::einzelturnier());
        $zweite = null;

        foreach ($zeilen as $zeile) {
            if (2 === (int) $zeile['spieler']['tnr']) {
                $zweite = $zeile;

                break;
            }
        }

        $this->assertNotNull($zweite);
        $this->assertTrue($zweite['runden'][3]['leer']);
        $this->assertSame($zweite['runden'][2]['stand'], $zweite['runden'][3]['stand']);
    }
}
