{**
 * plugins/generic/coarNotifyReviewOffer/templates/coarNotifyReviewOffer.tpl
 *
 * Copyright (c) 2020-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @brief Template for display the list of submissions of an author
 *}

<link rel="stylesheet" type="text/css" href="/plugins/generic/coarNotifyReviewOffer/styles/coarNotifyReviewOffer.css">
<script type="text/javascript" src="/plugins/generic/coarNotifyReviewOffer/js/coar-notify.js"></script>

<script type="text/javascript">
    $(function() {ldelim}
        $('#reviewOfferPreferences').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>


{load_header context="backend"}

<div id="coarNotifyReviewOffer">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.coarNotifyReviewOffer.displayName"}</h2>
    </div>
    {if !$doi}
        <p>Review offers can only be sent once a DOI has been assigned.</p>
        <p>Please ensure a DOI is assigned.</p>

    {else}
        {if $isPublished}
            <p>
                To offer your preprint for peer review to any of the services listed below simply click the associated
                'Ask for Reviews' button.
            </p>
            <p>A request will then be sent to the target service via the COAR Notify protocol.</p>

            <h3>Services</h3>
            <div>
                {foreach from=$reviewServiceList key=targetHomeUrl item=targetInboxUrl name=reviewServiceList}
                    <div class="reviewBlock">
                        <h4 class="reviewServiceHomeUrl">{$targetHomeUrl}</h4>
                        <button
                                id="{$smarty.foreach.reviewServiceList.index}-send-button"
                                class="askForReviewButton"
                                onclick="sendNotificationHandler(
                                        '{$originInboxUrl}',
                                        '{$originHomeUrl}',
                                        '{$targetInboxUrl}',
                                        '{$targetHomeUrl}',
                                        '{$authorId}',
                                        '{$actorName}',
                                        '{$doi}',
                                        '{$smarty.foreach.reviewServiceList.index}-send-button'
                                        )"
                        >
                            Ask for Reviews
                        </button>
                    </div>
                {/foreach}
            </div>

        {else}
            <p>
                To automatically offer your preprint for peer review after publication to any of the services listed below
                simply check the associated checkbox.
            </p>
            <p>
                A request will then be sent to the target service via the COAR Notify protocol once your preprint has been
                published. You can update this choice at any point before publication.
            </p>

            <div id="reviewOfferPreferences">
                {capture assign=reviewOfferPrefsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.coarNotifyReviewOffer.controllers.grid.CoarReviewOfferGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}{/capture}
                {load_url_in_div id="reviewOfferPrefsGridContainer"|uniqid url=$reviewOfferPrefsGridUrl}
            </div>
        {/if}
    {/if}

</div>