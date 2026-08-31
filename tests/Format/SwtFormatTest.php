<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Format;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\FormatVerzeichnis;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\Swt\SwtFormat;

/**
 * Prüft die Erkennung des SWT-Formats und das Format-Verzeichnis.
 *
 * Das eigentliche Auslesen einer Turnierdatei prüft dieser Test nicht — dazu
 * bräuchte es eine echte Binärdatei im Paket. Geprüft wird, dass die
 * Erkennung nicht auf Beliebiges anspringt und dass das Verzeichnis die
 * richtigen Auskünfte gibt.
 */
class SwtFormatTest extends TestCase
{
    /**
     * Prüft, dass zu kurze Inhalte nicht erkannt werden.
     *
     * @return void
     */
    public function testKurzeDateiWirdAbgelehnt(): void
    {
        $format = new SwtFormat();

        $this->assertFalse($format->erkennt('turnier.swt', ''));
        $this->assertFalse($format->erkennt('turnier.swt', str_repeat("\x00", 100)));
    }

    /**
     * Prüft, dass eine Datei mit unmöglicher Fassungsnummer abgelehnt wird.
     *
     * Der Wert an 0x0261 ist entweder 0 oder liegt zwischen 500 und 1500.
     * Ein Text voller Buchstaben ergibt dort eine Zahl weit darüber.
     *
     * @return void
     */
    public function testFremdeDateiWirdAbgelehnt(): void
    {
        $format = new SwtFormat();

        $this->assertFalse($format->erkennt('text.swt', str_repeat('Guten Tag! ', 500)));
    }

    /**
     * Prüft, dass ein aufgebauter Kopfbereich erkannt wird.
     *
     * Nachgebildet werden nur die drei Felder, auf die sich die Erkennung
     * stützt: Fassungsnummer, Rundenzahl und Teilnehmerzahl.
     *
     * @return void
     */
    public function testAufgebauterKopfWirdErkannt(): void
    {
        $inhalt = str_repeat("\x00", 0x0600);
        $inhalt = substr_replace($inhalt, pack('v', 930), 0x0261, 2);
        $inhalt = substr_replace($inhalt, pack('v', 7), 0x0001, 2);
        $inhalt = substr_replace($inhalt, pack('v', 24), 0x0007, 2);

        $this->assertTrue((new SwtFormat())->erkennt('irgendwas.dat', $inhalt));
    }

    /**
     * Prüft die Auskünfte des Format-Verzeichnisses.
     *
     * @return void
     */
    public function testVerzeichnisKenntDasFormat(): void
    {
        $verzeichnis = new FormatVerzeichnis([new SwtFormat()]);

        $this->assertSame(['swt'], $verzeichnis->dateiendungen());
        $this->assertSame(['swt' => 'SWT (Swiss-Chess)'], $verzeichnis->auswahl());
        $this->assertArrayHasKey('swt', $verzeichnis->alle());
    }

    /**
     * Prüft, dass ein unbekannter Formatschlüssel als Fehler gemeldet wird.
     *
     * @return void
     */
    public function testUnbekanntesFormatWirdGemeldet(): void
    {
        $verzeichnis = new FormatVerzeichnis([new SwtFormat()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nicht bekannt');

        $verzeichnis->lese('turnier.pgn', 'Inhalt', 'pgn');
    }

    /**
     * Prüft, dass eine nicht erkannte Datei als Fehler gemeldet wird.
     *
     * @return void
     */
    public function testNichtErkannteDateiWirdGemeldet(): void
    {
        $verzeichnis = new FormatVerzeichnis([new SwtFormat()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nicht erkannt');

        $verzeichnis->lese('turnier.swt', 'zu kurz', 'auto');
    }
}
