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
 * Öffnet den Umschlag, der mehrere Turnierausgaben zu Reitern zusammenfasst.
 *
 * Das Element gibt nur die öffnende Hülle aus; was darin steht, sind die
 * Turnierausgaben, die im Artikel dazwischenstehen. Geschlossen wird mit
 * „Umschlag Ende". Contao kennt dieses Muster von Akkordeon und Slider und
 * rückt die eingeschlossenen Elemente im Backend ein, sobald das Element in
 * `TL_WRAPPERS` steht.
 *
 * **Die Reiterleiste baut das Skript**, nicht der Server: Das öffnende
 * Element weiß nicht, was nach ihm kommt — es müsste den Artikel absuchen und
 * dabei erraten, wo der Umschlag endet. Das Skript findet die Ausgaben
 * dagegen einfach im fertigen HTML. Ohne JavaScript stehen sie untereinander,
 * jede mit ihrer Überschrift; das ist die Rückfallebene, die der Betrachter
 * ohnehin schon hatte.
 */
class UmschlagAnfangController extends AbstractContentElementController
{
    /**
     * Erzeugt die öffnende Hülle.
     *
     * Zum Rückgabe- und Parametertyp siehe TurnierBetrachterController: Die
     * Signatur erfüllt die abstrakten Vorgaben beider Contao-Fassungen.
     *
     * @param Template     $template Das Template des Inhaltselements
     * @param ContentModel $model    Der Datensatz des Inhaltselements
     * @param Request      $request  Die laufende Anfrage
     *
     * @return Response Die öffnende Hülle
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $GLOBALS['TL_CSS']['ctv'] = 'bundles/contaochesstournamentviewer/css/betrachter.css|static';
        $GLOBALS['TL_JAVASCRIPT']['ctv'] = 'bundles/contaochesstournamentviewer/js/betrachter.js|static';

        $template->kennung = 'ctv-umschlag-'.$model->id;

        return $template->getResponse();
    }
}
