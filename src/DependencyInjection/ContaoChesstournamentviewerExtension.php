<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\DependencyInjection;

use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\InserttagHookListener;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\InserttagResolver;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\InserttagAusgabe;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Lädt die Dienste des Bundles in den Symfony-Container.
 *
 * Symfony leitet den erwarteten Klassennamen aus dem Bundle-Namen ab:
 * ContaoChesstournamentviewerExtension gehört zu
 * ContaoChesstournamentviewerBundle. Weicht eines von beiden ab, findet der
 * Kernel die Dienste nicht — und zwar ohne Fehlermeldung.
 */
class ContaoChesstournamentviewerExtension extends Extension
{
    /**
     * Liest die services.yaml des Bundles ein und meldet den Inserttag an.
     *
     * Eine eigene Konfiguration über die Projektdatei `config/config.yaml`
     * bietet das Bundle nicht an: alle Einstellungen stehen am jeweiligen
     * Inhaltselement beziehungsweise am Datensatz des Backend-Moduls. Der
     * Parameter $configs bleibt deshalb ungenutzt.
     *
     * @param array<array-key, mixed> $configs   Konfiguration aus dem Projekt, hier leer
     * @param ContainerBuilder        $container Der im Aufbau befindliche Container
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $this->meldeInserttagAn($container);
    }

    /**
     * Meldet den Inserttag `{{ctv::…}}` passend zur Contao-Fassung an.
     *
     * Contao 5.2 hat eine eigene Schnittstelle für Inserttags eingeführt und
     * den alten Hook für veraltet erklärt; in Contao 6 wird er entfallen.
     * Contao 4.13 wiederum kennt die neue Schnittstelle nicht. Angemeldet
     * wird deshalb genau einer der beiden Wege — wären es beide, stünde die
     * Turniertabelle zweimal auf der Seite.
     *
     * Erkannt wird die Fassung am Attribut `AsInsertTag`: Es kam mit der
     * neuen Schnittstelle und ist damit der verlässlichere Anhaltspunkt als
     * eine Versionsnummer.
     *
     * @param ContainerBuilder $container Der im Aufbau befindliche Container
     *
     * @return void
     */
    private function meldeInserttagAn(ContainerBuilder $container): void
    {
        $neu = class_exists('Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag');

        $dienst = new Definition($neu ? InserttagResolver::class : InserttagHookListener::class);
        $dienst->setArguments([new Reference(InserttagAusgabe::class)]);
        $dienst->setPublic(false);

        if ($neu) {
            $dienst->addTag('contao.insert_tag', ['name' => 'ctv']);
        } else {
            $dienst->addTag('contao.hook', ['hook' => 'replaceInsertTags']);
        }

        $container->setDefinition('schachbulle_ctv.inserttag', $dienst);
    }
}
