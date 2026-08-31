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
     * Blendet die Felder aus, die zur Turnierart nicht passen.
     *
     * Ist die gewählte Datei ein Einzelturnier, verschwindet die Feldgruppe
     * für Mannschaftsturniere; ist es ein Mannschaftsturnier, verschwindet
     * sie nicht. Solange keine Datei gewählt ist — beim Anlegen des Elements
     * — bleibt alles stehen, denn dann ist über die Turnierart nichts
     * bekannt.
     *
     * Die Maske richtet sich damit nach dem Inhalt der Datei und nicht nach
     * einer Einstellung, die noch einmal gepflegt werden müsste. Der Preis:
     * Sie passt sich erst nach dem Speichern an, weil die Datei vorher nicht
     * im Datensatz steht.
     *
     * @param DataContainer|null $dc Der Data Container mit der Datensatz-ID
     *
     * @return void
     */
    public function passeAnTurnierart(DataContainer $dc = null): void
    {
        $turnier = $this->turnier($dc);

        if (null === $turnier || $turnier->istMannschaftsturnier()) {
            return;
        }

        $palette = &$GLOBALS['TL_DCA']['tl_content']['palettes']['chesstournamentviewer'];

        // Die ganze Feldgruppe fällt weg, nicht nur ihre Felder: Eine leere
        // Überschrift „Mannschaftsturniere" wäre irreführender als gar keine.
        $palette = preg_replace('/\{ctv_mannschaft_legend[^}]*\}[^;]*;/', '', (string) $palette);
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
        if (null === $dc || !$dc->id) {
            return null;
        }

        $this->framework->initialize();
        $datensatz = $this->framework->getAdapter(ContentModel::class)->findByPk($dc->id);

        if (null === $datensatz || 'chesstournamentviewer' !== $datensatz->type) {
            return null;
        }

        return $this->lader->ladeStill($datensatz->ctvDatei, (string) ($datensatz->ctvFormat ?: 'auto'));
    }
}
