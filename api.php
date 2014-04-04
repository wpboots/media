<?php

/**
 * Media
 *
 * @package Boots
 * @subpackage Media
 * @version 1.0.0
 * @license GPLv2
 *
 * Boots - The missing WordPress framework. http://wpboots.com
 *
 * Copyright (C) <2014>  <M. Kamal Khan>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

class Boots_Media
{
    private $Boots;
    private $Settings;
    private $dir;
    private $url;

    private $source = null;
    private $width = null;
    private $height = null;
    private $crop = null;

    public function __construct($Boots, $Args, $dir, $url)
    {
        $this->Boots = $Boots;
        $this->Settings = $Args;
        $this->dir = $dir;
        $this->url = $url;
    }

    public function wp()
    {
        add_action('wp_enqueue_scripts', array(&$this, 'scripts'));
    }

    public function admin()
    {
        add_action('admin_enqueue_scripts', array(&$this, 'scripts'));
    }

    public function scripts()
    {
        // wp_enqueue_media doesn't
        // work on the front! (need fix)
        if(!is_admin())
        {
            return false;
        }

        wp_enqueue_media();

        $this->Boots->Enqueue
        ->script('jquery')->done()
        ->raw_script('boots_media')
            ->source($this->url . '/js/boots_media.min.js')
            ->requires('jquery')
            ->done(true);
    }

    private function _get($array, $url, $width, $height)
    {
        $this->source = null;
        $this->width = null;
        $this->height = null;
        $this->crop = null;

        return !$array ? $url : array(
            'url' => $url,
            'width' => $width,
            'height' => $height
        );
    }

    private function url($ver = false, $array = false)
	{
		$img_src = wp_get_attachment_image_src($this->source, $ver ? $ver : '');
		if(!$img_src)
        {
            return false;
        }
        $return['src'] = $img_src[0];
        $return['width'] = $img_src[1];
        $return['height'] = $img_src[2];
        return $array ? $return : $return['src'];
	}

    public function width($w)
    {
        $this->width = $w;

        return $this;
    }

    public function height($h)
    {
        $this->height = $h;

        return $this;
    }

    public function crop($crop = true)
    {
        $this->crop = $crop;

        return $this;
    }

    public function image($src)
    {
        $this->source = $src;
        $this->crop = true;

        return $this;
    }

    public function get($array = false)
    {
        if(!$this->source)
        {
            $this->Boots->error('Image source not found. Have you called <em>Media&rarr;image($src)</em> ?');
            return '';
        }

        $img_url = $this->source;
        if(is_numeric($img_url))
        {
            $img_url = $this->url();
            if($img_url === false)
            {
                $this->Boots->error('Image source id incorrect');
            }
        }

        // $regex_date=/YYYY/MM/
        $regex_img = preg_match('{\/[0-9]{4}\/[0-9]{2}\/}', $img_url, $regex_img_date);

        if($regex_img)
        {
            $regex_img_date = trim($regex_img_date[0], '/'); // YYYY/MM
            $wp_upload_dir = wp_upload_dir($regex_img_date);
            $wp_upload_path = $wp_upload_dir['path'];
            // Is the current working upload path based on date (YYYY/MM)
            $regex_dir = preg_match('{\/[0-9]{4}\/[0-9]{2}}', $wp_upload_path, $regex_dir_date);
            if(!$regex_dir)
            {
                $wp_upload_path .= '/' . $regex_img_date; // path/to/upload/dir/YYYY/MM
            }
        }
        // The image is NOT based on date (YYYY/MM)
        else
        {
            $wp_upload_dir = wp_upload_dir();
            $wp_upload_path = $wp_upload_dir['path'];
            // If the working upload path is based on date (YYYY/MM), lets remove it
            // path/to/upload/dir
            $wp_upload_path = preg_replace('{\/[0-9]{4}\/[0-9]{2}}', '', $wp_upload_path);
        }

        // Okay, we have the upload path, lets get some information about the image
        $file = array();
        $file['name'] = basename($img_url);
        $file['path'] = $wp_upload_path . '/' . $file['name'];
        if(!file_exists($file['path']))
        {
             $this->Boots->error('Image not found: <em>' . $file['path'] . '</em>');
        }

        $file['size'] = getimagesize($file['path']);
        $img_width = $this->width ? $this->width : $file['size'][0];
        $img_height = $this->height ? $this->height : $file['size'][1];
        $file['info'] = pathinfo($img_url);

        // We have the information, lets proceed if the required size is smaller than the original.
        // Otherwise we have nothing to do
        if(!$img_width || !$img_height || (($img_width >= $file['size'][0]) && ($img_height >= $file['size'][1])))
        {
            return $this->_get($array, $img_url, $file['size'][0], $file['size'][1]);
        }

        // Adjust the new width and height if the user doesn't want it cropped. (i.e. Get the proportional size)
        if(!$this->crop)
        {
            $proportionality = wp_constrain_dimensions($file['size'][0], $file['size'][1], $img_width, $img_height);
            $img_width = $proportionality[0];
            $img_height = $proportionality[1];
        }

        // Lets save the new file name as per namespace of wordpress images
        $file['new'] = $file['info']['dirname'] . '/' . $file['info']['filename'] . '-' . $img_width . 'x' . $img_height . '.' . $file['info']['extension'];

        // Only create the file, if it does not exist.
        // Nothing to do if the new file already exists
        if(file_exists($file['new']))
        {
            return $this->_get($array, $file['new'], $img_width, $img_height);
        }

        if(!function_exists('wp_get_current_user'))
        {
            $this->Boots->error('<em>Use the init or any subsequent action to call this function</em>');
        }
        $img_new = image_make_intermediate_size($file['path'], $img_width, $img_height, $this->crop);
        $file['new'] = $file['info']['dirname'] . '/' . $img_new['file'];

        return $this->_get($array, $file['new'], $img_new['width'], $img_new['height']);
    }
}






