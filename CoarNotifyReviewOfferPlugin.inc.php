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
            $reviewOfferPreference = new ReviewOfferPreference();

            $reviewOfferPreferenceDao = new ReviewOfferPreferenceDAO();
            DAORegistry::registerDAO('ReviewOfferPreferenceDAO', $reviewOfferPreferenceDao);

            $reviewOfferPreference->setSubmissionId('1234567');
            $reviewOfferPreference->setServiceUrl('https://test.com');
            $reviewOfferPreference->setIsSent(false);

//            $reviewOfferPreferenceDao->insertObject($reviewOfferPreference);

            HookRegistry::register('Template::Workflow::Publication', array($this, 'addToWorkflow'));
            HookRegistry::register('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', array($this, 'submissionWizard'));
//            HookRegistry::register('Publication::edit', array($this, 'publicationSave'));

            HookRegistry::register('LoadHandler', array($this, 'handleRequests'));
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

    function notification($type, $message)
    {
        import('classes.notification.NotificationManager');
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            $type,
            ['contents' => __($message)]
        );
    }

    /**
     * Retrieves the list of review services from the plugin settings and caches it
     * @return array List of review services, where key is the home URL and value is the inbox URL
     */
    private function getReviewServiceList(): array
    {
        if (
            $this->_reviewServiceList === null
            && !is_array($this->_reviewServiceList = $this->getSetting($this->getCurrentContextId(), 'reviewServiceList'))
        ) {
            $this->_reviewServiceList = [];
        }
        return $this->_reviewServiceList;
    }

    public function sendHttpPostRequest($url, $data) {
        $this->notification(
            NOTIFICATION_TYPE_SUCCESS,
            'Start of POST',
        );

        // Initialize cURL session
        $ch = curl_init();

        // Convert the array into JSON string
        $jsonData = json_encode($data);

        $this->notification(
            NOTIFICATION_TYPE_SUCCESS,
            'JSON data: '.$jsonData,
        );

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));

        // Execute cURL session and get the response
        $response = curl_exec($ch);
        $result = json_decode($response);

        // Check for cURL errors
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        $this->notification(
            NOTIFICATION_TYPE_SUCCESS,
            'End of POST',
        );

        // Close cURL session
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
//        $this->notification(
//            NOTIFICATION_TYPE_SUCCESS,
//            'In install migration',
//        );

//        $this->import('CoarNotifyReviewOfferSchemaMigration');
//        return new CoarNotifyReviewOfferSchemaMigration();
        $migration = new CoarNotifyReviewOfferSchemaMigration();

//        $migration->up();

        return $migration;
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
            'submission' => json_encode($submission),
            'originHomeUrl' => $this->getSetting($this->getCurrentContextId(), 'originHomeUrl'),
            'originInboxUrl' => $this->getSetting($this->getCurrentContextId(), 'originInboxUrl'),
            'actorName' => $user->getFullName(),
            'authorId' => $this->getAuthorId($user),
            'isPublished' => $this->isSubmissionPublished($submission),
            'doi' => $this->getDoi($submission),
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
    public function submissionWizard(string $hookname, array $args): void
    {
        $templateMgr = &$args[1];

        $request = $this->getRequest();
        $context = $request->getContext();
        $dispatcher = $request->getDispatcher();
        $apiBaseUrl = $dispatcher->url($request, ROUTE_API, $context->getData('urlPath'), '');

        $publicationDao = \DAORegistry::getDAO('PublicationDAO');
        $submissionId = $request->getUserVar('submissionId');
        $publication = $publicationDao->getById($submissionId);

//        $this->templateParameters['pluginApiUrl'] = $apiBaseUrl . OPTIMETA_CITATIONS_API_ENDPOINT;
        $this->templateParameters['submissionId'] = $submissionId;
//        $this->templateParameters['doiBaseUrl'] = OPTIMETA_CITATIONS_DOI_URL;

//        $pluginDAO = new PluginDao();
//        $this->templateParameters['citationsParsed'] = json_encode($pluginDAO->getCitations($publication));

//        $publicationWorkDb = $publication->getData(OPTIMETA_CITATIONS_PUBLICATION_WORK);

        if (!empty($publicationWorkDb) && $publicationWorkDb !== '[]')
            $this->templateParameters['workModel'] = $publicationWorkDb;

//        if (!$this->isProduction)
//            $this->templateParameters['wikidataURL'] = OPTIMETA_CITATIONS_WIKIDATA_URL_TEST;

        $this->templateParameters['statusCodePublished'] = STATUS_PUBLISHED;

        $templateMgr->assign($this->templateParameters);

        $templateMgr->display($this->getTemplateResource("submission/form/submissionWizard.tpl"));
    }

    private function getAuthorId($user): string {
        $orcid = $user->getOrcid();
        return ($orcid != "") ? $orcid : "mailto:{$user->getEmail()}";
    }

    public function getDoi($submission) {
//        import('classes.submission.Submission');
//        $submission = Services::get('submission')->get($id);
//        $doi="doi-".str_replace("/", "-", strtolower($urldoi));

        // Need to also check if PublicationSettings - setting_name vorDoi is present as well encase added manually by author
//        $vorDoi = $submission->getData('publications')[0]->getData('vorDoi');
//        $pubDoi = $submission->getData('publications')[0]->getData('pub-id::doi');
//        return ($pubDoi != "") ? $pubDoi : $vorDoi;
        return $submission->getData('publications')[0]->getData('pub-id::doi');
    }

    private function getSubmissionType(): string
    {
        $applicationName = substr(Application::getName(), 0, 3);

        if($applicationName == 'ops') {
            return 'preprint';
        }

        return 'article';
    }

    public function handleRequests($hookName, $params) {
        $request = $this->getRequest();
        $templateMgr = TemplateManager::getManager($request);
//        $method = $request->getServerVar('REQUEST_METHOD');
        $method = $request->getRequestMethod();

        $page = $params[0];
        $op = $params[1];

        if ($op == 'fetchModalContent') {
            $templateMgr = TemplateManager::getManager($request);
            // fetch and assign data to the template if needed
            $templateMgr->assign('modalContent', 'This is the content of the modal.');
            $templateMgr->display($this->getTemplateResource('modalContent.tpl'));
        }


//        $this->notification(
//            NOTIFICATION_TYPE_SUCCESS,
//            'Request method' . $method,
//        );
//
//        $this->notification(
//            NOTIFICATION_TYPE_SUCCESS,
//            'Requested page:' . $params[0],
//        );
//
//        $this->notification(
//            NOTIFICATION_TYPE_SUCCESS,
//            'OP:' . $params[1],
//        );

//$request->getRequestedPage() === 'coarNotifyReviewOffer'
        if ($method === 'POST') {
            // Handle the POST request here

            // Get form data
            $formData = $request->getUserVars();

            $optionValues = $formData['option'] ?? null; // the 'option' is the name attribute of your form fields

            $this->notification(
                NOTIFICATION_TYPE_SUCCESS,
                'Looking at option values = ' . $optionValues,
            );

            // Prevent the default request handling
//            return true;
        }

        return false;
    }

    public function fetchModalContent($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
        // fetch and assign data to the template if needed
        $templateMgr->assign('modalContent', 'This is the content of the modal.');
        return $templateMgr->display($this->getTemplateResource('modalContent.tpl'));
    }
}