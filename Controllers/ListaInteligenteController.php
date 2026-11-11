<?php

namespace App\Plugins\ListaInteligentePlugin\Controllers;

use App\Controllers\Secure_Controller;
use App\Plugins\ListaInteligentePlugin\Libraries\Envio;
use App\Plugins\ListaInteligentePlugin\ListaInteligentePlugin;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The plugin's page (office menu): what is sent, consent and "Compare now".
 */
class ListaInteligenteController extends Secure_Controller
{
    private ListaInteligentePlugin $plugin;

    public function __construct()
    {
        parent::__construct('listainteligente');
        $this->plugin = new ListaInteligentePlugin();
    }

    public function getIndex(): string
    {
        return view('App\Plugins\ListaInteligentePlugin\Views\index', [
            'available' => Envio::available(),
            'consent'   => $this->plugin->hasConsent(),
            'autoSync'  => $this->plugin->getSettings()['auto_sync'] === '1',
            'itemCount' => count(Envio::items()),
        ]);
    }

    /**
     * Sends the active items and returns the link to the comparison page.
     * POST, so it stays behind the CSRF filter.
     */
    public function postCompare(): ResponseInterface
    {
        if (! Envio::available()) {
            return $this->response->setJSON(['success' => false, 'message' => lang('ListaInteligentePlugin.only_argentina')]);
        }
        if (! $this->plugin->hasConsent()) {
            if (! $this->request->getPost('consent')) {
                return $this->response->setJSON(['success' => false, 'message' => lang('ListaInteligentePlugin.consent_required')]);
            }
            $this->plugin->giveConsent();
        }

        $rows = Envio::items();
        if ($rows === []) {
            return $this->response->setJSON(['success' => false, 'message' => lang('ListaInteligentePlugin.no_items')]);
        }

        $response = Envio::send('', Envio::csv($rows), $this->plugin->installationId());
        if (empty($response['url'])) {
            return $this->response->setJSON(['success' => false, 'message' => $response['error'] ?? lang('ListaInteligentePlugin.cannot_connect')]);
        }

        return $this->response->setJSON(['success' => true, 'url' => $response['url']]);
    }
}
