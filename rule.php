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
 * Implementaton for the quizaccess_proctoring plugin.
 *
 * @package    quizaccess_proctoring
 * @copyright  2020 Brain Station 23, 2026 Evanka Fauzya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file must be included within the Moodle framework.
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/classes/link_generator.php');

// Check if the Moodle version is 4.2 or higher, which introduced updates to the access rule base class.
if (class_exists('\mod_quiz\local\access_rule_base')) {
    // Use class aliases for compatibility with Moodle 4.2 or higher.
    class_alias('\mod_quiz\local\access_rule_base', '\quizaccess_proctoring_parent_class_alias');
    class_alias('\mod_quiz\form\preflight_check_form', '\quizaccess_proctoring_preflight_form_alias');
} else {
    // Include the legacy access rule base class for older Moodle versions.
    require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
    class_alias('\quiz_access_rule_base', '\quizaccess_proctoring_parent_class_alias');
    class_alias('\mod_quiz_preflight_check_form', '\quizaccess_proctoring_preflight_form_alias');
}

/**
 * Quiz access proctoring class.
 *
 * Extends the parent class to implement custom proctoring behavior.
 */
class quizaccess_proctoring extends quizaccess_proctoring_parent_class_alias {
    /**
     * Determines whether a preflight check is required for the given attempt.
     *
     * @param int $attemptid The ID of the attempt being checked.
     * @return bool True if a preflight check is required, false otherwise.
     */
    public function is_preflight_check_required($attemptid) {
        $script = $this->get_topmost_script();
        $base = basename($script);

        return ($base === 'view.php');
    }

    /**
     * Get the file path of the topmost script in the call stack.
     *
     * @return string The file path of the topmost script.
     * @throws coding_exception If an error occurs while retrieving the script.
     */
    public function get_topmost_script() {
        $backtrace = debug_backtrace(
            defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? DEBUG_BACKTRACE_IGNORE_ARGS : false
        );
        $topframe = array_pop($backtrace);

        return $topframe['file'];
    }

    /**
     * Retrieve course ID, quiz ID, and course module ID from the preflight form.
     *
     * @param quizaccess_proctoring_preflight_form_alias $quizform The preflight form instance.
     * @return array An associative array containing 'courseid', 'quizid', and 'cmid'.
     * @throws coding_exception If an error occurs during processing.
     */
    public function get_courseid_cmid_from_preflight_form(quizaccess_proctoring_preflight_form_alias $quizform) {
        return [
            'courseid' => $this->quiz->course,
            'quizid' => $this->quiz->id,
            'cmid' => $this->quiz->cmid,
        ];
    }


    /**
     * Generate the modal content for the webcam proctoring interface.
     *
     * @param mixed $quizform The quiz form instance.
     * @param mixed $faceidcheck A flag indicating whether face ID check is required.
     * @return string The rendered HTML content for the modal.
     * @throws coding_exception If an error occurs during rendering.
     */
    public function make_modal_content($quizform, $faceidcheck) {
        global $OUTPUT;

        // Prepare data for Mustache template rendering.
        $data = [
            'header' => get_string('openwebcam', 'quizaccess_proctoring'),
            'proctoringstatement' => get_string(
                'proctoringstatement',
                'quizaccess_proctoring'
            ),
            'videonotavailable' => get_string('videonotavailable', 'quizaccess_proctoring'),
            'photoalt' => get_string('photoalttext', 'quizaccess_proctoring'),
        ];

        // Render the content using Mustache template.
        return $OUTPUT->render_from_template('quizaccess_proctoring/cam_modal_content', $data);
    }

    /**
     * Adds preflight check form fields.
     *
     * @param quizaccess_proctoring_preflight_form_alias $quizform The preflight form instance.
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param int $attemptid The quiz attempt ID.
     * @throws coding_exception If an error occurs during processing.
     */
    public function add_preflight_check_form_fields(
        quizaccess_proctoring_preflight_form_alias $quizform,
        MoodleQuickForm $mform,
        $attemptid
    ) {
        global $PAGE, $DB, $USER, $CFG;

        // Retrieve course and module data.
        $coursedata = $this->get_courseid_cmid_from_preflight_form($quizform);

        // Fetch camera shot delay configuration.
        $delaydata = $DB->get_record('config_plugins', [
            'plugin' => 'quizaccess_proctoring',
            'name' => 'autoreconfigurecamshotdelay',
        ]);
        $camshotdelay = !empty($delaydata) ? ((int)$delaydata->value * 1000) : 30000; // Default to 30 seconds if not configured.

        // Fetch face ID check setting.
        $faceidrow = $DB->get_record('config_plugins', [
            'plugin' => 'quizaccess_proctoring',
            'name' => 'fcheckstartchk',
        ]);
        $faceidcheck = $faceidrow->value ?? 0;

        // Fetch image width configuration.
        $imagewidth = get_config('quizaccess_proctoring', 'autoreconfigureimagewidth') ?? '';

        // Prepare data for the JavaScript module.
        $examurl = new moodle_url('/mod/quiz/startattempt.php');
        $apiendpoint = get_config('quizaccess_proctoring', 'bsapi') ?? 'http://localhost:5000';
        $record = [
            'id' => 0,
            'courseid' => (int)$coursedata['courseid'],
            'cmid' => (int)$coursedata['cmid'],
            'attemptid' => $attemptid,
            'imagewidth' => $imagewidth,
            'screenshotinterval' => $camshotdelay,
            'examurl' => $examurl->out(false),
            'apiendpoint' => $apiendpoint,
        ];

        // Include Face API JS library if required.
        $fcmethod = get_config('quizaccess_proctoring', 'fcmethod');
        $modelurl = null;
        // Always load face-api.min.js for face detection on the webcam
        $modelurl = $CFG->wwwroot . '/mod/quiz/accessrule/proctoring/thirdpartylibs/models';
        $PAGE->requires->js('/mod/quiz/accessrule/proctoring/amd/build/face-api.min.js', true);
        
        $PAGE->requires->js_call_amd('quizaccess_proctoring/startAttempt', 'setup', [$record, $modelurl]);

        // Add HTML wrapper for the form.
        $mform->addElement('html', "<div class='quiz-check-form'>");

        // Prepare user profile image URL.
        $profileimageurl = $USER->picture
            ? (new moodle_url("/user/pix.php/{$USER->id}/f1.jpg"))->out(false)
            : '';

        // Render modal content.
        $modalcontent = $this->make_modal_content($quizform, $faceidcheck);
        // Add modal content and action buttons to the form.
        $mform->addElement('html', $modalcontent);

        // Hidden form inputs.
        $hiddenvalue = sprintf(
            '<input type="hidden" id="courseidval" value="%d"/>
            <input type="hidden" id="cmidval" value="%d"/>
            <input type="hidden" id="profileimage" value="%s"/>',
            $coursedata['courseid'],
            $coursedata['cmid'],
            $profileimageurl
        );

        // Prepare action buttons if face validation is enabled.
        $actionbtns = '';
        if ($faceidcheck === '1') {
            $facevalidationlabel = get_string('modal:facevalidation', 'quizaccess_proctoring');
            $pending = get_string('modal:pending', 'quizaccess_proctoring');
            $validateface = get_string('modal:validateface', 'quizaccess_proctoring');
            $actionbtns = sprintf(
                "%s&nbsp;<span id='face_validation_result'>%s</span>
                <button id='fcvalidate' class='btn btn-primary mt-3' style='display: flex;
                                            justify-content: center; align-items: center;'>
                    <div class='proctoring-loadingspinner' id='loading_spinner'></div>%s
                </button>",
                $facevalidationlabel,
                $pending,
                $validateface
            );
        }

        if (!empty($actionbtns)) {
            $mform->addElement('html', "<div class='container'><div class='row'><div class='col'>{$actionbtns}</div></div></div>");
        }

        // Add hidden inputs and proctoring checkbox.
        $mform->addElement('html', $hiddenvalue);
        if ($faceidcheck === '1') {
            $mform->addElement('html', '<div id="form_activate" style="visibility: hidden">');
        }
        $mform->addElement('checkbox', 'proctoring', '', get_string('proctoringlabel', 'quizaccess_proctoring'));
        if ($faceidcheck === '1') {
            $mform->addElement('html', '</div>');
        }

        // Close the form wrapper.
        $mform->addElement('html', '</div>');

        // Add a validation rule for the proctoring checkbox.
        $mform->addRule('proctoring', get_string('youmustagree', 'quizaccess_proctoring'), 'required', null, 'client');
    }

    /**
     * Validate the preflight check.
     *
     * @param array $data Form data submitted by the user.
     * @param array $files Files uploaded during the form submission.
     * @param array $errors Array to hold validation errors.
     * @param int $attemptid The quiz attempt ID.
     * @return array Updated errors array.
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        // Extend validation from the parent class.
        if (method_exists(get_parent_class($this), 'validate_preflight_check')) {
            $errors = parent::validate_preflight_check($data, $files, $errors, $attemptid);
        }

        // Ensure the proctoring checkbox is checked.
        if (empty($data['proctoring'])) {
            $errors['proctoring'] = get_string('youmustagree', 'quizaccess_proctoring');
        }

        return $errors;
    }

    /**
     * Determine if the access rule should be applied to the quiz.
     *
     * @param quiz $quizobj Quiz object.
     * @param int $timenow Current timestamp.
     * @param bool $canignoretimelimits Flag to check if time limits can be ignored.
     * @return quiz_access_rule_base|null Returns an instance of the rule or null.
     */
    public static function make($quizobj, $timenow, $canignoretimelimits) {
        // Check if proctoring is required for the quiz.
        if (empty($quizobj->get_quiz()->proctoringrequired)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Add the proctoring required setting to the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform The quiz settings form object.
     * @param MoodleQuickForm $mform The Moodle form wrapper.
     */
    public static function add_settings_form_fields($quizform, MoodleQuickForm $mform) {
        // Add the "Proctoring Required" dropdown.
        $mform->addElement(
            'select',
            'proctoringrequired',
            get_string('proctoringrequired', 'quizaccess_proctoring'),
            [
                0 => get_string('notrequired', 'quizaccess_proctoring'),
                1 => get_string('proctoringrequiredoption', 'quizaccess_proctoring'),
            ]
        );

        // Add a help button for the proctoring setting.
        $mform->addHelpButton('proctoringrequired', 'proctoringrequired', 'quizaccess_proctoring');

        // In-quiz re-verification interval. 0 = disabled (only initial + pre-submit checks).
        $mform->addElement(
            'select',
            'reverifyinterval',
            get_string('setting:reverifyinterval', 'quizaccess_proctoring'),
            [
                0 => get_string('setting:reverifyinterval_disabled', 'quizaccess_proctoring'),
                60 => get_string('setting:reverifyinterval_1min', 'quizaccess_proctoring'),
                120 => get_string('setting:reverifyinterval_2min', 'quizaccess_proctoring'),
                180 => get_string('setting:reverifyinterval_3min', 'quizaccess_proctoring'),
                300 => get_string('setting:reverifyinterval_5min', 'quizaccess_proctoring'),
                600 => get_string('setting:reverifyinterval_10min', 'quizaccess_proctoring'),
            ]
        );
        $mform->setDefault('reverifyinterval', 120);
        $mform->addHelpButton('reverifyinterval', 'setting:reverifyinterval', 'quizaccess_proctoring');
        $mform->hideIf('reverifyinterval', 'proctoringrequired', 'eq', 0);

        // Whether to pause (extend) the quiz timer while the student verifies.
        $mform->addElement(
            'advcheckbox',
            'pausequiztime',
            get_string('setting:pausequiztime', 'quizaccess_proctoring'),
            get_string('setting:pausequiztime_label', 'quizaccess_proctoring')
        );
        $mform->setDefault('pausequiztime', 0);
        $mform->addHelpButton('pausequiztime', 'setting:pausequiztime', 'quizaccess_proctoring');
        $mform->hideIf('pausequiztime', 'proctoringrequired', 'eq', 0);
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted.
     * Called from quiz_after_add_or_update() in lib.php.
     *
     * @param object $quiz Data from the quiz form, including $quiz->id for the quiz being saved.
     * @throws dml_exception
     */
    public static function save_settings($quiz) {
        global $DB;

        // Check if proctoring is required for the quiz.
        if (empty($quiz->proctoringrequired)) {
            // Remove any existing proctoring settings if not required.
            $DB->delete_records('quizaccess_proctoring', ['quizid' => $quiz->id]);
            return;
        }

        // Sanitize: interval is one of the allowed values; pause is 0/1.
        $allowedintervals = [0, 60, 120, 180, 300, 600];
        $interval = isset($quiz->reverifyinterval) ? (int) $quiz->reverifyinterval : 120;
        if (!in_array($interval, $allowedintervals, true)) {
            $interval = 120;
        }
        $pause = !empty($quiz->pausequiztime) ? 1 : 0;

        $existing = $DB->get_record('quizaccess_proctoring', ['quizid' => $quiz->id]);
        if ($existing) {
            $existing->proctoringrequired = 1;
            $existing->reverifyinterval = $interval;
            $existing->pausequiztime = $pause;
            $DB->update_record('quizaccess_proctoring', $existing);
        } else {
            $DB->insert_record('quizaccess_proctoring', (object)[
                'quizid' => $quiz->id,
                'proctoringrequired' => 1,
                'reverifyinterval' => $interval,
                'pausequiztime' => $pause,
            ]);
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted.
     * Called from quiz_delete_instance() in lib.php.
     *
     * @param object $quiz Data from the database, including $quiz->id for the quiz being deleted.
     * @throws dml_exception
     */
    public static function delete_settings($quiz) {
        global $DB;

        // Remove all proctoring settings related to the quiz.
        $DB->delete_records('quizaccess_proctoring', ['quizid' => $quiz->id]);
    }

    /**
     * Return SQL needed to load settings from all access plugins in one query.
     * This optimizes performance for loading quiz settings.
     *
     * @param int $quizid The ID of the quiz for which settings are being loaded.
     * @return array Contains fields, joins, and params for the SQL query.
     */
    public static function get_settings_sql($quizid) {
        return [
            'proctoringrequired, proctoring.reverifyinterval, proctoring.pausequiztime',
            'LEFT JOIN {quizaccess_proctoring} proctoring ON proctoring.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Provide information about the restriction to display on the quiz view page.
     *
     * @return array Messages explaining the restriction.
     * @throws coding_exception
     */
    public function description() {
        global $PAGE;

        // Localized strings for user messages.
        $record = (object)[
            'allowcamerawarning' => get_string('warning:cameraallowwarning', 'quizaccess_proctoring'),
            'cameraallow' => get_string('info:cameraallow', 'quizaccess_proctoring'),
        ];

        // Initialize JS for proctoring with the required data.
        $PAGE->requires->js_call_amd('quizaccess_proctoring/proctoring', 'init', [$record]);

        // Messages for the quiz view page.
        $messages = [
            get_string('proctoringheader', 'quizaccess_proctoring'),
            $this->get_download_config_button(),
        ];

        return $messages;
    }

    /**
     * Sets up the attempt (review or summary) page with any special extra
     * properties required by this rule.
     *
     * @param moodle_page $page The page object to initialise.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function setup_attempt_page($page) {
        global $CFG, $DB, $COURSE, $USER;

        // Fetch parameters.
        $cmid = optional_param('cmid', 0, PARAM_INT);
        $attempt = optional_param('attempt', 0, PARAM_INT);
        // Set page properties.
        $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
        $page->set_popup_notification_allowed(false);
        $page->set_heading($page->title);

        if ($cmid) {
            // Fetch the course module record for the quiz.
            $contextquiz = $DB->get_record('course_modules', ['id' => $cmid]);

            if (!$contextquiz) {
                throw new coding_exception('Invalid course module ID.');
            }

            // Insert a placeholder log entry for the attempt. proctoring.js
            // uses its id to namespace silent-capture file storage. The row
            // itself carries no webcam picture and is filtered out of the
            // report (source = 'seed').
            $record = (object)[
                'courseid' => $COURSE->id,
                'quizid' => $contextquiz->id,
                'userid' => $USER->id,
                'webcampicture' => '',
                'status' => $attempt,
                'source' => 'seed',
                'timemodified' => time(),
            ];
            $record->id = $DB->insert_record('quizaccess_proctoring_logs', $record);

            // Retrieve screenshot delay and image width settings.
            $camshotdelay = (int)get_config('quizaccess_proctoring', 'autoreconfigurecamshotdelay') * 1000 ?: 30000;
            $imagewidth = (int)get_config('quizaccess_proctoring', 'autoreconfigureimagewidth') ?: 230;

            // Add additional data to the record.
            $quizurl = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
            $record->camshotdelay = $camshotdelay;
            $record->image_width = $imagewidth;
            $record->quizurl = $quizurl->out();

            // Configure face model URL and include JS.
            $fcmethod = get_config('quizaccess_proctoring', 'fcmethod');
            $modelurl = ($fcmethod === "BS")
                ? $CFG->wwwroot . '/mod/quiz/accessrule/proctoring/thirdpartylibs/models'
                : null;

            if ($modelurl) {
                $page->requires->js('/mod/quiz/accessrule/proctoring/amd/build/face-api.min.js', true);
            }

            // Initialise the proctoring setup with JavaScript.
            $page->requires->js_call_amd('quizaccess_proctoring/proctoring', 'setup', [$record, $modelurl]);

            // Add periodic (in-quiz) and pre-submission face re-verification.
            // This only runs when a face match method is configured.
            if ($fcmethod === 'BS') {
                // Per-quiz settings: interval (0 = disabled) and pause-timer flag.
                $proctoringsettings = $DB->get_record('quizaccess_proctoring',
                    ['quizid' => $contextquiz->instance]);
                $intervalseconds = $proctoringsettings
                    ? (int) $proctoringsettings->reverifyinterval
                    : 120;
                $pause = $proctoringsettings && !empty($proctoringsettings->pausequiztime);

                $page->requires->js_init_code(
                    $this->get_reverification_js(
                        (int) $COURSE->id,
                        (int) $cmid,
                        (int) $attempt,
                        $intervalseconds * 1000,
                        $pause
                    ),
                    true
                );
            }
        }
    }

    /**
     * Build the JavaScript for periodic and pre-submission face re-verification.
     *
     * - Re-verifies every $intervalms during the quiz with a blocking modal.
     *   If $intervalms === 0 the in-quiz check is disabled (only the
     *   pre-submission check on the summary page still runs).
     * - When $pausequiztime is true, the seconds the modal was open are
     *   added back to the quiz timer via the extend_attempt_time service.
     *
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID.
     * @param int $attemptid The quiz attempt ID (0 outside an attempt).
     * @param int $intervalms Re-verification interval, in milliseconds (0 = disabled).
     * @param bool $pausequiztime Whether to extend the quiz timer to compensate for verification.
     * @return string The JavaScript code (no surrounding script tags).
     */
    private function get_reverification_js(int $courseid, int $cmid, int $attemptid,
            int $intervalms, bool $pausequiztime): string {
        $pauseliteral = $pausequiztime ? 'true' : 'false';
        // Pull every UI string from the language pack so the modal renders
        // in the student's language. JSON-encoding keeps quotes and unicode
        // safe inside the inline JS block below.
        $strings = json_encode([
            'verifybtn'           => get_string('modal:verifybtn',           'quizaccess_proctoring'),
            'starting_camera'     => get_string('modal:starting_camera',     'quizaccess_proctoring'),
            'camera_ready'        => get_string('modal:camera_ready',        'quizaccess_proctoring'),
            'camera_failed'       => get_string('modal:camera_failed',       'quizaccess_proctoring'),
            'camera_not_ready'    => get_string('modal:camera_not_ready',    'quizaccess_proctoring'),
            'nosupport'           => get_string('modal:nosupport',           'quizaccess_proctoring'),
            'verifying'           => get_string('modal:verifying',           'quizaccess_proctoring'),
            'identity_verified'   => get_string('modal:identity_verified',   'quizaccess_proctoring'),
            'restoring_time'      => get_string('modal:restoring_time',      'quizaccess_proctoring'),
            'no_enrolled_photo'   => get_string('modal:no_enrolled_photo',   'quizaccess_proctoring'),
            'invalid_api'         => get_string('modal:invalid_api',         'quizaccess_proctoring'),
            'service_unavailable' => get_string('modal:service_unavailable', 'quizaccess_proctoring'),
            'face_not_matched'    => get_string('modal:face_not_matched',    'quizaccess_proctoring'),
            'verification_failed' => get_string('modal:verification_failed', 'quizaccess_proctoring'),
            'presubmit_heading'   => get_string('modal:presubmit_heading',   'quizaccess_proctoring'),
            'presubmit_message'   => get_string('modal:presubmit_message',   'quizaccess_proctoring'),
            'periodic_heading'    => get_string('modal:periodic_heading',    'quizaccess_proctoring'),
            'periodic_message'    => get_string('modal:periodic_message',    'quizaccess_proctoring'),
        ], JSON_UNESCAPED_UNICODE);
        return <<<JS
(function() {
    if (window.proctoringReverifyLoaded) {
        return;
    }
    window.proctoringReverifyLoaded = true;

    var COURSEID = {$courseid};
    var CMID = {$cmid};
    var ATTEMPTID = {$attemptid};
    var INTERVAL = {$intervalms};
    var PAUSE_QUIZ_TIME = {$pauseliteral};
    var STR = {$strings};
    var LAST_KEY = 'proctoring_reverify_last_' + CMID;
    var pauseStartedAt = 0;

    // Never run on the quiz review page.
    if (document.getElementById('page-mod-quiz-review')) {
        return;
    }
    var isSummary = !!document.getElementById('page-mod-quiz-summary');
    var currentSource = isSummary ? 'presubmit' : 'periodic';

    // If the teacher disabled in-quiz re-verification (interval = 0) and we are
    // NOT on the summary page, nothing to do here. The preflight check at quiz
    // start already happened, and the pre-submission check is only on /summary.
    if (INTERVAL === 0 && !isSummary) {
        return;
    }

    var video = document.createElement('video');
    video.setAttribute('autoplay', '');
    video.setAttribute('playsinline', '');
    video.muted = true;
    var canvas = document.createElement('canvas');
    var cameraReady = false;
    var modalOpen = false;
    var onSuccessCallback = null;
    var overlay, headingEl, msgEl, videoWrap, statusEl, verifyBtn;

    // sessionStorage can throw in some private-browsing contexts. Wrap so a
    // SecurityError on read doesn't kill the entire re-verification init.
    function lastVerified() {
        try {
            var v = sessionStorage.getItem(LAST_KEY);
            return v ? parseInt(v, 10) : 0;
        } catch (e) {
            return 0;
        }
    }
    function markVerified() {
        try {
            sessionStorage.setItem(LAST_KEY, Date.now().toString());
        } catch (e) {
            // Falls back to anchoring on next page load.
        }
    }

    function ensureCamera() {
        if (cameraReady && video.srcObject) {
            return Promise.resolve();
        }
        var existing = document.getElementById('video');
        if (existing && existing.srcObject) {
            video.srcObject = existing.srcObject;
            return video.play().then(function() {
                cameraReady = true;
            });
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error(STR.nosupport));
        }
        return navigator.mediaDevices.getUserMedia({video: true, audio: false}).then(function(stream) {
            video.srcObject = stream;
            return video.play();
        }).then(function() {
            cameraReady = true;
        });
    }

    function captureFrame() {
        var w = video.videoWidth || 320;
        var h = video.videoHeight || 240;
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(video, 0, 0, w, h);
        return canvas.toDataURL('image/png');
    }

    function buildModal() {
        overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;'
            + 'background:rgba(0,0,0,0.85);z-index:2147483000;display:none;'
            + 'align-items:center;justify-content:center;';
        var box = document.createElement('div');
        box.style.cssText = 'background:#fff;border-radius:10px;padding:24px;max-width:420px;'
            + 'width:90%;text-align:center;font-family:sans-serif;box-shadow:0 8px 40px rgba(0,0,0,0.5);';
        headingEl = document.createElement('h3');
        headingEl.style.cssText = 'margin:0 0 8px;font-size:20px;color:#1d2125;';
        msgEl = document.createElement('p');
        msgEl.style.cssText = 'margin:0 0 14px;color:#555;font-size:14px;';
        videoWrap = document.createElement('div');
        videoWrap.style.cssText = 'margin:0 auto 14px;width:280px;height:210px;'
            + 'background:#000;border-radius:8px;overflow:hidden;';
        video.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        videoWrap.appendChild(video);
        statusEl = document.createElement('div');
        statusEl.style.cssText = 'min-height:20px;margin-bottom:14px;font-size:14px;font-weight:bold;';
        verifyBtn = document.createElement('button');
        verifyBtn.type = 'button';
        verifyBtn.textContent = STR.verifybtn;
        verifyBtn.style.cssText = 'background:#0f6cbf;color:#fff;border:0;border-radius:6px;'
            + 'padding:10px 22px;font-size:15px;cursor:pointer;';
        box.appendChild(headingEl);
        box.appendChild(msgEl);
        box.appendChild(videoWrap);
        box.appendChild(statusEl);
        box.appendChild(verifyBtn);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
    }

    function setStatus(text, color) {
        statusEl.textContent = text || '';
        statusEl.style.color = color || '#555';
    }

    function openModal(heading, message, onSuccess) {
        modalOpen = true;
        // Track when the student became blocked so we can give the time back
        // to the quiz timer (via extend_attempt_time) once verification passes.
        pauseStartedAt = Date.now();
        onSuccessCallback = onSuccess;
        headingEl.textContent = heading;
        msgEl.textContent = message;
        setStatus(STR.starting_camera, '#555');
        verifyBtn.disabled = false;
        overlay.style.display = 'flex';
        verifyBtn.focus();
        ensureCamera().then(function() {
            setStatus(STR.camera_ready, '#1a7f37');
        }).catch(function(err) {
            setStatus(err.message || STR.camera_failed, '#b3261e');
        });
    }

    function closeModal() {
        modalOpen = false;
        overlay.style.display = 'none';
    }

    function doVerify(Ajax) {
        verifyBtn.disabled = true;
        setStatus(STR.verifying, '#555');
        ensureCamera().then(function() {
            if (!video.videoWidth) {
                throw new Error(STR.camera_not_ready);
            }
            return Ajax.call([{
                methodname: 'quizaccess_proctoring_validate_face',
                args: {
                    courseid: COURSEID,
                    cmid: CMID,
                    profileimage: '',
                    webcampicture: captureFrame(),
                    parenttype: 'camshot_image',
                    faceimage: '',
                    facefound: 1,
                    source: currentSource
                }
            }])[0];
        }).then(function(res) {
            if (res && res.status === 'success') {
                markVerified();
                setStatus(STR.identity_verified, '#1a7f37');

                var finishSuccess = function() {
                    closeModal();
                    if (typeof onSuccessCallback === 'function') {
                        onSuccessCallback();
                    }
                };

                // If the teacher enabled "pause quiz timer", give the seconds
                // spent on the verification modal back to the attempt and reload
                // the page so the visible quiz timer reflects the new deadline.
                // We skip this on the summary page (the student is about to
                // submit -- no point extending the timer).
                var pauseSeconds = Math.round((Date.now() - pauseStartedAt) / 1000);
                if (PAUSE_QUIZ_TIME && !isSummary && ATTEMPTID > 0 && pauseSeconds >= 1) {
                    setStatus(STR.restoring_time.replace('{seconds}', pauseSeconds), '#1a7f37');
                    Ajax.call([{
                        methodname: 'quizaccess_proctoring_extend_attempt_time',
                        args: {
                            attemptid: ATTEMPTID,
                            cmid: CMID,
                            pauseseconds: pauseSeconds
                        }
                    }])[0].then(function() {
                        window.location.reload();
                        return null;
                    }).catch(function() {
                        // If the extension call failed, still let the student
                        // continue rather than blocking them on a server hiccup.
                        setTimeout(finishSuccess, 800);
                    });
                } else {
                    setTimeout(finishSuccess, 800);
                }
            } else if (res && res.status === 'photonotuploaded') {
                setStatus(STR.no_enrolled_photo, '#b3261e');
                verifyBtn.disabled = false;
            } else if (res && res.status === 'invalidApi') {
                setStatus(STR.invalid_api, '#b3261e');
                verifyBtn.disabled = false;
            } else if (res && res.status === 'serviceunavailable') {
                setStatus(STR.service_unavailable, '#b3261e');
                verifyBtn.disabled = false;
            } else {
                setStatus(STR.face_not_matched, '#b3261e');
                verifyBtn.disabled = false;
            }
            return null;
        }).catch(function(err) {
            setStatus((err && err.message) ? err.message : STR.verification_failed, '#b3261e');
            verifyBtn.disabled = false;
        });
    }

    require(['core/ajax'], function(Ajax) {
        buildModal();
        verifyBtn.addEventListener('click', function() {
            doVerify(Ajax);
        });

        if (isSummary) {
            // Pre-submission: block the "Submit all and finish" button until verified.
            var submitBtns = document.querySelectorAll('.mod_quiz-next-nav');
            for (var i = 0; i < submitBtns.length; i++) {
                submitBtns[i].disabled = true;
            }
            openModal(STR.presubmit_heading, STR.presubmit_message,
                function() {
                    for (var j = 0; j < submitBtns.length; j++) {
                        submitBtns[j].disabled = false;
                    }
                });
        } else {
            // Periodic re-verification while answering the quiz.
            var fire = function() {
                if (modalOpen) {
                    setTimeout(fire, 5000);
                    return;
                }
                openModal(STR.periodic_heading, STR.periodic_message,
                    function() {
                        setTimeout(fire, INTERVAL);
                    });
            };
            var anchor = lastVerified();
            if (!anchor) {
                markVerified();
                anchor = lastVerified() || Date.now();
            }
            setTimeout(fire, Math.max(0, INTERVAL - (Date.now() - anchor)));
        }
    });
})();
JS;
    }

    /**
     * Get a button to view the Proctoring report.
     *
     * @return string A link to view the report, or an empty string if the user lacks capability.
     *
     * @throws coding_exception
     */
    private function get_download_config_button(): string {
        global $OUTPUT, $USER;

        // Get the context for the module.
        $context = context_module::instance($this->quiz->cmid, MUST_EXIST);

        // Check if the user has the required capability to view the report.
        if (has_capability('quizaccess/proctoring:viewreport', $context, $USER->id)) {
            // Generate the link for the proctoring report.
            $httplink = \quizaccess_proctoring\link_generator::get_link(
                $this->quiz->course,
                $this->quiz->cmid,
                false,
                is_https()
            );

            // Return a single button linking to the report.
            return $OUTPUT->single_button($httplink, get_string('picturesreport', 'quizaccess_proctoring'), 'get');
        }

        // Return an empty string if the user lacks the required capability.
        return '';
    }

    public function verify_face($imagepath, $userid) {
    global $CFG;
    
    $ai_url = get_config('quizaccess_proctoring', 'ai_service_url');
    if (empty($ai_url)) {
        $ai_url = 'http://127.0.0.1:5000';
    }
    
    // Check if file exists
    if (!file_exists($imagepath)) {
        return array(
            'success' => false,
            'matched' => false,
            'error' => 'Image file not found'
        );
    }
    
    // Prepare curl request
    $ch = curl_init();
    $cfile = new CURLFile($imagepath, 'image/jpeg', basename($imagepath));
    $postdata = array(
        'user_id' => $userid,
        'image' => $cfile
    );
    
    curl_setopt($ch, CURLOPT_URL, $ai_url . '/verify');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the verification attempt
    error_log("Face verification for user $userid: HTTP $http_code, Response: $response");
    
    if ($http_code !== 200) {
        return array(
            'success' => false,
            'matched' => false,
            'error' => 'AI service returned error: ' . $curl_error
        );
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return array(
            'success' => false,
            'matched' => false,
            'error' => 'Invalid response from AI service'
        );
    }
    
    // Return verification result
    return array(
        'success' => true,
        'matched' => isset($result['matched']) ? $result['matched'] : false,
        'similarity' => isset($result['similarity']) ? $result['similarity'] : 0,
        'threshold' => isset($result['threshold']) ? $result['threshold'] : 0,
        'error' => isset($result['error']) ? $result['error'] : null
    );
}
}
