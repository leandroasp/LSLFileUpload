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

function LSLFileUpload(opt) {
  var opt = $.extend({
    url: 'upload.php',
    max_file_size: '10mb',
    max_file_count: 20,
    file_is_image: false,
    extensions: '*',
    base_url: '/',
    temp_dir: '/temp/',
    with_caption: false,
    with_crop: false,
    aspect_ratio: 0
  }, opt);

  var $thumbnails = opt.thumbnails;
  $(function() {
    opt.uploader.plupload({
      runtimes : 'flash,html5,browserplus,silverlight,gears,html4',
      url : opt.url,
      max_file_size : opt.max_file_size,
      max_file_count: opt.max_file_count,
      chunk_size : '1mb',
      unique_names : true,
      multiple_queues : true,
      filters : [
        {title : "Files", extensions : opt.extensions},
      ],

      // Flash settings
      flash_swf_url : opt.base_url + '/js/plupload.flash.swf',

      // Silverlight settings
      silverlight_xap_url : opt.base_url + '/js/plupload.silverlight.xap',

      init: {
        FileUploaded: function(up, file, info) {
          var html ='<li class="span2 ' + file.id + '">' +
                    '  <div class="thumbnail">' +
                    '    <a href="#" class="btn btn-inverse btn-mini lsl-btn-close fn-close" title="Remove file"><i class="icon-ok icon-white"></i></a>' +
                    '    <input type="hidden" name="status_' + file.id + '" value="ok" class="fn-status" />';

          if (opt.with_crop) {
            html += '    <input type="hidden" name="crop_' + file.id + '" value="" />';
          }

          if (opt.file_is_image) {
            html += '    <img src="' + opt.temp_dir + file.target_name + '" width="160" alt="" class="img_' + file.id + '" />';
          } else {
            var ext = file.target_name.replace(/^.+\.([^\.]+)$/,'$1');
            html += '    <p class="lsl-thumbnail-noimage" title="' + file.name + '">Arquivo ' + ext.toUpperCase() + '</p>';
          }

          if (opt.with_crop || opt.with_caption) {
            html += '<div class="caption">';
            if (opt.with_caption) {
              html += '<textarea name="caption_' + file.id + '" class="lsl-caption lsl-hidden"></textarea>';
            }
            html += '<p>';
            if (opt.with_crop) {
              html += '<a href="#" class="btn btn-mini btn-info fn-crop">Crop</a> ';
            }
            if (opt.with_caption) {
              html += '<a href="#" class="btn btn-mini btn-info fn-caption">Caption</a>';
            }
            html += '</p></div>';
          }

          html += '</div></li>';

          html = $(html);
          $thumbnails.append(html);
          if (opt.with_caption) showHideCaption(html.find('a.fn-caption'));
          if (opt.with_crop) addCropImage(html.find('a.fn-crop'));
          addDropFile(html.find('a.fn-close'));
          updateInputCount();
        },
        FilesRemoved: function(up, files) {
          plupload.each(files, function(file, i) {
            $thumbnails.find('li.' + file.id).detach();
            updateInputCount();
          });
        }
      }
    });
  });

  function updateInputCount() {
    var $upload_count = $thumbnails.find('input#my_upload_count');
    var size = $thumbnails.find('li:not(.lsl-disabled)').size();
    if ($upload_count.size() == 0) {
      $upload_count = $('<input type="hidden" name="my_upload_count" value="' + size + '" id="my_upload_count" />');
      $thumbnails.prepend($upload_count);
    } else {
      $upload_count.val(size);
    }
  }

  function showHideCaption($element) {
    $element.click(function() {
      var $p = $(this).parent();
      if (!$p.parent().parent().parent().hasClass('lsl-disabled')) {
        $p.prev().toggleClass('lsl-hidden');
      }
      return false;
    });
  }
  if (opt.with_caption) showHideCaption($thumbnails.find('a.fn-caption'));

  function addDropFile($element) {
    $element.click(function() {
      var $a = $(this);
      var $li = $a.parent().parent();
      $li.toggleClass('lsl-disabled');
      var $input = $li.find('input.fn-status');
      if ($li.hasClass('lsl-disabled')) {
        var val = $input.val();
        $input.val('drop');
        $input.attr('data',val);
        $a.attr('title','Activate file').find('i').removeClass('icon-ok').addClass('icon-remove');
      } else {
        var val = $input.attr('data');
        $input.val(val);
        $input.removeAttr('data');
        $a.attr('title','Remove file').find('i').removeClass('icon-remove').addClass('icon-ok');
      }

      updateInputCount();
      return false;
    });
  }
  addDropFile($thumbnails.find('a.fn-close'));

  var dialogsInPage = new Array();
  function addCropImage($element) {
    $element.click(function() {
      var $div = $(this).parent().parent();
      if ($div.parent().parent().hasClass('lsl-disabled')) {
        return false;
      }
      var $img = $div.prev();
      var $input = $img.prev();
      var c = $img.attr('class');

      if ($img.get(0).tagName.toLowerCase() != 'img') return false;

      var $newImg = null;
      if (dialogsInPage[c]) {
        $newImg = dialogsInPage[c];
        if ($newImg.dialog('isOpen')) {
          $newImg.dialog('close');
        } else {
          $newImg.dialog('open');
        }
      } else {
        $newImg = $img.clone();
        $newImg.removeAttr('width');
        $newImg.load(function() {
          var width = $newImg.width();
          var height = $newImg.height();

          $newImg.attr('id',c);
          $newImg.attr('width',width);

          $newImg.dialog({
            open: function(event, ui) {
              var coords = $input.val();
              if (coords == '') {
                coords = [0,0,width,height];
              } else {
                coords = coords.split(';');
              }
              $(this).Jcrop({
                onSelect: function(c) {
                  $input.val(Math.round(c.x) + ';' + Math.round(c.y) + ';' + Math.round(c.x2) + ';' + Math.round(c.y2));
                  $div.parent().find('input.fn-status').val('ok');
                },
                setSelect: coords,
                aspectRatio: opt.aspect_ratio
              });
              $(this).css({display:'none'});
            },
            title: "Crop the image",
            resizable: false,
            position: ["center","center"],
            width: width+28,
            minHeight: 1,
            zIndex: 2000,
            show: "slide",
            hide: "slide"
          });
        });
        $('body').append($newImg);
        dialogsInPage[c] = $newImg;
      }
      return false;
    });
  }
  if (opt.with_crop) addCropImage($thumbnails.find('a.fn-crop'));
}
