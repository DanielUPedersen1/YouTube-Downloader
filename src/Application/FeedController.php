<?php

/*
 * PHP script for downloading videos from youtube
 * Copyright (C) 2012-2017  John Eckman
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, see <http://www.gnu.org/licenses/>.
 */

namespace YoutubeDownloader\Application;

use Exception;
use YoutubeDownloader\Config;
use YoutubeDownloader\VideoInfo\VideoInfo;

/**
 * The download controller
 */
class FeedController extends ControllerAbstract
{
	/**
	 * Excute the Controller
	 *
	 * @param string $route
	 * @param YoutubeDownloader\Application\App $app
	 *
	 * @return void
	 */
	public function execute()
	{
		$config = $this->get('config');
		$toolkit = $this->get('toolkit');
        $youtube_provider = $this->get('YoutubeDownloader\Provider\Youtube\Provider');
        $helper = new Helper();
            
        $dom=new \DOMDocument();
        if(isset($_GET["channelid"]))
        {
            $dom->load("https://www.youtube.com/feeds/videos.xml?channel_id=" . $_GET["channelid"]);
        }
        if(isset($_GET["user"]))
        {
            $dom->load("https://www.youtube.com/feeds/videos.xml?user=" . $_GET["user"]);
        }

        $root=$dom->documentElement; // This can differ (I am not sure, it can be only documentElement or documentElement->firstChild or only firstChild)

        $entries=$root->getElementsByTagName('entry');

        // Loop trough childNodes
        foreach ($entries as $entry) {
            $url=$entry->getElementsByTagName('link')->item(0)->getAttributeNode('href')->nodeValue;
            $title=$entry->getElementsByTagName('title')->item(0)->nodeValue;
            
            $video_id = substr(parse_url($url, PHP_URL_QUERY), 2);
            $video_info = $youtube_provider->provide($video_id);
            $redirect_url = $helper->getDownloadUrlByFormat($video_info, $_GET['format']);
            $type = $helper->getTypeByFormat($video_info, $_GET['format']);
            
            $original_media = $entry->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'group')->item(0);
            //echo $original_media->textContent;
            //$entry->removeChild($original_media);
            $original_content = $original_media->getElementsByTagNameNS('http://search.yahoo.com/mrss/','content')->item(0);
            
            
            $size = $this->getSize($redirect_url, $config, $toolkit);
            
            // an enclosure element must have the attributes: url, length and type
            $enclosure_url = $dom->createAttribute('url');
            $enclosure_url->appendChild($dom->createTextNode($redirect_url));
            $enclosure_length = $dom->createAttribute('length');
            $enclosure_length->appendChild($dom->createTextNode($size));
            $enclosure_type = $dom->createAttribute('type');
            $enclosure_type->appendChild($dom->createTextNode($type));
            $enclosure = $dom->createElementNS('http://search.yahoo.com/mrss/','content');
            $enclosure->appendChild($enclosure_url);
            $enclosure->appendChild($enclosure_length);
            $enclosure->appendChild($enclosure_type);
           $original_media->replaceChild($enclosure,$original_content);

        }
        header('Content-Type: text/xml; charset=utf-8', true);
        echo $dom->saveXML();
        
        exit;
	}
}
