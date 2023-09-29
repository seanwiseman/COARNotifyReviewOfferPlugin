{**
 * plugins/generic/registrationNotification/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Registration Notification plugin settings
 *
 *}
{load_script context="CoarNotifyReviewOfferSettingsForm"}
<script>
    $(function() {ldelim}
        // Attach the form handler.
        $('#coarNotifyReviewOfferSettingsForm').pkpHandler(
            '$.pkp.controllers.form.registrationNotification.RegistrationNotificationFormHandler',
            {ldelim}removeCaption: {translate|json_encode key="common.remove"}{rdelim}
        );
        {rdelim});
</script>

<form class="pkp_form" id="coarNotifyReviewOfferSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    <div id="coarNotifyReviewOfferSettings">
        <div id="description">{translate key="plugins.generic.coarNotifyReviewOffer.description"}</div>

        <h4>Origin {translate key="navigation.settings"}</h4>
        <p>These settings help identify which service has sent the review offer notification.</p>
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="coarNotifyReviewOfferSettingsFormNotification"}

        {fbvFormArea id="coarNotifyReviewOfferSettingsFormArea"}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.originHomeUrl" id="origin-home-url" name="originHomeUrl" value=$originHomeUrl inline=true size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}

        {fbvFormSection}
        {fbvElement type="text" label="plugins.generic.coarNotifyReviewOffer.originInboxUrl" id="origin-inbox-url" name="originInboxUrl" value=$originInboxUrl inline=true size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}

        <h4>Review Service Settings</h4>
        <p>These settings provide your users with a list of target review services.</p>
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