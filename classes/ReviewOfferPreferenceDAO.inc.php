<?php

/**
 * @file plugins/generic/funding/classes/classes/FunderDAO.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderDAO
 * @ingroup plugins_generic_funding
 *
 * Operations for retrieving and modifying Funder objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.coarNotifyReviewOffer.classes.ReviewOfferPreference');

class ReviewOfferPreferenceDAO extends DAO {

    /**
     * Get review offer preference by submission ID.
     * @param $submissionId int Submission ID
     * @return ReviewOfferPreference
     */
    function getBySubmissionId($submissionId) {
        $result = $this->retrieve(
            'SELECT * FROM funders WHERE submission_id = ?',
            $submissionId
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Insert a ReviewOfferPreference.
     * @param $preference ReviewOfferPreference
     * @return int Inserted ReviewOfferPreference ID
     */
    function insertObject($preference) {
        $preference->setId($this->getInsertId());
        $this->update(
            'INSERT INTO review_offer_preferences (id, submission_id, service_url, is_sent) VALUES (?, ?, ?, ?)',
            array(
                $preference->getId(),
                $preference->getSubmissionId(),
                $preference->getServiceUrl(),
                (bool) $preference->getIsSent()
            )
        );
        return $preference->getId();
    }

    /**
     * Get the id of the last inserted ReviewOfferPreference.
     * @return int
     */
    function getInsertId() {
        return parent::_getInsertId('review_offer_preferences', 'id');
    }

    /**
     * Update the database with a funder object
     * @param $funder ReviewOfferPreference
     */
    function updateObject($funder) {
        $this->update(
            'UPDATE	funders
			SET	context_id = ?,
				funder_identification = ?
			WHERE funder_id = ?',
            array(
                (int) $funder->getContextId(),
                $funder->getFunderIdentification(),
                (int) $funder->getId()
            )
        );
        $this->updateLocaleFields($funder);
    }

    /**
     * Delete a ReviewOfferPreference object.
     * @param $preference ReviewOfferPreference
     */
    function deleteObject($preference) {
        $this->deleteById($preference->getId());
    }

    /**
     * Generate a new ReviewOfferPreference object.
     * @return ReviewOfferPreference
     */
    function newDataObject() {
        return new ReviewOfferPreference();
    }

    /**
     * Return a new funder object from a given row.
     * @return ReviewOfferPreference
     */
    function _fromRow($row) {
        $preference = $this->newDataObject();
        $preference->setSubmissionId($row['submission_id']);
        $preference->setServiceUrl($row['service_url']);
        $preference->setIsSent($row['is_sent']);

        return $preference;
    }

}