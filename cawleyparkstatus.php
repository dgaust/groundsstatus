<?php
/**
 * Plugin Name: Wollongong Sportsground Status
 * Plugin URI: http://www.russellvalefootball.com
 * Description: Get current open/closed status of chosen Wollongong Sportsground
 * Version: 1.0
 * Author: dgaust
 * Author URI: http://www.russellvalefootball.com
 * License: GPL2
 */

/*  Copyright 2014  dgaust  (email : dgaust@outlook.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
     along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action("widgets_init", "init_ws_status");

function init_ws_status()
{
  register_sidebar_widget('Wollongong Sportsground Status', 'getGroundStatus');
  register_widget_control('Wollongong Sportsground Status', 'custom_widget_control');
}

function getGroundStatus($args)
{
   extract($args);
   $custom_widget_options = get_option('wsg_options');
   $parkname = $custom_widget_options['groundtitle'];
   $feedUrl = "http://russellvalefootball.com/test.json";
   $feedContent = "";
   $curl = curl_init();
   curl_setopt($curl, CURLOPT_URL, $feedUrl);
   curl_setopt($curl, CURLOPT_TIMEOUT, 3);
   curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_HEADER, false);

  // FeedBurner requires a proper USER-AGENT...
  curl_setopt($curl, CURL_HTTP_VERSION_1_1, true);
  curl_setopt($curl, CURLOPT_ENCODING, "gzip, deflate");
  curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3");
  $jsoncontent = curl_exec($curl);
  curl_close($curl);
  $groundstatus = json_decode($jsoncontent);
  foreach ($custom_widget as $item)
  {
    echo $item;
  }
  foreach ($groundstatus as $item)
  { 
       if($item->park_name == $parkname)
       {  
          if ($item->park_status == 'Open')
          {    
              echo '<div class="sp-widget-align-none"> <aside id="sportspress-countdown-4" class="widget widget_sportspress widget_countdown widget_sp_countdown"> <h3>';
              echo $custom_widget_options['groundname'];
              echo '</h3> <img style="width:100%; height:100%" src="https://russellvalefootball.com/wp-content/uploads/2016/04/Ground-1-2.png"/> <p class="widget-smalltext">Last Updated: ';
              echo $item->updated;
              echo '</p>  <a class="sp-view-all-link" href="http://www.wollongong.nsw.gov.au/facilities/sportrec/Pages/sportsgrounds.aspx" target="_blank">Other council grounds</a> </aside>	</div>';
          }
          else
          {
              echo '<div class="sp-widget-align-none"> <aside id="sportspress-countdown-4" class="widget widget_sportspress widget_countdown widget_sp_countdown"> <h3>';
              echo $custom_widget_options['groundname'];
              echo '</h3> <img style="width:100%; height:100%" src="https://russellvalefootball.com/wp-content/uploads/2016/04/Ground-2.png"/> <p class="widget-smalltext" color="black">Last Updated: ';
			  echo $item->updated;
			  echo '<br/>Council says: ';
			  echo $item->park_comment;
			  echo '</p> <a class="sp-view-all-link" href="http://www.wollongong.nsw.gov.au/facilities/sportrec/Pages/sportsgrounds.aspx" target="_blank">Other council grounds</a> </p> </aside> </div>';
          }
       }
  }
}

function custom_widget_control() 
{
  // Check if the option for this widget exists - if it doesnt, set some default values
  // and create the option.
  if(!get_option('wsg_options'))  
  {
    add_option('wsg_options', array('groundname'=>'Field Name (match exactly with WCC sportsgrounds)', 'groundtitle'=>'This the widget text'));
  }
  $custom_widget_options = $custom_widget_newoptions = get_option('wsg_options');
  
  // Check if new widget options have been posted from the form below - 
  // if they have, we'll update the option values.
  if ($_POST['custom_widget_title'])
  {
    $custom_widget_newoptions['groundname'] = $_POST['custom_widget_title'];
  }

  if ($_POST['custom_widget_text'])
  {
    $custom_widget_newoptions['groundtitle'] = $_POST['custom_widget_text'];
  }

  if($custom_widget_options != $custom_widget_newoptions)
  {
    $custom_widget_options = $custom_widget_newoptions;
    update_option('wsg_options', $custom_widget_options);
  }

  // Display html for widget form
   echo '<p> <label for="custom_widget_title">Display Name:<br /> <i>This will be the name shown on the site.</i><br/> <input id="custom_widget_title" name="custom_widget_title" type="text" value="'; 
   echo $custom_widget_options['groundname']; 
   echo '"/> </label> </p>';  
   echo '<p> <label for="custom_widget_text">Enter the <b>exact</b> name of the ground as provided on the Wollongong Sportsground <a href="http://www.wollongong.nsw.gov.au/facilities/sportrec/Pages/sportsgrounds.aspx" target="_blank">website.</a><br/> <i>The exact name must be used to ensure a match.</i><br/> <textarea rows=5 cols=25 id="custom_widget_text" name="custom_widget_text">'; 
   echo $custom_widget_options['groundtitle'];       
   echo '</textarea> </label> </p>';
} 
?>
