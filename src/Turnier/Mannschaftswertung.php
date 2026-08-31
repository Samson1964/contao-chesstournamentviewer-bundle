<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Stellt Wettkämpfe, Mannschaftstabelle und Mannschaftskreuztabelle zusammen.
 *
 * Die Zahlen kommen aus dem Format-Adapter und werden hier nicht mehr
 * nachgerechnet. Das war bis zur Fassung 1.1.0 anders: Der SWT-Leser lieferte
 * damals keine brauchbaren Mannschaftsdaten, weshalb dieses Bundle die
 * Wettkämpfe aus den Einzelpartien zurückrechnete. Seit der Leserfassung vom
 * 2026-08-31 macht er das selbst — und gründlicher:
 *
 *   * Die Mannschaftspunkte richten sich nach der Turniereinstellung, also
 *     zwei oder drei Punkte für den Sieg, statt fest zwei.
 *   * Drei Zustände werden unterschieden, die vorher alle als Remis
 *     durchgingen: Runde noch nicht ausgelost, Runde ausgelost aber nicht
 *     gespielt, und ein Kampf ganz ohne Paarungen, der am grünen Tisch
 *     entschieden wurde.
 *   * Die Zahlenfelder der Mannschaftskarteikarte folgen der Reihenfolge der
 *     eingestellten Feinwertungen und liegen nicht fest.
 *
 * Diese Klasse gruppiert die Daten nur noch für die Ausgabe: Sie führt die
 * beiden Sichten eines Wettkampfs zusammen, hängt die Einzelpartien an und
 * bildet die Summen für die Tabelle.
 *
 * **Freilose bleiben unbewertet.** Der Leser vergibt für eine spielfreie
 * Runde weder Brett- noch Mannschaftspunkte, weil in der Datei nichts darüber
 * steht. Manche Turnierleitungen schreiben einer freigelosten Mannschaft
 * trotzdem einen kampflosen Sieg gut; dann weicht die hier gezeigte Tabelle
 * von der gespeicherten ab, und der Leser vermerkt das als Hinweis über den
 * Tabellen. Ihn stillschweigend nachzubilden hieße raten.
 */
final class Mannschaftswertung
{
    /**
     * Führt die Wettkämpfe aller Runden zusammen.
     *
     * Die Datei führt jeden Wettkampf zweimal, einmal aus Sicht jeder
     * Mannschaft. Hier erscheint er einmal. Welche Mannschaft links steht,
     * richtet sich nach der Farbe am niedrigsten Brett: Wer dort Weiß führt,
     * gilt als Heimmannschaft. Das entspricht der üblichen Auslosung und ist
     * die einzige belastbare Angabe dazu — das Ortsbyte der
     * Mannschaftspaarungen ist in geprüften Dateien unglaubwürdig belegt
     * (bei 24 Mannschaften über sieben Runden 154 Heim- gegen 14
     * Auswärtsbegegnungen).
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Runde
     */
    public static function kaempfe(Turnier $turnier): array
    {
        if (!$turnier->istMannschaftsturnier()) {
            return [];
        }

        $mannschaften = $turnier->getMannschaften();
        $kaempfe = [];

        foreach ($turnier->getMannschaftspaarungen() as $mnr => $runden) {
            $mnr = (int) $mnr;

            if ($mannschaften[$mnr]['spielfrei'] ?? false) {
                continue;
            }

            foreach ($runden as $runde => $satz) {
                $gegner = (int) ($satz['gegner'] ?? 0);

                // Eine spielfreie Runde hat keine Gegenseite und steht deshalb
                // nur einmal in der Datei.
                if (0 === $gegner) {
                    if (self::rundeAusgelost($turnier, (int) $runde)) {
                        $kaempfe[(int) $runde][] = self::freilos($mnr, $mannschaften[$mnr]['name'] ?? '', (int) $runde);
                    }

                    continue;
                }

                // Jeden Wettkampf nur von einer Seite aufnehmen.
                if ($mnr > $gegner) {
                    continue;
                }

                $kaempfe[(int) $runde][] = self::kampf($turnier, $mnr, $gegner, (int) $runde, $satz);
            }
        }

        ksort($kaempfe);

        foreach ($kaempfe as $runde => $liste) {
            usort($liste, static fn (array $a, array $b): int => [$a['spielfrei'], $a['tisch']] <=> [$b['spielfrei'], $b['tisch']]);
            $kaempfe[$runde] = $liste;
        }

        return $kaempfe;
    }

    /**
     * Stellt die Mannschaftstabelle auf.
     *
     * Summiert wird über die Wettkampfsätze der jeweiligen Mannschaft, so wie
     * der Leser sie liefert. Die Reihenfolge ergibt sich aus Mannschafts- und
     * Brettpunkten; bei völligem Gleichstand entscheidet die in der Datei
     * gespeicherte Platzierung, damit die Tabelle nicht bei jedem Aufruf
     * springt und die Feinwertungen des Turnierprogramms nicht verlorengehen.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<int,array<string,mixed>> Die Mannschaften nach Platz
     *         sortiert, mit `platz`, `nummer`, `name`, `kaempfe`, `siege`,
     *         `unentschieden`, `niederlagen`, `freilose`,
     *         `mannschaftspunkte`, `brettpunkte`, `schnitt` und dem
     *         ursprünglichen Datensatz unter `datensatz`
     */
    public static function tabelle(Turnier $turnier): array
    {
        if (!$turnier->istMannschaftsturnier()) {
            return [];
        }

        $spieler = $turnier->getSpieler();
        $zeilen = [];

        foreach ($turnier->getMannschaften() as $mnr => $mannschaft) {
            if ($mannschaft['spielfrei'] ?? false) {
                continue;
            }

            $mnr = (int) $mnr;
            $zeile = [
                'platz' => 0,
                'nummer' => $mnr,
                'name' => (string) ($mannschaft['name'] ?? ''),
                'kaempfe' => 0,
                'siege' => 0,
                'unentschieden' => 0,
                'niederlagen' => 0,
                'freilose' => 0,
                'mannschaftspunkte' => 0.0,
                'brettpunkte' => 0.0,
                'schnitt' => self::wertungsschnitt($spieler, $mannschaft['spieler'] ?? []),
                'datensatz' => $mannschaft,
            ];

            foreach ($turnier->getMannschaftspaarungen()[$mnr] ?? [] as $runde => $satz) {
                $zeile['brettpunkte'] += (float) ($satz['brettpunkte'] ?? 0.0);

                if (0 === (int) ($satz['gegner'] ?? 0)) {
                    // Nur ausgeloste Runden zählen als Freilos; die noch nicht
                    // ausgelosten stehen ebenfalls ohne Gegner in der Datei.
                    if (self::rundeAusgelost($turnier, (int) $runde)) {
                        ++$zeile['freilose'];
                    }

                    continue;
                }

                // Ein Kampf ohne Mannschaftspunkte ist noch nicht gespielt.
                if (null === ($satz['mannschaftspunkte'] ?? null)) {
                    continue;
                }

                $zeile['mannschaftspunkte'] += (float) $satz['mannschaftspunkte'];
                ++$zeile['kaempfe'];

                $eigen = (float) ($satz['brettpunkte'] ?? 0.0);
                $fremd = (float) ($satz['brettpunkteGegner'] ?? 0.0);

                if ($eigen > $fremd) {
                    ++$zeile['siege'];
                } elseif ($eigen === $fremd) {
                    ++$zeile['unentschieden'];
                } else {
                    ++$zeile['niederlagen'];
                }
            }

            $zeilen[] = $zeile;
        }

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
     * Mit `$kurz` steht in der Zelle nur die eigene Zahl — „3½" statt „3½:½".
     * So hält es auch Swiss-Chess in seinen Ausdrucken: Die Gegenzahl steht
     * ohnehin gespiegelt in der Zelle des Gegners, und die Tabelle wird um
     * die Hälfte schmaler.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param bool    $kurz    Ob nur die eigenen Brettpunkte ausgegeben werden
     *
     * @return array{mannschaften:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}
     *         `mannschaften` gibt die Reihenfolge vor, `zeilen` enthält je
     *         Zeilenindex ein Array von Spaltenindex auf Text
     */
    public static function kreuztabelle(Turnier $turnier, bool $kurz = false): array
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

        foreach ($turnier->getMannschaftspaarungen() as $mnr => $runden) {
            $zeile = $spalte[(int) $mnr] ?? null;

            if (null === $zeile) {
                continue;
            }

            foreach ($runden as $satz) {
                $gegenspalte = $spalte[(int) ($satz['gegner'] ?? 0)] ?? null;

                if (null === $gegenspalte || null === ($satz['mannschaftspunkte'] ?? null)) {
                    continue;
                }

                $eigene = self::punkteText((float) $satz['brettpunkte']);

                $zeilen[$zeile][$gegenspalte] = $kurz
                    ? $eigene
                    : $eigene.':'.self::punkteText((float) $satz['brettpunkteGegner']);
            }
        }

        return ['mannschaften' => $tabelle, 'zeilen' => $zeilen];
    }

    /**
     * Stellt einen einzelnen Wettkampf zusammen.
     *
     * @param Turnier             $turnier Das eingelesene Turnier
     * @param int                 $eine    Mannschaftsnummer der einen Seite
     * @param int                 $andere  Mannschaftsnummer der anderen Seite
     * @param int                 $runde   Rundennummer
     * @param array<string,mixed> $satz    Der Wettkampfsatz aus Sicht von $eine
     *
     * @return array<string,mixed> Der Wettkampf mit beiden Seiten, Punkten und
     *                             den Einzelpartien nach Brett sortiert
     */
    private static function kampf(Turnier $turnier, int $eine, int $andere, int $runde, array $satz): array
    {
        $mannschaften = $turnier->getMannschaften();
        $partien = self::partien($turnier, $eine, $andere, $runde);

        // Links steht, wer am niedrigsten Brett Weiß führt.
        $tausch = ($partien[0]['weissMannschaft'] ?? $eine) !== $eine;
        $heim = $tausch ? $andere : $eine;
        $gast = $tausch ? $eine : $andere;

        $gegensatz = $turnier->getMannschaftspaarungen()[$andere][$runde] ?? [];
        $punkte = [
            $eine => (float) ($satz['brettpunkte'] ?? 0.0),
            $andere => (float) ($satz['brettpunkteGegner'] ?? 0.0),
        ];
        $mannschaftspunkte = [
            $eine => $satz['mannschaftspunkte'] ?? null,
            $andere => $gegensatz['mannschaftspunkte'] ?? null,
        ];

        // Der Schnitt gilt für die Mannschaft, die in diesem Wettkampf antrat,
        // nicht für den gemeldeten Kader: Wer nicht am Brett saß, sagt über
        // die Stärke dieser Begegnung nichts aus.
        $schnitt = [$heim => [], $gast => []];

        foreach ($partien as $partie) {
            foreach (['weiss', 'schwarz'] as $seite) {
                $twz = (int) ($partie[$seite]['twz'] ?? 0);
                $nummer = $partie[$seite.'Mannschaft'] ?? null;

                if ($twz > 0 && isset($schnitt[$nummer])) {
                    $schnitt[$nummer][] = $twz;
                }
            }
        }

        $partien = self::richteAus($partien, $heim, (float) $turnier->getPartienProRunde());

        return [
            'runde' => $runde,
            'heim' => $heim,
            'gast' => $gast,
            'heimName' => (string) ($mannschaften[$heim]['name'] ?? ''),
            'gastName' => (string) ($mannschaften[$gast]['name'] ?? ''),
            'schnittHeim' => [] === $schnitt[$heim] ? 0 : (int) round(array_sum($schnitt[$heim]) / \count($schnitt[$heim])),
            'schnittGast' => [] === $schnitt[$gast] ? 0 : (int) round(array_sum($schnitt[$gast]) / \count($schnitt[$gast])),
            'brettpunkteHeim' => $punkte[$heim],
            'brettpunkteGast' => $punkte[$gast],
            'mannschaftspunkteHeim' => $mannschaftspunkte[$heim],
            'mannschaftspunkteGast' => $mannschaftspunkte[$gast],
            'gespielt' => null !== $mannschaftspunkte[$heim],
            'amGruenenTisch' => (bool) ($satz['amGruenenTisch'] ?? false),
            'spielfrei' => false,
            'tisch' => (int) ($satz['tisch'] ?? 0),
            'partien' => $partien,
        ];
    }

    /**
     * Richtet die Partien eines Wettkampfs an den Mannschaften aus.
     *
     * Die Partien liegen an Weiß ausgerichtet vor, wie es eine Paarungsliste
     * braucht. In der Wettkampfansicht stehen aber die Mannschaften über den
     * Spalten, und dort **darf nicht nach Farbe sortiert werden**: Die Farben
     * wechseln von Brett zu Brett, sodass in einer Spalte abwechselnd Spieler
     * beider Mannschaften stünden. Genau das war bis Fassung 1.3.0 der Fall.
     *
     * Ergänzt werden deshalb je Partie der Spieler beider Mannschaften, deren
     * Farbe und das Ergebnis aus Sicht der Heimmannschaft. Die an Weiß
     * ausgerichteten Felder bleiben daneben stehen, weil die Paarungsliste sie
     * weiter braucht.
     *
     * @param array<int,array<string,mixed>> $partien     Die Partien des Wettkampfs
     * @param int                            $heim        Nummer der Mannschaft, die links steht
     * @param float                          $hoechstwert Punkte, die eine Paarung je Runde vergibt
     *
     * @return array<int,array<string,mixed>> Dieselben Partien mit den Feldern
     *                                        `heimSpieler`, `gastSpieler`,
     *                                        `heimFarbe`, `gastFarbe` und
     *                                        `ergebnisHeim`
     */
    private static function richteAus(array $partien, int $heim, float $hoechstwert): array
    {
        foreach ($partien as $index => $partie) {
            $heimIstWeiss = ($partie['weissMannschaft'] ?? null) === $heim;
            $ergebnis = $partie['ergebnis'];

            $partien[$index] = array_merge($partie, [
                'heimSpieler' => $heimIstWeiss ? $partie['weiss'] : $partie['schwarz'],
                'gastSpieler' => $heimIstWeiss ? $partie['schwarz'] : $partie['weiss'],
                'heimFarbe' => $heimIstWeiss ? 'w' : 's',
                'gastFarbe' => $heimIstWeiss ? 's' : 'w',
                'ergebnisHeim' => null === $ergebnis ? null : ($heimIstWeiss ? (float) $ergebnis : $hoechstwert - (float) $ergebnis),
                'hoechstwert' => $hoechstwert,
            ]);
        }

        return $partien;
    }

    /**
     * Erzeugt den Eintrag für eine spielfreie Runde.
     *
     * Punkte werden keine vergeben; siehe Klassenkommentar.
     *
     * @param int    $mnr   Mannschaftsnummer
     * @param string $name  Name der Mannschaft
     * @param int    $runde Rundennummer
     *
     * @return array<string,mixed> Der Eintrag in derselben Form wie ein Wettkampf
     */
    private static function freilos(int $mnr, string $name, int $runde): array
    {
        return [
            'runde' => $runde,
            'heim' => $mnr,
            'gast' => null,
            'heimName' => $name,
            'gastName' => '',
            'schnittHeim' => 0,
            'schnittGast' => 0,
            'brettpunkteHeim' => 0.0,
            'brettpunkteGast' => 0.0,
            'mannschaftspunkteHeim' => null,
            'mannschaftspunkteGast' => null,
            'gespielt' => false,
            'amGruenenTisch' => false,
            'spielfrei' => true,
            'tisch' => PHP_INT_MAX,
            'partien' => [],
        ];
    }

    /**
     * Sammelt die Einzelpartien eines Wettkampfs.
     *
     * Grundlage ist die Spielerliste beider Mannschaften; aufgenommen wird
     * jede Partie, deren Gegner zur anderen Mannschaft gehört. Jede Partie
     * erscheint einmal, ausgerichtet an Weiß.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param int     $eine    Mannschaftsnummer der einen Seite
     * @param int     $andere  Mannschaftsnummer der anderen Seite
     * @param int     $runde   Rundennummer
     *
     * @return array<int,array<string,mixed>> Die Partien nach Brett sortiert
     */
    private static function partien(Turnier $turnier, int $eine, int $andere, int $runde): array
    {
        $spieler = $turnier->getSpieler();
        $paarungen = $turnier->getPaarungen();
        $mannschaften = $turnier->getMannschaften();
        $hoechstwert = (float) $turnier->getPartienProRunde();
        $partien = [];

        foreach ($mannschaften[$eine]['spieler'] ?? [] as $tnr) {
            $tnr = (int) $tnr;
            $satz = $paarungen[$tnr][$runde] ?? null;
            $gegnerNr = (int) ($satz['gegner'] ?? 0);

            if (null === $satz || 0 === $gegnerNr) {
                continue;
            }

            if ((int) ($spieler[$gegnerNr]['mannschaftsnummer'] ?? 0) !== $andere) {
                continue;
            }

            // Partien gegen den Platzhalterteilnehmer gehören nicht zum
            // Wettkampf. Der Leser lässt ihn aus der Spielerliste der
            // Mannschaft heraus und rechnet ihn deshalb auch nicht in die
            // Brettpunkte ein; nähme man ihn hier auf, ginge die Summe der
            // Bretter nicht mehr mit dem Wettkampfergebnis zusammen.
            if (($spieler[$tnr]['spielfrei'] ?? false) || ($spieler[$gegnerNr]['spielfrei'] ?? false)) {
                continue;
            }

            $istWeiss = 'w' === mb_strtolower(mb_substr(trim((string) ($satz['farbe'] ?? '')), 0, 1));
            $ergebnis = $satz['ergebnis'];

            // Das Gegenergebnis ist nicht 1 minus dem eigenen, sondern der
            // Rundenhoechstwert minus dem eigenen: Bei zwei Partien je Runde
            // laeuft ein Rundenergebnis von 0 bis 2.
            if (null !== $ergebnis && !$istWeiss) {
                $ergebnis = $hoechstwert - (float) $ergebnis;
            }

            $partien[] = [
                'brett' => (int) ($satz['brett'] ?? 0),
                'runde' => $runde,
                'weiss' => $spieler[$istWeiss ? $tnr : $gegnerNr] ?? [],
                'schwarz' => $spieler[$istWeiss ? $gegnerNr : $tnr] ?? [],
                'weissMannschaft' => $istWeiss ? $eine : $andere,
                'schwarzMannschaft' => $istWeiss ? $andere : $eine,
                'ergebnis' => $ergebnis,
                'status' => (string) ($satz['status'] ?? ''),
            ];
        }

        usort($partien, static fn (array $a, array $b): int => $a['brett'] <=> $b['brett']);

        return $partien;
    }

    /**
     * Prüft, ob eine Runde überhaupt ausgelost ist.
     *
     * Nicht ausgeloste Runden stehen in der Datei genauso ohne Gegner wie
     * spielfreie. Ohne diese Unterscheidung bekäme in einem laufenden Turnier
     * jede Mannschaft für jede kommende Runde ein Freilos.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param int     $runde   Rundennummer
     *
     * @return bool true, wenn für diese Runde Partien vorliegen
     */
    private static function rundeAusgelost(Turnier $turnier, int $runde): bool
    {
        return [] !== $turnier->getRunde($runde);
    }

    /**
     * Errechnet die durchschnittliche Turnierwertungszahl einer Mannschaft.
     *
     * Gemittelt wird über **alle gemeldeten Spieler** der Mannschaft, die eine
     * Wertungszahl führen. Das ist genau der Kreis, den die Mannschaftsliste
     * mit eingeschalteter Aufstellung darunter zeigt; eine Zahl, die sich auf
     * einen anderen Kreis bezöge, wäre dort nicht nachzuvollziehen.
     *
     * **Diese Zahl ist nicht die von Swiss-Chess.** Nach welcher Regel dessen
     * TWZ-Spalte gebildet wird, ließ sich an den vorliegenden Ausgaben nicht
     * bestimmen — die beiden Referenzturniere widersprechen sich:
     *
     * | Regel | Blitz-MM 2012 (25 Mannschaften) | Betriebs-MM 2012 (37) |
     * | --- | ---: | ---: |
     * | alle gemeldeten Spieler | 24 | 10 |
     * | nur die Bretter 1 bis zur Brettzahl | 12 | 35 |
     * | die stärksten Spieler in Brettzahl | 12 | 24 |
     *
     * Beide Turniere haben vier Bretter; im ersten zählt Swiss-Chess den
     * fünften Spieler mit, im zweiten nicht. Ein Fall bleibt selbst dann
     * unerklärt, wenn eine Mannschaft genau vier Spieler an den Brettern 1
     * bis 4 führt und beide Rechnungen dasselbe ergeben. Solange die Regel
     * nicht feststeht, ist die einfache und nachvollziehbare die bessere.
     *
     * @param array<int,array<string,mixed>> $spieler  Alle Teilnehmer des Turniers
     * @param array<int,int>                 $tnrs     Teilnehmernummern der Mannschaft
     *
     * @return int Der Schnitt, oder 0 wenn kein Spieler eine Wertungszahl hat
     */
    public static function wertungsschnitt(array $spieler, array $tnrs): int
    {
        $werte = [];

        foreach ($tnrs as $tnr) {
            $twz = (int) ($spieler[(int) $tnr]['twz'] ?? 0);

            if ($twz > 0) {
                $werte[] = $twz;
            }
        }

        return [] === $werte ? 0 : (int) round(array_sum($werte) / \count($werte));
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
}
