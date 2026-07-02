<?php
// includes/upload_helper.php — Shared file upload validation and storage.

if (!defined('UPLOAD_MAX_BYTES')) {
    define('UPLOAD_MAX_BYTES', 10 * 1024 * 1024); // 10 MB
}

const ALLOWED_EXTENSIONS = ['pdf','doc','docx','xls','xlsx','ppt','pptx','png','jpg','jpeg'];

const ALLOWED_MIMES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/png',
    'image/jpeg',
];

/**
 * Validate and store an uploaded file.
 *
 * @param string $inputName  The $_FILES key.
 * @param string $uploadDir  Absolute path to the destination directory.
 * @return array ['success'=>bool, 'stored_name'=>string, 'original_name'=>string,
 *               'file_size'=>int, 'mime_type'=>string, 'error'=>string]
 */
function validateAndStoreUpload(string $inputName, string $uploadDir): array
{
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'no_file'];
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporary directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        return ['success' => false, 'error' => $msgs[$file['error']] ?? 'Upload error.'];
    }

    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return ['success' => false, 'error' => 'File size exceeds 10 MB limit.'];
    }

    if ($file['size'] === 0) {
        return ['success' => false, 'error' => 'Uploaded file is empty.'];
    }

    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG.'];
    }

    // MIME validation via finfo
    if (!function_exists('finfo_open')) {
        return ['success' => false, 'error' => 'Server cannot validate file type. Contact administrator.'];
    }
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_MIMES, true)) {
        return ['success' => false, 'error' => 'File content does not match its extension. Upload rejected.'];
    }

    // Create directory if missing
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['success' => false, 'error' => 'Could not create upload directory.'];
    }

    // Unique stored filename — never use original name on disk
    $storedName  = uniqid('u_', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save file. Please try again.'];
    }

    return [
        'success'       => true,
        'stored_name'   => $storedName,
        'original_name' => $originalName,
        'file_size'     => (int)$file['size'],
        'mime_type'     => $mimeType,
        'error'         => '',
    ];
}

/**
 * Delete an upload file from disk safely.
 */
function deleteUploadFile(string $uploadDir, string $storedName): void
{
    // Guard: stored_name must not contain path separators
    if (empty($storedName) || strpbrk($storedName, '/\\') !== false) {
        return;
    }
    $path = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
    if (is_file($path)) {
        @unlink($path);
    }
}
