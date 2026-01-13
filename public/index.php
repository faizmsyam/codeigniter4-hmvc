<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.1'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

$licenseFile = FCPATH . '../app/Config/.license.key';

$pageLocked = <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Locked</title>
        <style>
            :root {
                --bg: #020617;
                --card: #020617;
                --border: #1e293b;
                --text: #e5e7eb;
                --muted: #94a3b8;
                --danger: #f87171;
                --accent: #38bdf8;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at top, #020617, #020617);
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                color: var(--text);
            }

            .card {
                background: rgba(2, 6, 23, 0.95);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 40px 36px;
                max-width: 420px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0,0,0,.6);
            }

            .icon {
                font-size: 48px;
                margin-bottom: 12px;
            }

            h1 {
                margin: 0 0 12px;
                font-size: 24px;
                color: var(--danger);
            }

            p {
                margin: 0 0 24px;
                font-size: 15px;
                line-height: 1.6;
                color: var(--muted);
            }

            .hint {
                font-size: 13px;
                color: #64748b;
            }

            .footer {
                margin-top: 28px;
                font-size: 12px;
                color: #475569;
            }

            @media (max-width: 480px) {
                .card {
                    margin: 20px;
                    padding: 32px 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">🔒</div>
            <h1>Application Locked</h1>
            <p>
                Lisensi aplikasi tidak ditemukan atau tidak valid untuk server ini.
                Silakan hubungi administrator atau penyedia aplikasi untuk mengaktifkan lisensi.
            </p>

            <div class="hint">
                Error Code: LICENSE_INVALID
            </div>

            <div class="footer">
                &copy; 2026 Cafeweb Indonesia
            </div>
        </div>
    </body>
    </html>
    HTML;

if (! is_file($licenseFile)) {
    http_response_code(403);
    echo $pageLocked;
    exit;
}

$serverKey = '9fb2d3aa180bf5fa59df7ba501b8a00f87eb6d6e';

$license = trim(file_get_contents($licenseFile));

if (!hash_equals($serverKey, sha1($license))) {
    http_response_code(403);
    echo $pageLocked;
    exit;
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
