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
**Turnier-Betrachter** anlegen. Dort stehen vier Einstellungen:

| Feld | Bedeutung |
| --- | --- |
| **Turnierdatei** | Die Datei aus der Dateiverwaltung. Angeboten werden nur Dateien mit einer Endung, die ein registriertes Format kennt. |
| **Format der Turnierdatei** | „Automatisch erkennen" ist der Regelfall; die Formate erkennen ihre Dateien am Inhalt, nicht an der Endung. Ein Format von Hand zu wählen hilft nur, wenn eine Datei nicht erkannt wird, obwohl sie dazugehört. |
| **Auszugebende Listen** | Mehrfachauswahl. Ab zwei Listen erscheinen Reiter. |
| **Spieler mit ausgeben** | Betrifft nur Mannschaftsturniere: Aufstellungen in der Mannschaftsliste und Einzelpartien in den Wettkämpfen. |

Listen für Mannschaftsturniere werden bei einem Einzelturnier stillschweigend
übergangen. Dieselbe Auswahl taugt damit für beide Turnierarten, und man muss
das Inhaltselement nicht ändern, wenn im nächsten Jahr ein Mannschaftsturnier
an derselben Stelle steht.

### Die Listen

| Liste | Inhalt |
| --- | --- |
| Turnierdaten | Name, Ort, Zeitraum, Turnierform, Runden, Feinwertungen |
| Teilnehmer | Startliste mit Elo, DWZ, TWZ und Verein |
| Rangliste | Endstand mit Bilanz, Punkten und Feinwertungen |
| Kreuztabelle | Jeder gegen jeden, in Ranglistenreihenfolge |
| Fortschrittstabelle | Je Runde Gegner, Farbe, Ergebnis und Punktestand |
| Paarungen | Auslosung je Runde, ohne Ergebnisse |
| Ergebnisse | Dieselben Partien mit Ergebnis |
| Mannschaften | Mannschaftsliste, auf Wunsch mit Aufstellung |
| Mannschaftstabelle | Wettkämpfe, Bilanz, Mannschafts- und Brettpunkte |
| Wettkämpfe | Die Begegnungen je Runde, auf Wunsch mit Einzelpartien |
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
| `ctv_fortschritt` | Fortschrittstabelle |
| `ctv_paarungen` | Paarungen — und über einen Verweis auch die Ergebnisse |
| `ctv_ergebnisse` | Ergebnisse |
| `ctv_mannschaften` | Mannschaften |
| `ctv_mannschaftsrangliste` | Mannschaftstabelle |
| `ctv_mannschaftspaarungen` | Wettkämpfe |
| `ctv_mannschaftskreuztabelle` | Kreuztabelle der Mannschaften |

Für die Formatierung steht in den Templates die Klasse
`Schachbulle\ContaoChesstournamentviewerBundle\Liste\Ausgabe` bereit:
`punkte()` schreibt halbe Punkte als ½, `esc()` maskiert Werte aus der
Turnierdatei, `spalte()` holt eine Spaltenbeschriftung aus der Sprachdatei.

Farben und Abstände kommen aus `betrachter.css`. Die Farben stehen als
CSS-Eigenschaften am Element `.ctv` und lassen sich im eigenen Theme
überschreiben, ohne die Datei zu ersetzen:

```css
.ctv {
    --ctv-linie: #c8d6e5;
    --ctv-kopf: #eef3f8;
}
```

## Was der Betrachter über die Zahlen sagt

Über den Tabellen können Hinweise stehen. Sie sind kein Fehler, sondern
Selbstkontrolle: Die Punktzahlen stehen in einer SWT-Datei zweimal — einmal
auf den Karteikarten der Teilnehmer, einmal als Ergebnisse in den Paarungen.
Gehen beide auseinander, sagt der Betrachter das, statt es zu verschweigen.
Häufigster Fall ist eine Rangliste, die noch nicht neu berechnet wurde,
nachdem die letzte Runde eingegeben war.

### Mannschaftswertung

Die Mannschaftsangaben der SWT-Dateien sind **nicht** die Grundlage der
Mannschaftslisten. Der Mannschaftsbereich der Datei enthält in allen
geprüften Turnieren kein einziges Ergebnis, und in Dateien vor Fassung 800
stehen auf den Mannschaftskarteikarten unbrauchbare Zahlen — Brettpunkte wie
14137 bei sechs Brettern und sieben Runden.

Wettkämpfe, Brett- und Mannschaftspunkte werden deshalb aus den
**Einzelpartien** zurückgerechnet, die über den gesamten Bestand geprüft sind.
Zwei Regeln gehören dazu:

1. Die Mannschaftsnummer ist die Position in der Mannschaftsliste, nicht das
   Nummernfeld der Karteikarte — dieses liefert 999 plus Position.
2. Eine Mannschaft ohne Wettkampf in einer gespielten Runde hat ein Freilos
   und bekommt die volle Brettzahl sowie zwei Mannschaftspunkte.

Der Nachweis: Verglichen mit den gespeicherten Werten der Dateien, in denen
diese brauchbar sind, ergibt die Rückrechnung **keine einzige Abweichung**.

| Datei | Fassung | Mannschaften | Abweichungen |
| --- | ---: | ---: | ---: |
| `DBMM_2012.SWT` | 882 | 38 | 0 |
| `FVS_BMM_2012_13.SWT` | 882 | 14 | 0 |
| `BSMM2017.SWT` | 897 | 23 | 0 |

Gewertet wird mit zwei Punkten für den gewonnenen und einem für den
unentschiedenen Wettkampf. Ligen, die anders werten, zeigt die Datei nicht an;
weicht die Rückrechnung von gespeicherten Werten ab, erscheint darüber ein
Hinweis.

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
