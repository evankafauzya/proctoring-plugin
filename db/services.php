<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External services for the quizaccess_proctoring plugin.
 *
 * This file defines external services for the `quizaccess_proctoring` plugin,
 * including methods for sending and retrieving webcam snapshots,
 * as well as validating faces for proctoring purposes.
 *
 * @package    quizaccess_proctoring
 * @copyright  2024 Brain Station 23, 2026 Evanka Fauzya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

defined('MOODLE_INTERNAL') || die();

// List of external functions for the quizaccess_proctoring plugin.
$functions = [
    // Send a camera snapshot on the given session.
    'quizaccess_proctoring_send_camshot' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'send_camshot',
        'description'  => 'Send a camera snapshot on the given session.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Send a camera snapshot to validate the face.
    'quizaccess_proctoring_validate_face' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'validate_face',
        'description'  => 'Send a camera snapshot to validate face.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Log multi-user alert
    'quizaccess_proctoring_log_multiuser_alert' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'log_multiuser_alert',
        'description'  => 'Log a multi-user detection alert.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Log eye tracking alert
    'quizaccess_proctoring_log_eyetrack_alert' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'log_eyetrack_alert',
        'description'  => 'Log an eye tracking/gaze alert.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Log re-verification attempt
    'quizaccess_proctoring_log_reverification' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'log_reverification',
        'description'  => 'Log a periodic re-verification attempt.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Update re-verification status
    'quizaccess_proctoring_update_reverification' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'update_reverification',
        'description'  => 'Update re-verification status after completion.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Generate analytics report
    'quizaccess_proctoring_generate_analytics' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'generate_analytics',
        'description'  => 'Generate comprehensive analytics report after quiz.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Get analytics for a report
    'quizaccess_proctoring_get_analytics' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'get_analytics',
        'description'  => 'Retrieve analytics report for a quiz attempt.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'quizaccess/proctoring:sendcamshot',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // Extend the current quiz attempt's deadline by N seconds (used to give
    // back the time spent on a forced face-verification modal).
    'quizaccess_proctoring_extend_attempt_time' => [
        'classname'    => 'quizaccess_proctoring_external',
        'methodname'   => 'extend_attempt_time',
        'description'  => 'Extend the running quiz attempt deadline to compensate for verification time.',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'mod/quiz:attempt',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];

