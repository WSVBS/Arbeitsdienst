
<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
        <div id="pad_template_input"    class="card admidio-field-group">
            <div class="card-body">
                <table>
                    <tr>
                        <td class="col-sm-4 col-form-label" valign="top">
                            <h4>{$l10n->get('PLG_ARBEITSDIENST_OVERVIEW_PAYMENT')}</h4>
                        </td>
                        <td class="col-sm-8 col-form-label" colspan="2">
                            {$l10n->get('PLG_ARBEITSDIENST_DATE_PAID_DESC')}
                        </td>
                    <tr>
                    </tr>
                        <td>
                        </td>
                        <td class="col-sm-8 col-form-label" colspan="2">
                            {include 'sys-template-parts/form.button.tpl' data=$elements['btn_contribution_payment']}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
</form>







