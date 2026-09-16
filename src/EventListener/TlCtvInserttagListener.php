<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\EventListener;

use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;

/**
 * Rückrufe des Backend-Moduls „Turnier-Inserttags".
 *
 * Zwei Dinge macht die Klasse: Sie zeigt in der Liste den fertigen Inserttag
 * zum Abschreiben, und sie bildet die Kennung aus dem Titel, wenn der
 * Redakteur keine einträgt.
 */
class TlCtvInserttagListener
{
    /**
     * Erzeugt den Rückruf.
     *
     * @param ContaoFramework $framework Für den Zugriff auf die Datenbank
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Baut die Beschriftung einer Zeile in der Übersicht.
     *
     * Hinter dem Titel steht der Inserttag, wie er in die Seite gehört. Die
     * geschweiften Klammern stehen dabei als Zeichenentitäten im Markup:
     * Contao ersetzt Inserttags auch im Backend, und ohne die Maskierung
     * stünde in der Übersicht die ganze Turniertabelle. Beim Herauskopieren
     * kommen wieder echte Klammern heraus.
     *
     * @param array<string,mixed> $zeile Der Datensatz der Zeile
     *
     * @return string Die Beschriftung als HTML
     */
    public function beschriftung(array $zeile): string
    {
        $kennung = trim((string) ($zeile['alias'] ?? '')) ?: (string) ($zeile['id'] ?? '');
        $tag = str_replace(['{', '}'], ['&#123;', '&#125;'], sprintf('{{ctv::%s}}', $kennung));

        return sprintf(
            '%s <span style="color:#b3b3b3;padding-left:.75em">%s</span>',
            StringUtil::specialchars((string) ($zeile['titel'] ?? '')),
            $tag
        );
    }

    /**
     * Bildet die Kennung und stellt sicher, dass es sie nur einmal gibt.
     *
     * Ohne Eingabe entsteht sie aus dem Titel — „Olympiade 2026, Runde 1" wird
     * zu „olympiade-2026-runde-1". Gibt es die Kennung schon, hängt ein
     * Bindestrich mit der Datensatz-ID an; zwei gleiche Kennungen wären für
     * den Inserttag nicht auseinanderzuhalten.
     *
     * @param mixed         $wert Die eingegebene Kennung, oft leer
     * @param DataContainer $dc   Der Data Container mit Datensatz und Eingaben
     *
     * @return string Die endgültige Kennung
     *
     * @throws InternalServerErrorException Wenn eine von Hand eingetragene
     *                                      Kennung schon vergeben ist
     */
    public function kennung(mixed $wert, DataContainer $dc): string
    {
        $this->framework->initialize();

        $wert = trim((string) $wert);
        $selbstGewaehlt = '' !== $wert;

        if (!$selbstGewaehlt) {
            $wert = StringUtil::generateAlias((string) ($dc->activeRecord->titel ?? ''));
        }

        $datenbank = $this->framework->getAdapter(Database::class)->getInstance();

        $vergeben = $datenbank
            ->prepare('SELECT id FROM tl_ctv_inserttag WHERE alias=? AND id!=?')
            ->execute($wert, $dc->id)
        ;

        if (!$vergeben->numRows) {
            return $wert;
        }

        if ($selbstGewaehlt) {
            throw new InternalServerErrorException(sprintf(
                $GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Die Kennung "%s" ist bereits vergeben.',
                $wert
            ));
        }

        return $wert.'-'.$dc->id;
    }
}
