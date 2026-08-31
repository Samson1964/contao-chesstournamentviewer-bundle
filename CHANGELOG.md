# Änderungen

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
