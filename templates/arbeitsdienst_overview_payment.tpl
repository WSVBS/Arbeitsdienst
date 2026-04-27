<div class="table-responsive">
    <table id="adm_lists_table" class="{$classTable}" {foreach $attributes as $attribute} {$attribute@key}="{$attribute}" {/foreach} >
        <thead>
            <tr style="{$headersStyle}"> 
                {foreach $headers as $key => $header}
                    <th style="text-align:$columnAlign[$key]">
                        {$header}
                    </th>
                {/foreach}
            </tr>
        </thead>
        <tbody>
        {if count($rows) eq 0}
            <tr>
                <td colspan="{count($headers)}" style="text-align: center;">{$no_data_text}</td>
            </tr>
        {else}
        {foreach $rows as $key => $row}
            <tr nobr="true" id="userid_{$row['8']}"  class="odd"> 
                <td class="text-center dtr-control"; tabindex="0"; width:5%">{$row['0']}</td>
                <td style="text-align:text-left; width:10%">{$row['1']}</td>
                <td style="text-align:text-left; width:15%">{$row['2']}</td>
                <td style="text-align:text-left; width:15%">{$row['3']}</td>
                <td style="text-align:text-right; width:7%">{$row['4']}</td>
                <td style="text-align:text-left; width:20%">{$row['5']}</td>
                <td style="text-align:text-left; width:18%">{$row['6']}</td>
                <td style="text-align:text-left; width:10%">{$row['7']}</td>

            </tr>
        {/foreach}
        {/if}
        </tbody>
    </table>
</div>