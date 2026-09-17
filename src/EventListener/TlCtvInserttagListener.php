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
use Contao\CoreBundle\Slug\Slug;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;
use Schachbulle\ContaoChesstournamentviewerBundle\Model\CtvInserttagModel;

/**
 * Rückrufe des Backend-Moduls „Turnier-Inserttags".
 *
 * Zwei Dinge macht die Klasse: Sie zeigt in der Liste den fertigen Inserttag
 * zum Abschreiben, und sie bildet die Kennung — aus dem Titel, wenn der
 * Redakteur keine einträgt, und sonst aus seiner Eingabe.
 */
class TlCtvInserttagListener
{
    /**
     * Erzeugt den Rückruf.
     *
     * @param ContaoFramework $framework Für den Zugriff auf die Datenbank
     * @param Slug            $slug      Der Slug-Dienst von Contao; bildet die
     *                                   Kennung samt Umschrift der Umlaute
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Slug $slug,
    ) {
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
     * Ohne Eingabe entsteht sie aus dem Titel. Mit Eingabe wird diese in
     * dieselbe Form gebracht: Kleinschreibung, Bindestriche, und Umlaute so
     * umgeschrieben, wie Contao es bei Seitenaliasen tut — „ä" zu „ae", „ß"
     * zu „ss". So entsteht aus „Olympiade 2026 Männer, 2. Runde" die Kennung
     * „olympiade-2026-maenner-2-runde".
     *
     * Bis Fassung 1.16.0 blieb eine eingegebene Kennung unverändert. Stand
     * dort „männer" und im Text „maenner", fand der Inserttag nichts.
     *
     * Gibt es die aus dem Titel gebildete Kennung schon, hängt eine
     * fortlaufende Nummer an. Eine von Hand eingetragene, schon vergebene
     * Kennung wird dagegen abgelehnt — sie still zu ändern hieße, dass der
     * Redakteur einen anderen Inserttag in die Seite schreibt als den, der
     * gespeichert ist.
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

        $eingabe = trim((string) $wert);
        $selbstGewaehlt = '' !== $eingabe;
        $quelle = $selbstGewaehlt ? $eingabe : (string) ($dc->activeRecord->titel ?? '');

        $kennung = CtvInserttagModel::kennung($this->slug, $quelle);

        if ('' === $kennung) {
            return '';
        }

        $datenbank = $this->framework->getAdapter(Database::class)->getInstance();

        $vergeben = static fn (string $kandidat): bool => (bool) $datenbank
            ->prepare('SELECT id FROM tl_ctv_inserttag WHERE alias=? AND id!=?')
            ->execute($kandidat, $dc->id)
            ->numRows
        ;

        if (!$vergeben($kennung)) {
            return $kennung;
        }

        if ($selbstGewaehlt) {
            throw new InternalServerErrorException(sprintf(
                $GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Die Kennung "%s" ist bereits vergeben.',
                $kennung
            ));
        }

        for ($nummer = 2; $vergeben($kennung.'-'.$nummer); ++$nummer) {
            // weiterzählen, bis eine freie Kennung gefunden ist
        }

        return $kennung.'-'.$nummer;
    }
}
