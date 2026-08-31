<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Format;

use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Schnittstelle für ein Turnierdateiformat.
 *
 * Jedes unterstützte Format der gängigen Turnierverwaltungen bekommt eine
 * eigene Umsetzung dieser Schnittstelle. Wird sie als Dienst registriert,
 * findet das Verzeichnis sie von allein — die Autokonfiguration in der
 * services.yaml versieht alle Umsetzungen mit dem passenden Tag. Weder
 * Controller noch Templates müssen dafür angefasst werden.
 */
interface TurnierFormatInterface
{
    /**
     * Gibt den technischen Schlüssel des Formats zurück.
     *
     * Er steht im Auswahlfeld des Inhaltselements in der Datenbank und darf
     * sich deshalb nicht mehr ändern, sobald das Format veröffentlicht ist.
     * Erlaubt sind Kleinbuchstaben und Ziffern.
     *
     * @return string Der Schlüssel, etwa „swt"
     */
    public function getSchluessel(): string;

    /**
     * Gibt den Namen des Formats für die Anzeige zurück.
     *
     * @return string Anzeigename, etwa „SWT (Swiss-Chess)"
     */
    public function getName(): string;

    /**
     * Nennt die Dateiendungen, unter denen dieses Format üblicherweise abgelegt wird.
     *
     * Die Endungen werden für die Dateiauswahl im Backend gebraucht. Sie sind
     * kleingeschrieben und ohne führenden Punkt anzugeben.
     *
     * @return string[] Endungen, etwa `['swt']`
     */
    public function getDateiendungen(): array;

    /**
     * Prüft, ob dieses Format die übergebene Datei lesen kann.
     *
     * Die Prüfung soll sich auf den Inhalt stützen, nicht auf den Dateinamen:
     * Turnierdateien werden häufig umbenannt, und die automatische Erkennung
     * ist nur dann etwas wert, wenn sie sich davon nicht täuschen lässt. Die
     * Methode darf keine Ausnahme werfen — ein unlesbarer Inhalt ist schlicht
     * kein Treffer.
     *
     * @param string $dateiname Name der Datei, nur für Endungsprüfungen gedacht
     * @param string $inhalt    Der vollständige Dateiinhalt
     *
     * @return bool Wahr, wenn lese() mit dieser Datei etwas anfangen kann
     */
    public function erkennt(string $dateiname, string $inhalt): bool;

    /**
     * Liest die Datei ein und gibt das Turnier zurück.
     *
     * @param string $dateiname Name der Datei, für Fehlermeldungen und Anzeige
     * @param string $inhalt    Der vollständige Dateiinhalt
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn die Datei nicht auswertbar ist
     */
    public function lese(string $dateiname, string $inhalt): Turnier;
}
