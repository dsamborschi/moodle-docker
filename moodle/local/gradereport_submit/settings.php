<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_gradereport_submit', get_string('local_gradereport_submit', 'local_gradereport_submit'));

    //Exernal URL API to post grades to
    $settings->add(new admin_setting_configtext(
        'local_gradereport_submit/externalurl',
        get_string('externalurl', 'local_gradereport_submit'),
        get_string('externalurldesc', 'local_gradereport_submit'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_gradereport_submit/authtoken',
        get_string('authtoken', 'local_gradereport_submit'),
        get_string('authtokendesc', 'local_gradereport_submit'),
        '',
        PARAM_URL
    ));

      //Lock editing the course grades
      $settings->add(new admin_setting_configcheckbox(
        'local_gradereport_submit/lockcourseediting',  // Name of the setting (used in the database)
        get_string('checkboxsetting', 'local_gradereport_submit'),  // Human-readable name
        get_string('checkboxsetting_desc', 'local_gradereport_submit'),  // Description
        0  // Default value (unchecked)
    ));

    $ADMIN->add('grades', new admin_externalpage('local_gradereport_submit',
    get_string('gradereport_submit_link', 'local_gradereport_submit'),
    new moodle_url('/local/gradereport_submit/pending.php')));

    $ADMIN->add('localplugins', $settings);
}
