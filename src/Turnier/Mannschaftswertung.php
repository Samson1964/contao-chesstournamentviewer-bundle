<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Rechnet die Mannschaftswertung aus den Einzelpartien zurück.
 *
 * Warum überhaupt zurückgerechnet wird
 * ------------------------------------
 * Die Mannschaftsangaben, die Swiss-Chess in seinen Dateien ablegt, sind
 * nicht durchgängig brauchbar. Geprüft an den fünf Mannschaftsdateien unter
 * `Gegentest/C-Mannschaftsturnier` (Fassungen 650 bis 897):
 *
 *   * Der Mannschaftspaarungsbereich enthält in allen fünf Dateien **kein
 *     einziges Ergebnis** — `ergebnis`, `brettergebnis` und
 *     `mannschaftsergebnis` sind durchweg null. Wer die Wettkämpfe daraus
 *     ausgibt, zeigt eine Tabelle ohne Zahlen.
 *   * Vor Fassung 800 stehen auf den Mannschaftskarteikarten Unsinnswerte
 *     (Brettpunkte wie 14137 bei sechs Brettern und sieben Runden).
 *
 * Die Einzelpartien dagegen sind über den gesamten Bestand geprüft. Aus ihnen
 * lässt sich alles gewinnen, was eine Mannschaftstabelle braucht — die
 * Zuordnung Spieler zu Mannschaft steht auf jeder Spielerkarteikarte.
 *
 * Nachweis der Rückrechnung
 * -------------------------
 * Verglichen wurden die zurückgerechneten Brett- und Mannschaftspunkte mit
 * den gespeicherten Werten der Mannschaftskarteikarten, für alle Dateien der
 * Fassungen ab 800, in denen diese Werte plausibel sind:
 *
 *   | Datei                | Mannschaften | Abweichungen |
 *   | -------------------- | -----------: | -----------: |
 *   | DBMM_2012.SWT (882)  |           38 |            0 |
 *   | FVS_BMM_2012_13 (882)|           14 |            0 |
 *   | BSMM2017.SWT (897)   |           23 |            0 |
 *
 * Bei den älteren Fassungen 650 und 710 gibt es nichts zu vergleichen, weil
 * die gespeicherten Werte dort unbrauchbar sind; die Rückrechnung ist die
 * einzige Quelle.
 *
 * Zwei Regeln waren dafür nötig und sind an genau diesen Zahlen belegt:
 *
 *   1. Die Mannschaftsnummer ist die **Position** in der Mannschaftsliste,
 *      nicht das Nummernfeld der Karteikarte — dieses liefert in allen fünf
 *      Dateien 999 plus Position. (Geprüft an 540 Teilnehmern: die Position
 *      stimmt in jedem einzelnen Fall mit dem Mannschaftsnamen des Spielers
 *      überein.) Die Adapter liefern die Liste deshalb positionsindiziert.
 *   2. Eine Mannschaft ohne Wettkampf in einer gespielten Runde hat ein
 *      Freilos und bekommt die volle Brettzahl sowie zwei Mannschaftspunkte.
 *      Ohne diese Regel fehlten bei BSMM2017 genau den sieben Mannschaften
 *      mit sechs statt sieben Wettkämpfen je 4 Brett- und 2 Mannschaftspunkte.
 */
final class Mannschaftswertung
{
    /**
     * Bildet die Wettkämpfe aller Runden aus den Einzelpartien.
     *
     * Zwei Mannschaften gelten als aufeinandergetroffen, sobald in einer Runde
     * mindestens eine Partie zwischen ihren Spielern steht. Die Reihenfolge
     * innerhalb eines Wettkampfs richtet sich nach der Farbe am niedrigsten
     * Brett: Wer dort Weiß führt, steht links. Das entspricht der üblichen
     * Auslosung und ist die einzige Angabe zur Heimseite, die sich aus den
     * Einzelpartien gewinnen lässt — ob eine Mannschaft tatsächlich Gastgeber
     * war, steht in der Datei nicht verwertbar.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Runde.
     *         Jeder Eintrag enthält `heim`, `gast` (Mannschaftsnummern, `gast`
     *         ist null bei Freilos), die zugehörigen Namen, Brett- und
     *         Mannschaftspunkte beider Seiten sowie unter `partien` die
     *         Einzelpartien nach Brett sortiert.
     */
    public static function kaempfe(Turnier $turnier): array
    {
        if (!$turnier->istMannschaftsturnier()) {
            return [];
        }

        $spieler = $turnier->getSpieler();
        $mannschaften = $turnier->getMannschaften();
        $bretter = $turnier->getBretter();
        $platzhalter = self::platzhaltermannschaften($mannschaften);

        // Schritt 1: Partien nach Runde und Mannschaftspaar bündeln.
        $roh = [];

        foreach ($turnier->getPaarungen() as $tnr => $runden) {
            $tnr = (int) $tnr;
            $eigene = (int) ($spieler[$tnr]['mannschaftsnummer'] ?? 0);

            if (0 === $eigene || isset($platzhalter[$eigene]) || ($spieler[$tnr]['spielfrei'] ?? false)) {
                continue;
            }

            foreach ($runden as $runde => $satz) {
                $gegnerNr = (int) ($satz['gegner'] ?? 0);

                if (0 === $gegnerNr || !isset($spieler[$gegnerNr]) || ($spieler[$gegnerNr]['spielfrei'] ?? false)) {
                    continue;
                }

                $fremde = (int) ($spieler[$gegnerNr]['mannschaftsnummer'] ?? 0);

                // Partien innerhalb derselben Mannschaft gehören zu keinem
                // Wettkampf; sie kommen in Turnieren mit Restplätzen vor.
                if (0 === $fremde || $fremde === $eigene || isset($platzhalter[$fremde])) {
                    continue;
                }

                // Jede Partie wird von beiden Seiten gesehen. Der Schlüssel
                // aus den beiden Mannschaftsnummern in fester Reihenfolge
                // sorgt dafür, dass sie nur einmal im Wettkampf landet.
                $paar = $eigene < $fremde ? $eigene.'-'.$fremde : $fremde.'-'.$eigene;

                $roh[(int) $runde][$paar]['punkte'][$eigene] = ($roh[(int) $runde][$paar]['punkte'][$eigene] ?? 0.0) + (float) ($satz['ergebnis'] ?? 0.0);
                $roh[(int) $runde][$paar]['gespielt'][$eigene] = true;

                if ($eigene < $fremde) {
                    $roh[(int) $runde][$paar]['partien'][$tnr] = self::partie($turnier, $tnr, (int) $runde, $satz, $eigene, $fremde);
                }
            }
        }

        // Schritt 2: Wettkämpfe zusammensetzen, Runde für Runde.
        $kaempfe = [];
        $rundennummern = array_keys($roh);
        sort($rundennummern);

        foreach ($rundennummern as $runde) {
            $angetreten = [];

            foreach ($roh[$runde] as $paar => $daten) {
                [$erste, $zweite] = array_map('intval', explode('-', (string) $paar));
                $angetreten[$erste] = true;
                $angetreten[$zweite] = true;

                $partien = $daten['partien'] ?? [];
                usort($partien, static fn (array $a, array $b): int => $a['brett'] <=> $b['brett']);

                // Links steht, wer am niedrigsten Brett Weiß führt.
                $tausch = ($partien[0]['weissMannschaft'] ?? $erste) !== $erste;
                $heim = $tausch ? $zweite : $erste;
                $gast = $tausch ? $erste : $zweite;

                $bpHeim = (float) ($daten['punkte'][$heim] ?? 0.0);
                $bpGast = (float) ($daten['punkte'][$gast] ?? 0.0);

                $kaempfe[$runde][] = [
                    'heim' => $heim,
                    'gast' => $gast,
                    'heimName' => (string) ($mannschaften[$heim]['name'] ?? ''),
                    'gastName' => (string) ($mannschaften[$gast]['name'] ?? ''),
                    'brettpunkteHeim' => $bpHeim,
                    'brettpunkteGast' => $bpGast,
                    'mannschaftspunkteHeim' => self::mannschaftspunkte($bpHeim, $bpGast),
                    'mannschaftspunkteGast' => self::mannschaftspunkte($bpGast, $bpHeim),
                    'spielfrei' => false,
                    'partien' => array_values($partien),
                ];
            }

            // Wer in einer gespielten Runde nirgends antrat, hat ein Freilos.
            foreach ($mannschaften as $nr => $mannschaft) {
                if (isset($angetreten[$nr]) || isset($platzhalter[$nr])) {
                    continue;
                }

                $kaempfe[$runde][] = [
                    'heim' => $nr,
                    'gast' => null,
                    'heimName' => (string) ($mannschaft['name'] ?? ''),
                    'gastName' => '',
                    'brettpunkteHeim' => (float) $bretter,
                    'brettpunkteGast' => 0.0,
                    'mannschaftspunkteHeim' => 2.0,
                    'mannschaftspunkteGast' => 0.0,
                    'spielfrei' => true,
                    'partien' => [],
                ];
            }
        }

        return $kaempfe;
    }

    /**
     * Stellt die Mannschaftstabelle auf.
     *
     * Gewertet wird mit zwei Punkten für einen gewonnenen und einem für einen
     * unentschiedenen Wettkampf; die Brettpunkte sind die zweite Wertung. Die
     * Reihenfolge ergibt sich aus Mannschafts- und Brettpunkten. Bei
     * völligem Gleichstand entscheidet die im Turnier gespeicherte
     * Platzierung, damit die Tabelle nicht bei jedem Aufruf springt.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array<string,mixed>> Die Mannschaften nach Platz
     *         sortiert, mit `platz`, `nummer`, `name`, `kaempfe`, `siege`,
     *         `unentschieden`, `niederlagen`, `freilose`,
     *         `mannschaftspunkte`, `brettpunkte` und dem ursprünglichen
     *         Datensatz unter `datensatz`
     */
    public static function tabelle(Turnier $turnier): array
    {
        $mannschaften = $turnier->getMannschaften();
        $platzhalter = self::platzhaltermannschaften($mannschaften);
        $zeilen = [];

        foreach ($mannschaften as $nr => $mannschaft) {
            if (isset($platzhalter[$nr])) {
                continue;
            }

            $zeilen[$nr] = [
                'platz' => 0,
                'nummer' => $nr,
                'name' => (string) ($mannschaft['name'] ?? ''),
                'kaempfe' => 0,
                'siege' => 0,
                'unentschieden' => 0,
                'niederlagen' => 0,
                'freilose' => 0,
                'mannschaftspunkte' => 0.0,
                'brettpunkte' => 0.0,
                'datensatz' => $mannschaft,
            ];
        }

        foreach (self::kaempfe($turnier) as $kaempfeDerRunde) {
            foreach ($kaempfeDerRunde as $kampf) {
                foreach ([['heim', 'gast'], ['gast', 'heim']] as [$seite, $gegenseite]) {
                    $nr = $kampf[$seite];

                    if (null === $nr || !isset($zeilen[$nr])) {
                        continue;
                    }

                    $eigen = (float) $kampf['brettpunkte'.ucfirst($seite)];
                    $fremd = (float) $kampf['brettpunkte'.ucfirst($gegenseite)];

                    $zeilen[$nr]['brettpunkte'] += $eigen;
                    $zeilen[$nr]['mannschaftspunkte'] += (float) $kampf['mannschaftspunkte'.ucfirst($seite)];

                    // Ein Freilos ist kein gewonnener Wettkampf. Die Punkte
                    // zählen mit, die Bilanz aus Siegen, Unentschieden und
                    // Niederlagen bleibt davon unberührt und wird gesondert
                    // ausgewiesen.
                    if ($kampf['spielfrei']) {
                        ++$zeilen[$nr]['freilose'];

                        continue;
                    }

                    ++$zeilen[$nr]['kaempfe'];

                    if ($eigen > $fremd) {
                        ++$zeilen[$nr]['siege'];
                    } elseif ($eigen === $fremd) {
                        ++$zeilen[$nr]['unentschieden'];
                    } else {
                        ++$zeilen[$nr]['niederlagen'];
                    }
                }
            }
        }

        $zeilen = array_values($zeilen);

        usort(
            $zeilen,
            static fn (array $a, array $b): int => [$b['mannschaftspunkte'], $b['brettpunkte'], -(int) ($a['datensatz']['platz'] ?? 0)]
                <=> [$a['mannschaftspunkte'], $a['brettpunkte'], -(int) ($b['datensatz']['platz'] ?? 0)]
        );

        foreach ($zeilen as $index => $zeile) {
            $zeilen[$index]['platz'] = $index + 1;
        }

        return $zeilen;
    }

    /**
     * Erzeugt die Kreuztabelle der Mannschaften.
     *
     * Zeilen und Spalten folgen der Mannschaftstabelle. In jeder Zelle stehen
     * die Brettpunkte des Zeilengegenübers gegen die Spaltenmannschaft, etwa
     * „3½:½". Mannschaften, die nicht gegeneinander angetreten sind, bleiben
     * leer; die Diagonale ist mit `**` gefüllt.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array{mannschaften:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}
     *         `mannschaften` gibt die Reihenfolge vor, `zeilen` enthält je
     *         Zeilenindex ein Array von Spaltenindex auf Text
     */
    public static function kreuztabelle(Turnier $turnier): array
    {
        $tabelle = self::tabelle($turnier);
        $spalte = [];

        foreach ($tabelle as $index => $zeile) {
            $spalte[$zeile['nummer']] = $index;
        }

        $zeilen = [];

        foreach ($tabelle as $index => $unused) {
            $zeilen[$index] = array_fill(0, \count($tabelle), '');
            $zeilen[$index][$index] = '**';
        }

        foreach (self::kaempfe($turnier) as $kaempfeDerRunde) {
            foreach ($kaempfeDerRunde as $kampf) {
                if ($kampf['spielfrei'] || null === $kampf['gast']) {
                    continue;
                }

                $zeileHeim = $spalte[$kampf['heim']] ?? null;
                $zeileGast = $spalte[$kampf['gast']] ?? null;

                if (null === $zeileHeim || null === $zeileGast) {
                    continue;
                }

                $zeilen[$zeileHeim][$zeileGast] = self::punkteText($kampf['brettpunkteHeim']).':'.self::punkteText($kampf['brettpunkteGast']);
                $zeilen[$zeileGast][$zeileHeim] = self::punkteText($kampf['brettpunkteGast']).':'.self::punkteText($kampf['brettpunkteHeim']);
            }
        }

        return ['mannschaften' => $tabelle, 'zeilen' => $zeilen];
    }

    /**
     * Vergleicht die zurückgerechnete Wertung mit den gespeicherten Werten.
     *
     * Dient der Selbstkontrolle, wie sie der SWT-Leser für die Einzelwertung
     * schon vornimmt. Weichen die Zahlen ab, ist das kein Fehler der
     * Rückrechnung — bei alten Dateiformaten stehen auf den Karteikarten
     * ohnehin Unsinnswerte —, aber der Betrachter soll es erwähnen dürfen.
     *
     * Verglichen wird nur, wenn die gespeicherten Werte überhaupt plausibel
     * sind: mehr Brettpunkte als Bretter mal Runden kann keine Mannschaft
     * erreicht haben, und solche Werte kommen in alten Dateien massenhaft vor.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return string[] Ein Hinweis je Abweichung, leer wenn alles zusammenpasst
     *                  oder die gespeicherten Werte nicht vergleichbar sind
     */
    public static function hinweise(Turnier $turnier): array
    {
        if (!$turnier->istMannschaftsturnier()) {
            return [];
        }

        $hoechstwert = $turnier->getBretter() * max(1, $turnier->getLetzteRunde());
        $hinweise = [];
        $abweichungen = 0;
        $vergleichbar = 0;

        foreach (self::tabelle($turnier) as $zeile) {
            $gespeichert = $zeile['datensatz']['brettpunkte'] ?? null;

            if (!is_numeric($gespeichert) || (float) $gespeichert > $hoechstwert) {
                continue;
            }

            ++$vergleichbar;

            if (abs((float) $gespeichert - $zeile['brettpunkte']) > 0.001) {
                ++$abweichungen;
            }
        }

        if ($vergleichbar > 0 && $abweichungen > 0) {
            $hinweise[] = sprintf(
                'Die aus den Einzelpartien errechneten Brettpunkte weichen bei %d von %d Mannschaften von den in der Datei gespeicherten ab. Angezeigt werden die errechneten Werte.',
                $abweichungen,
                $vergleichbar
            );
        }

        if (0 === $vergleichbar && [] !== $turnier->getMannschaften()) {
            $hinweise[] = 'Die Datei enthält keine brauchbaren Mannschaftspunkte. Die Mannschaftswertung wurde vollständig aus den Einzelpartien errechnet.';
        }

        return $hinweise;
    }

    /**
     * Stellt eine einzelne Partie eines Wettkampfs zusammen.
     *
     * Die Partie wird immer aus Sicht des Spielers aufgenommen, dessen
     * Mannschaft die kleinere Nummer trägt; die Ausrichtung nach Weiß und
     * Schwarz erfolgt danach anhand der Farbe.
     *
     * @param Turnier             $turnier   Das Turnier, für den Zugriff auf die Spielerliste
     * @param int                 $tnr       Teilnehmernummer des betrachteten Spielers
     * @param int                 $runde     Rundennummer
     * @param array<string,mixed> $satz      Der Paarungssatz dieses Spielers
     * @param int                 $eigene    Mannschaftsnummer des betrachteten Spielers
     * @param int                 $fremde    Mannschaftsnummer des Gegners
     *
     * @return array<string,mixed> Die Partie mit `brett`, `weiss`, `schwarz`,
     *                             `weissMannschaft`, `schwarzMannschaft`,
     *                             `ergebnis` und `ergebnisText`
     */
    private static function partie(Turnier $turnier, int $tnr, int $runde, array $satz, int $eigene, int $fremde): array
    {
        $spieler = $turnier->getSpieler();
        $gegnerNr = (int) $satz['gegner'];
        $istWeiss = 'w' === mb_strtolower(mb_substr(trim((string) ($satz['farbe'] ?? '')), 0, 1));

        $weissNr = $istWeiss ? $tnr : $gegnerNr;
        $schwarzNr = $istWeiss ? $gegnerNr : $tnr;

        // Das Ergebnis liegt aus Sicht von $tnr vor und muss für die Ausgabe
        // an Weiß ausgerichtet werden.
        $ergebnis = $satz['ergebnis'];

        if (null !== $ergebnis && !$istWeiss) {
            $ergebnis = 1.0 - (float) $ergebnis;
        }

        return [
            'brett' => (int) ($satz['brett'] ?? 0),
            'runde' => $runde,
            'weiss' => $spieler[$weissNr] ?? [],
            'schwarz' => $spieler[$schwarzNr] ?? [],
            'weissMannschaft' => $istWeiss ? $eigene : $fremde,
            'schwarzMannschaft' => $istWeiss ? $fremde : $eigene,
            'ergebnis' => $ergebnis,
            'ergebnisText' => self::ergebnisText($ergebnis),
        ];
    }

    /**
     * Ermittelt die Mannschaftspunkte einer Seite.
     *
     * Zwei Punkte für den Sieg, einer für ein Unentschieden. Diese Wertung
     * stimmt in allen geprüften Dateien mit den gespeicherten Werten überein.
     * Ligen, die anders werten (etwa 1 und ½), gäben abweichende Zahlen; ein
     * entsprechender Schalter steht in den Dateien nicht.
     *
     * @param float $eigen Brettpunkte der betrachteten Mannschaft
     * @param float $fremd Brettpunkte des Gegners
     *
     * @return float 2, 1 oder 0
     */
    private static function mannschaftspunkte(float $eigen, float $fremd): float
    {
        if ($eigen > $fremd) {
            return 2.0;
        }

        return $eigen === $fremd ? 1.0 : 0.0;
    }

    /**
     * Findet Platzhaltermannschaften.
     *
     * Bei ungerader Mannschaftszahl legt Swiss-Chess eine Mannschaft an, gegen
     * die spielt, wer aussetzt. Sie heißt „spielfrei" oder trägt gar keinen
     * Namen. In Tabelle und Wettkampflisten hat sie nichts verloren, und die
     * Freilosregel würde sie zu einem Tabellenführer machen.
     *
     * @param array<int,array<string,mixed>> $mannschaften Die Mannschaftsliste
     *
     * @return array<int,true> Mannschaftsnummern der Platzhalter als Schlüssel
     */
    private static function platzhaltermannschaften(array $mannschaften): array
    {
        $platzhalter = [];

        foreach ($mannschaften as $nr => $mannschaft) {
            $name = mb_strtolower(trim((string) ($mannschaft['name'] ?? '')));

            if ('' === $name || 'spielfrei' === $name || 'bye' === $name) {
                $platzhalter[$nr] = true;
            }
        }

        return $platzhalter;
    }

    /**
     * Formt eine Punktzahl für die Anzeige.
     *
     * Halbe Punkte werden wie im Schach üblich mit ½ geschrieben, ganze ohne
     * Nachkommastelle.
     *
     * @param float $punkte Die Punktzahl
     *
     * @return string Die Punktzahl als Text, etwa „3½"
     */
    public static function punkteText(float $punkte): string
    {
        $ganz = (int) floor($punkte);
        $halb = $punkte - $ganz >= 0.5;

        if (!$halb) {
            return (string) $ganz;
        }

        return (0 === $ganz ? '' : (string) $ganz).'½';
    }

    /**
     * Formt das Ergebnis einer Partie aus Sicht von Weiß.
     *
     * @param float|null $ergebnis Punkte für Weiß, oder null wenn die Partie
     *                             noch nicht gewertet ist
     *
     * @return string „1", „½", „0" oder eine leere Zeichenkette
     */
    private static function ergebnisText(?float $ergebnis): string
    {
        return match (true) {
            null === $ergebnis => '',
            $ergebnis >= 1.0 => '1',
            $ergebnis >= 0.5 => '½',
            default => '0',
        };
    }
}
