# COAR Notify Review Offer Plugin


⚠️ 👷‍♂ **Still under active development**️👷 ⚠️

About
-----
This plugin enables the automatic & manual sending of preprint review offer notifications to target review services 
within the OPS environment. By utilizing the COAR Notify protocol, the plugin will ensure seamless and standardized 
notifications, simplifying the process for authors, reviewers, and editors alike.

Features
-----

### Configuration
Allowing admins to configure a list of target review services.


### Manual Review Request Sending 
Allowing authors and contributors to manually request reviews from review services for published preprints.


### Automated Review Request Sending
Allowing authors and contributors to opt into automatically send requests for reviews for preprints upon publication.


License
-------
This plugin is licensed under the GNU General Public License v3. See the file LICENSE for the complete terms of this license.

System Requirements
-------------------
OPS 3.3.0

Install
-------

* Copy the release source or unpack the release package into the OPS plugins/generic/coarNotifyReviewOffer/ folder.
* Run `php tools/upgrade.php upgrade` from the OPS folder. This creates the needed database tables.
* Go to Settings -> Website -> Plugins -> Generic Plugin -> COAR Notify Review Offer Plugin and enable the plugin.
 