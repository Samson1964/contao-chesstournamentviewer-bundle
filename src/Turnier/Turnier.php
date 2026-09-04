<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

/**
 * Ein eingelesenes Turnier, unabhängig vom Dateiformat.
 *
 * Diese Klasse ist ein reiner Datenträger. Sie rechnet nichts aus und weiß
 * nichts über SWT, PGN oder sonstige Formate — das ist Aufgabe der
 * Format-Adapter, die ein solches Objekt füllen. Damit lassen sich weitere
 * Turnierformate ergänzen, ohne die Listen und Templates anzufassen.
 *
 * Zwei Festlegungen sind für alle Adapter bindend, weil die Listen darauf
 * bauen:
 *
 *   1. `$spieler` ist nach Teilnehmernummer indiziert, `$mannschaften`
 *      lückenlos ab 1 nach Mannschaftsnummer. Die Mannschaftsnummer ist die
 *      Zahl, die auch in `$spieler[...]['mannschaftsnummer']` steht.
 *   2. Platzhalterteilnehmer („spielfrei"), die manche Verwaltungsprogramme
 *      bei ungerader Teilnehmerzahl anlegen, sind bereits aussortiert oder
 *      mit `spielfrei => true` gekennzeichnet.
 */
final class Turnier
{
    /**
     * Erzeugt das Turnierobjekt.
     *
     * Alle Angaben werden vom Format-Adapter fertig ausgewertet übergeben.
     * Der Konstruktor prüft sie nicht nach: eine unvollständige Liste würde
     * sich in der Ausgabe zeigen, nicht in einer Ausnahme, und ein halb
     * lesbares Turnier ist besser als gar keines.
     *
     * @param string                                  $format        Bezeichnung des Quellformats, etwa „SWT (Swiss-Chess)"
     * @param array<string,mixed>                     $kopf          Turnierdaten, Schlüssel siehe getKopf()
     * @param array<int,array<string,mixed>>          $spieler       Teilnehmer, Schlüssel ist die Teilnehmernummer
     * @param array<int,array<string,mixed>>          $mannschaften  Mannschaften, Schlüssel ist die Mannschaftsnummer ab 1
     * @param array<int,array<int,array<string,mixed>>> $paarungen   Einzelpaarungen als [Teilnehmernummer][Runde]
     * @param array<int,array<string,mixed>>          $rangliste     Teilnehmer nach Platz sortiert, ohne Platzhalter
     * @param array<int,array<int,array<string,mixed>>> $runden      Partien je Runde als [Runde][laufende Nummer]
     * @param array{spieler:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}|null $kreuztabelle
     *                                                               Kreuztabelle, oder null wenn das Format keine liefert
     * @param string[]                                $hinweise      Meldungen der Selbstkontrolle des Adapters
     * @param array<int,array<int,array<string,mixed>>> $mannschaftspaarungen
     *                                                               Wettkämpfe als [MNr.][Runde]; bei Einzelturnieren leer
     */
    public function __construct(
        private readonly string $format,
        private readonly array $kopf,
        private readonly array $spieler,
        private readonly array $mannschaften,
        private readonly array $paarungen,
        private readonly array $rangliste,
        private readonly array $runden,
        private readonly ?array $kreuztabelle,
        private readonly array $hinweise,
        private readonly array $mannschaftspaarungen = [],
    ) {
    }

    /**
     * Liefert dasselbe Turnier mit ergänzten Kopfdaten.
     *
     * Gebraucht wird das für Angaben, die nicht aus der Turnierdatei stammen,
     * sondern aus ihrer Umgebung — etwa das Änderungsdatum der Datei. Das
     * Format kann sie nicht liefern, es sieht nur den Inhalt.
     *
     * @param array<string,mixed> $zusatz Die zu ergänzenden Kopfdaten; sie
     *                                    überschreiben gleichnamige Angaben
     *
     * @return self Ein neues Turnier; das bestehende bleibt unverändert
     */
    public function mitKopf(array $zusatz): self
    {
        return new self(
            $this->format,
            array_merge($this->kopf, $zusatz),
            $this->spieler,
            $this->mannschaften,
            $this->paarungen,
            $this->rangliste,
            $this->runden,
            $this->kreuztabelle,
            $this->hinweise,
            $this->mannschaftspaarungen,
        );
    }

    /**
     * Gibt die Wettkämpfe der Mannschaften zurück.
     *
     * Aufbau: `[Mannschaftsnummer][Runde]`. Jeder Satz nennt mindestens
     * `gegner` (0 bei einer spielfreien Runde), `gegnerName`, `brettpunkte`,
     * `brettpunkteGegner`, `mannschaftspunkte` (null, solange der Kampf nicht
     * gespielt ist) und `amGruenenTisch`.
     *
     * Anders als bei den Einzelpaarungen steht ein Wettkampf zweimal darin —
     * einmal aus Sicht jeder Mannschaft.
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Mannschaft und Runde
     */
    public function getMannschaftspaarungen(): array
    {
        return $this->mannschaftspaarungen;
    }

    /**
     * Nennt das Format, aus dem dieses Turnier gelesen wurde.
     *
     * Der Text ist für die Anzeige gedacht, nicht für Fallunterscheidungen im
     * Code; er kann sich ändern, ohne dass sich am Format etwas ändert.
     *
     * @return string Anzeigename des Formats
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Gibt die allgemeinen Turnierdaten zurück.
     *
     * Erwartete Schlüssel, die alle Adapter füllen sollten: `name`,
     * `untertitel`, `ort`, `schiedsrichter`, `datumStart`, `datumEnde`,
     * `zeitkontrolle` (Liste), `modusText`, `runden`, `ausgelosteRunden`,
     * `teilnehmerzahl`, `mannschaftsturnier`, `mannschaftszahl`, `bretter`,
     * `feinwertung1Text`, `feinwertung2Text` und `feinwertungSicher`.
     *
     * @return array<string,mixed> Die Kopfdaten in der Form, die der Adapter geliefert hat
     */
    public function getKopf(): array
    {
        return $this->kopf;
    }

    /**
     * Liest einen einzelnen Wert aus den Kopfdaten.
     *
     * Bequemlichkeitsmethode für Templates und Listen, damit dort nicht
     * überall `?? ''` stehen muss. Ein fehlender Schlüssel ist kein Fehler:
     * nicht jedes Format kennt jede Angabe.
     *
     * @param string $schluessel Name des Kopffeldes
     * @param mixed  $standard   Rückgabewert, wenn das Feld fehlt oder leer ist
     *
     * @return mixed Der Wert aus den Kopfdaten oder $standard
     */
    public function kopf(string $schluessel, mixed $standard = null): mixed
    {
        $wert = $this->kopf[$schluessel] ?? null;

        return (null === $wert || '' === $wert) ? $standard : $wert;
    }

    /**
     * Gibt alle Teilnehmer zurück, einschließlich etwaiger Platzhalter.
     *
     * Für Anzeigezwecke ist meist getTeilnehmer() richtig; diese Methode
     * liefert die Rohliste, wie sie für die Auflösung von Gegnernummern
     * gebraucht wird.
     *
     * @return array<int,array<string,mixed>> Teilnehmer nach Teilnehmernummer
     */
    public function getSpieler(): array
    {
        return $this->spieler;
    }

    /**
     * Gibt die Teilnehmer ohne Platzhalter in Startreihenfolge zurück.
     *
     * Grundlage der Teilnehmerliste. Sortiert wird nach der Startnummer, weil
     * das der Reihenfolge entspricht, in der das Verwaltungsprogramm die
     * Teilnehmer führt; fehlt sie, entscheidet die Teilnehmernummer.
     *
     * @return array<int,array<string,mixed>> Teilnehmer als fortlaufende Liste
     */
    public function getTeilnehmer(): array
    {
        $liste = array_filter(
            $this->spieler,
            static fn (array $s): bool => !($s['spielfrei'] ?? false)
        );

        usort(
            $liste,
            static fn (array $a, array $b): int => ((int) ($a['startnummer'] ?: $a['tnr'])) <=> ((int) ($b['startnummer'] ?: $b['tnr']))
        );

        return array_values($liste);
    }

    /**
     * Gibt die Mannschaften zurück.
     *
     * Bei Einzelturnieren ist die Liste leer. Der Schlüssel ist die
     * Mannschaftsnummer ab 1 — dieselbe Zahl, die bei jedem Teilnehmer unter
     * `mannschaftsnummer` steht.
     *
     * @return array<int,array<string,mixed>> Mannschaften nach Mannschaftsnummer
     */
    public function getMannschaften(): array
    {
        return $this->mannschaften;
    }

    /**
     * Gibt alle Einzelpaarungen zurück.
     *
     * Aufbau: `[Teilnehmernummer][Runde]`. Jeder Satz enthält mindestens
     * `gegner`, `gegnerName`, `farbe`, `ergebnis` (float oder null),
     * `ergebnisText`, `tisch`, `brett` und `status`.
     *
     * @return array<int,array<int,array<string,mixed>>> Paarungen je Teilnehmer und Runde
     */
    public function getPaarungen(): array
    {
        return $this->paarungen;
    }

    /**
     * Gibt die Rangliste zurück.
     *
     * Die Reihenfolge stammt aus der Turnierdatei und wird nicht neu
     * berechnet — das Verwaltungsprogramm kennt die eingestellten
     * Feinwertungen und deren Reihenfolge, eine Nachrechnung würde bei
     * seltenen Wertungen abweichen.
     *
     * @return array<int,array<string,mixed>> Teilnehmer nach Platz sortiert
     */
    public function getRangliste(): array
    {
        return $this->rangliste;
    }

    /**
     * Gibt die Partien einer einzelnen Runde zurück.
     *
     * Jede Partie erscheint genau einmal, ausgerichtet an Weiß. Bei
     * spielfreien Teilnehmern ist `schwarz` null und `spielfrei` wahr.
     *
     * @param int $runde Rundennummer ab 1
     *
     * @return array<int,array<string,mixed>> Die Partien, oder ein leeres Array
     *                                        wenn die Runde nicht ausgelost ist
     */
    public function getRunde(int $runde): array
    {
        return $this->runden[$runde] ?? [];
    }

    /**
     * Gibt alle Runden zurück, für die Partien vorliegen.
     *
     * Nicht ausgeloste Runden fehlen. Die Liste ist die Grundlage der
     * Paarungs- und Ergebnislisten und bestimmt zugleich, welche Spalten die
     * Fortschrittstabelle bekommt.
     *
     * @return array<int,array<int,array<string,mixed>>> Partien je Rundennummer
     */
    public function getRunden(): array
    {
        return $this->runden;
    }

    /**
     * Nennt die Nummer der letzten Runde, für die Partien vorliegen.
     *
     * Maßgeblich ist der Bestand an Paarungen, nicht die im Kopf der Datei
     * angekündigte Rundenzahl: ein laufendes Turnier hat weniger Runden
     * gespielt als geplant, und ein doppelrundiges hat mehr.
     *
     * @return int Höchste belegte Rundennummer, 0 wenn keine Runde ausgelost ist
     */
    public function getLetzteRunde(): int
    {
        $runden = array_keys($this->runden);

        return $runden ? max($runden) : 0;
    }

    /**
     * Gibt die Kreuztabelle zurück.
     *
     * @return array{spieler:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}|null
     *         Zeilen und Spalten folgen der Rangliste; null, wenn das Format
     *         keine Kreuztabelle liefern kann
     */
    public function getKreuztabelle(): ?array
    {
        return $this->kreuztabelle;
    }

    /**
     * Gibt die Meldungen der Selbstkontrolle zurück.
     *
     * Typischer Fall ist eine Rangliste, die älter ist als die eingetragenen
     * Ergebnisse. Die Meldungen sind für den Betrachter gedacht und erklären,
     * warum Zahlen auseinandergehen können.
     *
     * @return string[] Hinweise in der Reihenfolge, in der sie entstanden sind
     */
    public function getHinweise(): array
    {
        return $this->hinweise;
    }

    /**
     * Sagt, ob es sich um ein Mannschaftsturnier handelt.
     *
     * Maßgeblich ist die Angabe des Formats. Eine gefüllte Mannschaftsliste
     * allein genügt nicht: manche Programme legen auch bei Einzelturnieren
     * Mannschaften an, um die Vereinszugehörigkeit zu führen.
     *
     * @return bool Wahr bei einem Mannschaftsturnier
     */
    public function istMannschaftsturnier(): bool
    {
        return (bool) ($this->kopf['mannschaftsturnier'] ?? false) && [] !== $this->mannschaften;
    }

    /**
     * Nennt die Zahl der Partien, die eine Paarung je Runde austrägt.
     *
     * Üblich ist eine. Bei Doppelrunden — verbreitet bei Blitzturnieren —
     * sind es zwei, und ein Rundenergebnis läuft dann von 0 bis 2. Wer das
     * übersieht, rechnet das Gegenergebnis als `1 − x` aus und landet bei
     * negativen Punktzahlen.
     *
     * @return int Partien je Paarung und Runde, mindestens 1
     */
    public function getPartienProRunde(): int
    {
        return max(1, (int) ($this->kopf['partienProRunde'] ?? 1));
    }

    /**
     * Nennt die Zahl der Bretter je Mannschaftskampf.
     *
     * Wird für die Wertung kampfloser Mannschaftskämpfe gebraucht: Wer
     * spielfrei ist, bekommt die volle Brettzahl gutgeschrieben.
     *
     * @return int Brettzahl, mindestens 1
     */
    public function getBretter(): int
    {
        return max(1, (int) ($this->kopf['bretter'] ?? 0));
    }
}
