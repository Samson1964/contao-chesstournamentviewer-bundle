<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Format\SwissManager;

use Schachbulle\ContaoChesstournamentviewerBundle\Format\TurnierFormatInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Kreuztabelle;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Schließt die Turnierdateien des Swiss-Managers an das Turniermodell an.
 *
 * Anders als beim SWT-Format sind Punkte und Platzierungen nicht aus der
 * Datei zu übernehmen — Swiss-Manager speichert sie nicht. Sie entstehen hier
 * aus den Partien. Feinwertungen bleiben leer: In der Datei steht nicht,
 * welche das Turnier führt, und eine geratene Zahl wäre schlechter als keine.
 */
class SwissManagerFormat implements TurnierFormatInterface
{
    /**
     * Höchste Teilnehmerzahl, für die noch eine Kreuztabelle entsteht.
     *
     * Eine Kreuztabelle wächst im Quadrat: Bei 250 Teilnehmern sind es 62.500
     * Felder, bei den 1031 Teilnehmern einer Schacholympiade über eine
     * Million. Die ließe sich weder aufbauen noch lesen. Anders als bei den
     * SWT-Dateien, wo Turniere mit mehr als fünfzig Teilnehmern die Ausnahme
     * sind, ist das bei Swiss-Manager der Regelfall.
     */
    private const KREUZTABELLE_HOECHSTENS = 250;

    /**
     * Gibt den Schlüssel des Formats zurück.
     *
     * @return string Der Schlüssel für Auswahl und Speicherung
     */
    public function getSchluessel(): string
    {
        return 'swissmanager';
    }

    /**
     * Gibt den Anzeigenamen des Formats zurück.
     *
     * @return string Der Name für die Ausgabe
     */
    public function getName(): string
    {
        return 'Swiss-Manager';
    }

    /**
     * Nennt die Dateiendungen des Swiss-Managers.
     *
     * Die vier Endungen unterscheiden die Turnierart: `tun` Schweizer System,
     * `tur` Rundenturnier, `tum` Mannschaft nach Schweizer System, `tut`
     * Mannschafts-Rundenturnier. Das angehängte `x` kennzeichnet die
     * Unicode-Fassung des Programms; die älteren ASCII-Fassungen kommen ohne
     * aus. Gelesen werden alle acht gleich — die Turnierart steht am Inhalt.
     *
     * @return string[] Die Endungen, klein geschrieben und ohne Punkt
     */
    public function getDateiendungen(): array
    {
        return ['tun', 'tunx', 'tur', 'turx', 'tum', 'tumx', 'tut', 'tutx'];
    }

    /**
     * Prüft, ob die Datei vom Swiss-Manager stammt.
     *
     * Geprüft wird der Inhalt, nicht die Endung: Turnierdateien werden häufig
     * umbenannt, und die Endungen sagen ohnehin nur etwas über die
     * Turnierart aus.
     *
     * @param string $dateiname Name der Datei; wird nicht ausgewertet
     * @param string $inhalt    Der Dateiinhalt
     *
     * @return bool Wahr, wenn die Datei gelesen werden kann
     */
    public function erkennt(string $dateiname, string $inhalt): bool
    {
        return SwissManagerFile::istSwissManager($inhalt);
    }

    /**
     * Liest die Datei ein und übersetzt sie ins Turniermodell.
     *
     * @param string $dateiname Name der Datei, für Fehlermeldungen
     * @param string $inhalt    Der vollständige Dateiinhalt
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn die Datei nicht auszuwerten ist
     */
    public function lese(string $dateiname, string $inhalt): Turnier
    {
        $datei = new SwissManagerFile($inhalt, $dateiname);
        $partien = $datei->getPartien();
        $spieler = $this->mitPunkten($datei->getSpieler(), $partien);
        $rangliste = $this->rangliste($spieler);
        $paarungen = $this->paarungen($spieler, $partien);
        $mannschaften = $datei->getMannschaften();

        return new Turnier(
            $this->getName(),
            $this->kopfdaten($datei->getTurnier(), $spieler, $mannschaften),
            $spieler,
            $mannschaften,
            $paarungen,
            $rangliste,
            $partien,
            \count($spieler) > self::KREUZTABELLE_HOECHSTENS ? null : Kreuztabelle::baue($rangliste, $paarungen),
            $this->hinweise($datei, \count($spieler)),
            $this->wettkaempfe($datei, $spieler, $paarungen, $mannschaften)
        );
    }

    /**
     * Baut die Wettkämpfe in der Form, die das Turniermodell erwartet.
     *
     * Die Datei nennt je Runde nur, wer gegen wen antrat. Brett- und
     * Mannschaftspunkte stehen nicht darin und werden aus den Einzelpartien
     * gebildet: Jede Partie zählt für die Mannschaft, der ihre Spieler
     * angehören. Die Mannschaftspunkte folgen der verbreiteten Regel zwei für
     * den Sieg, einen für das Unentschieden — welche Regel das Turnier
     * wirklich führte, steht in der Datei nicht.
     *
     * Jeder Wettkampf erscheint zweimal, einmal aus Sicht jeder Mannschaft;
     * so erwartet es das Modell.
     *
     * @param SwissManagerFile                          $datei        Die gelesene Datei
     * @param array<int,array<string,mixed>>            $spieler      Die Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $paarungen    Paarungen je Teilnehmer
     * @param array<int,array<string,mixed>>            $mannschaften Die Mannschaften
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Mannschaft und Runde
     */
    private function wettkaempfe(SwissManagerFile $datei, array $spieler, array $paarungen, array $mannschaften): array
    {
        $wettkaempfe = [];

        foreach ($datei->getWettkaempfe() as $runde => $liste) {
            foreach ($liste as $kampf) {
                $heim = (int) $kampf['heim'];
                $gast = (int) $kampf['gast'];
                $punkte = $this->brettpunkte($spieler, $paarungen, $mannschaften, $heim, $gast, (int) $runde);

                $wettkaempfe[$heim][$runde] = $this->wettkampfsatz($mannschaften, $gast, $punkte[0], $punkte[1], (int) $kampf['tisch']);
                $wettkaempfe[$gast][$runde] = $this->wettkampfsatz($mannschaften, $heim, $punkte[1], $punkte[0], (int) $kampf['tisch']);
            }
        }

        return $wettkaempfe;
    }

    /**
     * Summiert die Brettpunkte eines Wettkampfs aus den Einzelpartien.
     *
     * @param array<int,array<string,mixed>>            $spieler      Die Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $paarungen    Paarungen je Teilnehmer
     * @param array<int,array<string,mixed>>            $mannschaften Die Mannschaften
     * @param int                                       $heim         Nummer der ersten Mannschaft
     * @param int                                       $gast         Nummer der zweiten
     * @param int                                       $runde        Die Runde
     *
     * @return array{0:float,1:float} Brettpunkte der ersten und der zweiten Mannschaft
     */
    private function brettpunkte(array $spieler, array $paarungen, array $mannschaften, int $heim, int $gast, int $runde): array
    {
        $eigen = 0.0;
        $fremd = 0.0;

        foreach ([$heim, $gast] as $seite) {
            foreach ($mannschaften[$seite]['spieler'] ?? [] as $tnr) {
                $satz = $paarungen[(int) $tnr][$runde] ?? null;

                if (null === $satz || null === $satz['ergebnis']) {
                    continue;
                }

                $gegner = (int) $satz['gegner'];

                // Ohne Gegner steht eine kampflos entschiedene Partie da: Die
                // andere Seite hat das Brett nicht besetzt. Sie gehört zu
                // diesem Wettkampf, denn in dieser Runde spielt die
                // Mannschaft gegen keine andere. Ohne diesen Fall fehlten die
                // kampflosen Punkte in der Tabelle.
                $gehoertDazu = 0 === $gegner
                    || (int) ($spieler[$gegner]['mannschaftsnummer'] ?? 0) === ($seite === $heim ? $gast : $heim);

                if (!$gehoertDazu) {
                    continue;
                }

                // Jede Partie mit zwei Spielern steht auf beiden Seiten in den
                // Paarungen; sie darf deshalb nur von der Heimseite gezählt
                // werden. Kampflose Partien gibt es nur einmal.
                if (0 !== $gegner && $seite !== $heim) {
                    continue;
                }

                $ergebnis = (float) $satz['ergebnis'];

                if ($seite === $heim) {
                    $eigen += $ergebnis;
                    $fremd += 1.0 - $ergebnis;
                } else {
                    $fremd += $ergebnis;
                    $eigen += 1.0 - $ergebnis;
                }
            }
        }

        return [$eigen, $fremd];
    }

    /**
     * Baut einen Wettkampfsatz aus Sicht einer Mannschaft.
     *
     * @param array<int,array<string,mixed>> $mannschaften Die Mannschaften
     * @param int                            $gegner       Nummer der Gegenmannschaft
     * @param float                          $eigen        Eigene Brettpunkte
     * @param float                          $fremd        Brettpunkte der Gegenseite
     * @param int                            $tisch        Tischnummer
     *
     * @return array<string,mixed> Der Wettkampfsatz
     */
    private function wettkampfsatz(array $mannschaften, int $gegner, float $eigen, float $fremd, int $tisch): array
    {
        return [
            'gegner' => $gegner,
            'gegnerName' => (string) ($mannschaften[$gegner]['name'] ?? ''),
            'brettpunkte' => $eigen,
            'brettpunkteGegner' => $fremd,
            'mannschaftspunkte' => match (true) {
                $eigen > $fremd => 2.0,
                $eigen < $fremd => 0.0,
                default => 1.0,
            },
            'amGruenenTisch' => false,
            'tisch' => $tisch,
        ];
    }

    /**
     * Ergänzt die Kopfdaten um das, was das Turniermodell erwartet.
     *
     * @param array<string,mixed>            $kopf         Kopfdaten aus der Datei
     * @param array<int,array<string,mixed>> $spieler      Die Teilnehmer
     * @param array<int,array<string,mixed>> $mannschaften Die Mannschaften
     *
     * @return array<string,mixed> Kopfdaten für das Turniermodell
     */
    private function kopfdaten(array $kopf, array $spieler, array $mannschaften): array
    {
        return array_merge($kopf, [
            'teilnehmerzahl' => \count($spieler),
            'mannschaftszahl' => \count($mannschaften),
            'bretter' => $this->bretter($mannschaften),
            'modusText' => $kopf['mannschaftsturnier'] ? 'Mannschaftsturnier' : 'Einzelturnier',
            'feinwertung1Text' => '',
            'feinwertung2Text' => '',
            'feinwertungSicher' => true,
        ]);
    }

    /**
     * Schätzt die Zahl der Bretter je Mannschaftskampf.
     *
     * In der Datei steht sie nicht; sie ergibt sich aus der Aufstellung — die
     * höchste vergebene Brettnummer ist die Brettzahl. Ersatzspieler stehen
     * auf höheren Nummern, deshalb wird die häufigste Aufstellungsgröße
     * genommen und nicht die größte.
     *
     * @param array<int,array<string,mixed>> $mannschaften Die Mannschaften
     *
     * @return int Die Brettzahl, mindestens 1
     */
    private function bretter(array $mannschaften): int
    {
        if ([] === $mannschaften) {
            return 0;
        }

        $groessen = [];

        foreach ($mannschaften as $mannschaft) {
            $anzahl = \count($mannschaft['spieler'] ?? []);

            if ($anzahl > 0) {
                $groessen[$anzahl] = ($groessen[$anzahl] ?? 0) + 1;
            }
        }

        if ([] === $groessen) {
            return 1;
        }

        arsort($groessen);

        return max(1, (int) array_key_first($groessen));
    }

    /**
     * Rechnet Punkte und Bilanz aller Teilnehmer aus den Partien.
     *
     * Swiss-Manager speichert keine Punktzahlen; sie stehen in keiner
     * Karteikarte, sondern werden vom Programm bei jeder Anzeige neu
     * gebildet. Diese Klasse tut dasselbe.
     *
     * @param array<int,array<string,mixed>>            $spieler Die Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $partien Partien je Runde
     *
     * @return array<int,array<string,mixed>> Die Teilnehmer mit Punkten
     */
    private function mitPunkten(array $spieler, array $partien): array
    {
        foreach ($partien as $runde) {
            foreach ($runde as $partie) {
                $ergebnis = $partie['ergebnis'];

                if (null === $ergebnis) {
                    continue;
                }

                $weiss = $partie['weiss']['tnr'] ?? 0;
                $schwarz = $partie['schwarz']['tnr'] ?? 0;

                if (isset($spieler[$weiss])) {
                    $spieler[$weiss] = $this->buche($spieler[$weiss], (float) $ergebnis, null !== $partie['schwarz']);
                }

                if (isset($spieler[$schwarz])) {
                    $spieler[$schwarz] = $this->buche($spieler[$schwarz], 1.0 - (float) $ergebnis, true);
                }
            }
        }

        return $spieler;
    }

    /**
     * Schreibt ein Ergebnis in die Bilanz eines Teilnehmers.
     *
     * Eine spielfreie Runde bringt Punkte, zählt aber nicht als Partie: Sie
     * gehört nicht in die Bilanz aus Siegen, Remisen und Niederlagen.
     *
     * @param array<string,mixed> $spieler  Der Teilnehmer
     * @param float               $ergebnis Punkte aus seiner Sicht
     * @param bool                $gespielt Ob wirklich ein Gegner am Brett saß
     *
     * @return array<string,mixed> Der ergänzte Teilnehmer
     */
    private function buche(array $spieler, float $ergebnis, bool $gespielt): array
    {
        $spieler['punkte'] += $ergebnis;

        if (!$gespielt) {
            ++$spieler['ausgesetzt'];

            return $spieler;
        }

        ++$spieler['partien'];

        if ($ergebnis > 0.5) {
            ++$spieler['siege'];
        } elseif (0.5 === $ergebnis) {
            ++$spieler['remis'];
        } else {
            ++$spieler['niederlagen'];
        }

        return $spieler;
    }

    /**
     * Bildet die Rangliste aus den Punktzahlen.
     *
     * Sortiert wird nach Punkten, bei Gleichstand nach der Startnummer.
     * Feinwertungen gibt es nicht: In der Datei steht nicht, welche das
     * Turnier führt. Gleiche Punktzahlen bekommen deshalb denselben Platz —
     * eine Reihenfolge zu behaupten, für die es keine Grundlage gibt, wäre
     * eine Falschaussage.
     *
     * @param array<int,array<string,mixed>> $spieler Die Teilnehmer mit Punkten
     *
     * @return array<int,array<string,mixed>> Die Rangliste
     */
    private function rangliste(array $spieler): array
    {
        $liste = array_values($spieler);

        usort(
            $liste,
            static fn (array $a, array $b): int => [(float) $b['punkte'], -(int) $a['tnr']]
                <=> [(float) $a['punkte'], -(int) $b['tnr']]
        );

        $platz = 0;
        $vorher = null;

        foreach ($liste as $index => $satz) {
            if ((float) $satz['punkte'] !== $vorher) {
                $platz = $index + 1;
                $vorher = (float) $satz['punkte'];
            }

            $liste[$index]['platz'] = $platz;
        }

        return $liste;
    }

    /**
     * Baut die Paarungen je Teilnehmer und Runde.
     *
     * Das Turniermodell erwartet sie in der Form `[Teilnehmer][Runde]`, weil
     * Fortschrittstabelle und Kreuztabelle so rechnen. Die Datei liefert
     * dagegen eine Liste von Partien; hier wird sie aufgeteilt.
     *
     * @param array<int,array<string,mixed>>            $spieler Die Teilnehmer
     * @param array<int,array<int,array<string,mixed>>> $partien Partien je Runde
     *
     * @return array<int,array<int,array<string,mixed>>> Paarungen je Teilnehmer
     */
    private function paarungen(array $spieler, array $partien): array
    {
        $paarungen = [];

        foreach ($partien as $runde => $liste) {
            foreach ($liste as $partie) {
                $weiss = $partie['weiss']['tnr'] ?? 0;
                $schwarz = $partie['schwarz']['tnr'] ?? 0;
                $ergebnis = $partie['ergebnis'];

                if (isset($spieler[$weiss])) {
                    $paarungen[$weiss][$runde] = $this->satz($schwarz, $partie['schwarz'], 'Weiß', $ergebnis, $partie);
                }

                if (isset($spieler[$schwarz])) {
                    $paarungen[$schwarz][$runde] = $this->satz(
                        $weiss,
                        $partie['weiss'],
                        'Schwarz',
                        null === $ergebnis ? null : 1.0 - (float) $ergebnis,
                        $partie
                    );
                }
            }
        }

        return $paarungen;
    }

    /**
     * Baut einen einzelnen Paarungssatz aus Sicht eines Teilnehmers.
     *
     * @param int                      $gegner   Nummer des Gegners, 0 bei spielfrei
     * @param array<string,mixed>|null $angaben  Kurzangaben des Gegners
     * @param string                   $farbe    „Weiß" oder „Schwarz"
     * @param float|null               $ergebnis Punkte aus eigener Sicht
     * @param array<string,mixed>      $partie   Die Partie, für Tisch und Status
     *
     * @return array<string,mixed> Der Paarungssatz
     */
    private function satz(int $gegner, ?array $angaben, string $farbe, ?float $ergebnis, array $partie): array
    {
        return [
            'farbe' => $farbe,
            'gegner' => $gegner,
            'gegnerName' => (string) ($angaben['name'] ?? ''),
            'ergebnis' => $ergebnis,
            'ergebnisText' => match (true) {
                null === $ergebnis => '',
                1.0 === $ergebnis => '1',
                0.5 === $ergebnis => '½',
                default => '0',
            },
            'tisch' => $partie['tisch'],
            'brett' => $partie['brett'],
            'status' => $partie['status'],
        ];
    }

    /**
     * Stellt die Hinweise für den Betrachter zusammen.
     *
     * Neben den Meldungen des Lesers steht dort immer der Hinweis, dass
     * Punkte und Platzierungen nachgerechnet sind. Wer sie neben einer
     * Ausgabe von chess-results sieht, soll wissen, woher sie kommen.
     *
     * @param SwissManagerFile $datei   Die gelesene Datei
     * @param int              $spieler Zahl der Teilnehmer
     *
     * @return string[] Die Hinweise
     */
    private function hinweise(SwissManagerFile $datei, int $spieler): array
    {
        $hinweise = $datei->getHinweise();

        if ([] !== $datei->getPartien()) {
            $hinweise[] = 'Swiss-Manager speichert weder Punkte noch Feinwertungen; beide werden hier aus den Partien gebildet. Bei Punktgleichheit steht deshalb derselbe Platz, denn welche Feinwertung das Turnier führt, steht nicht in der Datei.';
        }

        if ($spieler > self::KREUZTABELLE_HOECHSTENS) {
            $hinweise[] = sprintf(
                'Bei %d Teilnehmern entfällt die Kreuztabelle: Sie hätte %s Felder.',
                $spieler,
                number_format($spieler ** 2, 0, ',', '.')
            );
        }

        return $hinweise;
    }
}
