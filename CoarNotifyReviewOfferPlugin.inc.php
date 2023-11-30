<?php
/**
 * @file plugins/generic/coarNotifyReviewOffer/CoarNotifyReviewOfferPlugin.inc.php
 *
 * Copyright (c) --

 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class CoarNotifyReviewOfferPlugin
 * @ingroup plugins_generic_coarNotifyReviewOffer
 * @brief Plugin class for the Coar Notify Review Offer plugin.
 */
import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.submission.PKPSubmission');
import('plugins.generic.coarNotifyReviewOffer.CoarNotifyReviewOfferSchemaMigration');

class CoarNotifyReviewOfferPlugin extends GenericPlugin {
    /** @var array Lazy loaded review service list */
    private $_reviewServiceList = null;

    public function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);

        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            import('plugins.generic.coarNotifyReviewOffer.classes.ReviewOfferPreference');
            import('plugins.generic.coarNotifyReviewOffer.classes.ReviewOfferPreferenceDAO');

            $reviewOfferPreferenceDao = new ReviewOfferPreferenceDAO();
            DAORegistry::registerDAO('ReviewOfferPreferenceDAO', $reviewOfferPreferenceDao);

            HookRegistry::register('Template::Workflow::Publication', array($this, 'addToWorkflow'));
            HookRegistry::register('TemplateManager::display',array($this, 'addGridhandlerJs'));
            HookRegistry::register('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', array($this, 'submissionWizard'));

            HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
            HookRegistry::register('Publication::publish', array($this, 'sendNotificationsOnPublish'), HOOK_SEQUENCE_CORE);
        }

        return $success;
    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the plugins list where editors can
     * enable and disable plugins.
     */
    public function getDisplayName() {
        return 'Coar Notify Review Offers';
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the plugins list where editors can
     * enable and disable plugins.
     */
    public function getDescription() {
        return 'This plugin notifies target review services when a submission has been successful and is ready for pre-reviews.';
    }

    private function getAuthorId($user): string {
        $orcid = $user->getOrcid();
        return ($orcid != "") ? $orcid : "mailto:{$user->getEmail()}";
    }

    public function getDoi($submission) {
        return $submission->getData('publications')[0]->getData('pub-id::doi');
    }

    private function getSubmissionType(): string {
        $applicationName = substr(Application::getName(), 0, 3);

        if($applicationName == 'ops') {
            return 'preprint';
        }

        return 'article';
    }

    /**
     * Retrieves the list of review services from the plugin settings and caches it
     * @return array List of review services, where key is the home URL and value is the inbox URL
     */
    function getReviewServiceList(): array {
        if (
            $this->_reviewServiceList === null
            && !is_array($this->_reviewServiceList = $this->getSetting($this->getCurrentContextId(), 'reviewServiceList'))
        ) {
            $this->_reviewServiceList = [];
        }
        return $this->_reviewServiceList;
    }

    public function sendHttpPostRequest($url, $data) {
        $ch = curl_init();
        $jsonData = json_encode($data);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));

        $response = curl_exec($ch);
        $result = json_decode($response);

        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    public function manage($args, $request) {
        if ($request->getUserVar('verb') == 'settings') {
            AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
            $this->import('CoarNotifyReviewOfferSettingsForm');
            $form = new CoarNotifyReviewOfferSettingsForm($this, $request->getContext()->getId());

            if ($request->getUserVar('save')) {
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    $notificationManager = new NotificationManager();
                    $notificationManager->createTrivialNotification($request->getUser()->getId());
                    return new JSONMessage(true);
                }
            } else {
                $form->initData();
            }
            return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb) {
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $actions = parent::getActions($request, $verb);
        if ($this->getEnabled()) {
            $actions += [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                )
            ];
        }
        return $actions;
    }

    public function getInstallMigration() {
        return new CoarNotifyReviewOfferSchemaMigration();
    }

    /**
     * @see Plugin::getInstallSitePluginSettingsFile()
     */
    public function getInstallSitePluginSettingsFile() {
        return $this->getPluginPath() . '/settings.xml';
    }

    private function isSubmissionPublished($submission): bool {
        return $submission->getData('status') === STATUS_PUBLISHED;
    }

    function getReviewOfferPreferences($submissionId) {
        /* @var $reviewOfferPreferenceDao ReviewOfferPreferenceDAO */
        $reviewOfferPreferenceDao = DAORegistry::getDAO('ReviewOfferPreferenceDAO');
        $reviewOfferPreferencesResult = $reviewOfferPreferenceDao->getBySubmissionId($submissionId)->toArray();

        return array_map(function($preference){
            return $preference->getData('serviceUrl');
        }, $reviewOfferPreferencesResult);
    }

    public function addToWorkflow($hookName, $params) {
        $smarty =& $params[1];
        $output =& $params[2];
        $submission = $smarty->get_template_vars('submission');
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        $smarty->assign(
            'userIsManager',
            $user->hasRole(Application::getWorkflowTypeRoles()[WORKFLOW_TYPE_EDITORIAL], $request->getContext()->getId())
        );

        $smarty->assign([
            'submissionType' => $this->getSubmissionType(),
            'reviewServiceList' => $this->getReviewServiceList(),
            'originHomeUrl' => $this->getSetting($this->getCurrentContextId(), 'originHomeUrl'),
            'originInboxUrl' => $this->getSetting($this->getCurrentContextId(), 'originInboxUrl'),
            'actorName' => $user->getFullName(),
            'authorId' => $this->getAuthorId($user),
            'isPublished' => $this->isSubmissionPublished($submission),
            'doi' => $this->getDoi($submission),
            'reviewOfferPreferences' => $this->getReviewOfferPreferences($submission->getData('id')),
        ]);

        $output .= sprintf(
            '<tab id="coarNotifyReviewOffer" label="%s">%s</tab>',
            __('plugins.generic.coarNotifyReviewOffer.displayName'),
            $smarty->fetch($this->getTemplateResource('coarNotifyReviewOffer.tpl'))
        );
    }

    /**
     * Show citations part on step 3 in submission wizard
     * @param string $hookname
     * @param array $args
     * @return void
     */
    public function submissionWizard(string $hookname, array $args): void {
        $templateMgr = &$args[1];
        $request = $this->getRequest();
        $submissionId = $request->getUserVar('submissionId');

        $this->templateParameters['submissionId'] = $submissionId;

        if (!empty($publicationWorkDb) && $publicationWorkDb !== '[]')
            $this->templateParameters['workModel'] = $publicationWorkDb;

        $this->templateParameters['statusCodePublished'] = STATUS_PUBLISHED;

        $templateMgr->assign($this->templateParameters);

        $templateMgr->display($this->getTemplateResource("submission/form/submissionWizard.tpl"));
    }

    /**
     * Permit requests to the grid handler
     * @param $hookName string The name of the hook being invoked
     * @param $args array The parameters to the invoked hook
     */
    function setupGridHandler($hookName, $params) {
        $component =& $params[0];
        if ($component == 'plugins.generic.coarNotifyReviewOffer.controllers.grid.CoarReviewOfferGridHandler') {
            import($component);
            CoarReviewOfferGridHandler::setPlugin($this);
            return true;
        }
        return false;
    }

    /**
     * Add custom gridhandlerJS for backend
     */
    function addGridhandlerJs($hookName, $params) {
        $templateMgr = $params[0];
        $request = $this->getRequest();
        $gridHandlerJs = $this->getJavaScriptURL($request, false) . DIRECTORY_SEPARATOR . 'CoarReviewOfferGridHandler.js';
        $templateMgr->addJavaScript(
            'CoarReviewOfferGridHandlerJs',
            $gridHandlerJs,
            array('contexts' => 'backend')
        );
        return false;
    }

    /**
     * Get the JavaScript URL for this plugin.
     */
    function getJavaScriptURL() {
        return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
    }

    function getReviewServiceTargetsForSubmission(string $submissionId): array {
        return array_map(function($targetServiceHomUrl) {
            // TODO - Filter out has been sent items
            return [
                "id" => $targetServiceHomUrl,
                "inbox" => $this->getReviewServiceList()[$targetServiceHomUrl],
                "type" => "Service"
            ];
        }, $this->getReviewOfferPreferences($submissionId));
    }

    /**
     * Send COAR Notifications on publish
     *
     * @param $hookName string
     * @param $args array [
     *		@option Publication The new version of the publication
     *		@option Publication The old version of the publication
     *		@option Submission
     * ]
     */
    function sendNotificationsOnPublish($hookName, $args) {
        /** @var $submission Submission */
        $submission =& $args[2];

        $doi = $this->getDoi($submission);
        $originName = $this->getSetting($this->getCurrentContextId(), 'originName');
        $originHomeUrl = $this->getSetting($this->getCurrentContextId(), 'originHomeUrl');
        $originInboxUrl = $this->getSetting($this->getCurrentContextId(), 'originInboxUrl');

        $targetServices = $this->getReviewServiceTargetsForSubmission($submission->getId());

        foreach ($targetServices as $target) {
            $notification = array(
                "id" => "urn:uuid:" . PKPString::generateUUID(),
                "@context" => array(
                    "https://www.w3.org/ns/activitystreams",
                    "https://purl.org/coar/notify"
                ),
                "type" => array(
                    "Offer",
                    "coar-notify:ReviewAction"
                ),
                "actor" => array(
                    "id" => $originHomeUrl,
                    "name" => $originName,
                    "type" => "Service",
                ),
                "object" => array(
                    "id" => $doi,
                    "ietf:cite-as" => "https://doi.org/" . $doi,
                ),
                "origin" => array(
                    "id" => $originHomeUrl,
                    "inbox" => $originInboxUrl,
                    "type" => "Service",
                ),
                "target" => $target,
            );

            try {
                $this->sendHttpPostRequest($target['inbox'], $notification);

                $this->notification(
                    NOTIFICATION_TYPE_SUCCESS,
                    'Review Offer sent',
                );
            } catch (Exception $e) {
                $this->notification(
                    NOTIFICATION_TYPE_ERROR,
                    'Review Offer failed to send',
                );
            }
        }

        return false;
    }

}