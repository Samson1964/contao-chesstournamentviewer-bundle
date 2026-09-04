<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Liste;

use Contao\StringUtil;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;

/**
 * Kleine Formatierungshilfen für die Templates.
 *
 * Die Templates sollen Zahlen ausgeben und nicht formatieren. Alles, was in
 * mehreren Templates gleich aussehen muss — halbe Punkte, Bilanzen,
 * Maskierung —, steht deshalb hier. Wer ein Template überschreibt, bekommt
 * diese Hilfen mit und muss die Schreibweise nicht nachbauen.
 */
final class Ausgabe
{
    /**
     * Die Länderkennungen des Weltschachbundes und ihre ISO-Entsprechung.
     *
     * Turnierdateien führen die dreibuchstabigen Kennungen der FIDE; für eine
     * Flagge braucht es die zweibuchstabigen nach ISO 3166. Die FIDE folgt
     * überwiegend den Kennungen des Olympischen Komitees, die sich von den
     * ISO-Kennungen an vielen Stellen unterscheiden — „GER" gegen „DE".
     *
     * Was hier fehlt, erscheint als Code. Das ist der bessere Ausgang als
     * eine falsche Flagge: Bei „FID" — Sportler unter der Flagge des
     * Weltschachbundes — gibt es keine.
     *
     * @var array<string,string>
     */
    private const LAENDER = [
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
     * Schreibt eine Punktzahl in der im Schach üblichen Form.
     *
     * Halbe Punkte erscheinen als ½, ganze ohne Nachkommastelle. Der Wert
     * null steht für „noch kein Ergebnis" und ergibt eine leere Zeichenkette —
     * eine Null stünde dort für ein verlorenes Spiel und wäre falsch.
     *
     * Die Schreibweise selbst kommt aus der Mannschaftswertung, damit sie im
     * ganzen Bundle nur einmal festgelegt ist.
     *
     * @param float|int|string|null $wert Die Punktzahl
     *
     * @return string Die Punktzahl als Text, etwa „3½"
     */
    public static function punkte(mixed $wert): string
    {
        if (null === $wert || '' === $wert) {
            return '';
        }

        return Mannschaftswertung::punkteText((float) $wert);
    }

    /**
     * Gibt eine Zahl aus, unterdrückt dabei aber die Null.
     *
     * In Ranglisten und Teilnehmerlisten steht bei fehlenden Wertungszahlen
     * eine 0 in der Datei. Eine Spalte voller Nullen liest sich schlechter
     * als eine leere.
     *
     * @param int|float|string|null $wert Die Zahl
     *
     * @return string Die Zahl als Text, oder eine leere Zeichenkette bei 0
     */
    public static function zahl(mixed $wert): string
    {
        return empty($wert) ? '' : (string) $wert;
    }

    /**
     * Setzt die Bilanz eines Teilnehmers zusammen.
     *
     * @param array<string,mixed> $spieler Der Teilnehmerdatensatz
     *
     * @return string Siege, Remisen und Niederlagen als „5/1/0"
     */
    public static function bilanz(array $spieler): string
    {
        return sprintf(
            '%d/%d/%d',
            (int) ($spieler['siege'] ?? 0),
            (int) ($spieler['remis'] ?? 0),
            (int) ($spieler['niederlagen'] ?? 0)
        );
    }

    /**
     * Setzt Titel und Namen eines Teilnehmers zusammen.
     *
     * @param array<string,mixed> $spieler Der Teilnehmerdatensatz
     *
     * @return string Name mit vorangestelltem Titel, unmaskiert
     */
    public static function name(array $spieler): string
    {
        $titel = trim((string) ($spieler['titel'] ?? ''));
        $name = trim((string) ($spieler['name'] ?? ''));

        return '' === $titel ? $name : $titel.' '.$name;
    }

    /**
     * Maskiert einen Wert für die Ausgabe im HTML.
     *
     * Turnierdateien kommen nicht durch die Eingabeprüfung von Contao; ihre
     * Inhalte müssen deshalb beim Ausgeben maskiert werden. Namen mit
     * Sonderzeichen — „Schmidt,Hans & Sohn" — gibt es durchaus.
     *
     * @param mixed $wert Der auszugebende Wert
     *
     * @return string Der maskierte Wert
     */
    public static function esc(mixed $wert): string
    {
        return StringUtil::specialchars((string) $wert);
    }

    /**
     * Schreibt das Ergebnis einer Partie als vollständige Paarung.
     *
     * In einer Ergebnisliste steht die Zahl zwischen zwei Namen; eine
     * einzelne „1" ließe offen, für welche Seite sie gilt. Ausgegeben wird
     * deshalb beides — „1:0", „½:½", „0:1" —, wie es auch Swiss-Chess und
     * chess-results tun.
     *
     * @param float|null $ergebnis Punkte für Weiß, oder null wenn die Partie
     *                             noch nicht gewertet ist
     *
     * @return string Das Ergebnis beider Seiten, leer wenn keines vorliegt
     */
    public static function ergebnisPaar(mixed $ergebnis, string $status = '', float $hoechstwert = 1.0): string
    {
        if (null === $ergebnis || '' === $ergebnis) {
            return '';
        }

        $eigen = (float) $ergebnis;
        $hoechstwert = max(1.0, $hoechstwert);

        // Kampflose Partien werden nicht mit Zahlen geschrieben, sondern mit
        // + und -, wie es Swiss-Chess und chess-results halten: Eine 1:0 ließe
        // eine gespielte Partie vermuten, die es nie gab.
        if (self::istKampflos($status)) {
            return match (true) {
                $eigen >= $hoechstwert => '+:-',
                $eigen <= 0.0 => '-:+',
                default => '½:½',
            };
        }

        // Die Gegenseite ist der Höchstwert minus dem eigenen Ergebnis. Bei
        // zwei Partien je Runde — verbreitet bei Blitzturnieren — läuft ein
        // Rundenergebnis von 0 bis 2, und aus „1½" wird „1½:½".
        return self::punkte($eigen).':'.self::punkte($hoechstwert - $eigen);
    }

    /**
     * Sagt, ob eine Partie kampflos gewertet wurde.
     *
     * Der Status kommt aus der Turnierdatei. „kampflos" steht für eine Partie,
     * zu der einer nicht erschienen ist, „nicht eingesetzt" für ein Brett, das
     * eine Mannschaft nicht besetzt hat — gewertet werden beide gleich.
     *
     * @param string $status Statustext aus dem Paarungssatz
     *
     * @return bool true, wenn nicht gespielt wurde
     */
    public static function istKampflos(string $status): bool
    {
        return \in_array(trim($status), ['kampflos', 'nicht eingesetzt'], true);
    }

    /**
     * Nennt die Wertungszahl, die im Turnier den Ausschlag gibt.
     *
     * Die Turnierwertungszahl wird je nach Einstellung aus Elo oder aus der
     * DWZ/NWZ gebildet. In einer Paarungsliste soll im Spaltenkopf stehen,
     * welche Zahl der Leser vor sich hat, und nicht der Sammelbegriff.
     *
     * @param mixed $turnier Das Turnier; erwartet wird ein Objekt mit der
     *                       Methode kopf(), andere Werte ergeben die
     *                       allgemeine Bezeichnung
     *
     * @return string „Elo", „NWZ" oder „TWZ", bereits maskiert
     */
    public static function wertungsname(mixed $turnier): string
    {
        $einstellung = null;

        if (\is_object($turnier) && method_exists($turnier, 'kopf')) {
            // Nennt das Format die Bezeichnung selbst, gilt sie. Swiss-Manager
            // tut das: Dort steht die Einstellung nicht in der Datei, wohl
            // aber, nach welcher Zahl die Startrangliste geordnet ist.
            $name = trim((string) $turnier->kopf('wertungName', ''));

            if ('' !== $name) {
                return self::esc($name);
            }

            $einstellung = $turnier->kopf('twzErmittlung');
        }

        // Fehlt die Angabe, bleibt es beim Sammelbegriff. Sie auf „Elo" zu
        // legen — die 0 der SWT-Dateien — hieße, eine Aussage zu treffen, die
        // nicht in der Datei steht: In einem Turnier nach nationalen Zahlen
        // stünde dann „Elo" über der falschen Spalte.
        $schluessel = match (true) {
            null === $einstellung => 'twz',
            0 === (int) $einstellung => 'elo',
            1 === (int) $einstellung => 'nwz',
            default => 'twz',
        };

        return self::esc($GLOBALS['TL_LANG']['ctv']['wertung'][$schluessel] ?? 'TWZ');
    }

    /**
     * Kürzt die Bezeichnung einer Feinwertung für den Spaltenkopf ab.
     *
     * Namen wie „Sonneborn-Berger" oder „Rating-Differenz (NWZ/TWZ)" machen
     * eine Zahlenspalte doppelt so breit wie nötig. Gibt es keine Kurzform,
     * bleibt der volle Name stehen — lieber breit als unverständlich.
     *
     * @param string $bezeichnung Die volle Bezeichnung aus der Turnierdatei
     *
     * @return string Die Kurzform oder die unveränderte Bezeichnung
     */
    public static function feinwertungKurz(string $bezeichnung): string
    {
        $kurz = $GLOBALS['TL_LANG']['ctv']['feinwertungKurz'][trim($bezeichnung)] ?? null;

        return \is_string($kurz) && '' !== $kurz ? $kurz : $bezeichnung;
    }

    /**
     * Gibt eine Rundenüberschrift aus.
     *
     * @param int $runde Die Rundennummer
     *
     * @return string Die Überschrift, etwa „Runde 3"
     */
    public static function runde(int $runde): string
    {
        return sprintf($GLOBALS['TL_LANG']['ctv']['runde'] ?? 'Runde %s', $runde);
    }

    /**
     * Gibt eine Spaltenbeschriftung aus der Sprachdatei zurück.
     *
     * Fehlt die Übersetzung, erscheint der Schlüssel. Das ist unschön, aber
     * besser als eine namenlose Spalte.
     *
     * @param string $schluessel Schlüssel unter TL_LANG['ctv']['spalte']
     *
     * @return string Die Beschriftung, bereits maskiert
     */
    public static function spalte(string $schluessel): string
    {
        return self::esc($GLOBALS['TL_LANG']['ctv']['spalte'][$schluessel] ?? $schluessel);
    }

    /**
     * Gibt ein Wort aus der Sprachdatei zurück.
     *
     * @param string $schluessel Schlüssel unter TL_LANG['ctv']
     * @param string $standard   Rückfalltext, wenn die Übersetzung fehlt
     *
     * @return string Der Text, bereits maskiert
     */
    public static function wort(string $schluessel, string $standard = ''): string
    {
        $wert = $GLOBALS['TL_LANG']['ctv'][$schluessel] ?? $standard;

        return self::esc(\is_string($wert) ? $wert : $standard);
    }

    /**
     * Gibt den Inhalt einer Tabellenzelle für eine wählbare Spalte aus.
     *
     * Alle Spalten von Teilnehmerliste und Rangliste laufen hier zusammen,
     * damit die Templates nicht für jede Spalte eine eigene Zeile brauchen —
     * sonst müsste jede neue Spalte an mehreren Stellen nachgetragen werden.
     * Das Ergebnis ist bereits maskiert und kann unmittelbar ausgegeben
     * werden.
     *
     * @param array<string,mixed> $zeile      Ein Teilnehmersatz
     * @param string              $spalte     Schlüssel der Spalte
     * @param bool                $ohneTitel  Lässt den Titel vor dem Namen weg.
     *                                        Wahr, wenn der Titel eine eigene
     *                                        Spalte hat — sonst stünde er zweimal da
     *
     * @return string Der Zelleninhalt, maskiert; leer, wenn nichts vorliegt
     */
    public static function zelle(array $zeile, string $spalte, bool $ohneTitel = false): string
    {
        return match ($spalte) {
            'nr' => self::zahl($zeile['tnr'] ?? 0),
            'platz' => self::zahl($zeile['platz'] ?? 0),
            'brett' => self::zahl($zeile['brett'] ?? 0),
            'name' => self::esc($ohneTitel ? trim((string) ($zeile['name'] ?? '')) : self::name($zeile)),
            'titel' => self::esc($zeile['titel'] ?? ''),
            'elo' => self::zahl($zeile['elo'] ?? 0),
            'dwz' => self::zahl($zeile['dwz'] ?? 0),
            'twz' => self::zahl($zeile['twz'] ?? 0),
            'verein' => self::esc($zeile['mannschaft'] ?? ''),
            'land' => self::flagge($zeile['land'] ?? ''),
            'gruppe' => self::esc($zeile['gruppe'] ?? ''),
            'geburtsjahr' => self::geburtsjahr($zeile),
            'fideId' => self::zahl($zeile['fideId'] ?? 0),
            'bilanz' => self::bilanz($zeile),
            'partien' => self::zahl($zeile['partien'] ?? 0),
            'punkte' => self::kommazahl($zeile['punkte'] ?? 0),
            'feinwertung1' => self::kommazahl($zeile['feinwertung1'] ?? 0),
            'feinwertung2' => self::kommazahl($zeile['feinwertung2'] ?? 0),
            default => '',
        };
    }

    /**
     * Schreibt eine Punktzahl mit Komma und einer Nachkommastelle.
     *
     * In einer Tabellenspalte, in der die Zahlen untereinander stehen und
     * verglichen werden, ist „7,5" leichter zu lesen als „7½" — und „7,0"
     * leichter mit „7,5" zu vergleichen als „7". So schreibt es auch
     * chess-results. Das ½ bleibt dort, wo eine Zahl für sich steht: in
     * Ergebnissen und Kreuztabellen.
     *
     * Eine Nachkommastelle steht immer, eine zweite nur, wenn sie gebraucht
     * wird: Sonneborn-Berger rechnet in Vierteln, und aus 196,25 dürfen nicht
     * 196,3 werden.
     *
     * @param float|int|string|null $wert Die Punktzahl
     *
     * @return string Die Zahl als Text, oder leer wenn keine vorliegt
     */
    public static function kommazahl(mixed $wert): string
    {
        if (null === $wert || '' === $wert) {
            return '';
        }

        return (string) preg_replace('/(,\d)0$/', '$1', number_format((float) $wert, 2, ',', ''));
    }

    /**
     * Gibt eine Föderation als Flagge aus.
     *
     * Die Flagge entsteht aus zwei Regionalbuchstaben — dieselbe Schreibweise,
     * die auch Smartphones benutzen; eine Bilddatei braucht es dafür nicht.
     * Nötig ist der Umweg über die zweibuchstabigen Landeskennungen, denn
     * Turnierdateien führen die dreibuchstabigen des Weltschachbundes.
     *
     * Der Ländercode bleibt als Titel am Feld: Nicht jedes System stellt
     * Flaggen dar, und wer eine Flagge nicht zuordnen kann, liest ihn ab.
     * Fehlt die Zuordnung — „FID" für den Weltschachbund etwa —, steht dort
     * weiterhin der Code selbst.
     *
     * @param mixed $land Die Föderation, dreibuchstabig
     *
     * @return string Die Flagge als HTML, oder der unveränderte Code
     */
    public static function flagge(mixed $land): string
    {
        $code = strtoupper(trim((string) $land));

        if ('' === $code) {
            return '';
        }

        $zwei = self::LAENDER[$code] ?? null;

        if (null === $zwei) {
            return self::esc($code);
        }

        $flagge = mb_chr(0x1F1E6 + \ord($zwei[0]) - \ord('A'), 'UTF-8')
            .mb_chr(0x1F1E6 + \ord($zwei[1]) - \ord('A'), 'UTF-8');

        return sprintf('<span class="ctv-flagge" title="%s">%s</span>', self::esc($code), $flagge);
    }

    /**
     * Liefert den ungeformten Wert einer Spalte.
     *
     * Gebraucht wird er, um zu entscheiden, ob eine Spalte überhaupt etwas zu
     * zeigen hat. Die geformte Zelle taugt dafür nicht: Eine Feinwertung von
     * null erscheint dort als „0" und sähe belegt aus.
     *
     * @param array<string,mixed> $zeile  Ein Teilnehmersatz
     * @param string              $spalte Schlüssel der Spalte
     *
     * @return mixed Der Wert aus dem Satz; bei zusammengesetzten Spalten der
     *               Wert, an dem sich die Belegung entscheidet
     */
    public static function rohwert(array $zeile, string $spalte): mixed
    {
        return match ($spalte) {
            'nr' => $zeile['tnr'] ?? null,
            'verein' => $zeile['mannschaft'] ?? null,
            'geburtsjahr' => self::geburtsjahr($zeile),
            'name' => self::name($zeile),
            'bilanz' => ($zeile['siege'] ?? 0) + ($zeile['remis'] ?? 0) + ($zeile['niederlagen'] ?? 0),
            default => $zeile[$spalte] ?? null,
        };
    }

    /**
     * Liefert den Wert, nach dem eine Zelle zu sortieren ist.
     *
     * Gebraucht wird er dort, wo die Anzeige nicht sortierbar ist: „3½" und
     * eine Bilanz wie „5/2/1" ordnet der Browser als Text falsch. Die Zahl
     * geht als `data-wert` mit ins Markup, das Skript sortiert danach.
     *
     * @param array<string,mixed> $zeile  Ein Teilnehmersatz
     * @param string              $spalte Schlüssel der Spalte
     *
     * @return string Der Sortierwert, oder eine leere Zeichenkette wenn die
     *                Anzeige selbst schon richtig sortiert
     */
    public static function sortierwert(array $zeile, string $spalte): string
    {
        return match ($spalte) {
            'punkte', 'feinwertung1', 'feinwertung2' => (string) (float) ($zeile[$spalte] ?? 0),
            'bilanz' => (string) (int) ($zeile['siege'] ?? 0),
            // In der Zelle steht eine Flagge; nach ihr zu ordnen ergäbe die
            // Reihenfolge der Unicode-Zeichen. Geordnet wird nach dem Code.
            'land' => strtoupper(trim((string) ($zeile['land'] ?? ''))),
            default => '',
        };
    }

    /**
     * Ermittelt das Geburtsjahr eines Teilnehmers.
     *
     * Die Formate liefern es verschieden: Swiss-Manager als Zahl, der
     * SWT-Leser als Datumstext. Gesucht wird deshalb die erste vierstellige
     * Jahreszahl; steht dort nichts Brauchbares, bleibt die Zelle leer.
     *
     * @param array<string,mixed> $zeile Ein Teilnehmersatz
     *
     * @return string Das Jahr, oder eine leere Zeichenkette
     */
    public static function geburtsjahr(array $zeile): string
    {
        $jahr = (int) ($zeile['geburtsjahr'] ?? 0);

        if ($jahr < 1000 && preg_match('/(1[89]\d\d|20\d\d)/', (string) ($zeile['geburtsdatum'] ?? ''), $treffer)) {
            $jahr = (int) $treffer[1];
        }

        return $jahr >= 1000 ? (string) $jahr : '';
    }
}
