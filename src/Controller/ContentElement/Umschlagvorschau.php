<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die Vorschau der beiden Umschlag-Elemente in der Backend-Liste.
 *
 * **Warum es diese Klasse braucht:** Contao stellt in der Liste der
 * Inhaltselemente jedes Element mit seiner echten Frontend-Ausgabe dar
 * (`tl_content::addCteType()` ruft dafür `getContentElement()` auf). Bei
 * einer Hülle ergibt das kaputtes HTML: „Umschlag Anfang" öffnet ein `div`,
 * das es nicht schließt, und „Umschlag Ende" gibt ein `</div>` aus, zu dem
 * in seiner eigenen Zeile nichts gehört. Das überzählige `</div>` schließt
 * dann nicht die Vorschau, sondern die Liste selbst — alle Elemente
 * dahinter landen außerhalb der `ul`. Sichtbar wird das daran, dass die
 * folgenden Elemente nach links rücken und sich nicht mehr ziehen lassen:
 * Was außerhalb der Liste steht, kennt die Sortierung nicht.
 *
 * Contao löst das bei seinen eigenen Hüllen ebenso: Akkordeon und Slider
 * schalten im Backend auf das Template `be_wildcard` um und geben dort nur
 * einen Hinweistext aus.
 *
 * @see \Contao\ContentAccordionStart::compile()
 */
final class Umschlagvorschau
{
    /**
     * Sagt, ob die laufende Anfrage aus dem Backend kommt.
     *
     * Gefragt wird der Gültigkeitsbereich der Anfrage, nicht die alte
     * Konstante `TL_MODE`: Diese gibt es in Contao 5 nicht mehr.
     *
     * @param Request|null $request Die laufende Anfrage; ohne Anfrage gilt
     *                              die Ausgabe als Frontend-Ausgabe
     *
     * @return bool Wahr, wenn die Anfrage im Backend läuft
     */
    public static function istBackend(?Request $request): bool
    {
        if (null === $request) {
            return false;
        }

        return System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request);
    }

    /**
     * Baut den Hinweistext zu einem Elementtyp.
     *
     * Genommen wird der Name, den das Element im Elementwähler trägt, in
     * Großbuchstaben und zwischen Rautenzeichen — so schreibt Contao die
     * Hinweise seiner eigenen Platzhalter. Fehlt die Sprachdatei, steht dort
     * der Typenschlüssel selbst; das ist immer noch besser als ein leerer
     * Kasten.
     *
     * @param string $typ Der Elementtyp, etwa `chesstournamentviewerStart`
     *
     * @return string Der fertige Hinweistext
     */
    public static function hinweis(string $typ): string
    {
        $eintrag = $GLOBALS['TL_LANG']['CTE'][$typ] ?? $typ;
        $name = \is_array($eintrag) ? ($eintrag[0] ?? $typ) : $eintrag;

        return '### '.mb_strtoupper((string) $name).' ###';
    }

    /**
     * Erzeugt die Vorschau einer Hülle für die Backend-Liste.
     *
     * Ausgegeben wird der graue Hinweiskasten, den Contao für Elemente ohne
     * eigene Vorschau verwendet. Das Ergebnis ist in sich geschlossenes
     * HTML und kann die Liste darum nicht zerreißen.
     *
     * @param string $hinweis Der Text im Kasten, üblicherweise in der Form
     *                        „### … ###"
     * @param string $titel   Eine Überschrift über dem Kasten; leer lassen,
     *                        wenn das Element keine führt
     *
     * @return Response Die fertige Vorschau
     */
    public static function antwort(string $hinweis, string $titel = ''): Response
    {
        $template = new BackendTemplate('be_wildcard');
        $template->wildcard = $hinweis;
        $template->title = $titel;

        return new Response($template->parse());
    }
}
