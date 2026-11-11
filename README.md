# Lista Inteligente Plugin

Compares a store's prices with the supermarkets of its province in Argentina
(official price program Precios Claros / SEPA) and lets the store publish them in
[Lista Inteligente](https://www.listainteligente.com.ar), a free price-comparison app.

## What it adds

- **Office menu → Lista Inteligente**: a page that explains what is sent, asks for
  consent once, and has a **Compare with supermarkets** button. It opens the
  comparison in a new tab (no sign-up needed). Publishing is a separate, opt-in step
  on that page.
- **Automatic updates** (optional, off by default, in the plugin settings): on
  `item_saved` the changed items are sent, so published prices stay up to date.
  The server ignores them until the store has published at least once.

## Data sent

Only **barcode, item name and final retail price** of active items (if Tax Included
is off, the item's taxes are added, as at checkout). Never costs, stock, customers
or sales. Data is sent only to `https://compra-inteligente.duckdns.org` (fixed in
`Libraries/Envio.php`), and only after explicit consent.

## Requirements

- OSPOS configured for Argentina: currency **ARS** or an `America/Argentina/*`
  timezone (Office → Configuration → Localization). Otherwise the page shows a
  notice and nothing is sent.

## Installation

1. Copy the `ListaInteligentePlugin` folder into `app/Plugins/`.
2. Enable it in the Plugins menu.
3. Open **Lista Inteligente** in the Office menu.

## Uninstalling

Disable, then Uninstall in the Plugins menu (removes its settings and menu entry),
and delete the folder. The plugin creates no tables.

Support: listacomprarinteligente@gmail.com (not the opensourcepos project).
