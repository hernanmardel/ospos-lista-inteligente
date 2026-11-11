<?php

namespace App\Plugins\ListaInteligentePlugin\Libraries;

use Config\Database;
use Config\Services;
use Throwable;

/**
 * Everything that reads items and talks to Lista Inteligente, in one place.
 * Read-only on the OSPOS database.
 */
class Envio
{
    /** Fixed: the plugin never sends data anywhere else. */
    public const SERVER = 'https://compra-inteligente.duckdns.org/carga/api/ospos';

    /**
     * Lista Inteligente compares against Argentine supermarkets only: available
     * when OSPOS is set to currency ARS or an America/Argentina timezone.
     */
    public static function available(): bool
    {
        $rows = Database::connect()->table('app_config')
            ->whereIn('key', ['currency_code', 'timezone'])
            ->get()->getResultArray();
        $config = array_column($rows, 'value', 'key');

        return strtoupper(trim($config['currency_code'] ?? '')) === 'ARS'
            || str_starts_with($config['timezone'] ?? '', 'America/Argentina');
    }

    /**
     * Active items with a barcode: barcode, name and FINAL retail price (what the
     * customer pays). If "Tax Included" is off, Retail Price excludes taxes and
     * OSPOS adds them at checkout, so the item's taxes are added here too.
     *
     * @param int[]|null $itemIds Only these items (null = all).
     */
    public static function items(?array $itemIds = null): array
    {
        $db = Database::connect();

        $builder = $db->table('items')
            ->select('item_id, item_number, name, unit_price')
            ->where('deleted', 0)
            ->where('item_number IS NOT NULL')
            ->where("item_number <> ''");
        if ($itemIds !== null) {
            if ($itemIds === []) {
                return [];
            }
            $builder->whereIn('item_id', array_map('intval', $itemIds));
        }
        $items = $builder->get()->getResultArray();

        $taxIncluded = (bool) ($db->table('app_config')->where('key', 'tax_included')->get()->getRowArray()['value'] ?? 0);
        $taxes = [];
        if (! $taxIncluded && $items !== []) {
            $rows = $db->table('items_taxes')
                ->select('item_id, SUM(percent) AS total')
                ->whereIn('item_id', array_column($items, 'item_id'))
                ->groupBy('item_id')
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $taxes[(int) $row['item_id']] = (float) $row['total'];
            }
        }

        $result = [];
        foreach ($items as $item) {
            $price = (float) $item['unit_price'] * (1 + ($taxes[(int) $item['item_id']] ?? 0) / 100);
            $result[] = [$item['item_number'], $item['name'], number_format($price, 2, '.', '')];
        }

        return $result;
    }

    public static function csv(array $rows): string
    {
        $file = fopen('php://temp', 'r+');
        fputcsv($file, ['codigo', 'nombre', 'precio']);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        rewind($file);
        $data = stream_get_contents($file);
        fclose($file);

        return $data;
    }

    /**
     * Posts the CSV to Lista Inteligente. Returns the decoded JSON response, or
     * ['error' => ...] when the server cannot be reached.
     */
    public static function send(string $path, string $csv, string $installationId, int $timeout = 120): array
    {
        try {
            $response = Services::curlrequest(['timeout' => $timeout, 'http_errors' => false])
                ->post(self::SERVER . $path . '?origen=ospos&nombre=articulos.csv', [
                    'body'    => $csv,
                    'headers' => [
                        'Content-Type'  => 'text/csv',
                        'X-Instalacion' => $installationId,
                    ],
                ]);
            $json = json_decode((string) $response->getBody(), true);

            return is_array($json) ? $json : ['error' => lang('ListaInteligentePlugin.cannot_connect')];
        } catch (Throwable $e) {
            log_message('error', 'Lista Inteligente: ' . $e->getMessage());

            return ['error' => lang('ListaInteligentePlugin.cannot_connect')];
        }
    }
}
