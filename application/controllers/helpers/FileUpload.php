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

  public function getUploadNames($count, $targetDir, $sourceDir, $thumbnails = null)
  {
    $month = date("Ym");

    if (!file_exists($targetDir . '/' . $month . '/')) {
      mkdir($targetDir . '/' . $month . '/');
    }

    $targetDir = $targetDir . '/' . $month;

    if (is_null($thumbnails) || !is_array($thumbnails) || count($thumbnails) == 0) {
      $thumbnails = array(array('append' => ''));
    }

    $names = array();
    for($i = 0; $i < $count; $i++) {
      if ($_REQUEST['uploader_' . $i . '_status'] != 'done') continue;

      $file = $_REQUEST['uploader_' . $i . '_tmpname'];
      $id = preg_replace('/^(.+)\.[^\.]+$/','$1',$file);
      $ext = preg_replace('/^.+\.([^\.]+)$/','$1',$file);
      $crop = $_REQUEST['crop_' . $id];
      $caption = $_REQUEST['caption_' . $id];

      $sourceFile = $sourceDir . '/' . $file;

      list($w, $h, $type) = getimagesize($sourceFile);

      $x1 = $y1 = $x2 = $y2 = '';
      if ($crop != '') {
        list($x1,$y1,$x2,$y2) = preg_split('/;/',$crop);
      }
      if (preg_match('/\D/',$x1)) $x1 = 0;
      if (preg_match('/\D/',$y1)) $y1 = 0;
      if (preg_match('/\D/',$x2)) $x2 = $w;
      if (preg_match('/\D/',$y2)) $y2 = $h;

      if ($type == 1) $sourceImg = imagecreatefromgif($sourceFile);
      elseif ($type == 3) $sourceImg = imagecreatefrompng($sourceFile);
      else $sourceImg = imagecreatefromjpeg($sourceFile);

      $n = array(
        'name' => $month . '/' . $id . '.' . $ext,
        'caption' => $caption
      );

      foreach($thumbnails as $thumb) {
        if (!isset($thumb['width']) || preg_match('/\D/',$thumb['width'])) $thumb['width'] = $x2 - $x1;
        if (!isset($thumb['height']) || preg_match('/\D/',$thumb['height'])) $thumb['height'] = $y2 - $y1;

        $sourceW = $x2 - $x1;
        $sourceH = $y2 - $y1;

        $sourceRatio = $sourceW/$sourceH;
        $dstRatio = $thumb['width']/$thumb['height'];

        $tempX1 = $x1;
        $tempY1 = $y1;
        if ($sourceRatio > $dstRatio) {
          $tempW = round($sourceH * $dstRatio);
          $tempX1 += round(($sourceW-$tempW)/2);
          $sourceW = $tempW;
        } else if($sourceRatio < $dstRatio) {
          $tempH = round($sourceW / $dstRatio);
          $tempY1 += round(($sourceH-$tempH)/2);
          $sourceH = $tempH;
        }

        //echo "$tempX1 ; $tempY1 ; $x2 ; $y2 ; $sourceW ; $sourceH ; " . $thumb['width'] . ' ; ' . $thumb['height'] . '<br/>';

        $targetImg = imagecreatetruecolor($thumb['width'], $thumb['height']);
        imagecopyresampled($targetImg,$sourceImg,0,0,$tempX1,$tempY1,$thumb['width'], $thumb['height'],$sourceW,$sourceH);

        if (!isset($thumb['append'])) $thumb['append'] = '';
        $newName = $targetDir . '/' . $id . $thumb['append'] . '.' . $ext;

        touch($newName);

        if ($type == 1) imagegif($targetImg, $newName);
        elseif ($type == 3) imagepng($targetImg, $newName);
        else imagejpeg($targetImg, $newName, 90);

        if ($thumb['append'] != '') {
          $n['name_' . $thumb['append']] = $month . '/' . $id . $thumb['append'] . '.' . $ext;
        }
      }

      array_push($names,$n);
    }

    return $names;
  }
}