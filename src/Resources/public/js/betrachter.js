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
     * Rüstet einen Betrachter mit der Reiternavigation aus.
     *
     * @param {HTMLElement} behaelter Der Betrachter
     */
    function ruesteAus(behaelter) {
        var knoepfe = behaelter.querySelectorAll('.ctv-reiter__knopf');

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
     * Sucht alle Betrachter der Seite und rüstet sie aus.
     */
    function start() {
        var behaelter = document.querySelectorAll('.ctv[data-ctv-reiter]');

        for (var i = 0; i < behaelter.length; i++) {
            ruesteAus(behaelter[i]);
        }
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
