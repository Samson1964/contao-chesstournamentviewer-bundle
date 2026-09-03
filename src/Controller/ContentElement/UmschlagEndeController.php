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
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schließt den Umschlag, den „Umschlag Anfang" geöffnet hat.
 *
 * Das Element gibt nichts als die schließende Hülle aus. Es hat keine
 * Einstellungen — alles, was den Umschlag betrifft, steht am öffnenden
 * Element.
 */
class UmschlagEndeController extends AbstractContentElementController
{
    /**
     * Erzeugt die schließende Hülle.
     *
     * @param Template     $template Das Template des Inhaltselements
     * @param ContentModel $model    Der Datensatz des Inhaltselements
     * @param Request      $request  Die laufende Anfrage
     *
     * @return Response Die schließende Hülle
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
