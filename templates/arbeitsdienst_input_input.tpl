
<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>

    <div id="pad_template_input"    class="card admidio-field-group">
        <div class="card-body">
            {include 'sys-template-parts/form.input.tpl' data=$elements['date']}
            {include 'sys-template-parts/form.select.tpl' data=$elements['cat_id']}
            {include 'sys-template-parts/form.select.tpl' data=$elements['pro_id']}
            {include 'sys-template-parts/form.input.tpl' data=$elements['discription']}
            {include 'sys-template-parts/form.input.tpl' data=$elements['hours']}
        </div>
    </div>
    <div class="form-alert" style="display: none;">
        &nbsp;
    </div>
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_input_save']}
</form>
