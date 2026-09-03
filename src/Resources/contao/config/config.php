<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Die beiden Hüllen als Umschlag anmelden.
 *
 * Contao rückt die eingeschlossenen Inhaltselemente daraufhin im Backend ein
 * und zeigt sie als zusammengehörig — dieselbe Darstellung wie bei Akkordeon
 * und Slider. Ohne diesen Eintrag stünden die Elemente unverbunden
 * untereinander, und niemand sähe, was zu welchem Umschlag gehört.
 *
 * Der Eintrag ist in Contao 4.13 und Contao 5 derselbe.
 */
$GLOBALS['TL_WRAPPERS']['start'][] = 'chesstournamentviewerStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'chesstournamentviewerStop';
