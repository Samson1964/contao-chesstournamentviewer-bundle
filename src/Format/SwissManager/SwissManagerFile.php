<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Format\SwissManager;

/**
 * Liest die Turnierdateien des Programms Swiss-Manager von Heinz Herzog.
 *
 * Die Endungen sind `.TUNx` (Einzelturnier nach Schweizer System), `.TURx`
 * (Rundenturnier), `.TUMx` (Mannschaftsturnier nach Schweizer System) und
 * `.TUTx` (Mannschafts-Rundenturnier); die älteren ASCII-Fassungen führen
 * dieselben Endungen ohne das `x`. Der Aufbau ist in allen vier gleich, nur
 * die Mannschaftsabschnitte fehlen bei Einzelturnieren.
 *
 * ## Aufbau der Datei
 *
 * Die Datei besteht aus Abschnitten. Jeder beginnt mit einer vier Byte langen
 * Marke `KK FF 89 44`, wobei `KK` den Abschnitt benennt:
 *
 * | Marke | Inhalt |
 * | --- | --- |
 * | `93` | Dateikopf: Turniername, Ort, Schiedsrichter, Bedenkzeit … |
 * | `95` | Einstellungen; an erster Stelle die Rundenzahl |
 * | `A3` | Termine der Runden |
 * | `A5` | Teilnehmer |
 * | `B3` | Partien, in Rundenfolge |
 * | `B5` | Mannschaften (nur bei Mannschaftsturnieren) |
 * | `C3` | leer in allen bekannten Dateien |
 * | `D3` | Verzeichnis der Abschnittsadressen |
 * | `E3` | Dateiende |
 *
 * Zeichenketten stehen als Zeichenzahl in einem 16-Bit-Wort, gefolgt von
 * ebenso vielen Zeichen in UTF-16LE. **Die Abschnitte sind nicht auf gerade
 * Adressen ausgerichtet**; wer die Datei wortweise durchsucht, findet die
 * Teilnehmer deshalb nicht.
 *
 * ## Was diese Klasse nicht kann
 *
 * Punkte, Platzierungen und Feinwertungen stehen **nicht** in der Datei —
 * Swiss-Manager rechnet sie bei jeder Anzeige neu. Die Punkte bildet deshalb
 * der Aufrufer aus den Partien; die Feinwertungen bleiben offen, weil in der
 * Datei nicht steht, welche eingestellt sind.
 *
 * Der Aufbau wurde an drei Dateien erarbeitet und gegen die Ausgaben von
 * chess-results.com geprüft. Nicht abgesichert ist alles, was in diesen drei
 * Dateien nicht vorkommt: spielfreie Runden, kampflose Partien und gespielte
 * Mannschaftsturniere.
 */
class SwissManagerFile
{
    /**
     * Länge eines Partiesatzes im Abschnitt B3.
     */
    private const LAENGE_PARTIE = 21;

    /**
     * Länge eines Wettkampfsatzes im Abschnitt C3.
     *
     * Sechs Byte kürzer als ein Partiesatz — ein Ergebnis führt er nicht, denn
     * das ergibt sich aus den Brettern.
     */
    private const LAENGE_WETTKAMPF = 15;

    /**
     * Zahl der Zeichenkettenfelder einer Teilnehmerkarte.
     */
    private const TEXTE_SPIELER = 33;

    /**
     * Länge des Zahlenblocks hinter den Zeichenketten einer Teilnehmerkarte.
     */
    private const ZAHLEN_SPIELER = 104;

    /**
     * Zahl der Zeichenkettenfelder einer Mannschaftskarte.
     */
    private const TEXTE_MANNSCHAFT = 27;

    /**
     * Länge des Zahlenblocks hinter den Zeichenketten einer Mannschaftskarte.
     */
    private const ZAHLEN_MANNSCHAFT = 52;

    /**
     * Ergebnisschlüssel der Partiesätze.
     *
     * Gerechnet wird aus Sicht von Weiß. Der Wert 0 heißt „noch kein
     * Ergebnis"; die Datei führt nicht gespielte Runden gar nicht erst auf.
     */
    private const ERGEBNIS = [
        1 => 1.0,
        2 => 0.5,
        3 => 0.0,
        4 => 1.0,
        5 => 0.0,
        9 => 1.0,
    ];

    /**
     * Ergebnisschlüssel, die eine kampflos entschiedene Partie kennzeichnen.
     *
     * Bei ihnen steht auf der unterlegenen Seite die Nummer 0 — es saß dort
     * niemand am Brett.
     */
    private const KAMPFLOS = [4, 5];

    /**
     * Ab dieser Gegnernummer steht kein Spieler, sondern ein Sonderfall.
     *
     * `0xFFFF` heißt „spielfrei" — der Teilnehmer wurde nicht ausgelost und
     * bekommt einen Punkt gutgeschrieben (Ergebnisschlüssel 9). `0xFFFE`
     * heißt „nicht ausgelost": Er fehlte in dieser Runde und bekommt nichts
     * (Ergebnisschlüssel 3). Beide Fälle stehen am Ende ihrer Runde, hinter
     * den gespielten Partien.
     */
    private const OHNE_GEGNER = 0xFFF0;

    /**
     * Gegnernummer für eine spielfreie Runde mit Punktgutschrift.
     */
    private const SPIELFREI = 0xFFFF;

    /**
     * Die Adressen der gefundenen Abschnitte, Marke als Schlüssel.
     *
     * @var array<int,int>
     */
    private array $abschnitte = [];

    /**
     * Die Kopfdaten des Turniers.
     *
     * @var array<string,mixed>
     */
    private array $turnier = [];

    /**
     * Die Teilnehmer, Schlüssel ist die Startnummer ab 1.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $spieler = [];

    /**
     * Die Mannschaften, Schlüssel ist die Startnummer ab 1.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $mannschaften = [];

    /**
     * Die Partien je Runde, aufsteigend nach Brett.
     *
     * @var array<int,array<int,array<string,mixed>>>
     */
    private array $partien = [];

    /**
     * Die Wettkämpfe je Runde, aufsteigend nach Tisch.
     *
     * @var array<int,array<int,array<string,mixed>>>
     */
    private array $wettkaempfe = [];

    /**
     * Meldungen der Selbstkontrolle.
     *
     * @var string[]
     */
    private array $hinweise = [];

    /**
     * Liest eine Swiss-Manager-Datei ein.
     *
     * @param string $inhalt    Der vollständige Dateiinhalt
     * @param string $dateiname Name der Datei, nur für Fehlermeldungen
     *
     * @throws \RuntimeException wenn die Datei keine Swiss-Manager-Datei ist
     *                           oder ihre Abschnitte nicht auffindbar sind
     */
    public function __construct(
        private readonly string $inhalt,
        private readonly string $dateiname = '',
    ) {
        if (!self::istSwissManager($inhalt)) {
            throw new \RuntimeException(sprintf('%s ist keine Swiss-Manager-Datei.', $dateiname ?: 'Die Datei'));
        }

        $this->sucheAbschnitte();
        $this->leseKopf();
        $this->leseSpieler();
        $this->leseMannschaften();
        $this->lesePartien();
        $this->leseWettkaempfe();
    }

    /**
     * Prüft, ob ein Dateiinhalt von Swiss-Manager stammt.
     *
     * Geprüft werden die Marke des Dateikopfes und das Vorhandensein des
     * Abschnittsverzeichnisses. Beides zusammen kommt in anderen Formaten
     * nicht vor; die Marke allein wäre eine schwache Aussage.
     *
     * @param string $inhalt Der Dateiinhalt
     *
     * @return bool Wahr, wenn es eine Swiss-Manager-Datei ist
     */
    public static function istSwissManager(string $inhalt): bool
    {
        if (!str_starts_with($inhalt, "\x93\xff\x89\x44")) {
            return false;
        }

        return str_contains($inhalt, "\xd3\xff\x89\x44") && str_contains($inhalt, "\xa5\xff\x89\x44");
    }

    /**
     * Gibt die Kopfdaten des Turniers zurück.
     *
     * @return array<string,mixed> Name, Ort, Bedenkzeit, Rundenzahl und mehr
     */
    public function getTurnier(): array
    {
        return $this->turnier;
    }

    /**
     * Gibt die Teilnehmer zurück.
     *
     * Die Punkte stehen auf 0: Swiss-Manager speichert sie nicht, sie sind
     * aus den Partien zu bilden.
     *
     * @return array<int,array<string,mixed>> Teilnehmer je Startnummer
     */
    public function getSpieler(): array
    {
        return $this->spieler;
    }

    /**
     * Gibt die Mannschaften zurück.
     *
     * @return array<int,array<string,mixed>> Mannschaften je Startnummer;
     *                                        bei Einzelturnieren leer
     */
    public function getMannschaften(): array
    {
        return $this->mannschaften;
    }

    /**
     * Gibt die Partien je Runde zurück.
     *
     * @return array<int,array<int,array<string,mixed>>> Partien je Rundennummer
     */
    public function getPartien(): array
    {
        return $this->partien;
    }

    /**
     * Gibt die Wettkämpfe je Runde zurück.
     *
     * Jeder Wettkampf nennt Tisch, Heim- und Gastmannschaft. Brett- und
     * Mannschaftspunkte stehen nicht in der Datei; sie sind aus den
     * Einzelpartien zu bilden.
     *
     * @return array<int,array<int,array<string,mixed>>> Wettkämpfe je Runde
     */
    public function getWettkaempfe(): array
    {
        return $this->wettkaempfe;
    }

    /**
     * Gibt die Meldungen der Selbstkontrolle zurück.
     *
     * @return string[] Hinweise für den Betrachter
     */
    public function getHinweise(): array
    {
        return $this->hinweise;
    }

    /**
     * Sagt, ob die Datei ein Mannschaftsturnier beschreibt.
     *
     * @return bool Wahr, wenn Mannschaften eingetragen sind
     */
    public function istMannschaftsturnier(): bool
    {
        return [] !== $this->mannschaften;
    }

    /**
     * Sucht die Marken aller Abschnitte.
     *
     * Gesucht wird nach dem Muster `?? FF 89 44`. Die Marken stehen an
     * beliebigen, auch ungeraden Adressen; das Abschnittsverzeichnis am
     * Dateiende führt zwar Adressen, nennt aber nicht alle Abschnitte, und
     * deshalb ist die Suche der verlässlichere Weg.
     *
     * @return void
     */
    private function sucheAbschnitte(): void
    {
        $laenge = \strlen($this->inhalt);

        for ($i = 0; $i + 3 < $laenge; ++$i) {
            if ("\xff\x89\x44" !== substr($this->inhalt, $i + 1, 3)) {
                continue;
            }

            $marke = \ord($this->inhalt[$i]);

            // Die erste Marke jeder Art gilt; eine zweite käme nur durch
            // Zufall in den Daten zustande.
            $this->abschnitte[$marke] ??= $i;
        }
    }

    /**
     * Nennt Anfang und Ende der Nutzdaten eines Abschnitts.
     *
     * Das Ende ist der Anfang der nächsten Marke, gleich welcher — die
     * Abschnitte liegen lückenlos hintereinander.
     *
     * @param int $marke Kennung des Abschnitts, etwa 0xA5
     *
     * @return array{0:int,1:int}|null Anfang und Ende, oder null wenn es den
     *                                 Abschnitt in dieser Datei nicht gibt
     */
    private function bereich(int $marke): ?array
    {
        if (!isset($this->abschnitte[$marke])) {
            return null;
        }

        $anfang = $this->abschnitte[$marke] + 4;
        $ende = \strlen($this->inhalt);

        foreach ($this->abschnitte as $adresse) {
            if ($adresse >= $anfang && $adresse < $ende) {
                $ende = $adresse;
            }
        }

        return [$anfang, $ende];
    }

    /**
     * Liest den Dateikopf und die Einstellungen.
     *
     * Die Reihenfolge der Felder im Kopf ist fest; leere Felder stehen als
     * Zeichenkette der Länge null darin und werden mitgezählt. Die Bedeutung
     * der Felder wurde durch Vergleich mit den Ausgaben auf chess-results.com
     * bestimmt; was sich nicht zuordnen ließ, bleibt ungenannt.
     *
     * @return void
     */
    private function leseKopf(): void
    {
        $offset = 0x6C;
        $texte = [];

        for ($i = 0; $i < 26; ++$i) {
            $texte[$i] = $this->text($offset);
            $offset = $this->naechster($offset);
        }

        $runden = 0;
        $bereich = $this->bereich(0x95);

        if (null !== $bereich) {
            $runden = $this->wort($bereich[0]);
        }

        // Der Ort steht an zwei Stellen: als eigenes Feld und häufig auch im
        // Untertitel, wenn die Turnierleitung ihn dort mit dem Zeitraum
        // zusammen eingetragen hat. Das eigene Feld hat Vorrang.
        $ort = '' !== $texte[10] ? $texte[10] : $texte[1];

        $this->turnier = [
            'name' => $texte[0],
            'untertitel' => $texte[1],
            'bemerkung' => $texte[2],
            'schiedsrichter' => '' !== $texte[21] ? $texte[21] : $texte[3],
            'weitereSchiedsrichter' => trim($texte[6].(('' !== $texte[6] && '' !== $texte[22]) ? ', ' : '').$texte[22]),
            'veranstalter' => $texte[4],
            'spiellokal' => $texte[5],
            'ort' => $ort,
            'ortLand' => $ort,
            'altersgruppen' => $texte[13],
            'bedenkzeit' => $texte[14],
            'land' => $texte[20],
            'email' => $texte[23],
            'internet' => $texte[24],
            'kennung' => $texte[11],
            'dateiname' => $this->dateiname,
            'runden' => $runden,
            'partienProRunde' => 1,
            'mannschaftsturnier' => isset($this->abschnitte[0xB5]),
        ];
    }

    /**
     * Liest die Teilnehmerkarten des Abschnitts A5.
     *
     * Eine Karte besteht aus 34 Zeichenketten und einem Zahlenblock von 102
     * Byte. Die Startnummer ergibt sich aus der Reihenfolge — sie ist zugleich
     * die Nummer, mit der die Partien auf die Teilnehmer verweisen.
     *
     * @return void
     */
    private function leseSpieler(): void
    {
        $bereich = $this->bereich(0xA5);

        if (null === $bereich) {
            return;
        }

        [$offset, $ende] = $bereich;
        $nummer = 0;

        while ($offset + self::ZAHLEN_SPIELER < $ende) {
            $texte = [];

            for ($i = 0; $i < self::TEXTE_SPIELER; ++$i) {
                $texte[$i] = $this->text($offset);
                $offset = $this->naechster($offset);
            }

            $nachname = trim($texte[0]);

            if ('' === $nachname) {
                break;
            }

            $vorname = trim($texte[1]);
            $geburt = $this->langwort($offset + 8);
            ++$nummer;

            $this->spieler[$nummer] = [
                'tnr' => $nummer,
                'startnummer' => $nummer,
                'name' => '' === $vorname ? $nachname : $nachname.','.$vorname,
                'nachname' => $nachname,
                'vorname' => $vorname,
                'titel' => trim($texte[4]),
                'kennung' => trim($texte[5]),
                'mannschaft' => trim($texte[9]),
                'land' => trim($texte[10]),
                'gruppe' => trim($texte[12]),
                'geschlecht' => trim($texte[14]),
                'elo' => $this->wort($offset + 2),
                'dwz' => $this->wort($offset + 4),
                'twz' => 0,
                'geburtsjahr' => intdiv($geburt, 10000),
                'geburtsdatum' => $geburt,
                'fideId' => $this->langwort($offset + 18),
                'mannschaftsnummer' => $this->wort($offset + 22),
                'brett' => $this->wort($offset + 24),
                'punkte' => 0.0,
                'sonderpunkte' => 0.0,
                'platz' => 0,
                'partien' => 0,
                'siege' => 0,
                'remis' => 0,
                'niederlagen' => 0,
                'ausgesetzt' => 0,
                'feinwertung1' => 0.0,
                'feinwertung2' => 0.0,
                'spielfrei' => false,
            ];

            $offset += self::ZAHLEN_SPIELER;
        }

        $this->setzeWertungszahl();
    }

    /**
     * Legt fest, welche Wertungszahl das Turnier führt.
     *
     * Swiss-Manager führt zwei: die internationale Elo-Zahl und eine
     * nationale. Welche das Turnier verwendet, steht nicht in der Datei; es
     * lässt sich aber daran erkennen, dass die Startrangliste nach ihr
     * geordnet ist. Führt keine der beiden Werte, bleibt die Spalte bei null.
     *
     * @return void
     */
    private function setzeWertungszahl(): void
    {
        $summeElo = 0;
        $summeDwz = 0;

        foreach ($this->spieler as $spieler) {
            $summeElo += $spieler['elo'];
            $summeDwz += $spieler['dwz'];
        }

        $feld = $summeDwz > 0 ? 'dwz' : 'elo';

        foreach ($this->spieler as $nummer => $spieler) {
            $this->spieler[$nummer]['twz'] = $spieler[$feld];
        }

        $this->turnier['wertungszahl'] = 'dwz' === $feld ? 'nationale Wertungszahl' : 'Elo';
    }

    /**
     * Liest die Mannschaftskarten des Abschnitts B5.
     *
     * Eine Karte besteht aus 27 Zeichenketten und einem Zahlenblock von 52
     * Byte. Bei Einzelturnieren gibt es den Abschnitt nicht.
     *
     * @return void
     */
    private function leseMannschaften(): void
    {
        $bereich = $this->bereich(0xB5);

        if (null === $bereich) {
            return;
        }

        [$offset, $ende] = $bereich;
        $nummer = 0;

        while ($offset + self::ZAHLEN_MANNSCHAFT < $ende) {
            $texte = [];

            for ($i = 0; $i < self::TEXTE_MANNSCHAFT; ++$i) {
                $texte[$i] = $this->text($offset);
                $offset = $this->naechster($offset);
            }

            $name = trim($texte[0]);

            if ('' === $name) {
                break;
            }

            ++$nummer;

            $this->mannschaften[$nummer] = [
                'nummer' => $nummer,
                'startnummer' => $nummer,
                'name' => $name,
                'kurzname' => trim($texte[1]),
                'kapitaen' => trim($texte[2]),
                'land' => trim($texte[3]),
                'spieler' => [],
                'brettpunkte' => 0.0,
                'mannschaftspunkte' => 0.0,
                'platz' => 0,
                'spielfrei' => false,
            ];

            $offset += self::ZAHLEN_MANNSCHAFT;
        }

        $this->ordneSpielerZu();
    }

    /**
     * Trägt in jede Mannschaft die Nummern ihrer Spieler ein.
     *
     * Maßgeblich ist die Mannschaftsnummer aus der Teilnehmerkarte; die
     * Reihenfolge ist die der Bretter.
     *
     * @return void
     */
    private function ordneSpielerZu(): void
    {
        $nach = [];

        foreach ($this->spieler as $nummer => $spieler) {
            $mnr = (int) $spieler['mannschaftsnummer'];

            if (isset($this->mannschaften[$mnr])) {
                $nach[$mnr][(int) $spieler['brett']] = $nummer;
            }
        }

        foreach ($nach as $mnr => $spieler) {
            ksort($spieler);
            $this->mannschaften[$mnr]['spieler'] = array_values($spieler);
        }
    }

    /**
     * Liest die Partien des Abschnitts B3 und teilt sie auf die Runden auf.
     *
     * Die Sätze stehen als flache Liste in Rundenfolge, innerhalb einer Runde
     * in Brettfolge; eine Rundennummer führen sie nicht. Die Grenze zwischen
     * zwei Runden ist deshalb daran zu erkennen, dass ein Teilnehmer zum
     * zweiten Mal auftaucht — in einer Runde spielt niemand zweimal.
     *
     * Das ist eine Annahme, keine gelesene Angabe. Sie geht schief, wenn eine
     * Runde nur aus Teilnehmern besteht, die in der vorigen Runde spielfrei
     * waren; bei einem Schweizer System kommt das praktisch nicht vor, weil
     * die vorderen Bretter durchweg mit Spielern der Vorrunde besetzt sind.
     * Ein Gegenbeispiel wäre ein Fehler dieser Klasse, kein Zufall.
     *
     * @return void
     */
    private function lesePartien(): void
    {
        $bereich = $this->bereich(0xB3);

        if (null === $bereich) {
            return;
        }

        [$offset, $ende] = $bereich;
        $runde = 1;
        $gesehen = [];
        $brett = 0;

        while ($offset + self::LAENGE_PARTIE <= $ende) {
            $weiss = $this->wort($offset);
            $schwarz = $this->wort($offset + 2);
            $schluessel = $this->wort($offset + 4);
            $offset += self::LAENGE_PARTIE;

            if (0 === $weiss && 0 === $schwarz) {
                continue;
            }

            $weissDa = $this->istSpieler($weiss);
            $schwarzDa = $this->istSpieler($schwarz);

            // Nur wirkliche Spielernummern zählen als „gesehen". Die Null
            // einer kampflosen Partie und die Kennungen einer spielfreien
            // Runde würden sonst einen Rundenwechsel vortäuschen, sobald sie
            // ein zweites Mal auftreten.
            if (($weissDa && isset($gesehen[$weiss])) || ($schwarzDa && isset($gesehen[$schwarz]))) {
                ++$runde;
                $gesehen = [];
                $brett = 0;
            }

            if ($weissDa) {
                $gesehen[$weiss] = true;
            }

            if ($schwarzDa) {
                $gesehen[$schwarz] = true;
            }

            ++$brett;

            $this->partien[$runde][] = [
                'tisch' => $brett,
                'brett' => $brett,
                'weiss' => $this->spielerKurz($weiss),
                'schwarz' => $this->spielerKurz($schwarz),
                'ergebnis' => self::ERGEBNIS[$schluessel] ?? null,
                'ergebnisText' => $this->ergebnistext(self::ERGEBNIS[$schluessel] ?? null),
                'status' => $this->status($schluessel, $schwarz),
                'spielfrei' => !$weissDa || !$schwarzDa,
            ];
        }

        $angekuendigt = (int) ($this->turnier['runden'] ?? 0);

        if ($angekuendigt > 0 && $runde > $angekuendigt && [] !== $this->partien) {
            $this->hinweise[] = sprintf(
                'Die Datei kündigt %d Runden an, die Partien ergeben aber %d. Die Aufteilung auf die Runden kann falsch sein.',
                $angekuendigt,
                $runde
            );
        }
    }

    /**
     * Liest die Wettkämpfe des Abschnitts C3.
     *
     * Der Aufbau ist derselbe wie bei den Einzelpartien, nur stehen dort die
     * Nummern der Mannschaften. Ein Ergebnis nennt der Satz nicht — die
     * Brettpunkte ergeben sich aus den Einzelpartien, und daraus wiederum die
     * Mannschaftspunkte. Deshalb wird hier nur die Begegnung festgehalten.
     *
     * @return void
     */
    private function leseWettkaempfe(): void
    {
        $bereich = $this->bereich(0xC3);

        if (null === $bereich || [] === $this->mannschaften) {
            return;
        }

        [$offset, $ende] = $bereich;
        $runde = 1;
        $gesehen = [];
        $tisch = 0;

        while ($offset + self::LAENGE_WETTKAMPF <= $ende) {
            $eine = $this->wort($offset);
            $andere = $this->wort($offset + 2);
            $offset += self::LAENGE_WETTKAMPF;

            if (0 === $eine && 0 === $andere) {
                continue;
            }

            if (isset($gesehen[$eine]) || isset($gesehen[$andere])) {
                ++$runde;
                $gesehen = [];
                $tisch = 0;
            }

            $gesehen[$eine] = true;
            $gesehen[$andere] = true;
            ++$tisch;

            $this->wettkaempfe[$runde][] = [
                'tisch' => $tisch,
                'heim' => $eine,
                'gast' => $andere,
            ];
        }
    }

    /**
     * Prüft, ob unter einer Nummer wirklich ein Teilnehmer steht.
     *
     * Die Null steht für die leere Seite einer kampflosen Partie, Nummern ab
     * `OHNE_GEGNER` für eine spielfreie Runde.
     *
     * @param int $nummer Die Nummer aus dem Paarungssatz
     *
     * @return bool Wahr, wenn dort ein Teilnehmer saß
     */
    private function istSpieler(int $nummer): bool
    {
        return $nummer > 0 && $nummer < self::OHNE_GEGNER && isset($this->spieler[$nummer]);
    }

    /**
     * Benennt den Sonderfall einer Paarung.
     *
     * @param int $schluessel Ergebnisschlüssel des Satzes
     * @param int $schwarz    Nummer der schwarzen Seite
     *
     * @return string `kampflos`, `spielfrei`, `nicht ausgelost` oder leer
     */
    private function status(int $schluessel, int $schwarz): string
    {
        if (\in_array($schluessel, self::KAMPFLOS, true)) {
            return 'kampflos';
        }

        if (self::SPIELFREI === $schwarz) {
            return 'spielfrei';
        }

        if ($schwarz >= self::OHNE_GEGNER) {
            return 'nicht ausgelost';
        }

        return '';
    }

    /**
     * Stellt die Kurzangaben eines Teilnehmers zusammen.
     *
     * @param int $nummer Startnummer
     *
     * @return array<string,mixed>|null Die Angaben, oder null wenn dort kein
     *                                  Spieler steht
     */
    private function spielerKurz(int $nummer): ?array
    {
        $spieler = $this->spieler[$nummer] ?? null;

        if (null === $spieler) {
            return null;
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
     * Wandelt ein Ergebnis in seine Kurzschreibweise.
     *
     * @param float|null $ergebnis Punkte aus Sicht von Weiß
     *
     * @return string `1`, `½`, `0` oder eine leere Zeichenkette
     */
    private function ergebnistext(?float $ergebnis): string
    {
        return match (true) {
            null === $ergebnis => '',
            1.0 === $ergebnis => '1',
            0.5 === $ergebnis => '½',
            default => '0',
        };
    }

    /**
     * Liest eine Zeichenkette mit vorangestellter Zeichenzahl.
     *
     * @param int $offset Adresse des Längenworts
     *
     * @return string Der Text in UTF-8; leer, wenn die Angabe nicht passt
     */
    private function text(int $offset): string
    {
        $len = $this->wort($offset);

        if ($len < 1 || $offset + 2 + $len * 2 > \strlen($this->inhalt)) {
            return '';
        }

        return (string) mb_convert_encoding(substr($this->inhalt, $offset + 2, $len * 2), 'UTF-8', 'UTF-16LE');
    }

    /**
     * Nennt die Adresse hinter einer Zeichenkette.
     *
     * @param int $offset Adresse des Längenworts
     *
     * @return int Adresse des nächsten Feldes
     */
    private function naechster(int $offset): int
    {
        $len = $this->wort($offset);

        if ($len < 1 || $offset + 2 + $len * 2 > \strlen($this->inhalt)) {
            return $offset + 2;
        }

        return $offset + 2 + $len * 2;
    }

    /**
     * Liest ein 16-Bit-Wort ohne Vorzeichen.
     *
     * @param int $offset Adresse
     *
     * @return int Der Wert, 0 hinter dem Dateiende
     */
    private function wort(int $offset): int
    {
        if ($offset < 0 || $offset + 1 >= \strlen($this->inhalt)) {
            return 0;
        }

        return \ord($this->inhalt[$offset]) | (\ord($this->inhalt[$offset + 1]) << 8);
    }

    /**
     * Liest ein 32-Bit-Wort ohne Vorzeichen.
     *
     * @param int $offset Adresse
     *
     * @return int Der Wert, 0 hinter dem Dateiende
     */
    private function langwort(int $offset): int
    {
        return $this->wort($offset) | ($this->wort($offset + 2) << 16);
    }
}
