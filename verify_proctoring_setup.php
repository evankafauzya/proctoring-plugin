#!/usr/bin/env php
<?php
/**
 * Proctoring Plugin - Verification Test Script
 * 
 * This script tests all critical components of the proctoring plugin
 * Run from command line: php verify_proctoring_setup.php
 */

// Define CLI_SCRIPT before including config (required for command-line scripts).
define('CLI_SCRIPT', true);
define('MOODLE_SKIP_SESSION', 1);

// Include Moodle config
$CFG_paths = [
    __DIR__ . '/../../../../config.php',
    dirname(__DIR__, 4) . '/config.php',
];

$config_found = false;
foreach ($CFG_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $config_found = true;
        break;
    }
}

if (!$config_found) {
    die("ERROR: Could not find Moodle config.php\n");
}

global $DB, $CFG;

class ProectoringVerifier {
    private $db;
    private $cfg;
    private $tests_passed = 0;
    private $tests_failed = 0;

    public function __construct($db, $cfg) {
        $this->db = $db;
        $this->cfg = $cfg;
    }

    public function run_all_tests() {
        echo "\n";
        echo "=================================================\n";
        echo "PROCTORING PLUGIN - VERIFICATION TEST SUITE\n";
        echo "=================================================\n\n";

        $this->test_config_settings();
        $this->test_database_tables();
        $this->test_api_connection();
        $this->test_user_enrollments();
        $this->test_verification_records();

        echo "\n=================================================\n";
        echo "TEST SUMMARY\n";
        echo "=================================================\n";
        echo "✓ Passed: {$this->tests_passed}\n";
        echo "✗ Failed: {$this->tests_failed}\n";
        echo "Total:   " . ($this->tests_passed + $this->tests_failed) . "\n";

        if ($this->tests_failed == 0) {
            echo "\n✓✓✓ ALL TESTS PASSED! ✓✓✓\n";
        } else {
            echo "\n✗✗✗ SOME TESTS FAILED ✗✗✗\n";
        }
        echo "=================================================\n\n";

        return $this->tests_failed == 0;
    }

    private function test_config_settings() {
        echo "\n1. CONFIGURATION SETTINGS\n";
        echo "------------------------\n";

        $fcmethod = get_config('quizaccess_proctoring', 'fcmethod');
        $bsapi = get_config('quizaccess_proctoring', 'bsapi');
        $threshold = get_config('quizaccess_proctoring', 'threshold');
        $delay = get_config('quizaccess_proctoring', 'autoreconfigurecamshotdelay');

        echo "Face match method: ";
        if ($fcmethod && ($fcmethod === 'BS' || $fcmethod === 'None')) {
            echo "✓ {$fcmethod}\n";
            $this->tests_passed++;
        } else {
            echo "✗ Not set or invalid: {$fcmethod}\n";
            $this->tests_failed++;
        }

        echo "Face match API endpoint: ";
        if ($bsapi && strpos($bsapi, 'localhost:5000') !== false) {
            echo "✓ {$bsapi}\n";
            $this->tests_passed++;
        } else {
            echo "✗ Not set or invalid: {$bsapi}\n";
            $this->tests_failed++;
        }

        echo "Face match threshold: ";
        if ($threshold) {
            echo "✓ {$threshold}%\n";
            $this->tests_passed++;
        } else {
            echo "⚠ Not configured (using default)\n";
        }

        echo "Webcam snapshot delay: ";
        if ($delay) {
            echo "✓ {$delay}s\n";
            $this->tests_passed++;
        } else {
            echo "⚠ Not configured (using default 30s)\n";
        }
    }

    private function test_database_tables() {
        echo "\n2. DATABASE TABLES\n";
        echo "------------------\n";

        $tables = [
            'quizaccess_proctoring_verifications' => 'Face verifications',
            'quizaccess_proctoring_frame_analysis' => 'Frame analysis',
            'quizaccess_proctoring_sessions' => 'Session tracking',
            'quizaccess_proctoring_warnings' => 'Warning logs',
        ];

        foreach ($tables as $table => $description) {
            echo "{$description}: ";
            try {
                $count = $this->db->count_records($table);
                echo "✓ {$count} records\n";
                $this->tests_passed++;
            } catch (Exception $e) {
                echo "✗ Table not found or error\n";
                $this->tests_failed++;
            }
        }
    }

    private function test_api_connection() {
        echo "\n3. API CONNECTION\n";
        echo "-----------------\n";

        $bsapi = get_config('quizaccess_proctoring', 'bsapi');
        if (!$bsapi) {
            echo "✗ Face match API endpoint not configured\n";
            $this->tests_failed++;
            return;
        }

        $url = rtrim($bsapi, '/') . '/health';
        echo "Testing: {$url}\n";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($http_code === 200) {
                echo "✓ API is responding (HTTP 200)\n";
                echo "  Response: " . substr($response, 0, 100) . "...\n";
                $this->tests_passed++;
            } else {
                echo "✗ API responded with HTTP {$http_code}\n";
                $this->tests_failed++;
            }
        } catch (Exception $e) {
            echo "✗ Cannot connect to API: " . $e->getMessage() . "\n";
            $this->tests_failed++;
        }
    }

    private function test_user_enrollments() {
        echo "\n4. USER ENROLLMENTS\n";
        echo "------------------\n";

        try {
            $sql = "SELECT COUNT(DISTINCT userid) as user_count
                    FROM {files}
                    WHERE component = 'quizaccess_proctoring'
                    AND filearea = 'face_image'";
            $result = $this->db->get_record_sql($sql);
            $user_count = $result->user_count;

            echo "Users with enrolled faces: ";
            if ($user_count > 0) {
                echo "✓ {$user_count} users\n";
                $this->tests_passed++;
            } else {
                echo "⚠ 0 users (no faces enrolled yet)\n";
            }
        } catch (Exception $e) {
            echo "✗ Error checking enrollments: " . $e->getMessage() . "\n";
            $this->tests_failed++;
        }
    }

    private function test_verification_records() {
        echo "\n5. VERIFICATION RECORDS\n";
        echo "----------------------\n";

        try {
            $total = $this->db->count_records('quizaccess_proctoring_verifications');
            $successful = $this->db->count_records('quizaccess_proctoring_verifications', ['is_verified' => 1]);
            $failed = $total - $successful;

            echo "Total verifications: ";
            if ($total > 0) {
                echo "✓ {$total}\n";
                echo "  Successful: {$successful}\n";
                echo "  Failed: {$failed}\n";
                $this->tests_passed++;
            } else {
                echo "⚠ 0 (no quiz attempts with proctoring yet)\n";
            }

            // Check latest verification
            $latest = $this->db->get_record_sql(
                "SELECT * FROM {quizaccess_proctoring_verifications} ORDER BY timecreated DESC LIMIT 1"
            );
            if ($latest) {
                $ago = time() - $latest->timecreated;
                echo "Latest verification: ";
                echo gmdate("H:i:s", $ago) . " ago\n";
            }
        } catch (Exception $e) {
            echo "⚠ Could not read verification records\n";
        }
    }
}

// Run verifier
$verifier = new ProectoringVerifier($DB, $CFG);
$success = $verifier->run_all_tests();

exit($success ? 0 : 1);
?>
