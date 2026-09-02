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
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\TurnierLader;

/**
 * Rückrufe für den Data Container der Inhaltselemente.
 *
 * Die Klasse hält alles, was die Eingabemaske über die registrierten
 * Turnierformate und über die gewählte Turnierdatei wissen muss. Sie steht
 * hier und nicht in der DCA-Datei, damit Format-Verzeichnis und Dateileser
 * über die Dienstverdrahtung hereinkommen.
 */
class TlContentListener
{
    /**
     * Listen, deren Ausgabe von der Rundenauswahl abhängt.
     *
     * Nur wenn eine von ihnen gewählt ist, hat das Feld „Angezeigte Runden"
     * eine Wirkung — die übrigen Listen zeigen ohnehin das ganze Turnier.
     */
    private const RUNDENLISTEN = ['paarungen', 'ergebnisse', 'mannschaftspaarungen'];

    /**
     * Listen, in denen die Spieler einer Mannschaft vorkommen können.
     */
    private const SPIELERLISTEN = ['mannschaften', 'mannschaftspaarungen'];

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
     * Streicht aus der Eingabemaske, was zur Datei und zur Auswahl nicht passt.
     *
     * Die Maske richtet sich nach zwei Dingen: nach dem Inhalt der gewählten
     * Turnierdatei — Einzel- oder Mannschaftsturnier, wie viele Runden — und
     * nach den angehakten Listen. Eine Einstellung, die keine gewählte Liste
     * beeinflusst, ist keine Hilfe, sondern eine Frage, die der Redakteur
     * beantworten soll, ohne dass die Antwort irgendwo ankommt.
     *
     * Solange keine Datei gewählt ist, bleibt alles stehen, was von ihr
     * abhängt: Dann ist über das Turnier nichts bekannt, und zu wenig
     * anzubieten wäre schlimmer als zu viel. Was von den Listen abhängt, wird
     * dagegen auch ohne Datei gestrichen — dafür braucht es die Datei nicht.
     *
     * Die Anpassung an die Datei greift erst nach dem Speichern, weil die
     * Datei vorher nicht im Datensatz steht; die Anpassung an die Listen
     * dagegen sofort, weil das Auswahlfeld die Maske abschickt.
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
        $gewaehlt = StringUtil::deserialize($datensatz->ctvListen, true);
        $weg = [];

        if (null !== $turnier && !$turnier->istMannschaftsturnier()) {
            $weg[] = 'ctvMannschaftSpieler';
            $weg[] = 'ctvKreuzKurz';
        } else {
            if ([] === array_intersect(self::SPIELERLISTEN, $gewaehlt)) {
                $weg[] = 'ctvMannschaftSpieler';
            }

            if (!\in_array('mannschaftskreuztabelle', $gewaehlt, true)) {
                $weg[] = 'ctvKreuzKurz';
            }
        }

        // Ein Turnier mit einer einzigen Runde kennt keinen Zwischenstand.
        if (null !== $turnier && $turnier->getLetzteRunde() < 2) {
            $weg[] = 'ctvStand';
            $weg[] = 'ctvRunden';
        } elseif ([] === array_intersect(self::RUNDENLISTEN, $gewaehlt)) {
            $weg[] = 'ctvRunden';
        }

        // Die Hinweise erklären Abweichungen der Zahlen. Gibt es in dieser
        // Datei keine und ist auch kein Zwischenstand eingestellt, der welche
        // erzeugen würde, ist das Kästchen wirkungslos.
        if (null !== $turnier && [] === $turnier->getHinweise() && !(int) $datensatz->ctvStand) {
            $weg[] = 'ctvHinweise';
        }

        if ([] === $weg) {
            return;
        }

        $palette = &$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'];
        $palette = self::ohneFelder((string) $palette, $weg);
    }

    /**
     * Liefert die Auswahl der Listen, passend zur gewählten Turnierdatei.
     *
     * Bei einem Einzelturnier erscheinen die Mannschaftslisten gar nicht
     * erst. Ausgegeben würden sie ohnehin nicht — der Listenbauer übergeht
     * sie —, aber ein Kästchen anzubieten, das nichts bewirkt, ist eine
     * schlechte Auskunft.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return string[] Die Schlüssel der anwendbaren Listen
     */
    public function listenOptionen(DataContainer $dc = null): array
    {
        $turnier = $this->turnier($dc);

        if (null === $turnier) {
            return Listen::schluessel();
        }

        return array_values(array_filter(
            Listen::schluessel(),
            static fn (string $schluessel): bool => Listen::passt($schluessel, $turnier)
        ));
    }

    /**
     * Liefert die Auswahl für „Stand nach Runde".
     *
     * Angeboten werden die Runden, für die in der Datei Paarungen stehen —
     * nicht die im Kopf angekündigte Rundenzahl: Ein laufendes Turnier hat
     * weniger, ein doppelrundiges mehr. An erster Stelle steht die Null für
     * das ganze Turnier; sie ist der Regelfall und nimmt die gespeicherten
     * Werte der Datei, statt sie nachzurechnen.
     *
     * Ist keine Datei gewählt, bleibt nur diese Null übrig. Eine Rundenzahl
     * zu raten hieße, Auswahlmöglichkeiten anzubieten, die es nicht gibt.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return int[] Die wählbaren Rundennummern, beginnend mit 0
     */
    public function standOptionen(DataContainer $dc = null): array
    {
        $letzte = $this->rundenzahl($dc);
        $beschriftungen = [0 => $GLOBALS['TL_LANG']['ctv']['standGanz'] ?? 'Ganzes Turnier'];

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
     * Der Rückruf hängt am Feld und nicht an der Tabelle; so läuft er nur,
     * wenn dieses Inhaltselement tatsächlich bearbeitet wird, und nicht bei
     * jedem beliebigen Inhaltselement.
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
            Message::addError(sprintf(
                $GLOBALS['TL_LANG']['ctv']['uploadWarnung']
                    ?? 'Die Dateiendung %s fehlt unter „Einstellungen → Erlaubte Dateitypen". Turnierdateien lassen sich deshalb nicht in die Dateiverwaltung hochladen.',
                implode(', ', $fehlend)
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
     * Datei. Die Eingabemaske soll sich dann nicht einschränken — im Zweifel
     * lieber zu viele Felder als zu wenige.
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
     * @return ContentModel|null Der Datensatz, oder null wenn es keiner des
     *                           Turnier-Betrachters ist
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
