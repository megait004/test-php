<?php
class FileManager {
    private $uploadDir;

    public function __construct($uploadDir = UPLOAD_DIR) {
        $this->uploadDir = $uploadDir;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    }

    public function uploadFile($file, $allowedTypes = ALLOWED_FILE_TYPES) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Lỗi upload file");
        }

        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Loại file không được hỗ trợ");
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("File quá lớn");
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $this->uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Không thể lưu file");
        }

        return $filePath;
    }

    public function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}