/*
 * Turnier-Betrachter — Reiternavigation.
 *
 * Das Skript kommt ohne Bibliothek aus und ändert am Markup nichts, was der
 * Server nicht schon geliefert hätte: Es setzt lediglich Klassen und die
 * ARIA-Merkmale um.
 *
 * Wichtig ist die Reihenfolge. Der Server liefert alle Listen sichtbar aus;
 * erst wenn dieses Skript läuft, bekommt der Behälter die Klasse
 * `ctv--bereit`, und erst die Stilvorlage blendet daraufhin alle Listen bis
 * auf die erste aus. Ohne JavaScript bleibt also alles lesbar, nur eben
 * untereinander statt hinter Reitern.
 */
(function () {
    'use strict';

    /**
     * Schaltet innerhalb eines Betrachters auf eine Liste um.
     *
     * @param {HTMLElement} behaelter Der Betrachter
     * @param {string}      kennung   Die ID der anzuzeigenden Liste
     */
    function zeige(behaelter, kennung) {
        var knoepfe = behaelter.querySelectorAll('.ctv-reiter__knopf');
        var listen = behaelter.querySelectorAll('.ctv-liste');
        var i;

        for (i = 0; i < knoepfe.length; i++) {
            var aktiv = knoepfe[i].getAttribute('aria-controls') === kennung;
            knoepfe[i].classList.toggle('ctv-reiter__knopf--aktiv', aktiv);
            knoepfe[i].setAttribute('aria-selected', aktiv ? 'true' : 'false');
        }

        for (i = 0; i < listen.length; i++) {
            listen[i].classList.toggle('ctv-liste--aktiv', listen[i].id === kennung);
        }
    }

    /**
     * Baut die Reiterlaschen aus den Ausgaben, die im Umschlag stehen.
     *
     * Der Umschlag weiß beim Ausliefern nicht, was in ihm steht — er ist ein
     * eigenes Inhaltselement und wird vor den Ausgaben gerendert. Deshalb
     * werden die Laschen hier gebaut: aus den Abschnitten, die im fertigen
     * HTML zwischen Anfang und Ende liegen. Ihre Beschriftung steht am
     * Abschnitt selbst.
     *
     * @param {HTMLElement} behaelter Der Umschlag
     *
     * @return {NodeList} Die erzeugten Laschen
     */
    function baueReiter(behaelter) {
        var leiste = behaelter.querySelector('.ctv-reiter');
        var listen = behaelter.querySelectorAll('.ctv-liste');

        if (!leiste || listen.length < 2) {
            return behaelter.querySelectorAll('.ctv-reiter__knopf');
        }

        for (var i = 0; i < listen.length; i++) {
            // Ohne eigene Kennung liesse sich die Lasche nicht zuordnen.
            if (!listen[i].id) {
                listen[i].id = behaelter.id + '-liste-' + i;
            }

            var knopf = document.createElement('button');
            knopf.type = 'button';
            knopf.className = 'ctv-reiter__knopf';
            knopf.setAttribute('role', 'tab');
            knopf.setAttribute('aria-controls', listen[i].id);
            knopf.setAttribute('aria-selected', 'false');
            knopf.textContent = listen[i].getAttribute('data-ctv-name') || listen[i].id;

            leiste.appendChild(knopf);
        }

        return behaelter.querySelectorAll('.ctv-reiter__knopf');
    }

    /**
     * Rüstet einen Umschlag mit der Reiternavigation aus.
     *
     * @param {HTMLElement} behaelter Der Umschlag
     */
    function ruesteAus(behaelter) {
        var knoepfe = baueReiter(behaelter);

        if (knoepfe.length < 2) {
            return;
        }

        behaelter.classList.add('ctv--bereit');

        for (var i = 0; i < knoepfe.length; i++) {
            knoepfe[i].addEventListener('click', function (ereignis) {
                zeige(behaelter, ereignis.currentTarget.getAttribute('aria-controls'));
            });

            /*
             * Pfeiltasten wechseln den Reiter, wie es für eine Reiterleiste
             * erwartet wird. Der Wechsel setzt zugleich den Tastaturfokus,
             * sonst bliebe er auf dem alten Reiter stehen.
             */
            knoepfe[i].addEventListener('keydown', function (ereignis) {
                var richtung = 0;

                if ('ArrowRight' === ereignis.key) {
                    richtung = 1;
                } else if ('ArrowLeft' === ereignis.key) {
                    richtung = -1;
                } else {
                    return;
                }

                ereignis.preventDefault();

                var alle = Array.prototype.slice.call(behaelter.querySelectorAll('.ctv-reiter__knopf'));
                var jetzt = alle.indexOf(ereignis.currentTarget);
                var naechster = alle[(jetzt + richtung + alle.length) % alle.length];

                naechster.focus();
                zeige(behaelter, naechster.getAttribute('aria-controls'));
            });
        }

        zeige(behaelter, knoepfe[0].getAttribute('aria-controls'));
    }

    /**
     * Ermittelt den Wert, nach dem eine Zelle einzuordnen ist.
     *
     * Steht am Feld ein `data-wert`, gilt der: Ein Punktestand wie „3½" und
     * eine Bilanz wie „5/2/1" sind als Text nicht zu ordnen. Sonst zählt der
     * sichtbare Inhalt — als Zahl, wenn die Spalte eine Zahlenspalte ist.
     *
     * Leere Zellen ergeben null. Sie stehen später immer am Ende, in beiden
     * Richtungen: Wer nach Wertungszahl ordnet, will die Spieler ohne Zahl
     * nicht vorn haben, gleichgültig ob auf- oder absteigend.
     *
     * @param {HTMLTableCellElement} feld  Die Zelle
     * @param {boolean}              zahl  Ob die Spalte Zahlen führt
     *
     * @return {number|string|null} Der Sortierwert, oder null wenn leer
     */
    function wertVon(feld, zahl) {
        if (!feld) {
            return null;
        }

        var roh = feld.getAttribute('data-wert');

        if (null !== roh) {
            return parseFloat(roh) || 0;
        }

        var text = (feld.textContent || '').trim();

        if ('' === text) {
            return null;
        }

        return zahl ? (parseFloat(text.replace(',', '.')) || 0) : text.toLowerCase();
    }

    /**
     * Ordnet die Zeilen einer Tabelle nach einer Spalte.
     *
     * Sortiert wird stabil und nur innerhalb des Tabellenkörpers; Kopf- und
     * Fußzeilen bleiben, wo sie sind. Ein zweiter Klick auf dieselbe Spalte
     * dreht die Richtung um.
     *
     * @param {HTMLTableElement} tabelle Die Tabelle
     * @param {number}           spalte  Nummer der Spalte, ab 0
     */
    function sortiere(tabelle, spalte) {
        var koepfe = tabelle.querySelectorAll('thead th');
        var kopf = koepfe[spalte];
        var koerper = tabelle.tBodies[0];

        if (!kopf || !koerper) {
            return;
        }

        var absteigend = 'ascending' === kopf.getAttribute('aria-sort');
        var zahl = '1' === kopf.getAttribute('data-ctv-zahl');
        var zeilen = Array.prototype.slice.call(koerper.rows);

        zeilen.sort(function (a, b) {
            var links = wertVon(a.cells[spalte], zahl);
            var rechts = wertVon(b.cells[spalte], zahl);

            // Leere Zellen ans Ende, unabhaengig von der Richtung.
            if (null === links || null === rechts) {
                if (links === rechts) {
                    return 0;
                }

                return null === links ? 1 : -1;
            }

            if (links === rechts) {
                return 0;
            }

            var kleiner = 'string' === typeof links
                ? links.localeCompare(rechts) < 0
                : links < rechts;

            return (kleiner ? -1 : 1) * (absteigend ? -1 : 1);
        });

        for (var i = 0; i < zeilen.length; i++) {
            koerper.appendChild(zeilen[i]);
        }

        for (var k = 0; k < koepfe.length; k++) {
            koepfe[k].removeAttribute('aria-sort');
        }

        kopf.setAttribute('aria-sort', absteigend ? 'descending' : 'ascending');
    }

    /**
     * Macht die Spaltenköpfe einer Tabelle anklickbar.
     *
     * Der Kopf wird dabei zur Schaltfläche im Sinne der Bedienung: Er ist mit
     * der Tastatur erreichbar und meldet über `aria-sort`, wonach gerade
     * geordnet ist. Ohne JavaScript passiert nichts von alledem — die Tabelle
     * steht dann in der Reihenfolge der Turnierdatei, und das ist die
     * richtige Rückfallebene.
     *
     * @param {HTMLTableElement} tabelle Die Tabelle
     */
    function ruesteTabelleAus(tabelle) {
        var koepfe = tabelle.querySelectorAll('thead th');

        var klick = function (nummer) {
            return function () {
                sortiere(tabelle, nummer);
            };
        };

        for (var i = 0; i < koepfe.length; i++) {
            koepfe[i].classList.add('ctv-sortierbar');
            koepfe[i].setAttribute('tabindex', '0');
            koepfe[i].setAttribute('role', 'columnheader');
            koepfe[i].addEventListener('click', klick(i));
            koepfe[i].addEventListener('keydown', (function (nummer) {
                return function (ereignis) {
                    if ('Enter' === ereignis.key || ' ' === ereignis.key) {
                        ereignis.preventDefault();
                        sortiere(tabelle, nummer);
                    }
                };
            })(i));
        }
    }

    /**
     * Sucht alle Betrachter der Seite und rüstet sie aus.
     */
    function start() {
        var behaelter = document.querySelectorAll('.ctv[data-ctv-reiter]');

        for (var i = 0; i < behaelter.length; i++) {
            ruesteAus(behaelter[i]);
        }

        var tabellen = document.querySelectorAll('.ctv table[data-ctv-sortierbar]');

        for (var t = 0; t < tabellen.length; t++) {
            ruesteTabelleAus(tabellen[t]);
        }
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
