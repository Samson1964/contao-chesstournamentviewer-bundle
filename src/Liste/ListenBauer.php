<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Fortschritt;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Stellt die Daten für die einzelnen Listen zusammen.
 *
 * Die Klasse steht zwischen Turniermodell und Templates: Sie holt aus dem
 * Turnier genau das, was eine Liste braucht, und reicht es in einer Form
 * weiter, die im Template ohne Rechnerei auskommt. Umgekehrt bleiben die
 * Berechnungen aus den Templates heraus, wo sie beim Überschreiben eines
 * Templates verlorengingen.
 */
class ListenBauer
{
    /**
     * Baut alle gewünschten Listen für ein Turnier.
     *
     * Listen, die zum Turnier nicht passen — Mannschaftslisten bei einem
     * Einzelturnier — werden übergangen, ebenso Listen ohne Inhalt. Ein
     * leerer Reiter ist kein Gewinn: Wer eine Kreuztabelle bestellt, bei
     * einem noch nicht ausgelosten Turnier aber keine bekommen kann, soll
     * lieber gar keinen Reiter sehen.
     *
     * @param Turnier  $turnier       Das eingelesene Turnier
     * @param string[] $gewaehlt      Die im Inhaltselement gewählten Listen
     * @param bool     $mitSpielern   Ob bei Mannschaftslisten die Einzelpartien
     *                                und Aufstellungen mit ausgegeben werden
     *
     * @return array<int,array{schluessel:string,name:string,template:string,daten:array<string,mixed>}>
     *         Die Listen in der Reihenfolge des Verzeichnisses, nicht in der
     *         Reihenfolge der Auswahl — die Auswahl ist ein Kästchensatz und
     *         hat keine verlässliche Ordnung
     */
    public function baue(Turnier $turnier, array $gewaehlt, bool $mitSpielern = false): array
    {
        $listen = [];

        foreach (Listen::schluessel() as $schluessel) {
            if (!\in_array($schluessel, $gewaehlt, true) || !Listen::passt($schluessel, $turnier)) {
                continue;
            }

            $daten = $this->daten($schluessel, $turnier, $mitSpielern);

            if ([] === $daten) {
                continue;
            }

            $listen[] = [
                'schluessel' => $schluessel,
                'name' => Listen::beschriftung($schluessel),
                'template' => Listen::template($schluessel),
                'daten' => $daten,
            ];
        }

        return $listen;
    }

    /**
     * Ermittelt die Daten einer einzelnen Liste.
     *
     * @param string  $schluessel  Schlüssel der Liste
     * @param Turnier $turnier     Das eingelesene Turnier
     * @param bool    $mitSpielern Ob Aufstellungen und Einzelpartien mitgehen
     *
     * @return array<string,mixed> Die Daten für das Template, oder ein leeres
     *                             Array, wenn die Liste nichts zu zeigen hat
     */
    private function daten(string $schluessel, Turnier $turnier, bool $mitSpielern): array
    {
        return match ($schluessel) {
            'turnierdaten' => $this->turnierdaten($turnier),
            'teilnehmer' => $this->schluessellos('teilnehmer', $turnier->getTeilnehmer()),
            'rangliste' => $this->rangliste($turnier),
            'kreuztabelle' => $this->kreuztabelle($turnier),
            'fortschritt' => $this->fortschritt($turnier),
            'paarungen', 'ergebnisse' => $this->runden($turnier, 'ergebnisse' === $schluessel),
            'mannschaften' => $this->mannschaften($turnier, $mitSpielern),
            'mannschaftsrangliste' => $this->schluessellos('mannschaften', Mannschaftswertung::tabelle($turnier)),
            'mannschaftspaarungen' => $this->mannschaftspaarungen($turnier, $mitSpielern),
            'mannschaftskreuztabelle' => $this->mannschaftskreuztabelle($turnier),
            default => [],
        };
    }

    /**
     * Verpackt eine einfache Liste unter einem Schlüssel.
     *
     * Templates bekommen immer ein assoziatives Array, damit sie auf benannte
     * Werte zugreifen können. Diese Hilfsmethode erspart den immer gleichen
     * Dreizeiler samt Leerprüfung.
     *
     * @param string             $name    Name, unter dem die Liste im Template steht
     * @param array<int,mixed>   $eintraege Die Einträge
     *
     * @return array<string,mixed> Das verpackte Array, leer wenn es keine Einträge gibt
     */
    private function schluessellos(string $name, array $eintraege): array
    {
        return [] === $eintraege ? [] : [$name => $eintraege];
    }

    /**
     * Stellt die Turnierdaten als beschriftete Wertepaare zusammen.
     *
     * Ausgegeben wird eine feste Auswahl von Feldern in fester Reihenfolge,
     * nicht alles, was das Format hergibt: Der Betrachter soll die Angaben
     * eines Turnieraushangs sehen, keine Feldliste. Leere Felder fallen
     * heraus, damit bei knapp gepflegten Dateien keine leeren Zeilen stehen.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `angaben` eine Liste aus `label` und `wert`
     */
    private function turnierdaten(Turnier $turnier): array
    {
        $kopf = $turnier->getKopf();
        $angaben = [];

        // Feld im Kopf => ob es nur bei Mannschaftsturnieren gezeigt wird
        $felder = [
            'name' => false,
            'untertitel' => false,
            'ort' => false,
            'zeitraum' => false,
            'modusText' => false,
            'runden' => false,
            'teilnehmerzahl' => false,
            'mannschaftszahl' => true,
            'bretter' => true,
            'zeitkontrolle' => false,
            'feinwertung1Text' => false,
            'feinwertung2Text' => false,
            'twzErmittlungText' => false,
            'schiedsrichter' => false,
            'organisator' => false,
            'bemerkung' => false,
            'format' => false,
        ];

        foreach ($felder as $feld => $nurMannschaft) {
            if ($nurMannschaft && !$turnier->istMannschaftsturnier()) {
                continue;
            }

            $wert = match ($feld) {
                'zeitraum' => $this->zeitraum($turnier),
                'format' => $turnier->getFormat(),
                default => $kopf[$feld] ?? null,
            };

            if (\is_array($wert)) {
                $wert = implode(', ', array_filter($wert));
            }

            $wert = trim((string) $wert);

            if ('' === $wert || '0' === $wert) {
                continue;
            }

            // Die Feinwertungsbezeichnung ist in alten Dateien nicht
            // verlässlich; der Zusatz sagt das, statt eine falsche Angabe
            // wortlos stehen zu lassen.
            if (\in_array($feld, ['feinwertung1Text', 'feinwertung2Text'], true) && !($kopf['feinwertungSicher'] ?? true)) {
                $wert .= ' '.($GLOBALS['TL_LANG']['ctv']['unsicher'] ?? '(Bezeichnung unsicher)');
            }

            $angaben[] = [
                'feld' => $feld,
                'label' => $GLOBALS['TL_LANG']['ctv']['turnierdaten'][$feld] ?? $feld,
                'wert' => $wert,
            ];
        }

        return [] === $angaben ? [] : ['angaben' => $angaben];
    }

    /**
     * Setzt Anfangs- und Enddatum zu einer Zeitangabe zusammen.
     *
     * Bei eintägigen Turnieren steht das Datum nur einmal. Fehlt eines der
     * beiden Felder, wird das vorhandene genommen.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return string Der Zeitraum als Text, leer wenn kein Datum hinterlegt ist
     */
    private function zeitraum(Turnier $turnier): string
    {
        $von = trim((string) $turnier->kopf('datumStart', ''));
        $bis = trim((string) $turnier->kopf('datumEnde', ''));

        if ('' === $von) {
            return $bis;
        }

        if ('' === $bis || $von === $bis) {
            return $von;
        }

        return $von.' – '.$bis;
    }

    /**
     * Stellt die Rangliste samt Spaltenbeschriftungen zusammen.
     *
     * Die Feinwertungsspalten tragen die Bezeichnung aus der Turnierdatei
     * („Buchholzwertung", „Sonneborn-Berger"), damit die Zahlen einzuordnen
     * sind. Eine Feinwertung, die in keiner Zeile einen Wert trägt, bekommt
     * keine Spalte.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `spieler` die Rangliste, unter
     *                             `feinwertung1`/`feinwertung2` die
     *                             Spaltenbeschriftungen oder null
     */
    private function rangliste(Turnier $turnier): array
    {
        $rangliste = $turnier->getRangliste();

        if ([] === $rangliste) {
            return [];
        }

        $benutzt = static function (array $zeilen, string $feld): bool {
            foreach ($zeilen as $zeile) {
                if (!empty($zeile[$feld])) {
                    return true;
                }
            }

            return false;
        };

        $zusatz = ($turnier->kopf('feinwertungSicher', true) ? '' : ' '.($GLOBALS['TL_LANG']['ctv']['unsicher'] ?? '(Bezeichnung unsicher)'));

        // Die Spaltenüberschrift ist die Bezeichnung der Feinwertung. Fehlt
        // sie — weil in der Datei keine eingestellt ist, die Zahlen aber
        // dennoch belegt sind —, springt eine allgemeine Beschriftung ein;
        // eine namenlose Zahlenspalte wäre nicht einzuordnen.
        $spaltenname = static function (Turnier $turnier, int $nummer) use ($zusatz): string {
            $name = trim((string) $turnier->kopf('feinwertung'.$nummer.'Text', ''));

            if ('' === $name) {
                return (string) ($GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung'.$nummer] ?? 'Feinwertung '.$nummer);
            }

            return $name.$zusatz;
        };

        return [
            'spieler' => $rangliste,
            'feinwertung1' => $benutzt($rangliste, 'feinwertung1') ? $spaltenname($turnier, 1) : null,
            'feinwertung2' => $benutzt($rangliste, 'feinwertung2') ? $spaltenname($turnier, 2) : null,
            'mannschaftsspalte' => $turnier->istMannschaftsturnier(),
        ];
    }

    /**
     * Bereitet die Kreuztabelle auf.
     *
     * Bei Schweizer Systemen mit vielen Teilnehmern ist die Tabelle fast leer
     * — bei 100 Teilnehmern und neun Runden sind von 10.000 Feldern 900
     * belegt. Ausgegeben wird sie trotzdem, wenn sie bestellt wurde; das
     * Template macht sie waagerecht scrollbar. Die Zahl der Teilnehmer geht
     * aber mit, damit ein eigenes Template darauf reagieren kann.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `spieler`, `zeilen` und `anzahl`
     */
    private function kreuztabelle(Turnier $turnier): array
    {
        $tabelle = $turnier->getKreuztabelle();

        if (null === $tabelle || [] === $tabelle['spieler']) {
            return [];
        }

        return [
            'spieler' => $tabelle['spieler'],
            'zeilen' => $tabelle['zeilen'],
            'anzahl' => \count($tabelle['spieler']),
        ];
    }

    /**
     * Bereitet die Fortschrittstabelle auf.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `zeilen` die Tabellenzeilen, unter
     *                             `runden` die Rundennummern für den Tabellenkopf
     */
    private function fortschritt(Turnier $turnier): array
    {
        $zeilen = Fortschritt::zeilen($turnier);

        if ([] === $zeilen) {
            return [];
        }

        $runden = array_keys($turnier->getRunden());
        sort($runden);

        return ['zeilen' => $zeilen, 'runden' => $runden];
    }

    /**
     * Bereitet Paarungs- und Ergebnisliste auf.
     *
     * Beide zeigen dieselben Partien; die Paarungsliste lässt die
     * Ergebnisspalte weg. Getrennt sind sie, weil eine Auslosung vor der
     * Runde ohne Ergebnisspalte gedruckt wird und mit ihr hinterher.
     *
     * @param Turnier $turnier       Das eingelesene Turnier
     * @param bool    $mitErgebnissen Ob die Ergebnisspalte ausgegeben wird
     *
     * @return array<string,mixed> Unter `runden` die Partien je Runde
     */
    private function runden(Turnier $turnier, bool $mitErgebnissen): array
    {
        $runden = $turnier->getRunden();

        if ([] === $runden) {
            return [];
        }

        // Swiss-Chess führt je nach Turnierart die Tischnummer oder die
        // Brettnummer. Bei Mannschaftsturnieren steht in den Einzelpaarungen
        // die Brettnummer und die Tischnummer bleibt durchweg null — dann
        // wäre eine Spalte voller Nullen die falsche Wahl.
        $spalte = 'brett';

        foreach ($runden as $partien) {
            foreach ($partien as $partie) {
                if ((int) ($partie['tisch'] ?? 0) > 0) {
                    $spalte = 'tisch';

                    break 2;
                }
            }
        }

        return [
            'runden' => $runden,
            'mitErgebnissen' => $mitErgebnissen,
            'mannschaftsturnier' => $turnier->istMannschaftsturnier(),
            'spalte' => $spalte,
        ];
    }

    /**
     * Stellt die Mannschaften mit ihren Aufstellungen zusammen.
     *
     * Die Spieler einer Mannschaft stehen nach Brett sortiert. Ohne die
     * Einstellung „mit Spielern" bleibt die Aufstellung leer und es erscheint
     * nur die Mannschaftsliste — bei einer Betriebsmeisterschaft mit 38
     * Mannschaften und 209 Teilnehmern ist das der Unterschied zwischen einer
     * Übersicht und einer Namensflut.
     *
     * @param Turnier $turnier     Das eingelesene Turnier
     * @param bool    $mitSpielern Ob die Aufstellungen mit ausgegeben werden
     *
     * @return array<string,mixed> Unter `mannschaften` die Liste
     */
    private function mannschaften(Turnier $turnier, bool $mitSpielern): array
    {
        $mannschaften = $turnier->getMannschaften();

        if ([] === $mannschaften) {
            return [];
        }

        // Die Aufstellungen werden immer gebildet, auch wenn sie nicht
        // ausgegeben werden: Ihre Länge ist die verlässlichere Spielerzahl.
        // Das gleichnamige Feld der Mannschaftskarteikarte enthält in alten
        // Dateiformaten Unsinnswerte.
        $aufstellungen = [];

        foreach ($turnier->getSpieler() as $spieler) {
            if ($spieler['spielfrei'] ?? false) {
                continue;
            }

            $nummer = (int) ($spieler['mannschaftsnummer'] ?? 0);

            if ($nummer > 0) {
                $aufstellungen[$nummer][] = $spieler;
            }
        }

        foreach ($aufstellungen as $nummer => $spielerliste) {
            usort($spielerliste, static fn (array $a, array $b): int => ((int) $a['brett']) <=> ((int) $b['brett']));
            $aufstellungen[$nummer] = $spielerliste;
        }

        $zeilen = [];

        foreach ($mannschaften as $nummer => $mannschaft) {
            $name = trim((string) ($mannschaft['name'] ?? ''));

            // Die Platzhaltermannschaft für Freilose gehört nicht in die Liste.
            if ('' === $name || 'spielfrei' === mb_strtolower($name)) {
                continue;
            }

            $zeilen[] = [
                'nummer' => $nummer,
                'mannschaft' => $mannschaft,
                'anzahl' => \count($aufstellungen[$nummer] ?? []),
                'schnitt' => $this->wertungsschnitt($aufstellungen[$nummer] ?? []),
                'spieler' => $mitSpielern ? ($aufstellungen[$nummer] ?? []) : [],
            ];
        }

        return [] === $zeilen ? [] : ['mannschaften' => $zeilen, 'mitSpielern' => $mitSpielern];
    }

    /**
     * Errechnet die durchschnittliche Turnierwertungszahl einer Aufstellung.
     *
     * Gerechnet wird über die Spieler, nicht über die Angabe auf der
     * Mannschaftskarteikarte: Diese steht in alten Dateiformaten an einer
     * anderen Adresse und liefert dort unbrauchbare Werte. Spieler ohne
     * Wertungszahl bleiben außen vor — sie würden den Schnitt nach unten
     * ziehen, obwohl über ihre Stärke nichts bekannt ist.
     *
     * @param array<int,array<string,mixed>> $spieler Die Aufstellung
     *
     * @return int Der Schnitt, oder 0 wenn kein Spieler eine Wertungszahl hat
     */
    private function wertungsschnitt(array $spieler): int
    {
        $werte = [];

        foreach ($spieler as $einzelner) {
            $twz = (int) ($einzelner['twz'] ?? 0);

            if ($twz > 0) {
                $werte[] = $twz;
            }
        }

        return [] === $werte ? 0 : (int) round(array_sum($werte) / \count($werte));
    }

    /**
     * Bereitet die Wettkämpfe der Mannschaften auf.
     *
     * @param Turnier $turnier     Das eingelesene Turnier
     * @param bool    $mitSpielern Ob die Einzelpartien der Wettkämpfe mitgehen
     *
     * @return array<string,mixed> Unter `runden` die Wettkämpfe je Runde
     */
    private function mannschaftspaarungen(Turnier $turnier, bool $mitSpielern): array
    {
        $kaempfe = Mannschaftswertung::kaempfe($turnier);

        if ([] === $kaempfe) {
            return [];
        }

        return ['runden' => $kaempfe, 'mitSpielern' => $mitSpielern];
    }

    /**
     * Bereitet die Kreuztabelle der Mannschaften auf.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `mannschaften`, `zeilen` und `anzahl`
     */
    private function mannschaftskreuztabelle(Turnier $turnier): array
    {
        $tabelle = Mannschaftswertung::kreuztabelle($turnier);

        if ([] === $tabelle['mannschaften']) {
            return [];
        }

        return [
            'mannschaften' => $tabelle['mannschaften'],
            'zeilen' => $tabelle['zeilen'],
            'anzahl' => \count($tabelle['mannschaften']),
        ];
    }
}
