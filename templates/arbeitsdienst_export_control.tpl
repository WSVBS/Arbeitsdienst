<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div id="pad_template_input"    class="card admidio-field-group">
        <div class="card-body">
            <table>
                <tr>
                    <td class="col-sm-4 col-form-label" valign="top">
                        <h4>Kontrolle</h4>
                    </td>
                    <td class="col-sm-8 col-form-label" colspan="2">
                        {include 'sys-template-parts/form.description.tpl' data=$elements['arbeitsdienst_export_control_info']}
                    </td>
                </tr>               
                <tr>
                    <td>
                    </td>
                    <td>
                        {include 'sys-template-parts/form.radio.tpl' data=$elements['typeofoutput']}
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>
                        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_export_control']}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</form>