<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * User self-enrollment page for proctoring face identification.
 * Allows users to upload their own reference photo for face verification during quizzes.
 *
 * @package    quizaccess_proctoring
 * @copyright  2024 Brain Station 23, 2026 Evanka Fauzya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/proctoring/classes/form/user_image_upload_form.php');

use quizaccess_proctoring\form\user_image_upload_form;

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/proctoring/user_enroll_photo.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('user_enroll_photo_title', 'quizaccess_proctoring'));
$PAGE->set_heading(get_string('user_enroll_photo_heading', 'quizaccess_proctoring'));
$PAGE->set_pagelayout('standard');

require_login();

global $USER, $DB, $CFG, $OUTPUT;

// Prevent non-logged-in users from accessing this page.
if (isguestuser()) {
    redirect($CFG->wwwroot, get_string('loggedinnot', 'quizaccess_proctoring'), null, \core\output\notification::NOTIFY_ERROR);
}

// Add navigation breadcrumb.
$PAGE->navbar->add(get_string('user_enroll_photo_title', 'quizaccess_proctoring'), $PAGE->url);

// Initialize the form for user photo upload.
$mform = new user_image_upload_form();

// Handle form cancellation.
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/my/', get_string('cancel_image_upload', 'quizaccess_proctoring'), null, \core\output\notification::NOTIFY_INFO);
} else if ($data = $mform->get_data()) {
    // Process the form submission.
    require_sesskey();

    // Validate that a face was detected in the uploaded image.
    if (empty($data->face_image) || $data->face_image === 'null') {
        \core\notification::error(get_string('no_face_detected', 'quizaccess_proctoring'));
    } else {
        try {
            $userid = $USER->id;
            $context = \context_system::instance();
            $fs = get_file_storage();

            // Decode the captured face image (a data URL: "data:image/...;base64,XXXX").
            $base64 = $data->face_image;
            if (strpos($base64, ',') !== false) {
                list(, $base64) = explode(',', $base64);
            }
            $binary = base64_decode($base64);

            if (empty($binary)) {
                \core\notification::error(get_string('face_image_invalid', 'quizaccess_proctoring'));
            } else {
                // 1. Move any file uploaded via the file picker into the 'user_photo' area.
                file_save_draft_area_files(
                    $data->user_photo,
                    $context->id,
                    'quizaccess_proctoring',
                    'user_photo',
                    $userid,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );

                // If no file was uploaded (webcam capture path), save the captured
                // image into the 'user_photo' area so quiz verification can find it.
                $existingphotos = $fs->get_area_files(
                    $context->id, 'quizaccess_proctoring', 'user_photo', $userid, 'id', false);
                if (empty($existingphotos)) {
                    $photofilename = 'userphoto-' . $userid . '-' . time() . '.png';
                    $fs->create_file_from_string([
                        'contextid' => $context->id,
                        'component' => 'quizaccess_proctoring',
                        'filearea'  => 'user_photo',
                        'itemid'    => $userid,
                        'filepath'  => '/',
                        'filename'  => $photofilename,
                    ], $binary);
                } else {
                    $photofilename = reset($existingphotos)->get_filename();
                }

                // 2. Save the cropped face image and obtain its public URL.
                $fs->delete_area_files($context->id, 'quizaccess_proctoring', 'face_image', $userid);
                $facerecord = new stdClass();
                $facerecord->filearea  = 'face_image';
                $facerecord->component = 'quizaccess_proctoring';
                $facerecord->filepath  = '/';
                $facerecord->itemid    = $userid;
                $facerecord->license   = '';
                $facerecord->author    = '';
                $faceurl = quizaccess_proctoring_geturl_of_faceimage(
                    $data->face_image, $userid, $facerecord, $context, $fs);

                // 3. Upsert the user image record that quiz verification looks up.
                if ($DB->record_exists('quizaccess_proctoring_user_images', ['user_id' => $userid])) {
                    $userimage = $DB->get_record('quizaccess_proctoring_user_images', ['user_id' => $userid]);
                } else {
                    $userimage = new stdClass();
                    $userimage->user_id = $userid;
                    $userimage->photo_draft_id = (int) $data->user_photo;
                    $userimage->id = $DB->insert_record('quizaccess_proctoring_user_images', $userimage);
                }

                // 4. Upsert the face image record. parent_type 'admin_image' is what
                //    quizaccess_proctoring_get_face_images() reads as the reference photo.
                $facetablerecord = new stdClass();
                $facetablerecord->parentid     = $userimage->id;
                $facetablerecord->parent_type  = 'admin_image';
                $facetablerecord->faceimage    = (string) $faceurl;
                $facetablerecord->facefound    = 1;
                $facetablerecord->timemodified = time();
                $existingface = $DB->get_record('quizaccess_proctoring_face_images',
                    ['parentid' => $userimage->id, 'parent_type' => 'admin_image']);
                if ($existingface) {
                    $facetablerecord->id = $existingface->id;
                    $DB->update_record('quizaccess_proctoring_face_images', $facetablerecord);
                } else {
                    $DB->insert_record('quizaccess_proctoring_face_images', $facetablerecord);
                }

                // Log the enrollment event.
                $event = \quizaccess_proctoring\event\user_photo_enrolled::create([
                    'context' => $context,
                    'userid' => $userid,
                    'other' => [
                        'filename' => $photofilename,
                    ],
                ]);
                $event->trigger();

                // Redirect with success message.
                redirect($CFG->wwwroot . '/my/',
                    get_string('photo_enrollment_success', 'quizaccess_proctoring'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }
        } catch (Exception $e) {
            \core\notification::error(get_string('photo_enrollment_failed', 'quizaccess_proctoring') . ': ' . $e->getMessage());
        }
    }
}

// Check if user already has an enrolled photo.
$context = context_system::instance();
$fs = get_file_storage();
$existingfaces = $fs->get_area_files($context->id, 'quizaccess_proctoring', 'face_image', $USER->id);
$hasenrolledphoto = count($existingfaces) > 1; // More than 1 because '.' is included.

// Output the page.
echo $OUTPUT->header();

// Display message about current enrollment status.
if ($hasenrolledphoto) {
    echo $OUTPUT->notification(get_string('photo_already_enrolled', 'quizaccess_proctoring'), 'info');
    echo '<p>' . get_string('photo_can_be_updated', 'quizaccess_proctoring') . '</p>';
}

echo $OUTPUT->heading(get_string('user_enroll_photo_instructions', 'quizaccess_proctoring'));
echo '<p>' . get_string('user_enroll_photo_description', 'quizaccess_proctoring') . '</p>';

// Display the form.
$mform->display();

echo $OUTPUT->footer();
