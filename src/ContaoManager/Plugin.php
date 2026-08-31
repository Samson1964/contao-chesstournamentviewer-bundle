<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\ContaoChesstournamentviewerBundle;

/**
 * Meldet das Bundle beim Contao Manager an.
 *
 * Ohne den Eintrag `extra.contao-manager-plugin` in der composer.json wird
 * diese Klasse nicht gefunden und das Bundle nicht in den Kernel geladen.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * Gibt die Ladereihenfolge des Bundles an.
     *
     * Das Bundle wird nach dem Contao-Kern geladen, weil es dessen
     * Inhaltselement-Infrastruktur und die Tabelle `tl_content` erweitert.
     * Weitere Abhängigkeiten bestehen nicht; das Auslesen der Turnierdateien
     * geschieht vollständig in eigenen Klassen ohne fremde Bibliotheken.
     *
     * @param ParserInterface $parser Wird vom Contao Manager übergeben, um
     *                                zusätzliche Konfigurationsdateien zu
     *                                lesen; hier nicht benötigt.
     *
     * @return BundleConfig[] Genau ein Eintrag für dieses Bundle
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoChesstournamentviewerBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
