<?php
class Settings {
    private $conn;
    private static $instance = null;
    private $settings = [];

    private function __construct($conn) {
        $this->conn = $conn;
        $this->loadSettings();
    }

    public static function getInstance($conn) {
        if (self::$instance === null) {
            self::$instance = new self($conn);
        }
        return self::$instance;
    }

    private function loadSettings() {
        $stmt = $this->conn->query("SELECT `key`, `value` FROM settings");
        while ($row = $stmt->fetch()) {
            $this->settings[$row['key']] = $row['value'];
        }
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value) {
        $stmt = $this->conn->prepare("
            INSERT INTO settings (`key`, `value`)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `value` = ?
        ");
        $stmt->execute([$key, $value, $value]);
        $this->settings[$key] = $value;
    }

    public function getAll() {
        return $this->settings;
    }

    // Các helper methods
    public function isRegistrationAllowed() {
        return $this->get('allow_registration') === '1';
    }

    public function isEmailVerificationRequired() {
        return $this->get('require_email_verification') === '1';
    }

    public function getDefaultUserRole() {
        return $this->get('default_user_role', 'user');
    }

    public function areCommentsAllowed() {
        return $this->get('allow_comments') === '1';
    }

    public function areCommentsModerated() {
        return $this->get('moderate_comments') === '1';
    }

    public function getSpamKeywords() {
        $keywords = $this->get('spam_keywords', '');
        return array_filter(array_map('trim', explode(',', $keywords)));
    }

    public function isCommentSpam($content) {
        $keywords = $this->getSpamKeywords();
        if (empty($keywords)) {
            return false;
        }
        $content = mb_strtolower($content);
        foreach ($keywords as $keyword) {
            if (!empty($keyword) && mb_strpos($content, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }
}