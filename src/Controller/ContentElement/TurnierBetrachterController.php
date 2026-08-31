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
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Template;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoChesstournamentviewerBundle\Format\FormatVerzeichnis;
use Schachbulle\ContaoChesstournamentviewerBundle\Liste\ListenBauer;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Mannschaftswertung;
use Schachbulle\ContaoChesstournamentviewerBundle\Turnier\Turnier;
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
     * Höchstgröße einer Turnierdatei in Byte.
     *
     * Turnierdateien sind klein — die größte im geprüften Bestand liegt bei
     * gut 200 Kilobyte. Die Schranke verhindert, dass eine versehentlich
     * ausgewählte Datei den Speicher füllt, bevor der Leser sie ablehnt.
     */
    private const HOECHSTGROESSE = 8 * 1024 * 1024;

    /**
     * Erzeugt den Controller.
     *
     * @param FormatVerzeichnis $formate      Kennt alle Turnierformate und wählt das passende
     * @param ListenBauer       $listenBauer  Stellt die Daten der einzelnen Listen zusammen
     * @param TokenChecker      $tokenChecker Wird gefragt, ob ein Backend-Benutzer angemeldet
     *                                        ist; nur diesem werden Fehlermeldungen gezeigt
     * @param LoggerInterface   $logger       Schreibt Lesefehler ins Contao-Fehlerprotokoll
     * @param string            $projectDir   Wurzelverzeichnis der Installation
     */
    public function __construct(
        private readonly FormatVerzeichnis $formate,
        private readonly ListenBauer $listenBauer,
        private readonly TokenChecker $tokenChecker,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
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
            $turnier = $this->leseTurnier($model);
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

        $gewaehlt = StringUtil::deserialize($model->ctvListen, true);
        $mitSpielern = (bool) $model->ctvMannschaftSpieler;
        $listen = $this->listenBauer->baue($turnier, $gewaehlt, $mitSpielern);

        if ([] === $listen) {
            return new Response();
        }

        $this->bindeDateienEin();

        $template->turnier = $turnier;
        $template->kopf = $turnier->getKopf();
        $template->listen = $listen;
        $template->hinweise = array_merge($turnier->getHinweise(), Mannschaftswertung::hinweise($turnier));

        // Die Reiternavigation lohnt erst ab zwei Listen. Bei einer einzigen
        // Liste bliebe eine Lasche übrig, die nichts umschalten kann.
        $template->mitReitern = \count($listen) > 1;
        $template->kennung = 'ctv-'.$model->id;

        return $template->getResponse();
    }

    /**
     * Liest die im Inhaltselement hinterlegte Turnierdatei ein.
     *
     * @param ContentModel $model Der Datensatz des Inhaltselements
     *
     * @return Turnier Das ausgewertete Turnier
     *
     * @throws \RuntimeException wenn keine Datei hinterlegt ist, die Datei
     *                           fehlt, zu groß ist oder von keinem Format
     *                           gelesen werden kann
     */
    private function leseTurnier(ContentModel $model): Turnier
    {
        if (!$model->ctvDatei) {
            throw new \RuntimeException('Dem Inhaltselement ist keine Turnierdatei zugeordnet.');
        }

        $filesAdapter = $this->getContaoAdapter(FilesModel::class);
        $datei = $filesAdapter->findByUuid($model->ctvDatei);

        if (null === $datei) {
            throw new \RuntimeException('Die zugeordnete Turnierdatei steht nicht mehr in der Dateiverwaltung.');
        }

        $pfad = $this->projectDir.'/'.$datei->path;

        if (!is_file($pfad) || !is_readable($pfad)) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" ist nicht lesbar.', $datei->path));
        }

        if (filesize($pfad) > self::HOECHSTGROESSE) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" ist größer als %d Byte.', $datei->path, self::HOECHSTGROESSE));
        }

        $inhalt = file_get_contents($pfad);

        if (false === $inhalt) {
            throw new \RuntimeException(sprintf('Die Turnierdatei "%s" konnte nicht gelesen werden.', $datei->path));
        }

        return $this->formate->lese(basename($datei->path), $inhalt, (string) ($model->ctvFormat ?: 'auto'));
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
