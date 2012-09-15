<?php

/*

Plugin Name: Google+ Plus Wordpress Widget

Plugin URI: http://patrick.bloggles.info/2011/08/01/google-plus-wordpress/

Description: Display the <a href="http://plus.google.com/">Google+</a> latest updates from a Google+ user inside your theme's widgets.

Version: 5.0

Author: Patrick.

Author URI: http://patrickchia.com/

License: GPLv2

Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mypatricks@gmail.com&item_name=Donate%20to%20Patrick%20Chia&item_number=1242543308&amount=3.00&no_shipping=0&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8&return=http://patrick.bloggles.info/ 

*/



/*

This program is free software; you can redistribute it and/or

modify it under the terms of the GNU General Public License

as published by the Free Software Foundation; either version 2

of the License, or (at your option) any later version.



This program is distributed in the hope that it will be useful,

but WITHOUT ANY WARRANTY; without even the implied warranty of

MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

GNU General Public License for more details.



You should have received a copy of the GNU General Public License

along with this program; if not, write to the Free Software

Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/



if ( !function_exists('wpcom_time_since') ) :

/*

 * Time since function taken from WordPress.com

 */



function wpcom_time_since( $original, $do_more = 0 ) {

        // array of time period chunks

        $chunks = array(

                array(60 * 60 * 24 * 365 , 'year'),

                array(60 * 60 * 24 * 30 , 'month'),

                array(60 * 60 * 24 * 7, 'week'),

                array(60 * 60 * 24 , 'day'),

                array(60 * 60 , 'hour'),

                array(60 , 'minute'),

        );



        $today = time();

        $since = $today - $original;



        for ($i = 0, $j = count($chunks); $i < $j; $i++) {

                $seconds = $chunks[$i][0];

                $name = $chunks[$i][1];



                if (($count = floor($since / $seconds)) != 0)

                        break;

        }



        $print = ($count == 1) ? '1 '.$name : "$count {$name}s";



        if ($i + 1 < $j) {

                $seconds2 = $chunks[$i + 1][0];

                $name2 = $chunks[$i + 1][1];



                // add second item if it's greater than 0

                if ( (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) && $do_more )

                        $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";

        }

        return $print;

}

endif;



function gplus_style(){

?>

<style type="text/css">.widget_google_plus a{color:#000;}.author-box{padding:10px;}.widget_google_plus{-moz-box-shadow:inset 0 0 15px #ccc;-webkit-box-shadow:inset 0 0 15px #ccc;box-shadow:inset 0 0 15px #ccc;font-family:arial, hevetica;border:solid 1px #ccc;background-color:#fff;padding:0; text-align:center;margin-bottom:15px;}a.plus_person, a.plus_person:hover, a.plus_person:visited{color:#000; font-size:16px;font-weight:normal;height:52px;}img.plus{vertical-align:middle;}.updates{font-weight:normal;color:#777;text-align:left;padding:8px 5px 10px 5px;border-top:solid 1px #E5E5E5;margin:0px 10px;}.updates:hover{color:#000;}.timesince a{font-weight:normal;color:#000;}.credit{margin:0;padding:0;text-align:right;display:block;height:18px;}a.cb{border-left:solid 1px #ccc;font-size:12px;font-weight:normal;text-decoration:none;padding:3px 10px 3px 10px;color:#fff;background: #000;}a.cre:hover, a.c{-moz-border-radius-topleft:8px;border-top-left-radius: 8px;font-size:12px;font-weight:normal;text-decoration:none;padding:3px 10px 3px 10px;color:#fff;background: #000;}a.cre{-moz-border-radius-topleft:8px;border-top-left-radius: 8px;font-size:12px;font-weight:normal;text-decoration:none;padding:3px 10px 3px 10px;color:#fff;background:none;}</style>

<?php

}



class Google_Plus_Widget extends WP_Widget {



	function Google_Plus_Widget() {

		$widget_ops = array('classname' => 'widget_google_plus', 'description' => __( 'Display your post from Google+') );

		parent::WP_Widget('googleplus', __('Google+ Personal Badge'), $widget_ops);

	}



	function widget( $args, $instance ) {

		extract( $args );


		$key = trim( urlencode( $instance['key'] ) );

		if ( empty($key) )
			$key = "AIzaSyDWjWB13iuFnHmCnJPG2fs3VYkY9nY0Ug8";


		$account = trim( urlencode( $instance['account'] ) );

		if ( empty($account) ) return;

		$items = absint( $instance['show'] );

		if ( $items > 20 )

			$items = 20;



		$hideupdates = (bool) $instance['hideupdates'];

		$hidecredit = (bool) $instance['hidecredit'];

		$author = (bool) $instance['author'];



		//get google profile

		if ( !$gson = wp_cache_get( 'widget-gplus-' . $this->number , 'widget' ) ) {

			$google_json_url = esc_url_raw( 'https://www.googleapis.com/plus/v1/people/'. $account .'?pp=1&key='.$key );



			$response = wp_remote_get( $google_json_url, array( 'User-Agent' => get_bloginfo('name') ) );

			$response_code = wp_remote_retrieve_response_code( $response );



			if ( 200 == $response_code ) {

				$gson = wp_remote_retrieve_body( $response );

				$gson = json_decode( $gson, true );

				$expire = 900;

				if ( !is_array( $gson ) || isset( $gson['error'] ) ) {

					$gson = 'error';

					$expire = 300;

				}

			} else {

				$gson = 'error';

				$expire = 300;

				wp_cache_add( 'widget-gplus-response-code-' . $this->number, $response_code, 'widget', $expire);



			}



			wp_cache_add( 'widget-gplus-' . $this->number, $gson, 'widget', 86400); //cache for 24 hours



		}



		if ( 'error' != $gson ) :

			//foreach ( (array) $gson as $json ) {

				$g_title = esc_html($gson['displayName']);

				$g_photo = $gson['image']['url'];

				$g_photo = str_replace("sz=50", "sz=32", $g_photo );

				$g_slogan = $gson['tagline'];

			//}



		else :

			if ( 401 == wp_cache_get( 'widget-gplus-response-code-' . $this->number , 'widget' ) )

				echo '<!--' . esc_html( sprintf( __( 'Error: Please make sure the Google account is <a href="%s">public</a>.'), 'http://plus.google.com/' ) ) . '-->';

			else

				echo '<!--' . esc_html__('Error: Google Plus did not respond. Please wait a few minutes and refresh this page.') . '-->';



		endif;



		if ( $author ) {

		echo "<div class='widget_google_plus'><div class='author-box'><a rel='author' class='plus_person' target='_blank' title='Add to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "?rel=author'>+<strong>" . $g_title . "</strong></a> on    <a href='" . esc_url( "https://plus.google.com/{$account}" ) . "'

           onmouseover=\"document.plus_avatar.src='".$g_photo."'\"

           onmouseout=\"document.plus_avatar.src='https://ssl.gstatic.com/images/icons/gplus-32.png'\">

           <img title='" . $g_title . "' name ='plus_avatar' class='plus' src='https://ssl.gstatic.com/images/icons/gplus-32.png' alt='google' height='32' width='32' /></div>";

		} else {

		echo "<div class='widget_google_plus'><div class='author-box'><a class='plus_person' target='_blank' title='Add to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'><strong>" . $g_title . "</strong></a> on    <a href='" . esc_url( "https://plus.google.com/{$account}" ) . "'

           onmouseover=\"document.plus_avatar.src='".$g_photo."'\"

           onmouseout=\"document.plus_avatar.src='https://ssl.gstatic.com/images/icons/gplus-32.png'\">

           <img title='" . $g_title . "' name ='plus_avatar' class='plus' src='https://ssl.gstatic.com/images/icons/gplus-32.png' alt='google' height='32' width='32' /></div>";

		}



		if ( !$hideupdates ) {

			//get activities

			if ( !$activities_json = wp_cache_get( 'gplus-' . $this->number , 'widget' ) ) {



				$google_activities_url = esc_url_raw( 'https://www.googleapis.com/plus/v1/people/'. $account .'/activities/public?alt=json&maxResults='. $items .'&pp=1&key='.$key );



				$activities_response = wp_remote_get( $google_activities_url, array( 'User-Agent' => get_bloginfo('name') ) );

				$activities_response_code = wp_remote_retrieve_response_code( $activities_response );



				if ( 200 == $activities_response_code ) {

					$activities_json = wp_remote_retrieve_body( $activities_response );

					$activities_json = json_decode( $activities_json, true );

					$expire = 900;

					if ( !is_array( $activities_json ) || isset( $activities_json['error'] ) ) {

						$activities_json = 'error';

						$expire = 300;

					}

				} else {

					$activities_son = 'error';

					$expire = 300;

					wp_cache_add( 'gplus-response-code-' . $this->number, $activities_response_code, 'widget', $expire);



				}



				wp_cache_add( 'gplus-' . $this->number, $activities_json, 'widget', 86400); //cache for 24 hours



			}





			if ( 'error' != $activities_json ) {

					foreach ( (array) $activities_json[items] as $activities_items ) {

						$link = esc_url( strip_tags( $activities_items[url] ) );

						$title = esc_html( $activities_items[title] );

						$date = esc_html( strip_tags( $activities_items[published] ) );

						$date = strtotime( $date );

						$date = gmdate( get_option( 'date_format' ), $date );

						$time = wpcom_time_since( strtotime( $activities_items[published] ) );



						echo "\t<div class='updates'><a title='". $date ."' href='" . esc_url($link) . "' class='timesince'>" . $time . "&nbsp;ago</a> " . $title . "<br />". $date ."</div>\n";

					}



			} else {



				if ( 401 == wp_cache_get( 'gplus-response-code-' . $this->number , 'widget' ) )

					echo '<!--' . esc_html( sprintf( __( 'Error: Please make sure the Google account is <a href="%s">public</a>.'), 'http://plus.google.com/' ) ) . '-->';

				else

					echo '<!--' . esc_html__('Error: Google Plus did not respond. Please wait a few minutes and refresh this page.') . '-->';



			}





			if ( !$hidecredit ) {
				?>
					<div class="credit"><a class="c" onclick="document.getElementById('code-<?php echo $account; ?>').style.display='';return false;" href="">Scan Me</a><a class="cb" href="http://patrick.bloggles.info/">Google+ Widgets</a>
					</div>
				<?php
				//echo '<div class="credit"><a class="c" href="http://patrick.bloggles.info/">Google+ Widgets</a></div>';

			} else {

			?><div class="credit"><a class="c" onclick="document.getElementById('code-<?php echo $account; ?>').style.display='';return false;" href="">Scan Me</a></div><?php

			}





		}

		?><a onclick="document.getElementById('code-<?php echo $account; ?>').style.display='none';return false;" href=""><img style="display:none;margin:10px;width:90%" src="http://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=<?php echo esc_url( "http://plus.google.com/{$account}" ) ?>&chld=H|0" id="code-<?php echo $account; ?>" alt="Scan Me" title="Click to close" /></a><?php

		echo "</div>";

	}



	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['key'] = trim( strip_tags( stripslashes( $new_instance['key'] ) ) );

		$instance['account'] = trim( strip_tags( stripslashes( $new_instance['account'] ) ) );

		$instance['account'] = str_replace('https://plus.google.com/', '', $instance['account']);

		$instance['account'] = str_replace('/', '', $instance['account']);

		$instance['show'] = absint($new_instance['show']);

		$instance['hideupdates'] = isset($new_instance['hideupdates']);

		$instance['hidecredit'] = isset($new_instance['hidecredit']);

		$instance['author'] = isset($new_instance['author']);



		return $instance;

	}



	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, array('account' => '110166843324170581731', 'show' => 3) );



		$account = esc_attr($instance['account']);
		$key = esc_attr($instance['key']);

		$show = absint($instance['show']);

		if ( $show < 1 || 10 < $show )

			$show = 5;



		$hideupdates = (bool) $instance['hideupdates'];

		$hidecredit = (bool) $instance['hidecredit'];

		$author = (bool) $instance['author'];



		echo '<p><label for="' . $this->get_field_id('account') . '">' . esc_html__('Google+ ID:') . ' 

		<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />

		</label></p>

		<p><label for="' . $this->get_field_id('key') . '">' . esc_html__('Google API Key:') . ' 

		<input class="widefat" id="' . $this->get_field_id('key') . '" name="' . $this->get_field_name('key') . '" type="text" value="' . $key . '" />

		</label></p>

		<p><label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of updates to show:') . '

			<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';



		for ( $i = 1; $i <= 20; ++$i )

			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";



		echo '		</select>

		</label></p>



		<p><label for="' . $this->get_field_id('hideupdates') . '"><input id="' . $this->get_field_id('hideupdates') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hideupdates') . '"';

		if ( $hideupdates )

			echo ' checked="checked"';

		echo ' /> ' . esc_html__('Hide All Updates') . '</label></p>';



		echo '<p><label for="' . $this->get_field_id('author') . '"><input id="' . $this->get_field_id('author') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('author') . '"';

		if ( $author )

			echo ' checked="checked"';

		echo ' /> ' . esc_html__('Link to Google+ Profile') . '</label></p>';



		echo '<p><label for="' . $this->get_field_id('hidecredit') . '"><input id="' . $this->get_field_id('hidecredit') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hidecredit') . '"';

		if ( $hidecredit )

			echo ' checked="checked"';

		echo ' /> ' . esc_html__('Hide Credit/QR Code') . '</label></p>';



		echo '<p>Credit: <a href="http://patrick.bloggles.info/">Patrick Chia</a><br /><a href="https://plus.google.com/u/0/110166843324170581731">Add Patrick to your circles</a><br /><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mypatricks@gmail.com&item_name=Donate%20to%20Patrick%20Chia&item_number=1242543308&amount=3.00&no_shipping=0&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8&return=http://patrick.bloggles.info/ ">Donate $3</a></p>';

	}



	/**

	 * Link a Twitter user mentioned in the tweet text to the user's page on Twitter.

	 *

	 * @param array $matches regex match

	 * @return string Tweet text with inserted @user link

	 */

	function _wpcom_widget_twitter_username( $matches ) { // $matches has already been through wp_specialchars

		return "$matches[1]@<a href='" . esc_url( 'http://twitter.com/' . urlencode( $matches[3] ) ) . "'>$matches[3]</a>";

	}



	/**

	 * Link a Twitter hashtag with a search results page on Twitter.com

	 *

	 * @param array $matches regex match

	 * @return string Tweet text with inserted #hashtag link

	 */

	function _wpcom_widget_twitter_hashtag( $matches ) { // $matches has already been through wp_specialchars

		return "$matches[1]<a href='" . esc_url( 'http://twitter.com/search?q=%23' . urlencode( $matches[3] ) ) . "'>#$matches[3]</a>";

	}



}



add_action( 'widgets_init', 'wickett_google_widget_init' );

add_action( 'wp_head', 'gplus_style' );



function wickett_google_widget_init() {

	register_widget('Google_Plus_Widget');

}