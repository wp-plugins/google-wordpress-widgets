<?php
/*
Plugin Name: Google+ Plus Wordpress Widget
Plugin URI: http://patrick.bloggles.info/2011/08/01/google-plus-wordpress/
Description: Display the <a href="http://plus.google.com/">Google+</a> latest updates from a Google+ user inside your theme's widgets.
Version: 1.5
Author: Patrick.
Author URI: http://patrickchia.com/
License: GPLv2
Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mypatricks@gmail.com&item_name=Donate%20to%20Patrick%20Chia&item_number=1242543308&amount=15.00&no_shipping=0&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8&return=http://patrick.bloggles.info/ 
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
	echo '<style type="text/css">.widget_google_plus h2{margin-bottom:10px;}.widget_google_plus img{float:left;margin-right:6px;border:none;-moz-border-radius:5px;-khtml-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;}span.note {display:block;color:#ccc;}</style>';
}

class Google_Plus_Widget extends WP_Widget {

	function Google_Plus_Widget() {
		$widget_ops = array('classname' => 'widget_google_plus', 'description' => __( 'Display your post from Google+') );
		parent::WP_Widget('googleplus', __('Google+Buzz'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$account = trim( urlencode( $instance['account'] ) );
		if ( empty($account) ) return;
		$items = absint( $instance['show'] );
		if ( $items > 10 )
			$items = 10;
		$include_buzz = (bool) $instance['includebuzz'];

		$url = "http://plusfeed.appspot.com/". $account;
		$buzzurl = 'http://buzz.googleapis.com/feeds/'. $account .'/public/posted';
		//get google profile
		$google_json_url = esc_url_raw( 'https://www.googleapis.com/buzz/v1/people/'. $account .'/@self?alt=json');
		$response = wp_remote_get( $google_json_url, array( 'User-Agent' => 'Blogates Google+ Widget' ) );
		$gson = json_decode($response['body']);

		if( $gson || $gson->data || $gson->data->results ) {
			$g_title = $gson->data->displayName;
			$g_photo = $gson->data->thumbnailUrl .'?sz=60';
			$g_slogan = $gson->data->organizations['0']->name;
		}

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
			echo "{$before_widget}{$before_title}<img src='". $g_photo ."' alt='google' height='60' width='60' /> <a title='Add to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'>" . esc_html("+".$g_title) . "</a><span class='note'>".$g_slogan."</span>{$after_title}";

		} else {
			$author = $rss->get_author();
			if ( $author->get_name() ) $title = $author->get_name();
			echo "{$before_widget}{$before_title}<img style='border: 0pt none;' src='http://t1.gstatic.com/images?q=tbn:ANd9GcTLmIJS_5RdLRyywWwer6bcxwuwIbMCbrOsXO_g2kK5rmSk8cGM' alt='google' height='14' width='14' /> <a title='Add to circles' href='" . esc_url( "https://plus.google.com/{$account}" ) . "'>" . esc_html("+".$title) . "</a>{$after_title}";
		}

		echo '<ul class="plus">' . "\n";

		if ( !isset($items) )
			$items = 10;

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

			echo "\t<li><a title='". $date ."' href='" . esc_url($link) . "' class='timesince'>" . $time . "&nbsp;ago</a> " . $title . "</li>\n";
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

						echo "\t<li><a title='". $date ."' href='" . esc_url($link) . "' class='timesince'>" . $time . "&nbsp;ago</a> " . $description . " (Buzz)</li>\n";
					}
				}

				$buzz->__destruct();
				unset($buzz);

		}

		echo "</ul>\n";
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

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('account' => '', 'show' => 5) );

		$account = esc_attr($instance['account']);
		//$title = esc_attr($instance['title']);
		$show = absint($instance['show']);
		if ( $show < 1 || 10 < $show )
			$show = 5;
		$include_buzz = (bool) $instance['includebuzz'];

		echo '<p><label for="' . $this->get_field_id('account') . '">' . esc_html__('Account ID#:') . ' 
		<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of updates to show:') . '
			<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';

		for ( $i = 1; $i <= 10; ++$i )
			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '		</select>
		</label></p>

		<p><label for="' . $this->get_field_id('includebuzz') . '"><input id="' . $this->get_field_id('includebuzz') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('includebuzz') . '"';
		if ( $include_buzz )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Include Buzz') . '</label></p>';

		echo '<p>Credit: <a href="http://patrick.bloggles.info/">Patrick Chia</a><br /><a href="https://plus.google.com/u/0/110166843324170581731">Add Patrick to your circles</a></p>';
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