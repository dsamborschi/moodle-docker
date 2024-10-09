<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/custommenu:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_PROHIBIT,
            'manager' => CAP_ALLOW,
        ],
    ],
];
