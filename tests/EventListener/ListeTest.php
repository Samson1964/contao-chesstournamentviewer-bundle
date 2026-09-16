<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\MaskeListener;

/**
 * Prüft, welche Ausgabe ein Inhaltselement zeigt.
 *
 * Seit Fassung 1.8.0 gibt ein Element genau eine Liste aus. Ältere Elemente
 * führen im Feld `ctvListen` mehrere; sie behalten ihre erste.
 */
class ListeTest extends TestCase
{
    /**
     * Prüft, dass die neue Einzelauswahl gilt.
     *
     * @return void
     */
    public function testEinzelauswahlGilt(): void
    {
        $this->assertSame('rangliste', MaskeListener::liste('rangliste', null));
        $this->assertSame('rangliste', MaskeListener::liste('rangliste', serialize(['teilnehmer'])));
    }

    /**
     * Prüft, dass ein altes Element seine erste Liste behält.
     *
     * @return void
     */
    public function testAltesElementBehaeltDieErsteListe(): void
    {
        $this->assertSame(
            'teilnehmer',
            MaskeListener::liste('', serialize(['teilnehmer', 'rangliste', 'kreuztabelle']))
        );
    }

    /**
     * Prüft, dass leere Einträge der alten Auswahl übergangen werden.
     *
     * @return void
     */
    public function testLeereEintraegeWerdenUebergangen(): void
    {
        $this->assertSame('kreuztabelle', MaskeListener::liste('', serialize(['', 'kreuztabelle'])));
        $this->assertSame('', MaskeListener::liste('', serialize([])));
        $this->assertSame('', MaskeListener::liste(null, null));
    }
}
