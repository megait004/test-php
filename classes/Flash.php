<?php
class Flash {
    public static function set($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function get() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function success($message) {
        self::set('success', $message);
    }

    public static function error($message) {
        self::set('danger', $message);
    }

    public static function warning($message) {
        self::set('warning', $message);
    }

    public static function info($message) {
        self::set('info', $message);
    }

    public static function display() {
        $flash = self::get();
        if ($flash) {
            echo sprintf(
                '<div class="alert alert-%s alert-dismissible fade show" role="alert">
                    %s
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>',
                $flash['type'],
                $flash['message']
            );
        }
    }
}