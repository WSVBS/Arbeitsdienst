<div class="table table-condensed table-hover">
{if ($resultempty == false)}
    <table class="table table-condensed">
        <thead>
            <tr>
                <th style="text-align: center; width:0%">
                    &#160
                </th>
                <th style="text-align: center; width:10%">
                    {$header_workingdate}
                </th>
                <th style="text-align: left; width:15%">
                    {$header_cat}
                </th>
                <th style="text-align: left; width:20%">
                    {$header_project}
                </th>
                <th style="text-align: left; width35%">
                    {$header_work}
                </th>
                <th style="text-align: right; width:7%">
                    {$header_hours_table}
                </th>
                <th style="text-align: right; width:10%">
                    &#160
                </th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$result item=id}
                <tr>
                    <td style="text-align: center; width:0%; ">
                    </td>
                    <td style="text-align: center; width:15%">
                        {$id.date}
                    </td>
                    <td style="text-align: left; width:15%">
                        {$id.cat}
                    </td>
                    <td style="text-align: left; width:20%">
                        {$id.proj}
                    </td>
                    <td style="text-align: left; width35%">
                        {$id.discription}
                    </td>
                    <td style="text-align: right; width:7%">
                        {$id.hours}
                    </td>
                    <td style="text-align: right; width:10%">
                        {$id.schalter}
                    </td>
                </tr>
            {foreachelse}
                {$header_result_no_data}
            {/foreach}
        </tbody>
    </table>
{else}
    {$header_result_no_data}
{/if}
</div>

<div id="input-work-result" class="card admidio-field-group ">
    <div class="card-body">
        <div class="table-responsive">
            <table id="table_input_work" class="tableresult table-input_result table-hover">
                <thead>
                    <tr>
                        <th width:5%></td>
                        <th style="text-align: center; width:5%">
                            {$header_result_age}
                        </th>
                        <th style="text-align: center; width:30%">
                            {$header_result_passive}
                        </th>
                        <th style="text-align: center; width:7%">
                            {$header_result_target}
                        </th>
                        <th style="text-align: center; width:7%">
                            {$header_result_actual}
                        </th>
                        <th style="text-align: center; width:7%">
                            {$header_result_diff}
                        </th>
                        <th style="text-align: center; width:25%">
                            {$header_result_missing}
                        </th>
                        <th style="text-align: right; width:15%">
                            {$header_result_topay}
                        </th>
                    </tr>
                </thead>
                <tbody>
                        <tr>
                            <td width:5%></td>
                            <td style="text-align: center; width:5%">
                                {$overview_result_alter}
                            </td>
                            <td style="text-align: center; width:30%">
                                {$overview_result_passiv}
                            </td>
                            <td style="text-align: center; width:7%">
                                {$overview_result_soll}
                            </td>
                            <td style="text-align: center; width:7%">
                                {$overview_result_ist}
                            </td>
                            <td style="text-align: center; width:7%">
                                {$overview_result_diff}
                            </td>
                            <td style="text-align: center; width:25%">
                                {$overview_result_fehl}
                            </td>
                            <td style="text-align: right; width:15%">
                                {$overview_result_topay}
                            </td>
                        </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>