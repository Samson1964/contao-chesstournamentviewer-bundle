# Contao Turnier-Betrachter

Gibt die Datei einer Schachturnierverwaltung im Frontend aus. Das
Inhaltselement liest die Datei bei jedem Seitenaufruf und stellt daraus die
gewünschten Listen zusammen — von den Turnierdaten über Rangliste und
Kreuztabelle bis zu den Wettkämpfen eines Mannschaftsturniers. Eine Datenbank
für die Turnierdaten braucht es nicht: Die Turnierdatei liegt in der
Dateiverwaltung, und mehr ist nicht zu pflegen.

Erstes unterstütztes Format ist **SWT** aus dem Programm *Swiss-Chess*.
Weitere Formate lassen sich ergänzen, ohne dass Listen oder Templates
angefasst werden müssen.

Läuft unter **Contao 4.13** und **Contao 5**, mit PHP ab 8.1.

## Installation

Über den Contao Manager nach `schachbulle/contao-chesstournamentviewer-bundle`
suchen, oder auf der Kommandozeile:

```bash
composer require schachbulle/contao-chesstournamentviewer-bundle
```

## Benutzung

Im Artikel ein Inhaltselement der Gruppe **Schach-Elemente** vom Typ
**Turnier-Betrachter** anlegen. Dort stehen diese Einstellungen:

| Feld | Bedeutung |
| --- | --- |
| **Turnierdatei** | Die Datei aus der Dateiverwaltung. Angeboten werden nur Dateien mit einer Endung, die ein registriertes Format kennt. |
| **Format der Turnierdatei** | „Automatisch erkennen" ist der Regelfall; die Formate erkennen ihre Dateien am Inhalt, nicht an der Endung. Ein Format von Hand zu wählen hilft nur, wenn eine Datei nicht erkannt wird, obwohl sie dazugehört. |
| **Auszugebende Listen** | Mehrfachauswahl. Ab zwei Listen erscheinen Reiter. |
| **Hinweise zu den Zahlen anzeigen** | Zeigt über den Tabellen, wenn die gespeicherten Zahlen der Datei mit den eingetragenen Ergebnissen nicht zusammengehen. |
| **Spieler mit ausgeben** | Nur bei Mannschaftsturnieren: Aufstellungen in der Mannschaftsliste und Einzelpartien in den Wettkämpfen. |
| **Kreuztabelle der Mannschaften kürzen** | Nur bei Mannschaftsturnieren: In jeder Zelle stehen nur die eigenen Brettpunkte statt beider Seiten. |

**Die Maske richtet sich nach der Datei.** Ist die gewählte Datei ein
Einzelturnier, verschwinden die Feldgruppe „Mannschaftsturniere" und die
Mannschaftslisten aus der Auswahl; bei einem Mannschaftsturnier stehen sie
bereit. Die Anpassung greift nach dem Speichern, denn vorher steht die Datei
nicht im Datensatz.

Steht die Dateiendung der Turnierformate nicht unter **Einstellungen →
Erlaubte Dateitypen**, warnt das Inhaltselement: Ohne den Eintrag lässt sich
gar keine Turnierdatei hochladen, und die Dateiauswahl bliebe ohne
erkennbaren Grund leer.

### Die Listen

| Liste | Inhalt |
| --- | --- |
| Turnierdaten | Name, Ort, Zeitraum, Turnierform, Runden, Feinwertungen |
| Teilnehmer | Startliste mit Elo, DWZ, TWZ und Verein; bei Mannschaftsturnieren nach Mannschaften gegliedert |
| Rangliste | Endstand mit Bilanz, Punkten und Feinwertungen; bei Mannschaftsturnieren nach Mannschaften gegliedert |
| Kreuztabelle | Jeder gegen jeden, in Ranglistenreihenfolge |
| Fortschrittstabelle | Je Runde Ergebnis, Farbe und Gegner, darunter der Punktestand |
| Fortschritt ohne Punktestand | Dieselbe Tabelle, nur der Verlauf |
| Paarungen | Auslosung je Runde, ohne Ergebnisse; bei Mannschaftsturnieren nach Wettkämpfen gegliedert |
| Ergebnisse | Dieselben Partien mit Ergebnis |
| Mannschaften | Mannschaftsliste, auf Wunsch mit Aufstellung |
| Mannschaftstabelle | Wettkämpfe, Bilanz, Mannschafts- und Brettpunkte |
| Wettkämpfe | Die Begegnungen je Runde mit Wertungsschnitt, auf Wunsch mit Einzelpartien |
| Kreuztabelle der Mannschaften | Die Wettkampfergebnisse als Kreuztabelle |

### Reiternavigation

Ab zwei Listen entsteht eine Reiterleiste; mit Pfeiltasten lässt sich zwischen
den Reitern wechseln. **Ohne JavaScript stehen alle Listen untereinander** und
bleiben vollständig lesbar — der Server liefert sie alle sichtbar aus, und
erst das Skript blendet aus, was hinter den Reitern liegt. Bei einer einzigen
Liste entfällt die Leiste.

## Anpassen der Ausgabe

Alle Listen sind eigene Contao-Templates und lassen sich einzeln
überschreiben:

| Template | Liste |
| --- | --- |
| `ce_chesstournamentviewer` | Rahmen mit Reiterleiste und Hinweisen |
| `ctv_turnierdaten` | Turnierdaten |
| `ctv_teilnehmer` | Teilnehmer |
| `ctv_rangliste` | Rangliste |
| `ctv_kreuztabelle` | Kreuztabelle |
| `ctv_fortschritt` | Fortschrittstabelle — und über einen Verweis auch die Fassung ohne Punktestand |
| `ctv_fortschrittohne` | Fortschritt ohne Punktestand |
| `ctv_paarungen` | Paarungen — und über einen Verweis auch die Ergebnisse |
| `ctv_ergebnisse` | Ergebnisse |
| `ctv_partiezeile` | Eine Partiezeile — von Paarungen, Ergebnissen und Wettkämpfen benutzt |
| `ctv_ranglistenzeile` | Eine Ranglistenzeile — durchgehend wie in den Mannschaftsgruppen |
| `ctv_mannschaften` | Mannschaften |
| `ctv_mannschaftsrangliste` | Mannschaftstabelle |
| `ctv_mannschaftspaarungen` | Wettkämpfe |
| `ctv_mannschaftskreuztabelle` | Kreuztabelle der Mannschaften |

Für die Formatierung steht in den Templates die Klasse
`Schachbulle\ContaoChesstournamentviewerBundle\Liste\Ausgabe` bereit:
`punkte()` schreibt halbe Punkte als ½, `esc()` maskiert Werte aus der
Turnierdatei, `spalte()` holt eine Spaltenbeschriftung aus der Sprachdatei.

Farben und Abstände kommen aus `betrachter.css`. Alle Farben stehen als
CSS-Eigenschaften am Element `.ctv` und lassen sich im eigenen Theme
überschreiben, ohne die Datei zu ersetzen:

```css
.ctv {
    --ctv-grund: #fff;            /* Grund des aktiven Reiters */
    --ctv-text: #1c1c1c;          /* Schrift in Reitern und Tabellen */
    --ctv-linie: #d5d5d5;         /* Tabellen- und Reiterlinien */
    --ctv-kopf: #eceef0;          /* Tabellenkopf, Blindfelder */
    --ctv-wechsel: #f6f7f8;       /* jede zweite Zeile, ruhende Reiter */
    --ctv-gedaempft: #6b6b6b;     /* Nebenangaben wie Farbe und Punktestand */
    --ctv-akzent: #1c5a8c;        /* Balken über dem aktiven Reiter */
    --ctv-blind: #c9ced3;         /* Blindfelder der Kreuztabelle */
    --ctv-blind-figur: #6b7379;   /* der König darin */
}
```

Auf einer dunklen Seite sind mindestens `--ctv-grund`, `--ctv-text` und
`--ctv-wechsel` zu setzen: Die Reiter bringen ihre Farben ausdrücklich mit,
statt sie vom Theme zu erben — sonst stünde je nach Theme dunkle Schrift auf
dunklem Grund.

## Was der Betrachter über die Zahlen sagt

Über den Tabellen können Hinweise stehen. Sie sind kein Fehler, sondern
Selbstkontrolle: Die Punktzahlen stehen in einer SWT-Datei zweimal — einmal
auf den Karteikarten der Teilnehmer, einmal als Ergebnisse in den Paarungen.
Gehen beide auseinander, sagt der Betrachter das, statt es zu verschweigen.
Häufigster Fall ist eine Rangliste, die noch nicht neu berechnet wurde,
nachdem die letzte Runde eingegeben war.

### Mannschaftswertung

Der Ausgang eines Mannschaftskampfes steht **nicht** in der SWT-Datei: Die
Paarungssätze der Mannschaften führen Spielort, Gegner und Tischnummer, aber
kein Ergebnis. Swiss-Chess rechnet den Kampf jedes Mal aus den Einzelpartien
zurück, und der SWT-Leser dieses Bundles tut dasselbe — Brettpunkte aus den
Ergebnissen aller Spieler einer Mannschaft in einer Runde, Mannschaftspunkte
aus dem Vergleich mit dem Gegner, mit zwei oder drei Punkten für den Sieg je
nach Turniereinstellung.

Der Nachweis gegen die Ausgaben von Swiss-Chess selbst:

| Vergleich | Umfang | Abweichungen |
| --- | ---: | ---: |
| Mannschaftstabelle Blitz-MM 2012 (Platz, S/R/N, MP, BP) | 28 Mannschaften | 0 |
| Mannschaftstabelle Betriebs-MM 2012 (Platz, Name, S/R/N, MP, BP) | 38 Mannschaften | 0 |
| Kreuztabelle der Mannschaften Blitz-MM 2012 | 784 Felder | 0 |

**Freilose bleiben unbewertet.** Zu einer spielfreien Runde steht in der Datei
nichts als das Fehlen eines Gegners. Ob die Turnierleitung dafür einen
kampflosen Sieg gutgeschrieben hat, ist daraus nicht zu erkennen — manche tun
es, manche nicht. Weicht die hier gezeigte Tabelle deshalb von der in der
Datei gespeicherten ab, steht darüber ein Hinweis.

Die Spalte **Ø TWZ** ist eine eigene Zahl und nicht die von Swiss-Chess:
Gemittelt wird über alle gemeldeten Spieler einer Mannschaft, also über genau
den Kreis, den die Mannschaftsliste mit eingeschalteter Aufstellung darunter
zeigt. Nach welcher Regel Swiss-Chess seine TWZ-Spalte bildet, ließ sich an
den vorliegenden Ausgaben nicht bestimmen; die Einzelheiten stehen im
Klassenkommentar von `Mannschaftswertung::wertungsschnitt()`.

## Weitere Turnierformate

Ein neues Format braucht eine Klasse, die
`Schachbulle\ContaoChesstournamentviewerBundle\Format\TurnierFormatInterface`
umsetzt, und einen Eintrag in der `services.yaml` des eigenen Bundles. Den
Dienst-Tag setzt die Autokonfiguration; Verzeichnis, Dateiauswahl und
Formatauswahl im Backend füllen sich daraufhin von allein.

Die Klasse liefert ein `Turnier` — ein reines Datenobjekt mit Teilnehmern,
Mannschaften, Paarungen, Rangliste und Runden. Zwei Festlegungen sind dabei
bindend, weil die Listen darauf bauen: Teilnehmer sind nach Teilnehmernummer
indiziert, Mannschaften lückenlos ab 1 nach Mannschaftsnummer.

## Prüfstand

Im Verzeichnis `tests/` liegen Testfälle, die ohne Contao und ohne
Turnierdateien auskommen:

```bash
vendor/bin/phpunit
```

Geprüft werden die Rückrechnung der Mannschaftswertung, die
Fortschrittstabelle, die Formaterkennung und das Listenverzeichnis.

## Lizenz

LGPL-3.0-or-later. Die Klasse `SwtFile` ist vom SWT-Parser des
Zugzwang-Projekts abgeleitet (<http://www.zugzwang.org/projects/swtparser>),
Copyright © 2005, 2012 Gustaf Mossakowski, Jacob Roggon, Falco Nogatz, und
steht unter derselben Lizenz.
