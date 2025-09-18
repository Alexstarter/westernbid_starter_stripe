<div class="panel">
  <div class="panel-heading">
    {l s='WesternBid Stripe configuration' mod='westernbid_starter_stripe'}
  </div>
  <ul class="nav nav-tabs" role="tablist" id="wb-stripe-config-tabs">
    <li class="active">
      <a href="#wb-stripe-settings" role="tab" data-toggle="tab">
        {l s='Settings' mod='westernbid_starter_stripe'}
      </a>
    </li>
    <li>
      <a href="#wb-stripe-logs" role="tab" data-toggle="tab">
        {l s='WesternBid logs' mod='westernbid_starter_stripe'}
      </a>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane active" id="wb-stripe-settings">
      {$configuration_form nofilter}
    </div>
    <div class="tab-pane" id="wb-stripe-logs">
      <div class="panel">
        <div class="panel-heading">
          {l s='Log filters' mod='westernbid_starter_stripe'}
        </div>
        <div class="panel-body">
          <form action="{$log_filter_action|escape:'htmlall':'UTF-8'}" method="get" class="form-horizontal">
            <input type="hidden" name="controller" value="{$controller_name|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
            <div class="form-group">
              <label class="control-label col-lg-2" for="wb_log_date_from">{l s='Date from' mod='westernbid_starter_stripe'}</label>
              <div class="col-lg-4">
                <input type="date" id="wb_log_date_from" name="wb_log_date_from" class="form-control" value="{$log_filters.date_from|escape:'htmlall':'UTF-8'}" />
              </div>
              <label class="control-label col-lg-2" for="wb_log_date_to">{l s='Date to' mod='westernbid_starter_stripe'}</label>
              <div class="col-lg-4">
                <input type="date" id="wb_log_date_to" name="wb_log_date_to" class="form-control" value="{$log_filters.date_to|escape:'htmlall':'UTF-8'}" />
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2" for="wb_log_order_id">{l s='Order ID' mod='westernbid_starter_stripe'}</label>
              <div class="col-lg-4">
                <input type="text" id="wb_log_order_id" name="wb_log_order_id" class="form-control" value="{$log_filters.order_id|escape:'htmlall':'UTF-8'}" />
              </div>
            </div>
            <div class="form-group">
              <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">
                  {l s='Apply filters' mod='westernbid_starter_stripe'}
                </button>
                <a href="{$log_filter_action|escape:'htmlall':'UTF-8'}" class="btn btn-default">
                  {l s='Reset' mod='westernbid_starter_stripe'}
                </a>
              </div>
            </div>
          </form>
          <form action="{$log_download_action|escape:'htmlall':'UTF-8'}" method="post" class="form-inline">
            <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
            <button type="submit" name="downloadWesternbidLog" value="1" class="btn btn-default"{if !$log_file_exists} disabled="disabled"{/if}>
              {l s='Download log' mod='westernbid_starter_stripe'}
            </button>
          </form>
        </div>
      </div>
      <div class="panel">
        <div class="panel-heading">
          {l s='Recent log entries' mod='westernbid_starter_stripe'}
        </div>
        <div class="panel-body">
          {if $log_entries|@count gt 0}
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>{l s='Date' mod='westernbid_starter_stripe'}</th>
                  <th>{l s='Event' mod='westernbid_starter_stripe'}</th>
                  <th>{l s='Order' mod='westernbid_starter_stripe'}</th>
                  <th>{l s='Details' mod='westernbid_starter_stripe'}</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$log_entries item=entry}
                  <tr>
                    <td><span class="badge">{$entry.timestamp|escape:'htmlall':'UTF-8'}</span></td>
                    <td>{$entry.event|escape:'htmlall':'UTF-8'}</td>
                    <td>
                      {if isset($entry.context.order_id) && $entry.context.order_id}
                        {$entry.context.order_id|escape:'htmlall':'UTF-8'}
                      {else}
                        —
                      {/if}
                    </td>
                    <td>
                      <pre class="wb-log-context">{$entry.context_pretty|escape:'htmlall':'UTF-8'}</pre>
                    </td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          {else}
            <div class="alert alert-info">
              {l s='No log entries found for the selected filters.' mod='westernbid_starter_stripe'}
            </div>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<style>
#wb-stripe-config-tabs { margin-bottom: 15px; }
.wb-log-context { margin: 0; white-space: pre-wrap; word-break: break-word; }
</style>
