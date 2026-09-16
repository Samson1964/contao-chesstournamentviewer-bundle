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

Es gibt drei Inhaltselemente in der Gruppe **Schach-Elemente**:

| Element | Zweck |
| --- | --- |
| **Schachturnier-Betrachter – Turnierausgabe** | Gibt **eine** Liste aus einer Turnierdatei aus |
| **Schachturnier-Betrachter – Umschlag Anfang** | Öffnet einen Umschlag; die folgenden Ausgaben erscheinen als Reiter |
| **Schachturnier-Betrachter – Umschlag Ende** | Schließt den Umschlag |

**Ein Element gibt genau eine Liste aus.** Wer Teilnehmer, Rangliste und
Kreuztabelle nebeneinander zeigen will, legt drei Turnierausgaben an und
klammert sie mit den beiden Umschlag-Elementen ein — so, wie Contao es mit
Akkordeon und Slider hält. Im Backend rückt Contao die eingeschlossenen
Elemente ein, sodass zu sehen ist, was zusammengehört.

### Eine Turnierausgabe anlegen

Die Maske baut sich in drei Schritten auf. Das ist Absicht: Was ausgegeben
werden kann, steht in der Turnierdatei, und die kennt das Element erst, wenn
sie gewählt und gespeichert ist.

1. **Turnierdatei wählen.** Mehr steht nicht in der Maske — alles Weitere
   hinge am Inhalt der Datei und wäre zu diesem Zeitpunkt geraten. Angeboten
   werden nur Dateien mit einer Endung, die ein registriertes Format kennt.
2. **Speichern.** Jetzt kommt **Auszugebende Liste** hinzu, ein Auswahlfeld
   mit dem, was diese Datei hergibt: Bei einem Einzelturnier fehlen die
   Mannschaftslisten, bei einem Mannschaftsturnier Kreuztabelle und
   Fortschrittstabellen der Spieler, vor der ersten Runde die Kreuztabelle.
3. **Liste wählen.** Deren Einstellungen erscheinen sofort — das Auswahlfeld
   schickt die Maske ab. Welche das sind, hängt von der Liste ab:

| Liste | Einstellungen |
| --- | --- |
| Teilnehmer, Rangliste | **Spalten** — siehe [Spalten und Sortierung](#spalten-und-sortierung) |
| Rangliste, Kreuztabelle, Fortschritt, Mannschaftstabelle, Fortschrittstabelle der Mannschaften, Kreuztabelle der Mannschaften | **Stand nach Runde** und **Zeile „Stand nach Runde" ausblenden** — siehe [Zeitpunkt und Runden](#zeitpunkt-und-runden) |
| Paarungen, Ergebnisse, Wettkämpfe | **Angezeigte Runden**; bei Mannschaftsturnieren zusätzlich **Mannschaften** |
| Mannschaften, Wettkämpfe | **Spieler mit ausgeben** |
| Kreuztabelle der Mannschaften | **Kreuztabelle kürzen** |
| alle | **Stand der Turnierdatei anzeigen** und **Hinweise zu den Zahlen anzeigen** |

**Eine Einstellung wirkt nur bei den Listen, zu denen sie gehört.** Wer ein
Element von der Mannschaftstabelle nach Runde 3 auf die Mannschaftsliste
umstellt, behält den Wert zwar im Datensatz, er bleibt aber ohne Wirkung.

**Mannschaften** beschränkt Paarungen, Ergebnisse und Wettkämpfe auf die
gewählten Mannschaften — wer nur „Deutschland" wählt, sieht dessen
Wettkämpfe samt Gegnern. Runden, in denen keine gewählte Mannschaft antrat,
entfallen.

„Stand der Turnierdatei anzeigen" setzt unter die Tabelle, wann die Datei
zuletzt geändert wurde — maßgeblich ist das Änderungsdatum in der
Dateiverwaltung. Turnierleitungen laden nach jeder Runde eine neue Fassung
hoch; damit sagt die Zeile, wie aktuell die Zahlen sind.

Das Feld **Format der Turnierdatei** steht ab Schritt 2 bereit. „Automatisch
erkennen" ist der Regelfall; die Formate erkennen ihre Dateien am Inhalt,
nicht an der Endung. Ein Format von Hand zu wählen hilft nur, wenn eine Datei
nicht erkannt wird, obwohl sie dazugehört.

Steht die Dateiendung der Turnierformate nicht unter **Einstellungen →
Erlaubte Dateitypen**, warnt das Inhaltselement: Ohne den Eintrag lässt sich
gar keine Turnierdatei hochladen, und die Dateiauswahl bliebe ohne
erkennbaren Grund leer.

### Mehrere Ausgaben als Reiter

Zwischen **Umschlag Anfang** und **Umschlag Ende** gehören beliebig viele
Turnierausgaben. Sie müssen nicht dieselbe Turnierdatei verwenden — ein
Umschlag kann ebenso gut Vorrunde und Endrunde nebeneinanderstellen.

Die Beschriftung einer Reiterlasche ist die **Überschrift** der
Turnierausgabe, und wenn dort nichts steht, der Name der Liste. Ein eigenes
Feld dafür gibt es nicht.

**Die Reiterleiste baut das Skript**, nicht der Server: Das öffnende Element
weiß beim Ausliefern nicht, was nach ihm kommt. Ohne JavaScript stehen die
Ausgaben deshalb untereinander, jede vollständig lesbar — dieselbe
Rückfallebene wie bisher.

### Eine Ausgabe als Inserttag einbinden

Nicht jede Tabelle soll ein eigenes Inhaltselement sein. Soll eine einzelne
Tabelle **mitten in einem Text** stehen — in einer Box auf der Startseite, in
einer Nachricht, in einem eigenen Template —, führt der Weg über das
Backend-Modul **Turnier-Inserttags**.

Dort wird eine Turnierausgabe genauso eingestellt wie am Inhaltselement:
Datei wählen, speichern, Liste wählen, Einstellungen setzen. Dazu kommen ein
**Titel** für die Übersicht und eine **Kennung**, die im Inserttag steht. Die
Übersicht zeigt hinter jedem Titel den fertigen Tag zum Abschreiben:

```
{{ctv::olympiade-ger-r1}}
```

Statt der Kennung geht auch die Datensatz-ID, also `{{ctv::7}}`. Die Ausgabe
ist dieselbe wie beim Inhaltselement, nur ohne dessen Überschrift und ohne
Reiter. Stilvorlage und Skript meldet der Inserttag selbst an; auf einer
Seite mit mehreren Ausgaben erscheinen sie trotzdem nur einmal.

Findet der Inserttag seine Ausgabe nicht oder lässt sich die Turnierdatei
nicht lesen, bleibt er **leer** — eine Fehlermeldung mitten im Text wäre für
den Besucher unbrauchbar. Der Grund steht im Contao-Fehlerprotokoll.

**Warum nicht die fertige Tabelle ins Textelement kopieren?** Weil Contao beim
Speichern eines Textelements alle Auszeichnungen entfernt, die nicht unter
„Erlaubte HTML-Tags" stehen — die `<span>` der Flaggen gehören dazu. Aus der
Flagge wird dann nichts. Der Inserttag erzeugt die Tabelle erst beim Ausliefern
und ist davon nicht betroffen. Außerdem wächst die Tabelle mit jeder neuen
Fassung der Turnierdatei von selbst mit.

### Die Listen

| Liste | Inhalt |
| --- | --- |
| Turnierdaten | Name, Ort, Zeitraum, Turnierform, Runden, Feinwertungen |
| Teilnehmer | Startliste mit Elo, DWZ, TWZ und Verein; bei Mannschaftsturnieren nach Mannschaften gegliedert |
| Rangliste | Endstand mit Bilanz, Punkten und Feinwertungen; bei Mannschaftsturnieren nach Mannschaften gegliedert |
| Kreuztabelle | Jeder gegen jeden, in Ranglistenreihenfolge; nur bei Einzelturnieren |
| Fortschrittstabelle | Je Runde Ergebnis, Farbe und Gegner, darunter der Punktestand; nur bei Einzelturnieren |
| Fortschritt ohne Punktestand | Dieselbe Tabelle, nur der Verlauf; nur bei Einzelturnieren |
| Paarungen | Auslosung je Runde, ohne Ergebnisse; bei Mannschaftsturnieren je Wettkampf eine Kopfzeile mit beiden Mannschaften — bei Ländern mit Flagge —, darunter die Bretter ab 1 |
| Ergebnisse | Dieselben Partien mit Ergebnis; in der Kopfzeile eines Wettkampfs steht dessen Endstand |
| Mannschaften | Mannschaftsliste, auf Wunsch mit Aufstellung |
| Mannschaftstabelle | Wettkämpfe, Bilanz, Mannschafts- und Brettpunkte |
| Fortschrittstabelle der Mannschaften | Je Runde die eigenen Brettpunkte und die Startnummer des Gegners, darunter der Stand der Mannschaftspunkte |
| Wettkämpfe | Die Begegnungen je Runde mit Wertungsschnitt, auf Wunsch mit Einzelpartien; die Bretter stehen nach Mannschaft ausgerichtet, die Farbe ist am Grund der Felder abzulesen |
| Kreuztabelle der Mannschaften | Die Wettkampfergebnisse als Kreuztabelle |

In Paarungen, Ergebnissen und Wettkämpfen eines Mannschaftsturniers stehen die
Spieler als „Titel Vorname Nachname", also „IM Marcin Molenda" statt
„IM Molenda,Marcin". Die Spalten gehören dort den Mannschaften, nicht den
Farben; wer Schwarz führte, sitzt auf dunklerem Grund.

### Spalten und Sortierung

Für **Teilnehmerliste** und **Rangliste** lässt sich einstellen, welche
Spalten erscheinen und in welcher Reihenfolge. Angeboten wird nur, was die
gewählte Datei hergibt: Ein Turnier ohne Elo-Zahlen bietet keine Elo-Spalte
an, ein Einzelturnier keine Brettspalte, eine Datei ohne Feinwertung keine
Feinwertungsspalte.

Zur Wahl stehen — je nach Datei — Startnummer, Platz, Brett, Name, Titel, Elo,
DWZ, Turnierwertungszahl, Verein, Land, Gruppe, Geburtsjahr, FIDE-Kennung,
Bilanz, Partien, Punkte und die beiden Feinwertungen. **Das Auswahlfeld lässt
sich ziehen; die Reihenfolge ist die der Ausgabe.** Ohne Auswahl erscheinen
die Vorgabespalten: bei der Teilnehmerliste Nr., Name, Wertungszahl und
Verein, bei der Rangliste Platz, Titel, Name, Wertungszahl, Verein, Punkte und
die Feinwertungen.

Zwei Spalten werden besonders gesetzt: **Die Föderation erscheint als
Flagge** — als Titel am Feld stehen Ländername und Code, etwa „Polen (POL)";
England, Schottland und Wales bekommen ihre eigene Flagge —,
und **Punkte und Feinwertungen stehen mit Komma**, also „7,5" statt „7½". Das
½ bleibt dort, wo eine Zahl für sich steht: in Ergebnislisten und
Kreuztabellen.

**Die Flaggen sind SVG-Dateien, keine Emoji.** Emoji wären der kürzere Weg,
sehen aber nicht überall gleich aus: Die Emoji-Schrift von Windows führt aus
politischen Gründen keine Länderflaggen, weshalb Chrome dort zwei Buchstaben
in Kästchen zeigte, während Firefox mit seiner eigenen Schrift die Flagge
darstellte. Das Bundle liefert deshalb die Flaggen des Pakets
[flag-icons](https://github.com/lipis/flag-icons) mit — 195 Dateien unter
`src/Resources/public/flags`, dazu die Stilvorlage `flaggen.css`. Geladen wird
nichts von fremden Servern; eine Kennung ohne Flagge, etwa „FID" für den
Weltschachbund, erscheint weiterhin als Code. Die Klassennamen sind die von
flag-icons, sodass eine Seite, die die Bibliothek ohnehin einbindet, keine
zweite Fassung braucht.

**Ländernamen werden in die Sprache der Seite übersetzt.** Nationalmannschaften
heißen in den Turnierdateien meist englisch — „Poland", „Uzbekistan 2",
„Czech Republic". Auf einer deutschen Seite erscheinen sie als „Polen",
„Usbekistan 2" und „Tschechien", in allen Listen, auch als Gegner in den
Paarungen. Übersetzt wird nur, was eindeutig ein Ländername ist: Der Name muss
— von einer angehängten Nummer abgesehen — zur Föderation der Mannschaft
passen. Vereinsnamen wie „SK Deizisau" und Verbände ohne Land wie „IBCA"
bleiben, wie sie sind. Die Namen stammen aus Symfony Intl, das Contao ohnehin
mitbringt.

Dieselbe Auswahl taugt für mehrere Turnierdateien: Was eine Datei nicht
hergibt, wird übergangen. Bleibt nichts übrig, greift die Vorgabe.

**Im Frontend ordnet ein Klick auf den Spaltenkopf die Tabelle**, ein zweiter
dreht die Richtung um. Punktestände wie „3½" und Bilanzen wie „5/2/1" werden
nach ihrem Zahlenwert geordnet, nicht als Text; leere Felder stehen in beiden
Richtungen am Ende. Ohne JavaScript steht die Tabelle in der Reihenfolge der
Turnierdatei.

Nach Mannschaften gegliederte Tabellen lassen sich nicht sortieren — die
Kopfzeilen der Mannschaften rutschten sonst zwischen die Spieler.

### Zeitpunkt und Runden

Je nach Liste steht eines von zwei Feldern in der Maske. Sie tun
Verschiedenes:

**Stand nach Runde** — bei Rangliste, Kreuztabelle, Fortschrittstabelle und
den Mannschaftstabellen. Es ist ein Schnitt durch das ganze Turnier: Alles,
was danach gespielt wurde, ist weg — nicht ausgeblendet, sondern entfernt.
Über der Ausgabe steht dann „Stand nach Runde 4", damit niemand einen
Zwischenstand für die Endtabelle hält. Nennt die Überschrift des Elements die
Runde schon, lässt sich die Zeile mit **Zeile „Stand nach Runde"
ausblenden** abschalten.

„Aktueller Stand (letzte Runde)" ist die Vorgabe. Sie nimmt die gespeicherten
Zahlen der Turnierdatei — die des jeweils letzten Standes — und rechnet
nichts nach. Genau das will man im Regelfall: Lädt jemand eine neue Fassung
der Datei hoch, wächst die Ausgabe von selbst mit.

**Angezeigte Runden** — bei Paarungen, Ergebnissen und Wettkämpfen.
Beschränkt die Ausgabe auf einzelne Runden; ohne Auswahl erscheinen alle. Die
Rundennummern kommen aus der Datei: Ein Turnier über fünf Runden bietet fünf
Kästchen an.

**Rundenüberschriften ausblenden** — ebenfalls bei diesen drei Listen. Über
jeder Tabelle steht sonst „Runde 4". Steht die Runde schon in der Überschrift
des Elements oder im Text um einen Inserttag herum, ist sie dort zu viel.
Ohne Auswahl bleiben die Überschriften stehen — eine Liste über mehrere
Runden wäre ohne sie nicht zu lesen.

#### Woher die Zahlen eines Zwischenstands kommen

Die Turnierdatei speichert Punkte und Feinwertungen **nur für den Endstand**.
Ein Zwischenstand muss deshalb gerechnet werden, und zwar aus den Partien.
Für die Punkte ist das eindeutig. Für die Feinwertungen nicht: Ob eine
spielfreie Runde mitzählt, mit welchem Wert ein Gegner eingeht, der selbst
ausgesetzt hat, und ob der schlechteste Wert gestrichen wird, sind
Turniereinstellungen, die in der Datei an keiner bekannten Stelle stehen.

Geraten wird deshalb nicht. Der Betrachter **rechnet zuerst den Endstand
nach** und vergleicht ihn mit dem, was in der Datei steht. Nur die
Regelfassung, die dabei jeden einzelnen Wert trifft, wird auf den
Zwischenstand angewandt. Findet sich keine, bleibt die Spalte leer und ein
Hinweis nennt den Grund — eine Zahl, die neben der amtlichen Tabelle steht
und ihr widerspricht, wäre schlimmer als keine.

Nachrechenbar sind Buchholzwertung, Buchholzsumme, mittlere Buchholz,
Sonneborn-Berger und Summenwertung. Wertungen, die auf Wertungszahlen beruhen
— Gegner-Elo-Mittel, Rating-Leistung —, fehlen im Zwischenstand immer.

Ebenfalls nicht im Zwischenstand: **Sonderpunkte**. Die Datei nennt nur ihre
Summe, nicht die Runde, in der sie vergeben wurden.

### Reiternavigation

Ab zwei Turnierausgaben in einem Umschlag entsteht eine Reiterleiste; mit
Pfeiltasten lässt sich zwischen den Reitern wechseln. **Ohne JavaScript stehen
alle Ausgaben untereinander** und bleiben vollständig lesbar — der Server
liefert sie alle sichtbar aus, und erst das Skript blendet aus, was hinter den
Reitern liegt. Bei einer einzigen Ausgabe entfällt die Leiste.

## Anpassen der Ausgabe

Alle Listen sind eigene Contao-Templates und lassen sich einzeln
überschreiben:

| Template | Liste |
| --- | --- |
| `ce_chesstournamentviewer` | Rahmen einer Turnierausgabe, mit Hinweisen |
| `ctv_ausgabe` | Rahmen einer Ausgabe, die über einen Inserttag kommt |
| `ce_chesstournamentviewerStart` | Öffnender Umschlag mit der Reiterleiste |
| `ce_chesstournamentviewerStop` | Schließender Umschlag |
| `ctv_spaltenkopf` | Ein Spaltenkopf von Teilnehmerliste und Rangliste |
| `ctv_spaltenzeile` | Eine Zeile von Teilnehmerliste und Rangliste, mit den gewählten Spalten |
| `ctv_turnierdaten` | Turnierdaten |
| `ctv_teilnehmer` | Teilnehmer |
| `ctv_rangliste` | Rangliste |
| `ctv_kreuztabelle` | Kreuztabelle |
| `ctv_fortschritt` | Fortschrittstabelle — und über einen Verweis auch die Fassung ohne Punktestand |
| `ctv_fortschrittohne` | Fortschritt ohne Punktestand |
| `ctv_paarungen` | Paarungen — und über einen Verweis auch die Ergebnisse |
| `ctv_ergebnisse` | Ergebnisse |
| `ctv_partiezeile` | Eine Partiezeile der Paarungs- und Ergebnisliste, nach Farbe ausgerichtet |
| `ctv_wettkampfzeile` | Eine Brettzeile im Wettkampf, nach Mannschaft ausgerichtet |
| `ctv_spaltenkopf` | Ein Spaltenkopf von Teilnehmerliste und Rangliste |
| `ctv_spaltenzeile` | Eine Zeile von Teilnehmerliste und Rangliste, mit den gewählten Spalten |
| `ctv_mannschaften` | Mannschaften |
| `ctv_mannschaftsrangliste` | Mannschaftstabelle |
| `ctv_mannschaftsfortschritt` | Fortschrittstabelle der Mannschaften |
| `ctv_mannschaftspaarungen` | Wettkämpfe |
| `ctv_mannschaftskreuztabelle` | Kreuztabelle der Mannschaften |

Für die Formatierung steht in den Templates die Klasse
`Schachbulle\ContaoChesstournamentviewerBundle\Liste\Ausgabe` bereit:
`punkte()` schreibt halbe Punkte als ½, `esc()` maskiert Werte aus der
Turnierdatei, `spalte()` holt eine Spaltenbeschriftung aus der Sprachdatei,
`zelle()` gibt den Inhalt einer wählbaren Spalte aus.

Welche Spalten es gibt und welche eine Datei füllen kann, steht in
`Schachbulle\ContaoChesstournamentviewerBundle\Liste\Spalten`. Eine neue
Spalte braucht dort einen Eintrag, einen Zweig in `Ausgabe::zelle()` und eine
Beschriftung in den Sprachdateien.

Farben und Abstände kommen aus `betrachter.css`. Alle Farben stehen als
CSS-Eigenschaften am Element `.ctv` und lassen sich im eigenen Theme
überschreiben, ohne die Datei zu ersetzen:

```css
.ctv {
    --ctv-grund: #fff;            /* Grund der Tabelle und des aktiven Reiters */
    --ctv-text: #002533;          /* Schrift in Reitern und Tabellen */
    --ctv-linie: #dfe5e9;         /* Tabellen- und Reiterlinien */
    --ctv-kopf: #004f6e;          /* Tabellenkopf und Wettkampfkopf */
    --ctv-kopf-text: #fff;        /* Schrift darin */
    --ctv-wechsel: #f2f6f8;       /* jede zweite Zeile, ruhende Reiter */
    --ctv-schweben: #e4eef4;      /* Zeile unter dem Mauszeiger */
    --ctv-gedaempft: #5b7484;     /* Nebenangaben wie Farbe und Punktestand */
    --ctv-akzent: #004f6e;        /* Balken über dem aktiven Reiter */
    --ctv-blind: #c9ced3;         /* Blindfelder der Kreuztabelle */
    --ctv-blind-figur: #6b7379;   /* der König darin */
    --ctv-weiss: #fff;            /* Punkt des Weißspielers im Wettkampf */
    --ctv-schwarz: #33454f;       /* Punkt des Schwarzspielers */
    --ctv-farbrand: #7d919c;      /* Rand dieser Punkte */
    --ctv-rundung: 10px;          /* Ecken der Tabelle */
    --ctv-schatten: 0 2px 12px rgba(0, 0, 0, .18);
}
```

**Die Tabelle ist als Karte gestaltet**: heller Grund, abgerundete Ecken, ein
flacher Schatten, dunkler Kopf mit Versalien und ein Zeilenwechsel in hellem
Blaugrau. Das ist die Gestaltung der Olympia-Tabellen auf schachbund.de. Wer
die schlichtere Fassung früherer Versionen möchte, setzt `--ctv-rundung: 0`,
`--ctv-schatten: none` und einen hellen `--ctv-kopf`.

**Die Farbe am Brett steht als Punkt vor dem Namen**, weiß gefüllt für Weiß,
dunkel für Schwarz. Bis Fassung 1.12.0 war stattdessen die ganze Zelle
eingefärbt; das nahm der Tabelle die Streifung.

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

## Unterstützte Turnierformate

| Format | Endungen | Umfang |
| --- | --- | --- |
| **SWT** (Swiss-Chess) | `swt` | Vollständig: Teilnehmer, Ergebnisse, Rangliste, Feinwertungen, Mannschaftswertung |
| **Swiss-Manager** | `tun`, `tunx`, `tur`, `turx`, `tum`, `tumx`, `tut`, `tutx` | Teilnehmer, Ergebnisse, Mannschaften und Wettkämpfe. **Ohne Feinwertungen** — siehe unten |

Bei **Swiss-Manager** stehen Punkte, Platzierungen und Feinwertungen nicht in
der Datei; das Programm rechnet sie bei jeder Anzeige neu. Der Betrachter tut
dasselbe, und zwar für die Punkte vollständig. Bei den Feinwertungen geht das
nicht: In der Datei steht nicht, welche das Turnier führt. Punktgleiche
Teilnehmer teilen sich deshalb den Platz, und die Feinwertungsspalten
entfallen. Ein Hinweis über der Tabelle sagt das.

Ab 250 Teilnehmern entfällt außerdem die Kreuztabelle — bei den 1031
Teilnehmern einer Schacholympiade hätte sie über eine Million Felder.

Der Aufbau des Swiss-Manager-Formats ist in [SWISS-MANAGER.md](SWISS-MANAGER.md)
beschrieben.

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
Fortschrittstabelle, die Formaterkennung, das Listenverzeichnis, der
Rundenschnitt samt Kalibrierung der Feinwertungen und das Kürzen der
Eingabemaske.

## Lizenz

LGPL-3.0-or-later. Die Klasse `SwtFile` ist vom SWT-Parser des
Zugzwang-Projekts abgeleitet (<http://www.zugzwang.org/projects/swtparser>),
Copyright © 2005, 2012 Gustaf Mossakowski, Jacob Roggon, Falco Nogatz, und
steht unter derselben Lizenz.

Die Flaggen unter `src/Resources/public/flags` stammen aus dem Paket
[flag-icons](https://github.com/lipis/flag-icons), Copyright © 2013 Panayiotis
Lipiridis, MIT-Lizenz; der Lizenztext liegt im selben Verzeichnis.
