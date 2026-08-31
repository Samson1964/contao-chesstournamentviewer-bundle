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
     * Schreibt das Ergebnis einer Partie als vollständige Paarung.
     *
     * In einer Ergebnisliste steht die Zahl zwischen zwei Namen; eine
     * einzelne „1" ließe offen, für welche Seite sie gilt. Ausgegeben wird
     * deshalb beides — „1:0", „½:½", „0:1" —, wie es auch Swiss-Chess und
     * chess-results tun.
     *
     * @param float|null $ergebnis Punkte für Weiß, oder null wenn die Partie
     *                             noch nicht gewertet ist
     *
     * @return string Das Ergebnis beider Seiten, leer wenn keines vorliegt
     */
    public static function ergebnisPaar(mixed $ergebnis, string $status = '', float $hoechstwert = 1.0): string
    {
        if (null === $ergebnis || '' === $ergebnis) {
            return '';
        }

        $eigen = (float) $ergebnis;
        $hoechstwert = max(1.0, $hoechstwert);

        // Kampflose Partien werden nicht mit Zahlen geschrieben, sondern mit
        // + und -, wie es Swiss-Chess und chess-results halten: Eine 1:0 ließe
        // eine gespielte Partie vermuten, die es nie gab.
        if (self::istKampflos($status)) {
            return match (true) {
                $eigen >= $hoechstwert => '+:-',
                $eigen <= 0.0 => '-:+',
                default => '½:½',
            };
        }

        // Die Gegenseite ist der Höchstwert minus dem eigenen Ergebnis. Bei
        // zwei Partien je Runde — verbreitet bei Blitzturnieren — läuft ein
        // Rundenergebnis von 0 bis 2, und aus „1½" wird „1½:½".
        return self::punkte($eigen).':'.self::punkte($hoechstwert - $eigen);
    }

    /**
     * Sagt, ob eine Partie kampflos gewertet wurde.
     *
     * Der Status kommt aus der Turnierdatei. „kampflos" steht für eine Partie,
     * zu der einer nicht erschienen ist, „nicht eingesetzt" für ein Brett, das
     * eine Mannschaft nicht besetzt hat — gewertet werden beide gleich.
     *
     * @param string $status Statustext aus dem Paarungssatz
     *
     * @return bool true, wenn nicht gespielt wurde
     */
    public static function istKampflos(string $status): bool
    {
        return \in_array(trim($status), ['kampflos', 'nicht eingesetzt'], true);
    }

    /**
     * Nennt die Wertungszahl, die im Turnier den Ausschlag gibt.
     *
     * Die Turnierwertungszahl wird je nach Einstellung aus Elo oder aus der
     * DWZ/NWZ gebildet. In einer Paarungsliste soll im Spaltenkopf stehen,
     * welche Zahl der Leser vor sich hat, und nicht der Sammelbegriff.
     *
     * @param mixed $turnier Das Turnier; erwartet wird ein Objekt mit der
     *                       Methode kopf(), andere Werte ergeben die
     *                       allgemeine Bezeichnung
     *
     * @return string „Elo", „NWZ" oder „TWZ", bereits maskiert
     */
    public static function wertungsname(mixed $turnier): string
    {
        $einstellung = null;

        if (\is_object($turnier) && method_exists($turnier, 'kopf')) {
            $einstellung = $turnier->kopf('twzErmittlung');
        }

        $schluessel = match ((int) $einstellung) {
            0 => 'elo',
            1 => 'nwz',
            default => 'twz',
        };

        return self::esc($GLOBALS['TL_LANG']['ctv']['wertung'][$schluessel] ?? 'TWZ');
    }

    /**
     * Kürzt die Bezeichnung einer Feinwertung für den Spaltenkopf ab.
     *
     * Namen wie „Sonneborn-Berger" oder „Rating-Differenz (NWZ/TWZ)" machen
     * eine Zahlenspalte doppelt so breit wie nötig. Gibt es keine Kurzform,
     * bleibt der volle Name stehen — lieber breit als unverständlich.
     *
     * @param string $bezeichnung Die volle Bezeichnung aus der Turnierdatei
     *
     * @return string Die Kurzform oder die unveränderte Bezeichnung
     */
    public static function feinwertungKurz(string $bezeichnung): string
    {
        $kurz = $GLOBALS['TL_LANG']['ctv']['feinwertungKurz'][trim($bezeichnung)] ?? null;

        return \is_string($kurz) && '' !== $kurz ? $kurz : $bezeichnung;
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
