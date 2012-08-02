<?php
class IndexController extends Zend_Controller_Action
{
  public function init()
  {
    /* Initialize action controller here */
  }

  public function indexAction()
  {
    // action body
  }

  public function createAction()
  {
    print_r($_REQUEST);
  }
  
  public function uploadAction()
  {
    $this->getHelper('layout')->disableLayout();
    $this->getHelper('viewRenderer')->setNoRender();

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    $targetDir = realpath(APPLICATION_PATH . "/../public/files/temp");
    $cleanupTargetDir = true; // Remove old files
    $maxFileAge = 1 * 3600; // Temp file age in seconds

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
      echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}}';
      return;
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
            echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}}';
            return;
          }
          fclose($in);
          fclose($out);
          @unlink($_FILES['file']['tmp_name']);
        } else {
          echo '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}}';
          return;
        }
      } else {
        echo '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}}';
        return;
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
          echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}}';
          return;
        }

        fclose($in);
        fclose($out);
      } else {
        echo '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}}';
        return;
      }
    }

    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
      // Strip the temp .part suffix off 
      rename("{$filePath}.part", $filePath);
    }

    // Return JSON-RPC response
    echo '{"jsonrpc" : "2.0", "result" : null}';
  }
}