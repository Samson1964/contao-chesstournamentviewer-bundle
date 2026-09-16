<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Model;

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
}
