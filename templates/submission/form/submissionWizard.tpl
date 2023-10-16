<div>
    <h3>{translate key="plugins.generic.coarNotifyReviewOffer.reviewOfferPreferences"}</h3>

    <p>{translate key="plugins.generic.coarNotifyReviewOffer.prePubDescriptionPartOne"}</p>
    <p>{translate key="plugins.generic.coarNotifyReviewOffer.prePubDescriptionPartTwo"}</p>
    <div id="reviewOfferPreferences">
        {capture assign=reviewOfferPrefsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.coarNotifyReviewOffer.controllers.grid.CoarReviewOfferGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}{/capture}
        {load_url_in_div id="reviewOfferPrefsGridContainer"|uniqid url=$reviewOfferPrefsGridUrl}
    </div>
</div>
<br>