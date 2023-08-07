<?php
/*
Plugin Name: OpenWeatherMap Widget
Description: Displays real-time weather for any ZIP code. Customizable details include temperature, wind, pressure, and more.
Version: 1.0
Author: Tom Kimberlin
Author URI: https://kimberlin.net
*/

/**
 * Registers the settings for the OpenWeatherMap widget, 
 * creating a new option for each setting if it does not already exist.
 */
function weather_widget_register_settings()
{
  $options = [
    'api_key' => '',
    'country_code' => 'us',
    'zipcode' => '',
    'units' => 'imperial',
    'round_data' => 'on',
    'show_city' => 'on',
    'temp' => 'on',
    'feels_like' => 'on',
    'summary' => 'on',
    'desc' => '',
    'humidity' => '',
    'wind_speed' => '',
    'pressure' => '',
    'visibility' => '',
    'style' => 'default',
    'rounded_corners' => 'off'
  ];

  foreach ($options as $name => $default) {
    $option_name = "weather_widget_option_$name";
    add_option($option_name, sanitize_text_field($default));
    register_setting('weather_widget_options_group', $option_name);
    sync_settings_to_widget($option_name, $default);
  }

  // Sync widget settings when plugin settings are saved
  add_action('updated_option', 'sync_settings_to_widget', 10, 3);
  add_action('add_option', 'sync_settings_to_widget', 10, 2);
}

add_action('admin_init', 'weather_widget_register_settings');

function sync_settings_to_widget($option_name, $old_value, $value = null)
{
  if (strpos($option_name, 'weather_widget_option_') === 0) {
    $option_value = get_option($option_name);
    $value = $option_value !== false ? $option_value : $old_value;

    $widgets = get_option('widget_OpenWeatherMap_Widget');
    if ($widgets) {
      foreach ($widgets as $index => $widget) {
        if (is_array($widget)) {
          $widgets[$index][str_replace('weather_widget_option_', '', $option_name)] = $value;
        }
      }
      update_option('widget_OpenWeatherMap_Widget', $widgets);
    }
  }
}

/**
 * Adds the OpenWeatherMap Widget settings page to the WordPress admin menu.
 */
function weather_widget_register_options_page()
{
  add_options_page('OpenWeatherMap Widget', 'OpenWeatherMap Widget', 'manage_options', 'weatherwidget', 'weather_widget_options_page');
}

add_action('admin_menu', 'weather_widget_register_options_page');

/**
 * Renders the OpenWeatherMap Widget settings page,
 * providing form fields for all the settings and allowing the user to update them.
 */
function weather_widget_options_page()
{
  $options = [
    'general' => [
      'title' => 'General Settings',
      'api_key' => ['API Key', 'text', '', 'You can register for a free API key on <a href="http://openweathermap.org/appid" target="_blank">OpenWeatherMap\'s website</a>.'],
      'country_code' => ['Country Code', 'text', 'us', 'The <a href="https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes">2-letter country code</a>.'],
      'zipcode' => ['ZIP Code', 'text', ''],
      'units' => ['Units of Measurement', 'select', [
        'standard' => 'Standard',
        'metric' => 'Metric',
        'imperial' => 'Imperial',
      ]],
      'round_data' => ['Round Weather Data', 'checkbox', 'off']
    ],
    'weather' => [
      'title' => 'Weather Options',
      'show_city' => ['Show City', 'checkbox', 'on'],
      'temp' => ['Temperature', 'checkbox', 'on'],
      'feels_like' => ['Feels Like', 'checkbox', 'on'],
      'summary' => ['Summary', 'checkbox', 'on'],
      'desc' => ['Description', 'checkbox', 'off'],
      'humidity' => ['Humidity', 'checkbox', 'off'],
      'wind_speed' => ['Wind Speed', 'checkbox', 'off'],
      'pressure' => ['Pressure', 'checkbox', 'off'],
      'visibility' => ['Visibility', 'checkbox', 'off'],
    ],
    'style' => [
      'title' => 'Style Settings',
      'style' => ['Style', 'select', array_combine(fetch_css_files(), fetch_css_files())],
      'rounded_corners' => ['Rounded Corners', 'checkbox', 'off'],
    ]
  ];

?>
  <div>
    <h2>OpenWeatherMap Widget Settings</h2>
    <form method="post" action="options.php">
      <?php settings_fields('weather_widget_options_group'); ?>
      <?php do_settings_sections('weather_widget_options_group'); ?>

      <?php foreach ($options as $group => $groupData) : ?>
        <h3><?php echo $groupData['title']; ?></h3>
        <?php foreach ($groupData as $option => $value) : ?>
          <?php if ($option === 'title') continue; ?>
          <?php
          $label = $value[0];
          $type = $value[1];
          $default = $value[2];
          $note = isset($value[3]) ? $value[3] : '';
          ?>
          <p>
            <label for="weather_widget_option_<?php echo $option; ?>"><?php echo $label; ?></label>
            <?php if ($type === 'text') : ?>
              <input type="text" id="weather_widget_option_<?php echo $option; ?>" name="weather_widget_option_<?php echo $option; ?>" value="<?php echo get_option("weather_widget_option_$option", $default); ?>" />
            <?php elseif ($type === 'checkbox') : ?>
              <input type="checkbox" id="weather_widget_option_<?php echo $option; ?>" name="weather_widget_option_<?php echo $option; ?>" <?php checked(get_option("weather_widget_option_$option", $default), 'on'); ?> />
            <?php elseif ($type === 'select') : ?>
              <select id="weather_widget_option_<?php echo $option; ?>" name="weather_widget_option_<?php echo $option; ?>">
                <?php foreach ($value[2] as $optValue => $optLabel) : ?>
                  <option value="<?php echo $optValue; ?>" <?php selected(get_option("weather_widget_option_$option", $default), $optValue); ?>><?php echo $optLabel; ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
            <?php if ($note) : ?>
              <small><?php echo $note; ?></small>
            <?php endif; ?>
          </p>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <?php
      // Check if API Key and ZIP code are not empty
      $api_key = get_option('weather_widget_option_api_key');
      $zipcode = get_option('weather_widget_option_zipcode');
      if (empty($api_key) || empty($zipcode)) {
        echo '<p style="color:red;">API Key and a ZIP code are required for the widget to function correctly.</p>';
      }

      ?>

      <?php submit_button(); ?>
    </form>
  </div>
  <?php
}

class OpenWeatherMap_Widget extends WP_Widget
{
  function __construct()
  {
    parent::__construct(
      'OpenWeatherMap_Widget',
      esc_html__('OpenWeatherMap Widget', 'text_domain'),
      array('description' => esc_html__('A widget to display the weather of a specified zip code', 'text_domain'),)
    );
  }

  /**
   * Displays the OpenWeatherMap Widget on the front end of the site. 
   * Retrieves the weather data from the OpenWeatherMap API and renders it in the widget.
   */
  public function widget($args, $instance)
  {
    $api_key = get_option('weather_widget_option_api_key');
    $country_code = get_option('weather_widget_option_country_code');
    $zipcode = get_option('weather_widget_option_zipcode');
    $units = get_option('weather_widget_option_units');

    if (empty($api_key)) {
      echo $args['before_widget'];
      echo $args['before_title'] . 'Weather' . $args['after_title'];
      echo "<p>Please provide your OpenWeatherMap API key in the plugin settings.</p>";
      echo $args['after_widget'];
      return;
    }

    if (empty($zipcode)) {
      echo $args['before_widget'];
      echo $args['before_title'] . 'Weather' . $args['after_title'];
      echo "<p>Please provide a ZIP code in the widget settings or the plugin settings.</p>";
      echo $args['after_widget'];
      return;
    }

    $api_url = "http://api.openweathermap.org/data/2.5/weather?zip={$zipcode},{$country_code}&units={$units}&appid={$api_key}";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
      echo $args['before_widget'];
      echo $args['before_title'] . 'Weather' . $args['after_title'];
      echo "<p>An error occurred while retrieving the weather information. Please check your settings.</p>";
      echo $args['after_widget'];
      return;
    }

    if (wp_remote_retrieve_response_code($response) != 200) {
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body);

      echo $args['before_widget'];
      echo $args['before_title'] . 'Weather' . $args['after_title'];

      if ($data->cod == 401) {
        echo "<p>Your API Key is not valid. Please check it in the settings.</p>";
      } else if ($data->cod == 404) {
        echo "<p>The ZIP Code is not valid. Please check it in the settings.</p>";
      } else {
        echo "<p>An unknown error occurred. Please check your settings.</p>";
      }

      echo $args['after_widget'];
      return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (!empty($data)) {
      $city_name = $data->name;

      // Adjust weather data rendering based on units
      $temp_unit = $units === 'metric' ? '°C' : ($units === 'standard' ? 'K' : '°F');
      $wind_speed_unit = $units === 'metric' ? 'meter/sec' : 'miles/hour';
      $pressure_unit = $units === 'imperial' ? 'inHg' : 'hPa';
      $visibility_unit = $units === 'imperial' ? 'miles' : 'meters';

      $should_round = get_option('weather_widget_option_round_data') === 'on';


      $weather_data = [
        'temp' => ['Temperature', ($should_round ? round($data->main->temp) : $data->main->temp) . $temp_unit],
        'feels_like' => ['Feels Like', ($should_round ? round($data->main->feels_like) : $data->main->feels_like) . $temp_unit],
        'summary' => ['Summary', $data->weather[0]->main],
        'desc' => ['Description', ucwords(strtolower($data->weather[0]->description))],
        'humidity' => ['Humidity', "{$data->main->humidity}%"],
        'wind_speed' => ['Wind Speed', ($should_round ? round($data->wind->speed) : $data->wind->speed) . " $wind_speed_unit"],
        'pressure' => ['Pressure', number_format($data->main->pressure) . " $pressure_unit"],
        'visibility' => ['Visibility', number_format($data->visibility) . " $visibility_unit"]
      ];

      echo $args['before_widget'];
      echo $args['before_title'] . 'Weather' . $args['after_title'];

      $style = '';
      if (get_option('weather_widget_option_rounded_corners') === 'on') {
        $style = ' style="border-radius: 10px;"';
      }

      echo "<div class='weather-widget-content'$style>";

      $icon_id = $data->weather[0]->icon;
      $icon_url = "http://openweathermap.org/img/w/{$icon_id}.png";
      echo "<img class='weather-icon' src='{$icon_url}' alt='Weather icon' />";

      if (get_option('weather_widget_option_show_city') === 'on') {
        echo "<p class='weather-city'><strong>City:</strong> {$city_name}</p>";
      }

      foreach ($weather_data as $key => $info) {
        if (get_option("weather_widget_option_$key") === 'on') {
          echo "<p class='weather-data'><strong>{$info[0]}:</strong> {$info[1]}</p>";
        }
      }

      echo "</div>";
      echo $args['after_widget'];
    }
  }

  /**
   * Renders the form on the widget settings page in the WordPress admin area. 
   * This allows the user to set a custom ZIP code for the widget.
   */
  public function form($instance)
  {
    $zipcode = !empty($instance['zipcode']) ? $instance['zipcode'] : esc_html__('', 'text_domain');
    $country_code = !empty($instance['country_code']) ? $instance['country_code'] : esc_html__('', 'text_domain');
  ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('country_code')); ?>"><?php esc_attr_e('Country Code:', 'text_domain'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('country_code')); ?>" name="<?php echo esc_attr($this->get_field_name('country_code')); ?>" type="text" value="<?php echo esc_attr(sanitize_text_field($country_code)); ?>">
      <small id="countrycode-error" style="color:red; display: none;">Invalid country code. Please enter a 2-letter country code.</small>
    </p>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('zipcode')); ?>"><?php esc_attr_e('ZIP Code:', 'text_domain'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('zipcode')); ?>" name="<?php echo esc_attr($this->get_field_name('zipcode')); ?>" type="text" value="<?php echo esc_attr(sanitize_text_field($zipcode)); ?>">
      <small id="zipcode-error" style="color:red; display: none;">Invalid ZIP code. Please check your input.</small>
    </p>
<?php
  }

  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['country_code'] = (!empty($new_instance['country_code'])) ? strip_tags($new_instance['country_code']) : '';
    $instance['zipcode'] = (!empty($new_instance['zipcode'])) ? strip_tags($new_instance['zipcode']) : '';

    // Update plugin settings
    update_option('weather_widget_option_country_code', $instance['country_code']);
    update_option('weather_widget_option_zipcode', $instance['zipcode']);

    return $instance;
  }
}

/**
 * Registers the OpenWeatherMap Widget so it can be added to widget areas on the site.
 */
function register_openweathermap_widget()
{
  register_widget('OpenWeatherMap_Widget');
}

add_action('widgets_init', 'register_openweathermap_widget');

/**
 * Adds a settings link to the OpenWeatherMap Widget on the plugins page.
 */
function openweathermap_widget_plugin_settings_link($links)
{
  $settings_link = '<a href="options-general.php?page=weatherwidget">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'openweathermap_widget_plugin_settings_link');

/**
 * Fetch all CSS files from the assets/css directory.
 */
function fetch_css_files()
{
  $css_dir = plugin_dir_path(__FILE__) . 'assets/css/';
  $css_files = array();

  if (is_dir($css_dir)) {
    foreach (glob($css_dir . '*.css') as $file) {
      $css_files[] = basename($file, '.css');
    }
  }

  return $css_files;
}

/**
 * Enqueues the stylesheet for the OpenWeatherMap Widget.
 */
function enqueue_openweathermap_widget_styles()
{
  $style = get_option('weather_widget_option_style', 'default');
  wp_enqueue_style('openweathermap-widget', plugin_dir_url(__FILE__) . "assets/css/{$style}.css");
}

add_action('wp_enqueue_scripts', 'enqueue_openweathermap_widget_styles');
