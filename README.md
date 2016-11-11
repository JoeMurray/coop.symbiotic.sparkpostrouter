CiviCRM SparkPost Router
========================

This CiviCRM extension re-routes SparkPost webhooks to the final site.
This is useful for when using SparkPost subaccounts and you only have
one webhook configured (you cannot really have multiple webhooks anyway,
since SparkPost will call all of them for all events).

This extension does not supplement, but rather complements, the excellent
SparkPost extension by CiviDesk (https://github.com/cividesk/com.cividesk.email.sparkpost/).

Requirements
------------

- CiviCRM >= 4.7
- PHP 5.6
- MySQL 5.7 or MariaDB 10.1

Installation
------------

If installing from source, you must have composer and run "composer install".

Todo
----

- Rename API Sparkpostrouter.process_messages to Job.sparkpostrouter_process?
- Fix subaccount/sender_domain mapping.
- How to handle bounces for transactional emails? (Mandrill had a fake mailing) - for com.cividesk.email.sparkpost
- Log an Activity with the exact bounce message? - for com.cividesk.email.sparkpost

Support
-------

Please post bug reports in the issue tracker of this project on github:  
https://github.com/coopsymbiotic/coop.symbiotic.sparkpostrouter/issues

While we do our best to provide community support for our extensions, please
consider financially contributing to support or development of this extension
if you can.

Commercial support available from Coop SymbioTIC:  
https://www.symbiotic.coop/en

License
-------

Distributed under the terms of the GNU Affero General public license (AGPL).
See LICENSE.txt for details.

(C) 2016 Mathieu Lutfy <mathieu@symbiotic.coop>  
(C) 2016 Coop SymbioTIC <info@symbiotic.coop>
