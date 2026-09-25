<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Controller\ContentElement\Umschlagvorschau;

/**
 * Prüft die Vorschau der Umschlag-Elemente in der Backend-Liste.
 */
class UmschlagvorschauTest extends TestCase
{
    /**
     * Prüft den Hinweistext mit geladener Sprachdatei.
     *
     * Contao führt die Beschriftung eines Inhaltselements als Paar aus Name
     * und Erklärung; im Kasten steht der Name in Großbuchstaben.
     *
     * @return void
     */
    public function testHinweisNimmtDenNamenAusDerSprachdatei(): void
    {
        $GLOBALS['TL_LANG']['CTE']['chesstournamentviewerStart'] = ['Umschlag Anfang', 'Öffnet einen Umschlag.'];

        $this->assertSame('### UMSCHLAG ANFANG ###', Umschlagvorschau::hinweis('chesstournamentviewerStart'));

        unset($GLOBALS['TL_LANG']['CTE']['chesstournamentviewerStart']);
    }

    /**
     * Prüft den Hinweistext ohne Sprachdatei.
     *
     * Fehlt die Beschriftung, steht der Typenschlüssel im Kasten — ein
     * leerer Kasten wäre in der Liste nicht zuzuordnen.
     *
     * @return void
     */
    public function testHinweisOhneSprachdatei(): void
    {
        unset($GLOBALS['TL_LANG']['CTE']['chesstournamentviewerStop']);

        $this->assertSame('### CHESSTOURNAMENTVIEWERSTOP ###', Umschlagvorschau::hinweis('chesstournamentviewerStop'));
    }
}
