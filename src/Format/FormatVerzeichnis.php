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
 * Hält alle bekannten Turnierformate und wählt das passende aus.
 *
 * Die Formate werden über die Autokonfiguration eingesammelt: jede Klasse,
 * die TurnierFormatInterface umsetzt und als Dienst registriert ist, landet
 * hier. Eine Liste, die bei jedem neuen Format nachgeführt werden müsste,
 * gibt es damit nicht.
 */
class FormatVerzeichnis
{
    /**
     * Alle bekannten Formate, nach Schlüssel indiziert.
     *
     * @var array<string,TurnierFormatInterface>
     */
    private array $formate = [];

    /**
     * Nimmt die registrierten Formate entgegen.
     *
     * @param iterable<TurnierFormatInterface> $formate Die vom Container
     *                                                  eingesammelten Formate
     */
    public function __construct(iterable $formate)
    {
        foreach ($formate as $format) {
            $this->formate[$format->getSchluessel()] = $format;
        }
    }

    /**
     * Gibt alle bekannten Formate zurück.
     *
     * @return array<string,TurnierFormatInterface> Formate nach Schlüssel
     */
    public function alle(): array
    {
        return $this->formate;
    }

    /**
     * Gibt die Formate als Auswahlliste für das Backend zurück.
     *
     * @return array<string,string> Schlüssel auf Anzeigename
     */
    public function auswahl(): array
    {
        $auswahl = [];

        foreach ($this->formate as $schluessel => $format) {
            $auswahl[$schluessel] = $format->getName();
        }

        asort($auswahl);

        return $auswahl;
    }

    /**
     * Nennt alle Dateiendungen, die die bekannten Formate mitbringen.
     *
     * Wird für die Dateiauswahl im Backend gebraucht, damit dort nur Dateien
     * angeboten werden, mit denen das Inhaltselement auch etwas anfangen kann.
     *
     * @return string[] Endungen ohne Punkt, alphabetisch und ohne Wiederholung
     */
    public function dateiendungen(): array
    {
        $endungen = [];

        foreach ($this->formate as $format) {
            foreach ($format->getDateiendungen() as $endung) {
                $endungen[mb_strtolower($endung)] = true;
            }
        }

        $liste = array_keys($endungen);
        sort($liste);

        return $liste;
    }

    /**
     * Liest eine Turnierdatei mit dem angegebenen oder erkannten Format.
     *
     * Bei `auto` werden alle Formate der Reihe nach gefragt, ob sie mit der
     * Datei etwas anfangen können. Ist ein Schlüssel angegeben, wird nur
     * dieses Format versucht — dann darf eine Datei auch dann gelesen werden,
     * wenn die Erkennung sie nicht sicher zuordnet.
     *
     * @param string $dateiname Name der Datei, für Anzeige und Fehlermeldungen
     * @param string $inhalt    Der vollständige Dateiinhalt
     * @param string $schluessel Formatschlüssel oder `auto` für die Erkennung
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn kein Format zuständig ist oder das
     *                           angegebene Format die Datei nicht lesen kann
     */
    public function lese(string $dateiname, string $inhalt, string $schluessel = 'auto'): Turnier
    {
        if ('auto' !== $schluessel && '' !== $schluessel) {
            $format = $this->formate[$schluessel] ?? null;

            if (null === $format) {
                throw new \RuntimeException(sprintf('Das Turnierformat "%s" ist nicht bekannt.', $schluessel));
            }

            return $format->lese($dateiname, $inhalt);
        }

        foreach ($this->formate as $format) {
            if ($format->erkennt($dateiname, $inhalt)) {
                return $format->lese($dateiname, $inhalt);
            }
        }

        throw new \RuntimeException(sprintf('Das Format der Datei "%s" wurde nicht erkannt.', $dateiname));
    }
}
