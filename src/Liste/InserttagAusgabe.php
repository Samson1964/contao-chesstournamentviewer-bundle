<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Model\CtvInserttagModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Erzeugt die Ausgabe zu einem Inserttag `{{ctv::kennung}}`.
 *
 * Gesucht wird der Datensatz des Backend-Moduls, entweder über seine Kennung
 * oder über seine ID. Gefunden oder nicht: Der Inserttag darf die Seite nicht
 * zerstören, deshalb bleibt die Ausgabe im Fehlerfall leer und der Grund
 * steht im Fehlerprotokoll.
 *
 * Beide Wege in Contao — der Hook unter 4.13 und das Attribut ab Contao 5 —
 * benutzen diesen Dienst; so gibt es die Arbeit nur einmal.
 */
class InserttagAusgabe
{
    /**
     * Erzeugt den Dienst.
     *
     * @param ContaoFramework $framework    Wird für Model und Template gebraucht
     * @param TurnierAusgabe  $ausgabe      Stellt die Ausgabe aus den Einstellungen zusammen
     * @param LoggerInterface $logger       Schreibt Lesefehler ins Contao-Fehlerprotokoll
     * @param RequestStack    $requestStack Liefert die Sprache der laufenden Anfrage
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TurnierAusgabe $ausgabe,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Gibt die fertige Ausgabe eines Inserttags zurück.
     *
     * @param string $kennung Der Wert hinter `ctv::` — Kennung oder ID
     *
     * @return string Das HTML der Liste, oder eine leere Zeichenkette, wenn
     *                es den Datensatz nicht gibt, die Datei nicht lesbar ist
     *                oder die Einstellungen nichts ergeben
     */
    public function html(string $kennung): string
    {
        $kennung = trim($kennung);

        if ('' === $kennung) {
            return '';
        }

        $this->framework->initialize();

        $datensatz = $this->framework->getAdapter(CtvInserttagModel::class)->findByIdOrAlias($kennung);

        if (null === $datensatz) {
            $this->logger->error(sprintf('Zum Inserttag {{ctv::%s}} gibt es keine Turnierausgabe.', $kennung));

            return '';
        }

        try {
            $werte = $this->ausgabe->baue($datensatz->row(), $this->requestStack->getCurrentRequest()?->getLocale());
        } catch (\Throwable $ausnahme) {
            $this->logger->error(sprintf('Turnierdatei des Inserttags {{ctv::%s}} konnte nicht gelesen werden: %s', $kennung, $ausnahme->getMessage()));

            return '';
        }

        if (null === $werte) {
            return '';
        }

        $this->bindeDateienEin();

        // Die Kennung im Markup macht mehrere Ausgaben auf einer Seite
        // unterscheidbar — für eigene Stilregeln und für Sprungmarken.
        $werte['kennung'] = 'ctv-it-'.$datensatz->id;

        $template = $this->framework->createInstance(FrontendTemplate::class, ['ctv_ausgabe']);
        $template->setData($werte);

        return $template->parse();
    }

    /**
     * Bindet Stilvorlagen und Skript des Betrachters ein.
     *
     * Das geht hier noch: Contao ersetzt die Inserttags eines Templates, wenn
     * dieses zusammengebaut wird — und das geschieht vor dem Seitenkopf. Die
     * Schlüssel im Array sind dieselben wie beim Inhaltselement, damit die
     * Dateien auf einer Seite mit beidem nur einmal erscheinen.
     *
     * @return void
     */
    private function bindeDateienEin(): void
    {
        $GLOBALS['TL_CSS']['ctv'] = 'bundles/contaochesstournamentviewer/css/betrachter.css|static';
        $GLOBALS['TL_CSS']['ctv_flaggen'] = 'bundles/contaochesstournamentviewer/css/flaggen.css|static';
        $GLOBALS['TL_JAVASCRIPT']['ctv'] = 'bundles/contaochesstournamentviewer/js/betrachter.js|static';
    }
}
