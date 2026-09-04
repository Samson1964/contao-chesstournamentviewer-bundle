# Änderungen

## Version 1.9.0 (2026-09-03)

Diese Fassung bringt ein neues Datenbankfeld mit. Nach dem Aktualisieren ist
ein **Datenbank-Abgleich** nötig.

* Fix: Die Spalte der Turnierwertungszahl hieß **„Elo", wenn die Datei nicht
  sagt, welche Zahl das Turnier führt** — und stand dann neben einer echten
  Elo-Spalte zweimal gleich da. Ohne Angabe trägt sie jetzt den Sammelbegriff
  „TWZ". Für Swiss-Manager wird die Angabe außerdem nachgeliefert: Dort steht
  sie zwar nicht in der Datei, ergibt sich aber daraus, nach welcher Zahl die
  Startrangliste geordnet ist. Betroffen war jede Swiss-Manager-Datei.
* Fix: Der FIDE-Titel steht vor dem Namen — „IM Berger,Steve". Hat er eine
  **eigene Spalte**, verschwindet er jetzt aus dem Namen; vorher stand er in
  derselben Zeile zweimal.
* Add: Kästchen **„Stand der Turnierdatei anzeigen"**. Setzt unter die Tabelle,
  wann die Turnierdatei zuletzt geändert wurde. Maßgeblich ist das
  Änderungsdatum in der Dateiverwaltung — Turnierleitungen laden nach jeder
  Runde eine neue Fassung hoch, und damit ist es die verlässliche Auskunft
  darüber, wie aktuell die Zahlen sind. Bringt ein Format eine eigene Angabe
  mit, hat diese Vorrang.
* Add: **Mannschafts-Rundenturniere (`.TUTx`) geprüft.** Die Endung war bisher
  nur angemeldet, aber nie an einer gespielten Datei erprobt. Die
  Senioren-Mannschaftsmeisterschaft der Landesverbände 2025 trifft die
  Endtabelle von chess-results exakt: acht Mannschaften, Mannschafts- und
  Brettpunkte ohne Abweichung.
* Add: Neuer Swiss-Manager-Prüfbestand mit 78 Dateien in allen vier Endungen —
  58 Schweizer System, 12 Rundenturniere, 4 Mannschafts-Rundenturniere und 4
  Mannschaftsturniere nach Schweizer System. Alle laufen ohne Lesefehler durch
  die volle Kette.

## Version 1.8.0 (2026-09-03)

Diese Fassung ändert die Bedienung grundlegend und bringt neue
Datenbankfelder mit. Nach dem Aktualisieren ist ein **Datenbank-Abgleich**
nötig.

**Bestehende Inhaltselemente mit mehreren Listen zeigen künftig nur noch ihre
erste.** Wer Reiter hatte, baut sie mit dem neuen Umschlag nach: ein Element
je Liste, eingeklammert von „Umschlag Anfang" und „Umschlag Ende".

* Change: **Ein Inhaltselement gibt genau eine Liste aus.** Aus der
  Mehrfachauswahl „Auszugebende Listen" wird das Auswahlfeld „Auszugebende
  Liste". Damit trägt jedes Element nur noch die Einstellungen, die zu seiner
  Liste gehören — statt einer Maske, in der neben den Feldern für die
  Kreuztabelle auch die für die Wettkämpfe stehen.
* Add: **Zwei neue Inhaltselemente, „Umschlag Anfang" und „Umschlag Ende".**
  Sie klammern beliebig viele Turnierausgaben ein, die dann als Reiter
  erscheinen. Contao rückt die eingeschlossenen Elemente im Backend ein, wie
  bei Akkordeon und Slider. Die Ausgaben müssen nicht dieselbe Turnierdatei
  verwenden.
* Change: Das Inhaltselement heißt **„Schachturnier-Betrachter –
  Turnierausgabe"**.
* Change: **Die Maske baut sich in drei Schritten auf.** Ohne Datei steht nur
  die Dateiauswahl da; nach dem Speichern kommt die Auswahl der Ausgabe hinzu;
  nach deren Wahl erscheinen sofort deren Einstellungen. Der Redakteur steht
  damit nie vor Feldern, die noch nichts bewirken können.
* Change: Statt zweier Spaltenfelder gibt es eins. Es zeigt die Spalten der
  gewählten Liste, und die gebräuchlichen sind vorangehakt.
* Change: Die Vorauswahl der Teilnehmerliste ist schlanker: Nr., Name,
  Turnierwertungszahl und Verein. Elo und DWZ lassen sich dazuhaken; alle drei
  Wertungszahlen nebeneinander machten die Tabelle auf schmalen Bildschirmen
  unlesbar.
* Change: „Stand nach Runde" heißt in der Vorgabe jetzt „Aktueller Stand
  (letzte Runde)" — dieselbe Wirkung, aber die Bezeichnung sagt, was sie tut:
  Sie nimmt die gespeicherten Zahlen der Datei und wächst mit, wenn eine neue
  Fassung hochgeladen wird.
* Change: Die Reiterleiste baut jetzt das Skript aus den Ausgaben, die es im
  Umschlag findet. Der Server kann sie nicht bauen: Das öffnende Element weiß
  beim Ausliefern nicht, was nach ihm kommt. Ohne JavaScript stehen die
  Ausgaben untereinander, jede vollständig lesbar.

## Version 1.7.0 (2026-09-03)

Diese Fassung bringt zwei neue Datenbankfelder mit. Nach dem Aktualisieren
ist ein **Datenbank-Abgleich** nötig.

* Add: **Wählbare Spalten für Teilnehmerliste und Rangliste.** Angeboten wird,
  was die gewählte Datei hergibt — ein Turnier ohne Elo-Zahlen bietet keine
  Elo-Spalte an, ein Einzelturnier keine Brettspalte, eine Datei ohne
  Feinwertung keine Feinwertungsspalte. Das Auswahlfeld lässt sich ziehen; die
  Reihenfolge ist die der Ausgabe. Ohne Auswahl erscheinen die bisherigen
  Spalten, bestehende Inhaltselemente ändern sich also nicht.
* Add: Neue Spalten, die es bisher nicht gab: Titel, Geburtsjahr,
  FIDE-Kennung, Gruppe, Land, Verein und die Zahl der Partien. Das Geburtsjahr
  kommt aus beiden Formaten — Swiss-Manager führt es als Zahl, der SWT-Leser
  als Datumstext.
* Add: **Sortierung im Frontend.** In Teilnehmerliste und Rangliste ordnet ein
  Klick auf den Spaltenkopf die Tabelle; ein zweiter dreht die Richtung um.
  Punktestände wie „3½" und Bilanzen wie „5/2/1" werden nach ihrem Zahlenwert
  geordnet, nicht als Text, und leere Felder stehen in beiden Richtungen am
  Ende. Die Köpfe sind mit der Tastatur erreichbar und melden über `aria-sort`,
  wonach geordnet ist. Ohne JavaScript steht die Tabelle in der Reihenfolge der
  Turnierdatei.
* Change: Nach Mannschaften gegliederte Tabellen sind nicht sortierbar — die
  Kopfzeilen der Mannschaften rutschten sonst zwischen die Spieler.
* Change: **Bei einer einzigen Liste steht keine Überschrift mehr über der
  Ausgabe.** Wer nur die Teilnehmerliste einbindet, hat die Überschrift des
  Inhaltselements dafür. Ab zwei Listen erscheint sie weiterhin — und wird
  ausgeblendet, sobald die Reiter stehen. Ohne JavaScript bleibt sie sichtbar,
  was bisher fehlte: Dort standen die Listen ohne jede Beschriftung
  untereinander.
* Change: Das Inhaltselement heißt **„Schachturnier-Betrachter"**.
* Change: Die Warnung zu den erlaubten Dateitypen nennt die Endungen ohne
  Leerzeichen — genau so, wie sie in „Einstellungen → Erlaubte Dateitypen"
  einzutragen sind.
* Change: Das Template `ctv_ranglistenzeile` entfällt; Teilnehmerliste und
  Rangliste bauen ihre Zeilen jetzt aus `ctv_spaltenzeile` und ihre
  Spaltenköpfe aus `ctv_spaltenkopf`. Wer `ctv_ranglistenzeile`
  überschrieben hatte, muss das übertragen.

## Version 1.6.0 (2026-09-03)

* Add: **Zweites Turnierformat: Swiss-Manager.** Gelesen werden die Endungen
  `tun`, `tur`, `tum` und `tut`, jeweils auch in der Unicode-Fassung mit
  angehängtem `x`. Das Format war nicht dokumentiert und wurde an sieben
  Turnierdateien erarbeitet; der Aufbau steht in `SWISS-MANAGER.md`.
* Add: Erkannt werden Teilnehmer mit Titel, Verein, Föderation, Gruppe, beiden
  Wertungszahlen, FIDE-Kennung und Geburtsjahr, die Partien aller Runden samt
  kampflosen und spielfreien Sätzen, die Mannschaften mit Mannschaftsführer
  und Aufstellung sowie die Wettkämpfe.
* Change: Bei Swiss-Manager entstehen **Punkte und Platzierungen aus den
  Partien** — die Datei speichert sie nicht. Feinwertungen bleiben leer, weil
  in der Datei nicht steht, welche das Turnier führt; punktgleiche Teilnehmer
  teilen sich deshalb den Platz. Ein Hinweis über der Tabelle nennt den Grund.
* Add: Ab 250 Teilnehmern entfällt die Kreuztabelle. Bei den 1031 Teilnehmern
  einer Schacholympiade hätte sie über eine Million Felder und wäre weder
  aufzubauen noch zu lesen.
* Add: Geprüft gegen chess-results.com — Punktzahlen der Endtabellen von vier
  Turnieren (Schweizer System, Rundenturnier, Turnier mit spielfreier Runde),
  die Mannschaftspunkte einer Frauen-Mannschaftsmeisterschaft und die
  Teilnehmerzahlen aller sieben Dateien. Acht Dateien laufen durch die volle
  Kette, mit und ohne Rundenschnitt.
* Add: `.gitignore` hält Turnierdateien aus dem Repository heraus. Sie
  enthalten Namen, Geburtsjahre und Mitgliedsnummern lebender Personen; die
  Testfälle bauen sich ihre Prüfdatei deshalb selbst zusammen.

## Version 1.5.0 (2026-09-02)

Diese Fassung bringt zwei neue Datenbankfelder mit. Nach dem Aktualisieren
ist ein **Datenbank-Abgleich** nötig (Contao Manager → System-Wartung, oder
`vendor/bin/contao-console contao:migrate`). Bestehende Inhaltselemente
bleiben unverändert: „Ganzes Turnier" und „alle Runden" sind die Vorgaben.

* Add: Feld **„Stand nach Runde"**. Es versetzt das ganze Inhaltselement auf
  den Stand nach einer Runde zurück — Rangliste, Kreuztabelle,
  Fortschrittstabelle und Mannschaftstabelle zeigen dann, wie es damals
  aussah. Über der Ausgabe steht, welcher Zeitpunkt gemeint ist.
* Add: Feld **„Angezeigte Runden"**. Es beschränkt Paarungs-, Ergebnis- und
  Wettkampfliste auf einzelne Runden; die Kästchen entstehen aus der Datei,
  ein Fünfrundenturnier bietet fünf an. Auf die Tabellen wirkt es nicht —
  dafür ist der Stand zuständig.
* Add: Die Feinwertungen eines Zwischenstands werden nachgerechnet, aber nur
  nach einer **am Endstand überprüften Regel.** Die Rechenweise steht nicht in
  der Datei; der Betrachter probiert deshalb vierzig Regelfassungen am
  gespeicherten Endstand durch und wendet nur die an, die dort jeden Wert
  trifft. Findet sich keine, bleibt die Spalte leer und ein Hinweis nennt den
  Grund. Im Prüfbestand ließen sich 226 von 294 Turnieren mit Buchholzwertung
  und 65 von 96 mit Sonneborn-Berger zuordnen.
* Change: **Die Eingabemaske zeigt nur noch, was auch wirkt.** Bisher richtete
  sie sich allein nach der Turnierart. Jetzt fallen zusätzlich alle
  Einstellungen weg, deren Liste gar nicht gewählt ist — „Kreuztabelle
  kürzen" ohne Kreuztabelle, „Spieler mit ausgeben" ohne Mannschaftsliste,
  die Rundenauswahl ohne Rundenliste —, ebenso die Rundenfelder bei einem
  einrundigen Turnier und das Hinweiskästchen bei einer Datei ohne Hinweise.
  Anders als die Anpassung an die Datei greift diese sofort: Das Auswahlfeld
  schickt die Maske ab.
* Change: Die Listenauswahl ist ein sortierbares Kästchenfeld
  (`checkboxWizard`). **Die Reiter stehen jetzt in der Reihenfolge, die der
  Redakteur zieht**, statt in der festen des Bundles.
* Add: Der Aufruf des Listenbauers nimmt die Einstellungen als ein Objekt
  `Auswahl` entgegen statt als wachsende Reihe von Wahrheitswerten. Wer den
  Listenbauer selbst aufruft, muss seinen Aufruf anpassen.
* Add: Die Kreuztabelle wird formatunabhängig aus Rangliste und Paarungen
  gebaut — gebraucht für den Zwischenstand, zugleich Rückfallebene für
  Formate ohne eigene Kreuztabelle. Gegen die Kreuztabelle des SWT-Lesers
  geprüft: 5.668.593 Felder, keine Abweichung.
* Add: Gegenprobe des Rundenschnitts über den Bestand — 2.373 Schnitte in 791
  Dateien, ohne Verstoß gegen die Bedingungen, dass keine Paarung jenseits der
  Schnittrunde übrigbleibt, die Punkte der Summe der Partien entsprechen und
  die Rangliste absteigend sortiert ist.

## Version 1.4.0 (2026-08-31)

* Fix: In den Wettkämpfen standen die Bretter nach **Farbe** ausgerichtet,
  während über den Spalten die **Mannschaften** stehen. Weil die Farben von
  Brett zu Brett wechseln, stand in einer Spalte abwechselnd ein Spieler
  jeder Mannschaft. Die Zeilen richten sich jetzt an den Mannschaften aus.
* Add: Damit die Farbe dabei nicht verlorengeht, ist sie am Feld abzulesen —
  wer Weiß führt, sitzt auf hellem Grund, wer Schwarz führt, auf grauem. Die
  Farben stehen als `--ctv-weiss` und `--ctv-schwarz` zur Verfügung.
* Fix: Bei **Doppelrunden** — zwei Partien je Paarung und Runde, verbreitet
  bei Blitzturnieren — wurde das Gegenergebnis als `1 − x` gerechnet statt
  als `2 − x`. Aus einem Wettkampf mit 1½ wurden dadurch −½ Punkte für die
  Gegenseite. Betroffen waren 515 von 14.159 Wettkämpfen im geprüften
  Bestand; die Ergebnisliste eines doppelrundigen Einzelturniers zeigte
  ebenfalls falsche Paare.
* Fix: Partien gegen den Platzhalterteilnehmer zählten in den Wettkämpfen
  mit, obwohl der Leser sie aus den Brettpunkten heraushält.
* Fix: Die Kreuztabelle der Mannschaften hatte die Überarbeitung von 1.1.0
  nicht mitbekommen: keine Königsfigur in den Blindfeldern, keine zentrierten
  Ergebnisse, keine gleich breiten Spalten.
* Add: Die Kreuztabelle der Mannschaften zeigt Mannschafts- und Brettpunkte
  vor den Ergebnisspalten, wie die Kreuztabelle der Einzelturniere.
* Add: Gegenprobe über den gesamten Bestand — für jeden Wettkampf muss der
  Heimspieler jeder Partie zur Heimmannschaft gehören und die Summe der
  Brettergebnisse das Wettkampfergebnis ergeben. Geprüft an 14.159
  Wettkämpfen mit 60.584 Einzelpartien, ohne Abweichung. Zwei Testfälle
  halten die Bedingung fest.

## Version 1.3.0 (2026-08-31)

* Add: Die Eingabemaske richtet sich nach der gewählten Datei. Ist es ein
  Einzelturnier, verschwinden die Feldgruppe „Mannschaftsturniere" und die
  Mannschaftslisten aus der Auswahl; bei einem Mannschaftsturnier stehen sie
  bereit. Die Anpassung greift nach dem Speichern, weil die Datei vorher
  nicht im Datensatz steht.
* Add: Warnung in der Eingabemaske, wenn die Dateiendung der Turnierformate
  nicht unter „Einstellungen → Erlaubte Dateitypen" steht. Ohne sie lassen
  sich Turnierdateien gar nicht erst hochladen, und die Dateiauswahl bleibt
  ohne erkennbaren Grund leer.
* Add: Kästchen „Hinweise zu den Zahlen anzeigen". Die Hinweise über den
  Tabellen erscheinen jetzt nur noch auf Wunsch.
* Add: Kästchen „Kreuztabelle der Mannschaften kürzen" — zeigt in jeder Zelle
  nur die eigenen Brettpunkte („3½") statt beider Seiten („3½:½").
* Change: Bei Mannschaftsturnieren werden Teilnehmerliste und Rangliste nach
  Mannschaften gegliedert: je Mannschaft eine Kopfzeile mit Startnummer und
  Name, darunter die Spieler. In der Rangliste bleibt die Platzierung des
  ganzen Turniers in der Spalte `Pl.` sichtbar.
* Change: Paarungs- und Ergebnisliste eines Mannschaftsturniers stehen
  ebenfalls nach Wettkämpfen gegliedert, mit den beiden Mannschaften und dem
  Wettkampfergebnis in der Kopfzeile.
* Change: Die Wettkämpfe sind neu aufgebaut — Tisch, Mannschaft,
  Wertungsschnitt der eingesetzten Bretter, Ergebnis, Mannschaft,
  Wertungsschnitt. Die Einzelpartien stehen darunter in denselben Spalten und
  brauchen deshalb keine eigene Kopfzeile mehr.
* Change: In der Kreuztabelle stehen Wertungszahl, Punkte und Feinwertungen
  jetzt vor den Ergebnisspalten statt dahinter; die Wertungszahl folgt
  unmittelbar auf den Namen.

## Version 1.2.0 (2026-08-31)

* Change: Der SWT-Leser wurde auf die Fassung vom 2026-08-31 gebracht. Er
  wertet die Mannschaftsdaten jetzt selbst aus — Mannschaften unter ihrer
  echten Nummer, Wettkämpfe mit Brett- und Mannschaftspunkten aus den
  Einzelpartien, Mannschaftspunkte nach der Turniereinstellung (zwei oder
  drei für den Sieg). Die Rückrechnung im Bundle entfällt dafür.
* Change: **Freilose bleiben unbewertet.** Bis 1.1.0 bekam eine Mannschaft
  ohne Wettkampf die volle Brettzahl und zwei Mannschaftspunkte
  gutgeschrieben. Das war geraten: In der Datei steht dazu nichts, und die
  Turnierleitungen halten es unterschiedlich. Weicht die Tabelle deshalb von
  der gespeicherten ab, steht darüber ein Hinweis.
* Change: Nicht gespielte und am grünen Tisch entschiedene Wettkämpfe
  erscheinen als Strich statt als Unentschieden.
* Fix: Kampflose Partien werden mit `+:-` und `-:+` ausgegeben statt mit
  `1:0` und `0:1`. Betrifft 906 Partien im geprüften Bestand.
* Fix: Kreuztabelle und Reiter setzen sich jetzt gegen Themes durch, die ihre
  Tabellen und Knöpfe über ID-Selektoren gestalten (`#main table td`,
  `#main button`). Gegen eine ID kommt keine Klassenregel an; die
  Eigenschaften, ohne die der Betrachter unbrauchbar wird, tragen deshalb ein
  `!important`. Anpassbar bleiben sie über die Farbeigenschaften an `.ctv`.
* Change: Blindfelder der Kreuztabelle heben sich deutlicher ab und tragen
  einen mittig gesetzten König.
* Add: Mannschaftstabelle und Kreuztabelle der Mannschaften gegen die
  CSV-Ausgaben von Swiss-Chess geprüft: 28 und 38 Mannschaften sowie 784
  Kreuzfelder, keine Abweichung.

## Version 1.1.0 (2026-08-31)

* Add: Neue Liste „Fortschritt ohne Punktestand" — dieselbe
  Fortschrittstabelle, aber ohne den laufenden Punktestand unter jedem
  Rundenergebnis.
* Change: Ergebnis- und Paarungsliste in der Spaltenfolge Brett, Weiß,
  Wertungszahl, Ergebnis, Schwarz, Wertungszahl. Das Ergebnis steht
  vollständig als `1:0`, `½:½` oder `0:1` statt als einzelne Zahl; in der
  Paarungsliste steht dort ein Strich. Weiß und Schwarz bekommen dieselbe
  Breite, Brett- und Ergebnisspalte bleiben schmal.
* Change: Der Spaltenkopf der Wertungszahl nennt die Zahl, die im Turnier den
  Ausschlag gibt — Elo, NWZ oder TWZ, je nach Einstellung der Datei.
* Change: Fortschrittstabelle in der Reihenfolge Ergebnis, Farbe, Gegner
  (`1w10`); die Rundenspalten tragen die Nummer ohne Punkt.
* Change: Kreuztabelle mit gleich breiten, mittig gesetzten Ergebnisfeldern.
  Die Blindfelder der Diagonale sind mit einem König gekennzeichnet und
  deutlicher abgesetzt.
* Change: Kurzformen in schmalen Spaltenköpfen — `Pl.`, `Pkt.`, `Br.` und
  Feinwertungen wie `SoBe` statt `Sonneborn-Berger`. Die volle Bezeichnung
  steht als Titel am Spaltenkopf.
* Fix: Die Reiter setzen Schrift- und Hintergrundfarbe ausdrücklich, statt sie
  vom Theme zu erben — dort konnte dunkle Schrift auf dunklem Grund stehen.
  Der aktive Reiter ist zusätzlich durch einen farbigen Balken, hellen Grund
  und eine offene Unterkante hervorgehoben.

## Version 1.0.0 (2026-08-31)

* Add: Inhaltselement „Turnier-Betrachter" für Contao 4.13 und Contao 5,
  mit Dateiauswahl, Formatauswahl samt automatischer Erkennung und
  Mehrfachauswahl der auszugebenden Listen.
* Add: Unterstützung des Formats SWT (Swiss-Chess) auf Grundlage der Klasse
  `SwtFile` aus dem Projekt SwtReader.
* Add: Listen für Einzelturniere — Turnierdaten, Teilnehmer, Rangliste,
  Kreuztabelle, Fortschrittstabelle, Paarungen und Ergebnisse.
* Add: Listen für Mannschaftsturniere — Mannschaften mit Aufstellung,
  Mannschaftstabelle, Wettkämpfe mit Einzelpartien und Kreuztabelle der
  Mannschaften; die Ausgabe der Spieler lässt sich abschalten.
* Add: Mannschaftswertung wird aus den Einzelpartien zurückgerechnet, weil
  der Mannschaftsbereich der SWT-Dateien keine Ergebnisse enthält. Geprüft
  gegen die gespeicherten Werte von 75 Mannschaften aus drei Turnierdateien
  der Fassungen 882 bis 897, ohne Abweichung.
* Add: Reiternavigation ab zwei Listen, mit Pfeiltasten bedienbar. Ohne
  JavaScript stehen alle Listen untereinander.
* Add: Schnittstelle `TurnierFormatInterface` für weitere Turnierformate;
  neue Formate brauchen nur einen Dienst-Eintrag.
