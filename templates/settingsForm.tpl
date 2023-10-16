<script type="text/javascript">
    $(function() {ldelim}
        $('#CoarNotifyReviewOfferSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>

<form class="pkp_form" id="CoarNotifyReviewOfferSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" tab="basic" save=true}">
    <div id="coarNotifyReviewOfferSettings">
        <div id="description">{translate key="plugins.generic.coarNotifyReviewOffer.description"}</div>

        <h4>{translate key="plugins.generic.coarNotifyReviewOffer.originSettings"}</h4>
        <p>{translate key="plugins.generic.coarNotifyReviewOffer.originSettingsDescription"}</p>
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="coarNotifyReviewOfferSettingsFormNotification"}

        {fbvFormArea id="coarNotifyReviewOfferSettingsFormArea"}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.originName" id="origin-home-url" name="originName" value=$originName inline=true size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.originHomeUrl" id="origin-home-url" name="originHomeUrl" value=$originHomeUrl inline=true size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.originInboxUrl" id="origin-inbox-url" name="originInboxUrl" value=$originInboxUrl inline=true size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}

            <h4>{translate key="plugins.generic.coarNotifyReviewOffer.reviewServiceSettings"}</h4>
            <p>{translate key="plugins.generic.coarNotifyReviewOffer.reviewServiceDescription"}</p>
        {foreach from=$inboxUrl key=index item=value}
            {fbvFormSection}
            {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.homeUrl" id="home-url-`$index`" name="homeUrl[]" value=$homeUrl[$index] inline=true size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.inboxUrl" id="inbox-url-`$index`" name="inboxUrl[]" value=$value inline=true size=$fbvStyles.size.MEDIUM}
            {fbvElement type="button" label="common.remove" id="remove-`$index`" inline=true class="default remove-button"}
            {/fbvFormSection}
        {/foreach}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.homeUrl" id="new-home-url" name="homeUrl[]" inline=true size=$fbvStyles.size.MEDIUM}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.inboxUrl" id="new-inbox-url" name="inboxUrl[]" inline=true size=$fbvStyles.size.MEDIUM}
        {fbvElement type="button" label="common.more" id="insert" inline=true class="pkp_button_primary default insert-button"}
        {/fbvFormSection}
        {/fbvFormArea}

        {fbvFormButtons}
        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
    </div>
</form>