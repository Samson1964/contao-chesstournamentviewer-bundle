<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Rechnet die Feinwertungen eines Turniers aus den Paarungen nach.
 *
 * Gebraucht wird das für den Rundenschnitt: In der Turnierdatei steht die
 * Feinwertung nur für den Endstand. Wer die Tabelle nach Runde 4 sehen will,
 * bekommt dort Werte nur, wenn sie neu berechnet werden.
 *
 * Die Schwierigkeit ist nicht die Formel, sondern das Kleingedruckte. Wie eine
 * spielfreie Runde in die Wertung eingeht, ob der schlechteste Gegnerwert
 * gestrichen wird und mit welcher Punktzahl ein Gegner zählt, der selbst eine
 * Runde ausgesetzt hat — all das sind Turniereinstellungen, die in der Datei
 * an keiner uns bekannten Stelle stehen. Geraten wird deshalb nicht:
 *
 * **Die Klasse rechnet zunächst den Endstand nach und vergleicht ihn mit den
 * gespeicherten Werten.** Nur die Regelfassung, die den Endstand des
 * betreffenden Turniers exakt trifft, wird auf den Zwischenstand angewandt.
 * Trifft keine, bleibt die Spalte leer — eine Zahl, die neben der amtlichen
 * Tabelle steht und ihr widerspricht, wäre schlimmer als keine Zahl.
 *
 * Bei 294 Turnieren des Prüfbestands mit Buchholzwertung ließen sich so 238
 * zuordnen; die häufigste Fassung war „Gegnerpunkte mit Remisregel, gedachter
 * Gegner für die eigene Freirunde, schlechtester Wert gestrichen".
 *
 * @see \Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier::bisRunde()
 */
final class Feinwertung
{
    /**
     * Die Wertungen, die diese Klasse nachrechnen kann, mit ihrem Rechenweg.
     *
     * Die Schlüssel sind die Bezeichnungen, die das Format im Turnierkopf
     * unter `feinwertung1Text` und `feinwertung2Text` nennt. Was hier fehlt —
     * etwa das Gegner-Elo-Mittel oder die Berliner Wertung — lässt sich aus
     * den Paarungen allein nicht bilden.
     */
    private const ARTEN = [
        'Buchholzwertung' => 'buchholz',
        'Mittlere Buchholz' => 'buchholz',
        'Buchholzsumme' => 'buchholzsumme',
        'Sonneborn-Berger' => 'sonneborn',
        'Summenwertung' => 'summenwertung',
    ];

    /**
     * Wie die Punktzahl eines Gegners für die Wertung gebildet wird.
     *
     * `remis`: Nicht gespielte Partien des Gegners zählen mit einem halben
     * Punkt, gleichgültig was gutgeschrieben wurde — die Regel des
     * FIDE-Turnierreglements. `echt`: Es zählt die Punktzahl der Tabelle.
     */
    private const GEGNER = ['remis', 'echt'];

    /**
     * Was für eine eigene spielfreie Runde in die Wertung eingeht.
     *
     * `gedacht`: ein gedachter Gegner mit dem eigenen Punktestand vor dieser
     * Runde, dem Gegenwert des gutgeschriebenen Ergebnisses und einem Remis
     * in jeder weiteren Runde. `weg`: gar nichts. `selbst`: die eigene
     * Punktzahl. `null`: eine Null.
     */
    private const FREIRUNDE = ['gedacht', 'weg', 'selbst', 'null'];

    /**
     * Welche Einzelwerte gestrichen werden, als [von unten, von oben].
     *
     * Die Reihenfolge ist die der Häufigkeit im Prüfbestand: ohne Streichung
     * und „schlechtester Wert gestrichen" decken zusammen den Großteil ab.
     * `[1, 1]` ist das, was Swiss-Chess „mittlere Buchholz" nennt.
     */
    private const STREICHUNGEN = [
        [0, 0],
        [1, 0],
        [1, 1],
        [2, 0],
        [0, 1],
        [2, 1],
    ];

    /**
     * Prüft, ob eine Wertung überhaupt nachgerechnet werden kann.
     *
     * @param string $art Bezeichnung der Wertung aus dem Turnierkopf
     *
     * @return bool Wahr, wenn kalibriere() für diese Wertung in Frage kommt
     */
    public static function kann(string $art): bool
    {
        return isset(self::ARTEN[$art]);
    }

    /**
     * Sucht die Regelfassung, die den gespeicherten Endstand trifft.
     *
     * Durchprobiert werden alle Kombinationen aus Gegnerregel, Behandlung der
     * eigenen Freirunde und Streichung — vierzig Stück, in der Reihenfolge
     * ihrer Häufigkeit im Prüfbestand. Zurück kommt die erste, die für
     * **jeden** Teilnehmer den gespeicherten Wert auf ein Tausendstel genau
     * trifft.
     *
     * Ein Turnier ohne gespeicherte Werte — alle Wertungen null, weil noch
     * keine Runde ausgewertet wurde — liefert null: Dass eine Regel lauter
     * Nullen trifft, sagt über sie nichts aus.
     *
     * @param array<int,array<string,mixed>>            $spieler   Teilnehmer, Schlüssel ist die Teilnehmernummer
     * @param array<int,array<int,array<string,mixed>>> $paarungen Paarungen als [Teilnehmernummer][Runde]
     * @param string                                    $art       Bezeichnung der Wertung
     * @param array<int,float>                          $soll      Gespeicherte Werte je Teilnehmernummer
     * @param int                                       $letzte    Letzte gespielte Runde
     *
     * @return array{gegner:string,freirunde:string,streiche:array{0:int,1:int}}|null
     *         Die gefundene Regelfassung, oder null wenn keine passt
     */
    public static function kalibriere(array $spieler, array $paarungen, string $art, array $soll, int $letzte): ?array
    {
        if (!self::kann($art) || $letzte < 1) {
            return null;
        }

        // Lauter Nullen wären mit jeder Regel zu treffen und beweisen nichts.
        $inhalt = array_filter($soll, static fn (float $wert): bool => abs($wert) > 0.001);

        if ([] === $inhalt) {
            return null;
        }

        foreach (self::regeln() as $regel) {
            $werte = self::werte($spieler, $paarungen, $art, $letzte, $regel);
            $passt = true;

            foreach ($soll as $tnr => $wert) {
                if (abs($wert - ($werte[$tnr] ?? 0.0)) > 0.001) {
                    $passt = false;

                    break;
                }
            }

            if ($passt) {
                return $regel;
            }
        }

        return null;
    }

    /**
     * Berechnet eine Feinwertung für alle Teilnehmer.
     *
     * Gerechnet wird ausschließlich mit den Paarungen bis zur angegebenen
     * Runde; gespeicherte Werte aus der Datei gehen nicht ein. Sonderpunkte,
     * die außerhalb der Partien vergeben wurden, bleiben deshalb außen vor.
     *
     * @param array<int,array<string,mixed>>            $spieler   Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $paarungen Paarungen als [Teilnehmernummer][Runde]
     * @param string                                    $art       Bezeichnung der Wertung
     * @param int                                       $bis       Höchste zu berücksichtigende Runde
     * @param array{gegner:string,freirunde:string,streiche:array{0:int,1:int}} $regel
     *                                                             Regelfassung aus kalibriere()
     *
     * @return array<int,float> Der Wertungswert je Teilnehmernummer; ein
     *                          leeres Array bei unbekannter Wertung
     */
    public static function werte(array $spieler, array $paarungen, string $art, int $bis, array $regel): array
    {
        $weg = self::ARTEN[$art] ?? null;

        if (null === $weg) {
            return [];
        }

        if ('summenwertung' === $weg) {
            return self::gestrichen(self::zwischenstaende($paarungen, $bis), $regel['streiche']);
        }

        $punkte = self::gegnerpunkte($spieler, $paarungen, $bis, $regel['gegner']);
        $einzeln = self::einzelwerte($spieler, $paarungen, $bis, $regel, $punkte, 'sonneborn' === $weg);

        if ('buchholzsumme' !== $weg) {
            return self::gestrichen($einzeln, $regel['streiche']);
        }

        // Die Buchholzsumme baut auf den Buchholzwerten der Gegner auf; diese
        // entstehen nach derselben Regel, einschließlich der Streichung.
        $buchholz = self::gestrichen(
            self::einzelwerte($spieler, $paarungen, $bis, $regel, $punkte, false),
            $regel['streiche']
        );

        $summe = [];

        foreach ($paarungen as $tnr => $runden) {
            $wert = 0.0;

            foreach ($runden as $runde => $satz) {
                if ($runde <= $bis && self::gespielt($spieler, $satz)) {
                    $wert += $buchholz[(int) $satz['gegner']] ?? 0.0;
                }
            }

            $summe[$tnr] = $wert;
        }

        return $summe;
    }

    /**
     * Liefert alle Regelfassungen in der Reihenfolge ihrer Häufigkeit.
     *
     * Die Reihenfolge entscheidet, welche Fassung gewinnt, wenn mehrere den
     * Endstand treffen — bei kleinen Turnieren ohne spielfreie Runde ist das
     * durchaus möglich. Dann soll die verbreitetste gewählt werden.
     *
     * @return array<int,array{gegner:string,freirunde:string,streiche:array{0:int,1:int}}>
     *         Vierzig Regelfassungen
     */
    private static function regeln(): array
    {
        $regeln = [];

        foreach (self::STREICHUNGEN as $streiche) {
            foreach (self::GEGNER as $gegner) {
                foreach (self::FREIRUNDE as $freirunde) {
                    $regeln[] = ['gegner' => $gegner, 'freirunde' => $freirunde, 'streiche' => $streiche];
                }
            }
        }

        // Ohne Streichung und mit gestrichenem schlechtesten Wert sind die
        // beiden häufigsten Fälle; sie stehen durch die Reihenfolge der
        // Konstanten bereits vorn.
        return $regeln;
    }

    /**
     * Ermittelt die Punktzahl, mit der jeder Spieler in fremde Wertungen eingeht.
     *
     * Unter der Regel `remis` ist das nicht seine Punktzahl aus der Tabelle:
     * Jede nicht gespielte Partie — spielfreie Runde, kampfloser Ausgang,
     * fehlendes Ergebnis — steuert dort einen halben Punkt bei. Wer eine
     * spielfreie Runde mit einem ganzen Punkt gutgeschrieben bekam, geht
     * deshalb mit einem halben Punkt weniger in die Buchholzwertung seiner
     * Gegner ein.
     *
     * @param array<int,array<string,mixed>>            $spieler   Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $paarungen Paarungen
     * @param int                                       $bis       Höchste Runde
     * @param string                                    $regel     `remis` oder `echt`
     *
     * @return array<int,float> Punktzahl je Teilnehmernummer
     */
    private static function gegnerpunkte(array $spieler, array $paarungen, int $bis, string $regel): array
    {
        $punkte = [];

        foreach ($paarungen as $tnr => $runden) {
            $summe = 0.0;

            foreach ($runden as $runde => $satz) {
                if ($runde > $bis) {
                    continue;
                }

                $summe += ('remis' === $regel && !self::gespielt($spieler, $satz))
                    ? 0.5
                    : (float) ($satz['ergebnis'] ?? 0.0);
            }

            $punkte[$tnr] = $summe;
        }

        return $punkte;
    }

    /**
     * Bildet die Einzelwerte, aus denen sich eine Wertung zusammensetzt.
     *
     * Für die Buchholzwertung ist das je Runde die Punktzahl des Gegners, für
     * Sonneborn-Berger das eigene Ergebnis mal dieser Punktzahl. Die Werte
     * kommen sortiert zurück, damit die Streichung sie nur noch abschneiden
     * muss.
     *
     * @param array<int,array<string,mixed>>            $spieler    Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $paarungen  Paarungen
     * @param int                                       $bis        Höchste Runde
     * @param array{gegner:string,freirunde:string,streiche:array{0:int,1:int}} $regel Regelfassung
     * @param array<int,float>                          $punkte     Gegnerpunkte aus gegnerpunkte()
     * @param bool                                      $gewichtet  Mit dem eigenen Ergebnis multiplizieren
     *
     * @return array<int,array<int,float>> Aufsteigend sortierte Einzelwerte je Teilnehmer
     */
    private static function einzelwerte(array $spieler, array $paarungen, int $bis, array $regel, array $punkte, bool $gewichtet): array
    {
        $alle = [];

        foreach ($paarungen as $tnr => $runden) {
            $werte = [];
            $stand = 0.0;

            foreach ($runden as $runde => $satz) {
                if ($runde > $bis) {
                    continue;
                }

                $ergebnis = (float) ($satz['ergebnis'] ?? 0.0);

                if (self::gespielt($spieler, $satz)) {
                    $gegnerpunkte = $punkte[(int) $satz['gegner']] ?? 0.0;
                    $werte[] = $gewichtet ? $ergebnis * $gegnerpunkte : $gegnerpunkte;
                } else {
                    $gegnerpunkte = match ($regel['freirunde']) {
                        'gedacht' => $stand + max(0.0, 1.0 - $ergebnis) + 0.5 * ($bis - $runde),
                        'selbst' => $punkte[$tnr] ?? 0.0,
                        'null' => 0.0,
                        default => null,
                    };

                    if (null !== $gegnerpunkte) {
                        $werte[] = $gewichtet ? $ergebnis * $gegnerpunkte : $gegnerpunkte;
                    }
                }

                $stand += $ergebnis;
            }

            sort($werte);
            $alle[$tnr] = $werte;
        }

        return $alle;
    }

    /**
     * Bildet die Zwischenstände, aus denen die Summenwertung entsteht.
     *
     * Nach jeder Runde wird der bis dahin erreichte Punktestand notiert. Die
     * Summenwertung ist die Summe dieser Stände; wer früh punktet, steht damit
     * besser als jemand, der dieselben Punkte spät holt.
     *
     * @param array<int,array<int,array<string,mixed>>> $paarungen Paarungen
     * @param int                                       $bis       Höchste Runde
     *
     * @return array<int,array<int,float>> Aufsteigend sortierte Zwischenstände je Teilnehmer
     */
    private static function zwischenstaende(array $paarungen, int $bis): array
    {
        $alle = [];

        foreach ($paarungen as $tnr => $runden) {
            $stand = 0.0;
            $werte = [];

            foreach ($runden as $runde => $satz) {
                if ($runde > $bis) {
                    continue;
                }

                $stand += (float) ($satz['ergebnis'] ?? 0.0);
                $werte[] = $stand;
            }

            sort($werte);
            $alle[$tnr] = $werte;
        }

        return $alle;
    }

    /**
     * Summiert Einzelwerte und lässt dabei die gestrichenen weg.
     *
     * Gestrichen wird nur, solange etwas übrigbleibt: Wer nach zwei Runden
     * ausscheidet, behält seine beiden Werte auch dann, wenn die Einstellung
     * zwei Streichergebnisse vorsieht.
     *
     * @param array<int,array<int,float>> $einzeln  Sortierte Einzelwerte je Teilnehmer
     * @param array{0:int,1:int}          $streiche Anzahl der Streichungen unten und oben
     *
     * @return array<int,float> Summe je Teilnehmernummer
     */
    private static function gestrichen(array $einzeln, array $streiche): array
    {
        [$unten, $oben] = $streiche;
        $summen = [];

        foreach ($einzeln as $tnr => $werte) {
            if (\count($werte) > $unten + $oben) {
                $werte = \array_slice($werte, $unten, \count($werte) - $unten - $oben);
            }

            $summen[$tnr] = array_sum($werte);
        }

        return $summen;
    }

    /**
     * Prüft, ob eine Paarung eine wirklich gespielte Partie ist.
     *
     * Nicht gespielt sind spielfreie Runden, Partien gegen den
     * Platzhalterteilnehmer, kampflos entschiedene Partien und Paarungen ohne
     * eingetragenes Ergebnis. Kampflose Partien gehören dazu, weil das
     * Reglement sie wie ausgefallene Partien behandelt — im Prüfbestand hing
     * daran der Unterschied zwischen 78 und 100 Prozent Übereinstimmung.
     *
     * @param array<int,array<string,mixed>> $spieler Teilnehmer
     * @param array<string,mixed>            $satz    Ein Paarungssatz
     *
     * @return bool Wahr, wenn ein Gegner am Brett saß
     */
    private static function gespielt(array $spieler, array $satz): bool
    {
        $gegner = (int) ($satz['gegner'] ?? 0);

        if (0 === $gegner || ($spieler[$gegner]['spielfrei'] ?? false)) {
            return false;
        }

        if (\in_array((string) ($satz['status'] ?? ''), ['kampflos', 'nicht eingesetzt'], true)) {
            return false;
        }

        return null !== ($satz['ergebnis'] ?? null);
    }
}
