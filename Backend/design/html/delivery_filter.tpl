{$meta_title = $btr->sviat__delivery_filter__page_title scope=global}

<div class="main_header">
    <div class="main_header__item">
        <div class="main_header__inner">
            <div class="box_heading heading_page">{$btr->sviat__delivery_filter__page_title|escape}</div>
        </div>
    </div>
    <div class="main_header__item">
        <div class="main_header__inner">
            <a class="btn btn_small btn_blue" href="index.php?controller=OrdersAdmin">{$btr->sviat__delivery_filter__to_orders|escape}</a>
        </div>
    </div>
</div>

<div class="boxed">
    <p class="mb-0">{$btr->sviat__delivery_filter__intro|escape}</p>
</div>

{if $message_success}
    <div class="alert alert--success" role="status"><div class="alert__content"><p>{$btr->sviat__delivery_filter__saved|escape}</p></div></div>
{/if}

<form method="post" class="boxed">
    <input type="hidden" name="session_id" value="{$smarty.session.id|escape}">
    <div class="heading_box">{$btr->sviat__delivery_filter__carriers|escape}</div>
    {if $delivery_filter_carriers}
        {foreach $delivery_filter_carriers as $df_row}
            <div class="df-carrier">
                <div class="df-carrier__head">
                    <b>{$df_row.module|escape}</b>
                    {$df_shown = 0}
                    {foreach $df_row.groups as $df_key => $df_group}{if empty($delivery_filter_hidden[$df_key])}{$df_shown = $df_shown + 1}{/if}{/foreach}
                    {if $df_row.state == 'connected' && $df_shown == 0}<span class="tag tag-default">{$btr->sviat__delivery_filter__state_hidden|escape}</span>
                    {elseif $df_row.state == 'connected'}<span class="tag tag-success">{$btr->sviat__delivery_filter__state_connected|escape}</span>
                    {elseif $df_row.state == 'disabled'}<span class="tag tag-default">{$btr->sviat__delivery_filter__state_disabled|escape}</span>
                    {elseif $df_row.state == 'silent'}<span class="tag tag-warning">{$btr->sviat__delivery_filter__state_silent|escape}</span>
                    {elseif $df_row.state == 'rejected'}<span class="tag tag-danger">{$btr->sviat__delivery_filter__state_rejected|escape}</span>
                    {elseif $df_row.state == 'error'}<span class="tag tag-danger">{$btr->sviat__delivery_filter__state_error|escape}</span>
                    {else}<span class="tag tag-default">{$btr->sviat__delivery_filter__state_unsupported|escape}</span>{/if}
                    {if $df_row.groups}
                        <span class="df-carrier__toggles">
                            {foreach $df_row.groups as $df_key => $df_group}
                                <input type="hidden" name="carriers[]" value="{$df_key|escape}">
                                <label class="df-carrier__switch">
                                    <span>{$btr->sviat__delivery_filter__show_in_filter|escape}{if $df_row.groups|count > 1} ({$df_group.short|escape}){/if}</span>
                                    <span class="switch switch-default">
                                        <input class="switch-input" type="checkbox" name="visible[]" value="{$df_key|escape}" {if empty($delivery_filter_hidden[$df_key])}checked{/if}>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </span>
                                </label>
                            {/foreach}
                        </span>
                    {/if}
                </div>
                {if $df_row.state != 'unsupported'}
                    <div class="text_grey">
                        {$btr->sviat__delivery_filter__list_filter|escape}: {if $df_row.filter}{$btr->sviat__delivery_filter__yes|escape}{else}{$btr->sviat__delivery_filter__no|escape}{/if}
                        · {$btr->sviat__delivery_filter__number_search|escape}: {if $df_row.search}{$btr->sviat__delivery_filter__yes|escape}{else}{$btr->sviat__delivery_filter__no|escape}{/if}
                    </div>
                {/if}
                {foreach $df_row.groups as $df_key => $df_group}
                    <div class="df-carrier__group">
                        <span>{$df_group.title|escape} <span class="text_grey">({$df_group.short|escape}, <code>{$df_key|escape}</code>)</span>:</span>
                        {foreach $df_group.options as $df_label}{$df_label|escape}{if !$df_label@last}, {/if}{/foreach}
                    </div>
                {/foreach}
            </div>
        {/foreach}
        {if $delivery_filter_toggles}
            <div class="df-carrier__buttons">
                <button type="submit" class="btn btn_small btn_blue"><span>{$btr->sviat__delivery_filter__save|escape}</span></button>
            </div>
        {/if}
    {else}
        <p class="mb-0">{$btr->sviat__delivery_filter__none|escape}</p>
    {/if}
</form>

<div class="boxed">
    <div class="heading_box">{$btr->sviat__delivery_filter__states|escape}</div>
    <ul class="mb-0">
        <li><b>{$btr->sviat__delivery_filter__state_silent|escape}</b> — {$btr->sviat__delivery_filter__state_silent_hint|escape}</li>
        <li><b>{$btr->sviat__delivery_filter__state_rejected|escape}</b> — {$btr->sviat__delivery_filter__state_rejected_hint|escape}</li>
        <li><b>{$btr->sviat__delivery_filter__state_unsupported|escape}</b> — {$btr->sviat__delivery_filter__state_unsupported_hint|escape}</li>
    </ul>
</div>

<div class="boxed">
    <div class="heading_box">{$btr->sviat__delivery_filter__developers|escape}</div>
    <p class="mb-0">{$btr->sviat__delivery_filter__developers_hint|escape}</p>
</div>

