<?php

/**
 * Language strings for local_departmentcohorts
 */
defined('MOODLE_INTERNAL') || die();

// Plugin info
$string['pluginname'] = 'Department Cohorts';
$string['managecohorts'] = 'Manage Department Cohorts';
$string['managecohortsdesc'] = 'Create and synchronize cohorts based on user departments.';

// Filter & Search
$string['searchcohort'] = 'Search cohort';
$string['filter'] = 'Filter';
$string['bydepartment'] = 'By Department';
$string['byinstitution'] = 'By Company';
$string['bycustom'] = 'Custom (All Users)';
$string['byupload'] = 'Upload File (CSV)';
$string['bymanaged'] = 'Managed Cohorts';

// Table headers
$string['department'] = 'Department';
$string['institution'] = 'Company';
$string['cohortname'] = 'Cohort Name';
$string['users'] = 'Users';
$string['actions'] = 'Actions';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['email'] = 'Email';

// Status badges
$string['status_synced'] = 'Synced';
$string['status_unlinked'] = 'Unlinked';

// Actions
$string['view'] = 'View';
$string['delete'] = 'Delete';
$string['syncmembers'] = 'Sync Members';

// Custom cohort
$string['customcohortname'] = 'New Cohort Name';
$string['createcustomcohort'] = 'Create & Sync Selected';

// CSV Upload
$string['uploadfile'] = 'Upload CSV File';
$string['downloadtemplate'] = 'Download Template';
$string['selectfile'] = 'Select CSV file';
$string['uploadprocessing'] = 'Process & Create Cohorts';
$string['uploadhelp'] = 'Upload a CSV file with columns: email, cohortname. Each row assigns one user to a cohort.';

// Modal
$string['userspreview'] = 'Users Preview';

// Notifications (used in PHP)
$string['memberssynced'] = 'Members synced successfully';
$string['uploadsuccess'] = 'Upload completed successfully';
$string['cohortdeleted'] = 'Cohort deleted';
$string['nocohortsfound'] = 'No cohorts found';
$string['nousersfound'] = 'No users found';
$string['syncall'] = 'Sync All';
$string['syncallconfirm'] = 'Are you sure you want to sync all groups?';
$string['syncallcomplete'] = 'All groups synced successfully';
