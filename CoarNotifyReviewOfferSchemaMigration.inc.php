<?php

/**
 * @file plugins/generic/coarNotifyReviewOffer/CoarNotifyReviewOfferSchemaMigration.inc.php
 *
 * @class CoarNotifyReviewOfferSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CoarNotifyReviewOfferSchemaMigration extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void {
        Capsule::schema()->create('review_offer_preferences', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->string('service_url', 255);
            $table->bigInteger('submission_id');
            $table->boolean('is_sent');
        });
    }
}