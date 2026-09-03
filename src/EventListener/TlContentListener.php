<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\EventListener;

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Message;
use Contao\StringUtil;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\FormatVerzeichnis;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Listen;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Spalten;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\TurnierLader;

/**
 * Rückrufe für den Data Container der Inhaltselemente.
 *
 * Die Klasse baut die Eingabemaske in drei Schritten auf, damit der Redakteur
 * nie vor Einstellungen steht, die noch nichts bewirken können:
 *
 * 1. **Ohne Datei** steht nur die Dateiauswahl da. Alles Weitere hängt am
 *    Inhalt der Datei und wäre zu diesem Zeitpunkt geraten.
 * 2. **Nach dem Speichern** kommt die Auswahl der Ausgabe hinzu — angeboten
 *    wird, was diese Datei hergibt.
 * 3. **Nach Wahl der Ausgabe** erscheinen deren Einstellungen, und zwar
 *    sofort: Das Auswahlfeld schickt die Maske ab.
 *
 * Der Umweg über das Speichern ist nicht zu vermeiden: Die Dateiauswahl setzt
 * ihren Wert per Skript, und eine Zuweisung löst kein `change`-Ereignis aus.
 */
class TlContentListener
{
    /**
     * Erzeugt den Rückruf.
     *
     * @param FormatVerzeichnis $formate   Kennt alle registrierten Turnierformate
     * @param TurnierLader      $lader     Liest die gewählte Turnierdatei
     * @param ContaoFramework   $framework Für den Zugriff auf ContentModel
     */
    public function __construct(
        private readonly FormatVerzeichnis $formate,
        private readonly TurnierLader $lader,
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * Trägt die Dateiendungen der bekannten Formate in die Dateiauswahl ein.
     *
     * Der Rückruf läuft beim Laden von `tl_content` und damit auch für alle
     * anderen Inhaltselemente; er schreibt lediglich einen Wert ins DCA-Array
     * und fällt nicht ins Gewicht. Der Weg über einen Rückruf ist nötig, weil
     * die Liste erst feststeht, wenn der Container gebaut ist — in der
     * DCA-Datei selbst stünde nur eine fest verdrahtete Endung.
     *
     * @param DataContainer|null $dc Der Data Container; wird nicht gebraucht,
     *                               von Contao aber übergeben
     *
     * @return void
     */
    public function setzeDateiendungen(DataContainer $dc = null): void
    {
        if (!isset($GLOBALS['TL_DCA']['tl_content']['fields']['ctvDatei'])) {
            return;
        }

        $endungen = $this->formate->dateiendungen();

        if ([] === $endungen) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['ctvDatei']['eval']['extensions'] = implode(',', $endungen);
    }

    /**
     * Baut die Eingabemaske entsprechend dem Stand der Einstellungen auf.
     *
     * Gestrichen wird alles, was zum jetzigen Zeitpunkt nichts bewirken kann:
     * ohne Datei alles außer der Dateiauswahl, ohne gewählte Ausgabe deren
     * Einstellungen, und bei jeder Ausgabe die Einstellungen der anderen.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return void
     */
    public function passeMaskeAn(DataContainer $dc = null): void
    {
        $datensatz = $this->datensatz($dc);

        if (null === $datensatz) {
            return;
        }

        $turnier = $this->turnier($dc);

        // Schritt 1: Ohne lesbare Datei bleibt nur die Dateiauswahl. Alles
        // Weitere hinge am Inhalt der Datei.
        if (null === $turnier) {
            $this->kuerze(['ctvFormat', 'ctvListe', 'ctvSpalten', 'ctvStand', 'ctvRunden', 'ctvHinweise', 'ctvMannschaftSpieler', 'ctvKreuzKurz']);

            return;
        }

        $liste = self::liste($datensatz->ctvListe, $datensatz->ctvListen);
        $weg = [];

        // Schritt 2: Datei da, aber noch keine Ausgabe gewählt.
        if ('' === $liste) {
            $this->kuerze(['ctvSpalten', 'ctvStand', 'ctvRunden', 'ctvHinweise', 'ctvMannschaftSpieler', 'ctvKreuzKurz']);

            return;
        }

        // Schritt 3: Die Einstellungen genau dieser Ausgabe.
        if (!Spalten::einstellbar($liste)) {
            $weg[] = 'ctvSpalten';
        }

        if (!\in_array($liste, Listen::MIT_STAND, true) || $turnier->getLetzteRunde() < 2) {
            $weg[] = 'ctvStand';
        }

        if (!\in_array($liste, Listen::MIT_RUNDEN, true) || $turnier->getLetzteRunde() < 2) {
            $weg[] = 'ctvRunden';
        }

        if (!\in_array($liste, Listen::MIT_SPIELERN, true)) {
            $weg[] = 'ctvMannschaftSpieler';
        }

        if ('mannschaftskreuztabelle' !== $liste) {
            $weg[] = 'ctvKreuzKurz';
        }

        // Die Hinweise erklären Abweichungen der Zahlen. Gibt es in dieser
        // Datei keine und ist auch kein Zwischenstand eingestellt, der welche
        // erzeugen würde, ist das Kästchen wirkungslos.
        if ([] === $turnier->getHinweise() && !(int) $datensatz->ctvStand) {
            $weg[] = 'ctvHinweise';
        }

        $this->kuerze($weg);
    }

    /**
     * Liefert die Ausgaben, die diese Turnierdatei hergibt.
     *
     * Bei einem Einzelturnier erscheinen die Mannschaftslisten gar nicht
     * erst, und Listen ohne Inhalt — eine Kreuztabelle vor der ersten Runde —
     * ebenso wenig. Ein Eintrag, der eine leere Ausgabe erzeugt, wäre eine
     * schlechte Auskunft.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return string[] Die Schlüssel der möglichen Ausgaben
     */
    public function listenOptionen(DataContainer $dc = null): array
    {
        $turnier = $this->turnier($dc);

        if (null === $turnier) {
            return [];
        }

        return array_values(array_filter(
            Listen::schluessel(),
            static fn (string $schluessel): bool => Listen::passt($schluessel, $turnier)
        ));
    }

    /**
     * Liefert die wählbaren Spalten der gewählten Ausgabe.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return string[] Die Spaltenschlüssel in natürlicher Reihenfolge
     */
    public function spaltenOptionen(DataContainer $dc = null): array
    {
        $turnier = $this->turnier($dc);
        $datensatz = $this->datensatz($dc);

        if (null === $turnier || null === $datensatz) {
            return [];
        }

        $spalten = Spalten::verfuegbar(self::liste($datensatz->ctvListe, $datensatz->ctvListen), $turnier);
        $beschriftungen = [];

        foreach ($spalten as $spalte) {
            $satz = Spalten::beschreibung($spalte, $turnier);
            $beschriftungen[$spalte] = '' !== $satz['titel'] ? $satz['titel'] : $satz['name'];
        }

        $GLOBALS['TL_LANG']['ctv']['spaltenwahl'] = $beschriftungen;

        return $spalten;
    }

    /**
     * Hakt die gebräuchlichen Spalten vor, solange nichts gewählt ist.
     *
     * Der Rückruf ändert nur die Anzeige: Gespeichert wird erst, was der
     * Redakteur beim nächsten Speichern stehen lässt. Ein leeres Feld
     * bedeutet in der Ausgabe ohnehin „Vorgabespalten"; vorangehakt zu zeigen,
     * was dann erscheint, ist die ehrlichere Maske.
     *
     * @param mixed              $wert Der gespeicherte Wert
     * @param DataContainer|null $dc   Der Data Container
     *
     * @return mixed Der Wert, oder die Vorauswahl wenn nichts gespeichert ist
     */
    public function spaltenVorauswahl(mixed $wert, DataContainer $dc = null): mixed
    {
        if ([] !== StringUtil::deserialize($wert, true)) {
            return $wert;
        }

        $turnier = $this->turnier($dc);
        $datensatz = $this->datensatz($dc);

        if (null === $turnier || null === $datensatz) {
            return $wert;
        }

        return serialize(Spalten::vorauswahl(self::liste($datensatz->ctvListe, $datensatz->ctvListen), $turnier));
    }

    /**
     * Liefert die Auswahl für „Stand nach Runde".
     *
     * Angeboten werden die Runden, für die in der Datei Paarungen stehen —
     * nicht die im Kopf angekündigte Rundenzahl: Ein laufendes Turnier hat
     * weniger, ein doppelrundiges mehr. An erster Stelle steht die Null für
     * den aktuellen Stand; sie nimmt die gespeicherten Werte der Datei, statt
     * sie nachzurechnen, und meint damit stets die zuletzt gespielte Runde.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return int[] Die wählbaren Rundennummern, beginnend mit 0
     */
    public function standOptionen(DataContainer $dc = null): array
    {
        $letzte = $this->rundenzahl($dc);
        $beschriftungen = [0 => $GLOBALS['TL_LANG']['ctv']['standGanz'] ?? 'Aktueller Stand (letzte Runde)'];

        for ($runde = 1; $runde <= $letzte; ++$runde) {
            $beschriftungen[$runde] = sprintf(
                $GLOBALS['TL_LANG']['ctv']['standRunde'] ?? 'Stand nach Runde %d',
                $runde
            );
        }

        $GLOBALS['TL_LANG']['ctv']['stand'] = $beschriftungen;

        return array_keys($beschriftungen);
    }

    /**
     * Liefert die Auswahl für „Angezeigte Runden".
     *
     * Die Kästchen entstehen aus der Datei: Ein Turnier über fünf Runden
     * bietet fünf an, keine starre Neunerreihe. Ohne Datei bleibt die Auswahl
     * leer — und ein leeres Kästchenfeld bedeutet in der Ausgabe „alle
     * Runden", also genau das, was ohne Einstellung gelten soll.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return int[] Die wählbaren Rundennummern ab 1
     */
    public function rundenOptionen(DataContainer $dc = null): array
    {
        $letzte = $this->rundenzahl($dc);
        $beschriftungen = [];

        for ($runde = 1; $runde <= $letzte; ++$runde) {
            $beschriftungen[$runde] = sprintf(
                $GLOBALS['TL_LANG']['ctv']['rundeNummer'] ?? 'Runde %d',
                $runde
            );
        }

        $GLOBALS['TL_LANG']['ctv']['runden'] = $beschriftungen;

        return array_keys($beschriftungen);
    }

    /**
     * Warnt, wenn die Turnierformate gar nicht hochgeladen werden dürfen.
     *
     * Die Dateiauswahl zeigt nur, was in der Dateiverwaltung liegt — und dort
     * landet nichts, dessen Endung nicht unter „Einstellungen → Erlaubte
     * Dateitypen" steht. Der Redakteur sieht dann eine leere Auswahl und hat
     * keinen Anhaltspunkt, woran es liegt: Der Upload scheitert an ganz
     * anderer Stelle, mit einer Meldung, die das Inhaltselement nicht
     * erwähnt. Deshalb der Hinweis hier, wo er gebraucht wird.
     *
     * @param mixed              $wert Der gespeicherte Feldwert; wird unverändert
     *                                 zurückgegeben
     * @param DataContainer|null $dc   Der Data Container; wird nicht gebraucht
     *
     * @return mixed Der unveränderte Feldwert
     */
    public function pruefeUploadTypen(mixed $wert, DataContainer $dc = null): mixed
    {
        $erlaubt = StringUtil::trimsplit(',', mb_strtolower((string) Config::get('uploadTypes')));
        $fehlend = array_values(array_diff($this->formate->dateiendungen(), $erlaubt));

        if ([] !== $fehlend) {
            // Ohne Leerzeichen: Genau so steht die Liste in „Erlaubte
            // Dateitypen", und so lässt sie sich von hier aus übernehmen.
            Message::addError(sprintf(
                $GLOBALS['TL_LANG']['ctv']['uploadWarnung']
                    ?? 'Unter „Einstellungen → Erlaubte Dateitypen" fehlt: %s. Turnierdateien lassen sich deshalb nicht in die Dateiverwaltung hochladen.',
                implode(',', $fehlend)
            ));
        }

        return $wert;
    }

    /**
     * Liefert die Auswahl für das Feld „Format".
     *
     * An erster Stelle steht die automatische Erkennung. Sie ist der
     * Regelfall: Die Formate erkennen ihre Dateien am Inhalt, und eine
     * Festlegung von Hand hilft nur dort, wo eine Datei von der Erkennung
     * nicht angenommen wird, obwohl sie zum Format gehört.
     *
     * @return string[] Die Formatschlüssel, beginnend mit `auto`
     */
    public function formatOptionen(): array
    {
        return array_merge(['auto'], array_keys($this->formate->auswahl()));
    }

    /**
     * Ermittelt die Ausgabe eines Inhaltselements.
     *
     * Bis Fassung 1.7.0 führte ein Element mehrere Listen. Ein solches
     * Element behält seine erste — mehr lässt sich nicht retten, ohne zu
     * raten, und der Rest ist mit dem Umschlag nachzubauen.
     *
     * Die Werte werden einzeln übergeben und nicht der Datensatz: So lässt
     * sich der Rückfall auf die alte Mehrfachauswahl prüfen, ohne dass ein
     * Contao-Datensatz gebraucht wird.
     *
     * @param mixed $einzeln  Der Wert des Feldes `ctvListe`
     * @param mixed $mehrfach Der Wert des alten Feldes `ctvListen`
     *
     * @return string Der Schlüssel der Liste, oder eine leere Zeichenkette
     */
    public static function liste(mixed $einzeln, mixed $mehrfach): string
    {
        $liste = trim((string) $einzeln);

        if ('' !== $liste) {
            return $liste;
        }

        $alt = StringUtil::deserialize($mehrfach, true);

        foreach ($alt as $eintrag) {
            if (\is_string($eintrag) && '' !== $eintrag) {
                return $eintrag;
            }
        }

        return '';
    }

    /**
     * Streicht Felder aus der Palette der Turnierausgabe.
     *
     * @param string[] $felder Die zu entfernenden Feldnamen
     *
     * @return void
     */
    private function kuerze(array $felder): void
    {
        if ([] === $felder) {
            return;
        }

        $palette = &$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'];
        $palette = self::ohneFelder((string) $palette, $felder);
    }

    /**
     * Entfernt Felder aus einer Palette und räumt leere Feldgruppen ab.
     *
     * Eine Feldgruppe, aus der alles gestrichen wurde, verschwindet mit ihrer
     * Überschrift: Eine leere Überschrift „Mannschaftsturniere" wäre
     * irreführender als gar keine.
     *
     * @param string   $palette Die Palettenzeichenkette
     * @param string[] $felder  Die zu entfernenden Feldnamen
     *
     * @return string Die gekürzte Palettenzeichenkette
     */
    private static function ohneFelder(string $palette, array $felder): string
    {
        $gruppen = [];

        foreach (explode(';', $palette) as $gruppe) {
            if ('' === trim($gruppe)) {
                continue;
            }

            $teile = explode(',', $gruppe);
            $kopf = '';

            // Die Überschrift steht in geschweiften Klammern am Anfang der
            // Gruppe. Es gibt auch Gruppen ohne Überschrift.
            if (str_starts_with(trim($teile[0]), '{')) {
                $kopf = array_shift($teile);
            }

            $teile = array_values(array_filter(
                $teile,
                static fn (string $feld): bool => !\in_array(trim($feld), $felder, true)
            ));

            if ([] === $teile) {
                continue;
            }

            $gruppen[] = ('' === $kopf ? '' : $kopf.',').implode(',', $teile);
        }

        return implode(';', $gruppen);
    }

    /**
     * Ermittelt die Zahl der Runden, für die Paarungen vorliegen.
     *
     * @param DataContainer|null $dc Der Data Container
     *
     * @return int Die letzte Runde, 0 wenn keine Datei lesbar ist
     */
    private function rundenzahl(DataContainer $dc = null): int
    {
        $turnier = $this->turnier($dc);

        return null === $turnier ? 0 : $turnier->getLetzteRunde();
    }

    /**
     * Liest das Turnier des gerade bearbeiteten Inhaltselements.
     *
     * Gibt null zurück, sobald irgendetwas fehlt oder nicht passt: kein
     * Datensatz, ein anderes Inhaltselement, keine Datei, eine unlesbare
     * Datei.
     *
     * @param DataContainer|null $dc Der Data Container
     *
     * @return Turnier|null Das Turnier, oder null
     */
    private function turnier(DataContainer $dc = null): ?Turnier
    {
        $datensatz = $this->datensatz($dc);

        if (null === $datensatz) {
            return null;
        }

        return $this->lader->ladeStill($datensatz->ctvDatei, (string) ($datensatz->ctvFormat ?: 'auto'));
    }

    /**
     * Holt den Datensatz des gerade bearbeiteten Inhaltselements.
     *
     * @param DataContainer|null $dc Der Data Container
     *
     * @return ContentModel|null Der Datensatz, oder null wenn es keine
     *                           Turnierausgabe ist
     */
    private function datensatz(DataContainer $dc = null): ?ContentModel
    {
        if (null === $dc || !$dc->id) {
            return null;
        }

        $this->framework->initialize();
        $datensatz = $this->framework->getAdapter(ContentModel::class)->findByPk($dc->id);

        if (null === $datensatz || 'chesstournamentviewer' !== $datensatz->type) {
            return null;
        }

        return $datensatz;
    }
}
