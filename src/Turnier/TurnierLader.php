<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Turnier;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\FormatVerzeichnis;

/**
 * Liest die Turnierdatei eines Inhaltselements aus der Dateiverwaltung.
 *
 * Gebraucht wird das an zwei Stellen: im Frontend, um die Listen auszugeben,
 * und im Backend, um die Eingabemaske an die Turnierart anzupassen. Damit
 * beide dasselbe sehen — und dieselben Fehlermeldungen —, steht der Weg von
 * der Datei-UUID zum Turnier hier und nicht zweimal.
 *
 * Innerhalb einer Anfrage wird jede Datei nur einmal ausgewertet. Im Backend
 * läuft der Rückruf für die Eingabemaske mehrfach; ohne diesen
 * Zwischenspeicher würde die Datei bei jedem Aufruf neu zerlegt.
 */
class TurnierLader
{
    /**
     * Bereits ausgewertete Turniere, nach Dateipfad und Format.
     *
     * @var array<string,Turnier>
     */
    private array $gelesen = [];

    /**
     * Höchstgröße einer Turnierdatei in Byte.
     *
     * Turnierdateien sind klein — die größte im geprüften Bestand liegt bei
     * gut 200 Kilobyte. Die Schranke verhindert, dass eine versehentlich
     * ausgewählte Datei den Speicher füllt, bevor der Leser sie ablehnt.
     */
    private const HOECHSTGROESSE = 8 * 1024 * 1024;

    /**
     * Erzeugt den Dienst.
     *
     * @param FormatVerzeichnis $formate    Kennt alle Turnierformate
     * @param ContaoFramework   $framework  Für den Zugriff auf FilesModel
     * @param string            $projectDir Wurzelverzeichnis der Installation
     */
    public function __construct(
        private readonly FormatVerzeichnis $formate,
        private readonly ContaoFramework $framework,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Liest die Turnierdatei zu einer UUID aus der Dateiverwaltung.
     *
     * @param mixed  $uuid   Die UUID der Datei, wie sie im Datensatz steht
     * @param string $format Formatschlüssel oder `auto` für die Erkennung
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn keine Datei hinterlegt ist, die Datei
     *                           fehlt, zu groß ist oder von keinem Format
     *                           gelesen werden kann
     */
    public function lade(mixed $uuid, string $format = 'auto'): Turnier
    {
        if (!$uuid) {
            throw new \RuntimeException('Dem Inhaltselement ist keine Turnierdatei zugeordnet.');
        }

        $this->framework->initialize();

        $datei = $this->framework->getAdapter(FilesModel::class)->findByUuid($uuid);

        if (null === $datei) {
            throw new \RuntimeException('Die zugeordnete Turnierdatei steht nicht mehr in der Dateiverwaltung.');
        }

        $schluessel = $datei->path.'|'.$format;

        if (isset($this->gelesen[$schluessel])) {
            return $this->gelesen[$schluessel];
        }

        $pfad = $this->projectDir.'/'.$datei->path;

        if (!is_file($pfad) || !is_readable($pfad)) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" ist nicht lesbar.', $datei->path));
        }

        if (filesize($pfad) > self::HOECHSTGROESSE) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" ist größer als %d Byte.', $datei->path, self::HOECHSTGROESSE));
        }

        $inhalt = file_get_contents($pfad);

        if (false === $inhalt) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" konnte nicht gelesen werden.', $datei->path));
        }

        return $this->gelesen[$schluessel] = $this->formate->lese(basename($datei->path), $inhalt, '' !== $format ? $format : 'auto');
    }

    /**
     * Liest die Turnierdatei, ohne bei einem Fehler abzubrechen.
     *
     * Für das Backend gedacht: Dort soll eine unlesbare Datei die Eingabemaske
     * nicht zerstören, sondern nur dazu führen, dass die Maske alle
     * Möglichkeiten anbietet.
     *
     * @param mixed  $uuid   Die UUID der Datei
     * @param string $format Formatschlüssel oder `auto`
     *
     * @return Turnier|null Das Turnier, oder null wenn es nicht zu lesen war
     */
    public function ladeStill(mixed $uuid, string $format = 'auto'): ?Turnier
    {
        try {
            return $this->lade($uuid, $format);
        } catch (\Throwable) {
            return null;
        }
    }
}
