<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div id="pad_template_input"    class="card admidio-field-group">
        <div class="card-body">
            {include 'sys-template-parts/form.input.tpl' data=$elements['input_build']}
            {include 'sys-template-parts/form.button.tpl' data=$elements['btn_input_save']} 
            {include 'sys-template-parts/form.select.tpl' data=$elements['show_build']}
        </div>
    </div>
</form>