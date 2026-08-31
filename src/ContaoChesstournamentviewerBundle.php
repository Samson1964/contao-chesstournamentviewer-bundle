<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Hauptklasse des Bundles.
 *
 * Symfony erkennt ein Bundle ausschließlich an einer solchen Klasse. Sie
 * bleibt bewusst leer: Dienste, Templates und DCA-Dateien werden über die
 * Extension und die Verzeichnisstruktur gefunden, nicht über überschriebene
 * Methoden.
 *
 * Aus dem Klassennamen leitet Symfony auch das Verzeichnis der öffentlichen
 * Dateien ab. `src/Resources/public/` landet deshalb unter
 * `public/bundles/contaochesstournamentviewer/` — auf diesen Pfad verweisen
 * die Einbindungen von CSS und JavaScript im Controller.
 */
class ContaoChesstournamentviewerBundle extends Bundle
{
}
