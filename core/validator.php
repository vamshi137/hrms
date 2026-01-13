<?php
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validatePhone($phone) {
        return preg_match('/^[0-9]{10}$/', $phone);
    }
    
    public static function validatePAN($pan) {
        return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
    }
    
    public static function validateAadhaar($aadhaar) {
        return preg_match('/^[2-9]{1}[0-9]{11}$/', $aadhaar);
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function validateFile($file, $allowed_types = [], $max_size = 5242880) {
        $errors = [];
        
        if($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
        }
        
        if($file['size'] > $max_size) {
            $errors[] = 'File size exceeds limit';
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!empty($allowed_types) && !in_array($file_ext, $allowed_types)) {
            $errors[] = 'Invalid file type';
        }
        
        return $errors;
    }
    
    public static function sanitize($input) {
        if(is_array($input)) {
            foreach($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
        } else {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
}
?>