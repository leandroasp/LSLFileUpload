<?php
class Zend_Controller_Action_Helper_FileUpload extends Zend_Controller_Action_Helper_Abstract
{
  public function makeUpload($options = array())
  {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    array_merge(array(
      'targetDir' => APPLICATION_PATH,
      'cleanupTargetDir' => true,
      'maxFileAge' => 3600), $options);
    
    $targetDir = $options['targetDir'];
    $cleanupTargetDir = $options['cleanupTargetDir']; // Remove old files
    $maxFileAge = $options['maxFileAge']; // Temp file age in seconds

    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
    $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

    $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

    if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
      $ext = strrpos($fileName, '.');
      $fileName_a = substr($fileName, 0, $ext);
      $fileName_b = substr($fileName, $ext);

      $count = 1;
      while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
        $count++;

      $fileName = $fileName_a . '_' . $count . $fileName_b;
    }

    $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    // Create target dir
    if (!file_exists($targetDir))
      @mkdir($targetDir);

    // Remove old temp files	
    if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
      while (($file = readdir($dir)) !== false) {
        $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

        // Remove temp file if it is older than the max age and is not the current file
        if ((filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
          @unlink($tmpfilePath);
        }
      }

      closedir($dir);
    } else {
      return '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}}';
    }

    // Look for the content type header
    if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
      $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

    if (isset($_SERVER["CONTENT_TYPE"]))
      $contentType = $_SERVER["CONTENT_TYPE"];

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (strpos($contentType, "multipart") !== false) {
      if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        // Open temp file
        $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
        if ($out) {
          // Read binary input stream and append it to temp file
          $in = fopen($_FILES['file']['tmp_name'], "rb");

          if ($in) {
            while ($buff = fread($in, 4096))
              fwrite($out, $buff);
          } else {
            return '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}}';
          }
          fclose($in);
          fclose($out);
          @unlink($_FILES['file']['tmp_name']);
        } else {
          return '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}}';
        }
      } else {
        return '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}}';
      }
    } else {
      // Open temp file
      $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
      if ($out) {
        // Read binary input stream and append it to temp file
        $in = fopen("php://input", "rb");

        if ($in) {
          while ($buff = fread($in, 4096))
            fwrite($out, $buff);
        } else {
          return '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}}';
        }

        fclose($in);
        fclose($out);
      } else {
        return '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}}';
      }
    }

    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
      // Strip the temp .part suffix off 
      rename("{$filePath}.part", $filePath);
    }

    // Return JSON-RPC response
    return '{"jsonrpc" : "2.0", "result" : null}';
  }

  public function getUploadNames($size, $targetDir, $sourceDir)
  {
    for($i = 0; $i < $size; $i++) {
      if ($_REQUEST['uploader_' + $i + '_status'] != 'done') continue;
      
      $file = $_REQUEST['uploader_' + $i + '_tmpname'];
      $id = preg_replace('/^(.+)\.[^\.]+$/','$1',$file);
      $crop = $_REQUEST['crop_' + $id];
      $caption = $_REQUEST['caption_' + $id];
      //uploader_0_tmpname
    }
  }
}