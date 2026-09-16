<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;
use Symfony\Component\Intl\Countries;

/**
 * Länderkennungen, Ländernamen und ihre Übersetzung.
 *
 * Turnierdateien führen die dreibuchstabigen Kennungen des Weltschachbundes
 * und — bei Nationalmannschaften — englische Ländernamen. Diese Klasse bildet
 * die Kennungen auf ISO 3166 ab und übersetzt die Namen in die Sprache der
 * Seite.
 *
 * Die Namen selbst stehen nicht hier, sie kommen aus Symfony Intl. Das ist
 * eine Abhängigkeit des Contao-Kerns in beiden unterstützten Fassungen und
 * bringt die Ländernamen in allen Sprachen mit; eine eigene Liste müsste
 * gepflegt werden und veraltete — „Türkei" statt „Türkiye" ist genau die Art
 * Frage, die dort schon entschieden ist.
 */
final class Laender
{
    /**
     * Die Länderkennungen des Weltschachbundes und ihre ISO-Entsprechung.
     *
     * Die FIDE folgt überwiegend den Kennungen des Olympischen Komitees, die
     * sich von den ISO-Kennungen an vielen Stellen unterscheiden — „GER" gegen
     * „DE". Was hier fehlt, bleibt unübersetzt und erscheint als Code: Bei
     * „FID", „IBCA" oder „IPCA" — Verbände statt Länder — gibt es weder
     * Flagge noch Ländernamen.
     *
     * @var array<string,string>
     */
    private const ISO = [
        'AFG' => 'AF', 'ALB' => 'AL', 'ALG' => 'DZ', 'AND' => 'AD', 'ANG' => 'AO',
        'ANT' => 'AG', 'ARG' => 'AR', 'ARM' => 'AM', 'ARU' => 'AW', 'AUS' => 'AU',
        'AUT' => 'AT', 'AZE' => 'AZ', 'BAH' => 'BS', 'BAN' => 'BD', 'BAR' => 'BB',
        'BDI' => 'BI', 'BEL' => 'BE', 'BEN' => 'BJ', 'BER' => 'BM', 'BHU' => 'BT',
        'BIH' => 'BA', 'BLR' => 'BY', 'BLZ' => 'BZ', 'BOL' => 'BO', 'BOT' => 'BW',
        'BRA' => 'BR', 'BRN' => 'BH', 'BRU' => 'BN', 'BUL' => 'BG', 'BUR' => 'BF',
        'CAF' => 'CF', 'CAM' => 'KH', 'CAN' => 'CA', 'CAY' => 'KY', 'CGO' => 'CG',
        'CHA' => 'TD', 'CHI' => 'CL', 'CHN' => 'CN', 'CIV' => 'CI', 'CMR' => 'CM',
        'COD' => 'CD', 'COL' => 'CO', 'COM' => 'KM', 'CPV' => 'CV', 'CRC' => 'CR',
        'CRO' => 'HR', 'CUB' => 'CU', 'CYP' => 'CY', 'CZE' => 'CZ', 'DEN' => 'DK',
        'DJI' => 'DJ', 'DOM' => 'DO', 'ECU' => 'EC', 'EGY' => 'EG', 'ENG' => 'GB',
        'ERI' => 'ER', 'ESA' => 'SV', 'ESP' => 'ES', 'EST' => 'EE', 'ETH' => 'ET',
        'FAI' => 'FO', 'FIJ' => 'FJ', 'FIN' => 'FI', 'FRA' => 'FR', 'GAB' => 'GA',
        'GAM' => 'GM', 'GCI' => 'GG', 'GEO' => 'GE', 'GER' => 'DE', 'GHA' => 'GH',
        'GRE' => 'GR', 'GRN' => 'GD', 'GUA' => 'GT', 'GUM' => 'GU', 'GUY' => 'GY',
        'HAI' => 'HT', 'HKG' => 'HK', 'HON' => 'HN', 'HUN' => 'HU', 'INA' => 'ID',
        'IND' => 'IN', 'IRI' => 'IR', 'IRL' => 'IE', 'IRQ' => 'IQ', 'ISL' => 'IS',
        'ISR' => 'IL', 'ISV' => 'VI', 'ITA' => 'IT', 'IVB' => 'VG', 'JAM' => 'JM',
        'JCI' => 'JE', 'JOR' => 'JO', 'JPN' => 'JP', 'KAZ' => 'KZ', 'KEN' => 'KE',
        'KGZ' => 'KG', 'KOR' => 'KR', 'KOS' => 'XK', 'KSA' => 'SA', 'KUW' => 'KW',
        'LAO' => 'LA', 'LAT' => 'LV', 'LBA' => 'LY', 'LBN' => 'LB', 'LBR' => 'LR',
        'LCA' => 'LC', 'LES' => 'LS', 'LIE' => 'LI', 'LTU' => 'LT', 'LUX' => 'LU',
        'MAC' => 'MO', 'MAD' => 'MG', 'MAR' => 'MA', 'MAS' => 'MY', 'MAW' => 'MW',
        'MDA' => 'MD', 'MDV' => 'MV', 'MEX' => 'MX', 'MGL' => 'MN', 'MKD' => 'MK',
        'MLI' => 'ML', 'MLT' => 'MT', 'MNE' => 'ME', 'MNC' => 'MC', 'MOZ' => 'MZ',
        'MRI' => 'MU', 'MTN' => 'MR', 'MYA' => 'MM', 'NAM' => 'NA', 'NCA' => 'NI',
        'NED' => 'NL', 'NEP' => 'NP', 'NGR' => 'NG', 'NIG' => 'NE', 'NOR' => 'NO',
        'NZL' => 'NZ', 'OMA' => 'OM', 'PAK' => 'PK', 'PAN' => 'PA', 'PAR' => 'PY',
        'PER' => 'PE', 'PHI' => 'PH', 'PLE' => 'PS', 'PNG' => 'PG', 'POL' => 'PL',
        'POR' => 'PT', 'PUR' => 'PR', 'QAT' => 'QA', 'ROU' => 'RO', 'RSA' => 'ZA',
        'RUS' => 'RU', 'RWA' => 'RW', 'SCO' => 'GB', 'SEN' => 'SN', 'SEY' => 'SC',
        'SGP' => 'SG', 'SLE' => 'SL', 'SLO' => 'SI', 'SMR' => 'SM', 'SOL' => 'SB',
        'SOM' => 'SO', 'SRB' => 'RS', 'SRI' => 'LK', 'SSD' => 'SS', 'STP' => 'ST',
        'SUD' => 'SD', 'SUI' => 'CH', 'SUR' => 'SR', 'SVK' => 'SK', 'SWE' => 'SE',
        'SWZ' => 'SZ', 'SYR' => 'SY', 'TAN' => 'TZ', 'THA' => 'TH', 'TJK' => 'TJ',
        'TKM' => 'TM', 'TLS' => 'TL', 'TOG' => 'TG', 'TPE' => 'TW', 'TTO' => 'TT',
        'TUN' => 'TN', 'TUR' => 'TR', 'UAE' => 'AE', 'UGA' => 'UG', 'UKR' => 'UA',
        'URU' => 'UY', 'USA' => 'US', 'UZB' => 'UZ', 'VEN' => 'VE', 'VIE' => 'VN',
        'VIN' => 'VC', 'WLS' => 'GB', 'YEM' => 'YE', 'ZAM' => 'ZM', 'ZIM' => 'ZW',
    ];

    /**
     * Länder, die im Schach eigene Verbände haben, nach ISO aber nicht.
     *
     * England, Schottland und Wales spielen als eigene Mannschaften, teilen
     * sich aber die ISO-Kennung des Vereinigten Königreichs. Symfony Intl
     * kennt sie nicht einzeln; ihre Namen stehen deshalb hier.
     *
     * @var array<string,array<string,string>>
     */
    private const EIGENE_NAMEN = [
        'ENG' => ['de' => 'England', 'en' => 'England'],
        'SCO' => ['de' => 'Schottland', 'en' => 'Scotland'],
        'WLS' => ['de' => 'Wales', 'en' => 'Wales'],
    ];

    /**
     * Englische Schreibweisen, die von der Bezeichnung in Symfony Intl abweichen.
     *
     * Turnierleitungen schreiben „USA", „Czech Republic" oder „Turkey"; Intl
     * führt „United States", „Czechia" und „Türkiye". Ohne diese Liste bliebe
     * genau der Name unübersetzt, den eine Turnierleitung am ehesten tippt.
     *
     * @var array<string,string[]>
     */
    private const ENGLISCHE_VARIANTEN = [
        'BA' => ['Bosnia', 'Bosnia & Herzegovina'],
        'CD' => ['DR Congo', 'Congo DR', 'Democratic Republic of the Congo'],
        'CG' => ['Congo', 'Republic of the Congo'],
        'CI' => ['Ivory Coast', "Cote d'Ivoire"],
        'CV' => ['Cape Verde'],
        'CZ' => ['Czech Republic'],
        'HK' => ['Hong Kong'],
        'IR' => ['Iran'],
        'KR' => ['Korea', 'South Korea', 'Korea Republic'],
        'LA' => ['Laos'],
        'MD' => ['Moldova'],
        'MK' => ['Macedonia', 'North Macedonia', 'FYR Macedonia'],
        'MM' => ['Myanmar', 'Burma'],
        'MO' => ['Macau', 'Macao'],
        'PS' => ['Palestine'],
        'RU' => ['Russia'],
        'SY' => ['Syria'],
        'SZ' => ['Swaziland', 'Eswatini'],
        'TR' => ['Turkey', 'Turkiye'],
        'TW' => ['Chinese Taipei', 'Taiwan'],
        'TZ' => ['Tanzania'],
        'US' => ['USA', 'United States of America', 'US'],
        'AE' => ['UAE', 'United Arab Emirates'],
        'VE' => ['Venezuela'],
        'VN' => ['Vietnam', 'Viet Nam'],
        'BO' => ['Bolivia'],
        'BN' => ['Brunei'],
        'GB' => ['Great Britain', 'United Kingdom', 'UK'],
        'FO' => ['Faroe Islands', 'Faroes'],
    ];

    /**
     * Bildet eine FIDE-Kennung auf die ISO-Kennung ab.
     *
     * @param mixed $code Die dreibuchstabige FIDE-Kennung, Groß- oder Kleinschreibung
     *
     * @return string|null Die zweibuchstabige ISO-Kennung, oder null wenn es
     *                     keine gibt
     */
    public static function iso(mixed $code): ?string
    {
        return self::ISO[strtoupper(trim((string) $code))] ?? null;
    }

    /**
     * Nennt die Flaggenkennung zu einer FIDE-Kennung.
     *
     * Das ist meist die kleingeschriebene ISO-Kennung — die Dateinamen des
     * Pakets flag-icons folgen ihr. England, Schottland und Wales haben
     * dort eigene Flaggen, obwohl sie sich die ISO-Kennung des Vereinigten
     * Königreichs teilen; für sie gelten die Unterkennungen.
     *
     * @param mixed $code Die dreibuchstabige FIDE-Kennung
     *
     * @return string|null Die Kennung der Flaggendatei ohne Endung, oder null
     *                     wenn es zu dieser Kennung keine Flagge gibt
     */
    public static function flaggenCode(mixed $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        $eigene = [
            'ENG' => 'gb-eng',
            'SCO' => 'gb-sct',
            'WLS' => 'gb-wls',
        ];

        if (isset($eigene[$code])) {
            return $eigene[$code];
        }

        $iso = self::ISO[$code] ?? null;

        return null === $iso ? null : strtolower($iso);
    }

    /**
     * Nennt den Ländernamen zu einer FIDE-Kennung in einer Sprache.
     *
     * @param mixed       $code    Die dreibuchstabige FIDE-Kennung
     * @param string|null $sprache Die Sprache, etwa `de` oder `en`; ohne Angabe
     *                             die Sprache der Seite, sonst Deutsch
     *
     * @return string|null Der Ländername, oder null wenn die Kennung kein Land
     *                     bezeichnet oder Symfony Intl nicht verfügbar ist
     */
    public static function name(mixed $code, ?string $sprache = null): ?string
    {
        $code = strtoupper(trim((string) $code));
        $sprache = self::sprache($sprache);

        if (isset(self::EIGENE_NAMEN[$code])) {
            return self::EIGENE_NAMEN[$code][$sprache] ?? self::EIGENE_NAMEN[$code]['en'];
        }

        $iso = self::ISO[$code] ?? null;

        if (null === $iso || !class_exists(Countries::class)) {
            return null;
        }

        try {
            return Countries::getName($iso, $sprache);
        } catch (\Throwable) {
            // Symfony Intl kennt manche Kennung in manchen Fassungen nicht —
            // Kosovo etwa kam erst spät hinzu. Dann bleibt der Name offen.
            return null;
        }
    }

    /**
     * Übersetzt den Namen einer Nationalmannschaft.
     *
     * Übersetzt wird nur, was sicher ein Ländername ist: Der Name muss — bis
     * auf eine angehängte Nummer wie bei „Uzbekistan 2" — mit dem englischen
     * Namen des Landes übereinstimmen, das die Mannschaft als Föderation
     * führt, oder mit dessen FIDE-Kennung. Ein Verein wie „SK Deizisau" mit
     * der Föderation GER bleibt dadurch unangetastet, ebenso Verbände wie
     * „IBCA", die kein Land sind.
     *
     * @param string      $name    Der Mannschaftsname aus der Turnierdatei
     * @param mixed       $land    Die Föderation der Mannschaft, FIDE-Kennung
     * @param string|null $sprache Die Zielsprache; ohne Angabe die der Seite
     *
     * @return string Der übersetzte oder der unveränderte Name
     */
    public static function mannschaftsname(string $name, mixed $land, ?string $sprache = null): string
    {
        $code = strtoupper(trim((string) $land));
        $iso = self::ISO[$code] ?? null;

        if (null === $iso) {
            return $name;
        }

        // Eine angehängte Nummer — „Uzbekistan 1", „Germany 2" — bleibt stehen.
        $basis = $name;
        $zusatz = '';

        if (preg_match('/^(.*?)(\s+\d+|\s+[IVX]+)$/u', trim($name), $teile)) {
            $basis = $teile[1];
            $zusatz = $teile[2];
        }

        $varianten = array_merge(
            [$code, self::name($code, 'en') ?? '', self::EIGENE_NAMEN[$code]['en'] ?? ''],
            self::ENGLISCHE_VARIANTEN[$iso] ?? []
        );

        $gesucht = self::vergleichbar($basis);
        $passt = false;

        foreach ($varianten as $variante) {
            if ('' !== $variante && self::vergleichbar($variante) === $gesucht) {
                $passt = true;

                break;
            }
        }

        if (!$passt) {
            return $name;
        }

        $uebersetzt = self::name($code, $sprache);

        return null === $uebersetzt ? $name : $uebersetzt.$zusatz;
    }

    /**
     * Übersetzt die Mannschaftsnamen eines ganzen Turniers.
     *
     * Die Namen stehen an zwei Stellen: in den Mannschaften selbst und in den
     * Wettkämpfen als Name der Gegenseite. Beide werden übersetzt, sonst hieße
     * dieselbe Mannschaft in der Tabelle „Polen" und im Wettkampf „Poland".
     *
     * @param Turnier     $turnier Das eingelesene Turnier
     * @param string|null $sprache Die Zielsprache; ohne Angabe die der Seite
     *
     * @return Turnier Ein neues Turnier mit übersetzten Namen, oder das
     *                 übergebene, wenn es keine Mannschaften hat
     */
    public static function uebersetzeMannschaften(Turnier $turnier, ?string $sprache = null): Turnier
    {
        $mannschaften = $turnier->getMannschaften();

        if ([] === $mannschaften) {
            return $turnier;
        }

        foreach ($mannschaften as $nummer => $mannschaft) {
            $mannschaften[$nummer]['name'] = self::mannschaftsname(
                (string) ($mannschaft['name'] ?? ''),
                $mannschaft['land'] ?? '',
                $sprache
            );
        }

        $paarungen = $turnier->getMannschaftspaarungen();

        foreach ($paarungen as $nummer => $runden) {
            foreach ($runden as $runde => $satz) {
                $gegner = (int) ($satz['gegner'] ?? 0);

                if (isset($mannschaften[$gegner])) {
                    $paarungen[$nummer][$runde]['gegnerName'] = $mannschaften[$gegner]['name'];
                }
            }
        }

        return $turnier->mitMannschaften($mannschaften, $paarungen);
    }

    /**
     * Bestimmt die Sprache für die Ländernamen.
     *
     * @param string|null $sprache Die ausdrücklich gewünschte Sprache
     *
     * @return string Die Sprache als zweibuchstabiges Kürzel; ohne Angabe die
     *                der Contao-Seite, und wenn auch die fehlt, Deutsch
     */
    private static function sprache(?string $sprache): string
    {
        $sprache = $sprache ?: (string) ($GLOBALS['TL_LANGUAGE'] ?? 'de');

        return strtolower(substr(str_replace('_', '-', $sprache), 0, 2)) ?: 'de';
    }

    /**
     * Bringt einen Namen in eine vergleichbare Form.
     *
     * Groß- und Kleinschreibung, Akzente, Satzzeichen und Leerraum spielen
     * beim Vergleich keine Rolle: „Türkiye", „Turkiye" und „TURKIYE" sollen
     * dasselbe Land treffen.
     *
     * @param string $text Der Name
     *
     * @return string Nur noch Kleinbuchstaben a bis z
     */
    private static function vergleichbar(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, [
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'í' => 'i', 'ì' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u',
            'ç' => 'c', 'ñ' => 'n', '&' => 'and',
        ]);

        return (string) preg_replace('/[^a-z]/', '', $text);
    }
}
