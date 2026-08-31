<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Baut die Fortschrittstabelle eines Turniers.
 *
 * Die Fortschrittstabelle zeigt je Teilnehmer eine Zeile mit einer Spalte je
 * Runde. In jeder Zelle stehen Gegner, Farbe, Ergebnis und der Punktestand
 * nach dieser Runde. Sie ist die Liste, aus der sich der Verlauf eines
 * Turniers ablesen lässt — anders als die Kreuztabelle bleibt sie auch bei
 * vielen Teilnehmern schmal.
 *
 * Die Klasse rechnet ausschließlich mit den Paarungen. Der laufende
 * Punktestand wird aufsummiert und nicht aus den Karteikarten übernommen:
 * Diese führen nur den Endstand, und der ist in manchen Dateien älter als die
 * eingetragenen Ergebnisse.
 */
final class Fortschritt
{
    /**
     * Erzeugt die Zeilen der Fortschrittstabelle.
     *
     * Die Reihenfolge folgt der Rangliste, weil die Tabelle üblicherweise
     * zusammen mit dieser gelesen wird. Teilnehmer ohne jede Paarung — etwa
     * nachträglich angelegte — erscheinen mit leeren Rundenzellen, damit die
     * Zeilenzahl zur Rangliste passt.
     *
     * Sonderpunkte (Zusatzpunkte außerhalb der Partien) werden bewusst nicht
     * eingerechnet: Sie gehören keiner Runde an, und ihr Einrechnen würde die
     * Zwischenstände verfälschen. Die Endpunktzahl der Rangliste kann deshalb
     * um diesen Betrag über dem letzten Zwischenstand liegen.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array{spieler:array<string,mixed>,runden:array<int,array<string,mixed>>,summe:float}>
     *         Je Teilnehmer eine Zeile; `runden` ist nach Rundennummer
     *         indiziert und enthält nur ausgeloste Runden
     */
    public static function zeilen(Turnier $turnier): array
    {
        $paarungen = $turnier->getPaarungen();
        $spieler = $turnier->getSpieler();
        $runden = array_keys($turnier->getRunden());
        sort($runden);

        $zeilen = [];

        foreach ($turnier->getRangliste() as $teilnehmer) {
            $tnr = (int) $teilnehmer['tnr'];
            $stand = 0.0;
            $zellen = [];

            foreach ($runden as $runde) {
                $satz = $paarungen[$tnr][$runde] ?? null;

                if (null === $satz) {
                    $zellen[$runde] = self::leereZelle($stand);

                    continue;
                }

                $gegner = (int) $satz['gegner'];

                // Spielfrei liegt vor, wenn kein Gegner eingetragen ist oder
                // der eingetragene Gegner der Platzhalterteilnehmer ist, den
                // manche Programme bei ungerader Teilnehmerzahl mitführen.
                $spielfrei = 0 === $gegner
                    || !isset($spieler[$gegner])
                    || ($spieler[$gegner]['spielfrei'] ?? false);

                $stand += (float) ($satz['ergebnis'] ?? 0.0);

                $zellen[$runde] = [
                    'gegner' => $spielfrei ? null : $gegner,
                    'gegnerName' => $spielfrei ? '' : (string) ($satz['gegnerName'] ?? ''),
                    'farbe' => self::farbkuerzel((string) ($satz['farbe'] ?? '')),
                    'ergebnis' => $satz['ergebnis'],
                    'ergebnisText' => (string) ($satz['ergebnisText'] ?? ''),
                    'spielfrei' => $spielfrei,
                    'stand' => $stand,
                    'leer' => false,
                ];
            }

            $zeilen[] = [
                'spieler' => $teilnehmer,
                'runden' => $zellen,
                'summe' => $stand,
            ];
        }

        return $zeilen;
    }

    /**
     * Liefert eine Zelle für eine Runde ohne Paarung.
     *
     * Das kommt vor, wenn ein Teilnehmer erst später ins Turnier kam oder
     * wenn eine Runde nur teilweise ausgelost ist. Der Punktestand wird
     * unverändert weitergereicht, damit die Spalte lesbar bleibt.
     *
     * @param float $stand Punktestand, der vor dieser Runde erreicht war
     *
     * @return array<string,mixed> Eine als leer gekennzeichnete Zelle
     */
    private static function leereZelle(float $stand): array
    {
        return [
            'gegner' => null,
            'gegnerName' => '',
            'farbe' => '',
            'ergebnis' => null,
            'ergebnisText' => '',
            'spielfrei' => false,
            'stand' => $stand,
            'leer' => true,
        ];
    }

    /**
     * Kürzt die Farbbezeichnung auf einen Buchstaben.
     *
     * In der Fortschrittstabelle ist neben Gegnernummer und Ergebnis kein
     * Platz für ausgeschriebene Farben. Die Bezeichnungen der Formate sind
     * uneinheitlich („Weiß", „Weiss", „Heim"), deshalb wird nur der erste
     * Buchstabe ausgewertet.
     *
     * @param string $farbe Farbbezeichnung, wie sie das Format liefert
     *
     * @return string „w" für Weiß, „s" für Schwarz, sonst eine leere Zeichenkette
     */
    private static function farbkuerzel(string $farbe): string
    {
        $erster = mb_strtolower(mb_substr(trim($farbe), 0, 1));

        return match ($erster) {
            'w' => 'w',
            's' => 's',
            default => '',
        };
    }
}
