# Swiss-Manager von Heinz Herzog

## Native Dateiendungen des Swiss-Managers

Beim Speichern eines Turniers vergibt das Programm automatisch eine der folgenden Endungen:

.TUNx: Für Einzelturniere nach dem Schweizer System (Standardfall)
.TURx: Für Einzelturniere als Rundenturnier (Jeder gegen Jeden / Round Robin)
.TUTx: Für Mannschafts-Rundenturniere
.TUMx: Für Mannschaftsturniere nach dem Schweizer System

Hinweis: Das kleine „x“ am Ende steht für die moderne Unicode-Version des Programms (z. B. .tunx), während ältere ASCII-Versionen des Programms die Endungen ohne das „x“ nutzten (z. B. .tun).

Siehe auch https://swiss-manager.at/downloadhist.aspx?lan=0

## Aufbau des Dateiformats

Erarbeitet an sieben Turnierdateien und gegen die Ausgaben auf
chess-results.com geprüft. Die Klasse `SwissManagerFile` setzt das um.

### Abschnitte

Die Datei besteht aus Abschnitten. Jeder beginnt mit einer vier Byte langen
Marke `KK FF 89 44`; `KK` benennt den Abschnitt:

| Marke | Inhalt |
| --- | --- |
| `93` | Dateikopf, ab Adresse `0x6C` eine Folge von 26 Zeichenketten |
| `95` | Einstellungen; die ersten zwei Byte sind die Rundenzahl |
| `A3` | Termine der Runden |
| `A5` | Teilnehmer |
| `B3` | Partien, in Rundenfolge |
| `B5` | Mannschaften (nur bei Mannschaftsturnieren) |
| `C3` | Wettkämpfe (nur bei Mannschaftsturnieren) |
| `D3` | Verzeichnis einiger Abschnittsadressen, je vier Byte |
| `E3` | Dateiende |

Ein Abschnitt reicht bis zur nächsten Marke. Fehlt ein Abschnitt — bei einem
Einzelturnier `B5` und `C3` —, folgt die nächste Marke unmittelbar.

### Zeichenketten

Zeichenzahl in einem 16-Bit-Wort, dann ebenso viele Zeichen in **UTF-16LE**.
Leere Felder stehen als Länge null da und zählen mit.

**Die Abschnitte sind nicht auf gerade Adressen ausgerichtet.** Wer die Datei
wortweise durchsucht, findet die Teilnehmer nicht — sie beginnen häufig auf
einer ungeraden Adresse.

### Dateikopf (`93`)

Ab `0x6C` stehen 26 Zeichenketten:

| Nr. | Inhalt |
| --- | --- |
| 0 | Turniername |
| 1 | Untertitel |
| 2 | Bemerkung |
| 3 | Schiedsrichter |
| 4 | Veranstalter |
| 5 | Spiellokal |
| 6 | weitere Schiedsrichter |
| 7, 8 | Pfade zu PGN- und Stellungsdateien |
| 9 | Turniername, wiederholt |
| 10 | Ort |
| 11 | interne Kennung |
| 13 | Altersgruppen |
| 14 | Bedenkzeit |
| 20 | Föderation |
| 21 | Hauptschiedsrichter |
| 22 | stellvertretender Schiedsrichter |
| 23 | E-Mail |
| 24 | Internetseite |
| 25 | Föderation, wiederholt |

Die ersten `0x6C` Byte enthalten Kennungen und Prüfsummen; sie werden nicht
ausgewertet.

### Teilnehmerkarte (`A5`)

**33 Zeichenketten, dann 104 Byte Zahlen.** Die Startnummer ergibt sich aus
der Reihenfolge; auf sie beziehen sich die Partien.

Zeichenketten: 0 Nachname, 1 Vorname, 3 Kurzname („A. Erster“), 4 Titel,
5 nationale Kennung, 9 Verein, 10 Föderation, 12 Gruppe, 14 Geschlecht,
17 Föderation.

Zahlenblock, Versatz ab seinem Anfang:

| Versatz | Breite | Inhalt |
| --- | --- | --- |
| +2 | 16 Bit | internationale Wertungszahl (Elo) |
| +4 | 16 Bit | nationale Wertungszahl |
| +8 | 32 Bit | Geburtsdatum als `JJJJMMTT`; Tag und Monat oft null |
| +18 | 32 Bit | FIDE-Kennung |
| +22 | 16 Bit | Mannschaftsnummer |
| +24 | 16 Bit | Brett innerhalb der Mannschaft |

**Punkte, Platz und Feinwertungen stehen nicht in der Datei.** Swiss-Manager
bildet sie bei jeder Anzeige neu; der Betrachter tut dasselbe.

### Partie (`B3`)

**21 Byte**, in Rundenfolge, innerhalb einer Runde in Brettfolge. Eine
Rundennummer führt der Satz nicht.

| Versatz | Breite | Inhalt |
| --- | --- | --- |
| +0 | 16 Bit | Weiß, Startnummer |
| +2 | 16 Bit | Schwarz, Startnummer |
| +4 | 16 Bit | Ergebnisschlüssel |
| +6 | 15 Byte | in allen geprüften Dateien null |

Ergebnisschlüssel: `1` = 1:0, `2` = ½:½, `3` = 0:1, `4` = kampfloser Sieg für
Weiß, `5` = kampfloser Sieg für Schwarz, `9` = spielfrei mit einem Punkt.

Sonderwerte auf der Gegenseite: `0` bei kampflosen Partien (dort saß niemand),
`0xFFFF` für „spielfrei“, `0xFFFE` für „nicht ausgelost“. Beide stehen am Ende
ihrer Runde, hinter den gespielten Partien.

**Die Rundengrenze ist nicht gespeichert.** Sie wird daran erkannt, dass eine
Startnummer zum zweiten Mal auftaucht — in einer Runde spielt niemand zweimal.
Sonderwerte zählen dabei nicht mit.

### Mannschaftskarte (`B5`)

**27 Zeichenketten, dann 52 Byte Zahlen.** Zeichenketten: 0 Name, 1 Kurzname,
2 Mannschaftsführer, 3 Föderation. Die Startnummer ergibt sich wieder aus der
Reihenfolge; die Aufstellung steht in den Teilnehmerkarten.

### Wettkampf (`C3`)

**15 Byte**: `+0` Heimmannschaft, `+2` Gastmannschaft, Rest null. Ein Ergebnis
führt der Satz nicht — Brett- und Mannschaftspunkte ergeben sich aus den
Einzelpartien.

### Feinwertungen (`95`), teilweise entschlüsselt

Im Abschnitt `95` steht, **welche** Feinwertungen das Turnier führt — die
Werte selbst stehen nirgends, Swiss-Manager rechnet sie bei jeder Anzeige neu.

| Versatz ab Abschnittsanfang | Breite | Inhalt |
| --- | --- | --- |
| +0 | 16 Bit | Rundenzahl |
| +27 | 16 Bit | Zahl der Feinwertungen |
| +29, +31, … | je 16 Bit | die Schlüssel, in der Reihenfolge der Wertung |

Belegt an vier Turnieren, deren Rangkriterien auf chess-results.com stehen:

| Schlüssel | Bedeutung |
| --- | --- |
| `0x09` | Fidewertung mit Streichresultaten |
| `0x0B` | Direkte Begegnung |
| `0x25` | Buchholz (variabel) |
| `0x34` | Sonneborn-Berger-Wertung variabel |
| `0x44` | Zahl der Siege |
| `0x54` | Buchholzwertung Variabel (2023), Spielpunkte |
| `0x55` | Sonneborn-Berger-Wertung Variabel (2023), Spielpunkte |

**Zum Rechnen reicht das nicht.** Zwei Beobachtungen stehen dem entgegen:

* Bei der Offenen Deutschen Blindenmeisterschaft (tnr1148367) tragen die
  Wertungen 2 und 3 beide den Schlüssel `0x54` und unterscheiden sich nur
  durch „Cut1". **Die Streichung steht also woanders**, an einer Stelle, die
  noch nicht gefunden ist.
* Ebendort tragen die Wertungen 4 und 5 beide `0x44`, heißen aber „Zahl der
  Partien mit Schwarz" und „Zahl der Siege". Ein Schlüssel allein bestimmt die
  Wertung demnach nicht; es fehlt mindestens ein Merkmal.

Solange diese Merkmale nicht gefunden sind, bleiben die Feinwertungsspalten
bei Swiss-Manager leer: Eine gerechnete Zahl, die neben der amtlichen Tabelle
steht und ihr widerspricht, wäre schlechter als keine. Aus demselben Grund
teilen sich Punktgleiche den Platz.

Wer weitersucht, findet den Weg dorthin so: Die Ordner unter
`F:\Claude\Swiss-Manager-Turnierdateien auf Chess-Results\` enthalten je eine
Verknüpfung zur zugehörigen Seite; unter der Rangliste steht dort die Legende
mit den Rangkriterien im Klartext. Damit lässt sich jede Vermutung über die
fehlenden Merkmale sofort an achtundsiebzig Turnieren prüfen.

## Was noch offen ist
* **Mannschaftspunkte.** Die Regel steht nicht in der Datei; angesetzt sind
  zwei für den Sieg und einer für das Unentschieden. Für die
  Frauen-Mannschaftsmeisterschaft 2025 trifft das die Tabelle von
  chess-results exakt.
* **Ungeklärte Zahlenfelder** in der Teilnehmerkarte, unter anderem bei +6,
  +12, +16 und +50. Für die Ausgabe werden sie nicht gebraucht.

## Geprüft gegen chess-results.com

| Datei | Prüfung |
| --- | --- |
| `oly_u7_2026` (tnr1349693) | 12 Teilnehmer, 9 Runden, 54 Partien, alle Punktzahlen der Endtabelle |
| `iodfem` (tnr1386786) | 22 Teilnehmer, 9 Runden, spielfreie Runde und „nicht ausgelost“, Punktzahlen |
| `dsem_2025` (tnr1195993) | 36 Teilnehmer, 9 Runden zu je 18 Partien |
| `dfem_blitz2026` (tnr1373580) | Rundenturnier, 24 Teilnehmer, 23 Runden, Punktzahlen |
| `dfmm_lv2025` (tnr1139815) | Mannschaftsturnier, 14 Mannschaften, 5 Runden, Mannschaftspunkte der Endtabelle |
| `dssam_gesamtliste` (tnr1488770) | 205 Teilnehmer der Meldeliste |
| `olympiad2026open` (tnr1469895) | 1031 Teilnehmer, 208 Mannschaften |
