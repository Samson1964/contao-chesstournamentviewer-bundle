<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\EventListener;

use Contao\DataContainer;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\FormatVerzeichnis;

/**
 * Rückrufe für den Data Container der Inhaltselemente.
 *
 * Die Klasse hält alles, was die DCA-Datei über die registrierten
 * Turnierformate wissen muss. Sie steht hier und nicht in der DCA-Datei,
 * damit das Format-Verzeichnis über die Dienstverdrahtung hereinkommt und
 * nicht als öffentlicher Dienst aus dem Container geholt werden muss.
 */
class TlContentListener
{
    /**
     * Erzeugt den Rückruf.
     *
     * @param FormatVerzeichnis $formate Kennt alle registrierten Turnierformate
     */
    public function __construct(private readonly FormatVerzeichnis $formate)
    {
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
}
