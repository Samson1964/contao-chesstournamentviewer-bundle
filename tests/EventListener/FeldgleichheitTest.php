<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;

/**
 * Wacht darüber, dass Inhaltselement und Inserttag dieselben Felder führen.
 *
 * Beide Masken sollen 1:1 dasselbe können. Die Felder werden zwar aus
 * `tl_content` übernommen, aber an drei Stellen muss ein neues Feld
 * nachgetragen werden: in der Übernahmeliste der DCA-Datei, in der beider
 * Sprachdateien und in der Palette. Wird eine davon vergessen, fehlt das Feld
 * im Backend-Modul oder steht dort ohne Beschriftung — beides fällt erst auf,
 * wenn es jemand benutzt.
 *
 * Geprüft wird auf dem Text der Dateien und nicht am geladenen DCA: Dafür
 * müsste eine Contao-Installation hochfahren, und das ist für die Frage, ob
 * eine Liste vollständig ist, zu viel Aufwand.
 */
class FeldgleichheitTest extends TestCase
{
    /**
     * Felder, die es nur am Inhaltselement gibt.
     *
     * `ctvListen` rettet die erste Liste eines Elements aus der Zeit vor
     * Fassung 1.8.0; in einer neuen Tabelle hat es nichts zu suchen.
     */
    private const NUR_INHALTSELEMENT = ['ctvListen'];

    /**
     * Prüft, dass das Inserttag-Modul alle Felder übernimmt.
     *
     * @return void
     */
    public function testInserttagUebernimmtAlleFelder(): void
    {
        $this->assertSame([], array_diff($this->felderDesInhaltselements(), $this->uebernommeneFelder()), 'Im Inserttag-Modul fehlen Felder des Inhaltselements.');
        $this->assertSame([], array_diff($this->uebernommeneFelder(), $this->felderDesInhaltselements()), 'Das Inserttag-Modul übernimmt Felder, die es am Inhaltselement nicht gibt.');
    }

    /**
     * Prüft, dass beide Sprachdateien dieselben Beschriftungen übernehmen.
     *
     * @return void
     */
    public function testBeschriftungenWerdenUebernommen(): void
    {
        foreach (['de', 'en'] as $sprache) {
            $uebernommen = $this->listeAusDatei(__DIR__.'/../../src/Resources/contao/languages/'.$sprache.'/tl_ctv_inserttag.php');
            $fehlend = array_diff($this->felderDesInhaltselements(), $uebernommen);

            $this->assertSame([], $fehlend, sprintf('In der Sprachdatei %s fehlen Beschriftungen.', $sprache));
        }
    }

    /**
     * Prüft, dass jedes Einstellungsfeld auch in einer Palette steht.
     *
     * Ein Feld ohne Palette ist im Backend nicht zu erreichen. Ausgenommen
     * sind die Felder, die nur noch der Verträglichkeit wegen mitlaufen.
     *
     * @return void
     */
    public function testJedesFeldStehtInEinerPalette(): void
    {
        $altlasten = ['ctvListen', 'ctvStandAus', 'ctvRundenkopfAus'];

        foreach ([
            'tl_content.php' => 'chesstournamentviewer',
            'tl_ctv_inserttag.php' => 'default',
        ] as $datei => $palette) {
            $inhalt = (string) file_get_contents(__DIR__.'/../../src/Resources/contao/dca/'.$datei);

            foreach (array_diff($this->felderDesInhaltselements(), $altlasten) as $feld) {
                $this->assertMatchesRegularExpression(
                    '/[{,]'.preg_quote($feld, '/').'[,;\']/',
                    $inhalt,
                    sprintf('Das Feld "%s" steht in %s (Palette %s) nicht in der Palette.', $feld, $datei, $palette)
                );
            }
        }
    }

    /**
     * Sammelt die Einstellungsfelder des Inhaltselements aus der DCA-Datei.
     *
     * @return string[] Die Feldnamen ohne die nur dort geführten
     */
    private function felderDesInhaltselements(): array
    {
        $inhalt = (string) file_get_contents(__DIR__.'/../../src/Resources/contao/dca/tl_content.php');

        preg_match_all("/\\['fields'\\]\\['(ctv\\w+)'\\]/", $inhalt, $treffer);

        $felder = array_values(array_unique(array_diff($treffer[1], self::NUR_INHALTSELEMENT)));
        sort($felder);

        return $felder;
    }

    /**
     * Sammelt die Felder, die das Inserttag-Modul übernimmt.
     *
     * @return string[] Die Feldnamen
     */
    private function uebernommeneFelder(): array
    {
        return $this->listeAusDatei(__DIR__.'/../../src/Resources/contao/dca/tl_ctv_inserttag.php');
    }

    /**
     * Liest die Feldnamen aus der Übernahmeliste einer Datei.
     *
     * Gemeint ist die `foreach`-Schleife am Dateiende, die die Felder
     * beziehungsweise ihre Beschriftungen aus `tl_content` holt.
     *
     * @param string $pfad Pfad zur Datei
     *
     * @return string[] Die Feldnamen, ohne die Überschriften der Feldgruppen
     */
    private function listeAusDatei(string $pfad): array
    {
        $inhalt = (string) file_get_contents($pfad);

        if (!preg_match('/foreach \(\[(.+?)\] as \$feld\)/s', $inhalt, $treffer)) {
            $this->fail(sprintf('In "%s" steht keine Übernahmeliste.', basename($pfad)));
        }

        preg_match_all("/'(ctv\\w+)'/", $treffer[1], $namen);

        $felder = array_values(array_unique($namen[1]));
        sort($felder);

        return $felder;
    }
}
