<?php
class Uploader {
    private $upload_dir;
    private $allowed_types;
    private $max_size;
    
    public function __construct($upload_dir, $allowed_types = [], $max_size = 5242880) {
        $this->upload_dir = $upload_dir;
        $this->allowed_types = $allowed_types;
        $this->max_size = $max_size;
        
        // Create directory if not exists
        if(!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function upload($file, $prefix = '') {
        $errors = Validator::validateFile($file, $this->allowed_types, $this->max_size);
        
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = $prefix . '_' . uniqid() . '_' . time() . '.' . $file_ext;
        $file_path = $this->upload_dir . '/' . $file_name;
        
        if(move_uploaded_file($file['tmp_name'], $file_path)) {
            return ['success' => true, 'file_path' => $file_name];
        }
        
        return ['success' => false, 'errors' => ['Failed to upload file']];
    }
    
    public function delete($file_path) {
        $full_path = $this->upload_dir . '/' . $file_path;
        if(file_exists($full_path)) {
            return unlink($full_path);
        }
        return false;
    }
}
?>