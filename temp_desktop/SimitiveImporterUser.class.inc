<?php

/**
 * @file
 * Extended SimitiveImporterProcess to handle adding/updating users.
 *
 * Shares a lot with SimitiveImporterJobRole and SimitiveImporterWorkgroup.
 */

class SimitiveImporterUser extends SimitiveImporterProcess {

  /**
   * The username we will load or generate.
   *
   * @var string
   */
  protected $username;

  /**
   * The user object.
   *
   * @var object
   */
  protected $user;

  /**
   * Cached/cloned version before modification, to check whether it's updated.
   *
   * @var object
   */
  protected $userCached;

  /**
   * Flag for SSO/External user
   * @var boolean
   */
  protected $isSSOUser = FALSE;

  /**
   * Calls the parent __contruct with some added extras.
   *
   * @see SimitiveImporterProcess::__construct()
   *
   * @param array $data
   *   Data array. @see SimitiveImporterProcess::__construct()
   * @param int $row_count
   *   Row count. @see SimitiveImporterProcess::__construct()
   * @param array $context
   *   Context reference. @see SimitiveImporterProcess::__construct()
   */
  public function __construct($data = NULL, $row_count = 0, &$context = NULL) {
    // Call the parent method.
    parent::__construct($data, $row_count, $context);
    // Load in the password inc file to allow for drupal pass hashing.
    require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
    // Set the template fields for the employee type.
    $this->template_fields = $template_fields = simitive_importer_simitive_importer_template_fields('employee');
  }

  /**
   * Calls the parent __destruct, but modifies the 'processing' message.
   *
   * @see SimitiveImporterProcess::__destruct()
   */
  public function __destruct() {
    // Call the parent method.
    parent::__destruct();
    // Modify messages.
    $this->messages['processing'] = 'Processing: ' . $this->username;
    // Reprint the messages now they've been changed.
    $this->printMessages();
  }

  /**
   * Check whether the user already exists. Bit messy at the moment.
   *
   * @param string $name
   *   Optional username to check against.
   *
   * @return object
   *   Object containing the uid, or FALSE if not found.
   */
  protected function checkExisting($name = NULL) {
    // If it's already been loaded, don't run the SQL statement again.
    if ($this->existing && !$name) {
      return $this->existing;
    }
    // Get the employee ID from the data.
    $employee_id = isset($this->data[$this->map['employee id']]) ? $this->data[$this->map['employee id']] : '';

    if ($employee_id != '') {
      if ($name) {
        // Check against both Employee ID and username.
        $existing = db_query(
          'SELECT uid from {users} u LEFT JOIN {field_data_field_employee_no} en ON u.uid = en.entity_id WHERE u.name = :name',
          array(
            ':name' => ($name ? $name : $this->username),
          )
        )->fetch();
      }
      else {
        // Check against just Employee ID.
        $existing = db_query(
          'SELECT uid from {users} u LEFT JOIN {field_data_field_employee_no} en ON u.uid = en.entity_id WHERE en.field_employee_no_value = :employee_id',
          array(
            ':employee_id' => $employee_id,
          )
        )->fetch();
      }
    }
    else {
      // Check against just the username.
      $existing = db_query("SELECT uid from {users} u where u.name = :name", array(':name' => ($name ? $name : $this->username)))->fetch();
    }

    if (empty($existing)) {
      return FALSE;
    }
    return $existing;
  }

  /**
   * When creating a user, generate a username for them from their real name.
   *
   * @param string $forename
   *   The user's forename.
   * @param string $surname
   *   The user's surname.
   */
  protected function generateCleanUsername($forename = NULL, $surname = NULL) {
    if (!$forename || !$surname) {
      return FALSE;
    }
    // Concat the names with a full stop.
    $this->username = SimitiveImporter::sanitise($forename) . '.' . SimitiveImporter::sanitise($surname);
    // Replace spaces with full stops.
    $this->username = preg_replace('/\s+/', '.', $this->username);
    // Remove non-standard characters.
    $this->username = SimitiveImporter::sanitise(SimitiveImporter::translit($this->username));

    // Enumerate duplicate usernames.
    do {
      // Ensure the first value will be "name", not "name0".
      if (!isset($i)) {
        $i = '';
      }
      // Check whether the username already exists.
      $new_username = $this->username . (string) $i;
      $exists = $this->checkExisting($new_username);

      // Afterwards, start enumerating from 1.
      if (!is_numeric($i)) {
        $i = 0;
      }
      $i++;
      // Flood control!
      if ($i > 100) {
        break;
      }
    } while ($exists);

    $this->refreshInitialTable($this->username, $new_username);

    $this->username = $new_username;

    // Old, crappy version. Intentionally commented out.
    // $this->username = preg_replace('/[^A-Za-z0-9\.]/', '', $this->username);
    return $this;
  }

  /**
   * [DK] ticket #1681 Replaces the username field for users with entries in ig_core_initial
   * @param  string $old the user's old username
   * @param  string $new the user's new username
   */
  protected function refreshInitialTable($old, $new) {
    $query = db_update('ig_core_initial')
      ->fields(array(
        'name' => $new,
      ))
      ->condition('name', $old, '=')
      ->execute();
  }

  /**
   * Loads the workgroup taxonomy object and appends the user's UID to it.
   *
   * @param string $title
   *   The name of the workgroup.
   */
  protected function addContributingWorkgroup($title = NULL) {
    if (!$title) {
      return FALSE;
    }
    // Load the workgroup object from the title.
    $wg = SimitiveImporter::getWorkgroupObject($title);

    // If no members have been added, set the initial empty array.
    if (!isset($wg->field_members[LANGUAGE_NONE])) {
      $wg->field_members = array(
        LANGUAGE_NONE => array(),
      );
    }
    if (!empty($wg->field_members)) {
      // Run through each member and clear out null values or the user's UID.
      foreach ($wg->field_members[LANGUAGE_NONE] as $key => $member) {
        if (is_null($member['uid']) || $member['uid'] == $this->user->uid) {
          unset($wg->field_members[LANGUAGE_NONE][$key]);
        }
      }
      // Reindex the keys.
      $wg->field_members[LANGUAGE_NONE] = array_values($wg->field_members[LANGUAGE_NONE]);
    }
    else {
      $wg->field_members[LANGUAGE_NONE] = array();
    }
    // Add in the new UID.
    $wg->field_members[LANGUAGE_NONE][] = array('uid' => $this->user->uid);

    // Save the workgorup.
    taxonomy_term_save($wg);
    return $wg;
  }

  /**
   * Adds multiple job roles to the user object.
   *
   * @param string $title
   *   The name of the job role.
   */
  protected function addOtherJobRole($title = NULL) {
    if (!$title) {
      return FALSE;
    }
    // Load the job role's TID from the job role name.
    $job_role_id = SimitiveImporter::getJobRoleID($title);

    if (!empty($this->user->field_job)) {
      // Clear up any duplicates or mistakes.
      foreach ($this->user->field_job[LANGUAGE_NONE] as $key => $job) {
        if (is_null($job['tid']) || $job['tid'] == $job_role_id) {
          unset($this->user->field_job[LANGUAGE_NONE][$key]);
        }
      }
      // Reindex the keys.
      $this->user->field_job[LANGUAGE_NONE] = array_values($this->user->field_job[LANGUAGE_NONE]);
    }
    else {
      $this->user->field_job[LANGUAGE_NONE] = array();
    }
    // Add in the new TID to the user.
    $this->user->field_job[LANGUAGE_NONE][] = array('tid' => SimitiveImporter::getJobRoleID($title));
    return $this;
  }

  /**
   * Create a Drupal role for the user.
   *
   * @param string $role
   *   The role name as plain text.
   */
  protected function addRole($role) {
    // Find the role ID from the list of user roles.
    $rid = array_search(SimitiveImporter::sanitise($role), user_roles());
    if (empty($rid)) {
      drupal_set_message(t('Could not add role :role as it does not exist.', array(':role' => $role)), 'error', FALSE);
      return FALSE;
    }
    // Append the RID to the list of roles.
    $this->user->roles[$rid] = $role;
  }

  /**
   * Remove a Drupal role for the user.
   *
   * @param string $role
   *   The role name as plain text.
   */
  protected function removeRole($role) {
    // Find the role ID from the list of user roles.
    $rid = array_search(SimitiveImporter::sanitise($role), $this->user->roles);
    if (empty($rid)) {
      return FALSE;
    }
    // Remove the RID to the list of roles.
    unset($this->user->roles[$rid]);
  }

  /**
   * Set all fields on the user object, some specified and some looped-through.
   */
  protected function setFields() {
    // Add in the standard fields on the user.
    // @see SimitiveImporterProcess->addField()
    $this->addField($this->user->status, 'status', 'int');
    $this->addField($this->user->mail, 'email address', 'string');
    $this->addField($this->user->timezone, 'timezone', 'string');

    // We can assume that they will have the 'employee' role.
    $this->addRole('employee');

    // Add field specific logic.
    // This should remain as agnostic as possible.
    foreach ($this->template_fields as $field_id => $field) {

      if (!isset($this->map[SimitiveImporter::sanitise($field['name'])])) {
        continue;
      }
      $data = $this->data[$this->map[SimitiveImporter::sanitise($field['name'])]];
      if (!$data) {
        continue;
      }

      // Run any custom logic functions that will modify the data.
      if (isset($field['custom_logic']) && is_callable($field['custom_logic'])) {
        // Call the function and pass $data in as a reference.
        call_user_func_array($field['custom_logic'], array(&$data));
      }

      // if (isset($field['user_reference']) && $field['user_reference']) {
      //   dsm('Yep, reference');
      //   $this->addUserReference(SimitiveImporter::sanitise($data), $this->data[$this->map['employee id']]);
      //   continue;
      // }


      if (isset($field['user_reference']) && $field['user_reference'] && SimitiveImporter::getVar('ignoreUserReferences')) {
        continue;
      }

      // Get the field suffix, ie uid, tid, value etc.
      $field_suffix = (isset($field['type']) ? $field['type'] : 'value');

      // Add 'use module' roles, if set.
      if (isset($field['module_use']) && $field['module_use'] != '') {
        if ($data == SimitiveImporter::BLANKOUT_CHAR) {
          $this->removeRole($field['module_use']);
        }
        else {
          $this->addRole($field['module_use']);
        }
        continue;
      }
      // Explode any fields specifically set as "multiple" by its delimiter.
      if (isset($field['multiple']) && $field['multiple']) {
        $fragments = explode(SimitiveImporterProcess::MULTIPLE_DELIMITER, $data);
        if (count($fragments) === 0) {
          continue;
        }
        foreach ($fragments as $fragment) {
          if ($field_id == 'contributing_workgroups') {
            $workgroup = $this->addContributingWorkgroup($fragment);
            continue;
          }
          if ($field_id == 'other_job_roles') {
            $job_role = $this->addOtherJobRole($fragment);
            continue;
          }

          if (isset($field['field'])) {
            $this->user->{$field['field']}[LANGUAGE_NONE][0][$field_suffix] = SimitiveImporter::translit($fragment);
          }

          if ($field_id == 'advanced_user') {
            $this->addRole($fragment);
          }
        }
        continue;
      }
      if ($field_id == 'primary_workgroup') {
        $this->user->{$field['field']}[LANGUAGE_NONE][0][$field_suffix] = SimitiveImporter::getWorkgroupID($data);
        continue;
      }
      if ($field_id == 'primary_job_role') {
        $this->user->{$field['field']}[LANGUAGE_NONE][0][$field_suffix] = SimitiveImporter::getJobRoleID($data);
        continue;
      }
      if (isset($field['field'])) {
        $this->user->{$field['field']}[LANGUAGE_NONE][0][$field_suffix] = SimitiveImporter::translit($data);
      }

    }
  }

  /**
   * Add a row to the ig_core_initial database table.
   *
   * This is to allow emails to be sent out to the user to give them their
   * password and such. The initial password is saved as plain text, even
   * though this is shockingly insecure :)
   */
  protected function setInitialTable() {
    // [DK] ticket #1127 only do this if SSO is off or if they're not an external user
    if (ig_core_check_initial($this->user)) {
      db_merge('ig_core_initial')
        ->key(array('uid' => $this->user->uid))
        ->fields(array(
          'uid'  => $this->user->uid,
          'name' => $this->user->name,
          'pass' => $this->user->passPlainText,
          'sent' => 0,
        ))
        ->execute();
    }

    return $this;
  }

  /**
   * Update function. This is currently used only by the "quickFix" mode.
   *
   * @todo: Think of a way to merge this in with similar code in the create()
   * method. It does pretty much the exact same thing.
   */
  public function update() {
    parent::update();
    if ($this->existing = $this->checkExisting()) {
      if ($this->getVar('skipDuplicates')) {
        $this->skip = TRUE;
      }
      $entity = entity_load('user', array($this->existing->uid));
      $this->user = reset($entity);

      $this->username = $this->user->name;
      $this->userCached = clone $this->user;

      $this->setFields();
    }

    return $this;
  }

  /**
   * Create the new user. This also checks if it's a dupe and updates if so.
   */
  public function create() {
    parent::create();

    // [SH] If a CAS SSO name was included we should use it.
    if (module_exists('simitive_cas_sso_usernames') && ($this->data[$this->map['non-sso user']] == '0' || strtolower($this->data[$this->map['non-sso user']]) == 'no' || strtolower($this->data[$this->map['non-sso user']]) == 'false' || $this->data[$this->map['non-sso user']] == '')) {
      $this->username = $this->data[$this->map['sso username']];
      // Have a fallback..
      if (empty($this->username)) {
        $this->generateCleanUsername($this->data[$this->map['first name']], $this->data[$this->map['last name']]);
      }
      $this->isSSOUser = TRUE;
    }
    else {
      // Create a clean username from the first name and last name.
      $this->generateCleanUsername($this->data[$this->map['first name']], $this->data[$this->map['last name']]);
      $this->data[$this->map['sso username']] = $this->username;
    }

    // Check if the user already exists.
    if ($this->existing = $this->checkExisting()) {
      if ($this->getVar('skipDuplicates')) {
        $this->skip = TRUE;
        if (SimitiveImporter::DISPLAY_DEBUG) {
          drupal_set_message(t('User @user already exists, skipping...', array('@user' => $this->username)));
        }
        return $this;
      }
      if (SimitiveImporter::DISPLAY_DEBUG) {
        drupal_set_message(t('User @user already exists, updating...', array('@user' => $this->username)));
      }
      // Load the user.
      $entity = entity_load('user', array($this->existing->uid));
      // Strip away the pointless wrapping.
      $this->user = reset($entity);

      $old_name = $this->user->name;

      if (!$this->isSSOUser) {
        $this->user->name = $this->username;
      }
      // Clone it to a cached version to check against later.
      $this->userCached = clone $this->user;

      $new_name = $this->user->name;

      // [DK] ticket #1681
      $this->refreshInitialTable($old_name, $new_name);
    }
    else {
      if (parent::DISPLAY_DEBUG) {
        drupal_set_message(t('Creating user @user...', array('@user' => $this->username)));
      }
      // Create a new user entity.
      $this->user = entity_create('user', array());
      // Generate a random password...
      $this->user->passPlainText = user_password();
      // ...and save a hashed version.
      $this->user->pass = user_hash_password($this->user->passPlainText);

      if (!$this->username) {
        drupal_set_message(t('Error! Username could not be generated. Possibly no forename or surname were provided...'), 'error');
        $this->skip = TRUE;
        return $this;
      }
      $this->user->name = $this->username;

      // General default values. Can add custom functionality later, if needed.
      $this->user->field_visual[LANGUAGE_NONE][0]['value'] = '0';
      $this->user->data['htmlmail_plaintext'] = FALSE;
      $this->user->data['overlay'] = 1;

    }
    // Modify or add all the non Drupal standard fields.
    $this->setFields();
    return $this;
  }

  /**
   * Save the user entity.
   */
  public function save() {
    parent::save();
    // Skip if the user equals cached version. This is very sensitive and
    // requires the original versions to be identical in every way.
    if ($this->user == $this->userCached) {
      $this->skip = TRUE;
    }

    if (!$this->skip) {
      // Save the entity.
      entity_save('user', $this->user);

      if ($this->existing) {
        $this->context['results']['updated_no']++;
      }
      else {
        $this->setInitialTable();
        $this->context['results']['created_no']++;
      }

      // Update and save the importer_task.
      $this->updateTask();

    }
    else {
      $this->context['results']['skipped_no']++;
    }
    return $this;
  }

}
