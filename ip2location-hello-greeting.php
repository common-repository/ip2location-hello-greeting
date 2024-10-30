<?php
/*
Plugin Name: IP2Location Hello Greeting
Plugin URI: https://ip2location.com/resources/wordpress-ip2location-hello-greeting
Description: Displays the Hello greeting message in visitor's native language based on visitor's origin country.
Version: 1.2.9
Author: IP2Location
Author URI: https://www.ip2location.com
*/

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
define('IP2LOCATION_HELLO_GREETING_ROOT', __DIR__ . DS);

require_once IP2LOCATION_HELLO_GREETING_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

class IP2LocationHelloGreeting extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'ip2locationhellogreeting',
			__('IP2Location Hello Greeting', 'text_domain'),
			['description' => __('IP2Location Hello Greeting', 'text_domain')]
		);
	}

	public function widget($args, $instance)
	{
		echo $args['before_widget'];
		if (!empty($instance['title'])) {
			echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
		}
		echo '<p>' . $this->get_greeting() . '</p>';
		echo $args['after_widget'];
	}

	public function form($instance)
	{
		$title = !empty($instance['title']) ? $instance['title'] : __('IP2Location Hello Greeting', 'text_domain'); ?>
		<p>
		<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e(esc_attr('Title:')); ?></label>
		<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>
		<?php
		echo '<a href="options-general.php?page=' . basename(__FILE__) . '">Go to Settings</a>';
	}

	public function update($new_instance, $old_instance)
	{
		$instance = [];
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

		return $instance;
	}

	public function init()
	{
		add_action('widgets_init', [&$this, 'register']);
		add_action('admin_menu', [&$this, 'admin_page']);
		add_filter('the_content', [&$this, 'parse_content']);
		add_action('admin_enqueue_scripts', [&$this, 'plugin_enqueues']);
		add_action('wp_ajax_ip2location_hello_greeting_submit_feedback', [&$this, 'submit_feedback']);
		add_action('admin_footer_text', [&$this, 'admin_footer_text']);
	}

	public function register()
	{
		register_widget('IP2LocationHelloGreeting');
	}

	public function admin_page()
	{
		add_options_page('IP2Location Hello Greeting', 'IP2Location Hello Greeting', 'edit_pages', 'ip2location-hello-greeting', [&$this, 'admin_options']);
	}

	public function admin_options()
	{
		$status = '';

		if (!is_admin()) {
			$this->write_debug_log('Not logged in as administrator. Settings page will not be shown.');

			return;
		}

		$enable_debug_log = (isset($_POST['submit']) && isset($_POST['enable_debug_log'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['enable_debug_log']))) ? 0 : get_option('ip2location_hello_greeting_debug_log_enabled'));

		if (isset($_POST['submit'])) {
			update_option('ip2location_hello_greeting_debug_log_enabled', $enable_debug_log);

			$status .= '
			<div id="message" class="updated">
				<p>Changes saved.</p>
			</div>';
		}

		echo '
		<div class="wrap">
			<h2>IP2Location Hello Greeting</h2>
			<p>
				IP2Location Hello Greeting plugin displays the <strong>Hello</strong> greeting message in visitor\'s native language based on visitor\'s origin country. This plugin will derive the country information from the visiting IP address using <a href="https://www.ip2location.com/?r=wordpress" target="_blank">IP2Location geolocation database</a>, and automatically select the right Hello message for display. This plugin support Chinese, English, Spanish, Arabic, Hindi, Russian, Bengali, Portuguese, Malay, French, German, Urdu, Japanese, Persian, Italian, Korean, Turkish, Vietnamese and Polish languages for display, and in case the translation is not available for a particular language, it will be substituted with English display.
			</p>

			<p>
				This plugin supports both the <strong>shortcode</strong> and <strong>widget</strong> display. In case of any missing language that you would like to see in this plugin, please feel free to feedback us at <a href="mailto:support@ip2location.com">support@ip2location.com</a>. We will be happy to include this into the plugin.
			</p>

			<p>&nbsp;</p>

			' . $status . '

			<div style="border-bottom:1px solid #ccc;">
				<h3>Local BIN Database</h3>
			</div>';

		if (!file_exists(IP2LOCATION_HELLO_GREETING_ROOT . get_option('ip2location_hello_greeting_database'))) {
			$this->write_debug_log('Unable to find the IP2Location BIN database.');
			echo '
						<div id="message" class="error">
							<p>
								Unable to find the IP2Location BIN database! Please download the database at <a href="https://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="https://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.
							</p>
						</div>';
		} else {
			// Create IP2Location object.
			$ipl = new \IP2Location\Database(IP2LOCATION_HELLO_GREETING_ROOT . get_option('ip2location_hello_greeting_database'), \IP2Location\Database::FILE_IO);
			$dbVersion = $ipl->getDatabaseVersion();
			$curdbVersion = str_replace('.', '-', $dbVersion);

			echo '
						<p>
							<b>Current Database Version: </b>
							' . $curdbVersion . '
						</p>';

			if (strtotime($curdbVersion) < strtotime('-2 months')) {
				echo '
							<div style="background:#fff;padding:2px 10px;border-left:3px solid #cc0000">
								<p>
									<strong>REMINDER</strong>: Your IP2Location database was outdated. We strongly recommend you to download the latest version for accurate result.
								</p>
							</div>';
			}
		}

		echo'
						<p>
							<b>Download BIN Database </b>
						</p>

						<div style="margin-top:20px;">
							Please follow the below procedures to manually update the database.
							<ol style="list-style-type:circle;margin-left:30px">
								<li>Download the BIN database at <a href="https://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="https://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.</li>
								<li>Decompress the zip file and update the BIN database to ' . __DIR__ . '.</li>
								<li>Once completed, please refresh the information by reloading the setting page.</li>
							</ol>
						</div>

			<p>&nbsp;</p>

			<div style="border-bottom:1px solid #ccc;">
				<h3>Usage</h3>
			</div>

			<p>
				The following shortcode is required to be embedded in any post, article or page. Then the image with translated Hello word will be displayed.
				<h4>Shortcode used to show translated Hello word image</h4>
				<pre> {ip:Hello} </pre>
			</p>

			<p>&nbsp;</p>

			<div style="border-bottom:1px solid #ccc;">
				<h3>Settings</h3>
			</div>

			<form id="hello-greeting-setting" method="post">
				<p>
					<label for="enable_debug_log">
						<input type="checkbox" name="enable_debug_log" id="enable_debug_log"' . (($enable_debug_log) ? ' checked' : '') . '>
						Enable debug log for development purpose.
					</label>
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
				</p>
			</form>

			<p>&nbsp;</p>

			<p>If you like this plugin, please leave us a <a href="https://wordpress.org/support/view/plugin-reviews/ip2location-hello-greeting">5 stars rating</a>. Thank You!</p>
		</div>';
	}

	public function parse_content($content)
	{
		$find = '{ip:Hello}';

		$replace = $this->get_greeting();

		$content = str_replace($find, $replace, $content);

		return $content;
	}

	public function get_greeting()
	{
		$ip_address = $_SERVER['REMOTE_ADDR'];

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		$result = $this->get_location($ip_address);
		$this->write_debug_log('Country [' . $result['countryCode'] . '] is used for Hello greeting display.');

		switch ($result['countryCode']) {
			case 'CN':
			case 'MO':
			case 'MN':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/zh.png', __FILE__) . '" ></a> ';
				break;

			case 'AR':
			case 'BO':
			case 'CL':
			case 'CO':
			case 'CR':
			case 'CU':
			case 'DO':
			case 'EC':
			case 'EH':
			case 'ES':
			case 'GI':
			case 'GQ':
			case 'GT':
			case 'HN':
			case 'MX':
			case 'NI':
			case 'PA':
			case 'PE':
			case 'PR':
			case 'PY':
			case 'SV':
			case 'UY':
			case 'VE':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/es.png', __FILE__) . '" ></a> ';
				break;

			case 'AE':
			case 'BH':
			case 'DZ':
			case 'EG':
			case 'IQ':
			case 'JO':
			case 'KM':
			case 'KW':
			case 'LB':
			case 'LY':
			case 'MA':
			case 'MR':
			case 'OM':
			case 'QA':
			case 'SA':
			case 'SD':
			case 'SO':
			case 'SY':
			case 'TN':
			case 'YE':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ar.png', __FILE__) . '" ></a> ';
				break;

			case 'IN':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/hi.png', __FILE__) . '" ></a> ';
				break;

			case 'KG':
			case 'KZ':
			case 'RU':
			case 'TM':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ru.png', __FILE__) . '" ></a> ';
				break;

			case 'BD':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/bn.png', __FILE__) . '" ></a> ';
				break;

			case 'AO':
			case 'BR':
			case 'CV':
			case 'GW':
			case 'MZ':
			case 'PT':
			case 'ST':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/pt.png', __FILE__) . '" ></a> ';
				break;

			case 'MY':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ms.png', __FILE__) . '" ></a> ';
				break;

			case 'BF':
			case 'BI':
			case 'BJ':
			case 'CF':
			case 'DJ':
			case 'FR':
			case 'GA':
			case 'GF':
			case 'GN':
			case 'GP':
			case 'JE':
			case 'LU':
			case 'MC':
			case 'MG':
			case 'ML':
			case 'MQ':
			case 'MU':
			case 'NC':
			case 'NE':
			case 'PF':
			case 'RW':
			case 'SN':
			case 'WF':
			case 'YT':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/fr.png', __FILE__) . '" ></a> ';
				break;

			case 'AT':
			case 'CH':
			case 'DE':
			case 'LI':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/de.png', __FILE__) . '" ></a> ';
				break;

			case 'PK':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ur.png', __FILE__) . '" ></a> ';
				break;

			case 'JP':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ja.png', __FILE__) . '" ></a> ';
				break;

			case 'AF':
			case 'IR':
			case 'TJ':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/fa.png', __FILE__) . '" ></a> ';
				break;

			case 'IT':
			case 'SM':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/it.png', __FILE__) . '" ></a> ';
				break;

			case 'KP':
			case 'KR':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/ko.png', __FILE__) . '" ></a> ';
				break;

			case 'TR':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/tr.png', __FILE__) . '" ></a> ';
				break;

			case 'VN':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/vi.png', __FILE__) . '" ></a> ';
				break;

			case 'PL':
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/pl.png', __FILE__) . '" ></a> ';
				break;

			default:
				$replace = '<a style="box-shadow: none;" href="https://www.iplocationtools.com/?r=wordpress" target="_blank"><img style="display: inline;" src="' . plugins_url('hello_top25/en.png', __FILE__) . '" ></a> ';
				break;
		}

		return $replace;
	}

	public function get_location($ip)
	{
		switch (get_option('ip2location_hello_greeting_lookup_mode')) {
			case 'bin':
				if (!is_file(IP2LOCATION_HELLO_GREETING_ROOT . get_option('ip2location_hello_greeting_database'))) {
					return false;
				}

				$geo = new \IP2Location\Database(IP2LOCATION_HELLO_GREETING_ROOT . get_option('ip2location_hello_greeting_database'), \IP2Location\Database::FILE_IO);

				$response = $geo->lookup($ip, \IP2Location\Database::ALL);

				return [
					'ipAddress'   => $ip,
					'countryCode' => $response['countryCode'],
					'countryName' => $response['countryName'],
					/*'regionName' => $response['regionName'],
					'cityName' => $response['cityName'],
					'latitude' => $response['latitude'],
					'longitude' => $response['longitude'],
					'isp'=> $response['isp'],
					'domainName' => $response['domainName'],
					'zipCode' => $response['zipCode'],
					'timeZone' => $response['timeZone'],
					'netSpeed' => $response['netSpeed'],
					'iddCode' => $response['iddCode'],
					'areaCode' => $response['areaCode'],
					'weatherStationCode' => $response['weatherStationCode'],
					'weatherStationName' =>$response['weatherStationName'],
					'mcc' => $response['mcc'],
					'mnc' => $response['mnc'],
					'mobileCarrierName' => $response['mobileCarrierName'],
					'elevation' => $response['elevation'],
					'usageType' => $response['usageType'],*/
				];
			break;
		}
	}

	public function write_debug_log($message)
	{
		if (!get_option('ip2location_hello_greeting_debug_log_enabled')) {
			return;
		}

		file_put_contents(IP2LOCATION_HELLO_GREETING_ROOT . 'debug.log', gmdate('Y-m-d H:i:s') . "\t" . $message . "\n", FILE_APPEND);
	}

	public function set_defaults()
	{
		if (get_option('ip2location_hello_greeting_lookup_mode') !== false) {
			return;
		}

		update_option('ip2location_hello_greeting_lookup_mode', 'bin');
		update_option('ip2location_hello_greeting_database', '');
		update_option('ip2location_hello_greeting_debug_log_enabled', 0);

		// Find any .BIN files in current directory
		$files = scandir(IP2LOCATION_HELLO_GREETING_ROOT);

		foreach ($files as $file) {
			if (strtoupper(substr($file, -4)) == '.BIN') {
				update_option('ip2location_hello_greeting_database', $file);
				break;
			}
		}
	}

	public function plugin_enqueues($hook)
	{
		if ($hook == 'plugins.php') {
			// Add in required libraries for feedback modal
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_style('wp-jquery-ui-dialog');

			wp_enqueue_script('ip2location_hello_greeting_admin_script', plugins_url('/assets/js/feedback.js', __FILE__), ['jquery'], null, true);
		}
	}

	public function admin_footer_text($footer_text)
	{
		$plugin_name = substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.'));
		$current_screen = get_current_screen();

		if (($current_screen && strpos($current_screen->id, $plugin_name) !== false)) {
			$footer_text .= sprintf(
				__('Enjoyed %1$s? Please leave us a %2$s rating. A huge thanks in advance!', $plugin_name),
				'<strong>' . __('IP2Location Hello Greeting', $plugin_name) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/' . $plugin_name . '/reviews/?filter=5/#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}

		if ($current_screen->id == 'plugins') {
			return $footer_text . '
			<div id="ip2location-hello-greeting-feedback-modal" class="hidden" style="max-width:800px">
				<span id="ip2location-hello-greeting-feedback-response"></span>
				<p>
					<strong>Would you mind sharing with us the reason to deactivate the plugin?</strong>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-hello-greeting-feedback" value="1"> I no longer need the plugin
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-hello-greeting-feedback" value="2"> I couldn\'t get the plugin to work
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-hello-greeting-feedback" value="3"> The plugin doesn\'t meet my requirements
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-hello-greeting-feedback" value="4"> Other concerns
						<br><br>
						<textarea id="ip2location-hello-greeting-feedback-other" style="display:none;width:100%"></textarea>
					</label>
				</p>
				<p>
					<div style="float:left">
						<input type="button" id="ip2location-hello-greeting-submit-feedback-button" class="button button-danger" value="Submit & Deactivate" />
					</div>
					<div style="float:right">
						<a href="#">Skip & Deactivate</a>
					</div>
				</p>
			</div>';
		}

		return $footer_text;
	}

	public function submit_feedback()
	{
		$feedback = (isset($_POST['feedback'])) ? sanitize_text_field($_POST['feedback']) : '';
		$others = (isset($_POST['others'])) ? sanitize_text_field($_POST['others']) : '';

		$options = [
			1 => 'I no longer need the plugin',
			2 => 'I couldn\'t get the plugin to work',
			3 => 'The plugin doesn\'t meet my requirements',
			4 => 'Other concerns' . (($others) ? (' - ' . $others) : ''),
		];

		if (isset($options[$feedback])) {
			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();
			$response = $request->request('https://www.ip2location.com/wp-plugin-feedback?' . http_build_query([
				'name'    => 'ip2location-hello-greeting',
				'message' => $options[$feedback],
			]), ['timeout' => 5]);
		}
	}
}

$ip2location_hello_greeting = new IP2LocationHelloGreeting();
$ip2location_hello_greeting->init();

register_activation_hook(__FILE__, [$ip2location_hello_greeting, 'set_defaults']);

?>
