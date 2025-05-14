<?php

class FileUtility
{
    const use_s3 = false;
    public $maxSize, $ext, $path, $errors;
    public $filename, $extension, $file;
    // static variables
    public static $FIRST = 1, $LAST = 2;
    /**
     * Constructor
     * 
     * @param int $maxSize
     * @param array $extensions
     * @param array $options
     */
    public function __construct($maxSize, $extensions = array())
    {
        $this->maxSize = $maxSize;
        $this->ext = $extensions;

        foreach ($this->ext as $k => $ext) {
            $this->ext[$k] = strtolower(trim($ext));
        }
    }

    /**
     * 
     * upload a file to destination
     * @param array $file
     * @param string $dest_path
     * @return boolean
     */
    public function uploadFile($file, $dest_path, $filename = "")
    {
        //validating file
        $this->errors = array();
        $file["name"] = trim($file["name"]);
        if (!$this->validateFile($file)) {
            return false;
        }

        //creating folder
        $dest_path = static::removePathSlashs($dest_path);
        $dest_path .= "/";

        self::createFolder($dest_path);

        $this->path = $dest_path;

        $temp = pathinfo($file["name"]);

        $this->filename = static::cleanFileName($temp['filename']);
        $this->extension = $temp['extension'];

        if ($filename) {
            $this->filename = static::cleanFileName($filename);
            if (strlen($this->filename) > 100) {
                $this->filename = substr($this->filename, 0, 100);
            }

            $this->file = $this->filename . "." . $this->extension;
        } else {
            if (strlen($this->filename) > 100) {
                $this->filename = substr($this->filename, 0, 100);
            }

            $this->file = self::getAutoincreamentFileName($this->filename, $this->extension, $dest_path);
            $this->filename = pathinfo($this->file, PATHINFO_FILENAME);
        }

        return move_uploaded_file($file['tmp_name'], $this->path . $this->file);
    }

    /**
     * validate the file
     * @param string $file
     * @return boolean
     */
    public function validateFile($file)
    {
        $result = true;

        if ($file['size'] > $this->maxSize) {
            $this->errors[] = "File size must not exceeds " . round($this->maxSize / 1024) . " kb";
            $result = false;
        }

        $temp = pathinfo($file["name"]);

        $this->filename = $temp['filename'];
        if (!isset($temp['extension']) || !$temp['extension']) {
            $this->errors[] = "Could not find type of file";
            $result = false;
        } else {
            $this->extension = strtolower($temp['extension']);

            if (!empty($this->ext) && !in_array($this->extension, $this->ext)) {
                $this->errors[] = "Invalid file Type : " . $this->extension;
                $result = false;
            }
        }

        return $result;
    }

    /**
     * return filename which which will be save 
     * @param string $filename
     * @param string $ext
     * @param string $dest_path
     * @return string
     */
    public static function getAutoincreamentFileName($filename, $ext, $dest_path, $sep = "_", $i = 0)
    {
        $temp_name = $i > 0 ? $filename . $sep . $i : $filename;

        if (file_exists($dest_path . $temp_name . "." . $ext)) {
            return self::getAutoincreamentFileName($filename, $ext, $dest_path, $sep, $i + 1);
        } else {
            return $temp_name . "." . $ext;
        }
    }

    public static function createFolder($path)
    {
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, TRUE)) {
                throw new Exception("Fail to create $path");
            }
        } else {
            if (!is_readable($path)) {
                throw new Exception("$path is not readable");
            }
        }
    }

    public static function deleteAll($path, $exts = array(), $recursive = false)
    {
        $files = self::getFileList($path, $exts, $recursive);

        $result = true;
        foreach ($files as $file) {
            if (unlink($file)) {
                //continue
            } else {
                $result = false;
            }
        }

        return $result;
    }

    public static function getFileList($path, $exts = array(), $recursive = false)
    {
        $files = scandir($path);
        $ret_files = array();

        foreach ($files as $k => $file) {
            $f = $path . $file . "/";

            if ($file == '.' || $file == '..') {
            } else if ($recursive && is_dir($f)) {
                $ret_files = array_merge($ret_files, self::getFileList($f, $exts, $recursive));
            } else if (!empty($exts) && !in_array(pathinfo($file, PATHINFO_EXTENSION), $exts)) {
            } else {
                $ret_files[] = $path . $file;
            }
        }

        return $ret_files;
    }

    public static function read($file)
    {
        $file = explode("?", $file)[0];

        if (file_exists($file)) {
            return array(
                "filename" => pathinfo($file, PATHINFO_BASENAME),
                "content_type" => mime_content_type($file),
                "content" => file_get_contents($file)
            );
        }

        return false;
    }

    public static function delete($file, $aws = true)
    {
        $file = explode("?", $file)[0];

        if (file_exists($file)) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    public static function isExist($file)
    {
        $file = trim($file);
        $file = explode("?", $file)[0];

        if (!$file) {
            return false;
        }

        if (file_exists($file)) {
            return true;
        }

        return false;
    }

    public static function move($src, $dest, bool $will_override = false)
    {
        if ( !file_exists($src) )
        {
            throw new Exception("Src : $src is not exist");
        }

        if ( is_dir($src) )
        {
            throw new Exception("Src : $src is a directroy. only file can move");
        }

        $dest_path = dirname($dest);        

        if ( file_exists($dest) )
        {
            if (!$will_override)
            {
                $filename = self::cleanFileName(pathinfo($dest, PATHINFO_FILENAME));
                $ext = pathinfo($dest, PATHINFO_EXTENSION);

                $file = self::getAutoincreamentFileName($filename, $ext, $dest_path);

                $dest = $dest_path . "/" . $file;
            }
        }
        else
        {
            $ext = pathinfo($dest, PATHINFO_EXTENSION);

            if (!$ext)
            {
                throw new Exception("Dest : $dest have no extension");
            }
            
            self::createFolder($dest_path);
        }

        if (rename($src, $dest)) {
            return $dest;
        }

        return false;
    }

    public static function removePathSlashs($path, $side = '')
    {
        $side = strtoupper($side);
        $path = trim(str_replace('\\', '/', $path));

        if ($side == 'FIRST' || $side == 'START' || empty($side)) {
            if (substr($path, 0, 1) == "/") {
                $path = substr($path, 1, strlen($path));
            }
        }

        if ($side == 'LAST' || $side == 'END' || empty($side)) {
            if (substr($path, -1) == "/") {
                $path = substr($path, 0, strrpos($path, "/"));
            }
        }
        return $path;
    }

    public static function cleanFileName($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-_]/', '', $string); // Removes special chars.
    }

    public static function base64ToFile(String $base64, String $dest_path, String $file): String
    {
        $dest_path = self::removePathSlashs($dest_path);

        self::createFolder($dest_path);

        $filename = self::cleanFileName(pathinfo($file, PATHINFO_FILENAME));
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        $file = self::getAutoincreamentFileName($filename, $ext, $dest_path);

        $file = $dest_path . "/" . $file;

        $arr = explode(',', $base64);

        if (count($arr) > 1)
        {
            $base64 = $arr[1];
        }
        else
        {
            $base64 = $arr[0];
        }

        $ifp = fopen($file, 'wb');

        fwrite($ifp, base64_decode($base64));

        // clean up the file resource
        fclose($ifp);

        return $file;
    }
}
