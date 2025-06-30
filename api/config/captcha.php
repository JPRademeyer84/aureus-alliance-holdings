<?php
/**
 * CAPTCHA SYSTEM
 * Simple mathematical CAPTCHA for additional security
 */

class SimpleCaptcha {
    
    /**
     * Generate a simple mathematical CAPTCHA
     */
    public static function generate() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operation = rand(0, 1) ? '+' : '-';
        
        if ($operation === '-' && $num1 < $num2) {
            // Ensure positive result
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        
        $question = "$num1 $operation $num2";
        $answer = $operation === '+' ? $num1 + $num2 : $num1 - $num2;
        
        // Store in session
        $_SESSION['captcha_answer'] = $answer;
        $_SESSION['captcha_generated'] = time();
        
        return [
            'question' => $question,
            'token' => hash('sha256', $answer . session_id())
        ];
    }
    
    /**
     * Validate CAPTCHA answer
     */
    public static function validate($userAnswer, $token = null) {
        if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_generated'])) {
            return false;
        }
        
        // Check if CAPTCHA is expired (5 minutes)
        if (time() - $_SESSION['captcha_generated'] > 300) {
            self::clear();
            return false;
        }
        
        $correctAnswer = $_SESSION['captcha_answer'];
        
        // Validate token if provided
        if ($token) {
            $expectedToken = hash('sha256', $correctAnswer . session_id());
            if (!hash_equals($expectedToken, $token)) {
                return false;
            }
        }
        
        $isValid = (int)$userAnswer === $correctAnswer;
        
        if ($isValid) {
            self::clear();
        }
        
        return $isValid;
    }
    
    /**
     * Clear CAPTCHA from session
     */
    public static function clear() {
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_generated']);
    }
    
    /**
     * Check if CAPTCHA is required based on failed attempts
     */
    public static function isRequired($identifier, $action) {
        require_once 'rate-limiter.php';
        $rateLimiter = RateLimiter::getInstance();
        $attempts = $rateLimiter->getAttemptCount($identifier, $action);
        
        // Require CAPTCHA after 2 failed attempts
        return $attempts >= 2;
    }
    
    /**
     * Generate image-based CAPTCHA (basic implementation)
     */
    public static function generateImage() {
        $width = 120;
        $height = 40;
        $image = imagecreate($width, $height);
        
        // Colors
        $bg_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $line_color = imagecolorallocate($image, 64, 64, 64);
        
        // Add noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, 0, rand(0, $height), $width, rand(0, $height), $line_color);
        }
        
        // Generate random string
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $captcha_string = '';
        for ($i = 0; $i < 5; $i++) {
            $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Store in session
        $_SESSION['captcha_answer'] = $captcha_string;
        $_SESSION['captcha_generated'] = time();
        
        // Add text to image
        imagestring($image, 5, 30, 10, $captcha_string, $text_color);
        
        // Output image
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    /**
     * Validate image-based CAPTCHA
     */
    public static function validateImage($userInput) {
        if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_generated'])) {
            return false;
        }
        
        // Check if CAPTCHA is expired (5 minutes)
        if (time() - $_SESSION['captcha_generated'] > 300) {
            self::clear();
            return false;
        }
        
        $isValid = strtoupper(trim($userInput)) === $_SESSION['captcha_answer'];
        
        if ($isValid) {
            self::clear();
        }
        
        return $isValid;
    }
}

/**
 * CAPTCHA API endpoint
 */
if (basename($_SERVER['PHP_SELF']) === 'captcha.php') {
    require_once 'secure-session.php';
    SecureSession::start();
    
    $action = $_GET['action'] ?? 'generate';
    
    switch ($action) {
        case 'generate':
            header('Content-Type: application/json');
            echo json_encode(SimpleCaptcha::generate());
            break;
            
        case 'image':
            SimpleCaptcha::generateImage();
            break;
            
        case 'validate':
            header('Content-Type: application/json');
            $input = json_decode(file_get_contents('php://input'), true);
            $answer = $input['answer'] ?? '';
            $token = $input['token'] ?? '';
            
            $isValid = SimpleCaptcha::validate($answer, $token);
            echo json_encode(['valid' => $isValid]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>
