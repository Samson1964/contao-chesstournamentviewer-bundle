<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\TlContentListener;

/**
 * Prüft das Kürzen der Palette.
 *
 * Die Maske streicht Felder, die zur Datei oder zur Listenauswahl nicht
 * passen. Weil eine falsch gekürzte Palette Felder unerreichbar macht — der
 * Redakteur kann dann eine Einstellung weder sehen noch zurücknehmen —, wird
 * die Zeichenkettenarbeit hier für sich geprüft.
 */
class PaletteTest extends TestCase
{
    /**
     * Die vollständige Palette des Inhaltselements.
     */
    private const PALETTE = '{type_legend},type,headline;'
        .'{ctv_legend},ctvDatei,ctvFormat,ctvListe;'
        .'{ctv_spalten_legend},ctvSpalten;'
        .'{ctv_runden_legend},ctvStand,ctvRunden;'
        .'{ctv_mannschaft_legend},ctvMannschaftSpieler,ctvKreuzKurz;'
        .'{template_legend:hide},customTpl;'
        .'{invisible_legend:hide},invisible,start,stop';

    /**
     * Prüft, dass einzelne Felder verschwinden und der Rest bleibt.
     *
     * @return void
     */
    public function testFeldWirdEntfernt(): void
    {
        $palette = $this->ohneFelder(['ctvSpalten']);

        $this->assertStringNotContainsString('ctvSpalten', $palette);
        $this->assertStringNotContainsString('ctv_spalten_legend', $palette);
        $this->assertStringContainsString('{ctv_legend},ctvDatei,ctvFormat,ctvListe;', $palette);
        $this->assertStringContainsString('{ctv_runden_legend},ctvStand,ctvRunden;', $palette);
    }

    /**
     * Prüft, dass eine leergeräumte Feldgruppe mitsamt Überschrift wegfällt.
     *
     * Eine Überschrift „Mannschaftsturniere" ohne ein einziges Feld darunter
     * wäre irreführender als gar keine.
     *
     * @return void
     */
    public function testLeereGruppeFaelltWeg(): void
    {
        $palette = $this->ohneFelder(['ctvMannschaftSpieler', 'ctvKreuzKurz']);

        $this->assertStringNotContainsString('ctv_mannschaft_legend', $palette);
        $this->assertStringContainsString('{template_legend:hide},customTpl;', $palette);
    }

    /**
     * Prüft, dass mehrere Gruppen gleichzeitig geräumt werden können.
     *
     * @return void
     */
    public function testMehrereGruppenGleichzeitig(): void
    {
        $palette = $this->ohneFelder(['ctvStand', 'ctvRunden', 'ctvMannschaftSpieler', 'ctvKreuzKurz']);

        $this->assertStringNotContainsString('ctv_runden_legend', $palette);
        $this->assertStringNotContainsString('ctv_mannschaft_legend', $palette);
        $this->assertStringContainsString('{ctv_legend},ctvDatei,ctvFormat,ctvListe;', $palette);
    }

    /**
     * Prüft, dass unbekannte Feldnamen die Palette unverändert lassen.
     *
     * @return void
     */
    public function testUnbekanntesFeldAendertNichts(): void
    {
        $this->assertSame(self::PALETTE, $this->ohneFelder(['gibtesnicht']));
    }

    /**
     * Ruft die private Hilfsmethode des Rückrufs auf.
     *
     * Sie ist bewusst privat — von außen gebraucht wird sie nicht. Für die
     * Prüfung ist der Umweg über die Spiegelung der geringere Preis, als die
     * Schnittstelle für einen Testfall zu öffnen.
     *
     * @param string[] $felder Die zu entfernenden Feldnamen
     *
     * @return string Die gekürzte Palette
     */
    private function ohneFelder(array $felder): string
    {
        $methode = new \ReflectionMethod(TlContentListener::class, 'ohneFelder');
        $methode->setAccessible(true);

        return $methode->invoke(null, self::PALETTE, $felder);
    }
}
