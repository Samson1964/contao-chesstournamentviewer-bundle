<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

/**
 * Die Einstellungen eines Inhaltselements in einem Wert zusammengefasst.
 *
 * Die Klasse ersetzt die wachsende Reihe von Wahrheitswerten, mit der der
 * Listenbauer früher aufgerufen wurde. Sie kennt Contao nicht: Der Controller
 * liest den Datensatz aus und übergibt das Ergebnis, die Testfälle bauen sich
 * dieselbe Auswahl von Hand.
 */
final class Auswahl
{
    /**
     * Nimmt die Einstellungen entgegen.
     *
     * @param string[] $listen      Schlüssel der gewählten Listen; ihre
     *                              Reihenfolge bestimmt die Reihenfolge der Reiter
     * @param bool     $mitSpielern Ob Mannschaftslisten die Aufstellungen und
     *                              Einzelpartien mit ausgeben
     * @param bool     $kreuzKurz   Ob die Kreuztabelle der Mannschaften nur die
     *                              eigenen Brettpunkte zeigt
     * @param int      $stand       Runde, nach der der Stand gezeigt wird; 0 für
     *                              das ganze Turnier
     * @param int[]    $runden      Rundennummern, auf die Paarungs-, Ergebnis-
     *                              und Wettkampfliste beschränkt werden; leer
     *                              für alle Runden
     */
    public function __construct(
        public readonly array $listen = [],
        public readonly bool $mitSpielern = false,
        public readonly bool $kreuzKurz = false,
        public readonly int $stand = 0,
        public readonly array $runden = [],
    ) {
    }

    /**
     * Sagt, ob eine Runde in den Rundenlisten erscheinen soll.
     *
     * Ohne Auswahl erscheinen alle Runden — ein leeres Kästchenfeld heißt
     * „keine Einschränkung" und nicht „nichts anzeigen". Wer wirklich nichts
     * sehen will, wählt die Liste ab.
     *
     * @param int $runde Rundennummer ab 1
     *
     * @return bool Wahr, wenn die Runde ausgegeben wird
     */
    public function zeigtRunde(int $runde): bool
    {
        return [] === $this->runden || \in_array($runde, $this->runden, true);
    }
}
