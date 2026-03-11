<?php
/**
 * Settings integration
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('users', new admin_externalpage(
        'local_departmentcohorts_manage',
        get_string('managedeptcohorts', 'local_departmentcohorts'),
        new moodle_url('/local/departmentcohorts/index.php'),
        'local/departmentcohorts:manage'
    ));
}
