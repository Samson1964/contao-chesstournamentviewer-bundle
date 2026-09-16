<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\StringUtil;
use Contao\Template;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\TurnierAusgabe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inhaltselement, das eine Turnierdatei im Frontend ausgibt.
 *
 * Registriert wird das Element über den Dienst-Tag `contao.content_element`
 * in der services.yaml und nicht über ein Attribut: Der Tag wirkt unter
 * Contao 4.13 genauso wie unter Contao 5, das Attribut gibt es erst ab
 * Contao 5.
 *
 * Die Ausgabe selbst baut der Dienst TurnierAusgabe; er wird ebenso vom
 * Inserttag `{{ctv::…}}` benutzt.
 */
class TurnierBetrachterController extends AbstractContentElementController
{
    /**
     * Erzeugt den Controller.
     *
     * @param TurnierAusgabe  $ausgabe      Stellt die Ausgabe aus den Einstellungen zusammen
     * @param TokenChecker    $tokenChecker Wird gefragt, ob ein Backend-Benutzer angemeldet
     *                                      ist; nur diesem werden Fehlermeldungen gezeigt
     * @param LoggerInterface $logger       Schreibt Lesefehler ins Contao-Fehlerprotokoll
     */
    public function __construct(
        private readonly TurnierAusgabe $ausgabe,
        private readonly TokenChecker $tokenChecker,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Erzeugt die Ausgabe des Inhaltselements.
     *
     * Der Rückgabetyp `Response` und der Parametertyp `Template` erfüllen die
     * abstrakten Signaturen beider Contao-Fassungen: PHP erlaubt beim
     * Überschreiben einen breiteren Parametertyp — `Template` ist die
     * Elternklasse des in Contao 5 verwendeten `FragmentTemplate` — und einen
     * engeren Rückgabetyp als das dort deklarierte `?Response`.
     *
     * Scheitert das Lesen der Datei, bleibt die Ausgabe für Besucher leer;
     * der Grund steht im Fehlerprotokoll und wird zusätzlich angezeigt, wenn
     * ein Backend-Benutzer angemeldet ist. Ein Besucher kann mit „Datei nicht
     * gefunden" nichts anfangen, der Redakteur sehr wohl.
     *
     * @param Template     $template Das Template des Inhaltselements
     * @param ContentModel $model    Der Datensatz des Inhaltselements
     * @param Request      $request  Die laufende Anfrage
     *
     * @return Response Die Antwort mit der gewählten Liste, oder eine leere
     *                  Antwort, wenn nichts auszugeben ist
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $template->fehler = null;
        $template->turnier = null;
        $template->liste = null;
        $template->hinweise = [];

        try {
            $werte = $this->ausgabe->baue($model->row(), $request->getLocale());
        } catch (\Throwable $ausnahme) {
            $this->logger->error(
                sprintf('Turnierdatei des Inhaltselements ID %s konnte nicht gelesen werden: %s', $model->id, $ausnahme->getMessage())
            );

            if (!$this->tokenChecker->hasBackendUser()) {
                return new Response();
            }

            $template->fehler = $ausnahme->getMessage();

            return $template->getResponse();
        }

        if (null === $werte) {
            return new Response();
        }

        $this->bindeDateienEin();

        foreach ($werte as $name => $wert) {
            $template->{$name} = $wert;
        }

        // Die Beschriftung des Reiters: die Überschrift des Elements, wenn
        // eine gesetzt ist, sonst der Name der Liste. So kann der Redakteur
        // die Lasche benennen, ohne dafür ein eigenes Feld zu brauchen.
        $ueberschrift = StringUtil::deserialize($model->headline, true);
        $template->reitername = trim((string) ($ueberschrift['value'] ?? '')) ?: $werte['name'];
        $template->kennung = 'ctv-'.$model->id;

        return $template->getResponse();
    }

    /**
     * Bindet Stilvorlagen und Skript des Betrachters ein.
     *
     * Jede Datei bekommt einen festen Schlüssel im Array, damit sie bei
     * mehreren Betrachtern auf einer Seite nur einmal ausgegeben wird. Der
     * Zusatz `|static` hält sie aus der Zusammenfassung dynamischer Dateien
     * heraus.
     *
     * Die Flaggen stehen in einer eigenen Stilvorlage: Sie besteht fast nur
     * aus Verweisen auf die SVG-Dateien und wäre in der Grundgestaltung ein
     * Fremdkörper.
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
