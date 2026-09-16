<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\EventListener;

use Schachbulle\ContaoChesstournamentviewerBundle\Liste\InserttagAusgabe;

/**
 * Setzt den Inserttag `{{ctv::kennung}}` über den alten Hook um.
 *
 * Diesen Weg gibt es in jeder Contao-Fassung. Ab Contao 5.2 ist er veraltet
 * und wird in Contao 6 entfallen; dort greift stattdessen die Klasse
 * InserttagResolver mit dem Attribut `AsInsertTag`. Welcher der beiden
 * Dienste angemeldet wird, entscheidet die DI-Erweiterung anhand der
 * vorhandenen Contao-Fassung — beide zugleich würden die Ausgabe verdoppeln.
 */
class InserttagHookListener
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
     * Ersetzt den Inserttag, wenn er zu diesem Bundle gehört.
     *
     * @param string $tag Der Inhalt des Inserttags ohne die Klammern,
     *                    also etwa `ctv::olympiade-ger-r1`
     *
     * @return string|false Das HTML der Ausgabe, oder false, wenn der Tag
     *                      einem anderen Bundle gehört — dann macht Contao
     *                      mit den übrigen Rückrufen weiter
     */
    public function __invoke(string $tag): string|false
    {
        $teile = explode('::', $tag, 2);

        if ('ctv' !== strtolower(trim($teile[0]))) {
            return false;
        }

        return $this->ausgabe->html($teile[1] ?? '');
    }
}
