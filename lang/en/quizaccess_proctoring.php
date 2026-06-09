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
 * Strings for the quizaccess_proctoring plugin.
 *
 * @package    quizaccess_proctoring
 * @copyright  2020 Brain Station 23, 2026 Evanka Fauzya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['accessdenied'] = 'Access Denied';
$string['action_upload_image'] = 'Action';
$string['actions'] = 'Actions';
$string['additional_settings'] = 'General settings';
$string['analyzbtn'] = 'Analyze';
$string['analyzbtnconfirm'] = 'Click the Analyze button for face match of the user.';
$string['analyzimage'] = 'Analyze images';
$string['areyousure_delete_all_course_record'] = 'Are you sure you want to delete all images and records of students that were captured during the exams for <b>this course?</b>';
$string['areyousure_delete_all_record'] = 'Are you sure you want to delete all images of students that were captured during the exams?';
$string['areyousure_delete_image'] = 'Do you want to delete this image?';
$string['areyousure_delete_record'] = 'Are you sure you want to delete this record?';
$string['back'] = 'Back';
$string['cancel_image_upload'] = 'Cancelled image upload';
$string['confirmdeletioncourse'] = 'Are you sure you want to delete this course pictures?';
$string['confirmdeletionquiz'] = 'Are you sure you want to delete the pictures of this quiz?';
$string['course_proctoring_summary'] = 'Course Report';
$string['dateverified'] = 'Date and time';
$string['delete'] = 'Delete';
$string['delete_images_task'] = 'Delete images task';
$string['delete_images_task_desc'] = 'Delete all proctoring images';
$string['deleteallcourse'] = 'Delete course images';
$string['deletequizdata'] = 'Delete quiz images';
$string['email']  = 'Email address';
$string['enable_web_camera_before_submitting'] = 'You need to enable web camera before submitting this quiz!';
$string['eprotroringreports'] = 'Proctoring report for: ';
$string['eprotroringreportsdesc'] = 'In this report you will find all the images of the students which are taken during the exam. Now you can validate their identity, like their profile picture and webcam images.';
$string['error_face_not_found'] = 'Face not found in the image. Please contact the administrator.';
$string['error_invalid_report'] = 'Invalid report data. Please try again.';
$string['examdata'] = 'No data is available for this exam session. Please check the exam setup or monitoring configurations.';
$string['execute_facematch_task'] = 'Execute face match task';
$string['facefound'] = 'Face found in the uploaded image.';
$string['facematch'] = 'Face match successful. The student identity is verified.';
$string['facematched'] = 'Face matched.';
$string['facematchs'] = 'All images have been successfully analyzed. Please review them to verify the face match.';
$string['facenotfound'] = 'Face not found in the uploaded image.';
$string['facenotfoundoncam'] = 'Face not found. Try changing your camera to a better lighting. Thanks.';
$string['facenotmatched'] = 'Face not matched.';
$string['foundtext'] = 'Found';
$string['identity_mismatch_label'] = 'Identity Mismatch';
$string['image'] = 'Upload Image';
$string['image_not_uploaded'] = 'The uploaded image does not contain any faces.';
$string['image_updated'] = 'Image updated';
$string['image_upload'] = 'Upload image';
$string['info:cameraallow'] = 'Your camera is now in use.';
$string['initiate_facematch_task'] = 'Initiate face match task';
$string['initiate_facematch_task_desc'] = 'Initiates a face match task to compare images for proctoring verification.';
$string['invalid_api'] = 'The provided API key is invalid.';
$string['invalid_facematch_method'] = 'Invalid face match method in settings. Please configure a valid face match service.';
$string['invalid_service_api'] = 'The provided face match service API is invalid.';
$string['invalidapi'] = 'The API key is invalid. Please contact to the admin.';
$string['serviceunavailable'] = 'The face verification service is currently unavailable. Please try again in a moment or contact the admin.';

// Student-report summary card.
$string['summary:title'] = 'Session summary';
$string['summary:framescaptured'] = 'Frames captured';
$string['summary:framesanalyzed'] = 'Frames analyzed';
$string['summary:facesuccessrate'] = 'Face match success rate';
$string['summary:avgscore'] = 'Average match score';
$string['summary:noface'] = 'Frames with no face detected';
$string['summary:multifaces'] = 'Frames with multiple faces';
$string['summary:unusualhead'] = 'Frames with unusual head pose';
$string['summary:unusualgaze'] = 'Frames with unusual eye gaze';
$string['summary:avggazeoffset'] = 'Average eye-gaze offset';
$string['summary:riskdistribution'] = 'Risk distribution';
$string['summary:duration'] = 'Session duration';
$string['summary:overallrisk'] = 'Overall risk';
$string['summary:threshold'] = 'Face match threshold';
$string['summary:preflight'] = 'Preflight check (quiz start)';
$string['summary:periodic'] = 'Mid-quiz re-verifications';
$string['summary:presubmit'] = 'Pre-submit verification';

// In-quiz re-verification modal text (rule.php::get_reverification_js).
$string['modal:verifybtn'] = 'Verify my face';
$string['modal:starting_camera'] = 'Starting camera...';
$string['modal:camera_ready'] = 'Camera ready. Click "Verify my face".';
$string['modal:camera_failed'] = 'Camera could not be started.';
$string['modal:camera_not_ready'] = 'Camera is not ready yet. Please wait and try again.';
$string['modal:nosupport'] = 'This browser does not support camera access.';
$string['modal:verifying'] = 'Verifying, please wait...';
$string['modal:identity_verified'] = 'Identity verified.';
$string['modal:restoring_time'] = 'Restoring your quiz time ({seconds}s)...';
$string['modal:no_enrolled_photo'] = 'No enrolled photo found. Please contact your administrator.';
$string['modal:invalid_api'] = 'Face verification service rejected the request (authentication failed). Contact your administrator.';
$string['modal:service_unavailable'] = 'Face verification service is temporarily unavailable. Wait a moment and try again.';
$string['modal:face_not_matched'] = 'Face not matched. Re-position your face in good light and try again.';
$string['modal:verification_failed'] = 'Verification failed. Please try again.';
$string['modal:presubmit_heading'] = 'Verify your identity to submit';
$string['modal:presubmit_message'] = 'Before submitting your quiz you must verify your identity. The Submit button stays disabled until you do.';
$string['modal:periodic_heading'] = 'Identity re-verification required';
$string['modal:periodic_message'] = 'Please verify your identity to continue your quiz.';

// Per-frame columns in the student report.
$string['frame:index'] = '#';
$string['frame:time'] = 'Time';
$string['frame:webcam'] = 'Webcam';
$string['frame:score'] = 'Face score';
$string['frame:risk'] = 'Risk';
$string['frame:indicators'] = 'Suspicious indicators';
$string['frame:none'] = '—';
$string['invalidsesskey'] = 'Invalid session key. Please try again.';
$string['invalidtype'] = 'The provided type is invalid.';
$string['mainsettingspagebtn'] = 'Proctoring settings';
$string['modal:facevalidation'] = 'Face validated:';
$string['modal:pending'] = 'Pending';
$string['modal:validateface'] = 'Validate face recognition';
$string['name'] = 'Student name';
$string['no_permission'] = 'You do not have proper permission to view this page';
$string['nodata'] = 'No data found for the given criteria.';
$string['none'] = 'None';
$string['nopermission'] = 'You do not have permission to perform this action.';
$string['notenrolled'] = 'You are not enrolled in this course or do not have the required permissions.';
$string['notfoundtext'] = 'Not Found';
$string['notpermissionreport'] = 'Proctoring reports are disabled for you.';
$string['notrequired'] = 'Not required';
$string['nousersfound'] = 'No users found';
$string['numberofimages'] = 'Number of images';
$string['openwebcam'] = 'Allow your webcam to continue';
$string['photoalttext'] = 'The screen capture will appear in this box.';
$string['photonotuploaded'] = 'Photo not uploaded. Please contact to the admin.';
$string['picturesreport'] = 'View proctoring report';
$string['picturesusedreport'] = 'These are the pictures captured during the quiz.';
$string['plugin_description'] = 'The Moodle Proctoring plugin enhances the security of online quizzes by capturing and verifying user identities through webcam images. It is designed to ensure that only authorized users can attempt the quiz, providing a secure and reliable proctoring solution.';
$string['pluginname'] = 'Proctoring for Moodle';
$string['privacy:core_files'] = 'QuizAccess Proctoring webcam pictures';
$string['privacy:metadata'] = 'We do not share any personal data with third parties.';
$string['privacy:metadata:core_files'] = 'The Quiz Access stores users picture which has been shot by the webcam during quiz attempt.';
$string['privacy:metadata:courseid'] = 'The ID of the course that uses proctoring.';
$string['privacy:metadata:quizaccess_proctoring_logs'] = 'Moodle Quiz access Proctoring logs table that stores user\'s picture.';
$string['privacy:metadata:quizid'] = 'The ID of the quiz that uses proctoring.';
$string['privacy:metadata:status'] = 'The status of the proctoring.';
$string['privacy:metadata:userid'] = 'The ID of the user who took the quiz.';
$string['privacy:metadata:webcampicture'] = 'The name of the picture that has been taken by the proctoring.';
$string['pro_version_description'] = 'Enhance your online exams with Moodle Proctoring Pro! Catch tab-switching, monitor clipboard activity, use face recognition for real-time monitoring, and access detailed proctoring reports to ensure fair and secure assessments.';
$string['pro_version_text'] = 'Learn more about the Pro version of this plugin here.';
$string['pro_version_title_text'] = 'Get Proctoring Pro.';
$string['proctoring:analyzeimages'] = 'Proctoring analyze images';
$string['proctoring:deletecamshots'] = 'Delete images from proctoring logs.';
$string['proctoring:getcamshots'] = 'Proctoring get webcam images';
$string['proctoring:sendcamshot'] = 'Proctoring send webcam photo';
$string['proctoring:viewreport'] = 'Proctoring view report';
$string['proctoring_pro_promo'] = 'Proctoring Pro promo';
$string['proctoring_pro_promo:admin'] = 'Detailed admin reports';
$string['proctoring_pro_promo:adminlist1'] = 'Provides a detailed view of all participants\' proctored logs.';
$string['proctoring_pro_promo:adminlist2'] = 'Allows downloading a comprehensive PDF report.';
$string['proctoring_pro_promo:detectcopypaste'] = 'Copy-paste forgery detection';
$string['proctoring_pro_promo:detectcopypastelist1'] = 'Detects any copy and paste actions during the quiz attempt.';
$string['proctoring_pro_promo:detectcopypastelist2'] = 'Logs each attempt to copy or paste text.';
$string['proctoring_pro_promo:email'] = 'Email support';
$string['proctoring_pro_promo:emailsupport'] = 'Receive direct email support from our team.';
$string['proctoring_pro_promo:emailsupportlist1'] = 'Get 24/7 email support for any queries or issues.';
$string['proctoring_pro_promo:feature'] = 'Features of Proctoring Pro';
$string['proctoring_pro_promo:featurelist1'] = 'Compatible with face recognition service (AWS).';
$string['proctoring_pro_promo:featurelist2'] = 'Detect if webcam was enabled for entire time of attempt.';
$string['proctoring_pro_promo:featurelist3'] = 'Detect if user has moved to any other application/tab.';
$string['proctoring_pro_promo:featurelist4'] = 'Detect if user has resized the browser window.';
$string['proctoring_pro_promo:featurelist5'] = 'Detect if copy and paste occurred during the attempt.';
$string['proctoring_pro_promo:featurelist6'] = 'Detect if user has pressed F12 key.';
$string['proctoring_pro_promo:featurelist7'] = 'Detailed admin report of every event log and webcam images.';
$string['proctoring_pro_promo:featurelist8'] = 'Admin summary report of all users.';
$string['proctoring_pro_promo:featurelist9'] = 'Email support/bug fixes';
$string['proctoring_pro_promo:header'] = 'Secure your online exams with Proctoring Pro cutting-edge technology for unbeatable monitoring';
$string['proctoring_pro_promo:learnmore'] = 'Learn more';
$string['proctoring_pro_promo:mail'] = 'Contact us at';
$string['proctoring_pro_promo:namefree'] = 'Proctoring (Free)';
$string['proctoring_pro_promo:namepro'] = 'Proctoring Pro';
$string['proctoring_pro_promo:pdfgenerator'] = 'PDF report generation';
$string['proctoring_pro_promo:pdfgeneratordesc'] = 'Generates a detailed PDF report for each user, containing all logged events.';
$string['proctoring_pro_promo:profeature'] = 'What\'s new in Proctoring Pro 2.0';
$string['proctoring_pro_promo:profeaturebulkphotoupload'] = 'Bulk photo upload';
$string['proctoring_pro_promo:profeaturebulkphotouploaddesc'] = 'Allows admins to upload images for multiple users at once via a zip file or upload individual images.';
$string['proctoring_pro_promo:profeaturehphotofillter'] = 'Photo filtering';
$string['proctoring_pro_promo:profeaturehphotofillterdesc'] = 'Admins can filter users based on whether their photo is uploaded or if the user\'s face is missing from the captured images.';
$string['proctoring_pro_promo:screenmonitoring'] = 'Screen size monitoring';
$string['proctoring_pro_promo:screenmonitoringlist1'] = 'Detects any changes in screen size during the quiz attempt.';
$string['proctoring_pro_promo:screenmonitoringlist2'] = 'Logs each instance when the user resizes the quiz window.';
$string['proctoring_pro_promo:subheader'] = 'Get the Proctoring Pro plugin now.';
$string['proctoring_pro_promo:suscipiousevent'] = 'Other suspicious events';
$string['proctoring_pro_promo:suscipiouseventlist1'] = 'Detects if the F12 key is pressed during the exam.';
$string['proctoring_pro_promo:suscipiouseventlist2'] = 'Logs each instance when the user presses F12 while attempting the quiz.';
$string['proctoring_pro_promo:tabmonitoring'] = 'Focus tab monitoring';
$string['proctoring_pro_promo:tabmonitoringlist1'] = 'Detects if the user switches to another window or tab.';
$string['proctoring_pro_promo:tabmonitoringlist2'] = 'Logs every instance when the user moves away from the exam tab or window.';
$string['proctoring_pro_promo:webcam'] = 'Webcam detection';
$string['proctoring_pro_promo:webcamlist1'] = 'Detects whether the webcam remained enabled throughout the entire exam attempt.';
$string['proctoring_pro_promo:webcamlist2'] = 'Logs any instances when the webcam is disabled.';
$string['proctoring_pro_promo_heading'] = 'Proctoring Pro promo';
$string['proctoring_report'] = 'Proctoring report';
$string['proctoringheader'] = '<strong>To continue with this quiz attempt you must open your webcam, and it will take some of your pictures randomly during the quiz.</strong>';
$string['proctoringlabel'] = 'I agree with the validation process.';
$string['proctoringrequired'] = 'Webcam identity validation';
$string['proctoringrequired_help'] = 'Enabling proctoring requires students to be monitored using webcam and screen recording during the quiz attempt.';
$string['proctoringrequiredoption'] = 'Enable webcam capture by Proctoring';
$string['proctoringstatement'] = 'This exam requires webcam access.<br />(Please allow webcam access).';
$string['provide_image'] = 'Please provide an image to upload.';
$string['quizaccess_proctoring'] = 'Quizaccess Proctoring';
$string['quiztitle'] = 'Quiz Title';
$string['report_search_clear'] = 'Clear';
$string['report_search_placeholder'] = 'Search by email or name';
$string['report_search_submit'] = 'Search';
$string['reportpage'] = 'Course Proctoring Summary';
$string['setting:adminimagedescription'] = 'These images will be used as base images for face verification. Please ensure each image contains a clearly visible face.';
$string['setting:adminimagepage'] = 'Proctoring User List';

$string['setting:bs_api'] = 'Face match service API';
$string['setting:bs_api_key'] = 'Face match API key';
$string['setting:bs_api_keydesc'] = 'Enter the API key for the face match service. Leave blank if the service does not require one.';
$string['setting:bs_apidesc'] = 'Face match service API endpoint URL.';
$string['setting:debuglog'] = 'Face match diagnostic logging';
$string['setting:debuglogdesc'] = 'When enabled, each face match attempt is written to the PHP error log (tagged [quizaccess_proctoring] FaceMatch). Use only for troubleshooting; leave disabled in production.';
$string['setting:bs_apifacematchthreshold'] = 'Face match threshold';
$string['setting:bs_bs_apifacematchthresholddesc'] = 'The minimum similarity percentage required for a face to match. Higher = stricter. (Default: 68%)';
$string['setting:camshotdelay'] = 'The delay between webcam images (seconds)';
$string['setting:camshotdelay_desc'] = 'The given value will be the delay in seconds between each webcam image.';
$string['setting:camshotwidth'] = 'The width of the webcam image (pixels)';
$string['setting:camshotwidth_desc'] = 'The given value will be the width of the webcam image. The image height will be scaled to match this.';
$string['setting:facematch'] = 'Number of face matches per quiz';
$string['setting:facematchdesc'] = 'Number of face match checks. Use 0 or less to check all snapshots.';
$string['setting:fc_method'] = 'Face match method';
$string['setting:fc_methoddesc'] = 'Service used to match faces. Options: AI face match, None.';

// Per-quiz re-verification settings (set on the quiz settings form).
$string['setting:reverifyinterval'] = 'Re-verification interval';
$string['setting:reverifyinterval_help'] = 'How often the student must re-verify their face mid-quiz. Set to "Disabled" to skip in-quiz re-verification entirely; the pre-submission check on the summary page still runs in that case.';
$string['setting:reverifyinterval_disabled'] = 'Disabled (no mid-quiz re-verification)';
$string['setting:reverifyinterval_1min'] = 'Every 1 minute';
$string['setting:reverifyinterval_2min'] = 'Every 2 minutes';
$string['setting:reverifyinterval_3min'] = 'Every 3 minutes';
$string['setting:reverifyinterval_5min'] = 'Every 5 minutes';
$string['setting:reverifyinterval_10min'] = 'Every 10 minutes';
$string['setting:pausequiztime'] = 'Pause quiz timer during verification';
$string['setting:pausequiztime_label'] = 'Give the student back the seconds spent verifying';
$string['setting:pausequiztime_help'] = 'When enabled, the quiz timer is extended by the number of seconds the verification modal was open, so the student is not penalised for the interruption. The page reloads once the timer has been extended.';
$string['setting:fcthreshold'] = 'Face match threshold percentage';
$string['setting:fcthresholddesc'] = 'Face match threshold percentage';
$string['setting:uploaduserimages'] = 'Upload base image for users';
$string['setting:userslist'] = 'Upload user images';
$string['settings:deleteallsuccess'] = 'Successfully deleted all records.';
$string['settings:deleteuserimagesuccess'] = 'Successfully deleted user image.';
$string['settings:fcheckquizstart'] = 'Face validation on quiz start';
$string['settings:fcheckquizstart_desc'] = 'If enabled, users must validate their face before they can start the quiz.';

$string['settingscontroll:deleteall'] = 'Delete all record that captured during the exams';
$string['settingscontroll:deleteallcourseimage'] = 'Delete all images and records of students that were captured during the exams for <b>this course</b>.';
$string['settingscontroll:deletealldescription'] = 'This will permanently delete all captured images and proctoring related data. This action cannot be undone.';

$string['settingscontroll:deletealllinktext'] = 'Delete all records';
$string['status'] = 'Validation status';
$string['studentreport'] = 'Student report';
$string['submit'] = 'Submit';
$string['summarypagedesc'] = 'In this report you will find the summary of proctoring report for this course and its quizzes. You can delete all the data related to quiz and course. It will delete image file as well as logs.';
$string['task:delete_images'] = 'Delete images task';
$string['timemodified'] = 'Last modified';
$string['upload_first_image'] = 'Please upload user image.';
$string['upload_image'] = 'Upload image';
$string['upload_image_heading'] = 'Upload user image';
$string['upload_image_info'] = 'Upload images to the system for user verification. This helps ensure the integrity of your online quizzes.';
$string['upload_image_link_text'] = 'Click here to Upload user images.';
$string['upload_image_message'] = 'Proctoring needs user images to authenticate their identity.';
$string['upload_image_title'] = 'Upload image for face detection';
$string['uploadimagehere'] = 'Click here to upload the image.';
$string['user'] = 'Users';
$string['user_image_not_uploaded'] = 'User image is not uploaded. Please upload the image.';
$string['user_image_not_uploaded_teacher'] = 'User image is not uploaded. Please contact with administrator to upload the image.';
$string['userimagenotuploaded'] = 'User image is not uploaded.';
$string['userlist'] = 'User list';
$string['username'] = 'User Name';
$string['users_list'] = 'Proctoring for Moodle Users list';
$string['users_list_info_description'] = 'This page lists all users who require a base image for proctoring.
                                        These images will be used for face-matching during quizzes to ensure authentication and prevent impersonation.
                                        If an image is not uploaded, the user may not be properly verified during proctored exams. To get more features like customized filtering, searching, and uploading many images at once, ';
$string['videonotavailable'] = 'Video stream not available.';
$string['viewimages'] = 'View images';
$string['warning:cameraallowwarning'] = 'Please allow camera access.';
$string['warninglabel'] = 'Warnings';
$string['webcam'] = 'Webcam';
$string['webcampicture'] = 'Captured pictures';
$string['wrong_during_taking_image'] = 'Something went wrong during taking the image.';
$string['wrong_during_taking_screenshot'] = 'Something went wrong during taking screenshot.';
$string['youmustagree'] = 'You must agree to validate your identity before continuing.';

// User self-enrollment strings
$string['user_enroll_photo_title'] = 'Proctoring - Enroll Your Face';
$string['user_enroll_photo_heading'] = 'Face Enrollment for Proctored Quizzes';
$string['user_enroll_photo_instructions'] = 'Enroll Your Face for Proctoring';
$string['user_enroll_photo_description'] = 'To take proctored quizzes, you need to enroll a reference photo of your face. This photo will be used to verify your identity during the exam. You can either capture a photo using your webcam or upload an existing photo.';
$string['user_enroll_webcam_header'] = 'Capture Photo with Webcam';
$string['user_enroll_start_camera'] = 'Start Camera';
$string['user_enroll_capture_photo'] = 'Capture Photo';
$string['user_enroll_preview_header'] = 'Photo Preview';
$string['user_enroll_no_preview'] = 'No photo captured yet';
$string['user_enroll_upload_header'] = 'Or Upload a Photo';
$string['user_enroll_upload_description'] = 'If you prefer, you can upload an existing photo instead of using the webcam.';
$string['user_enroll_upload_label'] = 'Upload Photo';
$string['user_enroll_submit'] = 'Enroll Face';
$string['user_enroll_photo_required'] = 'Please either capture a photo with your webcam or upload a photo.';
$string['photo_enrollment_success'] = 'Your face has been successfully enrolled! You can now take proctored quizzes.';
$string['photo_enrollment_failed'] = 'Failed to enroll your face. Please try again.';
$string['photo_already_enrolled'] = 'You already have an enrolled photo.';
$string['photo_can_be_updated'] = 'You can update it by submitting a new photo below.';
$string['no_face_detected'] = 'No face was detected in your photo. Please ensure your face is clearly visible and try again.';
$string['face_image_invalid'] = 'The face image is invalid. Please try again.';
$string['loggedinnot'] = 'You must be logged in to access this page.';
$string['proctoring_enroll_menu'] = 'Enroll for Proctoring';

// Event strings
$string['event_user_photo_enrolled'] = 'User photo enrolled for proctoring';

// Multi-face detection (live in-quiz alert).
$string['messageprovider:multiface_alert'] = 'Multiple persons detected in a proctored quiz';
$string['multiface_alert_subject'] = 'Proctoring alert: {$a->count} persons detected for {$a->student}';
$string['multiface_alert_body'] = '{$a->count} persons were detected in the webcam of {$a->student} while taking "{$a->quiz}" at {$a->time}. Open the proctoring report for details.';
$string['multiface_banner'] = 'Multiple persons detected in your webcam. Please make sure only you are visible.';
