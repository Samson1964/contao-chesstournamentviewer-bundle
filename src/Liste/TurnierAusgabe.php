<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Contao\Config;
use Contao\Date;
use Contao\StringUtil;
use Schachbulle\ContaoChesstournamentviewerBundle\EventListener\MaskeListener;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Rundenschnitt;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\TurnierLader;

/**
 * Stellt aus gespeicherten Einstellungen eine fertige Ausgabe zusammen.
 *
 * Die Klasse steht zwischen den gespeicherten Einstellungen und den
 * Templates. Sie wird von zwei Seiten benutzt: vom Inhaltselement und vom
 * Inserttag `{{ctv::…}}`, dessen Einstellungen im Backend-Modul gepflegt
 * werden. Beide sollen dieselbe Ausgabe erzeugen — stünde die Arbeit im
 * Controller, müsste der Inserttag sie nachbauen, und die beiden Wege gingen
 * mit der Zeit auseinander.
 *
 * Die Einstellungen kommen als einfaches Array herein und nicht als
 * Contao-Datensatz: Die beiden Tabellen führen zwar dieselben Felder, aber
 * die Testfälle sollen ohne Datenbank auskommen.
 */
class TurnierAusgabe
{
    /**
     * Erzeugt den Dienst.
     *
     * @param TurnierLader $lader       Liest die Turnierdatei aus der Dateiverwaltung
     * @param ListenBauer  $listenBauer Stellt die Daten der Liste zusammen
     */
    public function __construct(
        private readonly TurnierLader $lader,
        private readonly ListenBauer $listenBauer,
    ) {
    }

    /**
     * Baut die Ausgabe zu einem Satz Einstellungen.
     *
     * Erwartet werden die Feldwerte, wie sie in `tl_content` oder
     * `tl_ctv_inserttag` stehen; die Namen sind in beiden Tabellen dieselben.
     * Serialisierte Felder dürfen roh übergeben werden.
     *
     * @param array<string,mixed> $einstellungen Die Feldwerte des Datensatzes
     * @param string|null         $sprache       Sprache für die Ländernamen;
     *                                           ohne Angabe die der Seite
     *
     * @return array<string,mixed>|null Die Werte für das Template — `turnier`,
     *                                  `liste`, `hinweise`, `stand`,
     *                                  `aktualisiert`, `name` —, oder null,
     *                                  wenn die Einstellungen nichts ergeben
     *
     * @throws \Throwable Wenn die Turnierdatei nicht gelesen werden kann; der
     *                    Aufrufer entscheidet, ob das jemand sehen soll
     */
    public function baue(array $einstellungen, ?string $sprache = null): ?array
    {
        $turnier = $this->lader->lade(
            $einstellungen['ctvDatei'] ?? null,
            (string) ($einstellungen['ctvFormat'] ?? 'auto') ?: 'auto'
        );

        $schluessel = MaskeListener::liste($einstellungen['ctvListe'] ?? '', $einstellungen['ctvListen'] ?? null);

        $auswahl = Auswahl::fuerListe(
            $schluessel,
            (bool) ($einstellungen['ctvMannschaftSpieler'] ?? false),
            (bool) ($einstellungen['ctvKreuzKurz'] ?? false),
            (int) ($einstellungen['ctvStand'] ?? 0),
            StringUtil::deserialize($einstellungen['ctvRunden'] ?? null, true),
            StringUtil::deserialize($einstellungen['ctvSpalten'] ?? null, true),
            StringUtil::deserialize($einstellungen['ctvMannschaftswahl'] ?? null, true),
            (bool) ($einstellungen['ctvRundenkopfAus'] ?? false),
        );

        // Nationalmannschaften heißen in den Dateien meist englisch —
        // „Poland", „Uzbekistan 2". Übersetzt wird in die Sprache der Seite.
        $turnier = Laender::uebersetzeMannschaften($turnier, $sprache);

        // Der Rundenschnitt versetzt das Turnier zurück; von da an gelten
        // dessen Zahlen, auch für Kopfdaten und Hinweise.
        if ($auswahl->stand > 0) {
            $turnier = Rundenschnitt::bis($turnier, $auswahl->stand);
        }

        $listen = $this->listenBauer->baue($turnier, $auswahl);

        if ([] === $listen) {
            return null;
        }

        return [
            'turnier' => $turnier,
            'kopf' => $turnier->getKopf(),
            'liste' => $listen[0],
            'name' => $listen[0]['name'],
            // Die Hinweise erklären, warum Zahlen auseinandergehen können. Auf
            // einer Vereinsseite ist das oft mehr, als der Besucher wissen
            // will; deshalb erscheinen sie nur auf Wunsch.
            'hinweise' => ($einstellungen['ctvHinweise'] ?? false) ? $turnier->getHinweise() : [],
            // Der Zwischenstand steht unabhängig von den Hinweisen über der
            // Ausgabe: Eine Tabelle nach Runde 4 sähe sonst aus wie die
            // Endtabelle, und niemand könnte den Unterschied erkennen.
            'stand' => ($einstellungen['ctvStandAus'] ?? false) ? 0 : (int) $turnier->kopf('standNachRunde', 0),
            'aktualisiert' => ($einstellungen['ctvDatum'] ?? false) ? $this->aktualisiert($turnier) : '',
        ];
    }

    /**
     * Ermittelt, wann die Turnierdatei zuletzt aktualisiert wurde.
     *
     * Vorrang hat eine Angabe aus der Datei selbst — bislang liefert sie
     * keines der beiden Formate, künftige mögen es tun. Sonst gilt das
     * Änderungsdatum der Datei im Dateisystem: Die Turnierleitung lädt nach
     * jeder Runde eine neue Fassung hoch, und damit ist es die verlässliche
     * Auskunft darüber, wie aktuell die Zahlen sind.
     *
     * Geschrieben wird im Datums- und Zeitformat der Contao-Einstellungen,
     * damit die Zeile aussieht wie der Rest der Seite.
     *
     * @param Turnier $turnier Das eingelesene Turnier
     *
     * @return string Das Datum als Text, oder eine leere Zeichenkette wenn
     *                sich keines ermitteln ließ
     */
    private function aktualisiert(Turnier $turnier): string
    {
        $angabe = trim((string) $turnier->kopf('aktualisiert', ''));

        if ('' !== $angabe) {
            return $angabe;
        }

        $stempel = (int) $turnier->kopf('dateidatum', 0);

        if ($stempel < 1) {
            return '';
        }

        return Date::parse((string) Config::get('datimFormat'), $stempel);
    }
}
