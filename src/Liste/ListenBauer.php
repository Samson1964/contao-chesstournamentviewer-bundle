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
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Rundenschnitt;
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
     * Ist ein Rundenstand gewählt, wird das Turnier vorher zurückversetzt —
     * einmal für alle Listen, damit Tabelle, Kreuztabelle und
     * Fortschrittstabelle denselben Zeitpunkt zeigen.
     *
     * @param Turnier $turnier  Das eingelesene Turnier
     * @param Auswahl $auswahl  Die Einstellungen des Inhaltselements
     *
     * @return array<int,array{schluessel:string,name:string,template:string,daten:array<string,mixed>}>
     *         Die Listen in der vom Redakteur festgelegten Reihenfolge; wo
     *         diese fehlt, in der Reihenfolge des Verzeichnisses
     */
    public function baue(Turnier $turnier, Auswahl $auswahl): array
    {
        if ($auswahl->stand > 0) {
            $turnier = Rundenschnitt::bis($turnier, $auswahl->stand);
        }

        $listen = [];

        foreach ($this->reihenfolge($auswahl->listen) as $schluessel) {
            if (!Listen::passt($schluessel, $turnier)) {
                continue;
            }

            $daten = $this->daten($schluessel, $turnier, $auswahl);

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
     * Bringt die gewählten Listen in ihre Ausgabereihenfolge.
     *
     * Das Auswahlfeld im Backend lässt sich sortieren; die gespeicherte
     * Reihenfolge ist deshalb die des Redakteurs und wird übernommen. Was
     * dabei durchfällt — unbekannte Schlüssel aus einer älteren Fassung —
     * wird verworfen, und Listen ohne festgelegte Stellung folgen in der
     * Reihenfolge des Verzeichnisses.
     *
     * @param string[] $gewaehlt Die gewählten Schlüssel in gespeicherter Ordnung
     *
     * @return string[] Die gültigen Schlüssel in Ausgabereihenfolge
     */
    private function reihenfolge(array $gewaehlt): array
    {
        $bekannt = Listen::schluessel();
        $sortiert = array_values(array_filter(
            $gewaehlt,
            static fn (mixed $schluessel): bool => \is_string($schluessel) && \in_array($schluessel, $bekannt, true)
        ));

        foreach ($bekannt as $schluessel) {
            if (\in_array($schluessel, $gewaehlt, true) && !\in_array($schluessel, $sortiert, true)) {
                $sortiert[] = $schluessel;
            }
        }

        return array_unique($sortiert);
    }

    /**
     * Ermittelt die Daten einer einzelnen Liste.
     *
     * @param string  $schluessel Schlüssel der Liste
     * @param Turnier $turnier    Das eingelesene Turnier, gegebenenfalls
     *                            bereits auf den gewählten Stand zurückversetzt
     * @param Auswahl $auswahl    Die Einstellungen des Inhaltselements
     *
     * @return array<string,mixed> Die Daten für das Template, oder ein leeres
     *                             Array, wenn die Liste nichts zu zeigen hat
     */
    private function daten(string $schluessel, Turnier $turnier, Auswahl $auswahl): array
    {
        return match ($schluessel) {
            'turnierdaten' => $this->turnierdaten($turnier),
            'teilnehmer' => $this->teilnehmer($turnier),
            'rangliste' => $this->rangliste($turnier),
            'kreuztabelle' => $this->kreuztabelle($turnier),
            'fortschritt' => $this->fortschritt($turnier, true),
            'fortschrittohne' => $this->fortschritt($turnier, false),
            'paarungen', 'ergebnisse' => $this->runden($turnier, 'ergebnisse' === $schluessel, $auswahl),
            'mannschaften' => $this->mannschaften($turnier, $auswahl->mitSpielern),
            'mannschaftsrangliste' => $this->schluessellos('mannschaften', Mannschaftswertung::tabelle($turnier)),
            'mannschaftspaarungen' => $this->mannschaftspaarungen($turnier, $auswahl),
            'mannschaftskreuztabelle' => $this->mannschaftskreuztabelle($turnier, $auswahl->kreuzKurz),
            default => [],
        };
    }

    /**
     * Stellt die Teilnehmerliste zusammen.
     *
     * Bei einem Mannschaftsturnier wird nach Mannschaften gegliedert: je
     * Mannschaft eine Kopfzeile mit Startnummer und Name, darunter deren
     * Spieler nach Brett. Eine durchgehende Liste mit einer Spalte
     * „Mannschaft" wäre bei zweihundert Teilnehmern kaum zu lesen, und die
     * Aufstellung ist genau das, was an einem Mannschaftsturnier interessiert.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array<string,mixed> Unter `teilnehmer` die flache Liste, bei
     *                             Mannschaftsturnieren zusätzlich `gruppen`
     */
    private function teilnehmer(Turnier $turnier): array
    {
        $teilnehmer = $turnier->getTeilnehmer();

        if ([] === $teilnehmer) {
            return [];
        }

        return [
            'teilnehmer' => $teilnehmer,
            'gruppen' => $turnier->istMannschaftsturnier() ? $this->nachMannschaften($turnier, $teilnehmer, true) : [],
        ];
    }

    /**
     * Gliedert eine Teilnehmerliste nach Mannschaften.
     *
     * Mannschaften ohne einen einzigen Spieler in der übergebenen Liste
     * fallen heraus; ebenso die Platzhaltermannschaft. Spieler ohne
     * Mannschaftszugehörigkeit — in gemischten Turnieren kommt das vor —
     * landen in einer Gruppe ohne Namen am Ende, damit niemand verschwindet.
     *
     * @param Turnier                        $turnier    Das eingelesene Turnier
     * @param array<int,array<string,mixed>> $spieler    Die zu gliedernde Liste
     * @param bool                           $nachBrett  Ob innerhalb der Mannschaft
     *                                                   nach Brett sortiert wird;
     *                                                   sonst bleibt die Reihenfolge
     *
     * @return array<int,array{nummer:int,name:string,startnummer:int,spieler:array<int,array<string,mixed>>}>
     */
    private function nachMannschaften(Turnier $turnier, array $spieler, bool $nachBrett): array
    {
        $mannschaften = $turnier->getMannschaften();
        $gruppen = [];

        foreach ($spieler as $einzelner) {
            $nummer = (int) ($einzelner['mannschaftsnummer'] ?? 0);

            if (!isset($mannschaften[$nummer]) || ($mannschaften[$nummer]['spielfrei'] ?? false)) {
                $nummer = 0;
            }

            $gruppen[$nummer][] = $einzelner;
        }

        // Ohne Mannschaft ans Ende: PHP sortiert die 0 sonst nach vorn.
        $ohne = $gruppen[0] ?? null;
        unset($gruppen[0]);
        ksort($gruppen);

        if (null !== $ohne) {
            $gruppen[0] = $ohne;
        }

        $ergebnis = [];

        foreach ($gruppen as $nummer => $liste) {
            if ($nachBrett) {
                usort($liste, static fn (array $a, array $b): int => [(int) $a['brett'] ?: PHP_INT_MAX, (int) $a['tnr']] <=> [(int) $b['brett'] ?: PHP_INT_MAX, (int) $b['tnr']]);
            }

            $mannschaft = $mannschaften[$nummer] ?? [];

            $ergebnis[] = [
                'nummer' => (int) $nummer,
                'name' => (string) ($mannschaft['name'] ?? ''),
                'startnummer' => (int) ($mannschaft['startnummer'] ?? 0) ?: (int) $nummer,
                'spieler' => $liste,
            ];
        }

        return $ergebnis;
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

        [$feinwertung1, $feinwertung2] = $this->feinwertungsspalten($turnier, $rangliste);

        return [
            'spieler' => $rangliste,
            'gruppen' => $turnier->istMannschaftsturnier() ? $this->nachMannschaften($turnier, $rangliste, false) : [],
            'feinwertung1' => $feinwertung1,
            'feinwertung2' => $feinwertung2,
            'mannschaftsspalte' => $turnier->istMannschaftsturnier(),
        ];
    }

    /**
     * Ermittelt die Spaltenköpfe der beiden Feinwertungen.
     *
     * Im Spaltenkopf steht die Kurzform der Feinwertung, damit die
     * Zahlenspalte nicht durch ihre Überschrift breit wird; der volle Name
     * geht als Titel mit und erscheint beim Überfahren. Fehlt die Bezeichnung
     * — weil in der Datei keine eingestellt ist, die Zahlen aber dennoch
     * belegt sind —, springt eine allgemeine Beschriftung ein; eine namenlose
     * Zahlenspalte wäre nicht einzuordnen.
     *
     * Eine Feinwertung, die in keiner Zeile einen Wert trägt, bekommt keine
     * Spalte.
     *
     * @param Turnier                        $turnier Das eingelesene Turnier
     * @param array<int,array<string,mixed>> $zeilen  Die Teilnehmerzeilen
     *
     * @return array{0:array{name:string,titel:string}|null,1:array{name:string,titel:string}|null}
     */
    private function feinwertungsspalten(Turnier $turnier, array $zeilen): array
    {
        $zusatz = ($turnier->kopf('feinwertungSicher', true) ? '' : ' '.($GLOBALS['TL_LANG']['ctv']['unsicher'] ?? '(Bezeichnung unsicher)'));
        $spalten = [];

        foreach ([1, 2] as $nummer) {
            $benutzt = false;

            foreach ($zeilen as $zeile) {
                if (!empty($zeile['feinwertung'.$nummer])) {
                    $benutzt = true;

                    break;
                }
            }

            if (!$benutzt) {
                $spalten[] = null;

                continue;
            }

            $name = trim((string) $turnier->kopf('feinwertung'.$nummer.'Text', ''));

            if ('' === $name) {
                $allgemein = (string) ($GLOBALS['TL_LANG']['ctv']['spalte']['feinwertung'.$nummer] ?? 'Feinwertung '.$nummer);
                $spalten[] = ['name' => $allgemein, 'titel' => $allgemein];

                continue;
            }

            $spalten[] = ['name' => Ausgabe::feinwertungKurz($name), 'titel' => $name.$zusatz];
        }

        return $spalten;
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

        [$feinwertung1, $feinwertung2] = $this->feinwertungsspalten($turnier, $tabelle['spieler']);

        return [
            'spieler' => $tabelle['spieler'],
            'zeilen' => $tabelle['zeilen'],
            'anzahl' => \count($tabelle['spieler']),
            'feinwertung1' => $feinwertung1,
            'feinwertung2' => $feinwertung2,
        ];
    }

    /**
     * Bereitet die Fortschrittstabelle auf.
     *
     * Es gibt sie in zwei Fassungen: mit dem laufenden Punktestand unter
     * jedem Rundenergebnis und ohne ihn. Die zweite ist die schmalere und
     * für den bloßen Verlauf oft die bessere; welche gebraucht wird, hängt
     * vom Turnier ab und wird deshalb im Backend gewählt statt festgelegt.
     *
     * @param Turnier $turnier  Das eingelesene Turnier
     * @param bool    $mitStand Ob der laufende Punktestand mitgeht
     *
     * @return array<string,mixed> Unter `zeilen` die Tabellenzeilen, unter
     *                             `runden` die Rundennummern für den Tabellenkopf
     */
    private function fortschritt(Turnier $turnier, bool $mitStand): array
    {
        $zeilen = Fortschritt::zeilen($turnier);

        if ([] === $zeilen) {
            return [];
        }

        $runden = array_keys($turnier->getRunden());
        sort($runden);

        return ['zeilen' => $zeilen, 'runden' => $runden, 'mitStand' => $mitStand];
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
    private function runden(Turnier $turnier, bool $mitErgebnissen, Auswahl $auswahl): array
    {
        $runden = array_filter(
            $turnier->getRunden(),
            static fn (int $runde): bool => $auswahl->zeigtRunde($runde),
            ARRAY_FILTER_USE_KEY
        );

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
            // Bei einem Mannschaftsturnier steht statt einer flachen Liste
            // aller Partien je Wettkampf eine Kopfzeile mit den beiden
            // Mannschaften, darunter die Bretter. Eine Runde einer
            // Betriebsmeisterschaft hat sonst hundert Zeilen ohne jede
            // Gliederung.
            'kaempfe' => $turnier->istMannschaftsturnier() ? $this->kaempfe($turnier, $auswahl) : [],
            'hoechstwert' => (float) $turnier->getPartienProRunde(),
        ];
    }

    /**
     * Holt die Wettkämpfe und lässt die abgewählten Runden weg.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param Auswahl $auswahl Die Einstellungen des Inhaltselements
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Runde
     */
    private function kaempfe(Turnier $turnier, Auswahl $auswahl): array
    {
        return array_filter(
            Mannschaftswertung::kaempfe($turnier),
            static fn (int $runde): bool => $auswahl->zeigtRunde($runde),
            ARRAY_FILTER_USE_KEY
        );
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
            // Die Platzhaltermannschaft für Freilose gehört nicht in die
            // Liste; das Format kennzeichnet sie selbst.
            if ($mannschaft['spielfrei'] ?? false) {
                continue;
            }

            $zeilen[] = [
                'nummer' => $nummer,
                'mannschaft' => $mannschaft,
                'anzahl' => \count($aufstellungen[$nummer] ?? []),
                'schnitt' => Mannschaftswertung::wertungsschnitt($turnier->getSpieler(), array_column($aufstellungen[$nummer] ?? [], 'tnr')),
                'spieler' => $mitSpielern ? ($aufstellungen[$nummer] ?? []) : [],
            ];
        }

        return [] === $zeilen ? [] : ['mannschaften' => $zeilen, 'mitSpielern' => $mitSpielern];
    }

    /**
     * Bereitet die Wettkämpfe der Mannschaften auf.
     *
     * @param Turnier $turnier     Das eingelesene Turnier
     * @param bool    $mitSpielern Ob die Einzelpartien der Wettkämpfe mitgehen
     *
     * @return array<string,mixed> Unter `runden` die Wettkämpfe je Runde
     */
    private function mannschaftspaarungen(Turnier $turnier, Auswahl $auswahl): array
    {
        $kaempfe = $this->kaempfe($turnier, $auswahl);

        if ([] === $kaempfe) {
            return [];
        }

        return [
            'runden' => $kaempfe,
            'mitSpielern' => $auswahl->mitSpielern,
            'hoechstwert' => (float) $turnier->getPartienProRunde(),
        ];
    }

    /**
     * Bereitet die Kreuztabelle der Mannschaften auf.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     * @param bool    $kurz    Ob in jeder Zelle nur die eigenen Brettpunkte
     *                         stehen statt beider Seiten
     *
     * @return array<string,mixed> Unter `mannschaften`, `zeilen` und `anzahl`
     */
    private function mannschaftskreuztabelle(Turnier $turnier, bool $kurz): array
    {
        $tabelle = Mannschaftswertung::kreuztabelle($turnier, $kurz);

        if ([] === $tabelle['mannschaften']) {
            return [];
        }

        return [
            'mannschaften' => $tabelle['mannschaften'],
            'zeilen' => $tabelle['zeilen'],
            'anzahl' => \count($tabelle['mannschaften']),
            'kurz' => $kurz,
        ];
    }
}
