<?php

declare(strict_types=1);

/*
 * Uebernommen aus dem Projekt SwtReader (F:\Claude\SwissChess\SwtReader).
 * Diese Kopie wird im Bundle gepflegt; Aenderungen gehoeren mit dem
 * Ursprungsprojekt abgeglichen.
 *
 * Abgeleitet vom SWT-Parser des Zugzwang-Projekts,
 * http://www.zugzwang.org/projects/swtparser
 * Copyright (C) 2005, 2012 Gustaf Mossakowski, Jacob Roggon, Falco Nogatz
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Format\Swt;

/**
 * Liest SWT-Turnierdateien des Programms „Swiss-Chess" ein.
 *
 * SWT-Dateien sind Binärdateien mit festem Satzaufbau. Sie bestehen aus drei
 * hintereinanderliegenden Bereichen:
 *
 *   1. Kopfbereich mit allen Turniereinstellungen (feste Adressen ab 0x0000)
 *   2. Paarungssätze zu je 19 Byte — erst alle Einzelpaarungen
 *      (Teilnehmerzahl × Runden Sätze), danach die Mannschaftspaarungen
 *      (Mannschaftszahl × Runden Sätze)
 *   3. Karteikarten zu je 655 Byte — erst alle Spieler, danach alle
 *      Mannschaften
 *
 * Die Anfangsadresse von Bereich 2 hängt von der Dateiversion ab: ab Version
 * 800 liegt sie bei 0x3448, davor bei 0x0F36 (mit kürzeren Karteikarten von
 * 292 Byte). Die Adresse von Bereich 3 muss aus Teilnehmerzahl, Runden und
 * Mannschaftszahl errechnet werden.
 *
 * Diese Klasse ist eine eigenständige, objektorientierte Neufassung des
 * SWT-Parsers aus dem „Zugzwang Project". Die Feldadressen stammen aus dessen
 * Strukturdateien, sind hier aber fest im Quelltext hinterlegt, damit die
 * Klasse ohne Begleitdateien auskommt.
 *
 * Ursprüngliche Fassung: SWT parser, http://www.zugzwang.org/projects/swtparser
 * Copyright (C) 2005, 2012 Gustaf Mossakowski, Jacob Roggon, Falco Nogatz
 * Lizenz: LGPL-3.0 — diese abgeleitete Fassung steht unter derselben Lizenz.
 *
 * Beispiel:
 *
 *     $turnier = new SwtFile('turnier.SWT');
 *     echo $turnier->getTurnier()['ueberschrift1'];
 *     foreach ($turnier->getRangliste() as $spieler) {
 *         printf("%2d. %-30s %4.1f\n", $spieler['platz'], $spieler['name'], $spieler['punkte']);
 *     }
 */
class SwtFile
{
    /** Länge eines Paarungssatzes in Byte (alle Dateiversionen). */
    private const LAENGE_PAARUNG = 19;

    /** Anfang des Paarungsbereichs ab Dateiversion 800. */
    private const START_AB_V800 = 0x3448;

    /** Anfang des Paarungsbereichs vor Dateiversion 800. */
    private const START_VOR_V800 = 0x0F36;

    /** Länge einer Karteikarte ab Dateiversion 800. */
    private const LAENGE_KARTE_AB_V800 = 655;

    /** Länge einer Karteikarte vor Dateiversion 800. */
    private const LAENGE_KARTE_VOR_V800 = 292;

    /**
     * Turniermodus (Feld an Adresse 0x0254).
     */
    private const MODUS = [
        0 => 'Schweizer System',
        1 => 'Rundenturnier',
        2 => 'KO-Turnier',
        3 => 'Scheveninger System',
    ];

    /**
     * Feinwertungen. Der Index ist der in der Datei gespeicherte Bytewert.
     */
    private const FEINWERTUNG = [
        0x00 => 'Nicht gesetzt',
        0x01 => 'Mannschaftspunkte',
        0x02 => 'Brettpunkte',
        0x03 => 'Buchholzwertung',
        0x04 => 'Summenwertung',
        0x05 => 'Sonneborn-Berger',
        0x06 => 'Mittlere Buchholz',
        0x07 => 'Buchholzsumme',
        0x08 => 'Gegner-NWZ/Elo-Mittel',
        0x09 => 'Rating-Leistung (NWZ/TWZ)',
        0x0A => 'Rating-Differenz (NWZ/TWZ)',
        0x0C => 'Drei-Punkte-Wertung',
        0x0D => 'Drei-Punkte-Farbwertung',
        0x10 => 'Berliner Wertung',
        0x13 => 'Schmuljan-Wertung',
    ];

    /**
     * Ermittlung der Turnierwertungszahl (Feld an Adresse 0x0246).
     */
    private const TWZ_ERMITTLUNG = [
        0 => 'Elo vor DWZ',
        1 => 'NWZ vor Elo',
        2 => 'Elo oder DWZ',
    ];

    /**
     * Farbe bzw. Spielort einer Paarung (Byte an Satzadresse 0x08).
     */
    private const FARBE = [
        0 => '',
        1 => 'Weiß',
        2 => 'Weiß (spielfrei)',
        3 => 'Schwarz',
        4 => 'Schwarz (spielfrei)',
    ];

    /**
     * Spielort einer Mannschaftspaarung (Byte an Satzadresse 0x08).
     */
    private const ORT = [
        0 => '',
        1 => 'Heim',
        2 => 'Auswärts',
        3 => 'Heim (spielfrei)',
        4 => 'Auswärts (spielfrei)',
    ];

    /**
     * Status einer Partie (Byte an Satzadresse 0x0F).
     */
    private const STATUS = [
        0x00 => '',
        0x01 => 'Hängepartie',
        0x11 => 'Hängepartie',
        0x02 => 'kampflos',
        0x22 => 'kampflos',
        0x03 => 'nicht eingesetzt',
        0x33 => 'nicht eingesetzt',
    ];

    /** Vollständiger Dateiinhalt als Binärstring. */
    private string $inhalt;

    /** Pfad der eingelesenen Datei, für Fehlermeldungen und Ausgabe. */
    private string $dateiname;

    /** Anfangsadresse des Paarungsbereichs. */
    private int $startPaarungen;

    /** Länge einer Karteikarte in Byte. */
    private int $laengeKarte;

    /** Zahl der je Teilnehmer gespeicherten Paarungssätze (Runden × Durchgänge). */
    private int $rundenSaetze = 0;

    /** Anfangsadresse des Karteikartenbereichs. */
    private int $startKarten = 0;

    /** @var array<int,string> Auffälligkeiten beim Auswerten, siehe getHinweise(). */
    private array $hinweise = [];

    /** true, wenn das Ergebnisbyte je Partie ein Halbbyte belegt statt zwei Bit. */
    private bool $halbbyteKodierung = false;

    /** @var array<string,mixed> Allgemeine Turnierdaten aus dem Kopfbereich. */
    private array $turnier = [];

    /** @var array<int,array<string,mixed>> Spieler, Schlüssel ist die Teilnehmernummer. */
    private array $spieler = [];

    /** @var array<int,array<string,mixed>> Mannschaften, Schlüssel ist die Mannschaftsnummer. */
    private array $mannschaften = [];

    /** @var array<int,array<int,array<string,mixed>>> Einzelpaarungen [Teilnehmernummer][Runde]. */
    private array $paarungen = [];

    /** @var array<int,array<int,array<string,mixed>>> Mannschaftspaarungen [Mannschaftsnummer][Runde]. */
    private array $mannschaftspaarungen = [];

    /**
     * Liest die angegebene SWT-Datei vollständig ein und wertet sie aus.
     *
     * Die Auswertung geschieht sofort und vollständig im Konstruktor, weil die
     * Bereiche der Datei voneinander abhängen: ohne Teilnehmerzahl und
     * Rundenzahl aus dem Kopf lässt sich die Lage der Karteikarten nicht
     * berechnen, und ohne die Karteikarten lassen sich die Gegner in den
     * Paarungen nicht auflösen. Eine typische Datei ist wenige Dutzend
     * Kilobyte groß, das Verfahren ist also unkritisch.
     *
     * @param string      $dateiname Pfad zur SWT-Datei, absolut oder relativ zum
     *                               aktuellen Arbeitsverzeichnis
     * @param string|null $inhalt    Der Dateiinhalt, falls er schon vorliegt.
     *                               Dann wird nichts vom Datenträger gelesen und
     *                               $dateiname benennt nur die Herkunft. Gedacht
     *                               für SWT-Dateien aus ZIP-Archiven, die sich so
     *                               auswerten lassen, ohne sie auszupacken.
     *
     * @throws \RuntimeException wenn die Datei fehlt, nicht lesbar ist oder
     *                           zu kurz für den Kopfbereich ist
     */
    public function __construct(string $dateiname, ?string $inhalt = null)
    {
        if (null === $inhalt) {
            if (!is_file($dateiname) || !is_readable($dateiname)) {
                throw new \RuntimeException(sprintf('Die Datei "%s" ist nicht lesbar.', $dateiname));
            }

            $inhalt = file_get_contents($dateiname);
        }

        // Der Kopfbereich reicht bis 0x0538. Kürzere Dateien können keine
        // Turnierdatei sein. Die früher hier stehende Schranke von 0x3000 war
        // zu hoch: Dateien des alten Formats mit wenigen Teilnehmern bleiben
        // deutlich darunter.
        if (false === $inhalt || \strlen($inhalt) < 0x0540) {
            throw new \RuntimeException(sprintf('Die Datei "%s" ist keine gültige SWT-Datei (zu kurz).', $dateiname));
        }

        $this->inhalt = $inhalt;
        $this->dateiname = $dateiname;

        $this->leseKopf();
        $this->bestimmeAufteilung();
        $this->leseSpaeteKopffelder();
        $this->bestimmeErgebniskodierung();
        $this->lesePersonen();
        $this->lesePaarungen();
        $this->pruefeStimmigkeit();
    }

    /**
     * Erzeugt eine Instanz aus einem Dateipfad.
     *
     * Reine Bequemlichkeitsmethode, damit sich Aufrufe verketten lassen
     * (`SwtFile::oeffnen($pfad)->getRangliste()`), ohne den Ausdruck klammern
     * zu müssen.
     *
     * @param string $dateiname Pfad zur SWT-Datei
     *
     * @return self
     *
     * @throws \RuntimeException siehe Konstruktor
     */
    public static function oeffnen(string $dateiname): self
    {
        return new self($dateiname);
    }

    /**
     * Wertet eine SWT-Datei aus, die bereits im Speicher liegt.
     *
     * Gedacht für Dateien aus ZIP-Archiven: `ZipArchive::getFromIndex()` gibt
     * den Inhalt zurück, ohne dass er auf die Festplatte muss.
     *
     * @param string $inhalt    Der vollständige Dateiinhalt
     * @param string $herkunft  Bezeichnung für Ausgaben und Fehlermeldungen,
     *                          etwa „turniere.zip → 2004/blitz.SWT"
     *
     * @return self
     *
     * @throws \RuntimeException wenn der Inhalt zu kurz für den Kopfbereich ist
     */
    public static function ausInhalt(string $inhalt, string $herkunft = 'unbenannt.SWT'): self
    {
        return new self($herkunft, $inhalt);
    }

    /**
     * Liefert die allgemeinen Turnierdaten aus dem Kopfbereich der Datei.
     *
     * Enthalten sind unter anderem die beiden Überschriftzeilen, Turniername,
     * Ort, Zeitraum, Turniermodus, Rundenzahl, Teilnehmerzahl, die
     * eingestellten Feinwertungen und die Dateiversion.
     *
     * @return array<string,mixed> Die Schlüssel sind sprechende deutsche
     *                             Bezeichner, siehe leseKopf()
     */
    public function getTurnier(): array
    {
        return $this->turnier;
    }

    /**
     * Liefert Auffälligkeiten, die beim Auswerten aufgefallen sind.
     *
     * Die Klasse bricht bei ungewöhnlichen Dateien nicht ab, sondern liest so
     * weit wie möglich und vermerkt hier, was nicht zusammenpasste — etwa eine
     * Dateigröße, die nicht zu Teilnehmer- und Rundenzahl passt, oder
     * Karteikarten, die über das Dateiende hinausragen. Bei alten Dateien, die
     * selbst Swiss-Chess nicht mehr öffnet, steht hier der Grund.
     *
     * Ein leeres Array bedeutet: Alles ging auf.
     *
     * @return array<int,string> Meldungen in der Reihenfolge des Auftretens
     */
    public function getHinweise(): array
    {
        return $this->hinweise;
    }

    /**
     * Liefert alle Spieler des Turniers.
     *
     * Schlüssel des Arrays ist die Teilnehmernummer (TNr.-ID), unter der die
     * Paarungssätze die Gegner referenzieren. Die Reihenfolge entspricht der
     * Speicherung in der Datei, also der Eingabereihenfolge, nicht der
     * Rangliste — dafür getRangliste() verwenden.
     *
     * Achtung: Bei Turnieren mit ungerader Teilnehmerzahl legt Swiss-Chess
     * einen Platzhalterspieler „spielfrei" an, der ebenfalls enthalten ist.
     * Er ist am Feld 'spielfrei' erkennbar.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getSpieler(): array
    {
        return $this->spieler;
    }

    /**
     * Liefert alle Mannschaften des Turniers.
     *
     * Bei Einzelturnieren ist das Ergebnis ein leeres Array. Schlüssel ist die
     * Mannschaftsnummer (MNr.-ID), unter der die Mannschaftspaarungen ihre
     * Gegner referenzieren.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getMannschaften(): array
    {
        return $this->mannschaften;
    }

    /**
     * Liefert die Einzelpaarungen als zweistufiges Array.
     *
     * Die erste Ebene ist die Teilnehmernummer, die zweite die Runde
     * (beginnend bei 1). Jede Partie steht daher zweimal darin — einmal aus
     * Sicht jedes Beteiligten. Für eine Partienliste ohne Dopplung
     * getRunde() verwenden.
     *
     * @return array<int,array<int,array<string,mixed>>>
     */
    public function getPaarungen(): array
    {
        return $this->paarungen;
    }

    /**
     * Liefert die Mannschaftspaarungen als zweistufiges Array.
     *
     * Aufbau wie bei getPaarungen(), erste Ebene ist die Mannschaftsnummer.
     * Bei Einzelturnieren ist das Ergebnis leer.
     *
     * @return array<int,array<int,array<string,mixed>>>
     */
    public function getMannschaftspaarungen(): array
    {
        return $this->mannschaftspaarungen;
    }

    /**
     * Liefert die Spieler sortiert nach dem in der Datei gespeicherten Platz.
     *
     * Der Platz wird von Swiss-Chess selbst vergeben und berücksichtigt alle
     * eingestellten Feinwertungen; er wird hier nicht neu berechnet. Der
     * Platzhalterspieler „spielfrei" wird ausgelassen, weil er in keiner
     * Rangliste auftaucht.
     *
     * Spieler ohne vergebenen Platz (Wert 0, etwa vor der ersten Auslosung)
     * landen am Ende der Liste statt am Anfang.
     *
     * @return array<int,array<string,mixed>> Fortlaufend indiziert, damit sich
     *                                        das Ergebnis direkt durchlaufen lässt
     */
    public function getRangliste(): array
    {
        $liste = array_values(array_filter(
            $this->spieler,
            static fn (array $spieler): bool => !$spieler['spielfrei']
        ));

        // usort ist seit PHP 8.0 stabil, gleiche Plätze behalten also die
        // Dateireihenfolge.
        usort($liste, static function (array $a, array $b): int {
            $platzA = $a['platz'] > 0 ? $a['platz'] : PHP_INT_MAX;
            $platzB = $b['platz'] > 0 ? $b['platz'] : PHP_INT_MAX;

            return $platzA <=> $platzB;
        });

        return $liste;
    }

    /**
     * Liefert die Mannschaften sortiert nach dem gespeicherten Platz.
     *
     * Verhält sich wie getRangliste(), nur für Mannschaften. Bei
     * Einzelturnieren ist das Ergebnis leer.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getMannschaftsrangliste(): array
    {
        $liste = array_values($this->mannschaften);

        usort($liste, static function (array $a, array $b): int {
            $platzA = $a['platz'] > 0 ? $a['platz'] : PHP_INT_MAX;
            $platzB = $b['platz'] > 0 ? $b['platz'] : PHP_INT_MAX;

            return $platzA <=> $platzB;
        });

        return $liste;
    }

    /**
     * Liefert die Partien einer Runde als Liste, jede Partie genau einmal.
     *
     * Da die Datei jede Partie aus Sicht beider Spieler speichert, wird hier
     * entdoppelt: Ausgegeben wird jeweils der Satz des Weißspielers, damit
     * Farbe und Ergebnis zusammenpassen. Spielfreie Teilnehmer erscheinen als
     * eigener Eintrag ohne Gegner.
     *
     * @param int $runde Rundennummer, beginnend bei 1
     *
     * @return array<int,array<string,mixed>> Liste mit den Schlüsseln tisch,
     *                                        brett, weiss, schwarz, ergebnis,
     *                                        ergebnisText, status, spielfrei;
     *                                        leer, wenn die Runde nicht
     *                                        ausgelost ist
     */
    public function getRunde(int $runde): array
    {
        $partien = [];
        $erledigt = [];

        if (!$this->rundeBelegt($runde)) {
            return [];
        }

        foreach ($this->paarungen as $tnr => $runden) {
            if (!isset($runden[$runde]) || isset($erledigt[$tnr])) {
                continue;
            }

            if ($this->spieler[$tnr]['spielfrei'] ?? false) {
                continue;
            }

            $satz = $runden[$runde];
            $gegner = $satz['gegner'];

            // Spielfrei: kein Gegner eingetragen oder der Gegner ist der von
            // Swiss-Chess angelegte Platzhalterspieler.
            $istSpielfrei = 0 === $gegner
                || !isset($this->spieler[$gegner])
                || $this->spieler[$gegner]['spielfrei'];

            if ($istSpielfrei) {
                $erledigt[$tnr] = true;
                $partien[] = [
                    'tisch' => $satz['tisch'],
                    'brett' => $satz['brett'],
                    'weiss' => $this->spielerKurz($tnr),
                    'schwarz' => null,
                    'ergebnis' => $satz['ergebnis'],
                    'ergebnisText' => $satz['ergebnisText'],
                    'status' => $satz['status'],
                    'spielfrei' => true,
                ];

                continue;
            }

            $erledigt[$tnr] = true;
            $erledigt[$gegner] = true;

            // Der Satz des Schwarzspielers beschreibt dieselbe Partie aus der
            // Gegenrichtung; die Ausgabe wird immer an Weiß ausgerichtet.
            $dieserIstWeiss = 0 !== strncmp($satz['farbe'], 'Schwarz', 7);
            $weiss = $dieserIstWeiss ? $tnr : $gegner;
            $schwarz = $dieserIstWeiss ? $gegner : $tnr;
            $satzWeiss = $this->paarungen[$weiss][$runde] ?? $satz;

            $partien[] = [
                'tisch' => $satzWeiss['tisch'],
                'brett' => $satzWeiss['brett'],
                'weiss' => $this->spielerKurz($weiss),
                'schwarz' => $this->spielerKurz($schwarz),
                'ergebnis' => $satzWeiss['ergebnis'],
                'ergebnisText' => $satzWeiss['ergebnisText'],
                'status' => $satzWeiss['status'],
                'spielfrei' => false,
            ];
        }

        usort($partien, static fn (array $a, array $b): int => $a['tisch'] <=> $b['tisch']);

        return $partien;
    }

    /**
     * Erzeugt eine Kreuztabelle über alle Spieler.
     *
     * Zeilen- und Spaltenreihenfolge entsprechen der Rangliste, wie es bei
     * Rundenturnieren üblich ist. In jeder Zelle steht das Ergebnis des
     * Zeilenspielers gegen den Spaltenspieler als Symbol: `1`, `½`, `0` für
     * gespielte Partien, `+`/`-` für kampflose, `**` für die Diagonale, leer
     * wenn die beiden nicht gegeneinander gespielt haben.
     *
     * Bei Schweizer Systemen mit vielen Teilnehmern ist die Tabelle sehr dünn
     * besetzt und eher zur Kontrolle als zur Darstellung geeignet.
     *
     * @return array{spieler:array<int,array<string,mixed>>,zeilen:array<int,array<int,string>>}
     *         'spieler' ist die Rangliste (Reihenfolge der Zeilen und Spalten),
     *         'zeilen' enthält je Zeilenindex ein Array von Spaltenindex auf Symbol
     */
    public function getKreuztabelle(): array
    {
        $rangliste = $this->getRangliste();
        $spalte = [];

        foreach ($rangliste as $index => $spieler) {
            $spalte[$spieler['tnr']] = $index;
        }

        $zeilen = [];

        foreach ($rangliste as $index => $spieler) {
            $zeile = array_fill(0, \count($rangliste), '');
            $zeile[$index] = '**';

            foreach ($this->paarungen[$spieler['tnr']] ?? [] as $satz) {
                if (!isset($spalte[$satz['gegner']])) {
                    continue;
                }

                $symbol = $this->ergebnisSymbol($satz);

                // Ohne Ergebnis nichts eintragen: In einem doppelrundigen
                // Turnier stehen die Paarungen der Rückrunde schon in der
                // Datei, bevor sie gespielt sind. Ein leerer Eintrag würde
                // sonst das Ergebnis der Hinrunde wieder auslöschen.
                if ('' === $symbol) {
                    continue;
                }

                $spaltenindex = $spalte[$satz['gegner']];
                $zeile[$spaltenindex] = '' === $zeile[$spaltenindex]
                    ? $symbol
                    : $zeile[$spaltenindex].' '.$symbol;
            }

            $zeilen[$index] = $zeile;
        }

        return ['spieler' => $rangliste, 'zeilen' => $zeilen];
    }

    /**
     * Gibt den gesamten Dateiinhalt als verschachteltes Array zurück.
     *
     * Gedacht für die Weitergabe an Vorlagen, für die JSON-Ausgabe oder zum
     * Vergleich zweier Dateien. Rangliste, Rundenübersicht und Kreuztabelle
     * sind mit enthalten, damit die Empfängerseite sie nicht selbst aus den
     * Paarungen herleiten muss.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $runden = [];

        for ($runde = 1; $runde <= $this->rundenSaetze; ++$runde) {
            $runden[$runde] = $this->getRunde($runde);
        }

        return [
            'datei' => basename($this->dateiname),
            'turnier' => $this->turnier,
            'spieler' => $this->spieler,
            'mannschaften' => $this->mannschaften,
            'paarungen' => $this->paarungen,
            'mannschaftspaarungen' => $this->mannschaftspaarungen,
            'rangliste' => $this->getRangliste(),
            'mannschaftsrangliste' => $this->getMannschaftsrangliste(),
            'runden' => $runden,
            'kreuztabelle' => $this->getKreuztabelle(),
        ];
    }

    /**
     * Gibt den gesamten Dateiinhalt als JSON-Zeichenkette zurück.
     *
     * Alle Texte sind bereits nach UTF-8 gewandelt, Umlaute erscheinen daher
     * unverändert und nicht als \u-Fluchtfolgen.
     *
     * @param bool $lesbar true rückt die Ausgabe ein (für Kontrollzwecke),
     *                     false liefert kompaktes JSON
     *
     * @return string
     *
     * @throws \JsonException wenn die Daten nicht kodierbar sind; das kann nur
     *                        bei beschädigten Dateien mit ungültigen Bytefolgen
     *                        vorkommen
     */
    public function toJson(bool $lesbar = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        if ($lesbar) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->toArray(), $flags);
    }

    /**
     * Gibt eine kurze Textübersicht des Turniers zurück.
     *
     * Gedacht für die Kommandozeile und für schnelle Kontrollen: Kopfdaten,
     * Rangliste und je Runde die Paarungen, alles als einfacher Text ohne
     * Auszeichnung.
     *
     * @return string mehrzeiliger Text, mit \n abgeschlossen
     */
    public function toText(): string
    {
        $t = $this->turnier;
        $zeilen = [];
        $zeilen[] = $t['ueberschrift1'] ?: $t['turniername'] ?: basename($this->dateiname);

        if ('' !== $t['ueberschrift2']) {
            $zeilen[] = $t['ueberschrift2'];
        }

        $zeilen[] = sprintf(
            '%s, %d Runden%s, %d Teilnehmer%s',
            $t['modusText'],
            $this->rundenSaetze,
            (int) $t['partienProRunde'] > 1 ? sprintf(' zu je %d Partien', $t['partienProRunde']) : '',
            \count($this->getRangliste()),
            $t['datumStart'] ? ', ab '.$t['datumStart'] : ''
        );
        $zeilen[] = '';
        $zeilen[] = 'Rangliste'.($t['feinwertung1Text'] && 'Nicht gesetzt' !== $t['feinwertung1Text']
            ? ' (letzte Spalte: '.$t['feinwertung1Text'].')'
            : '');
        $zeilen[] = str_repeat('-', 72);

        foreach ($this->getRangliste() as $spieler) {
            $zeilen[] = sprintf(
                '%3d. %s %s %5s %2d/%2d/%2d %6s %8s',
                $spieler['platz'],
                $this->spalte($spieler['name'], 28),
                $this->spalte($spieler['titel'], 4),
                $spieler['twz'] ?: '',
                $spieler['siege'],
                $spieler['remis'],
                $spieler['niederlagen'],
                $this->punkteText($spieler['punkte']),
                $this->punkteText($spieler['feinwertung1'])
            );
        }

        for ($runde = 1; $runde <= $this->rundenSaetze; ++$runde) {
            $partien = $this->getRunde($runde);

            if (!$partien) {
                continue;
            }

            $zeilen[] = '';
            $zeilen[] = sprintf('Runde %d', $runde);
            $zeilen[] = str_repeat('-', 72);

            foreach ($partien as $partie) {
                if ($partie['spielfrei']) {
                    $zeilen[] = sprintf('%3d  %s   spielfrei', $partie['tisch'], $this->spalte($partie['weiss']['name'], 26));

                    continue;
                }

                $zeilen[] = sprintf(
                    '%3d  %s - %s %s%s',
                    $partie['tisch'],
                    $this->spalte($partie['weiss']['name'], 26),
                    $this->spalte($partie['schwarz']['name'], 26),
                    $partie['ergebnisText'],
                    $partie['status'] ? ' ('.$partie['status'].')' : ''
                );
            }
        }

        return implode("\n", $zeilen)."\n";
    }

    /**
     * Liest den Kopfbereich der Datei mit allen Turniereinstellungen aus.
     *
     * Muss als Erstes laufen, weil erst danach feststeht, wo Paarungen und
     * Karteikarten liegen: Die Dateiversion (Adresse 0x0261) entscheidet über
     * Satzlängen und Anfangsadresse, Teilnehmer- und Rundenzahl über die Größe
     * des Paarungsbereichs.
     *
     * Die Feinwertungen verdienen eine Erklärung: Swiss-Chess speichert zwei
     * Feinwertungseinstellungen — eine „für das Schweizer System" (0x026D) und
     * eine zweite (0x023C). Welche davon in welchem Zahlenfeld der
     * Spielerkarteikarte landet, hängt vom Turniermodus ab. Bei
     * Rundenturnieren wird nur die zweite Einstellung verwendet und im ersten
     * Zahlenfeld abgelegt; sonst füllen beide Einstellungen der Reihe nach die
     * beiden Felder. Das ist an den mitgelieferten Beispieldateien gegen die
     * Textausgabe von Swiss-Chess geprüft.
     *
     * @return void Das Ergebnis landet in $this->turnier
     */
    private function leseKopf(): void
    {
        $version = $this->wort(0x0261);

        // Ab Version 8 liegt der Paarungsbereich weiter hinten und die
        // Karteikarten sind länger. Ältere Dateien haben ein anderes Format.
        if ($version >= 800) {
            $this->startPaarungen = self::START_AB_V800;
            $this->laengeKarte = self::LAENGE_KARTE_AB_V800;
        } else {
            $this->startPaarungen = self::START_VOR_V800;
            $this->laengeKarte = self::LAENGE_KARTE_VOR_V800;
        }

        // Vor Fassung 8 ist das Byte an 0x0254 kein Auswahlwert, sondern ein
        // Schalter: FF steht für Rundenturnier, 00 für Schweizer System. Das
        // ist an den Turnieren mit „Runden = Teilnehmer − 1" abzulesen, die
        // durchweg FF tragen. Ab Fassung 8 zählt der Wert die Modi durch, und
        // FF kommt dort nicht mehr vor.
        $modus = $this->byte(0x0254);

        if (0xFF === $modus) {
            $modus = 1;
        }

        $feinwertungA = $this->byte(0x026D);
        $feinwertungB = $this->byte(0x023C);

        // Siehe Methodenkommentar: Beim Rundenturnier rutscht die zweite
        // Einstellung in das erste Zahlenfeld der Karteikarte.
        if (1 === $modus) {
            $feinwertung1 = $feinwertungB;
            $feinwertung2 = 0;
        } else {
            $feinwertung1 = $feinwertungA;
            $feinwertung2 = $feinwertungB;
        }

        $this->turnier = [
            'version' => $version,
            'ueberschrift1' => $this->text(0x00F5, 0x0130),
            'ueberschrift2' => $this->text(0x0178, 0x01B4),
            'turniername' => $this->text(0x0316, 0x033D),
            'ortLand' => $this->text(0x033F, 0x0366),
            'schiedsrichter' => $this->text(0x0368, 0x03A3),
            'weitereSchiedsrichter' => array_values(array_filter([$this->text(0x03A5, 0x03E0)])),
            'organisator' => '',
            'bemerkung' => $this->text(0x03E2, 0x041D),
            'datumStart' => $this->text(0x041F, 0x0432),
            'datumEnde' => $this->text(0x0434, 0x0447),
            'zeitkontrolle' => array_values(array_filter([
                $this->text(0x0449, 0x045C),
                $this->text(0x045E, 0x0471),
                $this->text(0x0473, 0x0487),
            ])),
            'modus' => $modus,
            'modusText' => self::MODUS[$modus] ?? sprintf('unbekannt (%d)', $modus),
            // Diese Zählwerte belegen je zwei Byte. Der ältere Parser las nur
            // das erste und kam bei Turnieren mit mehr als 255 Teilnehmern auf
            // eine viel zu kleine Zahl — und damit auf eine falsche
            // Anfangsadresse für sämtliche Karteikarten.
            'runden' => $this->wort(0x0001),
            'aktuelleRunde' => $this->wort(0x0003),
            'ausgelosteRunden' => $this->wort(0x0005),
            'teilnehmerzahl' => $this->wort(0x0007),
            'mannschaftsturnier' => 0 !== $this->byte(0x025E),
            'mannschaftszahl' => $this->wort(0x0534),
            'bretter' => $this->byte(0x025C),
            'spielerProMannschaft' => $this->byte(0x052F),
            'partienProRunde' => $this->byte(0x0255),
            'durchgaenge' => $this->byte(0x0258),
            'streichwertungen' => $this->wort(0x0009),
            'maxFarbdifferenz' => $this->byte(0x0238),
            'maxFarbgleichheit' => $this->byte(0x023A),
            'twzErmittlung' => $this->byte(0x0246),
            'twzErmittlungText' => self::TWZ_ERMITTLUNG[$this->byte(0x0246)] ?? '',
            'feinwertung1' => $feinwertung1,
            'feinwertung1Text' => self::FEINWERTUNG[$feinwertung1] ?? '',
            'feinwertung2' => $feinwertung2,
            'feinwertung2Text' => self::FEINWERTUNG[$feinwertung2] ?? '',
            // Vor Fassung 8 trifft die Bezeichnung nur etwa in der Hälfte der
            // Fälle zu; die Einstellung steht dort offenbar teilweise woanders.
            // Die Zahlenwerte selbst stimmen, weil sich der Teiler nur bei
            // Sonneborn-Berger unterscheidet und der Fall selten ist.
            'feinwertungBezeichnungSicher' => $version >= 800,
            'feinwertungMannschaft1Text' => self::FEINWERTUNG[$this->byte(0x0263)] ?? '',
            'einzelwertung' => $this->byte(0x0538),
            'mannschaftswertung' => $this->byte(0x053A),
        ];
    }

    /**
     * Stellt fest, wie das Ergebnisbyte der Paarungssätze aufgeteilt ist.
     *
     * Swiss-Chess kennt zwei Aufteilungen. Im Regelfall belegt jede Angabe
     * zwei Bit; bei Mannschaftsturnieren mit zwei Partien je Runde dagegen
     * belegt jede Partie ein ganzes Halbbyte. Der Unterschied ist nicht
     * nebensächlich: Ein doppelter Sieg steht dort als `FF` in der Datei, was
     * paarweise gelesen einen einzelnen Punkt statt zweier ergäbe.
     *
     * Die Aufteilung hängt nicht an der Dateiversion. Speichert Swiss-Chess
     * ein altes Mannschaftsturnier in der heutigen Fassung neu, behält es die
     * Halbbyte-Aufteilung bei — eine Unterscheidung nach Versionsnummer ginge
     * hier fehl. Deshalb wird zuerst die Datei selbst befragt: Werte über
     * `0F` kann es nur bei Halbbyte-Aufteilung geben, weil die paarweise
     * Aufteilung die oberen vier Bit gar nicht benutzt. Findet sich kein
     * solcher Wert, entscheidet die Turnierart.
     *
     * @return void Das Ergebnis landet in $this->halbbyteKodierung
     */
    private function bestimmeErgebniskodierung(): void
    {
        if ($this->turnier['mannschaftsturnier'] && (int) $this->turnier['partienProRunde'] > 1) {
            $this->halbbyteKodierung = true;

            return;
        }

        $anzahl = $this->rundenSaetze * (
            (int) $this->turnier['teilnehmerzahl']
            + ($this->turnier['mannschaftsturnier'] ? (int) $this->turnier['mannschaftszahl'] : 0)
        );

        for ($i = 0; $i < $anzahl; ++$i) {
            if ($this->byte($this->startPaarungen + $i * self::LAENGE_PAARUNG + 0x0B) > 0x0F) {
                $this->halbbyteKodierung = true;

                return;
            }
        }
    }

    /**
     * Liest die Kopffelder nach, die weit hinten in der Datei liegen.
     *
     * Turnierorganisator und die Schiedsrichterzeilen zwei bis vier stehen bei
     * 0x2E0A und dahinter. Im Format vor Fassung 8 beginnen dort längst die
     * Paarungen, dann muss das Feld ungelesen bleiben — sonst käme
     * Zufallsinhalt heraus. Ob das der Fall ist, steht erst nach
     * bestimmeAufteilung() fest, weshalb diese Felder getrennt gelesen werden.
     *
     * @return void Das Ergebnis ergänzt $this->turnier
     */
    private function leseSpaeteKopffelder(): void
    {
        if ($this->startPaarungen <= 0x2EFC) {
            return;
        }

        $this->turnier['organisator'] = $this->text(0x2E0A, 0x2E45);
        $this->turnier['weitereSchiedsrichter'] = array_values(array_filter([
            $this->text(0x03A5, 0x03E0),
            $this->text(0x2E47, 0x2E82),
            $this->text(0x2E84, 0x2EBF),
            $this->text(0x2EC1, 0x2EFC),
        ]));
    }

    /**
     * Liest die Karteikarten der Spieler und, falls vorhanden, der Mannschaften.
     *
     * Die Karteikarten stehen hinter dem Paarungsbereich, dessen Größe
     * bestimmeAufteilung() ermittelt hat. Zuerst kommen die Spieler in
     * Eingabereihenfolge, dahinter die Mannschaften.
     *
     * @return void Das Ergebnis landet in $this->spieler und $this->mannschaften
     */
    private function lesePersonen(): void
    {
        $start = $this->startKarten;
        $anzahl = (int) $this->turnier['teilnehmerzahl'];

        for ($i = 0; $i < $anzahl; ++$i) {
            $offset = $start + $i * $this->laengeKarte;

            if ($offset + $this->laengeKarte > \strlen($this->inhalt)) {
                $this->hinweise[] = sprintf(
                    'Nur %d der %d angekündigten Spielerkarteikarten liegen in der Datei; danach ist sie zu Ende.',
                    $i,
                    $anzahl
                );

                break;
            }

            $spieler = $this->leseSpielerkarte($offset);
            $this->spieler[$spieler['tnr']] = $spieler;
        }

        if (\count($this->spieler) < $anzahl && !$this->hinweise) {
            $this->hinweise[] = sprintf(
                '%d Karteikarten ergaben nur %d verschiedene Teilnehmernummern — vermutlich liegt der'
                .' Karteikartenbereich woanders als errechnet.',
                $anzahl,
                \count($this->spieler)
            );
        }

        if (!$this->turnier['mannschaftsturnier']) {
            return;
        }

        $start += $anzahl * $this->laengeKarte;
        $anzahlMannschaften = (int) $this->turnier['mannschaftszahl'];

        for ($i = 0; $i < $anzahlMannschaften; ++$i) {
            $offset = $start + $i * $this->laengeKarte;

            if ($offset + $this->laengeKarte > \strlen($this->inhalt)) {
                break;
            }

            $mannschaft = $this->leseMannschaftskarte($offset);
            $this->mannschaften[$mannschaft['mnr']] = $mannschaft;
        }
    }

    /**
     * Wertet eine einzelne Spielerkarteikarte aus.
     *
     * Die Punktzahlen liegen in der Datei verdoppelt vor, damit halbe Punkte
     * ganzzahlig gespeichert werden können; sie werden hier zurückgerechnet.
     * Bei der Feinwertung Sonneborn-Berger ist der Faktor 4 statt 2, weil dort
     * Viertelpunkte auftreten können.
     *
     * Die Zahlenfelder sind 16 Bit breit und liegen im Rechnerformat mit dem
     * niederwertigen Byte zuerst. Der ältere Parser des Zugzwang-Projekts las
     * nur das erste Byte, was bei Buchholzwerten über 127 falsche Ergebnisse
     * liefert — hier werden beide Bytes ausgewertet.
     *
     * @param int $offset Anfangsadresse der Karteikarte in der Datei
     *
     * @return array<string,mixed> Alle Felder des Spielers, siehe Schlüssel im Rumpf
     */
    private function leseSpielerkarte(int $offset): array
    {
        $name = $this->text($offset, $offset + 0x1F);
        $elo = (int) $this->text($offset + 0x46, $offset + 0x49);
        $dwz = (int) $this->text($offset + 0x4B, $offset + 0x4E);

        // Ältere Dateien haben mit 292 statt 655 Byte deutlich kürzere
        // Karteikarten. Punktzahlen und Wertungen liegen dort weiter vorn,
        // FIDE-Kennung und PKZ an ganz anderer Stelle, und für die vier
        // Infozeilen ist gar kein Platz — deren Adressen lägen jenseits des
        // Satzendes und würden in die nächste Karteikarte hineinlesen.
        $altesFormat = self::LAENGE_KARTE_VOR_V800 === $this->laengeKarte;
        $adrPunkte = $altesFormat ? 0xE9 : 0x124;
        $adrFeinwertung1 = $altesFormat ? 0xEB : 0x128;
        $adrFeinwertung2 = $altesFormat ? 0xED : 0x12C;

        $sonderpunkte = $this->byte($offset + 0x111) / 2;

        if (0 !== $this->byte($offset + 0x110)) {
            $sonderpunkte = -$sonderpunkte;
        }

        if ($altesFormat) {
            $fideId = $this->text($offset + 0x8B, $offset + 0x91);
            $pkz = $this->text($offset + 0x61, $offset + 0x66);
            $zpsMitglied = $this->text($offset + 0x9F, $offset + 0xA1);
            $info = [];
        } else {
            $fideId = $this->text($offset + 0x144, $offset + 0x14F);
            $pkz = $this->text($offset + 0x151, $offset + 0x15C);
            $zpsMitglied = $this->text($offset + 0x9F, $offset + 0xA2);
            $info = array_values(array_filter([
                $this->text($offset + 0x15E, $offset + 0x185),
                $this->text($offset + 0x187, $offset + 0x1AE),
                $this->text($offset + 0x1B0, $offset + 0x1D7),
                $this->text($offset + 0x1D9, $offset + 0x200),
            ]));
        }

        return [
            'tnr' => $this->wort($offset + 0xD9),
            'startnummer' => $this->wort($offset + 0xDB),
            'name' => $name,
            'spielfrei' => $this->istPlatzhalter($name),
            'mannschaft' => $this->text($offset + 0x21, $offset + 0x40),
            'titel' => $this->text($offset + 0x42, $offset + 0x44),
            'elo' => $elo,
            'dwz' => $dwz,
            'twz' => $this->ermittleTwz($elo, $dwz),
            'land' => $this->text($offset + 0x69, $offset + 0x6B),
            'verband' => $this->text($offset + 0x6D, $offset + 0x6F),
            'geburtsdatum' => $this->text($offset + 0x80, $offset + 0x89),
            'attribut' => $this->text($offset + 0xB8, $offset + 0xB8),
            'aktiv' => '*' !== $this->text($offset + 0xB8, $offset + 0xB8),
            'selekt' => $this->text($offset + 0xBC, $offset + 0xBC),
            'wahl' => $this->text($offset + 0xC0, $offset + 0xC2),
            'teilnehmerkennung' => $this->text($offset + 0x5A, $offset + 0x5E),
            'zpsStatus' => $this->text($offset + 0x97, $offset + 0x97),
            'zpsVerein' => $this->text($offset + 0x99, $offset + 0x9D),
            'zpsMitglied' => $zpsMitglied,
            'fideId' => $fideId,
            'pkz' => $pkz,
            'mannschaftsnummer' => $this->wort($offset + 0xC9),
            'brett' => $this->wort($offset + 0xCB),
            'platz' => $this->wort($offset + 0xDD),
            'platzBrettwertung' => $this->wort($offset + 0xDF),
            'partien' => $this->wort($offset + 0xE1),
            'siege' => $this->wort($offset + 0xE3),
            'remis' => $this->wort($offset + 0xE5),
            'niederlagen' => $this->wort($offset + 0xE7),
            'ausgesetzt' => max(0, $this->ganzzahlMitVorzeichen($offset + 0xAD)),
            'punkte' => $this->wort($offset + $adrPunkte) / 2,
            'sonderpunkte' => $sonderpunkte,
            'feinwertung1' => $this->wort($offset + $adrFeinwertung1) / $this->feinwertungsteiler(1),
            'feinwertung2' => $this->wort($offset + $adrFeinwertung2) / $this->feinwertungsteiler(2),
            'info' => $info,
        ];
    }

    /**
     * Wertet eine einzelne Mannschaftskarteikarte aus.
     *
     * Aufbau und Zahlenformate entsprechen der Spielerkarteikarte. Mangels
     * Mannschaftsturnier unter den Beispieldateien sind diese Felder aus den
     * Strukturangaben des Zugzwang-Projekts übernommen und nicht gegen eine
     * Ausgabe von Swiss-Chess geprüft.
     *
     * @param int $offset Anfangsadresse der Karteikarte in der Datei
     *
     * @return array<string,mixed> Alle Felder der Mannschaft
     */
    private function leseMannschaftskarte(int $offset): array
    {
        return [
            'mnr' => $this->wort($offset + 0xD9),
            'startnummer' => $this->wort($offset + 0xDB),
            'name' => $this->text($offset, $offset + 0x1F),
            'eloSchnitt' => (int) $this->text($offset + 0x46, $offset + 0x49),
            'dwzSchnitt' => (int) $this->text($offset + 0x4B, $offset + 0x4E),
            'land' => $this->text($offset + 0x69, $offset + 0x6B),
            'verband' => $this->text($offset + 0x6D, $offset + 0x6F),
            'datum' => $this->text($offset + 0x80, $offset + 0x89),
            'zpsVerein' => $this->text($offset + 0x99, $offset + 0x9D),
            'attribut' => $this->text($offset + 0xB8, $offset + 0xB8),
            'erstesBrett' => $this->wort($offset + 0xCB),
            'beginnSpieler' => $this->wort($offset + 0xCF),
            'spielerzahl' => $this->wort($offset + 0xD5),
            'platz' => $this->wort($offset + 0xDD),
            'partien' => $this->wort($offset + 0xE1),
            'siege' => $this->wort($offset + 0xE3),
            'remis' => $this->wort($offset + 0xE5),
            'niederlagen' => $this->wort($offset + 0xE7),
            'mannschaftspunkte' => $this->wort($offset + 0x124) / 2,
            'brettpunkte' => $this->wort($offset + 0x128) / 2,
            'feinwertung1' => $this->wort($offset + 0x12C) / 2,
            'info' => array_values(array_filter([
                $this->text($offset + 0x15E, $offset + 0x185),
                $this->text($offset + 0x187, $offset + 0x1AE),
                $this->text($offset + 0x1B0, $offset + 0x1D7),
                $this->text($offset + 0x1D9, $offset + 0x200),
            ])),
        ];
    }

    /**
     * Liest den Paarungsbereich der Datei.
     *
     * Die Sätze stehen ohne Trennung hintereinander: zuerst für jeden Spieler
     * in Dateireihenfolge alle Runden, danach dasselbe für die Mannschaften.
     * Die Zuordnung eines Satzes zu Spieler und Runde ergibt sich also allein
     * aus seiner Position, nicht aus seinem Inhalt.
     *
     * @return void Das Ergebnis landet in $this->paarungen und
     *              $this->mannschaftspaarungen
     */
    private function lesePaarungen(): void
    {
        $runden = $this->rundenSaetze;

        if ($runden < 1) {
            return;
        }

        $tnrListe = array_keys($this->spieler);
        $offset = $this->startPaarungen;

        foreach ($tnrListe as $tnr) {
            for ($runde = 1; $runde <= $runden; ++$runde) {
                $this->paarungen[$tnr][$runde] = $this->lesePaarung($offset, false);
                $offset += self::LAENGE_PAARUNG;
            }
        }

        if (!$this->turnier['mannschaftsturnier']) {
            return;
        }

        $offset = $this->startPaarungen
            + (int) $this->turnier['teilnehmerzahl'] * $runden * self::LAENGE_PAARUNG;

        foreach (array_keys($this->mannschaften) as $mnr) {
            for ($runde = 1; $runde <= $runden; ++$runde) {
                $this->mannschaftspaarungen[$mnr][$runde] = $this->lesePaarung($offset, true);
                $offset += self::LAENGE_PAARUNG;
            }
        }
    }

    /**
     * Wertet einen einzelnen Paarungssatz von 19 Byte aus.
     *
     * Das Ergebnisbyte an Satzadresse 0x0B trägt zwei Angaben in einem Byte.
     * Wie es aufgeteilt ist, unterscheidet sich zwischen den Dateiformaten —
     * das ist der Punkt, an dem eine unbedachte Auswertung alter
     * Mannschaftsturniere auseinanderfliegt.
     *
     * **Vor Fassung 8** belegt jede Partie ein ganzes Halbbyte: das untere die
     * erste, das obere die zweite. Maßgeblich sind davon jeweils nur die
     * unteren zwei Bit (0 = nichts eingetragen, 1 = 0, 2 = Remis, 3 = ganzer
     * Punkt). Ein doppelter Sieg steht als `FF` in der Datei, ein Sieg mit
     * anschließendem Remis als `FA`, ein einzelner Sieg als `0F`.
     *
     * **Ab Fassung 8** belegt jede Angabe nur noch zwei Bit: die unteren das
     * eigene Ergebnis, die oberen je nach Turnier den Ausgang des
     * Mannschaftskampfes oder — bei mehreren Partien je Runde — die zweite
     * Partie derselben Paarung.
     *
     * Welche der beiden Partien einer Doppelrunde wie ausging, lässt sich
     * nicht zurückgewinnen; nur die Summe stimmt, und nur sie geht mit den
     * Punktzahlen der Rangliste zusammen.
     *
     * Geprüft an den Textausgaben von Swiss-Chess und daran, dass die so
     * errechneten Punktsummen bei allen Teilnehmern mit den Karteikarten
     * zusammengehen.
     *
     * @param int  $offset      Anfangsadresse des Satzes in der Datei
     * @param bool $mannschaft  true wertet den Satz als Mannschaftspaarung aus;
     *                          dann steht in Byte 0x08 der Spielort statt der Farbe
     *
     * @return array<string,mixed> Felder farbe, gegner, gegnerName, ergebnis,
     *                             ergebnisText, ergebniscode, brettergebnis,
     *                             mannschaftsergebnis, tisch, brett, status
     */
    private function lesePaarung(int $offset, bool $mannschaft): array
    {
        $farbcode = $this->byte($offset + 0x08);

        // Gegner- und Tischnummer belegen je zwei Byte. Bei Turnieren mit mehr
        // als 255 Teilnehmern zeigt sonst jeder Gegner ab Nummer 256 auf den
        // falschen Spieler.
        $gegner = $this->wort($offset + 0x09);
        $ergebniscode = $this->byte($offset + 0x0B);
        $statuscode = $this->byte($offset + 0x0F);

        $brettergebnis = null;
        $mannschaftsergebnis = null;

        if ($this->halbbyteKodierung) {
            // Je Partie ein Halbbyte, ausgewertet werden davon die unteren
            // zwei Bit. Eine Trennung in Brett- und Mannschaftspunkte gibt es
            // in dieser Aufteilung nicht.
            $erste = $this->ergebniswert($ergebniscode & 0x03);
            $zweite = $this->ergebniswert(($ergebniscode >> 4) & 0x03);
            $ergebnis = (null === $erste && null === $zweite)
                ? null
                : ($erste ?? 0.0) + ($zweite ?? 0.0);

            return $this->paarungssatz(
                $offset,
                $mannschaft,
                $farbcode,
                $gegner,
                $ergebniscode,
                $statuscode,
                $ergebnis,
                null,
                null
            );
        }

        $unten = $this->ergebniswert($ergebniscode & 0x03);
        $oben = $this->ergebniswert(($ergebniscode >> 2) & 0x03);

        if ($mannschaft) {
            $ergebnis = $oben;
            $brettergebnis = $unten;
        } elseif ($this->turnier['mannschaftsturnier']) {
            // Im Mannschaftsturnier ist die obere Hälfte immer der Ausgang des
            // Mannschaftskampfes — auch wenn im Kopf „zwei Partien je Runde"
            // steht. Diese Angabe ist in Mannschaftsdateien nicht verlässlich
            // belegt; würde man sie hier auswerten, käme jede Partie doppelt
            // in die Punktzahl.
            $ergebnis = $unten;
            $mannschaftsergebnis = $oben;
        } elseif ((int) $this->turnier['partienProRunde'] > 1) {
            $ergebnis = (null === $unten && null === $oben) ? null : ($unten ?? 0.0) + ($oben ?? 0.0);
        } else {
            $ergebnis = $unten;
        }

        return $this->paarungssatz(
            $offset,
            $mannschaft,
            $farbcode,
            $gegner,
            $ergebniscode,
            $statuscode,
            $ergebnis,
            $brettergebnis,
            $mannschaftsergebnis
        );
    }

    /**
     * Stellt einen ausgewerteten Paarungssatz zusammen.
     *
     * Die Aufteilung des Ergebnisbytes unterscheidet sich zwischen den
     * Dateiformaten, alles Übrige nicht. Diese Methode bündelt das Gemeinsame,
     * damit lesePaarung() nur noch die Ergebnisse ermitteln muss.
     *
     * @param int        $offset              Anfangsadresse des Satzes
     * @param bool       $mannschaft          Mannschafts- statt Einzelpaarung
     * @param int        $farbcode            Byte an Satzadresse 0x08
     * @param int        $gegner              Nummer des Gegners
     * @param int        $ergebniscode        unausgewertetes Ergebnisbyte
     * @param int        $statuscode          Byte an Satzadresse 0x0F
     * @param float|null $ergebnis            Punkte aus dieser Paarung
     * @param float|null $brettergebnis       nur bei Mannschaftspaarungen
     * @param float|null $mannschaftsergebnis nur bei Mannschaftsturnieren ab Fassung 8
     *
     * @return array<string,mixed>
     */
    private function paarungssatz(
        int $offset,
        bool $mannschaft,
        int $farbcode,
        int $gegner,
        int $ergebniscode,
        int $statuscode,
        ?float $ergebnis,
        ?float $brettergebnis,
        ?float $mannschaftsergebnis
    ): array {
        $gegnerListe = $mannschaft ? $this->mannschaften : $this->spieler;

        return [
            'farbe' => ($mannschaft ? self::ORT[$farbcode] : self::FARBE[$farbcode]) ?? sprintf('unbekannt (%d)', $farbcode),
            'gegner' => $gegner,
            'gegnerName' => $gegnerListe[$gegner]['name'] ?? '',
            'ergebnis' => $ergebnis,
            'ergebnisText' => $this->ergebnistext($ergebnis),
            'ergebniscode' => $ergebniscode,
            'brettergebnis' => $brettergebnis,
            'mannschaftsergebnis' => $mannschaftsergebnis,
            'tisch' => $this->wort($offset + 0x0D),
            'brett' => $this->byte($offset + 0x12),
            'status' => self::STATUS[$statuscode] ?? sprintf('unbekannt (%d)', $statuscode),
        ];
    }

    /**
     * Bestimmt, wie viele Paarungssätze je Teilnehmer gespeichert sind und wo
     * der Karteikartenbereich beginnt.
     *
     * Die naheliegende Rechnung „Teilnehmer × Runden" greift zu kurz. Bei
     * doppelrundigen Turnieren legt Swiss-Chess für jeden Durchgang einen
     * eigenen Satz an; die Kopfangabe „Runden" zählt aber nur die Runden eines
     * Durchgangs. Außerdem legt das Programm auch bei Einzelturnieren eine
     * Mannschaftskarteikarte an, sobald das Feld „Mannschaftszahl" belegt ist —
     * ohne dass es dazu Mannschaftspaarungen gäbe.
     *
     * Weil die Dateigröße durch die Aufteilung vollständig festgelegt ist,
     * lässt sich die Satzzahl aus ihr zurückrechnen. Das ist der zuverlässigere
     * Weg, denn er kommt ohne Annahmen darüber aus, ob Swiss-Chess Platz für
     * alle geplanten oder nur für die ausgelosten Runden reserviert. Nur wenn
     * die Rückrechnung kein sinnvolles Ergebnis liefert — etwa weil an die
     * Datei etwas angehängt wurde —, greift die Rechnung aus den Kopfangaben.
     *
     * Geprüft an 46 Turnierdateien der Fassungen 9.11 und 9.30; für alle geht
     * die Rechnung ohne Rest auf.
     *
     * @return void Das Ergebnis landet in $this->rundenSaetze und
     *              $this->startKarten
     */
    private function bestimmeAufteilung(): void
    {
        $teilnehmer = (int) $this->turnier['teilnehmerzahl'];
        $mannschaften = (int) $this->turnier['mannschaftszahl'];
        $durchgaenge = max(1, (int) $this->turnier['durchgaenge']);

        // Karteikarten gibt es für alle Teilnehmer und alle Mannschaften,
        // Paarungssätze dagegen nur für Mannschaften eines Mannschaftsturniers.
        $anzahlKarten = $teilnehmer + $mannschaften;
        $satzTeiler = $teilnehmer + ($this->turnier['mannschaftsturnier'] ? $mannschaften : 0);

        $nominal = $this->turnier['ausgelosteRunden'] > 0
            ? (int) $this->turnier['runden'] * $durchgaenge
            : 0;

        // Ein Satz mehr als geplante Runden ist zulässig: Einzelne Dateien
        // führen einen Reservesatz mit, der nie belegt wird. Mehr als das wäre
        // kein Reservesatz mehr, sondern ein Zeichen dafür, dass die Rechnung
        // nur zufällig aufgeht.
        $obergrenze = (int) $this->turnier['runden'] * $durchgaenge + 1;

        $this->rundenSaetze = $nominal;
        $bestaetigt = false;

        // Beide bekannten Aufteilungen durchprobieren, die zur Version
        // passende zuerst. Die Versionsangabe allein reicht nicht: Bei sehr
        // alten Dateien steht an 0x0261 nicht zwingend eine brauchbare Zahl.
        // Die Dateigröße dagegen ist durch die Aufteilung vollständig
        // festgelegt und entscheidet die Frage eindeutig.
        $kandidaten = [
            [self::START_AB_V800, self::LAENGE_KARTE_AB_V800],
            [self::START_VOR_V800, self::LAENGE_KARTE_VOR_V800],
        ];

        if ($this->startPaarungen === self::START_VOR_V800) {
            $kandidaten = array_reverse($kandidaten);
        }

        if ($satzTeiler > 0) {
            $blockgroesse = $satzTeiler * self::LAENGE_PAARUNG;

            foreach ($kandidaten as [$start, $laenge]) {
                $rest = \strlen($this->inhalt) - $start - $anzahlKarten * $laenge;

                if ($rest < 0 || 0 !== $rest % $blockgroesse) {
                    continue;
                }

                $ausDatei = intdiv($rest, $blockgroesse);

                if ($ausDatei > $obergrenze) {
                    continue;
                }

                if ($start !== $this->startPaarungen) {
                    $this->hinweise[] = sprintf(
                        'Die Datei nennt Fassung %d, ihr Aufbau entspricht aber %s.'
                        .' Ausgewertet wurde nach dem Aufbau, weil nur er zur Dateigröße passt.',
                        (int) $this->turnier['version'],
                        self::START_AB_V800 === $start ? 'dem Format ab Fassung 8' : 'dem Format vor Fassung 8'
                    );
                    $this->startPaarungen = $start;
                    $this->laengeKarte = $laenge;
                }

                $this->rundenSaetze = $ausDatei;
                $bestaetigt = true;

                break;
            }
        }

        if (0 === $teilnehmer) {
            $this->hinweise[] = 'Die Datei nennt keine Teilnehmer. Entweder ist das Turnier leer,'
                .' oder der Kopfbereich hat einen anderen Aufbau als erwartet.';
        } elseif (!$bestaetigt) {
            $this->hinweise[] = sprintf(
                'Die Dateigröße (%d Byte) passt nicht zu %d Teilnehmern, %d Runden und %d Durchgängen.'
                .' Die Karteikarten wurden bei %d vermutet; die Auswertung kann daneben liegen.',
                \strlen($this->inhalt),
                $teilnehmer,
                (int) $this->turnier['runden'],
                $durchgaenge,
                $this->startPaarungen + $this->rundenSaetze * $satzTeiler * self::LAENGE_PAARUNG
            );
        }

        $this->turnier['rundenGespeichert'] = $this->rundenSaetze;
        $this->turnier['aufteilungBestaetigt'] = $bestaetigt;
        $this->startKarten = $this->startPaarungen + $this->rundenSaetze * $satzTeiler * self::LAENGE_PAARUNG;
    }

    /**
     * Vergleicht die Punktzahlen der Karteikarten mit den Rundenergebnissen.
     *
     * Beides steht getrennt in der Datei, und beides muss zusammenpassen —
     * wenn die Datei in sich stimmig ist. Weicht es ab, hat das in aller Regel
     * einen harmlosen Grund: Swiss-Chess schreibt die Punktzahlen erst beim
     * Berechnen der Rangliste fort. Wurde nach der letzten Berechnung noch
     * eine Runde eingegeben, hinkt die Rangliste den Ergebnissen hinterher.
     * Genau dieser Fall lässt sich hier erkennen, indem geprüft wird, ob die
     * Karteikarten zu einer *früheren* Runde passen.
     *
     * Die Prüfung dient zugleich als Selbstkontrolle der Auswertung: Läge der
     * Karteikartenbereich falsch, könnten die Zahlen kaum zufällig für alle
     * Teilnehmer aufgehen.
     *
     * Bei KO-Turnieren entfällt die Prüfung — dort führt Swiss-Chess gar keine
     * Punktzahlen.
     *
     * @return void Etwaige Abweichungen landen in $this->hinweise
     */
    private function pruefeStimmigkeit(): void
    {
        if (2 === (int) $this->turnier['modus'] || $this->rundenSaetze < 1) {
            return;
        }

        $spieler = array_filter($this->spieler, static fn (array $s): bool => !$s['spielfrei']);

        if (!$spieler) {
            return;
        }

        // Sonderfall: Turnier angelegt und ausgelost, aber nie eine Rangliste
        // berechnet. Dann stehen alle Karteikarten auf null, obwohl Paarungen
        // und Ergebnisse vorhanden sind. Ein Vergleich wäre hier sinnlos.
        $ohneWerte = array_filter(
            $spieler,
            static fn (array $s): bool => 0 === (int) $s['partien'] && 0.0 === (float) $s['punkte']
        );

        if (\count($ohneWerte) === \count($spieler)) {
            $this->hinweise[] = 'In dieser Datei wurde nie eine Rangliste berechnet: Punkte, Partien und'
                .' Platzierungen aller Karteikarten stehen auf null. Paarungen und Ergebnisse sind'
                .' vorhanden und werden ausgewertet.';

            return;
        }

        // Für jede Rundenzahl zählen, wie viele Teilnehmer damit aufgehen.
        // Der beste Wert verrät, auf welchen Stand sich die Rangliste bezieht.
        $beste = 0;
        $besteRunde = $this->rundenSaetze;

        for ($bis = $this->rundenSaetze; $bis >= 1; --$bis) {
            $stimmig = 0;

            foreach ($spieler as $tnr => $daten) {
                $summe = 0.0;

                for ($runde = 1; $runde <= $bis; ++$runde) {
                    $summe += $this->paarungen[$tnr][$runde]['ergebnis'] ?? 0.0;
                }

                if (abs($summe - ($daten['punkte'] - $daten['sonderpunkte'])) < 0.01) {
                    ++$stimmig;
                }
            }

            if ($stimmig > $beste) {
                $beste = $stimmig;
                $besteRunde = $bis;
            }

            if ($stimmig === \count($spieler)) {
                break;
            }
        }

        $anteil = $beste / \count($spieler);

        if ($besteRunde === $this->rundenSaetze && $anteil > 0.95) {
            return;
        }

        if ($anteil > 0.95) {
            $this->hinweise[] = sprintf(
                'Die Rangliste in der Datei gibt den Stand nach Runde %d wieder, gespeichert sind aber'
                .' %d Runden. Die Ergebnisse der Runden %d bis %d sind eingetragen, aber noch nicht in'
                .' die Punktzahlen eingerechnet.',
                $besteRunde,
                $this->rundenSaetze,
                $besteRunde + 1,
                $this->rundenSaetze
            );

            return;
        }

        $this->hinweise[] = sprintf(
            'Die Punktzahlen der Karteikarten gehen nicht mit den Rundenergebnissen zusammen:'
            .' nur %d von %d Teilnehmern stimmen überein (bestenfalls nach Runde %d).'
            .' Die Rundenergebnisse sind hier verlässlicher als die Rangliste.',
            $beste,
            \count($spieler),
            $besteRunde
        );
    }

    /**
     * Erkennt den Platzhalterteilnehmer, mit dem Swiss-Chess spielfreie
     * Runden abbildet.
     *
     * Bei ungerader Teilnehmerzahl legt das Programm einen zusätzlichen
     * Teilnehmer an, gegen den spielt, wer aussetzt. Meist trägt er den Namen
     * „spielfrei"; in einigen Dateien bleibt das Namensfeld aber leer. Beide
     * Fälle müssen erkannt werden, sonst taucht der Platzhalter in der
     * Rangliste auf und die Paarungen jeder Runde enthalten eine Scheinpartie.
     *
     * @param string $name Der Name aus der Karteikarte
     *
     * @return bool true, wenn es sich um den Platzhalter handelt
     */
    private function istPlatzhalter(string $name): bool
    {
        $name = strtolower(trim($name));

        return '' === $name || \in_array($name, ['spielfrei', 'bye'], true);
    }

    /**
     * Prüft, ob für eine Runde überhaupt Paarungen eingetragen sind.
     *
     * Swiss-Chess reserviert den Platz für alle Runden auf einmal. Bei einem
     * noch laufenden Turnier stehen in den hinteren Runden deshalb lauter
     * Nullbytes, die sonst als lauter spielfreie Teilnehmer erschienen. Als
     * belegt gilt eine Runde, sobald mindestens ein Teilnehmer einen Gegner
     * hat.
     *
     * @param int $runde Rundennummer, beginnend bei 1
     *
     * @return bool true, wenn die Runde ausgelost ist
     */
    private function rundeBelegt(int $runde): bool
    {
        foreach ($this->paarungen as $runden) {
            if (($runden[$runde]['gegner'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bestimmt die Turnierwertungszahl eines Spielers aus Elo und DWZ.
     *
     * Welche der beiden Zahlen den Vorrang hat, legt die Turniereinstellung
     * „TWZ-Ermittlung" fest. Bei der Einstellung „Elo oder DWZ" ist aus den
     * Beispieldateien nicht belegbar, welche Zahl Swiss-Chess wählt; hier wird
     * die höhere genommen, weil das der üblichen Handhabung entspricht.
     *
     * @param int $elo FIDE-Elo, 0 wenn nicht gesetzt
     * @param int $dwz DWZ beziehungsweise NWZ, 0 wenn nicht gesetzt
     *
     * @return int Die Turnierwertungszahl, 0 wenn der Spieler keine hat
     */
    private function ermittleTwz(int $elo, int $dwz): int
    {
        switch ((int) $this->turnier['twzErmittlung']) {
            case 1:
                return $dwz ?: $elo;

            case 2:
                return max($elo, $dwz);

            default:
                return $elo ?: $dwz;
        }
    }

    /**
     * Liefert den Teiler, mit dem ein Feinwertungsfeld zurückzurechnen ist.
     *
     * Swiss-Chess speichert Feinwertungen als ganze Zahlen. Normalerweise ist
     * der Wert verdoppelt, damit halbe Punkte darstellbar sind. Bei
     * Sonneborn-Berger entstehen Viertelpunkte (halbe Punkte gegen einen
     * Gegner mit halben Punkten), deshalb ist der Wert dort vervierfacht.
     *
     * @param int $nummer 1 oder 2 für die erste beziehungsweise zweite Feinwertung
     *
     * @return int 4 bei Sonneborn-Berger, sonst 2
     */
    private function feinwertungsteiler(int $nummer): int
    {
        $art = (int) ($this->turnier[1 === $nummer ? 'feinwertung1' : 'feinwertung2'] ?? 0);

        return 0x05 === $art ? 4 : 2;
    }

    /**
     * Stellt die Kurzangaben eines Spielers für Partienlisten zusammen.
     *
     * Für Paarungslisten sind nur wenige Felder interessant; die vollständige
     * Karteikarte würde die Ausgabe unnötig aufblähen.
     *
     * @param int $tnr Teilnehmernummer
     *
     * @return array<string,mixed> Felder tnr, name, titel, twz, verein, land,
     *                             punkte; leere Werte, wenn die Nummer unbekannt ist
     */
    private function spielerKurz(int $tnr): array
    {
        $spieler = $this->spieler[$tnr] ?? null;

        if (null === $spieler) {
            return ['tnr' => $tnr, 'name' => '', 'titel' => '', 'twz' => 0, 'verein' => '', 'land' => '', 'punkte' => 0.0];
        }

        return [
            'tnr' => $spieler['tnr'],
            'name' => $spieler['name'],
            'titel' => $spieler['titel'],
            'twz' => $spieler['twz'],
            'verein' => $spieler['mannschaft'],
            'land' => $spieler['land'],
            'punkte' => $spieler['punkte'],
        ];
    }

    /**
     * Wandelt einen Paarungssatz in das Symbol einer Kreuztabelle.
     *
     * Kampflose Partien werden wie in der Ausgabe von Swiss-Chess mit `+` und
     * `-` gekennzeichnet, damit sie sich von gespielten Partien unterscheiden.
     *
     * @param array<string,mixed> $satz Ein Eintrag aus $this->paarungen
     *
     * @return string Ein Symbol: 1, ½, 0, +, - oder leer
     */
    private function ergebnisSymbol(array $satz): string
    {
        $kampflos = 'kampflos' === $satz['status'] || 'nicht eingesetzt' === $satz['status'];

        if ($kampflos && null !== $satz['ergebnis']) {
            if (1.0 === $satz['ergebnis']) {
                return '+';
            }

            if (0.0 === $satz['ergebnis']) {
                return '-';
            }
        }

        return $satz['ergebnisText'];
    }

    /**
     * Wandelt den gespeicherten Ergebniscode in eine Punktzahl.
     *
     * @param int $code Zwei-Bit-Wert aus dem Ergebnisbyte des Paarungssatzes
     *
     * @return float|null 0.0, 0.5 oder 1.0; null wenn noch kein Ergebnis
     *                    eingetragen ist
     */
    private function ergebniswert(int $code): ?float
    {
        switch ($code) {
            case 1: return 0.0;
            case 2: return 0.5;
            case 3: return 1.0;
            default: return null;
        }
    }

    /**
     * Setzt eine Punktzahl in die im Schach übliche Schreibweise um.
     *
     * Halbe Punkte werden als ½ geschrieben, bei Turnieren mit zwei Partien je
     * Runde auch zusammengesetzt: aus 1.5 wird „1½".
     *
     * @param float|null $wert Die Punktzahl, null wenn kein Ergebnis vorliegt
     *
     * @return string Die Schreibweise, leer bei fehlendem Ergebnis
     */
    private function ergebnistext(?float $wert): string
    {
        if (null === $wert) {
            return '';
        }

        $ganze = (int) floor($wert);
        $halb = abs($wert - $ganze) > 0.01;

        if (!$halb) {
            return (string) $ganze;
        }

        return ($ganze > 0 ? (string) $ganze : '').'½';
    }

    /**
     * Liest ein einzelnes Byte als vorzeichenlose Zahl.
     *
     * @param int $offset Adresse in der Datei
     *
     * @return int Wert zwischen 0 und 255; 0 wenn die Adresse hinter dem
     *             Dateiende liegt, damit unvollständige Dateien nicht zum
     *             Abbruch führen
     */
    private function byte(int $offset): int
    {
        return isset($this->inhalt[$offset]) ? \ord($this->inhalt[$offset]) : 0;
    }

    /**
     * Liest zwei Byte als vorzeichenlose Zahl im Rechnerformat.
     *
     * Swiss-Chess legt Zahlen mit dem niederwertigen Byte zuerst ab (Intel-
     * Reihenfolge). Betroffen sind Dateiversion, Punktzahlen und
     * Feinwertungen — überall dort, wo Werte über 255 vorkommen können.
     *
     * @param int $offset Adresse des ersten Bytes
     *
     * @return int Wert zwischen 0 und 65535
     */
    private function wort(int $offset): int
    {
        return $this->byte($offset) + ($this->byte($offset + 1) << 8);
    }

    /**
     * Liest zwei Byte als Zahl mit Vorzeichen im Zweierkomplement.
     *
     * Einzelne Felder benutzen −1 (in der Datei FF FF) als Kennzeichen für
     * „nicht gesetzt", etwa die Zahl der ausgesetzten Runden. Ohne Vorzeichen
     * gelesen käme dort 65535 heraus.
     *
     * @param int $offset Adresse des ersten Bytes
     *
     * @return int Wert zwischen −32768 und 32767
     */
    private function ganzzahlMitVorzeichen(int $offset): int
    {
        $wert = $this->wort($offset);

        return $wert >= 0x8000 ? $wert - 0x10000 : $wert;
    }

    /**
     * Liest einen Textbereich und gibt ihn als UTF-8 zurück.
     *
     * Texte stehen in der Datei mit fester Länge. Das erste Nullbyte beendet
     * den Text; dahinter stehen Reste früherer Eingaben, die verworfen werden
     * müssen. Die Zeichen selbst sind nach Windows-1252 kodiert, Umlaute
     * belegen also nur ein Byte.
     *
     * @param int $von  Erste Adresse des Bereichs
     * @param int $bis  Letzte Adresse des Bereichs, einschließlich
     *
     * @return string Der Text ohne Nullbyte und ohne umgebende Leerzeichen
     */
    private function text(int $von, int $bis): string
    {
        $roh = substr($this->inhalt, $von, $bis - $von + 1);
        $ende = strpos($roh, "\0");

        if (false !== $ende) {
            $roh = substr($roh, 0, $ende);
        }

        return trim($this->nachUtf8($roh));
    }

    /**
     * Wandelt eine Zeichenkette von Windows-1252 nach UTF-8.
     *
     * Bevorzugt wird mbstring, ersatzweise iconv. Fehlen beide, greift eine
     * einfache Umsetzung nach ISO-8859-1; sie unterscheidet sich von
     * Windows-1252 nur im Bereich 0x80 bis 0x9F, in dem in Turnierdateien
     * praktisch nur typografische Anführungszeichen vorkommen.
     *
     * @param string $text Zeichenkette in Windows-1252
     *
     * @return string Dieselbe Zeichenkette in UTF-8
     */
    private function nachUtf8(string $text): string
    {
        if ('' === $text) {
            return '';
        }

        if (\function_exists('mb_convert_encoding')) {
            return (string) mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        if (\function_exists('iconv')) {
            $umgewandelt = @iconv('Windows-1252', 'UTF-8//TRANSLIT', $text);

            if (false !== $umgewandelt) {
                return $umgewandelt;
            }
        }

        $ausgabe = '';

        for ($i = 0, $laenge = \strlen($text); $i < $laenge; ++$i) {
            $zeichen = \ord($text[$i]);
            $ausgabe .= $zeichen < 0x80
                ? $text[$i]
                : \chr(0xC0 | ($zeichen >> 6)).\chr(0x80 | ($zeichen & 0x3F));
        }

        return $ausgabe;
    }

    /**
     * Formatiert eine Punktzahl für die Textausgabe.
     *
     * Ganze Punkte erscheinen ohne Nachkommastellen, halbe mit einer,
     * Viertelpunkte aus der Sonneborn-Berger-Wertung mit zweien.
     *
     * @param float $punkte Die Punktzahl
     *
     * @return string Die formatierte Zahl
     */
    private function punkteText(float $punkte): string
    {
        if ($punkte === floor($punkte)) {
            return number_format($punkte, 0, ',', '');
        }

        return number_format($punkte, 0.0 === fmod($punkte, 0.5) ? 1 : 2, ',', '');
    }

    /**
     * Bringt einen Text für die Textausgabe auf eine feste Spaltenbreite.
     *
     * Zu langer Text wird gekürzt, zu kurzer mit Leerzeichen aufgefüllt. Die
     * Rechnung erfolgt in Zeichen statt in Bytes: Ein „ß" belegt in UTF-8 zwei
     * Bytes, weshalb `sprintf('%-26s', …)` bei Namen wie „Rennspieß" eine
     * Stelle zu wenig auffüllt und die Spalten verrutschen.
     *
     * @param string $text   Der auszugebende Text
     * @param int    $breite Gewünschte Breite in Zeichen
     *
     * @return string Der Text mit genau $breite Zeichen
     */
    private function spalte(string $text, int $breite): string
    {
        if (\function_exists('mb_substr')) {
            $text = mb_substr($text, 0, $breite);
            $fehlend = $breite - mb_strlen($text);
        } else {
            $text = substr($text, 0, $breite);
            $fehlend = $breite - \strlen($text);
        }

        return $text.str_repeat(' ', max(0, $fehlend));
    }
}
