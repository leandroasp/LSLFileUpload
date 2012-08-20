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

class Zend_View_Helper_ShowFilesUploaded extends Zend_View_Helper_Abstract
{
  public function showFilesUploaded($thumbnails, $options = array())
  {
    $options = array_merge(array(
      'id'          => 'thumbnails',
      'withCrop'    => false,
      'withCaption' => false,
      'isImage'     => false
    ), $options);

    if (is_null($thumbnails) || !is_array($thumbnails)) {
      $thumbnails = array();
    }

    $return = '<ul class="thumbnails" id="' . $options['id'] . '">';
    $countUpload = count($thumbnails);

    $i = 0;
    foreach ($thumbnails as $id => $thumb) {
      if ($thumb['uploaded_' . $i . '_status'] == 'done') {
        $temp = 'temp/';
      } else {
        $temp = '';
      }

      if ($thumb['status_' . $id] == 'drop') {
        $disabled = ' lsl-disabled';
        $countUpload--;
      } else {
        $disabled = '';
      }

      $return .= '<li class="span2 ' . $id . $disabled . '">' . PHP_EOL;
      $return .= '  <div class="thumbnail">' . PHP_EOL;

      if ($disabled == '') {
        $return .= '    <a href="#" class="btn btn-inverse btn-mini lsl-btn-close fn-close" title="Remove file"><i class="icon-ok icon-white"></i></a>' . PHP_EOL;
      } else {
        $return .= '    <a href="#" class="btn btn-inverse btn-mini lsl-btn-close fn-close" title="Activate file"><i class="icon-remove icon-white"></i></a>' . PHP_EOL;
      }

      $return .= '    <input type="hidden" name="uploaded_' . $i . '_tmpname" value="' . $thumb['uploaded_' . $i . '_tmpname'] . '" />' . PHP_EOL;
      $return .= '    <input type="hidden" name="uploaded_' . $i . '_status" value="' . $thumb['uploaded_' . $i . '_status'] . '" />' . PHP_EOL;
      $return .= '    <input type="hidden" name="status_' . $id . '" value="' . $thumb['status_' . $id] . '" class="fn-status" />' . PHP_EOL;

      if ($options['withCrop'] == true) {
        $return .= '    <input type="hidden" name="crop_' . $id . '" value="' . $thumb['crop_' . $id] . '" />' . PHP_EOL;
      }

      if ($options['isImage'] == true) {
        $return .= '    <img src="' . $this->view->baseUrl('/files/' . $temp . $thumb['uploaded_' . $i . '_tmpname']) . '" width="160" alt="" class="img_' . $id . '" />' . PHP_EOL;
      } else {
        $ext = preg_replace('/^.+\.([^\.]+)$/','$1',$thumb['uploaded_' . $i . '_tmpname']);
        $return .= '    <p class="lsl-thumbnail-noimage" title="Name: ' . $thumb['uploaded_' . $i . '_tmpname'] . '">' . strtoupper($ext) . ' file</p>' . PHP_EOL;
      }

      if ($options['withCrop'] == true || $options['withCaption'] == true) {
        $return .= '    <div class="caption">' . PHP_EOL;

        if ($options['withCaption'] == true) {
          $return .= '      <textarea name="caption_' . $id . '" class="lsl-caption lsl-hidden">' . $thumb['caption_' . $id] . '</textarea>' . PHP_EOL;
        }
        $return .= '<p>';
        if ($options['withCrop'] == true) {
          $return .= '<a href="#" class="btn btn-mini btn-info fn-crop">Crop</a> ';
        }
        if ($options['withCaption'] == true) {
          $return .= '<a href="#" class="btn btn-mini btn-info fn-caption">Caption</a>' . PHP_EOL;
        }
        $return .= '</p></div>' . PHP_EOL;
      }
      $return .= '  </div>' . PHP_EOL;
      $return .= '</li>' . PHP_EOL;
      $i++;
    }
    
    $return .= '</ul>';

    $return1 = '<input type="hidden" name="my_upload_count" value="' . $countUpload . '" id="my_upload_count" />' . PHP_EOL;
    $return1 .= '<input type="hidden" name="uploaded_count" value="' . count($thumbnails) . '" id="uploaded_count" />' . PHP_EOL;

    return $return1 . $return;
  }
}
