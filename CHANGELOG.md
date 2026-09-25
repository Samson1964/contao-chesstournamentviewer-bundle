# Änderungen

## Version 1.17.1 (2026-09-25)

* Fix: **Die Umschlag-Elemente zerrissen die Liste der Inhaltselemente im
  Backend.** Contao stellt dort jedes Element mit seiner echten
  Frontend-Ausgabe dar. Bei „Umschlag Ende" ist das ein einzelnes `</div>`,
  und das schloss nicht die Vorschau, sondern die Liste selbst: Alle Elemente
  hinter dem ersten Umschlag standen außerhalb der `ul`. Sichtbar war das
  daran, dass die folgenden Elemente nach links rückten und sich nicht mehr
  ziehen ließen — was außerhalb der Liste steht, kennt die Sortierung nicht.
  Beide Umschlag-Elemente zeigen im Backend jetzt einen Hinweiskasten, so wie
  Akkordeon und Slider von Contao.

## Version 1.17.0 (2026-09-21)

* Fix: **Die Fortschrittstabelle der Mannschaften war bei der Olympiade
  falsch.** Freilose brachten keine Punkte, weil die Gutschrift in der Datei
  nicht gelesen wurde, und die Reihenfolge folgte nur Mannschafts- und
  Brettpunkten. Jetzt liest der Swiss-Manager-Leser die Wertungsliste des
  Turniers und die Freilosgutschrift aus dem Einstellungsabschnitt, und die
  Olympia-Wertungen werden gerechnet. Gegen die Endtabelle bei chess-results
  (tnr1470206): alle 41 Mannschaften, alle vier Wertungen und alle 287
  Rundenzellen gleich.
* Change: **Neue Darstellung der Rundenzellen**, wie bei chess-results in
  einem Zug: Brettpunkte, Farbe am ersten Brett, Platz des Gegners —
  „2½w6". Schwarz heißt `s`. Der Punktestand unter jeder Zelle entfällt.
  In der Zelle steht jetzt der Platz des Gegners statt seiner Startnummer.
* Add: **Spaltenauswahl für die Fortschrittstabelle der Mannschaften.** Die
  Vorgabe folgt der Wertungsreihenfolge des Turniers, bei der Olympiade
  Platz, Mannschaft, Runden, MP, OSB, BP, MP-Summe.
* Add: Spalten **OSB** (Olympia-Sonneborn-Berger) und **MP-Summe** für
  Mannschaftstabelle und Fortschrittstabelle — angeboten nur, wenn das
  Turnier nach ihnen ordnet.
* Change: Mannschaften mit 3/1/0-Wertung bekommen drei Punkte für den Sieg,
  wenn die Datei das so festlegt; bisher galt bei Swiss-Manager immer 2/1/0.
* Change: Brettpunkte stehen wie bei chess-results ohne „,0" hinter ganzen
  Zahlen.

## Version 1.16.3 (2026-09-17)

* Fix: **Die Tabellen waren auf schachbund.de trotz fester Spaltenbreiten
  ungleich breit.** Das Theme setzt `table { display: block }`, damit breite
  Tabellen rollen. Eine Tabelle als Block ist zwar 100 % breit, ihre Zeilen
  bilden darin aber eine unsichtbare Innentabelle, die nur so breit wird wie
  ihr Inhalt — die Namensspalten teilten sich den Platz dann je nach Namen
  verschieden auf. Die Tabellen des Betrachters erzwingen jetzt
  `display: table`; das Rollen übernimmt ohnehin ihr eigener Behälter.
  Auf der Live-Seite geprüft: alle drei Tabellen der Olympia-Box Spalte für
  Spalte gleich, bei 1400 px wie auf dem Handy.
* Change: Die Mindestbreite, unter der schmale Tabellen waagerecht rollen,
  sinkt von 22 auf 20 rem. Auf einem 400 px breiten Handy ist die Box
  330 px breit — mit 22 rem rollte sie dort um 22 px.

## Version 1.16.2 (2026-09-17)

* Fix: **Paarungen und Ergebnisse hatten verschiedene Spaltenbreiten.** Die
  Spalten richteten sich nach dem Inhalt: In den Paarungen steht im Ergebnis
  ein „–", in den Ergebnissen „2½:1½" — die Ergebnisspalte wurde breiter, die
  Namen rückten zusammen, und mehrere Tabellen untereinander standen nicht
  bündig. Jetzt haben Brett, Wertungszahlen und Ergebnis feste Breiten, und
  die beiden Namensspalten teilen sich den Rest. Die Ergebnisspalte fasst von
  vornherein „10½:9½". Das gilt auch für die Liste der Wettkämpfe.
* Change: **Wird der Platz knapp, rücken diese Tabellen zusammen** — maßgeblich
  ist die Breite der Tabelle selbst, nicht die des Bildschirms, damit es auch
  in einer schmalen Seitenleiste greift. Unterhalb von etwa 350 px rollt die
  Tabelle waagerecht, statt die Namen weiter zu stauchen.
* Fix: Lange Namen liefen in schmalen Tabellen über die Wertungszahl daneben;
  sie brechen jetzt um.

## Version 1.16.1 (2026-09-17)

* Fix: **Inserttag fand seine Ausgabe nicht, wenn die Kennung einen Umlaut
  hatte.** Die Kennung wurde mit `StringUtil::generateAlias()` gebildet, und
  das lässt „ä" stehen: Aus „Olympiade 2026 Männer, 2. Runde" wurde
  „olympiade-2026-männer-2-runde". Im Text stand
  `{{ctv::olympiade-2026-maenner-2-runde}}`, und im Fehlerprotokoll „Zum
  Inserttag … gibt es keine Turnierausgabe". Die Kennung entsteht jetzt über
  den Slug-Dienst von Contao, wie ein Seitenalias: „ä" wird zu „ae", „ß" zu
  „ss". Das gilt auch für eine von Hand eingetragene Kennung.
* Fix: **Bestehende Kennungen mit Umlaut greifen sofort**, ohne dass jemand
  die Datensätze neu speichern muss: Passt ein Inserttag nicht auf Anhieb,
  werden die Kennungen nach derselben Umschrift verglichen.
* Fix: **„Stand nach Runde" und „Angezeigte Runden" erscheinen schon ab der
  ersten Runde.** Bisher erst ab der zweiten. Wer eine Ausgabe anlegte,
  solange erst Runde 1 gespeichert war, konnte die Runde nicht wählen — und
  sobald die Datei mehr Runden hatte, zeigte die Ausgabe alle, obwohl Runde 1
  gemeint war.

## Version 1.16.0 (2026-09-16)

* Add: **Spaltenauswahl für die Mannschaftstabelle**, wie bei Teilnehmerliste
  und Rangliste: Platz, Startnummer, Mannschaft, Land, Wettkämpfe, Bilanz,
  Freilose, Mannschaftspunkte, Brettpunkte und Wertungsschnitt, in frei
  wählbarer Reihenfolge. Ohne Auswahl sieht die Tabelle aus wie bisher.
* Add: **Die Mannschaftstabelle lässt sich im Frontend sortieren** — ein Klick
  auf den Spaltenkopf.
* Change: **Brettpunkte stehen in der Mannschaftstabelle mit Komma**, also
  „11,5" statt „11½", wie die Punkte der Rangliste. Mannschaftspunkte bleiben
  ganze Zahlen.

## Version 1.15.1 (2026-09-16)

* Fix: **Swiss-Manager: Mannschaften ohne Gegner erschienen als Wettkampf
  gegen niemanden.** In der Wettkampfliste steht für eine Mannschaft, die in
  einer Runde nicht antritt, ein Sonderwert statt einer Mannschaftsnummer.
  Der Leser hielt ihn für eine Mannschaft; in der Ausgabe stand dann
  „Angola – " ohne Gegner und ohne Bretter. Und weil derselbe Wert in jedem
  solchen Satz steht, sah die Rundenerkennung darin eine Wiederholung: Die
  Schacholympiade 2026 zeigte fünf Runden, obwohl nur eine ausgelost war.
  Jetzt stimmt die Ausgabe mit chess-results überein — 101 Wettkämpfe und
  fünf nicht ausgeloste Mannschaften in Runde 1.
* Add: **„nicht ausgelost" wird von „Freilos" unterschieden.** Ein Freilos ist
  eine Runde ohne Gegner im laufenden Turnier, „nicht ausgelost" heißt, dass
  die Mannschaft gar nicht erst zugelost wurde — etwa weil sie nicht angereist
  ist. Nur das Freilos zählt in der Spalte „Freilose".

## Version 1.15.0 (2026-09-16)

* Change: **Ein Kästchen für beide automatischen Überschriften.** Aus „Zeile
  ‚Stand nach Runde' ausblenden" und „Rundenüberschriften ausblenden" wird
  **„Automatische Überschriften ausblenden"** — es sind zwei Ausprägungen
  derselben Sache, und wer die eine nicht will, will in aller Regel auch die
  andere nicht. Die beiden alten Felder bleiben in der Datenbank und wirken
  weiter, damit nichts wieder auftaucht, was schon abgeschaltet war.
* Change: **Beide Überschriften sehen jetzt gleich aus** — ein `h4` mit dem
  Aussehen der bisherigen Standzeile: heller Grund, Balken in der Akzentfarbe.
  Vorher war die eine ein Absatz und die andere eine schlichte Überschrift;
  das sah nach zwei Ebenen aus, die es nicht gibt.
* Change: **Die Mannschaftsauswahl steht alphabetisch**, deutsche
  Mannschaften zuoberst. Vorher galt die Startnummer — bei einer Olympiade
  mit vielen Ländern war die eigene Mannschaft damit kaum zu finden.
* Add: **Deutsche Mannschaften sind in den Tabellen hervorgehoben**: fett und
  mit einem Balken in der Akzentfarbe. Jedes Mannschaftsfeld trägt die Klasse
  seiner Föderation (`ctv-land--ger`, `ctv-land--pol`), sodass sich im Theme
  jede beliebige Nation hervorheben lässt.
* Add: Mannschaftstabelle, Mannschaftsliste und Wettkämpfe zeigen die
  **Flagge vor dem Mannschaftsnamen**, wie die Paarungen und Ergebnisse es
  schon taten.

## Version 1.14.0 (2026-09-16)

* Add: Kästchen **„Rundenüberschriften ausblenden"**. Bisher stand über jeder
  Tabelle einer Paarungs-, Ergebnis- oder Wettkampfliste „Runde 4", auch wenn
  die Runde schon in der Überschrift des Elements oder im Text um den
  Inserttag herum genannt war.
* Change: **Die beiden Kästchen für die automatischen Überschriften stehen in
  einer eigenen Feldgruppe „Überschriften"** — und zwar in jedem
  Inhaltselement und bei jedem Inserttag, gleich welche Liste gewählt ist, so
  wie die Gruppe „Hinweise" auch. „Zeile ‚Stand nach Runde' ausblenden"
  erschien vorher nur bei den Listen, die einen Stand zeigen; wer von dort
  auf eine Ergebnisliste umstellte, fand das Kästchen nicht wieder.
  Ohne Auswahl erscheinen beide Überschriften wie bisher.

## Version 1.13.1 (2026-09-16)

* Fix: **Das Backend-Modul „Turnier-Inserttags" stand in einer eigenen
  Menügruppe „Schach".** Die Module der übrigen Schach-Bundles dieses Hauses
  stehen in der Gruppe „Inhalte"; dort steht es jetzt auch. Eine eigene
  Menügruppe für ein einzelnes Modul war mehr Menü als Nutzen.

## Version 1.13.0 (2026-09-16)

* Add: **Backend-Modul „Turnier-Inserttags".** Dort lassen sich
  Turnierausgaben anlegen, die kein eigenes Inhaltselement sein sollen, und
  mit `{{ctv::kennung}}` in beliebige Texte einbinden — etwa drei
  Deutschland-Paarungen aus drei Turnieren in einer Box auf der Startseite.
  Die Einstellungen sind dieselben wie am Inhaltselement; die Übersicht zeigt
  hinter jedem Titel den fertigen Inserttag. Statt der Kennung geht auch die
  Datensatz-ID.
* Change: **Neues Standardlayout für alle Tabellen** nach dem Vorbild der
  Olympia-Tabellen auf schachbund.de: helle Karte mit abgerundeten Ecken und
  flachem Schatten, dunkler Kopf mit Versalien, Zeilenwechsel in hellem
  Blaugrau und eine hervorgehobene Zeile unter dem Mauszeiger. Alle Werte
  stehen weiterhin als CSS-Eigenschaften am `.ctv` und lassen sich im Theme
  überschreiben.
* Change: **Die Farbe am Brett steht als runder Punkt vor dem Namen**, weiß
  gefüllt für Weiß und dunkel für Schwarz. Vorher war die ganze Zelle
  eingefärbt; das nahm der Tabelle die Streifung.
* Change: Die Ausgabe entsteht jetzt in einem eigenen Dienst, den
  Inhaltselement und Inserttag gemeinsam benutzen. Für die Ausgabe ändert
  sich dadurch nichts.

## Version 1.12.0 (2026-09-16)

* Fix: **Die Länderflaggen fehlten in Chrome unter Windows.** Sie waren als
  Emoji ausgegeben, und die Emoji-Schrift von Windows führt keine
  Länderflaggen; statt der Flagge standen dort zwei Buchstaben in Kästchen,
  während Firefox mit seiner eigenen Schrift die Flagge zeigte. Das Bundle
  liefert die Flaggen jetzt als SVG mit — 195 Dateien aus dem Paket
  [flag-icons](https://github.com/lipis/flag-icons) (MIT) unter
  `src/Resources/public/flags`, dazu die Stilvorlage `flaggen.css`. Geladen
  wird nichts von fremden Servern.
* Add: **England, Schottland und Wales bekommen ihre eigene Flagge**, obwohl
  sie sich die ISO-Kennung des Vereinigten Königreichs teilen.
* Change: Das Flaggenfeld trägt den Ländernamen zusätzlich als `aria-label`;
  vorher hatte eine Vorlesehilfe dort nur das Emoji.

## Version 1.11.0 (2026-09-15)

* Fix: **„Stand nach Runde" blieb nach einem Wechsel der Liste stehen.** Wer
  ein Element von der Mannschaftstabelle nach Runde 3 auf die
  Mannschaftsliste oder die Ergebnisse umstellte, sah darüber weiter „Stand
  nach Runde 3" — und die Liste war tatsächlich zurückgesetzt. Die Maske
  blendet das Feld aus, der Wert stand aber noch im Datensatz. Jetzt wirkt
  jede Einstellung nur bei den Listen, zu denen sie gehört.
* Fix: **Swiss-Manager: Ausgeloste, aber noch nicht gespielte Wettkämpfe
  zählten als Unentschieden.** Die Mannschaftstabelle der Olympiade 2026 zeigte
  nach vier Runden fünf Wettkämpfe und für Kuba 9 statt 8 Mannschaftspunkte,
  weil Runde 5 schon ausgelost war. Ein Wettkampf ohne gewertetes Brett bleibt
  jetzt offen; die Tabelle stimmt mit chess-results überein.
* Add: Kästchen **„Zeile ‚Stand nach Runde' ausblenden"**, für Elemente, deren
  Überschrift die Runde schon nennt. Standard bleibt die Zeile sichtbar.
* Change: **Paarungen und Ergebnisse bei Mannschaftsturnieren neu gegliedert.**
  Statt eines Tabellenkopfs „Br. – Weiß – Elo – Schwarz – Elo" hat jeder
  Wettkampf seine eigene Kopfzeile: Brett, Mannschaft, Wertungszahl,
  Ergebnis, Mannschaft, Wertungszahl — bei Ländern mit Flagge. Darunter
  stehen die Bretter nach Mannschaften ausgerichtet; wer Schwarz führte,
  sitzt auf dunklerem Grund.
* Change: **Bretter zählen in jedem Wettkampf ab 1.** Swiss-Manager nummeriert
  über die ganze Runde durch; Tisch 2 einer Olympiade spielte bisher an den
  Brettern 5 bis 8.
* Change: **Spielernamen in Wettkämpfen als „Titel Vorname Nachname"** —
  „IM Marcin Molenda" statt „IM Molenda,Marcin".
* Add: **Auswahl der Mannschaften** für Paarungen, Ergebnisse und Wettkämpfe.
  Wer nur „Deutschland" wählt, sieht dessen Wettkämpfe samt Gegnern; Runden
  ohne gewählte Mannschaft entfallen.
* Add: **Fortschrittstabelle der Mannschaften**: je Runde die eigenen
  Brettpunkte, die Startnummer des Gegners und der Stand der
  Mannschaftspunkte.
* Change: **Kreuztabelle, Fortschrittstabelle und „Fortschritt ohne
  Punktestand" gibt es nur noch bei Einzelturnieren.** Bei
  Mannschaftsturnieren sagen sie wenig; ein bestehendes Element mit einer
  dieser Listen bleibt dort leer.

## Version 1.10.0 (2026-09-14)

* Fix: **Swiss-Manager: Mannschaftslisten brachen nach etwa der Hälfte ab.**
  Bei der Schacholympiade für Menschen mit Behinderung 2026 erschienen nur 20
  von 41 Mannschaften, die letzte davon unter dem Namen „Shermuhammadov,
  Samandar" — das ist der Mannschaftsführer von „Uzbekistan 1". Die
  Mannschaftskarte ist 26 Zeichenketten und 54 Byte lang, nicht 27 und 52;
  der Unterschied fällt nur auf, wenn in den beiden Byte etwas steht. Jetzt
  erscheinen alle 41 Mannschaften, und ein Hinweis meldet, wenn Spieler auf
  Mannschaften verweisen, die nicht gelesen werden konnten.
* Add: **Ländernamen werden in die Sprache der Seite übersetzt.** Aus
  „Poland", „Uzbekistan 2" und „Czech Republic" werden auf einer deutschen
  Seite „Polen", „Usbekistan 2" und „Tschechien" — in allen Listen und auch
  als Gegner in den Paarungen. Vereinsnamen und Verbände ohne Land wie „IBCA"
  bleiben unverändert. Am Flaggenfeld steht jetzt der Ländername mit Code als
  Titel, etwa „Polen (POL)".

* Change: **Die Rangliste beginnt mit anderen Spalten** — Platz, Titel, Name,
  Turnierwertungszahl, Verein, Punkte und die Feinwertungen. Zur Wahl stehen
  jetzt außerdem alle Spalten der Teilnehmerliste, also auch Gruppe,
  Geburtsjahr, FIDE-Kennung und Startnummer. Wer eigene Spalten eingestellt
  hat, behält sie.
* Fix: Die Spalte der Turnierwertungszahl hieß bei Swiss-Manager-Dateien mit
  nationalen Zahlen **„NWZ"**. Das ist die Bezeichnung von Swiss-Chess;
  chess-results schreibt für dieselbe Zahl „EloN", und in Deutschland heißt
  sie DWZ. Sie heißt jetzt **„DWZ"**. Bei SWT-Dateien bleibt es bei der
  Bezeichnung, die das Programm selbst führt.
* Change: **Punkte und Feinwertungen stehen mit Komma** — „7,5" statt „7½" und
  „7,0" statt „7". In einer Spalte, in der Zahlen untereinander verglichen
  werden, liest sich das besser, und so hält es auch chess-results. Eine
  zweite Nachkommastelle erscheint nur, wenn sie gebraucht wird:
  Sonneborn-Berger rechnet in Vierteln. Das ½ bleibt dort, wo eine Zahl für
  sich steht — in Ergebnislisten und Kreuztabellen.
* Change: **Die Föderation erscheint als Flagge.** Die Turnierdateien führen
  die dreibuchstabigen Kennungen des Weltschachbundes; sie werden auf die
  ISO-Kennungen abgebildet und als Flagge gesetzt. Der Code steht als Titel am
  Feld, und wo es keine Flagge gibt — „FID" für den Weltschachbund —, bleibt
  er stehen.
* Add: **Die Feinwertungseinstellung der Swiss-Manager-Dateien ist gefunden**,
  wenn auch noch nicht vollständig lesbar: Im Abschnitt `95` stehen die Zahl
  der Wertungen und ihre Schlüssel, sieben davon sind an Legenden von
  chess-results belegt. Zum Rechnen fehlt noch mindestens ein Merkmal je
  Eintrag — die Streichung steht woanders, und ein Schlüssel steht für zwei
  verschiedene Wertungen. Der Befund und der Weg zum nächsten Schritt stehen
  in `SWISS-MANAGER.md`. Bis dahin bleiben die Feinwertungsspalten bei
  Swiss-Manager leer, und Punktgleiche teilen sich den Platz.

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

### Ein Element je Ausgabe

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
* Change: Die Reiterleiste baut das Skript aus den Ausgaben, die es im
  Umschlag findet. Der Server kann sie nicht bauen: Das öffnende Element weiß
  beim Ausliefern nicht, was nach ihm kommt. Ohne JavaScript stehen die
  Ausgaben untereinander, jede mit ihrer Beschriftung und vollständig lesbar.
* Change: „Stand nach Runde" heißt in der Vorgabe jetzt „Aktueller Stand
  (letzte Runde)" — dieselbe Wirkung, aber die Bezeichnung sagt, was sie tut:
  Sie nimmt die gespeicherten Zahlen der Datei und wächst mit, wenn eine neue
  Fassung hochgeladen wird.

### Wählbare Spalten und Sortierung

* Add: **Wählbare Spalten für Teilnehmerliste und Rangliste.** Angeboten wird,
  was die gewählte Datei hergibt — ein Turnier ohne Elo-Zahlen bietet keine
  Elo-Spalte an, ein Einzelturnier keine Brettspalte, eine Datei ohne
  Feinwertung keine Feinwertungsspalte. Das Auswahlfeld lässt sich ziehen; die
  Reihenfolge ist die der Ausgabe. Die gebräuchlichen Spalten sind
  vorangehakt.
* Add: Neue Spalten, die es bisher nicht gab: Titel, Geburtsjahr,
  FIDE-Kennung, Gruppe, Land, Verein und die Zahl der Partien. Das Geburtsjahr
  kommt aus beiden Formaten — Swiss-Manager führt es als Zahl, der SWT-Leser
  als Datumstext.
* Change: Die Vorauswahl der Teilnehmerliste ist schlanker als die bisherige
  feste Spaltenfolge: Nr., Name, Turnierwertungszahl und Verein. Elo und DWZ
  lassen sich dazuhaken; alle drei Wertungszahlen nebeneinander machten die
  Tabelle auf schmalen Bildschirmen unlesbar.
* Add: **Sortierung im Frontend.** In Teilnehmerliste und Rangliste ordnet ein
  Klick auf den Spaltenkopf die Tabelle; ein zweiter dreht die Richtung um.
  Punktestände wie „3½" und Bilanzen wie „5/2/1" werden nach ihrem Zahlenwert
  geordnet, nicht als Text, und leere Felder stehen in beiden Richtungen am
  Ende. Die Köpfe sind mit der Tastatur erreichbar und melden über `aria-sort`,
  wonach geordnet ist. Ohne JavaScript steht die Tabelle in der Reihenfolge der
  Turnierdatei.
* Change: Nach Mannschaften gegliederte Tabellen sind nicht sortierbar — die
  Kopfzeilen der Mannschaften rutschten sonst zwischen die Spieler.

### Kleineres

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
