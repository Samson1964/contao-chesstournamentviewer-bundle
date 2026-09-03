<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Format;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\SwissManager\SwissManagerFormat;

/**
 * Prüft den Leser für Swiss-Manager-Dateien.
 *
 * Die Testfälle bauen sich eine Datei selbst zusammen, statt eine echte
 * mitzuliefern: Turnierdateien enthalten Namen, Geburtsdaten und
 * Mitgliedsnummern lebender Personen, und das gehört nicht in ein
 * öffentliches Repository. Der Aufbau folgt genau dem, was der Leser
 * beschreibt — geht eine Annahme darüber verloren, schlägt der Test an.
 */
class SwissManagerFormatTest extends TestCase
{
    /**
     * Prüft, dass fremde Dateien nicht angenommen werden.
     *
     * @return void
     */
    public function testFremdeDateienWerdenAbgelehnt(): void
    {
        $format = new SwissManagerFormat();

        $this->assertFalse($format->erkennt('x.tunx', ''));
        $this->assertFalse($format->erkennt('x.tunx', 'Das ist Text.'));
        $this->assertFalse($format->erkennt('x.tunx', "\x93\xff\x89\x44".str_repeat("\x00", 500)));
    }

    /**
     * Prüft, dass eine gültige Datei erkannt und gelesen wird.
     *
     * @return void
     */
    public function testDateiWirdGelesen(): void
    {
        $inhalt = $this->datei();
        $format = new SwissManagerFormat();

        $this->assertTrue($format->erkennt('pruef.tunx', $inhalt));

        $turnier = $format->lese('pruef.tunx', $inhalt);

        $this->assertSame('Prüfturnier', $turnier->kopf('name'));
        $this->assertSame(3, $turnier->kopf('runden'));
        $this->assertCount(4, $turnier->getSpieler());
        $this->assertSame('Erster,Anna', $turnier->getSpieler()[1]['name']);
        $this->assertSame(2000, $turnier->getSpieler()[1]['elo']);
        $this->assertSame(1990, $turnier->getSpieler()[1]['geburtsjahr']);
        $this->assertSame(4711, $turnier->getSpieler()[1]['fideId']);
    }

    /**
     * Prüft, dass Punkte und Rangliste aus den Partien entstehen.
     *
     * Die Prüfdatei hat zwei Runden: In Runde 1 gewinnt 1 gegen 2 und 3 spielt
     * remis gegen 4, in Runde 2 gewinnt 1 gegen 3, 2 gewinnt kampflos und 4
     * ist spielfrei. Daraus folgen 2, 1, 0,5 und 1,5 Punkte.
     *
     * @return void
     */
    public function testPunkteAusDenPartien(): void
    {
        $turnier = (new SwissManagerFormat())->lese('pruef.tunx', $this->datei());
        $spieler = $turnier->getSpieler();

        $this->assertSame(2.0, $spieler[1]['punkte']);
        $this->assertSame(1.0, $spieler[2]['punkte']);
        $this->assertSame(0.5, $spieler[3]['punkte']);
        $this->assertSame(1.5, $spieler[4]['punkte']);

        // Ein kampfloser Sieg zählt Punkte, aber keine gespielte Partie.
        $this->assertSame(1, $spieler[2]['partien']);
        $this->assertSame(1, $spieler[4]['partien']);
        $this->assertSame(1, $spieler[4]['ausgesetzt']);

        $this->assertSame([1, 2, 3, 4], array_column($turnier->getRangliste(), 'platz'));
        $this->assertSame('Erster,Anna', $turnier->getRangliste()[0]['name']);
    }

    /**
     * Prüft, dass die Runden richtig getrennt werden.
     *
     * Die Datei nennt keine Rundennummer; die Grenze ist daran zu erkennen,
     * dass ein Teilnehmer zum zweiten Mal auftaucht.
     *
     * @return void
     */
    public function testRundenWerdenGetrennt(): void
    {
        $runden = (new SwissManagerFormat())->lese('pruef.tunx', $this->datei())->getRunden();

        $this->assertSame([1, 2], array_keys($runden));
        $this->assertCount(2, $runden[1]);

        // Drei Einträge: eine Partie, ein kampfloser Sieg, eine spielfreie
        // Runde. Kampflose und spielfreie Sätze stehen hinter den Partien
        // ihrer Runde und dürfen keinen Rundenwechsel auslösen.
        $this->assertCount(3, $runden[2]);
        $this->assertTrue($runden[2][2]['spielfrei']);
    }

    /**
     * Baut eine vollständige Swiss-Manager-Datei für die Prüfung.
     *
     * @return string Der Dateiinhalt
     */
    private function datei(): string
    {
        $kopf = [
            'Prüfturnier', 'Untertitel', '', 'Schiedsrichter', 'Verband', 'Spiellokal',
            '', '', '', 'Prüfturnier', 'Prüfstadt', 'Kennung', '', 'U20', '10 min',
            '', '', '', '', '', 'GER', 'Hauptschiedsrichter', '', 'post@example.org',
            'https://example.org', 'GER',
        ];

        $inhalt = "\x93\xff\x89\x44".str_repeat("\x01", 0x68);

        foreach ($kopf as $feld) {
            $inhalt .= $this->kette($feld);
        }

        $inhalt .= "\x95\xff\x89\x44".$this->wort(3).str_repeat("\x00", 20);
        $inhalt .= "\xa3\xff\x89\x44".str_repeat("\x00", 20);
        $inhalt .= "\xa5\xff\x89\x44";

        $spieler = [
            ['Erster', 'Anna', 'WFM', 2000, 1990, 4711],
            ['Zweiter', 'Berta', '', 1900, 1991, 4712],
            ['Dritter', 'Clara', '', 1800, 1992, 4713],
            ['Vierter', 'Dora', '', 1700, 1993, 4714],
        ];

        foreach ($spieler as [$nachname, $vorname, $titel, $elo, $jahr, $fide]) {
            $inhalt .= $this->spielerkarte($nachname, $vorname, $titel, $elo, $jahr, $fide);
        }

        $inhalt .= "\xb3\xff\x89\x44";

        // Runde 1: 1 schlägt 2, 3 remis gegen 4.
        $inhalt .= $this->partie(1, 2, 1).$this->partie(3, 4, 2);

        // Runde 2: 1 schlägt 3, 2 gewinnt kampflos, 4 ist spielfrei.
        $inhalt .= $this->partie(1, 3, 1).$this->partie(2, 0, 4).$this->partie(4, 0xFFFF, 9);

        $inhalt .= "\xd3\xff\x89\x44".str_repeat("\x00", 28);
        $inhalt .= "\xe3\xff\x89\x44";

        return $inhalt;
    }

    /**
     * Baut eine Teilnehmerkarte: 33 Zeichenketten und 104 Byte Zahlen.
     *
     * @param string $nachname Nachname
     * @param string $vorname  Vorname
     * @param string $titel    Titel, etwa `WFM`
     * @param int    $elo      Internationale Wertungszahl
     * @param int    $jahr     Geburtsjahr
     * @param int    $fide     FIDE-Kennung
     *
     * @return string Die Karte als Bytefolge
     */
    private function spielerkarte(string $nachname, string $vorname, string $titel, int $elo, int $jahr, int $fide): string
    {
        $texte = array_fill(0, 33, '');
        $texte[0] = $nachname;
        $texte[1] = $vorname;
        $texte[3] = mb_substr($vorname, 0, 1).'. '.$nachname;
        $texte[4] = $titel;
        $texte[9] = 'SV Prüfstadt';
        $texte[10] = 'GER';

        $karte = '';

        foreach ($texte as $feld) {
            $karte .= $this->kette($feld);
        }

        $zahlen = $this->wort(0).$this->wort($elo).$this->wort(0).$this->wort(0);
        $zahlen .= $this->langwort($jahr * 10000);
        $zahlen .= $this->wort(0).$this->wort(0).$this->wort(0);
        $zahlen .= $this->langwort($fide);
        $zahlen .= str_repeat("\x00", 104 - \strlen($zahlen));

        return $karte.$zahlen;
    }

    /**
     * Baut einen Partiesatz von 21 Byte.
     *
     * @param int $weiss   Nummer der weißen Seite
     * @param int $schwarz Nummer der schwarzen Seite, 0 bei kampflos
     * @param int $code    Ergebnisschlüssel
     *
     * @return string Der Satz als Bytefolge
     */
    private function partie(int $weiss, int $schwarz, int $code): string
    {
        return $this->wort($weiss).$this->wort($schwarz).$this->wort($code).str_repeat("\x00", 15);
    }

    /**
     * Baut eine Zeichenkette mit vorangestellter Zeichenzahl.
     *
     * @param string $text Der Text in UTF-8
     *
     * @return string Längenwort und Text in UTF-16LE
     */
    private function kette(string $text): string
    {
        $roh = (string) mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');

        return $this->wort((int) (\strlen($roh) / 2)).$roh;
    }

    /**
     * Baut ein 16-Bit-Wort.
     *
     * @param int $wert Der Wert
     *
     * @return string Zwei Byte, niederwertiges zuerst
     */
    private function wort(int $wert): string
    {
        return \chr($wert & 0xFF).\chr(($wert >> 8) & 0xFF);
    }

    /**
     * Baut ein 32-Bit-Wort.
     *
     * @param int $wert Der Wert
     *
     * @return string Vier Byte, niederwertiges zuerst
     */
    private function langwort(int $wert): string
    {
        return $this->wort($wert & 0xFFFF).$this->wort(($wert >> 16) & 0xFFFF);
    }
}
