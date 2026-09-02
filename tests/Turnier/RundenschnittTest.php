<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Kreuztabelle;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Rundenschnitt;

/**
 * Prüft den Rundenschnitt und die nachgebaute Kreuztabelle.
 *
 * Das Prüfturnier hat vier Teilnehmer über drei Runden. Nach Runde 1 steht es
 * 1:0 für die Teilnehmer 1 und 3, nach Runde 2 führen beide mit 1½, und nach
 * Runde 3 hat Teilnehmer 1 durch eine spielfreie Runde 2½.
 */
class RundenschnittTest extends TestCase
{
    /**
     * Prüft, dass die Punkte dem Stand nach der gewählten Runde entsprechen.
     *
     * @return void
     */
    public function testPunkteNachRunde(): void
    {
        $schnitt = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 2);
        $spieler = $schnitt->getSpieler();

        $this->assertSame(1.5, $spieler[1]['punkte']);
        $this->assertSame(1.0, $spieler[2]['punkte']);
        $this->assertSame(1.5, $spieler[3]['punkte']);
        $this->assertSame(0.0, $spieler[4]['punkte']);
    }

    /**
     * Prüft, dass die Paarungen der späteren Runden verschwinden.
     *
     * Sie werden nicht bloß übergangen, sondern entfernt — sonst müsste jede
     * auswertende Stelle den Schnitt noch einmal beachten.
     *
     * @return void
     */
    public function testSpaetereRundenFallenWeg(): void
    {
        $schnitt = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 1);

        $this->assertSame(1, $schnitt->getLetzteRunde());
        $this->assertSame([1], array_keys($schnitt->getPaarungen()[1]));
        $this->assertSame([1], array_keys($schnitt->getRunden()));
    }

    /**
     * Prüft, dass die Rangliste neu sortiert wird und geteilte Plätze teilt.
     *
     * Nach Runde 2 haben die Teilnehmer 1 und 3 je anderthalb Punkte. Ohne
     * Feinwertung gibt es keinen Grund, einen von beiden vorzuziehen; beide
     * bekommen deshalb Platz 1 und der nächste Platz 3.
     *
     * @return void
     */
    public function testRanglisteWirdNeuGebildet(): void
    {
        $rangliste = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 2)->getRangliste();

        $this->assertCount(4, $rangliste);
        $this->assertSame([1, 1, 3, 4], array_column($rangliste, 'platz'));
        $this->assertSame([1.5, 1.5, 1.0, 0.0], array_column($rangliste, 'punkte'));
    }

    /**
     * Prüft, dass der Schnitt einen Hinweis auf den Zwischenstand hinterlässt.
     *
     * @return void
     */
    public function testHinweisAufDenZwischenstand(): void
    {
        $schnitt = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 2);

        $this->assertSame(2, $schnitt->kopf('standNachRunde'));
        $this->assertNotEmpty($schnitt->getHinweise());
    }

    /**
     * Prüft, dass ein zweiter Schnitt auf dieselbe Runde nichts mehr ändert.
     *
     * Controller und Listenbauer schneiden beide, ohne sich abzustimmen; der
     * zweite Aufruf darf deshalb weder rechnen noch den Hinweis wiederholen.
     *
     * @return void
     */
    public function testZweiterSchnittAendertNichts(): void
    {
        $einmal = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 2);
        $zweimal = Rundenschnitt::bis($einmal, 2);

        $this->assertSame($einmal, $zweimal);
    }

    /**
     * Prüft, dass eine Runde jenseits des Turniers nichts verändert.
     *
     * @return void
     */
    public function testRundeJenseitsDesTurniersBleibtWirkungslos(): void
    {
        $turnier = TurnierBauer::einzelturnier();

        $this->assertSame($turnier, Rundenschnitt::bis($turnier, 9));
        $this->assertSame($turnier, Rundenschnitt::bis($turnier, 0));
    }

    /**
     * Prüft, dass die Kreuztabelle des Zwischenstands zum Schnitt passt.
     *
     * Die Diagonale trägt das Blindfeld, und in Runde 1 steht in jeder Zeile
     * genau ein Ergebnis.
     *
     * @return void
     */
    public function testKreuztabelleWirdNeuGebaut(): void
    {
        $schnitt = Rundenschnitt::bis(TurnierBauer::einzelturnier(), 1);
        $tabelle = $schnitt->getKreuztabelle();

        $this->assertNotNull($tabelle);
        $this->assertCount(4, $tabelle['zeilen']);

        foreach ($tabelle['zeilen'] as $index => $zeile) {
            $this->assertSame('**', $zeile[$index], 'Die Diagonale trägt kein Blindfeld.');
            $this->assertCount(1, array_filter($zeile, static fn (string $feld): bool => '' !== $feld && '**' !== $feld));
        }
    }

    /**
     * Prüft, dass die Kreuztabelle kampflose Partien kennzeichnet.
     *
     * @return void
     */
    public function testKreuztabelleKennzeichnetKampfloseP(): void
    {
        $rangliste = [['tnr' => 1], ['tnr' => 2]];
        $paarungen = [
            1 => [1 => ['gegner' => 2, 'ergebnis' => 1.0, 'ergebnisText' => '1', 'status' => 'kampflos']],
            2 => [1 => ['gegner' => 1, 'ergebnis' => 0.0, 'ergebnisText' => '0', 'status' => 'kampflos']],
        ];

        $tabelle = Kreuztabelle::baue($rangliste, $paarungen);

        $this->assertSame('+', $tabelle['zeilen'][0][1]);
        $this->assertSame('-', $tabelle['zeilen'][1][0]);
    }
}
