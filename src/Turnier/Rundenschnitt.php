<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Versetzt ein Turnier auf den Stand nach einer bestimmten Runde zurück.
 *
 * Das Ergebnis ist ein vollwertiges Turnier: Punkte, Rangliste, Kreuztabelle
 * und Mannschaftswertung stehen so da, wie sie nach der gewählten Runde
 * standen. Alles, was danach kommt, ist entfernt und nicht bloß ausgeblendet
 * — deshalb brauchen weder der Listenbauer noch die Templates etwas von einem
 * Schnitt zu wissen. Fortschrittstabelle und Mannschaftstabelle rechnen
 * ohnehin aus den Paarungen und stimmen dadurch von selbst.
 *
 * Was der Schnitt **nicht** kann, steht ausdrücklich in den Hinweisen des
 * Turniers: Sonderpunkte lassen sich keiner Runde zuordnen und fallen weg,
 * und eine Feinwertung erscheint nur, wenn sich ihre Rechenweise am Endstand
 * der Datei überprüfen ließ.
 *
 * @see \Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Feinwertung
 */
final class Rundenschnitt
{
    /**
     * Liefert das Turnier im Stand nach der angegebenen Runde.
     *
     * Eine Runde jenseits der letzten gespielten liefert das unveränderte
     * Turnier. Die letzte Runde selbst wird dagegen wirklich nachgerechnet,
     * obwohl das Ergebnis dasselbe sein müsste — genau daran lässt sich die
     * Nachrechnung überprüfen, und das ist mehr wert als die eingesparte
     * Rechenzeit.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param int     $runde   Die letzte zu berücksichtigende Runde, ab 1
     *
     * @return Turnier Das zurückversetzte Turnier, oder das übergebene
     */
    public static function bis(Turnier $turnier, int $runde): Turnier
    {
        if ($runde < 1 || $runde > $turnier->getLetzteRunde()) {
            return $turnier;
        }

        // Ein zweiter Schnitt auf dieselbe Runde ändert nichts, würde aber die
        // Hinweise ein zweites Mal anhängen. Controller und Listenbauer dürfen
        // deshalb beide schneiden, ohne sich abzustimmen.
        if ($runde === (int) $turnier->kopf('standNachRunde', 0)) {
            return $turnier;
        }

        $spieler = self::spieler($turnier, $runde);
        $rangliste = self::rangliste($spieler);

        return new Turnier(
            $turnier->getFormat(),
            self::kopf($turnier, $runde, $spieler),
            $spieler,
            $turnier->getMannschaften(),
            self::kuerze($turnier->getPaarungen(), $runde),
            $rangliste,
            self::kuerzeRunden($turnier->getRunden(), $runde),
            Kreuztabelle::baue($rangliste, $turnier->getPaarungen(), $runde),
            self::hinweise($turnier, $runde, $spieler),
            self::kuerze($turnier->getMannschaftspaarungen(), $runde),
        );
    }

    /**
     * Rechnet die Teilnehmerdaten auf den Stand nach der Runde zurück.
     *
     * Neu bestimmt werden Punkte, Bilanz und Feinwertungen. Die Feinwertungen
     * bekommen nur dann Werte, wenn sich für das Turnier eine Regelfassung
     * finden ließ, die den gespeicherten Endstand trifft; andernfalls stehen
     * dort Nullen, was die Spalte in allen Listen verschwinden lässt.
     *
     * Sonderpunkte werden auf null gesetzt: In der Datei steht nur ihre Summe,
     * nicht die Runde, in der sie vergeben wurden. Sie in einem Zwischenstand
     * mitzuführen hieße, sie einer Runde zuzuschlagen, in der sie vielleicht
     * noch gar nicht vergeben waren.
     *
     * @param Turnier $turnier Das Turnier
     * @param int     $runde   Letzte zu berücksichtigende Runde
     *
     * @return array<int,array<string,mixed>> Die Teilnehmer, Schlüssel wie zuvor
     */
    private static function spieler(Turnier $turnier, int $runde): array
    {
        $spieler = $turnier->getSpieler();
        $paarungen = $turnier->getPaarungen();
        $wertungen = self::feinwertungen($turnier, $runde);

        foreach ($spieler as $tnr => $satz) {
            $punkte = 0.0;
            $partien = 0;
            $siege = 0;
            $remis = 0;
            $niederlagen = 0;
            $ausgesetzt = 0;

            foreach ($paarungen[$tnr] ?? [] as $nummer => $paarung) {
                if ($nummer > $runde) {
                    continue;
                }

                $ergebnis = $paarung['ergebnis'] ?? null;
                $punkte += (float) ($ergebnis ?? 0.0);
                $gegner = (int) ($paarung['gegner'] ?? 0);

                if (0 === $gegner || ($spieler[$gegner]['spielfrei'] ?? false)) {
                    ++$ausgesetzt;

                    continue;
                }

                if (null === $ergebnis) {
                    continue;
                }

                ++$partien;

                // Die Schwelle richtet sich nach der Zahl der Partien je
                // Runde: In einem doppelrundigen Turnier ist 1 kein Sieg,
                // sondern ein geteilter Durchgang.
                $hoechstwert = (float) $turnier->getPartienProRunde();

                if ((float) $ergebnis > $hoechstwert / 2) {
                    ++$siege;
                } elseif ((float) $ergebnis === $hoechstwert / 2) {
                    ++$remis;
                } else {
                    ++$niederlagen;
                }
            }

            $spieler[$tnr] = array_merge($satz, [
                'punkte' => $punkte,
                'sonderpunkte' => 0.0,
                'partien' => $partien,
                'siege' => $siege,
                'remis' => $remis,
                'niederlagen' => $niederlagen,
                'ausgesetzt' => $ausgesetzt,
                'feinwertung1' => $wertungen[1][$tnr] ?? 0.0,
                'feinwertung2' => $wertungen[2][$tnr] ?? 0.0,
                'platz' => 0,
            ]);
        }

        return $spieler;
    }

    /**
     * Rechnet beide Feinwertungen nach, soweit das belegbar möglich ist.
     *
     * Für jede der beiden Spalten wird zunächst am Endstand geprüft, mit
     * welcher Regelfassung Swiss-Chess gerechnet hat; nur diese wird dann auf
     * den Zwischenstand angewandt. Findet sich keine, bleibt die Spalte leer.
     *
     * @param Turnier $turnier Das Turnier
     * @param int     $runde   Letzte zu berücksichtigende Runde
     *
     * @return array<int,array<int,float>> Werte je Spaltennummer (1 und 2) und
     *                                     Teilnehmernummer; fehlende Spalten fehlen
     */
    private static function feinwertungen(Turnier $turnier, int $runde): array
    {
        $spieler = $turnier->getSpieler();
        $paarungen = $turnier->getPaarungen();
        $letzte = $turnier->getLetzteRunde();
        $ergebnis = [];

        foreach ([1, 2] as $nummer) {
            $art = trim((string) $turnier->kopf('feinwertung'.$nummer.'Text', ''));

            if (!Feinwertung::kann($art)) {
                continue;
            }

            $soll = [];

            foreach ($spieler as $tnr => $satz) {
                if (!($satz['spielfrei'] ?? false)) {
                    $soll[$tnr] = (float) ($satz['feinwertung'.$nummer] ?? 0.0);
                }
            }

            $regel = Feinwertung::kalibriere($spieler, $paarungen, $art, $soll, $letzte);

            if (null === $regel) {
                continue;
            }

            $ergebnis[$nummer] = Feinwertung::werte($spieler, $paarungen, $art, $runde, $regel);
        }

        return $ergebnis;
    }

    /**
     * Sortiert die Teilnehmer zur Rangliste des Zwischenstands.
     *
     * Sortiert wird nach Punkten, dann nach den beiden Feinwertungen, zuletzt
     * nach der Startnummer. Der Platz wird neu vergeben; geteilte Plätze
     * bekommen dieselbe Nummer, damit die Tabelle nicht eine Reihenfolge
     * behauptet, die es nicht gibt.
     *
     * @param array<int,array<string,mixed>> $spieler Die zurückgerechneten Teilnehmer
     *
     * @return array<int,array<string,mixed>> Die Rangliste ohne Platzhalter
     */
    private static function rangliste(array $spieler): array
    {
        $liste = array_values(array_filter(
            $spieler,
            static fn (array $satz): bool => !($satz['spielfrei'] ?? false)
        ));

        usort(
            $liste,
            static fn (array $a, array $b): int => [
                (float) $b['punkte'],
                (float) $b['feinwertung1'],
                (float) $b['feinwertung2'],
                -(int) $a['tnr'],
            ] <=> [
                (float) $a['punkte'],
                (float) $a['feinwertung1'],
                (float) $a['feinwertung2'],
                -(int) $b['tnr'],
            ]
        );

        $platz = 0;
        $vorher = null;

        foreach ($liste as $index => $satz) {
            $schluessel = [(float) $satz['punkte'], (float) $satz['feinwertung1'], (float) $satz['feinwertung2']];

            if ($schluessel !== $vorher) {
                $platz = $index + 1;
                $vorher = $schluessel;
            }

            $liste[$index]['platz'] = $platz;
        }

        return $liste;
    }

    /**
     * Ergänzt den Turnierkopf um die Angaben zum Schnitt.
     *
     * `standNachRunde` teilt den Templates mit, dass sie einen Zwischenstand
     * zeigen. Die Bezeichnung einer Feinwertung wird gelöscht, wenn für sie
     * keine Werte zustande kamen — sonst stünde in der Kopfzeile eine Spalte
     * angekündigt, die es nicht gibt.
     *
     * @param Turnier                        $turnier Das Turnier
     * @param int                            $runde   Die Schnittrunde
     * @param array<int,array<string,mixed>> $spieler Die zurückgerechneten Teilnehmer
     *
     * @return array<string,mixed> Der ergänzte Turnierkopf
     */
    private static function kopf(Turnier $turnier, int $runde, array $spieler): array
    {
        $kopf = $turnier->getKopf();
        $kopf['standNachRunde'] = $runde;

        foreach ([1, 2] as $nummer) {
            $belegt = false;

            foreach ($spieler as $satz) {
                if (abs((float) ($satz['feinwertung'.$nummer] ?? 0.0)) > 0.001) {
                    $belegt = true;

                    break;
                }
            }

            if (!$belegt) {
                $kopf['feinwertung'.$nummer.'Text'] = '';
            }
        }

        return $kopf;
    }

    /**
     * Stellt die Hinweise zusammen, die den Zwischenstand begleiten.
     *
     * Die Hinweise der Datei bleiben erhalten; hinzu kommt in jedem Fall die
     * Angabe, dass die Zahlen nachgerechnet sind, und bei Bedarf die Auskunft,
     * warum eine Feinwertung fehlt. Wer eine Tabelle mit anderen Zahlen als
     * der amtlichen vor sich hat, soll den Grund lesen können.
     *
     * @param Turnier                        $turnier Das Turnier
     * @param int                            $runde   Die Schnittrunde
     * @param array<int,array<string,mixed>> $spieler Die zurückgerechneten Teilnehmer
     *
     * @return string[] Die Hinweise
     */
    private static function hinweise(Turnier $turnier, int $runde, array $spieler): array
    {
        $hinweise = $turnier->getHinweise();
        $hinweise[] = sprintf(
            $GLOBALS['TL_LANG']['ctv']['hinweisSchnitt']
                ?? 'Gezeigt wird der Stand nach Runde %d. Punkte und Platzierungen sind aus den Partien nachgerechnet, nicht der Turnierdatei entnommen.',
            $runde
        );

        $fehlend = [];

        foreach ([1, 2] as $nummer) {
            $art = trim((string) $turnier->kopf('feinwertung'.$nummer.'Text', ''));

            if ('' === $art) {
                continue;
            }

            $belegt = false;

            foreach ($spieler as $satz) {
                if (abs((float) ($satz['feinwertung'.$nummer] ?? 0.0)) > 0.001) {
                    $belegt = true;

                    break;
                }
            }

            if (!$belegt) {
                $fehlend[] = $art;
            }
        }

        if ([] !== $fehlend) {
            $hinweise[] = sprintf(
                $GLOBALS['TL_LANG']['ctv']['hinweisOhneFeinwertung']
                    ?? 'Ohne Angabe bleibt die Feinwertung %s: Ihre Rechenweise ließ sich am Endstand des Turniers nicht bestätigen.',
                implode(' und ', array_unique($fehlend))
            );
        }

        return $hinweise;
    }

    /**
     * Kürzt ein nach Teilnehmer und Runde gegliedertes Array auf eine Runde.
     *
     * @param array<int,array<int,array<string,mixed>>> $daten Paarungen als [Nummer][Runde]
     * @param int                                       $runde Höchste zu behaltende Runde
     *
     * @return array<int,array<int,array<string,mixed>>> Das gekürzte Array
     */
    private static function kuerze(array $daten, int $runde): array
    {
        foreach ($daten as $nummer => $runden) {
            $daten[$nummer] = array_filter(
                $runden,
                static fn (int $key): bool => $key <= $runde,
                ARRAY_FILTER_USE_KEY
            );
        }

        return $daten;
    }

    /**
     * Kürzt die nach Runden gegliederte Partienliste.
     *
     * @param array<int,array<int,array<string,mixed>>> $runden Partien je Runde
     * @param int                                       $bis    Höchste zu behaltende Runde
     *
     * @return array<int,array<int,array<string,mixed>>> Die gekürzte Liste
     */
    private static function kuerzeRunden(array $runden, int $bis): array
    {
        return array_filter(
            $runden,
            static fn (int $key): bool => $key <= $bis,
            ARRAY_FILTER_USE_KEY
        );
    }
}
