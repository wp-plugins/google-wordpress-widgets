<?php
/*
Plugin Name: Google+ Plus Wordpress Widget
Plugin URI: http://patrick.bloggles.info/2011/08/01/google-plus-wordpress/
Description: Display the <a href="http://plus.google.com/">Google+</a> latest updates from a Google+ user inside your theme's widgets.
Version: 3.0
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
<style type="text/css">.widget_google_plus{border:none;background-color:#F2F2F2;-moz-border-radius:5px;-khtml-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;padding:1px;}.plus .updates{background-color:#fff;margin:1px;padding:6px 11px 6px 11px;border:none;}.plus .updates:hover{-moz-box-shadow:inset 0 0 8px #ccc;-webkit-box-shadow:inset 0 0 8px #ccc;box-shadow:inset 0 0 8px #ccc;}.widget_google_plus .widget-title,.widget_google_plus .widgettitle{background-color:#F2F2F2;height:48px;padding:9px 12px 8px 12px;margin-bottom:1px;border:none;}img.plus{border:none;float:left;margin-right:10px;-moz-border-radius:5px;-khtml-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;-webkit-box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);-moz-box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);}img.plus:hover{margin-right:6px;-moz-border-radius:40px;-khtml-border-radius:40px;-webkit-border-radius:40px;border-radius:40px;border:solid 2px #00CD00;}span.note {display:block;color:#ccc;}span.credit{text-align:right;margin-top:5px;margin-bottom:8px;margin-right:5px;background-color:#F2F2F2;display:block;}a.circles {display:block;-moz-box-shadow:inset 0px 1px 0px 0px #d9fbbe;-webkit-box-shadow:inset 0px 1px 0px 0px #d9fbbe;box-shadow:inset 0px 1px 0px 0px #d9fbbe;background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #b8e356), color-stop(1, #a5cc52) );background:-moz-linear-gradient( center top, #b8e356 5%, #a5cc52 100% );filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#b8e356', endColorstr='#a5cc52');background-color:#b8e356;-moz-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;border:1px solid #83c41a;display:inline-block;color:#ffffff;font-family:Verdana;font-size:10px;font-weight:bold;padding:2px 6px;text-decoration:none;text-shadow:-1px 1px 0px #86ae47;}a.circles:hover {background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #a5cc52), color-stop(1, #b8e356) );background:-moz-linear-gradient( center top, #a5cc52 5%, #b8e356 100% );filter:progid:DXImageTransform.Microsoft.gradient(startColorstr="#a5cc52", endColorstr="#b8e356");background-color:#a5cc52;}a.circles:active {position:relative;top:1px;}</style>
<?php
}

class Google_Plus_Widget extends WP_Widget {

	function Google_Plus_Widget() {
		$widget_ops = array('classname' => 'widget_google_plus', 'description' => __( 'Display your post from Google+') );
		parent::WP_Widget('googleplus', __('Google+'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$account = trim( urlencode( $instance['account'] ) );
		if ( empty($account) ) return;
		$items = absint( $instance['show'] );
		if ( $items > 10 )
			$items = 10;

		$hideupdates = (bool) $instance['hideupdates'];
		$include_buzz = (bool) $instance['includebuzz'];
		$hidecredit = (bool) $instance['hidecredit'];
		$hidework = (bool) $instance['hidework'];

		$url = "http://plusfeed.appspot.com/". $account;
		$buzzurl = 'http://buzz.googleapis.com/feeds/'. $account .'/public/posted';

		//get google profile
		if ( !$gson = wp_cache_get( 'widget-gplus-' . $this->number , 'widget' ) ) {
			$google_json_url = esc_url_raw( 'https://www.googleapis.com/buzz/v1/people/'. $account .'/@self?alt=json' );
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
			foreach ( (array) $gson as $json ) {
				$g_title = $json['displayName'];
				$g_photo = $json['thumbnailUrl'] .'?sz=40';
				$g_slogan = $json['organizations']['0']['name'];
			}

		else :
			if ( 401 == wp_cache_get( 'widget-gplus-response-code-' . $this->number , 'widget' ) )
				echo '<!--' . esc_html( sprintf( __( 'Error: Please make sure the Google account is <a href="%s">public</a>.'), 'http://plus.google.com/' ) ) . '-->';
			else
				echo '<!--' . esc_html__('Error: Google Plus did not respond. Please wait a few minutes and refresh this page.') . '-->';

		endif;


		include_once(ABSPATH . WPINC . '/rss.php');
		$rss = fetch_feed( $url );

		if ( is_wp_error($rss) ) {
			if ( is_admin() || current_user_can('manage_options') ) {
				echo '<p>';
				printf(__('<strong>RSS Error</strong>: %s'), $rss->get_error_message());
				echo '</p>';
			}
			return;
		}

		if ( !$rss->get_item_quantity() ) {
			echo '<p>' . esc_html__('Error: Google+ did not respond. Please wait a few minutes and refresh this page.') . '</p>';
			$rss->__destruct();
			unset($rss);
			return;
		}

		if ( $g_title ) {
			echo "{$before_widget}{$before_title}<a title='Add " . esc_html("+".$g_title) . " to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'><img title='" . esc_html("+".$g_title) . "' class='plus' src='". $g_photo ."' alt='google' height='40' width='40' /> " . esc_html("+".$g_title) . "</a>";

			if ( !$hidework ) {
				echo "<span class='note'>". $g_slogan ."</span>";
			} else {
				echo "<span class='note'><a class='circles' title='Add " . esc_html("+".$g_title) . " to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'>Add To Circles</a></span>";
			}

			echo "{$after_title}";

		} else {
			$author = $rss->get_author();
			if ( $author->get_name() ) $title = $author->get_name();
				echo "{$before_widget}{$before_title}<a title='Add " . esc_html("+".$title) . " to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'><img title='" . esc_html("+".$title) . "' class='plus' src='http://t2.gstatic.com/images?q=tbn:ANd9GcQ9TgoKZN1g2u87Oey4aZSahAT8zHUp9eP7FHtED-eiBcIDX3QQrU8d' alt='google' height='40' width='40' /> " . esc_html("+".$title) . "</a>{$after_title}";
		}

		echo '<div class="plus">' . "\n";

		if ( !isset($items) )
			$items = 10;

		if ( !$hideupdates ) {
			foreach ( $rss->get_items(0, $items) as $item ) {
				$link = esc_url( strip_tags( $item->get_link() ) );
				$title = $item->get_title();
				//$description = esc_html( strip_tags(@html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'))) );
				$description = $item->get_description();
				$content = $item->get_content();
				$content = wp_html_excerpt($content, 140) . ' ...';
				$date = esc_html( strip_tags( $item->get_date() ) );
				$date = strtotime( $date );
				$date = gmdate( get_option( 'date_format' ), $date );
				$time = wpcom_time_since(strtotime($item->get_date()));

				echo "\t<div class='updates'><a title='". $date ."' href='" . esc_url($link) . "' class='timesince'>" . $time . "&nbsp;ago</a> " . $title . "</div>\n";
			}

			if ( $include_buzz ) {
				$buzz = fetch_feed( $buzzurl );

				if ( !is_wp_error($buzz) ) {
					foreach ( $buzz->get_items(0, $items) as $item ) {
						$link = esc_url( strip_tags( $item->get_link() ) );
						$title = $item->get_title();
						$description = $item->get_description();
						$description = make_clickable( esc_html( $description ) );
						$description = preg_replace_callback('/(^|[^0-9A-Z&\/]+)(#|\xef\xbc\x83)([0-9A-Z_]*[A-Z_]+[a-z0-9_\xc0-\xd6\xd8-\xf6\xf8\xff]*)/iu',  array($this, '_wpcom_widget_twitter_hashtag'), $description);
						$description = preg_replace_callback('/([^a-zA-Z0-9_]|^)([@\xef\xbc\xa0]+)([a-zA-Z0-9_]{1,20})(\/[a-zA-Z][a-zA-Z0-9\x80-\xff-]{0,79})?/u', array($this, '_wpcom_widget_twitter_username'), $description);

						$date = esc_html( strip_tags( $item->get_date() ) );
						$date = strtotime( $date );
						$date = gmdate( get_option( 'date_format' ), $date );
						$time = wpcom_time_since(strtotime($item->get_date()));

						echo "\t<div class='updates'><a title='". $date ."' href='" . esc_url($link) . "' class='timesince'>" . $time . "&nbsp;ago</a> " . $description . " (Buzz)</div>\n";
					}
				}

				$buzz->__destruct();
				unset($buzz);

			}
		}

		if ( ! $hidecredit )
			echo "<span class='credit'><a title='Get Google+ Plus Wordpress Widgets' href='http://patrick.bloggles.info/2011/08/01/google-plus-wordpress/'><img src='http://t3.gstatic.com/images?q=tbn:ANd9GcQd70bdXUawL95y1Rb2eIIJKTUWwgw1llRFEtIEh6bqN44i0A8rcat4Aw'></a></span><!--Powered by Patrick/Google+ Plus Wordpress Widgets-->";

		echo "</div>\n";
		$rss->__destruct();
		unset($rss);

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['account'] = trim( strip_tags( stripslashes( $new_instance['account'] ) ) );
		$instance['account'] = str_replace('https://plus.google.com/', '', $instance['account']);
		$instance['account'] = str_replace('/', '', $instance['account']);
		$instance['show'] = absint($new_instance['show']);
		$instance['includebuzz'] = isset($new_instance['includebuzz']);
		$instance['hidecredit'] = isset($new_instance['hidecredit']);
		$instance['hidework'] = isset($new_instance['hidework']);
		$instance['hideupdates'] = isset($new_instance['hideupdates']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array('account' => '110166843324170581731', 'show' => 3) );

		$account = esc_attr($instance['account']);
		$show = absint($instance['show']);
		if ( $show < 1 || 10 < $show )
			$show = 5;
		$include_buzz = (bool) $instance['includebuzz'];
		$hidecredit = (bool) $instance['hidecredit'];
		$hidework = (bool) $instance['hidework'];
		$hideupdates = (bool) $instance['hideupdates'];

		echo '<p><label for="' . $this->get_field_id('account') . '">' . esc_html__('Account ID#:') . ' 
		<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of updates to show:') . '
			<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';

		for ( $i = 1; $i <= 10; ++$i )
			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '		</select>
		</label></p>

		<p><label for="' . $this->get_field_id('hideupdates') . '"><input id="' . $this->get_field_id('hideupdates') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hideupdates') . '"';
		if ( $hideupdates )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide All Updates') . '</label></p>';

		echo '<p><label for="' . $this->get_field_id('includebuzz') . '"><input id="' . $this->get_field_id('includebuzz') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('includebuzz') . '"';
		if ( $include_buzz )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Include Buzz') . '</label></p>';

		echo '<p><label for="' . $this->get_field_id('hidework') . '"><input id="' . $this->get_field_id('hidework') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hidework') . '"';
		if ( $hidework )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide Work Info') . '</label></p>';

		echo '<p><label for="' . $this->get_field_id('hidecredit') . '"><input id="' . $this->get_field_id('hidecredit') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hidecredit') . '"';
		if ( $hidecredit )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide Credit Icon') . '</label></p>';


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