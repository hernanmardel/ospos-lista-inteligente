<?php

namespace App\Plugins\ListaInteligentePlugin;

use App\Libraries\Plugins\BasePlugin;
use App\Plugins\ListaInteligentePlugin\Libraries\Envio;
use CodeIgniter\Events\Events;
use Throwable;

/**
 * Lista Inteligente (https://www.listainteligente.com.ar) compares a store's
 * prices with the supermarkets of its province (Argentina's official price
 * program, Precios Claros / SEPA) and lets the store publish them in the app.
 *
 * Only for stores in Argentina (currency ARS or an America/Argentina timezone).
 * Sends ONLY barcode, item name and final retail price of active items, and only
 * after the user's explicit consent. No costs, stock, customers or sales.
 *
 * Copyright (c) 2026 Hernán Arroyo — support is provided by the plugin author,
 * not by opensourcepos.
 */
class ListaInteligentePlugin extends BasePlugin
{
    /** Allowlist for saveSettings(): a crafted form post cannot write other keys. */
    private const EDITABLE_SETTINGS = ['auto_sync'];

    public function getPluginId(): string
    {
        return 'listainteligente';
    }

    public function getPluginName(): string
    {
        return 'Lista Inteligente';
    }

    public function getPluginDescription(): string
    {
        return lang('ListaInteligentePlugin.description');
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function registerEvents(): void
    {
        Events::on('item_saved', [$this, 'onItemSaved']);
        Events::on('view:module_icon_listainteligente', [$this, 'injectModuleIcon']);
    }

    public function install(): bool
    {
        $this->log('info', 'Installing Lista Inteligente plugin');

        // A random id for this OSPOS install. It is linked to the store the first
        // time the owner signs in from the comparison page, so later uploads and
        // automatic updates need no API key.
        if ($this->getSetting('installation_id', '') === '') {
            $this->setSetting('installation_id', bin2hex(random_bytes(16)));
        }
        $this->setSetting('consent', $this->getSetting('consent', '0'));
        $this->setSetting('auto_sync', $this->getSetting('auto_sync', '0'));

        $this->registerModule('listainteligente', 150, 'office');

        return true;
    }

    public function getConfigView(): ?string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'config';
    }

    public function getSettings(): array
    {
        return [
            'auto_sync' => $this->getSetting('auto_sync', '0'),
            'consent'   => $this->getSetting('consent', '0'),
        ];
    }

    public function getConfigViewData(): array
    {
        return ['available' => Envio::available()];
    }

    public function saveSettings(array $settings): bool
    {
        $normalized = [];
        foreach (self::EDITABLE_SETTINGS as $key) {
            $normalized[$key] = empty($settings[$key]) ? '0' : '1';
        }

        return parent::saveSettings($normalized);
    }

    public function installationId(): string
    {
        return (string) $this->getSetting('installation_id', '');
    }

    public function hasConsent(): bool
    {
        return $this->getSetting('consent', '0') === '1';
    }

    public function giveConsent(): void
    {
        $this->setSetting('consent', '1');
    }

    public function injectModuleIcon(): void
    {
        echo $this->renderView('module_icon');
    }

    /**
     * Keeps published prices up to date: when items are saved (form or CSV
     * import), sends those items. Only with consent, with automatic sync turned
     * on, in Argentina, and only once this OSPOS is linked to a store (the server
     * ignores it otherwise). Never blocks or breaks the item save.
     */
    public function onItemSaved(array $itemIds, array $pluginData = []): void
    {
        if ($this->getSetting('auto_sync', '0') !== '1' || ! $this->hasConsent() || ! Envio::available()) {
            return;
        }

        try {
            $rows = Envio::items($itemIds);
            if ($rows === []) {
                return;
            }
            $response = Envio::send('/articulos', Envio::csv($rows), $this->installationId(), 10);
            if (empty($response['ok'])) {
                $this->log('info', 'Automatic update not applied: ' . ($response['motivo'] ?? $response['error'] ?? 'no response'));
            }
        } catch (Throwable $e) {
            $this->log('error', 'Automatic update failed: ' . $e->getMessage());
        }
    }
}
