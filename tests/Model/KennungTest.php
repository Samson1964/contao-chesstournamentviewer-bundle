<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\Model;

use Ausi\SlugGenerator\SlugGenerator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Slug\Slug;
use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\Model\CtvInserttagModel;

/**
 * Prüft, wie die Kennung eines Inserttags gebildet wird.
 *
 * Anlass war ein Inserttag, der nichts fand: Gespeichert war
 * „olympiade-2026-männer-2-runde" — `StringUtil::generateAlias()` lässt das
 * „ä" stehen —, im Text stand „olympiade-2026-maenner-2-runde". Geprüft wird
 * mit dem echten Slug-Dienst von Contao; nur der Framework-Dienst ist
 * nachgebildet, weil er für eine Umschrift mit festen Einstellungen nicht
 * gebraucht wird.
 */
class KennungTest extends TestCase
{
    /**
     * Prüft die Umschrift der Umlaute und des ß.
     *
     * @return void
     */
    public function testUmlauteWerdenUmgeschrieben(): void
    {
        $slug = $this->slug();

        $this->assertSame('olympiade-2026-maenner-2-runde', CtvInserttagModel::kennung($slug, 'Olympiade 2026 Männer, 2. Runde'));
        $this->assertSame('strasse-oesterreich', CtvInserttagModel::kennung($slug, 'Straße Österreich'));
    }

    /**
     * Prüft, dass eine gespeicherte Kennung mit Umlaut zur neuen Form passt.
     *
     * Genau darauf beruht die Suche nach Umschrift im Inserttag: Beide Seiten
     * werden gleich umgeschrieben, dann stimmen sie überein.
     *
     * @return void
     */
    public function testAlteKennungPasstNachUmschrift(): void
    {
        $slug = $this->slug();

        $this->assertSame(
            CtvInserttagModel::kennung($slug, 'olympiade-2026-maenner-2-runde'),
            CtvInserttagModel::kennung($slug, 'olympiade-2026-männer-2-runde')
        );
    }

    /**
     * Prüft Grenzfälle: schon passende, leere und rein numerische Eingaben.
     *
     * Eine rein numerische Kennung bekommt ein Präfix: `{{ctv::2026}}` wäre
     * sonst nicht von der Datensatz-ID 2026 zu unterscheiden.
     *
     * @return void
     */
    public function testGrenzfaelle(): void
    {
        $slug = $this->slug();

        $this->assertSame('olympiade-ger-r1', CtvInserttagModel::kennung($slug, 'olympiade-ger-r1'));
        $this->assertSame('', CtvInserttagModel::kennung($slug, '   '));
        $this->assertSame('id-2026', CtvInserttagModel::kennung($slug, '2026'));
    }

    /**
     * Baut den Slug-Dienst, wie Contao ihn anlegt.
     *
     * @return Slug Der Dienst mit dem echten Slug-Generator
     */
    private function slug(): Slug
    {
        return new Slug(new SlugGenerator(), $this->createMock(ContaoFramework::class));
    }
}
