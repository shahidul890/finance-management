<?php
$zipFile = __DIR__ . '/../archive.zip'; // Ensure correct path to zip file
$extractTo = __DIR__ . '/../'; // Adjust extract path relative to unzipper.php location

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();
    // unlink($zipFile); // Delete archive.zip after extraction
    echo 'Unzipped and cleaned up successfully';
} else {
    echo 'Failed to unzip';
}
?>