<link rel="stylesheet" type="text/css" href="/plugins/generic/coarNotifyReviewOffer/styles/coarNotifyReviewOffer.css">
<script type="text/javascript" src="/plugins/generic/coarNotifyReviewOffer/templates/pagination.js"></script>
<script type="text/javascript" src="/plugins/generic/coarNotifyReviewOffer/js/coar-notify.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#openModalButton').click(function() {
            $.ajax({
                url: '{url op="fetchModalContent"}',
                type: 'GET',
                success: function(data) {
                    // Create and open the modal
                    new pkp.controllers.modal.AjaxModal(data.content);
                }
            });
        });
    });
</script>


{load_header context="backend"}

{$currentAuthor = 0}
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

            <h3>Services</h3>
            <form action="" method="post">
                <label>
                    <input type="checkbox" name="option[]" value="1"> Option 1
                </label><br>
                <label>
                    <input type="checkbox" name="option[]" value="2"> Option 2
                </label><br>
                <label>
                    <input type="checkbox" name="option[]" value="3"> Option 3
                </label><br>
                <button type="submit">Save</button>
            </form>

            {*            <button id="openModalButton">Open Modal</button>*}
        {/if}
    {/if}



    {*    <br/>*}
    {*    <hr/>*}
    {*    <div>*}
    {*        <h4>Debug</h4>*}
    {*        <p>Full name: {$actorName}</p>*}
    {*        <p>Author ID: {$authorId}</p>*}
    {*        <p>DOI: {$doi}</p>*}
    {*        <p>Is Published: {$isPublished}</p>*}
    {*    </div>*}

    {*    <div>{$submission}</div>*}

</div>