<div>
    <h3>Review Offer Preferences</h3>
    <p>If you like to opt in to automatically request reviews from any of the configured review services upon publication,
        simply add the service(s) below:</p>

    <div id="reviewOfferPreferences">
        {capture assign=reviewOfferPrefsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.coarNotifyReviewOffer.controllers.grid.CoarReviewOfferGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}{/capture}
        {load_url_in_div id="reviewOfferPrefsGridContainer"|uniqid url=$reviewOfferPrefsGridUrl}
    </div>
</div>
<br>