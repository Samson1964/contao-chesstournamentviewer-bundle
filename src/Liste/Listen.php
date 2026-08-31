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
 * Verzeichnis der Listen, die das Inhaltselement ausgeben kann.
 *
 * Hier steht, welche Listen es gibt, in welcher Reihenfolge sie erscheinen,
 * welches Template sie ausgibt und ob sie zu einem Einzel- oder
 * Mannschaftsturnier gehören. Eine neue Liste braucht einen Eintrag hier, ein
 * Template und eine Beschriftung in den Sprachdateien.
 */
final class Listen
{
    /**
     * Die Liste gilt für jedes Turnier.
     */
    public const IMMER = 'immer';

    /**
     * Die Liste gibt es nur bei Mannschaftsturnieren.
     */
    public const MANNSCHAFT = 'mannschaft';

    /**
     * Alle bekannten Listen in der Reihenfolge ihrer Ausgabe.
     *
     * Der Schlüssel steht in der Datenbank und darf sich nicht mehr ändern.
     * `template` ist der Name des Contao-Templates ohne Endung, `gilt` legt
     * fest, wann die Liste angeboten wird.
     *
     * @var array<string,array{template:string,gilt:string}>
     */
    public const LISTEN = [
        'turnierdaten' => ['template' => 'ctv_turnierdaten', 'gilt' => self::IMMER],
        'teilnehmer' => ['template' => 'ctv_teilnehmer', 'gilt' => self::IMMER],
        'rangliste' => ['template' => 'ctv_rangliste', 'gilt' => self::IMMER],
        'kreuztabelle' => ['template' => 'ctv_kreuztabelle', 'gilt' => self::IMMER],
        'fortschritt' => ['template' => 'ctv_fortschritt', 'gilt' => self::IMMER],
        'paarungen' => ['template' => 'ctv_paarungen', 'gilt' => self::IMMER],
        'ergebnisse' => ['template' => 'ctv_ergebnisse', 'gilt' => self::IMMER],
        'mannschaften' => ['template' => 'ctv_mannschaften', 'gilt' => self::MANNSCHAFT],
        'mannschaftsrangliste' => ['template' => 'ctv_mannschaftsrangliste', 'gilt' => self::MANNSCHAFT],
        'mannschaftspaarungen' => ['template' => 'ctv_mannschaftspaarungen', 'gilt' => self::MANNSCHAFT],
        'mannschaftskreuztabelle' => ['template' => 'ctv_mannschaftskreuztabelle', 'gilt' => self::MANNSCHAFT],
    ];

    /**
     * Gibt die Schlüssel aller bekannten Listen zurück.
     *
     * Wird für das Auswahlfeld im Backend gebraucht. Dort werden alle Listen
     * angeboten, auch die für Mannschaftsturniere: welche Datei das Element
     * später auswertet, steht bei der Auswahl noch nicht fest.
     *
     * @return string[] Die Schlüssel in Ausgabereihenfolge
     */
    public static function schluessel(): array
    {
        return array_keys(self::LISTEN);
    }

    /**
     * Prüft, ob eine Liste zu einem bestimmten Turnier passt.
     *
     * Mannschaftslisten bei einem Einzelturnier auszugeben, hieße leere
     * Tabellen zu zeigen. Sie werden deshalb übergangen, ohne dass die
     * Einstellung am Inhaltselement geändert werden müsste — dieselbe
     * Auswahl soll für Einzel- wie Mannschaftsturniere taugen.
     *
     * @param string  $schluessel Schlüssel der Liste
     * @param Turnier $turnier    Das eingelesene Turnier
     *
     * @return bool Wahr, wenn die Liste ausgegeben werden soll
     */
    public static function passt(string $schluessel, Turnier $turnier): bool
    {
        $liste = self::LISTEN[$schluessel] ?? null;

        if (null === $liste) {
            return false;
        }

        return self::MANNSCHAFT !== $liste['gilt'] || $turnier->istMannschaftsturnier();
    }

    /**
     * Gibt den Templatenamen einer Liste zurück.
     *
     * @param string $schluessel Schlüssel der Liste
     *
     * @return string Templatename ohne Endung, oder eine leere Zeichenkette
     *                bei unbekanntem Schlüssel
     */
    public static function template(string $schluessel): string
    {
        return self::LISTEN[$schluessel]['template'] ?? '';
    }

    /**
     * Gibt die Beschriftung einer Liste zurück.
     *
     * Die Beschriftung stammt aus der Sprachdatei. Fehlt sie — etwa weil das
     * Backend in einer Sprache läuft, für die das Bundle keine Übersetzung
     * mitbringt —, wird der Schlüssel selbst angezeigt. Eine leere Reiterlasche
     * wäre schlimmer als ein technischer Name.
     *
     * @param string $schluessel Schlüssel der Liste
     *
     * @return string Die Beschriftung für Reiter und Überschrift
     */
    public static function beschriftung(string $schluessel): string
    {
        $beschriftung = $GLOBALS['TL_LANG']['ctv']['listen'][$schluessel] ?? null;

        return \is_string($beschriftung) && '' !== $beschriftung ? $beschriftung : $schluessel;
    }
}
