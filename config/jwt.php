<?php
class JWT {
    private static $secret_key = 'YourSuperSecretKey2026';
    private static $algorithm = 'HS256';

    public static function generateToken($user_id, $email, $role_id) {
        $payload = [
            'user_id' => $user_id,
            'email' => $email,
            'role_id' => $role_id,
            'exp' => time() + 3600 * 24 // 24 часа
        ];
        
        return base64_encode(json_encode($payload));
    }

    public static function verifyToken($token) {
        $payload = json_decode(base64_decode($token), true);
        
        if(!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
}
?>