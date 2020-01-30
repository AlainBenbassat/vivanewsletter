<?php

header('Content-Type: application/json');

$success = FALSE;

try {
  // make sure we have the correct parameters
  if (array_key_exists('email', $_POST) && array_key_exists('language', $_POST)) {
    // bootstrap civicrm
    require_once '../civicrm/civicrm.config.php';
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
    CRM_Utils_System::loadBootStrap(array(), FALSE);

    // see if the email address exists in the database
    $sql = "
      select
        c.id
      from
        civicrm_contact c
      inner join
        civicrm_email e on c.id = e.contact_id
      where
        c.is_deleted = 0
      and
        e.email = %1
    ";
    $sqlParams = [
      1 => [$_POST['email'], 'String'],
    ];
    $contactID = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

    if ($contactID) {
      // update the language
      $params = [
        'id' => $contactID,
        'preferred_language' => $_POST['language'] == 'nl' ? 'nl_NL' : 'fr_FR',
      ];
      civicrm_api3('Contact', 'create', $params);
    }
    else {
      // create the contact
      $params = [
        'first_name' => $_POST['email'],
        'contact_type' => 'Individual',
        'api.email.create' => [
          'email' => $_POST['email'],
          'location_type_id' => 1,
        ],
        'preferred_language' => $_POST['language'] == 'nl' ? 'nl_NL' : 'fr_FR',
      ];
      $result = civicrm_api3('Contact', 'create', $params);
      $contactID = $result['id'];
    }

    // add contact to newsletter group
    $params = [
      'group_id' => 1515,
      'contact_id' => $contactID,
      'status' => 'Added',
    ];
    civicrm_api3('GroupContact', 'create', $params);

    $success = TRUE;
  }
  else {
    $success = FALSE;
  }
}
catch (Exception $e) {
  watchdog('Viva newsletter', $e->getMessage());
  $success = FALSE;
}

if ($success) {
  echo '{"success":"true"}';
}
else {
  echo '{"success":"false"}';
}
