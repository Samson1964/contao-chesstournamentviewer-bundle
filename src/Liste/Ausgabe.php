<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Contao\StringUtil;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;

/**
 * Kleine Formatierungshilfen für die Templates.
 *
 * Die Templates sollen Zahlen ausgeben und nicht formatieren. Alles, was in
 * mehreren Templates gleich aussehen muss — halbe Punkte, Bilanzen,
 * Maskierung —, steht deshalb hier. Wer ein Template überschreibt, bekommt
 * diese Hilfen mit und muss die Schreibweise nicht nachbauen.
 */
final class Ausgabe
{
    /**
     * Schreibt eine Punktzahl in der im Schach üblichen Form.
     *
     * Halbe Punkte erscheinen als ½, ganze ohne Nachkommastelle. Der Wert
     * null steht für „noch kein Ergebnis" und ergibt eine leere Zeichenkette —
     * eine Null stünde dort für ein verlorenes Spiel und wäre falsch.
     *
     * Die Schreibweise selbst kommt aus der Mannschaftswertung, damit sie im
     * ganzen Bundle nur einmal festgelegt ist.
     *
     * @param float|int|string|null $wert Die Punktzahl
     *
     * @return string Die Punktzahl als Text, etwa „3½"
     */
    public static function punkte(mixed $wert): string
    {
        if (null === $wert || '' === $wert) {
            return '';
        }

        return Mannschaftswertung::punkteText((float) $wert);
    }

    /**
     * Gibt eine Zahl aus, unterdrückt dabei aber die Null.
     *
     * In Ranglisten und Teilnehmerlisten steht bei fehlenden Wertungszahlen
     * eine 0 in der Datei. Eine Spalte voller Nullen liest sich schlechter
     * als eine leere.
     *
     * @param int|float|string|null $wert Die Zahl
     *
     * @return string Die Zahl als Text, oder eine leere Zeichenkette bei 0
     */
    public static function zahl(mixed $wert): string
    {
        return empty($wert) ? '' : (string) $wert;
    }

    /**
     * Setzt die Bilanz eines Teilnehmers zusammen.
     *
     * @param array<string,mixed> $spieler Der Teilnehmerdatensatz
     *
     * @return string Siege, Remisen und Niederlagen als „5/1/0"
     */
    public static function bilanz(array $spieler): string
    {
        return sprintf(
            '%d/%d/%d',
            (int) ($spieler['siege'] ?? 0),
            (int) ($spieler['remis'] ?? 0),
            (int) ($spieler['niederlagen'] ?? 0)
        );
    }

    /**
     * Setzt Titel und Namen eines Teilnehmers zusammen.
     *
     * @param array<string,mixed> $spieler Der Teilnehmerdatensatz
     *
     * @return string Name mit vorangestelltem Titel, unmaskiert
     */
    public static function name(array $spieler): string
    {
        $titel = trim((string) ($spieler['titel'] ?? ''));
        $name = trim((string) ($spieler['name'] ?? ''));

        return '' === $titel ? $name : $titel.' '.$name;
    }

    /**
     * Maskiert einen Wert für die Ausgabe im HTML.
     *
     * Turnierdateien kommen nicht durch die Eingabeprüfung von Contao; ihre
     * Inhalte müssen deshalb beim Ausgeben maskiert werden. Namen mit
     * Sonderzeichen — „Schmidt,Hans & Sohn" — gibt es durchaus.
     *
     * @param mixed $wert Der auszugebende Wert
     *
     * @return string Der maskierte Wert
     */
    public static function esc(mixed $wert): string
    {
        return StringUtil::specialchars((string) $wert);
    }

    /**
     * Gibt eine Rundenüberschrift aus.
     *
     * @param int $runde Die Rundennummer
     *
     * @return string Die Überschrift, etwa „Runde 3"
     */
    public static function runde(int $runde): string
    {
        return sprintf($GLOBALS['TL_LANG']['ctv']['runde'] ?? 'Runde %s', $runde);
    }

    /**
     * Gibt eine Spaltenbeschriftung aus der Sprachdatei zurück.
     *
     * Fehlt die Übersetzung, erscheint der Schlüssel. Das ist unschön, aber
     * besser als eine namenlose Spalte.
     *
     * @param string $schluessel Schlüssel unter TL_LANG['ctv']['spalte']
     *
     * @return string Die Beschriftung, bereits maskiert
     */
    public static function spalte(string $schluessel): string
    {
        return self::esc($GLOBALS['TL_LANG']['ctv']['spalte'][$schluessel] ?? $schluessel);
    }

    /**
     * Gibt ein Wort aus der Sprachdatei zurück.
     *
     * @param string $schluessel Schlüssel unter TL_LANG['ctv']
     * @param string $standard   Rückfalltext, wenn die Übersetzung fehlt
     *
     * @return string Der Text, bereits maskiert
     */
    public static function wort(string $schluessel, string $standard = ''): string
    {
        $wert = $GLOBALS['TL_LANG']['ctv'][$schluessel] ?? $standard;

        return self::esc(\is_string($wert) ? $wert : $standard);
    }
}
