# Änderungen

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
