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
 * Form class for user photo enrollment.
 *
 * @package    quizaccess_proctoring
 * @copyright  2024 Brain Station 23, 2026 Evanka Fauzya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_proctoring\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * User image upload form for self-enrollment in proctoring.
 *
 * This form allows users to upload a reference photo of themselves
 * which will be used for face verification during proctored quizzes.
 */
class user_image_upload_form extends \moodleform {

    /**
     * Define the form structure.
     */
    public function definition() {
        global $CFG, $USER;

        $mform = $this->_form;

        // Add webcam capture section.
        $mform->addElement('header', 'webcamheader', get_string('user_enroll_webcam_header', 'quizaccess_proctoring'));
        $mform->setExpanded('webcamheader', true);

        // Add video element for webcam streaming.
        $mform->addElement('html', '<div class="user-enroll-webcam-container" style="text-align: center; margin: 20px 0;">');
        $mform->addElement('html', '<video id="user-enroll-webcam" width="400" height="300" style="border: 2px solid #ccc; border-radius: 8px;"></video>');
        $mform->addElement('html', '<br><br>');

        // Add button to start the webcam manually (fallback if it does not auto-start).
        $mform->addElement('button', 'start_camera_button', get_string('user_enroll_start_camera', 'quizaccess_proctoring'), [
            'type' => 'button',
            'class' => 'btn btn-secondary',
        ]);

        $mform->addElement('html', '&nbsp;&nbsp;');

        // Add button to capture frame from webcam.
        $mform->addElement('button', 'capture_button', get_string('user_enroll_capture_photo', 'quizaccess_proctoring'), [
            'type' => 'button',
            'class' => 'btn btn-primary',
        ]);

        $mform->addElement('html', '<br><br>');
        $mform->addElement('html', '</div>');

        // Add canvas element for capturing frame (hidden).
        $mform->addElement('html', '<canvas id="user-enroll-canvas" style="display: none;"></canvas>');

        // Add hidden field to store captured face image in base64.
        $mform->addElement('hidden', 'face_image', '', ['id' => 'user-enroll-face-image']);
        $mform->setType('face_image', PARAM_RAW);

        // Add hidden field for user ID.
        $mform->addElement('hidden', 'id', $USER->id);
        $mform->setType('id', PARAM_INT);

        // Add hidden field for context ID.
        $mform->addElement('hidden', 'context_id', \context_system::instance()->id);
        $mform->setType('context_id', PARAM_INT);

        // Add photo preview section.
        $mform->addElement('header', 'previewheader', get_string('user_enroll_preview_header', 'quizaccess_proctoring'));

        $mform->addElement('html', '<div style="text-align: center; margin: 20px 0;">');
        $mform->addElement('html', '<img id="user-enroll-preview" src="" style="max-width: 300px; max-height: 300px; border: 1px solid #ddd; display: none; border-radius: 8px;"/>');
        $mform->addElement('html', '<p id="user-enroll-no-preview" style="display: none; color: #999;"><em>' . get_string('user_enroll_no_preview', 'quizaccess_proctoring') . '</em></p>');
        $mform->addElement('html', '</div>');

        // Add file upload as fallback.
        $mform->addElement('header', 'uploadheader', get_string('user_enroll_upload_header', 'quizaccess_proctoring'));
        $mform->addElement('html', '<p>' . get_string('user_enroll_upload_description', 'quizaccess_proctoring') . '</p>');

        $mform->addElement('filemanager', 'user_photo', get_string('user_enroll_upload_label', 'quizaccess_proctoring'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);

        // Add action buttons.
        $this->add_action_buttons(true, get_string('user_enroll_submit', 'quizaccess_proctoring'));

        // Add JavaScript for webcam functionality.
        $this->add_webcam_javascript();
    }

    /**
     * Add JavaScript for webcam capture functionality.
     */
    protected function add_webcam_javascript() {
        global $PAGE, $CFG;

        // The face-api library is served from the plugin's build directory.
        $faceapiurl = $CFG->wwwroot . '/mod/quiz/accessrule/proctoring/amd/build/face-api.min.js';

        // NOTE: this is injected via js_init_code(), so it must be raw JavaScript
        // with NO surrounding <script> tags (those would break the page output).
        $js = <<<JavaScript
(function() {
    'use strict';

    // Open the webcam and stream it into the video element.
    async function initializeWebcam() {
        var video = document.getElementById('user-enroll-webcam');
        var noPreview = document.getElementById('user-enroll-no-preview');
        var startBtn = document.getElementById('id_start_camera_button');
        if (!video) {
            return;
        }
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('This browser does not support camera access.');
            }
            var stream = await navigator.mediaDevices.getUserMedia({
                video: {width: 400, height: 300},
                audio: false
            });
            video.srcObject = stream;
            video.addEventListener('loadedmetadata', function() {
                video.play();
            });
            window.userEnrollWebcamStream = stream;
            if (startBtn) {
                startBtn.textContent = 'Camera Running';
                startBtn.disabled = true;
            }
            if (noPreview) {
                noPreview.style.display = 'none';
            }
        } catch (error) {
            console.error('Webcam access failed:', error);
            if (startBtn) {
                startBtn.textContent = 'Start Camera (retry)';
                startBtn.disabled = false;
            }
            if (noPreview) {
                noPreview.style.display = 'block';
                noPreview.innerText = 'Camera not started: ' + error.message
                    + ' - click "Start Camera" to allow access, or upload a photo manually below.';
            }
        }
    }

    // Capture a still frame from the running webcam.
    function capturePhotoFromWebcam() {
        var video = document.getElementById('user-enroll-webcam');
        var canvas = document.getElementById('user-enroll-canvas');
        var preview = document.getElementById('user-enroll-preview');
        var noPreview = document.getElementById('user-enroll-no-preview');

        if (!video || !video.srcObject) {
            alert('The camera is not running. Please click "Start Camera" first.');
            return;
        }

        var ctx = canvas.getContext('2d');
        canvas.width = 400;
        canvas.height = 300;
        ctx.drawImage(video, 0, 0, 400, 300);

        var imageData = canvas.toDataURL('image/jpeg', 0.9);

        preview.src = imageData;
        preview.style.display = 'block';
        if (noPreview) {
            noPreview.style.display = 'none';
        }

        validateAndStoreFace(imageData);
    }

    // Validate that the captured frame contains exactly one face, then store the
    // FULL frame. The AI backend runs its own face detection, so a tight crop
    // (which is too small for it to detect) must NOT be used here.
    async function validateAndStoreFace(imageData) {
        var faceInput = document.getElementById('user-enroll-face-image');
        try {
            var img = new Image();
            img.src = imageData;
            await new Promise(function(resolve) {
                img.onload = resolve;
            });

            if (typeof faceapi === 'undefined') {
                console.warn('Face API not loaded. Storing full image without validation.');
                faceInput.value = imageData;
                return;
            }

            var detections = await faceapi.detectAllFaces(img);
            if (detections.length === 0) {
                alert('No face detected in the image. Please try again with your face clearly visible.');
                faceInput.value = '';
                return;
            }
            if (detections.length > 1) {
                alert('Multiple faces detected. Please ensure only your face is in the frame.');
                faceInput.value = '';
                return;
            }

            // Exactly one face detected: store the full captured frame.
            faceInput.value = imageData;
        } catch (error) {
            console.warn('Face detection failed:', error);
            faceInput.value = imageData;
        }
    }

    // Wire up the buttons and attempt to auto-start the camera.
    function setup() {
        var startBtn = document.getElementById('id_start_camera_button');
        if (startBtn) {
            startBtn.addEventListener('click', initializeWebcam);
        }
        var captureBtn = document.getElementById('id_capture_button');
        if (captureBtn) {
            captureBtn.addEventListener('click', capturePhotoFromWebcam);
        }

        // Load face-api if it is not already present.
        if (typeof faceapi === 'undefined') {
            var script = document.createElement('script');
            script.src = '{$faceapiurl}';
            document.head.appendChild(script);
        }

        // Try to auto-start; if the browser blocks it, the button is the fallback.
        initializeWebcam();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }

    // Stop the camera when leaving the page.
    window.addEventListener('beforeunload', function() {
        if (window.userEnrollWebcamStream) {
            window.userEnrollWebcamStream.getTracks().forEach(function(track) {
                track.stop();
            });
        }
    });
})();
JavaScript;

        $PAGE->requires->js_init_code($js);
    }

    /**
     * Validation for the form.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Errors array.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check if either webcam capture or file upload was provided.
        $faceimage = $data['face_image'] ?? null;
        $userphoto = $files['user_photo'] ?? null;

        if (empty($faceimage) && empty($userphoto)) {
            $errors['user_photo'] = get_string('user_enroll_photo_required', 'quizaccess_proctoring');
        }

        return $errors;
    }
}
