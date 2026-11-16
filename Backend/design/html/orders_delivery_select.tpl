{if $delivery_status_filters}
    <div class="col-md-3 col-lg-3 col-sm-12">
        <select class="selectpicker form-control" data-live-search="true" onchange="location = this.value;" aria-label="{$btr->sviat__delivery_filter__label|escape}">
            <option value="{url delivery_status=null page=null}" {if !$delivery_status}selected{/if}>{$btr->sviat__delivery_filter__all|escape}</option>
            {foreach $delivery_status_filters as $df_carrier => $df_group}
                {* bootstrap-select вставляє label як HTML після того, як браузер його декодував: одинарного escape мало *}
                {if $delivery_status_filters|count > 1}<optgroup label="{$df_group.title|escape|escape}">{/if}
                {foreach $df_group.options as $df_status => $df_label}
                    {$df_value = "`$df_carrier`:`$df_status`"}
                    <option value="{url delivery_status=$df_value page=null}" {if $delivery_status == $df_value}selected{/if}>{$df_group.short|escape} · {$df_label|escape}</option>
                {/foreach}
                {if $delivery_status_filters|count > 1}</optgroup>{/if}
            {/foreach}
        </select>
    </div>
{/if}
