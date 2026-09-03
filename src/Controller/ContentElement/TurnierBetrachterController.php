<?php

declare(strict_types=1);

/*
 * Contao Chesstournamentviewer Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoChesstournamentviewerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\StringUtil;
use Contao\Template;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\Auswahl;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\ListenBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Rundenschnitt;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\TurnierLader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inhaltselement, das eine Turnierdatei im Frontend ausgibt.
 *
 * Registriert wird das Element über den Dienst-Tag `contao.content_element`
 * in der services.yaml und nicht über ein Attribut: Der Tag wirkt unter
 * Contao 4.13 genauso wie unter Contao 5, das Attribut gibt es erst ab
 * Contao 5.
 */
class TurnierBetrachterController extends AbstractContentElementController
{
    /**
     * Erzeugt den Controller.
     *
     * @param TurnierLader    $lader        Liest die Turnierdatei aus der Dateiverwaltung
     * @param ListenBauer     $listenBauer  Stellt die Daten der einzelnen Listen zusammen
     * @param TokenChecker    $tokenChecker Wird gefragt, ob ein Backend-Benutzer angemeldet
     *                                      ist; nur diesem werden Fehlermeldungen gezeigt
     * @param LoggerInterface $logger       Schreibt Lesefehler ins Contao-Fehlerprotokoll
     */
    public function __construct(
        private readonly TurnierLader $lader,
        private readonly ListenBauer $listenBauer,
        private readonly TokenChecker $tokenChecker,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Erzeugt die Ausgabe des Inhaltselements.
     *
     * Der Rückgabetyp `Response` und der Parametertyp `Template` erfüllen die
     * abstrakten Signaturen beider Contao-Fassungen: PHP erlaubt beim
     * Überschreiben einen breiteren Parametertyp — `Template` ist die
     * Elternklasse des in Contao 5 verwendeten `FragmentTemplate` — und einen
     * engeren Rückgabetyp als das dort deklarierte `?Response`.
     *
     * Scheitert das Lesen der Datei, bleibt die Ausgabe für Besucher leer;
     * der Grund steht im Fehlerprotokoll und wird zusätzlich angezeigt, wenn
     * ein Backend-Benutzer angemeldet ist. Ein Besucher kann mit „Datei nicht
     * gefunden" nichts anfangen, der Redakteur sehr wohl.
     *
     * @param Template     $template Das Template des Inhaltselements
     * @param ContentModel $model    Der Datensatz des Inhaltselements
     * @param Request      $request  Die laufende Anfrage
     *
     * @return Response Die Antwort mit den gewählten Listen, oder eine leere
     *                  Antwort, wenn nichts auszugeben ist
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $template->fehler = null;
        $template->turnier = null;
        $template->listen = [];
        $template->hinweise = [];

        try {
            $turnier = $this->lader->lade($model->ctvDatei, (string) ($model->ctvFormat ?: 'auto'));
        } catch (\Throwable $ausnahme) {
            $this->logger->error(
                sprintf('Turnierdatei des Inhaltselements ID %s konnte nicht gelesen werden: %s', $model->id, $ausnahme->getMessage())
            );

            if (!$this->tokenChecker->hasBackendUser()) {
                return new Response();
            }

            $template->fehler = $ausnahme->getMessage();

            return $template->getResponse();
        }

        $auswahl = new Auswahl(
            StringUtil::deserialize($model->ctvListen, true),
            (bool) $model->ctvMannschaftSpieler,
            (bool) $model->ctvKreuzKurz,
            (int) $model->ctvStand,
            array_map('intval', StringUtil::deserialize($model->ctvRunden, true)),
            [
                'teilnehmer' => StringUtil::deserialize($model->ctvSpaltenTeilnehmer, true),
                'rangliste' => StringUtil::deserialize($model->ctvSpaltenRangliste, true),
            ],
        );

        // Der Rundenschnitt versetzt das Turnier zurück; von da an gelten
        // dessen Zahlen, auch für Kopfdaten und Hinweise.
        if ($auswahl->stand > 0) {
            $turnier = Rundenschnitt::bis($turnier, $auswahl->stand);
        }

        $listen = $this->listenBauer->baue($turnier, $auswahl);

        if ([] === $listen) {
            return new Response();
        }

        $this->bindeDateienEin();

        $template->turnier = $turnier;
        $template->kopf = $turnier->getKopf();
        $template->listen = $listen;

        // Die Hinweise erklären, warum Zahlen auseinandergehen können. Auf
        // einer Vereinsseite ist das oft mehr, als der Besucher wissen will;
        // deshalb erscheinen sie nur auf Wunsch.
        $template->hinweise = $model->ctvHinweise ? $turnier->getHinweise() : [];

        // Die Reiternavigation lohnt erst ab zwei Listen. Bei einer einzigen
        // Liste bliebe eine Lasche übrig, die nichts umschalten kann.
        $template->mitReitern = \count($listen) > 1;
        $template->kennung = 'ctv-'.$model->id;

        // Der Zwischenstand steht unabhängig von den Hinweisen über der
        // Ausgabe: Eine Tabelle nach Runde 4 sähe sonst aus wie die
        // Endtabelle, und niemand könnte den Unterschied erkennen.
        $template->stand = (int) $turnier->kopf('standNachRunde', 0);

        return $template->getResponse();
    }

    /**
     * Bindet Stilvorlage und Skript des Betrachters ein.
     *
     * Beide bekommen einen festen Schlüssel im Array, damit sie bei mehreren
     * Betrachtern auf einer Seite nur einmal ausgegeben werden. Der Zusatz
     * `|static` hält sie aus der Zusammenfassung dynamischer Dateien heraus.
     *
     * @return void
     */
    private function bindeDateienEin(): void
    {
        $GLOBALS['TL_CSS']['ctv'] = 'bundles/contaochesstournamentviewer/css/betrachter.css|static';
        $GLOBALS['TL_JAVASCRIPT']['ctv'] = 'bundles/contaochesstournamentviewer/js/betrachter.js|static';
    }
}
