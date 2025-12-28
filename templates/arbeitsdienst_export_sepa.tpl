<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div id="pad_template_input"    class="card admidio-field-group">
        <div class="card-body">
            <table>
                <tr>
                    <td class="col-sm-4 col-form-label" valign="top">
                        <h4>SEPA</h4>
                    </td>
                    <td class="col-sm-8 col-form-label" colspan="2">
                        {include 'sys-template-parts/form.description.tpl' data=$elements['arbeitsdienst_export_sepa_info']}
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>
                         <strong>
                            {include 'sys-template-parts/form.description.tpl' data=$elements['plg_arbeitsdienst_faelligkeitsdatum']}
                         </strong>
                    </td>
                    <td>
                            {include 'sys-template-parts/form.description.tpl' data=$elements['plg_arbeitsdienst_faelligkeitsdatum_wert']}
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>
                         <strong>
                            {include 'sys-template-parts/form.description.tpl' data=$elements['plg_arbeitsdienst_sequenztyp']}
                         </strong>
                    </td>
                    <td>
                        {include 'sys-template-parts/form.radio.tpl' data=$elements['typeofsepaoutput']}
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>
                        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_export_sepa_xml']}
                    </td>
                    <td>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</form>