<?php
    require_once 'functions/auth.php';
    checkAdminAuth();
    require_once '../database.php';

    $pdo = getPDO();

    $table = $_GET['table'] ?? '';
    $id    = (int)($_GET['id'] ?? 0);

    // Allow only these tables
    $allowedTables = ['mt5_details', 'mt5_details_second'];
    if (!in_array($table, $allowedTables)) {
        die('Invalid table');
    }

    // Fetch attachment paths
    $stmt = $pdo->prepare("SELECT attachment_paths FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['attachment_paths'])) {
        die('No attachment found');
    }

    $paths = json_decode($row['attachment_paths'], true);
    if (!is_array($paths) || empty($paths)) {
        die('Invalid attachment data');
    }

    // If only one file → direct download
    if (count($paths) === 1) {
        $relativePath = $paths[0];
        $filePath = realpath(__DIR__ . '/../' . $relativePath);

        if (!$filePath || !file_exists($filePath)) {
            die('File not found');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    // If multiple files → ZIP
    $zipName = 'attachments_' . time() . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($paths as $relativePath) {
        $filePath = realpath(__DIR__ . '/../' . $relativePath);
        if ($filePath && file_exists($filePath)) {
            $zip->addFile($filePath, basename($filePath));
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);

    unlink($zipPath);
    exit;
