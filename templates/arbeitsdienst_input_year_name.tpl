<form>
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>

    <div id="pad_template_selectyear"    class="card admidio-field-group">
        <div class="card-body">
            {include 'sys-template-parts/form.select.tpl' data=$elements['plg_arbeitsdienst_input_year']}
            {include 'sys-template-parts/form.select.tpl' data=$elements['plg_arbeitsdienst_input_user']}
        </div>
    </div>

</form>