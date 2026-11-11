<?php

namespace App\Plugins\ListaInteligentePlugin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves the menu icon. Not a Secure_Controller, so the browser can load it
 * without an auth redirect (as in the plugin README).
 */
class IconController extends BaseController
{
    public function getIcon(): ResponseInterface
    {
        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'icon.svg'));
    }
}
