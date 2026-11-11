<?php
/**
 * @var bool $available Store configured for Argentina (ARS / Argentine timezone).
 * @var bool $consent   The user already accepted what is sent.
 * @var bool $autoSync  Automatic updates on item save.
 * @var int  $itemCount Active items with a barcode.
 */
?>
<?= view('partial/header') ?>

<div class="container" style="max-width: 760px; margin-top: 20px;">
    <h3><?= lang('ListaInteligentePlugin.title') ?></h3>
    <p><?= lang('ListaInteligentePlugin.intro') ?></p>

    <?php if (! $available): ?>
        <div class="alert alert-warning"><?= lang('ListaInteligentePlugin.only_argentina') ?></div>
    <?php else: ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <p><b><?= lang('ListaInteligentePlugin.what_is_sent_title') ?></b></p>
                <p><?= lang('ListaInteligentePlugin.what_is_sent') ?></p>
                <p class="text-muted"><?= lang('ListaInteligentePlugin.item_count', [$itemCount]) ?></p>

                <?php if (! $consent): ?>
                    <div class="checkbox">
                        <label><input type="checkbox" id="li_consent" value="1"> <?= lang('ListaInteligentePlugin.consent_label') ?></label>
                    </div>
                <?php endif; ?>

                <button type="button" class="btn btn-success" id="li_compare" <?= $itemCount === 0 ? 'disabled' : '' ?>>
                    <span class="glyphicon glyphicon-shopping-cart">&nbsp;</span><?= lang('ListaInteligentePlugin.compare_button') ?>
                </button>
                <p class="help-block"><?= lang('ListaInteligentePlugin.compare_help') ?></p>
            </div>
        </div>

        <p class="text-muted">
            <?= lang($autoSync ? 'ListaInteligentePlugin.auto_sync_on' : 'ListaInteligentePlugin.auto_sync_off') ?>
        </p>
    <?php endif; ?>
</div>

<?php if ($available): ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#li_compare').click(function() {
            var $btn = $(this);
            var $consent = $('#li_consent');
            if ($consent.length && !$consent.is(':checked')) {
                $.notify({ message: <?= json_encode(lang('ListaInteligentePlugin.consent_required')) ?> }, { type: 'warning' });
                return;
            }

            // Open the tab now: opened after the response, the browser blocks it.
            var tab = window.open('', '_blank');
            if (tab) {
                tab.document.write('<p style="font-family:sans-serif;padding:24px;font-size:18px">' +
                    <?= json_encode(lang('ListaInteligentePlugin.comparing')) ?> + '</p>');
            }
            $btn.prop('disabled', true);

            // The CSRF token is appended by the $.ajax wrapper in partial/header_js.
            $.post('<?= site_url('listainteligente/compare') ?>', { consent: $consent.is(':checked') ? 1 : 0 },
                function(response) {
                    if (response.success && response.url) {
                        if (tab) { tab.location = response.url; } else { window.location = response.url; }
                        $consent.closest('.checkbox').remove();
                    } else {
                        if (tab) { tab.close(); }
                        $.notify({ message: response.message }, { type: 'danger' });
                    }
                }, 'json'
            ).fail(function() {
                if (tab) { tab.close(); }
                $.notify({ message: <?= json_encode(lang('ListaInteligentePlugin.cannot_connect')) ?> }, { type: 'danger' });
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
    });
</script>
<?php endif; ?>

<?= view('partial/footer') ?>
