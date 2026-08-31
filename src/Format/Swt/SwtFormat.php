<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Format\Swt;

use Schachbulle\ContaoChesstournamentviewerBundle\Format\TurnierFormatInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;

/**
 * Liest Turnierdateien des Programms Swiss-Chess (Endung SWT).
 *
 * Die eigentliche Auswertung der Binärdatei erledigt SwtFile. Diese Klasse
 * übersetzt deren Ergebnis in das formatunabhängige Turniermodell und ist
 * damit die einzige Stelle im Bundle, an der SWT-Eigenheiten vorkommen.
 *
 * Die Übersetzung ist bewusst dünn: Seit der Fassung des Lesers vom
 * 2026-08-31 liefert dieser die Mannschaftsdaten selbst richtig — die
 * Mannschaften unter ihrer echten Nummer, die Wettkämpfe mit Brett- und
 * Mannschaftspunkten aus den Einzelpartien. Was hier früher geradegerückt
 * werden musste, ist entfallen.
 */
class SwtFormat implements TurnierFormatInterface
{
    /**
     * Gibt den technischen Schlüssel des Formats zurück.
     *
     * @return string Immer „swt"
     */
    public function getSchluessel(): string
    {
        return 'swt';
    }

    /**
     * Gibt den Namen des Formats für die Anzeige zurück.
     *
     * @return string Der Anzeigename samt Programmname, weil die Endung allein
     *                wenig sagt
     */
    public function getName(): string
    {
        return 'SWT (Swiss-Chess)';
    }

    /**
     * Nennt die übliche Dateiendung.
     *
     * @return string[] Nur `swt`; Sicherungskopien mit `bak` oder `swk` tragen
     *                  denselben Aufbau, werden aber nicht angeboten, um die
     *                  Dateiauswahl nicht mit Sicherungen zu füllen
     */
    public function getDateiendungen(): array
    {
        return ['swt'];
    }

    /**
     * Prüft, ob der Inhalt eine SWT-Datei sein kann.
     *
     * Geprüft wird der Kopfbereich, nicht der Dateiname: Turnierdateien werden
     * oft umbenannt, und eine Erkennung, die sich auf die Endung verlässt,
     * wäre keine. Ausgewertet werden die Werte, die in jeder Datei an fester
     * Stelle stehen und deren Wertebereich bekannt ist — Fassungsnummer,
     * Rundenzahl und Teilnehmerzahl. Die Schranken sind großzügig gewählt;
     * sie sollen Zufallstreffer bei anderen Binärdateien ausschließen, nicht
     * ungewöhnliche Turniere abweisen.
     *
     * @param string $dateiname Bleibt ungenutzt, siehe oben
     * @param string $inhalt    Der vollständige Dateiinhalt
     *
     * @return bool Wahr, wenn der Kopfbereich zu einer SWT-Datei passt
     */
    public function erkennt(string $dateiname, string $inhalt): bool
    {
        // Kürzer als der Kopfbereich kann keine Turnierdatei sein.
        if (\strlen($inhalt) < 0x0540) {
            return false;
        }

        $wort = static fn (int $adresse): int => \ord($inhalt[$adresse]) + (\ord($inhalt[$adresse + 1]) << 8);

        $version = $wort(0x0261);
        $runden = $wort(0x0001);
        $teilnehmer = $wort(0x0007);

        // Fassung 0 gibt es (Dateien aus der DOS-Zeit), dazwischen liegt
        // nichts: die erste Windows-Fassung trägt 650.
        if (0 !== $version && ($version < 500 || $version > 1500)) {
            return false;
        }

        return $runden >= 0 && $runden <= 200 && $teilnehmer >= 0 && $teilnehmer <= 10000;
    }

    /**
     * Liest die Datei ein und übersetzt sie ins Turniermodell.
     *
     * @param string $dateiname Name der Datei, für Fehlermeldungen
     * @param string $inhalt    Der vollständige Dateiinhalt
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn SwtFile die Datei nicht auswerten kann
     */
    public function lese(string $dateiname, string $inhalt): Turnier
    {
        $datei = SwtFile::ausInhalt($inhalt, $dateiname);
        $kopf = $datei->getTurnier();
        $paarungen = $datei->getPaarungen();

        return new Turnier(
            $this->getName(),
            $this->kopfdaten($kopf),
            $datei->getSpieler(),
            $datei->getMannschaften(),
            $paarungen,
            $datei->getRangliste(),
            $this->runden($datei, $paarungen),
            $datei->getKreuztabelle(),
            $datei->getHinweise(),
            $datei->getMannschaftspaarungen()
        );
    }

    /**
     * Übersetzt die Kopfdaten der SWT-Datei ins Turniermodell.
     *
     * Die Schlüssel des Modells sind formatunabhängig benannt; die
     * SWT-eigenen Angaben bleiben zusätzlich erhalten, damit die Liste der
     * Turnierdaten auch Felder zeigen kann, die es nur in diesem Format gibt.
     *
     * Als Turniername gilt die erste Überschriftzeile. Sie ist das, was
     * Swiss-Chess über seine Ausdrucke setzt; das Feld „Turniername" ist
     * häufig leer oder trägt eine interne Bezeichnung.
     *
     * @param array<string,mixed> $kopf Die Kopfdaten aus SwtFile
     *
     * @return array<string,mixed> Kopfdaten für das Turniermodell
     */
    private function kopfdaten(array $kopf): array
    {
        $name = trim((string) ($kopf['ueberschrift1'] ?? ''));

        if ('' === $name) {
            $name = trim((string) ($kopf['turniername'] ?? ''));
        }

        // Der Wert 0 heißt „keine Feinwertung eingestellt". SwtFile gibt dafür
        // die Bezeichnung „Nicht gesetzt" zurück; in einer Liste der
        // Turnierdaten wäre das eine Zeile, die nichts aussagt.
        $feinwertung1 = 0 === (int) ($kopf['feinwertung1'] ?? 0) ? '' : (string) ($kopf['feinwertung1Text'] ?? '');
        $feinwertung2 = 0 === (int) ($kopf['feinwertung2'] ?? 0) ? '' : (string) ($kopf['feinwertung2Text'] ?? '');

        return array_merge($kopf, [
            'name' => $name,
            'untertitel' => (string) ($kopf['ueberschrift2'] ?? ''),
            'ort' => (string) ($kopf['ortLand'] ?? ''),
            'feinwertung1Text' => $feinwertung1,
            'feinwertung2Text' => $feinwertung2,
            'feinwertungSicher' => (bool) ($kopf['feinwertungBezeichnungSicher'] ?? true),
        ]);
    }

    /**
     * Sammelt die Partien aller ausgelosten Runden.
     *
     * Die Rundenzahl wird nicht aus dem Kopf der Datei genommen, sondern aus
     * den tatsächlich vorhandenen Paarungssätzen: Bei doppelrundigen
     * Turnieren zählt der Kopf nur die Runden eines Durchgangs, und bei
     * laufenden Turnieren sind weniger Runden ausgelost als angekündigt.
     * Runden ohne Paarungen fallen dabei von allein heraus, weil getRunde()
     * für sie ein leeres Array liefert.
     *
     * @param SwtFile                                   $datei     Die eingelesene Datei
     * @param array<int,array<int,array<string,mixed>>> $paarungen Alle Einzelpaarungen
     *
     * @return array<int,array<int,array<string,mixed>>> Partien je Rundennummer
     */
    private function runden(SwtFile $datei, array $paarungen): array
    {
        $hoechste = 0;

        foreach ($paarungen as $runden) {
            $nummern = array_keys($runden);

            if ($nummern) {
                $hoechste = max($hoechste, max($nummern));
            }
        }

        $runden = [];

        for ($runde = 1; $runde <= $hoechste; ++$runde) {
            $partien = $datei->getRunde($runde);

            if ([] !== $partien) {
                $runden[$runde] = $partien;
            }
        }

        return $runden;
    }
}
