<?php
/**
 * @var array $settings
 * @var bool  $available
 */
?>

<?= form_open(site_url('plugins/saveConfig/listainteligente'), ['id' => 'config_form', 'class' => 'form-horizontal']) ?>
<div id="config_wrapper">
    <fieldset id="config_info">
        <div id="plugins_header"><?= lang('ListaInteligentePlugin.configuration') ?></div>
        <ul id="error_message_box" class="error_message_box"></ul>

        <?php if (empty($available)): ?>
            <div class="alert alert-warning"><?= lang('ListaInteligentePlugin.only_argentina') ?></div>
        <?php endif; ?>

        <div class="form-group form-group-sm">
            <div class="col-xs-offset-1 col-xs-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="auto_sync" value="1" <?= ($settings['auto_sync'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <?= lang('ListaInteligentePlugin.auto_sync_label') ?>
                    </label>
                </div>
                <span class="help-block"><?= lang('ListaInteligentePlugin.auto_sync_help') ?></span>
            </div>
        </div>

        <?= form_submit([
            'name'  => 'submit_listainteligente',
            'id'    => 'submit_listainteligente',
            'value' => lang('Common.submit'),
            'class' => 'btn btn-primary btn-sm pull-right',
        ]) ?>
    </fieldset>
</div>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#config_form').validate($.extend(form_support.handler, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        $.notify({ message: response.message }, { type: response.success ? 'success' : 'danger' });
                        if (response.success) {
                            $('#plugin-config-modal').modal('hide');
                        }
                    },
                    dataType: 'json'
                });
            },
            errorLabelContainer: '#error_message_box'
        }));
    });
</script>
