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
     * @param array<string,string[]> $spalten Gewählte Spalten je Liste, in der
     *                              Reihenfolge der Ausgabe; eine Liste ohne
     *                              Eintrag bekommt ihre Vorgabespalten
     * @param int[]    $mannschaften Mannschaftsnummern, auf deren Wettkämpfe
     *                              Paarungen, Ergebnisse und Wettkämpfe
     *                              beschränkt werden; leer für alle
     * @param bool     $rundenkopf  Ob über jeder Runde die Überschrift
     *                              „Runde 1" steht
     */
    public function __construct(
        public readonly array $listen = [],
        public readonly bool $mitSpielern = false,
        public readonly bool $kreuzKurz = false,
        public readonly int $stand = 0,
        public readonly array $runden = [],
        public readonly array $spalten = [],
        public readonly array $mannschaften = [],
        public readonly bool $rundenkopf = true,
    ) {
    }

    /**
     * Baut die Auswahl für ein Element, das genau eine Liste ausgibt.
     *
     * Übernommen wird nur, was zu dieser Liste gehört. Das ist nötig, weil
     * ein Element seine Einstellungen behält, wenn der Redakteur die Liste
     * wechselt: Die Maske blendet „Stand nach Runde" dann zwar aus, der Wert
     * steht aber weiter im Datensatz. Bis Fassung 1.10.0 wirkte er trotzdem —
     * wer von der Mannschaftstabelle nach Runde 3 auf die Mannschaftsliste
     * umstellte, sah darüber weiter „Stand nach Runde 3", und die Liste war
     * tatsächlich zurückgesetzt.
     *
     * @param string   $liste        Schlüssel der Liste; leer ergibt eine leere Auswahl
     * @param bool     $mitSpielern  Wert des Feldes „Spieler mit ausgeben"
     * @param bool     $kreuzKurz    Wert des Feldes „Kreuztabelle kürzen"
     * @param int      $stand        Wert des Feldes „Stand nach Runde"
     * @param int[]    $runden       Wert des Feldes „Angezeigte Runden"
     * @param string[] $spalten       Wert des Feldes „Spalten"
     * @param int[]    $mannschaften  Wert des Feldes „Mannschaften"
     * @param bool     $rundenkopfAus Wert des Feldes „Rundenüberschriften
     *                                ausblenden"
     *
     * @return self Die Auswahl, in der alles Unpassende auf seinen
     *              Ausgangswert zurückgesetzt ist
     */
    public static function fuerListe(
        string $liste,
        bool $mitSpielern = false,
        bool $kreuzKurz = false,
        int $stand = 0,
        array $runden = [],
        array $spalten = [],
        array $mannschaften = [],
        bool $rundenkopfAus = false,
    ): self {
        if ('' === $liste) {
            return new self();
        }

        return new self(
            [$liste],
            $mitSpielern && \in_array($liste, Listen::MIT_SPIELERN, true),
            $kreuzKurz && 'mannschaftskreuztabelle' === $liste,
            \in_array($liste, Listen::MIT_STAND, true) ? max(0, $stand) : 0,
            \in_array($liste, Listen::MIT_RUNDEN, true) ? array_values(array_map('intval', $runden)) : [],
            Spalten::einstellbar($liste) ? [$liste => $spalten] : [],
            \in_array($liste, Listen::MIT_MANNSCHAFTSWAHL, true) ? array_values(array_filter(array_map('intval', $mannschaften))) : [],
            !($rundenkopfAus && \in_array($liste, Listen::MIT_RUNDEN, true)),
        );
    }

    /**
     * Gibt die gewählten Spalten einer Liste zurück.
     *
     * @param string $liste Schlüssel der Liste
     *
     * @return string[] Die Spaltenschlüssel, oder ein leeres Array
     */
    public function spaltenFuer(string $liste): array
    {
        $spalten = $this->spalten[$liste] ?? [];

        return \is_array($spalten) ? $spalten : [];
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

    /**
     * Sagt, ob ein Wettkampf erscheinen soll.
     *
     * Ein Wettkampf erscheint, wenn eine der beiden Mannschaften gewählt ist —
     * wer „Deutschland" wählt, will auch sehen, gegen wen gespielt wurde.
     * Ohne Auswahl erscheinen alle.
     *
     * @param array<string,mixed> $kampf Ein Wettkampf, wie ihn
     *                                   Mannschaftswertung::kaempfe() liefert
     *
     * @return bool Wahr, wenn der Wettkampf ausgegeben wird
     */
    public function zeigtKampf(array $kampf): bool
    {
        if ([] === $this->mannschaften) {
            return true;
        }

        return \in_array((int) ($kampf['heim'] ?? 0), $this->mannschaften, true)
            || \in_array((int) ($kampf['gast'] ?? 0), $this->mannschaften, true);
    }
}
