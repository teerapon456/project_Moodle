<?php

/**
 * Capabilities
 */
defined('MOODLE_INTERNAL') || die();

$capabilities = [
  'local/departmentcohorts:manage' => [
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => [
      'admin' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ],
    'clonepermissionsfrom' => 'moodle/site:config'
  ],
];
