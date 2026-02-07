<?php

require_once __DIR__ . '/InputSanitizer.php';

/**
 * Secure File Upload Handler
 * ป้องกันการอัปโหลดไฟล์อันตราย
 */

class SecureFileUploader
{
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    private $uploadPath;
    private $errors = [];

    public function __construct($uploadPath)
    {
        $this->uploadPath = $uploadPath;
        $this->ensureUploadDir();
    }

    /**
     * จัดการการอัปโหลดไฟล์อย่างปลอดภัย
     */
    public function upload($file, $prefix = '')
    {
        // ตรวจสอบข้อผิดพลาดพื้นฐาน
        if (!$this->validateBasic($file)) {
            return false;
        }

        // ตรวจสอบความปลอดภัยของไฟล์
        $this->errors = InputSanitizer::validateFileUpload($file, $this->allowedTypes);
        if (!empty($this->errors)) {
            return false;
        }

        // สร้างชื่อไฟล์ที่ปลอดภัย
        $secureFilename = $this->generateSecureFilename($file['name'], $prefix);
        $targetPath = $this->uploadPath . '/' . $secureFilename;

        // ตรวจสอบเนื้อหาไฟล์อีกครั้ง
        if (!$this->validateFileContent($file['tmp_name'])) {
            return false;
        }

        // อัปโหลดไฟล์
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // ตั้งสิทธิ์ไฟล์ให้ปลอดภัย
            chmod($targetPath, 0644);
            return [
                'success' => true,
                'filename' => $secureFilename,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'path' => $targetPath
            ];
        }

        $this->errors[] = 'Failed to move uploaded file';
        return false;
    }

    /**
     * ตรวจสอบข้อผิดพลาดพื้นฐาน
     */
    private function validateBasic($file)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = 'Invalid file upload';
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = 'File too large (max ' . ($this->maxFileSize / 1024 / 1024) . 'MB)';
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบเนื้อหาไฟล์เพิ่มเติม
     */
    private function validateFileContent($tmpName)
    {
        $content = file_get_contents($tmpName);
        
        // ตรวจสอบ PHP tags
        if (strpos($content, '<?php') !== false) {
            $this->errors[] = 'File contains PHP code';
            return false;
        }

        if (strpos($content, '<?') !== false && strpos($content, '?>') !== false) {
            $this->errors[] = 'File contains executable code';
            return false;
        }

        // ตรวจสอบสตริงอันตรายอื่นๆ
        $dangerousPatterns = [
            '/eval\s*\(/',
            '/system\s*\(/',
            '/exec\s*\(/',
            '/shell_exec\s*\(/',
            '/passthru\s*\(/',
            '/`.*`/', // backticks
            '/\$_POST/',
            '/\$_GET/',
            '/\$_REQUEST/',
            '/file_get_contents\s*\(/',
            '/file_put_contents\s*\(/',
            '/fopen\s*\(/',
            '/fwrite\s*\(/'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->errors[] = 'File contains potentially dangerous code';
                return false;
            }
        }

        return true;
    }

    /**
     * สร้างชื่อไฟล์ที่ปลอดภัย
     */
    private function generateSecureFilename($originalName, $prefix = '')
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // ตรวจสอบว่า extension อนุญาตหรือไม่
        if (!in_array($extension, $this->allowedTypes)) {
            $this->errors[] = 'File type not allowed';
            return false;
        }

        $basename = InputSanitizer::sanitize(pathinfo($originalName, PATHINFO_FILENAME), 'filename');
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        
        return ($prefix ? $prefix . '_' : '') . $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * สร้าง directory สำหรับอัปโหลด
     */
    private function ensureUploadDir()
    {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        
        // ตรวจสอบว่ามี .htaccess ป้องกันหรือไม่
        $htaccessPath = $this->uploadPath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $this->createHtaccess($htaccessPath);
        }
    }

    /**
     * สร้างไฟล์ .htaccess ป้องกันการรัน script
     */
    private function createHtaccess($path)
    {
        $content = '
# Prevent execution of scripts
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.php*">
    Order Allow,Deny
    Deny from all
</Files>

# Disable PHP engine
php_flag engine off

# Prevent directory listing
Options -Indexes

# Only allow specific file types
<FilesMatch "^(?!(.*\.(jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|txt)$).*$)">
    Order Allow,Deny
    Deny from all
</FilesMatch>
';
        file_put_contents($path, $content);
    }

    /**
     * ดับเบิลการตรวจสอบไฟล์อัปโหลด
     */
    public function scanExistingFiles()
    {
        $suspiciousFiles = [];
        
        if (!is_dir($this->uploadPath)) {
            return $suspiciousFiles;
        }

        $files = scandir($this->uploadPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $this->uploadPath . '/' . $file;
            
            if (is_file($filePath)) {
                // ตรวจสอบไฟล์ที่น่าสงสัย
                if ($this->isSuspiciousFile($filePath, $file)) {
                    $suspiciousFiles[] = [
                        'file' => $file,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'reason' => $this->getSuspiciousReason($filePath, $file)
                    ];
                }
            }
        }
        
        return $suspiciousFiles;
    }

    /**
     * ตรวจสอบว่าไฟล์น่าสงสัยหรือไม่
     */
    private function isSuspiciousFile($filePath, $filename)
    {
        // ตรวจสอบนามสกุลไฟล์อันตราย
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'pl', 'py', 'cgi', 'sh', 'bat', 'exe'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            return true;
        }

        // ตรวจสอบชื่อไฟล์ที่น่าสงสัย
        if (strpos($filename, 'emergency') !== false || 
            strpos($filename, 'backdoor') !== false ||
            strpos($filename, 'shell') !== false ||
            strpos($filename, 'hack') !== false) {
            return true;
        }

        // ตรวจสอบเนื้อหาไฟล์
        $content = file_get_contents($filePath);
        if (strpos($content, '<?php') !== false || 
            strpos($content, 'eval(') !== false ||
            strpos($content, 'system(') !== false) {
            return true;
        }

        return false;
    }

    /**
     * หาเหตุผลที่ไฟล์น่าสงสัย
     */
    private function getSuspiciousReason($filePath, $filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'pl', 'py', 'cgi', 'sh', 'bat', 'exe'];
        
        if (in_array($extension, $dangerousExtensions)) {
            return 'Dangerous file extension: .' . $extension;
        }

        if (strpos($filename, 'emergency') !== false || 
            strpos($filename, 'backdoor') !== false) {
            return 'Suspicious filename';
        }

        $content = file_get_contents($filePath);
        if (strpos($content, '<?php') !== false) {
            return 'Contains PHP code';
        }

        if (strpos($content, 'eval(') !== false) {
            return 'Contains dangerous functions';
        }

        return 'Unknown reason';
    }

    /**
     * ลบไฟล์ที่น่าสงสัย
     */
    public function removeSuspiciousFile($filePath)
    {
        if (file_exists($filePath) && is_file($filePath)) {
            // สำรองไฟล์ไว้ก่อนลบ (optional)
            $backupPath = $filePath . '.quarantine_' . time();
            copy($filePath, $backupPath);
            
            if (unlink($filePath)) {
                return [
                    'success' => true,
                    'message' => 'File removed successfully',
                    'backup' => $backupPath
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Failed to remove file'
        ];
    }

    /**
     * ดับเบิลข้อผิดพลาด
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * แปลงข้อความ error code
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * ตั้งค่าประเภทไฟล์ที่อนุญาต
     */
    public function setAllowedTypes($types)
    {
        $this->allowedTypes = $types;
    }

    /**
     * ตั้งค่าขนาดไฟล์สูงสุด
     */
    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
    }
}
