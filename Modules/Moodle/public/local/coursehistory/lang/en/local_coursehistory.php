<?php
// This file is part of Inteqc Company Configuaration 
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$string['pluginname']       = 'Course History';
$string['coursehistory']     = 'Course History';

// Form fields
$string['courseinfo']        = 'Course Information';
$string['idnumber']          = 'Course ID / Code';
$string['idnumber_help']     = 'Enter the standard course ID or subject code (if any).';
$string['coursename']       = 'Course Name';
$string['coursename_help']  = 'Enter the full name of the external course you completed.';
$string['startdate']       = 'Start Date';
$string['enddate']         = 'End Date';
$string['occurrence']      = 'Session / Occurrence';
$string['occurrence_help'] = 'Specify the session or batch info (e.g., Batch 1/2024)';
$string['coursetype']      = 'Course Type';
$string['coursetype_mandatory'] = 'Mandatory Course';
$string['coursetype_plan']      = 'Yearly Plan Course';
$string['coursetype_elective']  = 'Elective Course';
$string['instructorname']   = 'Instructor Name';
$string['organization']     = 'Institution / Organization';
$string['certificatefile']  = 'Certificate File';
$string['uploadcertificate']     = 'Upload Certificate';
$string['uploadcertificate_help'] = 'Upload your certificate or proof of completion. Accepted formats: PDF, JPG, PNG (max 5MB).';

// Actions
$string['submitcourse']     = 'Add Completed Course';
$string['submitcourse_desc'] = 'Submit a course that you have previously completed. Please fill in all required fields and upload your certificate.';
$string['approve']          = 'Approve';
$string['reject']           = 'Reject';
$string['view']             = 'View';

// Status
$string['status']           = 'Status';
$string['status_pending']   = 'Pending Review';
$string['status_approved']  = 'Approved';
$string['status_rejected']  = 'Rejected';

// Messages
$string['submitsuccess']    = 'Course history submitted successfully! It will be reviewed by an administrator.';
$string['submitsuccess_autoapproved'] = 'Course history submitted successfully! It will be reviewed by an administrator.';
$string['submitsuccess_pending']      = 'Course history submitted successfully! It will be reviewed by an administrator.';
$string['coursematched']    = 'This course matches an existing course in the system: "{$a}". (Requires manual review)';
$string['approved_success'] = 'Submission has been approved.';
$string['rejected_success'] = 'Submission has been rejected.';
$string['err_required']     = 'This field is required.';
$string['err_invalidfile']  = 'Invalid file. Please upload a valid certificate file (PDF, JPG, or PNG only).';

// Review
$string['reviewsubmissions']       = 'Review Course Submissions';
$string['reviewedby_label']        = 'Reviewed By';
$string['reviewcomment']           = 'Review Comment';
$string['reviewcomment_placeholder'] = 'Add a comment (optional)...';
$string['reviewactions']           = 'Review Actions';
$string['backtoreview']            = 'Back to Review List';
$string['backtohistory']           = 'Back to Course History';
$string['gotoreview']              = 'Go to Review Page';

// Table & Profile
$string['learner']          = 'Learner';
$string['coursematch']      = 'Course Match';
$string['matched']          = 'Matched';
$string['nomatch']          = ' No matching course in system';
$string['coursenotfound']   = 'Matched course no longer exists';
$string['datesubmitted']    = 'Date Submitted';
$string['actions']          = 'Actions';
$string['viewsubmission']   = 'View Submission';
$string['nosubmissions']    = 'No course submissions yet.';
$string['nofile']           = 'No certificate file uploaded.';
$string['certificate_preview'] = 'Certificate Preview';

// Stats
$string['stat_total']       = 'Total';
$string['stat_approved']    = 'Approved';
$string['stat_pending']     = 'Pending';
$string['stat_rejected']    = 'Rejected';

// Filters
$string['filter_all']       = 'All';
$string['filter_pending']   = 'Pending';
$string['filter_approved']  = 'Approved';
$string['filter_rejected']  = 'Rejected';

// Search and Filter
$string['searchfilter']     = 'Search & Filter';
$string['searchcourses']    = 'Search Courses';
$string['searchplaceholder'] = 'Search by course name, instructor, or organization...';
$string['statusfilter']     = 'Status Filter';
$string['allstatus']        = 'All Status';
$string['yearfilter']       = 'Year Filter';
$string['allyears']         = 'All Years';
$string['sourcetypefilter'] = 'Source Type Filter';
$string['allsources']       = 'All Sources';
$string['sourcetype']       = 'Source Type';
$string['internal_course']  = 'Internal Course';
$string['external_course']  = 'External Course';
$string['system_instructor'] = 'System';
$string['unknown_organization'] = 'Unknown';
$string['auto_approved']    = 'Auto-approved (System)';

// Calendar
$string['calendar']         = 'Course Calendar';
$string['today']            = 'Today';
$string['thisweek']         = 'This Week';
$string['thismonth']        = 'This Month';
$string['thisyear']         = 'This Year';
$string['daterange']        = 'Date Range';
$string['fromdate']         = 'From Date';
$string['todate']           = 'To Date';
$string['applyfilter']      = 'Apply Filter';
$string['scheduleview']    = 'Schedule View';

// Capabilities
$string['coursehistory:submit']  = 'Submit external course history';
$string['coursehistory:review']  = 'Review all course history submissions';
$string['coursehistory:reviewteam'] = 'Review course history from own team only';
$string['coursehistory:viewall'] = 'View all users course history';

// Review list
$string['review_action']        = 'Review';
$string['review_date']          = 'Review Date';
$string['auto_approved']        = 'Auto-approved (Internal System)';
$string['error_duplicate_record'] = 'You have already recorded this course for this time period. Please check your history.';
$string['pending_review_count'] = '{$a} submission(s) pending review';

// Notifications
$string['notify_approved_subject'] = 'Your course history has been approved';
$string['notify_rejected_subject'] = 'Course History Rejected';
$string['notify_status_body']      = 'Your course history submission has been updated:

Course: {$a->coursename}
Status: {$a->status}
Reviewed by: {$a->reviewer}
Comment: {$a->comment}
Date: {$a->date}';

// Message provider names
$string['messageprovider:statuschanged'] = 'Course history approval status changed';
$string['messageprovider:newsubmission'] = 'New course history submission';

