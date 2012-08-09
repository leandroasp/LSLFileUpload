<?php
/**
 * LSL FileUpload released under LGPL
 * Author: Leandro Sales <leandroasp@gmail.com>
 * https://github.com/leandroasp/LSLFileUpload
 * Copyright (c) 2012
 * -------------------------------------------------------------------
 * This file is part of LSL FileUpload.
 *
 * LSL FileUpload is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LSL FileUpload is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with LSL FileUpload.  If not, see <http://www.gnu.org/licenses/>.
 */

class Zend_Controller_Action_Helper_FileUpload extends Zend_Controller_Action_Helper_Abstract
{
  public function makeUpload($options = array())
  {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $options = array_merge(
      array(
        'targetDir' => APPLICATION_PATH,
        'cleanupTargetDir' => true,
        'maxFileAge' => 2*3600
      ),
      $options);

    $targetDir = $options['targetDir'];
    $cleanupTargetDir = $options['cleanupTargetDir']; // Remove old files
    $maxFileAge = $options['maxFileAge']; // Temp file age in seconds

    $chunk = intval($this->getRequest()->getPost("chunk"),0);
    $chunks = intval($this->getRequest()->getPost("chunks"),0);
    $fileName = $this->getRequest()->getPost("name");

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

      //Crop the image
      if (isset($options['maxWidth']) || isset($options['maxHeight'])) {
        $ext = strtolower(preg_replace('/^.+\.([^\.]+)$/','$1',$filePath));
        if (in_array($ext,array('jpg','png','gif'))) {
          list($w, $h, $type) = getimagesize($filePath);
          if ($type == 1) $sourceImg = imagecreatefromgif($filePath);
          elseif ($type == 3) $sourceImg = imagecreatefrompng($filePath);
          else $sourceImg = imagecreatefromjpeg($filePath);

          if (isset($options['maxWidth'])) $width = intval($options['maxWidth']);
          if (isset($options['maxHeight'])) $height = intval($options['maxHeight']);
          if ($width <= 0 || $width > $w) $width = $w;
          if ($height <= 0 || $height > $h) $height = $h;

          $x = $y = 0;

          $sourceRatio = $w/$h;
          $dstRatio = $width/$height;
          if ($sourceRatio > $dstRatio) {
            $tempW = round($h * $dstRatio);
            $x1 = round(($w-$tempW)/2);
            $w = $tempW;
          } else if($sourceRatio < $dstRatio) {
            $tempH = round($w / $dstRatio);
            $y = round(($h-$tempH)/2);
            $h = $tempH;
          }

          $targetImg = imagecreatetruecolor($width, $height);
          imagecopyresampled($targetImg,$sourceImg,0,0,$x,$y,$width,$height,$w,$h);

          if ($type == 1) imagegif($targetImg, $filePath);
          elseif ($type == 3) imagepng($targetImg, $filePath);
          else imagejpeg($targetImg, $filePath, 100);

          imagedestroy($targetImg);
          imagedestroy($sourceImg);
        }
      }
    }

    // Return JSON-RPC response
    return '{"jsonrpc" : "2.0", "result" : null}';
  }

  public function moveAndGetFileNames($opt)
  {
    $targetDir = $sourceDir = $thumbnails = '';
    if (isset($opt['targetDir']))  $targetDir = $opt['targetDir'];
    if (isset($opt['sourceDir']))  $sourceDir = $opt['sourceDir'];
    if (isset($opt['thumbnails'])) $thumbnails = $opt['thumbnails'];

    $month = date("Ym");

    if (!file_exists($targetDir . '/' . $month . '/')) {
      @mkdir($targetDir . '/' . $month . '/');
    }

    if ($thumbnails == '' || !is_array($thumbnails) || count($thumbnails) == 0) {
      $thumbnails = array(array('append' => ''));
    } else {
      $add = true;
      foreach($thumbnails as $thumb) {
        if (!isset($thumb['append']) || $thumb['append'] == '') {
          $add = false;
          break;
        }
      }

      if ($add) array_push($thumbnails,array('append' => ''));
    }

    $fields = array('uploaded','uploader');
    $names = array();

    foreach ($fields as $f) {
      $count = intval($this->getRequest()->getPost("${f}_count",0));

      for($i = 0; $i < $count; $i++) {
        $status = $this->getRequest()->getPost("${f}_${i}_status", 'done');
        if ($status != 'done' && $status != '') continue;

        $file = $this->getRequest()->getPost("${f}_${i}_tmpname");
        $id = preg_replace('/^(.+\/)?(.+)\.[^\.]+$/','$2',$file);
        $path = preg_replace('/^(.+\/)?(.+)\.[^\.]+$/','$1',$file);
        $ext = preg_replace('/^.+\.([^\.]+)$/','$1',$file);
        $crop = $this->getRequest()->getPost("crop_${id}");
        $caption = $this->getRequest()->getPost("caption_${id}");
        
        if ($path == '') $path = $month . '/';

        $n = array(
          'name' => $path . $id . '.' . $ext,
          'caption' => $caption
        );

        if ($this->getRequest()->getPost("status_${id}") == 'drop') {
          @unlink($sourceDir . '/' . $file);
          if ($f == 'uploaded') {
            foreach($thumbnails as $thumb) {
              @unlink($targetDir . '/' . $path . $id . $thumb['append'] . '.' . $ext); //remove when the file has been moved (edit mode)
            }
          }
          continue;
        }
        if ($this->getRequest()->getPost("status_${id}") == 'skip') {
          array_push($names,$n);
          continue;
        }

        if (file_exists($sourceDir . '/' . $file)) {
          $sourceFile = $sourceDir . '/' . $file;
        } else if (file_exists($targetDir . '/' . $file)) {
          $sourceFile = $targetDir . '/' . $file;
        } else {
          continue;
        }

        list($w, $h, $type) = getimagesize($sourceFile);

        $x1 = $y1 = $x2 = $y2 = '';
        if ($crop != '') {
          list($x1,$y1,$x2,$y2) = preg_split('/;/',$crop);
        }

        if (!preg_match('/^\d+$/',$x1)) $x1 = 0;
        if (!preg_match('/^\d+$/',$y1)) $y1 = 0;
        if (!preg_match('/^\d+$/',$x2)) $x2 = $w;
        if (!preg_match('/^\d+$/',$y2)) $y2 = $h;

        if ($type == 1) $sourceImg = imagecreatefromgif($sourceFile);
        elseif ($type == 3) $sourceImg = imagecreatefrompng($sourceFile);
        else $sourceImg = imagecreatefromjpeg($sourceFile);

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
          $newName = $targetDir . '/' . $path . $id . $thumb['append'] . '.' . $ext;

          @touch($newName);

          if ($type == 1) imagegif($targetImg, $newName);
          elseif ($type == 3) imagepng($targetImg, $newName);
          else imagejpeg($targetImg, $newName, 90);

          if ($thumb['append'] != '') {
            $n['name_' . $thumb['append']] = $path . $id . $thumb['append'] . '.' . $ext;
          }
          imagedestroy($targetImg);
        }
        imagedestroy($sourceImg);

        array_push($names,$n);
      }
    }

    return $names;
  }

  public function getFieldsOfUploadedFiles($row = null,$fields = array())
  {
    $opt = array('name' => 'name','caption' => 'caption');

    $opt = array_merge($opt, $fields);

    $images = array();

    if (!is_null($row)) {
      for ($i = 0; $i < count($row); $i++) {
        $file = $row[$i][$opt['name']];
        $caption = $row[$i][$opt['caption']];
        $id = preg_replace('/^.+\/(.+)\.[^\.]+$/','$1',$file);

        $item = array(
          'uploaded_' . $i . '_tmpname' => $file,
          'uploaded_' . $i . '_status'  => '',
          'status_'  . $id => 'skip',
          'crop_'    . $id => '',
          'caption_' . $id => $caption
        );

        $images[$id] = $item;
      }
    }

    $fields = array('uploaded','uploader');
    $k = count($images);
    foreach ($fields as $f) {
      $count = intval($this->getRequest()->getPost("${f}_count",0));

      for($i = 0; $i < $count; $i++) {
        $file = $this->getRequest()->getPost("${f}_${i}_tmpname");
        $id = preg_replace('/^(.+\/)?(.+)\.[^\.]+$/','$2',$file);
        $item = array(
          'uploaded_' . $k . '_tmpname' => $file,
          'uploaded_' . $k . '_status'  => $this->getRequest()->getPost("${f}_${i}_status"),
          'status_'  . $id => $this->getRequest()->getPost('status_' . $id),
          'crop_'    . $id => $this->getRequest()->getPost('crop_' . $id),
          'caption_' . $id => $this->getRequest()->getPost('caption_' . $id)
        );
        $images[$id] = $item;
        $k++;
      }
    }

    return $images;
  }
}
