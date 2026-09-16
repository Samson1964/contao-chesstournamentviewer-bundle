<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\EventListener;

use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\InserttagAusgabe;

/**
 * Setzt den Inserttag `{{ctv::kennung}}` über die Schnittstelle ab Contao 5 um.
 *
 * Die benutzten Klassen gibt es erst ab Contao 5.2. Unter Contao 4.13 wird
 * diese Klasse deshalb nie geladen: Die DI-Erweiterung meldet dort
 * stattdessen den Hook-Rückruf an. Angemeldet wird der Dienst über den Tag
 * `contao.insert_tag`, nicht über das Attribut — so steht die Entscheidung an
 * einer Stelle, und die Datei bleibt auch unter Contao 4.13 lesbar.
 */
class InserttagResolver
{
    /**
     * Erzeugt den Rückruf.
     *
     * @param InserttagAusgabe $ausgabe Erzeugt die Ausgabe zur Kennung
     */
    public function __construct(private readonly InserttagAusgabe $ausgabe)
    {
    }

    /**
     * Ersetzt den Inserttag.
     *
     * Der erste Parameter hinter `ctv::` ist die Kennung oder die ID der
     * Turnierausgabe. Das Ergebnis ist fertiges HTML und darf deshalb nicht
     * noch einmal maskiert werden — dafür steht `OutputType::html`.
     *
     * @param ResolvedInsertTag $tag Der aufgelöste Inserttag samt Parametern
     *
     * @return InsertTagResult Das HTML der Ausgabe; leer, wenn es die
     *                         Turnierausgabe nicht gibt
     */
    public function __invoke(ResolvedInsertTag $tag): InsertTagResult
    {
        return new InsertTagResult(
            $this->ausgabe->html((string) ($tag->getParameters()->get(0) ?? '')),
            OutputType::html
        );
    }
}
