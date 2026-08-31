# Änderungen

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
