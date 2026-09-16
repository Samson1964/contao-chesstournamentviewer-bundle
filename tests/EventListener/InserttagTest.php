<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoChesstournamentviewerBundle\DependencyInjection\ContaoChesstournamentviewerExtension;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\InserttagHookListener;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\InserttagResolver;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\InserttagAusgabe;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Prüft den Inserttag `{{ctv::…}}`.
 *
 * Geprüft wird beides: dass der Rückruf fremde Inserttags in Ruhe lässt, und
 * dass genau einer der beiden Wege angemeldet wird — der alte Hook unter
 * Contao 4.13, die neue Schnittstelle ab Contao 5.2.
 */
class InserttagTest extends TestCase
{
    /**
     * Prüft, dass fremde Inserttags unberührt bleiben.
     *
     * Ein Rückruf, der alles beantwortet, verschluckt die Tags anderer
     * Erweiterungen. Deshalb muss hier false herauskommen, damit Contao mit
     * den übrigen Rückrufen weitermacht.
     *
     * @return void
     */
    public function testFremdeInserttagsBleibenUnberuehrt(): void
    {
        $ausgabe = $this->createMock(InserttagAusgabe::class);
        $ausgabe->expects($this->never())->method('html');

        $rueckruf = new InserttagHookListener($ausgabe);

        $this->assertFalse($rueckruf('insert_article::foo'));
        $this->assertFalse($rueckruf('news_url::12'));
    }

    /**
     * Prüft, dass der eigene Inserttag ausgewertet wird.
     *
     * Die Kennung steht hinter dem doppelten Doppelpunkt und darf selbst
     * keinen enthalten — `explode` mit Begrenzung sorgt dafür, dass eine
     * Kennung mit Doppelpunkt nicht abgeschnitten wird.
     *
     * @return void
     */
    public function testEigenerInserttagWirdAusgewertet(): void
    {
        $ausgabe = $this->createMock(InserttagAusgabe::class);
        $ausgabe->expects($this->once())
            ->method('html')
            ->with('olympiade-ger-r1')
            ->willReturn('<div class="ctv"></div>')
        ;

        $rueckruf = new InserttagHookListener($ausgabe);

        $this->assertSame('<div class="ctv"></div>', $rueckruf('ctv::olympiade-ger-r1'));
    }

    /**
     * Prüft, dass genau ein Weg für den Inserttag angemeldet wird.
     *
     * Wären es beide, stünde die Turniertabelle zweimal auf der Seite. Welcher
     * Weg gilt, hängt an der Contao-Fassung; im Testlauf liegt Contao 5 vor,
     * dort muss es die neue Schnittstelle sein.
     *
     * @return void
     */
    public function testNurEinWegWirdAngemeldet(): void
    {
        $container = new ContainerBuilder();
        (new ContaoChesstournamentviewerExtension())->load([], $container);

        $this->assertTrue($container->hasDefinition('schachbulle_ctv.inserttag'));

        $dienst = $container->getDefinition('schachbulle_ctv.inserttag');
        $neu = class_exists('Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag');

        if ($neu) {
            $this->assertSame(InserttagResolver::class, $dienst->getClass());
            $this->assertSame([['name' => 'ctv']], $dienst->getTag('contao.insert_tag'));
            $this->assertSame([], $dienst->getTag('contao.hook'));
        } else {
            $this->assertSame(InserttagHookListener::class, $dienst->getClass());
            $this->assertSame([['hook' => 'replaceInsertTags']], $dienst->getTag('contao.hook'));
            $this->assertSame([], $dienst->getTag('contao.insert_tag'));
        }
    }
}
