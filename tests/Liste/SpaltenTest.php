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
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\ListenBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Spalten;
use Schachbulle\ContaoChesstournamentviewerBundle\Tests\Turnier\TurnierBauer;

/**
 * Prüft die wählbaren Spalten von Teilnehmerliste und Rangliste.
 */
class SpaltenTest extends TestCase
{
    /**
     * Prüft, dass nur Spalten angeboten werden, die Werte haben.
     *
     * Das Prüfturnier führt eine TWZ, aber weder Elo noch DWZ, weder Titel
     * noch Geburtsjahr. Diese Spalten sollen im Backend gar nicht erst
     * erscheinen — ein Kästchen ohne Wirkung ist eine schlechte Auskunft.
     *
     * @return void
     */
    public function testNurBelegteSpaltenWerdenAngeboten(): void
    {
        $verfuegbar = Spalten::verfuegbar('teilnehmer', TurnierBauer::einzelturnier());

        $this->assertContains('nr', $verfuegbar);
        $this->assertContains('name', $verfuegbar);
        $this->assertContains('twz', $verfuegbar);
        $this->assertNotContains('elo', $verfuegbar);
        $this->assertNotContains('geburtsjahr', $verfuegbar);
        $this->assertNotContains('brett', $verfuegbar, 'Brett gibt es nur bei Mannschaftsturnieren.');
    }

    /**
     * Prüft, dass Mannschaftsturniere die Brettspalte anbieten.
     *
     * @return void
     */
    public function testMannschaftsturnierBietetBrettAn(): void
    {
        $this->assertContains('brett', Spalten::verfuegbar('teilnehmer', TurnierBauer::mannschaftsturnier()));
    }

    /**
     * Prüft, dass eine Feinwertung ohne Werte nicht erscheint.
     *
     * Eine Spalte voller Nullen sähe aus wie ein Ergebnis und wäre keines.
     *
     * @return void
     */
    public function testLeereFeinwertungFaelltWeg(): void
    {
        $ohne = Spalten::verfuegbar('rangliste', TurnierBauer::einzelturnier());
        $mit = Spalten::verfuegbar('rangliste', TurnierBauer::einzelturnier('Buchholzwertung', [1 => 4.0, 2 => 2.0, 3 => 2.0, 4 => 2.5]));

        $this->assertNotContains('feinwertung1', $ohne);
        $this->assertContains('feinwertung1', $mit);
    }

    /**
     * Prüft, dass die Reihenfolge der Auswahl übernommen wird.
     *
     * @return void
     */
    public function testReihenfolgeDerAuswahl(): void
    {
        $spalten = Spalten::fuerAusgabe('teilnehmer', ['twz', 'name', 'nr'], TurnierBauer::einzelturnier());

        $this->assertSame(['twz', 'name', 'nr'], array_column($spalten, 'schluessel'));
    }

    /**
     * Prüft, dass unbekannte Spalten wegfallen und die Vorgabe einspringt.
     *
     * Dieselbe Auswahl soll für mehrere Turnierdateien taugen; was eine Datei
     * nicht hergibt, wird übergangen. Bleibt nichts übrig, gilt die Vorgabe —
     * eine Tabelle ohne Spalten wäre unbrauchbar.
     *
     * @return void
     */
    public function testUnbekannteSpaltenFallenWeg(): void
    {
        $turnier = TurnierBauer::einzelturnier();

        $gemischt = Spalten::fuerAusgabe('teilnehmer', ['name', 'elo', 'gibtesnicht'], $turnier);
        $this->assertSame(['name'], array_column($gemischt, 'schluessel'));

        $leer = Spalten::fuerAusgabe('teilnehmer', ['elo', 'geburtsjahr'], $turnier);
        $this->assertSame(['nr', 'name', 'twz'], array_column($leer, 'schluessel'));
    }

    /**
     * Prüft, dass die Vorgabe ohne Auswahl greift.
     *
     * @return void
     */
    public function testVorgabeOhneAuswahl(): void
    {
        $spalten = Spalten::fuerAusgabe('rangliste', [], TurnierBauer::einzelturnier());

        $this->assertSame(['platz', 'name', 'twz', 'bilanz', 'punkte'], array_column($spalten, 'schluessel'));
    }

    /**
     * Prüft, dass der Listenbauer die Spalten durchreicht.
     *
     * @return void
     */
    public function testListenbauerReichtSpaltenDurch(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::einzelturnier(),
            new Auswahl(['teilnehmer'], false, false, 0, [], ['teilnehmer' => ['name', 'nr']])
        );

        $this->assertSame(['name', 'nr'], array_column($listen[0]['daten']['spalten'], 'schluessel'));
        $this->assertTrue($listen[0]['daten']['sortierbar']);
    }

    /**
     * Prüft, dass gegliederte Tabellen nicht sortierbar sind.
     *
     * Beim Sortieren rutschten die Kopfzeilen der Mannschaften zwischen die
     * Spieler und die Gliederung wäre dahin.
     *
     * @return void
     */
    public function testGegliederteTabelleIstNichtSortierbar(): void
    {
        $listen = (new ListenBauer())->baue(
            TurnierBauer::mannschaftsturnier(),
            new Auswahl(['teilnehmer'])
        );

        $this->assertFalse($listen[0]['daten']['sortierbar']);
    }
}
