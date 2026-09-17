<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Model;

use Contao\CoreBundle\Slug\Slug;
use Contao\Model;

/**
 * Datensatz einer Turnierausgabe, die über einen Inserttag eingebunden wird.
 *
 * Die Felder sind dieselben wie am Inhaltselement; gepflegt werden sie im
 * Backend-Modul „Turnier-Inserttags". Das Model gibt es, damit der Inserttag
 * seinen Datensatz über `findByIdOrAlias()` finden kann — sowohl
 * `{{ctv::7}}` als auch `{{ctv::olympiade-ger-r1}}` sollen gehen.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $titel
 * @property string $alias
 * @property string $ctvDatei
 * @property string $ctvFormat
 * @property string $ctvListe
 * @property string $ctvSpalten
 * @property int    $ctvStand
 * @property string $ctvStandAus
 * @property string $ctvRunden
 * @property string $ctvMannschaftswahl
 * @property string $ctvMannschaftSpieler
 * @property string $ctvKreuzKurz
 * @property string $ctvDatum
 * @property string $ctvHinweise
 */
class CtvInserttagModel extends Model
{
    /**
     * Die Tabelle, die dieses Model führt.
     *
     * @var string
     */
    protected static $strTable = 'tl_ctv_inserttag';

    /**
     * Die Einstellungen, mit denen eine Kennung gebildet wird.
     *
     * Die Sprache steht fest auf Deutsch: Nur so wird aus „ä" ein „ae" und
     * aus „ß" ein „ss" — wie bei den Seitenaliasen einer deutschen Seite.
     * Nach der Sprache des Backends zu gehen hieße, dass derselbe Titel je
     * nach Redakteur „maenner" oder „manner" ergibt; eine Kennung, die man
     * aus dem Kopf in einen Text schreibt, muss aber vorhersagbar sein.
     *
     * @var array{locale:string,validChars:string,delimiter:string}
     */
    public const KENNUNG_OPTIONEN = [
        'locale' => 'de',
        'validChars' => 'a-z0-9',
        'delimiter' => '-',
    ];

    /**
     * Bildet aus einem Text eine Kennung, wie Contao es für Aliase tut.
     *
     * „Olympiade 2026 Männer, 2. Runde" wird zu
     * „olympiade-2026-maenner-2-runde". Benutzt wird der Slug-Dienst von
     * Contao und nicht `StringUtil::generateAlias()`: Nur der Dienst kennt die
     * Umschrift nach Sprache; `generateAlias()` macht aus „ä" ein „a".
     *
     * @param Slug   $slug Der Slug-Dienst von Contao (`contao.slug`)
     * @param string $text Titel oder eingegebene Kennung
     *
     * @return string Die Kennung, leer wenn der Text nichts Verwertbares enthält
     */
    public static function kennung(Slug $slug, string $text): string
    {
        $text = trim($text);

        return '' === $text ? '' : $slug->generate($text, self::KENNUNG_OPTIONEN);
    }
}
