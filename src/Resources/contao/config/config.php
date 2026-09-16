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

/*
 * Backend-Modul für die Turnierausgaben, die über einen Inserttag eingebunden
 * werden. Es steht in derselben Gruppe wie die übrigen Schach-Module.
 */
$GLOBALS['BE_MOD']['schach']['ctv_inserttag'] = [
    'tables' => ['tl_ctv_inserttag'],
];

/*
 * Das Model, mit dem der Inserttag seinen Datensatz findet. Ohne diesen
 * Eintrag kennt Contao die Tabelle nicht und `findByIdOrAlias()` liefert
 * nichts.
 */
$GLOBALS['TL_MODELS']['tl_ctv_inserttag'] = Schachbulle\ContaoChesstournamentviewerBundle\Model\CtvInserttagModel::class;
