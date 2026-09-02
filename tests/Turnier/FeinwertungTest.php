<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Feinwertung;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Rundenschnitt;

/**
 * Prüft die Nachrechnung der Feinwertungen und ihre Kalibrierung.
 *
 * Das Prüfturnier ist dasselbe wie beim Rundenschnitt. Seine Buchholzwerte
 * lassen sich von Hand nachvollziehen; die angepassten Punktzahlen — nicht
 * gespielte Partien zählen als Remis — sind 2,0 für Teilnehmer 1, 1,0 für
 * Teilnehmer 2, 1,5 für Teilnehmer 3 und 0 für Teilnehmer 4.
 */
class FeinwertungTest extends TestCase
{
    /**
     * Die Buchholzwerte des Prüfturniers ohne Streichung.
     *
     * Teilnehmer 1 spielt gegen 2 (1,0) und 3 (1,5) und hat in Runde 3 eine
     * spielfreie Runde; für sie tritt ein gedachter Gegner mit dem eigenen
     * Punktestand vor dieser Runde an, also 1,5.
     */
    private const VOLL = [1 => 4.0, 2 => 2.0, 3 => 2.0, 4 => 2.5];

    /**
     * Dieselben Werte, aber ohne den jeweils schlechtesten Einzelwert.
     */
    private const GESTRICHEN = [1 => 3.0, 2 => 2.0, 3 => 2.0, 4 => 1.5];

    /**
     * Prüft, dass die Kalibrierung die Fassung ohne Streichung erkennt.
     *
     * @return void
     */
    public function testErkenntRechnungOhneStreichung(): void
    {
        $turnier = TurnierBauer::einzelturnier('Buchholzwertung', self::VOLL);

        $regel = Feinwertung::kalibriere(
            $turnier->getSpieler(),
            $turnier->getPaarungen(),
            'Buchholzwertung',
            self::VOLL,
            3
        );

        $this->assertNotNull($regel);
        $this->assertSame([0, 0], $regel['streiche']);
        $this->assertSame('remis', $regel['gegner']);
        $this->assertSame('gedacht', $regel['freirunde']);
    }

    /**
     * Prüft, dass die Kalibrierung eine Streichung erkennt.
     *
     * Das ist der eigentliche Beweis dafür, dass wirklich gesucht wird: Die
     * Fassung mit gestrichenem schlechtesten Wert steht nicht an erster
     * Stelle der Kandidaten.
     *
     * @return void
     */
    public function testErkenntStreichung(): void
    {
        $turnier = TurnierBauer::einzelturnier('Buchholzwertung', self::GESTRICHEN);

        $regel = Feinwertung::kalibriere(
            $turnier->getSpieler(),
            $turnier->getPaarungen(),
            'Buchholzwertung',
            self::GESTRICHEN,
            3
        );

        $this->assertNotNull($regel);
        $this->assertSame([1, 0], $regel['streiche']);
    }

    /**
     * Prüft, dass unerreichbare Werte keine Regel liefern.
     *
     * Lieber keine Feinwertung als eine erfundene: Eine Zahl, die neben der
     * amtlichen Tabelle steht und ihr widerspricht, ist schlimmer als eine
     * leere Spalte.
     *
     * @return void
     */
    public function testUnerreichbareWerteLiefernKeineRegel(): void
    {
        $werte = [1 => 99.0, 2 => 98.0, 3 => 97.0, 4 => 96.0];
        $turnier = TurnierBauer::einzelturnier('Buchholzwertung', $werte);

        $this->assertNull(Feinwertung::kalibriere(
            $turnier->getSpieler(),
            $turnier->getPaarungen(),
            'Buchholzwertung',
            $werte,
            3
        ));
    }

    /**
     * Prüft, dass lauter Nullen keine Regel bestätigen.
     *
     * Eine Datei ohne ausgewertete Runden hat überall null stehen; jede Regel
     * träfe das, und keine wäre dadurch belegt.
     *
     * @return void
     */
    public function testNullenBelegenKeineRegel(): void
    {
        $turnier = TurnierBauer::einzelturnier('Buchholzwertung');

        $this->assertNull(Feinwertung::kalibriere(
            $turnier->getSpieler(),
            $turnier->getPaarungen(),
            'Buchholzwertung',
            [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0],
            3
        ));
    }

    /**
     * Prüft, dass nicht nachrechenbare Wertungen abgelehnt werden.
     *
     * @return void
     */
    public function testUnbekannteWertungWirdAbgelehnt(): void
    {
        $this->assertFalse(Feinwertung::kann('Berliner Wertung'));
        $this->assertTrue(Feinwertung::kann('Buchholzwertung'));
        $this->assertSame([], Feinwertung::werte([], [], 'Berliner Wertung', 3, ['gegner' => 'remis', 'freirunde' => 'gedacht', 'streiche' => [0, 0]]));
    }

    /**
     * Prüft, dass der Rundenschnitt die Feinwertung nur bei Deckung zeigt.
     *
     * Mit passenden Werten in der Datei erscheint die Spalte im
     * Zwischenstand; mit unerreichbaren Werten verschwindet sie samt ihrer
     * Bezeichnung, und ein Hinweis nennt den Grund.
     *
     * @return void
     */
    public function testSchnittZeigtFeinwertungNurNachDeckung(): void
    {
        $mit = Rundenschnitt::bis(TurnierBauer::einzelturnier('Buchholzwertung', self::VOLL), 2);
        $ohne = Rundenschnitt::bis(TurnierBauer::einzelturnier('Buchholzwertung', [1 => 99.0, 2 => 1.0, 3 => 2.0, 4 => 3.0]), 2);

        $this->assertSame('Buchholzwertung', $mit->kopf('feinwertung1Text'));
        $this->assertGreaterThan(0.0, $mit->getSpieler()[1]['feinwertung1']);

        // kopf() gibt für eine leere Angabe den Standardwert zurück.
        $this->assertSame('', $ohne->kopf('feinwertung1Text', ''));
        $this->assertSame(0.0, $ohne->getSpieler()[1]['feinwertung1']);
        $this->assertCount(2, $ohne->getHinweise());
    }
}
