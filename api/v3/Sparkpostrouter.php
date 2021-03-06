<?php

/**
 * Route the messages to their final destination.
 * Implements Sparkpostrouter.process_messages
 *
 * @param  array  input parameters
 *
 * @return array API Result Array
 * @static void
 * @access public
 */
function civicrm_api3_sparkpostrouter_process_messages($params) {
  $processed = 0;
  $errors = 0;

  require 'vendor/autoload.php';
  $client = new GuzzleHttp\Client();

  $custom_table_name = CRM_Core_DAO::singleValueQuery('SELECT table_name FROM civicrm_custom_group WHERE name = "Sparkpost_Router"');
  $dao = NULL;

  // Allow force-replay of a specific message if the ID is provided.
  if (!empty($params['id'])) {
    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sparkpost_router WHERE id = %1', [
      1 => [$params['id'], 'Positive'],
    ]);
  }
  else {
    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sparkpost_router WHERE relay_status = 0');
  }

  while ($dao->fetch()) {
    $event = json_decode($dao->data);
    $friendly_from = $event->friendly_from;
    $sender_domain = explode('@', $friendly_from)[1];
    $webhook_url = NULL;

    if (!in_array($event->type, ['bounce', 'spam_complaint', 'policy_rejection', 'open', 'click'])) {
      // FIXME:
      // - move to BAO
      // - document statuses? (ex: 3 = ignored)
      CRM_Core_DAO::executeQuery('UPDATE civicrm_sparkpost_router SET relay_status = 3, relay_date = NOW() WHERE id = %1', [
        1 => [$dao->id, 'Positive'],
      ]);
      $processed++;
      continue;
    }

    // Lookup subaccount
    // TODO: move to BAO
    if (isset($event->subaccount_id)) {
      $webhook_url = CRM_Core_DAO::singleValueQuery('SELECT sparkpost_webhook_url FROM ' . $custom_table_name . ' WHERE sparkpost_subaccount = %1', [
        1 => [$event->subaccount_id, 'Integer'],
      ]);
    }

    // Lookup by sender domain, if subaccount not found
    // TODO: move to BAO
    if (empty($webhook_url)) {
      // FIXME: this isn't ideal, could cause problems if: fooacme.org and acme.org
      // Then again, that's why we use subaccounts, so this is just temporary?
      $webhook_url = CRM_Core_DAO::singleValueQuery('SELECT sparkpost_webhook_url FROM ' . $custom_table_name . ' WHERE sparkpost_domains LIKE %1', [
        1 => ['%' . $sender_domain . '%', 'String'],
      ]);
    }

    if (!$webhook_url) {
      Civi::log()->warning(ts("Could not find webhook for sender: %1", [1=>$sender_domain]));
      // FIXME:
      // - move to BAO
      // - log a more explicit error?
      CRM_Core_DAO::executeQuery('UPDATE civicrm_sparkpost_router SET relay_status = 2, relay_date = NOW() WHERE id = %1', [
        1 => [$dao->id, 'Positive'],
      ]);
      $errors++;
      continue;
    }

    $obj = new stdClass();
    $obj->msys = new stdClass();
    $obj->msys->message_event = json_decode($dao->data);

    $data = [
      0 => $obj,
    ];

    $response = $client->post($webhook_url, [
      'body' => json_encode($data),
    ]);

    $code = $response->getStatusCode();

    if ($code == 200) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_sparkpost_router SET relay_status = 1, relay_date = NOW() WHERE id = %1', [
        1 => [$dao->id, 'Positive'],
      ]);
      $processed++;
    }
    else {
      // FIXME:
      // - move to BAO
      // - log a more explicit error?
      CRM_Core_DAO::executeQuery('UPDATE civicrm_sparkpost_router SET relay_status = 2, relay_date = NOW() WHERE id = %1', [
        1 => [$dao->id, 'Positive'],
      ]);
      $errors++;
    }
  }

  $values = [
    'processed' => $processed,
    'errors' => $errors,
  ];

  return civicrm_api3_create_success($values, $params, 'Job', 'process_messages');
}
