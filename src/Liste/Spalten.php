<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Verzeichnis der Spalten, die Teilnehmerliste und Rangliste zeigen können.
 *
 * Welche Spalten sinnvoll sind, hängt am Turnier: Ein Turnier ohne
 * Wertungszahlen braucht keine Elo-Spalte, ein Einzelturnier keine
 * Mannschaftsspalte, und eine Feinwertung, die niemand hat, wäre eine Reihe
 * von Nullen. Diese Klasse beantwortet beides — **was es gibt** und **was
 * davon in dieser Datei etwas zu zeigen hat**.
 *
 * Das Backend bietet dem Redakteur genau die verfügbaren Spalten an; seine
 * Reihenfolge ist die der Ausgabe. Wählt er nichts, gilt die Vorgabe.
 */
final class Spalten
{
    /**
     * Die Listen, für die sich die Spalten einstellen lassen.
     *
     * Die übrigen Listen haben keine wählbaren Spalten: Eine Kreuztabelle
     * ohne Ergebnisspalten ist keine Kreuztabelle mehr, und in einer
     * Paarungsliste steht in jeder Spalte etwas, ohne das die Zeile nicht zu
     * lesen wäre.
     */
    public const LISTEN = ['teilnehmer', 'rangliste'];

    /**
     * Alle bekannten Spalten mit ihrer Darstellung.
     *
     * `feld` ist der Schlüssel im Teilnehmersatz, `label` der Schlüssel der
     * Beschriftung in der Sprachdatei, `kurz` eine Kurzform für schmale
     * Spalten (der volle Name steht dann als Titel am Spaltenkopf) und
     * `klasse` die CSS-Klasse der Zellen.
     *
     * @var array<string,array{label:string,kurz:string,klasse:string,zahl:bool}>
     */
    private const SPALTEN = [
        'nr' => ['label' => 'nr', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'platz' => ['label' => 'platz', 'kurz' => 'platzKurz', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'brett' => ['label' => 'brett', 'kurz' => 'brettKurz', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'name' => ['label' => 'name', 'kurz' => '', 'klasse' => '', 'zahl' => false],
        'titel' => ['label' => 'titel', 'kurz' => '', 'klasse' => 'ctv-schmal', 'zahl' => false],
        'elo' => ['label' => 'elo', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'dwz' => ['label' => 'dwz', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'twz' => ['label' => 'twz', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'verein' => ['label' => 'verein', 'kurz' => '', 'klasse' => '', 'zahl' => false],
        'land' => ['label' => 'land', 'kurz' => '', 'klasse' => 'ctv-schmal', 'zahl' => false],
        'geburtsjahr' => ['label' => 'geburtsjahr', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'fideId' => ['label' => 'fideId', 'kurz' => '', 'klasse' => 'ctv-zahl', 'zahl' => true],
        'gruppe' => ['label' => 'gruppe', 'kurz' => '', 'klasse' => 'ctv-schmal', 'zahl' => false],
        'bilanz' => ['label' => 'bilanz', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => false],
        'partien' => ['label' => 'partien', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'punkte' => ['label' => 'punkte', 'kurz' => 'punkteKurz', 'klasse' => 'ctv-zahl ctv-schmal ctv-punkte', 'zahl' => true],
        'feinwertung1' => ['label' => 'feinwertung1', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
        'feinwertung2' => ['label' => 'feinwertung2', 'kurz' => '', 'klasse' => 'ctv-zahl ctv-schmal', 'zahl' => true],
    ];

    /**
     * Die Spalten, die jede Liste anbieten darf, in ihrer natürlichen Folge.
     *
     * Sie ist zugleich die Reihenfolge im Auswahlfeld des Backends.
     *
     * @var array<string,string[]>
     */
    private const ANGEBOT = [
        'teilnehmer' => ['nr', 'brett', 'name', 'titel', 'elo', 'dwz', 'twz', 'verein', 'land', 'gruppe', 'geburtsjahr', 'fideId'],
        'rangliste' => ['platz', 'name', 'titel', 'twz', 'elo', 'dwz', 'bilanz', 'partien', 'punkte', 'feinwertung1', 'feinwertung2', 'verein', 'land'],
    ];

    /**
     * Die Spalten, die ohne eigene Auswahl erscheinen.
     *
     * Es sind die, die das Bundle bis Fassung 1.6.0 fest ausgegeben hat —
     * bestehende Inhaltselemente sehen dadurch unverändert aus.
     *
     * @var array<string,string[]>
     */
    private const VORGABE = [
        'teilnehmer' => ['nr', 'name', 'elo', 'dwz', 'twz', 'verein'],
        'rangliste' => ['platz', 'name', 'twz', 'bilanz', 'punkte', 'feinwertung1', 'feinwertung2'],
    ];

    /**
     * Sagt, ob sich für eine Liste die Spalten einstellen lassen.
     *
     * @param string $liste Schlüssel der Liste
     *
     * @return bool Wahr, wenn es für diese Liste eine Spaltenauswahl gibt
     */
    public static function einstellbar(string $liste): bool
    {
        return \in_array($liste, self::LISTEN, true);
    }

    /**
     * Nennt die Spalten, die in diesem Turnier etwas zu zeigen haben.
     *
     * Eine Spalte fällt weg, wenn sie bei keinem einzigen Teilnehmer belegt
     * ist: Eine Elo-Spalte in einem Turnier ohne Elo-Zahlen wäre eine Reihe
     * von Strichen, und im Backend ein Kästchen, das nichts bewirkt.
     *
     * @param string  $liste   Schlüssel der Liste
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return string[] Die Schlüssel der verfügbaren Spalten in natürlicher Folge
     */
    public static function verfuegbar(string $liste, Turnier $turnier): array
    {
        $angebot = self::ANGEBOT[$liste] ?? [];

        if ([] === $angebot) {
            return [];
        }

        $zeilen = 'rangliste' === $liste ? $turnier->getRangliste() : $turnier->getTeilnehmer();

        return array_values(array_filter(
            $angebot,
            static fn (string $spalte): bool => self::belegt($spalte, $zeilen, $turnier)
        ));
    }

    /**
     * Bringt eine gespeicherte Auswahl in eine ausgebbare Spaltenfolge.
     *
     * Gewählte Spalten, die es in diesem Turnier nicht gibt, fallen weg —
     * dieselbe Auswahl soll für mehrere Turnierdateien taugen. Ist am Ende
     * nichts übrig, gilt die Vorgabe: Eine Tabelle ohne Spalten wäre nutzlos,
     * und eine leere Auswahl heißt „nicht eingestellt", nicht „nichts".
     *
     * @param string   $liste    Schlüssel der Liste
     * @param string[] $gewaehlt Die gespeicherte Auswahl, in ihrer Reihenfolge
     * @param Turnier  $turnier  Das eingelesene Turnier
     *
     * @return array<int,array{schluessel:string,name:string,titel:string,klasse:string,zahl:bool}>
     *         Die Spalten in Ausgabereihenfolge
     */
    public static function fuerAusgabe(string $liste, array $gewaehlt, Turnier $turnier): array
    {
        $verfuegbar = self::verfuegbar($liste, $turnier);

        $schluessel = array_values(array_filter(
            $gewaehlt,
            static fn (mixed $spalte): bool => \is_string($spalte) && \in_array($spalte, $verfuegbar, true)
        ));

        if ([] === $schluessel) {
            $schluessel = array_values(array_intersect(self::VORGABE[$liste] ?? [], $verfuegbar));
        }

        $schluessel = array_unique($schluessel);
        $spalten = [];

        // Steht die Turnierwertungszahl neben Elo und DWZ, trägt sie den
        // Sammelbegriff: Sonst stünde in einem Elo-Turnier zweimal „Elo" über
        // zwei verschiedenen Spalten. Allein sagt der genaue Name mehr.
        $sammelbegriff = [] !== array_intersect(['elo', 'dwz'], $schluessel);

        foreach ($schluessel as $eine) {
            $satz = self::beschreibung($eine, $turnier);

            if ('twz' === $eine && $sammelbegriff) {
                $satz['name'] = Ausgabe::spalte('twz');
            }

            $spalten[] = $satz;
        }

        return $spalten;
    }

    /**
     * Beschreibt eine einzelne Spalte für den Tabellenkopf.
     *
     * Zwei Beschriftungen sind nicht fest, sondern stehen im Turnier: die
     * Wertungszahl heißt je nach Einstellung Elo, NWZ oder TWZ, und die
     * Feinwertungen tragen den Namen, den die Turnierdatei nennt.
     *
     * @param string  $spalte  Schlüssel der Spalte
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return array{schluessel:string,name:string,titel:string,klasse:string,zahl:bool} Die Beschreibung
     */
    public static function beschreibung(string $spalte, Turnier $turnier): array
    {
        $satz = self::SPALTEN[$spalte] ?? ['label' => $spalte, 'kurz' => '', 'klasse' => '', 'zahl' => false];
        $voll = Ausgabe::spalte($satz['label']);

        if ('twz' === $spalte) {
            $voll = Ausgabe::wertungsname($turnier);
        }

        if ('feinwertung1' === $spalte || 'feinwertung2' === $spalte) {
            $name = trim((string) $turnier->kopf($spalte.'Text', ''));

            if ('' !== $name) {
                return [
                    'schluessel' => $spalte,
                    'name' => Ausgabe::feinwertungKurz($name),
                    'titel' => $name.($turnier->kopf('feinwertungSicher', true) ? '' : ' '.Ausgabe::wort('unsicher', '(Bezeichnung unsicher)')),
                    'klasse' => $satz['klasse'],
                    'zahl' => true,
                ];
            }
        }

        $kurz = '' !== $satz['kurz'] ? Ausgabe::spalte($satz['kurz']) : '';

        return [
            'schluessel' => $spalte,
            'name' => '' !== $kurz ? $kurz : $voll,
            'titel' => '' !== $kurz ? $voll : '',
            'klasse' => $satz['klasse'],
            'zahl' => $satz['zahl'],
        ];
    }

    /**
     * Prüft, ob eine Spalte in diesem Turnier überhaupt Werte hat.
     *
     * Textspalten gelten als belegt, sobald irgendwo etwas steht;
     * Zahlenspalten erst, wenn ein Wert ungleich null vorkommt. Name und
     * Platz gelten immer als belegt — ohne sie ist keine Tabelle zu lesen.
     *
     * @param string                         $spalte  Schlüssel der Spalte
     * @param array<int,array<string,mixed>> $zeilen  Die Teilnehmer der Liste
     * @param Turnier                        $turnier Das eingelesene Turnier
     *
     * @return bool Wahr, wenn die Spalte etwas zu zeigen hat
     */
    private static function belegt(string $spalte, array $zeilen, Turnier $turnier): bool
    {
        if (\in_array($spalte, ['nr', 'platz', 'name', 'punkte', 'bilanz'], true)) {
            return true;
        }

        // Brett und Mannschaft gibt es nur, wo Mannschaften geführt werden.
        if ('brett' === $spalte) {
            return $turnier->istMannschaftsturnier();
        }

        if (\in_array($spalte, ['feinwertung1', 'feinwertung2'], true)
            && '' === trim((string) $turnier->kopf($spalte.'Text', ''))
            && !self::hatWert($spalte, $zeilen)
        ) {
            return false;
        }

        return self::hatWert($spalte, $zeilen);
    }

    /**
     * Sucht in den Zeilen nach einem belegten Wert dieser Spalte.
     *
     * @param string                         $spalte Schlüssel der Spalte
     * @param array<int,array<string,mixed>> $zeilen Die Teilnehmer
     *
     * @return bool Wahr, sobald eine Zeile etwas enthält
     */
    private static function hatWert(string $spalte, array $zeilen): bool
    {
        foreach ($zeilen as $zeile) {
            if (!empty(Ausgabe::rohwert($zeile, $spalte))) {
                return true;
            }
        }

        return false;
    }
}
