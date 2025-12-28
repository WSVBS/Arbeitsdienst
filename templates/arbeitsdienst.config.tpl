
<div id="adm_content" class="admidio-content " role="main">
    <form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
        </div class="admidio-form-required-notice"> 
            {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']}   
            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-2">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_AGE_TO_WORK')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.input.tpl' data=$elements['AGEBegin']}
                                {include 'sys-template-parts/form.input.tpl' data=$elements['AGEEnd']}
                            </div>
                        </div>
                    </div>
                </div>
            

            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-2">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_HOURS')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.input.tpl' data=$elements['workinghoursman']}
                                {include 'sys-template-parts/form.input.tpl' data=$elements['workinghourswoman']}
                                {include 'sys-template-parts/form.input.tpl' data=$elements['workinghoursamount']}
                            </div>
                        </div>
                    </div>
                </div>
            

            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-2">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_EXPORT_SEPA_DATE')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.input.tpl' data=$elements['dateaccounting']}
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-1">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_EXCEPTION')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.select.tpl' data=$elements['exceptions_roleselection']}
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-2">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_FILENAME')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.input.tpl' data=$elements['filename']}
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card admidio-field-group">
                <div class="card-body">
                    <div class="admidio-form-group admidio-form-custom-content row mb-2">
                        <label class="col-sm-4 col-form-label"> 
                            <strong>
                                <h5>
                                    {$l10n->get('PLG_ARBEITSDIENST_REFERENCE')} 
                                </h5>
                            </strong>
                        </label>
                        <div class="col-sm-8">
                            <div "class="admidio-form-group row mb-3 >
                                {include 'sys-template-parts/form.input.tpl' data=$elements['reference']}
                            </div>
                        </div>
                    </div>
                </div>

            
                <div class="form-alert" style="display: none;">&nbsp;</div>
                {include 'sys-template-parts/form.button.tpl' data=$elements['btn_input_save_reference']}

            </div>
           
        </div>
        
    <form>
</div>
   
