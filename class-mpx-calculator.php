<?php
/**
 * MPX Calculator Main Class
 *
 * Handles the core functionality of the shipping calculator plugin.
 */

class MPX_Calculator {

    private $default_air_rates;
    private $default_sea_rates;

    /**
     * Constructor
     */
    public function __construct() {
        $this->default_air_rates = array(
            'china_express_air' => array(
                'normal' => 23,
                'electronics' => 25,
                'phones' => 31, // per pc
                'laptops' => 42, // per pc
                'transit' => '10–14 Days',
                'mark' => 'MPX GLOBAL ZW KCGJ + Client Name + MPX Phone Number (+263778866087)',
                'address' => '开诚国际, 深圳市宝安区福永街道下十围宝海电商物流中心首层104仓, 开诚 18122056912',
            ),
            'china_normal' => array(
                'normal' => 19,
                'electronics' => 23,
                'phones' => 33, // per kg
                'laptops' => 33, // per kg
                'transit' => '15–30 Days',
                'mark' => 'MPX GLOBAL H01473 (Your Name & Phone Number)',
                'address' => '收件姓名：MPX GLOBAL H01473区, 收货地址：佛山市南海区里水镇大步社区北123号家渡产业园8号银河仓 H01473区, 联系电话：13500029317',
            ),
            'dubai_express' => array(
                'normal' => 15.50,
                'electronics' => 25,
                'phones' => 33, // per pc
                'transit' => '7–14 Days',
                'mark' => 'MPX-DUBAI-DXB',
                'address' => 'Alnoor Building 2, Al Nahda 2, Apartment 1102, Dubai',
            ),
            'usa_express' => array(
                'normal' => 25,
                'electronics' => 35,
                'laptops' => 48, // per pc
                'tvs' => 26, // per pc
                'transit' => '7–14 Days',
                'mark' => 'MPX Global ZW',
                'address' => 'MPX Global ZW / Your Name, 101 Tiger Way, Boonsboro, MD 21713',
            ),
            'uk_duty_incl' => array(
                'normal' => 12,
                'handling_fee' => 10,
                'min_weight' => 5,
                'transit' => '7–10 Days',
                'mark' => 'MPX-UK-LHR',
                'address' => 'London Warehouse, UK',
            ),
            'india_standard' => array(
                'normal' => 17,
                'transit' => '7–14 Days',
                'mark' => 'MPX GLOBALZW',
                'address' => 'B-11, Gr. Floor, Bldg No. B-2 Shree Devadiga CHS Ltd, Om Nagar, Near Jeena House, J.B. Nagar, Andheri, Mumbai-400099, Maharashtra, India',
            ),
            'turkey_standard' => array(
                'normal' => 15,
                'transit' => '7–14 Days',
                'mark' => 'MPX Global ZW',
                'address' => 'Mimar Kemalettin Mah. Soğanağa Camii Sk., No: 5D Beyazıt – Fatih / İST.',
            ),
        );

        $this->default_sea_rates = array(
            'china_normal' => array(
                'cbm_rate' => 480,
                'min_cbm' => 0.1,
                'transit' => '50–60 Days',
                'mark' => 'MPX GLOBAL ZW AIMRITE0753 + YOUR NAME + PHONE',
                'address' => 'No. 101, First Industrial Zone, Xinlong Village, Lecong Town, Shunde District, Foshan (地址： 广东省佛山市顺德区乐从镇新隆村第一工业区内101号) Recipient: 18188802770',
            ),
            'china_express' => array(
                'cbm_rate' => 600,
                'min_cbm' => 0.1,
                'transit' => '45 Days',
                'mark' => 'MPX GLOBAL ZW EXPRESS AIMRITE0753 + YOUR NAME + PHONE',
                'address' => '地址： 广东省佛山市顺德区乐从镇新隆村第一工业区内101号, 收件人： 18188802770',
            ),
            'dubai_standard' => array(
                'message' => 'Rates on Request',
                'transit' => '30–45 Days',
                'mark' => 'MPX-DUBAI-DXB-SEA',
                'address' => 'Dubai Port Warehouse, UAE',
            ),
        );

        // Register WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_calculate_shipping', array($this, 'calculate_shipping'));
        add_action('wp_ajax_nopriv_calculate_shipping', array($this, 'calculate_shipping'));
        add_action('wp_ajax_generate_pdf', array($this, 'generate_pdf'));
        add_action('wp_ajax_nopriv_generate_pdf', array($this, 'generate_pdf'));

        add_shortcode('mpx_calculator', array($this, 'render_calculator'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Set default options if not set
        if (!get_option('mpx_air_rates')) {
            update_option('mpx_air_rates', $this->default_air_rates);
        }

        if (!get_option('mpx_sea_rates')) {
            update_option('mpx_sea_rates', $this->default_sea_rates);
        }

        if (!get_option('mpx_currency')) {
            update_option('mpx_currency', 'USD');
        }

        if (!get_option('mpx_uk_currency')) {
            update_option('mpx_uk_currency', 'GBP');
        }

        if (!get_option('mpx_whatsapp')) {
            update_option('mpx_whatsapp', '+263714294473');
        }

        // Auto-update WhatsApp if it matches old default
        if (get_option('mpx_whatsapp') === '+263773061744') {
             update_option('mpx_whatsapp', '+263714294473');
        }

        // Auto-update Dubai address if it matches old default
        $stored_air_rates = get_option('mpx_air_rates');
        if (is_array($stored_air_rates) && isset($stored_air_rates['dubai_express']['address']) && $stored_air_rates['dubai_express']['address'] === 'Dubai Warehouse, UAE') {
            $stored_air_rates['dubai_express']['address'] = 'Alnoor Building 2, Al Nahda 2, Apartment 1102, Dubai';
            update_option('mpx_air_rates', $stored_air_rates);
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('mpx-calculator-style', plugin_dir_url(__FILE__) . 'assets/css/mpx-style.css', array(), '1.0.1');
        wp_enqueue_script('mpx-calculator-js', plugin_dir_url(__FILE__) . 'assets/js/calculator.js', array(), '1.0.1', true);
        wp_localize_script('mpx-calculator-js', 'mpx_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpx_calculate_nonce'),
            'pdf_nonce' => wp_create_nonce('mpx_generate_pdf_nonce'),
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'MPX Calculator Settings',
            'MPX Calculator',
            'manage_options',
            'mpx-calculator-settings',
            array($this, 'admin_settings_page'),
            'dashicons-calculator',
            30
        );
    }

    /**
     * Admin settings page callback
     */
    public function admin_settings_page() {
        $air_rates = array_replace_recursive($this->default_air_rates, (array)get_option('mpx_air_rates', array()));
        $sea_rates = array_replace_recursive($this->default_sea_rates, (array)get_option('mpx_sea_rates', array()));
        $currency = get_option('mpx_currency', 'USD');
        $uk_currency = get_option('mpx_uk_currency', 'GBP');
        $whatsapp = get_option('mpx_whatsapp', '263714294473');

        if (isset($_POST['submit'])) {
            // Verify nonce
            if (!wp_verify_nonce($_POST['mpx_settings_nonce'], 'mpx_settings')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
            } else {
                $new_air_rates = $_POST['air'] ?? array();
                $new_sea_rates = $_POST['sea'] ?? array();
                $new_currency = sanitize_text_field($_POST['currency']);
                $new_uk_currency = sanitize_text_field($_POST['uk_currency']);
                $new_whatsapp = sanitize_text_field($_POST['whatsapp']);

                update_option('mpx_air_rates', $new_air_rates);
                update_option('mpx_sea_rates', $new_sea_rates);
                update_option('mpx_currency', $new_currency);
                update_option('mpx_uk_currency', $new_uk_currency);
                update_option('mpx_whatsapp', $new_whatsapp);

                $air_rates = $new_air_rates;
                $sea_rates = $new_sea_rates;
                $currency = $new_currency;
                $uk_currency = $new_uk_currency;
                $whatsapp = $new_whatsapp;

                echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>MPX Calculator Settings</h1>
            <form method="post">
                <?php wp_nonce_field('mpx_settings', 'mpx_settings_nonce'); ?>
                <h2>Air Freight Rates</h2>
                <h3>China Express</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[china_express][normal]" value="<?php echo esc_attr($air_rates['china_express']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Electronics ($/kg)</th><td><input type="number" name="air[china_express][electronics]" value="<?php echo esc_attr($air_rates['china_express']['electronics']); ?>" step="0.01"></td></tr>
                    <tr><th>Phones ($/pc)</th><td><input type="number" name="air[china_express][phones]" value="<?php echo esc_attr($air_rates['china_express']['phones']); ?>" step="0.01"></td></tr>
                    <tr><th>Laptops ($/pc)</th><td><input type="number" name="air[china_express][laptops]" value="<?php echo esc_attr($air_rates['china_express']['laptops']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[china_express][transit]" value="<?php echo esc_attr($air_rates['china_express']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[china_express][mark]" value="<?php echo esc_attr($air_rates['china_express']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[china_express][address]" value="<?php echo esc_attr($air_rates['china_express']['address']); ?>"></td></tr>
                </table>

                <h3>China Normal</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[china_normal][normal]" value="<?php echo esc_attr($air_rates['china_normal']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Electronics ($/kg)</th><td><input type="number" name="air[china_normal][electronics]" value="<?php echo esc_attr($air_rates['china_normal']['electronics']); ?>" step="0.01"></td></tr>
                    <tr><th>Phones/Tabs/Laptops ($/kg)</th><td><input type="number" name="air[china_normal][phones]" value="<?php echo esc_attr($air_rates['china_normal']['phones']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[china_normal][transit]" value="<?php echo esc_attr($air_rates['china_normal']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[china_normal][mark]" value="<?php echo esc_attr($air_rates['china_normal']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[china_normal][address]" value="<?php echo esc_attr($air_rates['china_normal']['address']); ?>"></td></tr>
                </table>

                <h3>Dubai Express</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[dubai_express][normal]" value="<?php echo esc_attr($air_rates['dubai_express']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Electronics ($/kg)</th><td><input type="number" name="air[dubai_express][electronics]" value="<?php echo esc_attr($air_rates['dubai_express']['electronics']); ?>" step="0.01"></td></tr>
                    <tr><th>High-end Phones ($/pc)</th><td><input type="number" name="air[dubai_express][phones]" value="<?php echo esc_attr($air_rates['dubai_express']['phones']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[dubai_express][transit]" value="<?php echo esc_attr($air_rates['dubai_express']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[dubai_express][mark]" value="<?php echo esc_attr($air_rates['dubai_express']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[dubai_express][address]" value="<?php echo esc_attr($air_rates['dubai_express']['address']); ?>"></td></tr>
                </table>

                <h3>USA Express</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[usa_express][normal]" value="<?php echo esc_attr($air_rates['usa_express']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Electronics ($/kg)</th><td><input type="number" name="air[usa_express][electronics]" value="<?php echo esc_attr($air_rates['usa_express']['electronics']); ?>" step="0.01"></td></tr>
                    <tr><th>Laptops ($/pc)</th><td><input type="number" name="air[usa_express][laptops]" value="<?php echo esc_attr($air_rates['usa_express']['laptops']); ?>" step="0.01"></td></tr>
                    <tr><th>TVs ($/pc)</th><td><input type="number" name="air[usa_express][tvs]" value="<?php echo esc_attr($air_rates['usa_express']['tvs']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[usa_express][transit]" value="<?php echo esc_attr($air_rates['usa_express']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[usa_express][mark]" value="<?php echo esc_attr($air_rates['usa_express']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[usa_express][address]" value="<?php echo esc_attr($air_rates['usa_express']['address']); ?>"></td></tr>
                </table>

                <h3>UK Duty-Incl</h3>
                <table class="form-table">
                    <tr><th>Normal (£/kg)</th><td><input type="number" name="air[uk_duty_incl][normal]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Handling Fee (£)</th><td><input type="number" name="air[uk_duty_incl][handling_fee]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['handling_fee']); ?>" step="0.01"></td></tr>
                    <tr><th>Min Weight (kg)</th><td><input type="number" name="air[uk_duty_incl][min_weight]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['min_weight']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[uk_duty_incl][transit]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[uk_duty_incl][mark]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[uk_duty_incl][address]" value="<?php echo esc_attr($air_rates['uk_duty_incl']['address']); ?>"></td></tr>
                </table>

                <h3>India Standard</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[india_standard][normal]" value="<?php echo esc_attr($air_rates['india_standard']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[india_standard][transit]" value="<?php echo esc_attr($air_rates['india_standard']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[india_standard][mark]" value="<?php echo esc_attr($air_rates['india_standard']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[india_standard][address]" value="<?php echo esc_attr($air_rates['india_standard']['address']); ?>"></td></tr>
                </table>

                <h3>Turkey Standard</h3>
                <table class="form-table">
                    <tr><th>Normal ($/kg)</th><td><input type="number" name="air[turkey_standard][normal]" value="<?php echo esc_attr($air_rates['turkey_standard']['normal']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="air[turkey_standard][transit]" value="<?php echo esc_attr($air_rates['turkey_standard']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="air[turkey_standard][mark]" value="<?php echo esc_attr($air_rates['turkey_standard']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="air[turkey_standard][address]" value="<?php echo esc_attr($air_rates['turkey_standard']['address']); ?>"></td></tr>
                </table>

                <h2>Sea Freight Rates</h2>
                <h3>China Normal Sea</h3>
                <table class="form-table">
                    <tr><th>CBM Rate ($)</th><td><input type="number" name="sea[china_normal][cbm_rate]" value="<?php echo esc_attr($sea_rates['china_normal']['cbm_rate']); ?>" step="0.01"></td></tr>
                    <tr><th>Min CBM</th><td><input type="number" name="sea[china_normal][min_cbm]" value="<?php echo esc_attr($sea_rates['china_normal']['min_cbm']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="sea[china_normal][transit]" value="<?php echo esc_attr($sea_rates['china_normal']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="sea[china_normal][mark]" value="<?php echo esc_attr($sea_rates['china_normal']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="sea[china_normal][address]" value="<?php echo esc_attr($sea_rates['china_normal']['address']); ?>"></td></tr>
                </table>

                <h3>China Express Sea</h3>
                <table class="form-table">
                    <tr><th>CBM Rate ($)</th><td><input type="number" name="sea[china_express][cbm_rate]" value="<?php echo esc_attr($sea_rates['china_express']['cbm_rate']); ?>" step="0.01"></td></tr>
                    <tr><th>Min CBM</th><td><input type="number" name="sea[china_express][min_cbm]" value="<?php echo esc_attr($sea_rates['china_express']['min_cbm']); ?>" step="0.01"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="sea[china_express][transit]" value="<?php echo esc_attr($sea_rates['china_express']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="sea[china_express][mark]" value="<?php echo esc_attr($sea_rates['china_express']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="sea[china_express][address]" value="<?php echo esc_attr($sea_rates['china_express']['address']); ?>"></td></tr>
                </table>

                <h3>Dubai Standard Sea</h3>
                <table class="form-table">
                    <tr><th>Message</th><td><input type="text" name="sea[dubai_standard][message]" value="<?php echo esc_attr($sea_rates['dubai_standard']['message']); ?>"></td></tr>
                    <tr><th>Transit Time</th><td><input type="text" name="sea[dubai_standard][transit]" value="<?php echo esc_attr($sea_rates['dubai_standard']['transit']); ?>"></td></tr>
                    <tr><th>Shipping Mark</th><td><input type="text" name="sea[dubai_standard][mark]" value="<?php echo esc_attr($sea_rates['dubai_standard']['mark']); ?>"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="sea[dubai_standard][address]" value="<?php echo esc_attr($sea_rates['dubai_standard']['address']); ?>"></td></tr>
                </table>

                <h2>Currency Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Default Currency</th>
                        <td><input type="text" name="currency" value="<?php echo esc_attr($currency); ?>"></td>
                    </tr>
                    <tr>
                        <th>UK Currency</th>
                        <td><input type="text" name="uk_currency" value="<?php echo esc_attr($uk_currency); ?>"></td>
                    </tr>
                    <tr>
                        <th>WhatsApp Number</th>
                        <td><input type="text" name="whatsapp" value="<?php echo esc_attr($whatsapp); ?>"></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render calculator shortcode
     */
    public function render_calculator() {
        ob_start();
        ?>
        <div id="mpx-calculator" class="mpx-calculator">
            <div class="mpx-header">
                <h2>MPX Global Shipping Calculator</h2>
                <p>Get instant quotes for international shipping from China, Dubai, USA, UK & India to Zimbabwe</p>
            </div>
            <form id="mpx-calculator-form" class="mpx-form">
                <div class="mpx-field">
                    <label for="mpx-client-name">👤 Name</label>
                    <input type="text" id="mpx-client-name" name="client_name" required placeholder="e.g. John Doe">
                </div>
                <div class="mpx-field">
                    <label for="mpx-client-phone">📱 Phone Number</label>
                    <input type="text" id="mpx-client-phone" name="client_phone" required placeholder="e.g. 0771234567">
                </div>
                <div class="mpx-field">
                    <label for="mpx-origin">
                        🌍 Origin
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Select the country where your goods are shipped from</span>
                        </span>
                    </label>
                    <select id="mpx-origin" name="origin" required>
                        <option value="">Select Origin</option>
                        <option value="china">China</option>
                        <option value="dubai">Dubai</option>
                        <option value="usa">USA</option>
                        <option value="uk">UK</option>
                        <option value="india">India</option>
                        <option value="turkey">Turkey</option>
                    </select>
                </div>

                <div class="mpx-field" id="mpx-mode-field" style="display: none;">
                    <label for="mpx-mode">
                        ✈️ Mode
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Choose your preferred shipping method and speed</span>
                        </span>
                    </label>
                    <select id="mpx-mode" name="mode">
                        <!-- Options populated by JS -->
                    </select>
                </div>

                <div class="mpx-field">
                    <label for="mpx-category">
                        📦 Category
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Select the type of goods you're shipping</span>
                        </span>
                    </label>
                    <select id="mpx-category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="normal">Normal Goods</option>
                        <option value="electronics">Electronics</option>
                        <option value="phones">Phones</option>
                        <option value="laptops">Laptops</option>
                        <option value="tvs">TVs</option>
                    </select>
                </div>

                <div class="mpx-field">
                    <label for="mpx-weight">
                        ⚖️ Weight (kg)
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Enter the total weight of your shipment in kilograms</span>
                        </span>
                    </label>
                    <input type="number" id="mpx-weight" name="weight" min="0.1" max="1000" step="0.1" required>
                </div>

                <div class="mpx-field" id="mpx-quantity-field" style="display: none;">
                    <label for="mpx-quantity">
                        🔢 Quantity
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Number of phones (for phones category only)</span>
                        </span>
                    </label>
                    <input type="number" id="mpx-quantity" name="quantity" min="1">
                </div>

                <div class="mpx-field">
                    <label class="toggle-label">
                        📏 Do you know the box dimensions?
                        <span class="mpx-tooltip">?
                            <span class="tooltip-text">Check this if you have length, width, and height measurements for volumetric calculation</span>
                        </span>
                        <input type="checkbox" id="mpx-dimensions-toggle">
                    </label>
                </div>

                <div id="mpx-dimensions" style="display: none;">
                    <div class="mpx-field">
                        <label for="mpx-length">Length (cm)</label>
                        <input type="number" id="mpx-length" name="length" min="1" step="0.1">
                    </div>
                    <div class="mpx-field">
                        <label for="mpx-width">Width (cm)</label>
                        <input type="number" id="mpx-width" name="width" min="1" step="0.1">
                    </div>
                    <div class="mpx-field">
                        <label for="mpx-height">Height (cm)</label>
                        <input type="number" id="mpx-height" name="height" min="1" step="0.1">
                    </div>
                </div>

                <div style="display: flex; justify-content: center; align-items: center; grid-column: span 2;">
                    <button type="submit" id="mpx-calculate-btn" class="mpx-btn">
                        <span class="spinner"></span>
                        Calculate Shipping Cost
                    </button>
                    <button type="button" id="mpx-reset-btn" class="mpx-reset-btn">Reset</button>
                </div>
            </form>

            <div id="mpx-result" class="mpx-result" style="display: none;">
                <!-- Result will be populated by JS -->
                <div id="mpx-result-actions" style="display: none; margin-top: 20px;">
                    <button id="mpx-download-pdf-btn" class="mpx-btn">Download PDF</button>
                </div>
                <p class="mpx-export-fee-note" style="display: none; margin-top: 15px; font-style: italic;">There will be an export fee on top of the estimated prices.</p>
            </div>

            <div class="mpx-footer">
                <p>Need help? <a href="mailto:info@mpxglobal.co.zw">Contact MPX Global</a> | <a href="tel:0773061744">Call 0773 061 744</a></p>
                <p>Quotes are estimates. Final costs may vary based on actual weight, dimensions, and customs regulations.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for calculation
     */
    public function calculate_shipping() {
        // Verify nonce
        // if (!wp_verify_nonce($_POST['nonce'], 'mpx_calculate_nonce')) {
        //     wp_die('Security check failed');
        // }

        $client_name = isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '';
        $client_phone = isset($_POST['client_phone']) ? sanitize_text_field($_POST['client_phone']) : '';
        $origin = sanitize_text_field($_POST['origin']);
        $mode = sanitize_text_field($_POST['mode']);
        $category = sanitize_text_field($_POST['category']);
        $weight = floatval($_POST['weight']);
        $length = isset($_POST['length']) ? floatval($_POST['length']) : 0;
        $width = isset($_POST['width']) ? floatval($_POST['width']) : 0;
        $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;
        $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        if ($category === 'phones' && $qty <= 0) {
            $qty = 1;
        }

        $air_rates = array_replace_recursive($this->default_air_rates, (array)get_option('mpx_air_rates', array()));
        $sea_rates = array_replace_recursive($this->default_sea_rates, (array)get_option('mpx_sea_rates', array()));

        $result = array(
            'success' => false,
            'message' => 'Invalid input',
        );

        // Calculate volumetric weight and volume
        $volume_cbm = 0;
        $volumetric_weight = 0;
        if ($length > 0 && $width > 0 && $height > 0) {
            $volume_cbm = ($length * $width * $height) / 1000000;
            $volumetric_weight = ($length * $width * $height) / 6000;
        }
        $chargeable_weight = ceil(max($weight, $volumetric_weight));

        $total_cost = 0;
        $currency = get_option('mpx_currency', 'USD');
        $transit = '';
        $shipping_mark = '';
        $address = '';
        $unit = 'kg';
        $amount = $chargeable_weight;
        $note = '';

        // Determine freight type and normalize mode
        $freight_type = strpos($mode, '_sea') !== false ? 'sea' : 'air';
        $normalized_mode = str_replace('_sea', '', $mode);

        if ($freight_type === 'air') {
            if (isset($air_rates[$normalized_mode])) {
                $route = $air_rates[$mode];
                $transit = $route['transit'];
                $shipping_mark = $route['mark'];
                $address = $route['address'];

                if ($category === 'phones' && isset($route['phones'])) {
                    $total_cost = $route['phones'] * $qty;
                    $unit = 'pc';
                    $amount = $qty;
                } elseif ($category === 'laptops' && isset($route['laptops'])) {
                    $total_cost = $route['laptops'] * $qty;
                    $unit = 'pc';
                    $amount = $qty;
                } elseif ($category === 'tvs' && isset($route['tvs'])) {
                    $total_cost = $route['tvs'] * $qty;
                    $unit = 'pc';
                    $amount = $qty;
                } else {
                    $rate = $route[$category] ?? $route['normal'];
                    $total_cost = $rate * $chargeable_weight;
                }

                // Special handling for UK
                if ($mode === 'uk_duty_incl') {
                    $currency = get_option('mpx_uk_currency', 'GBP');
                    if ($chargeable_weight < $route['min_weight']) {
                        $total_cost += $route['handling_fee'];
                        $note = 'Note: UK shipments have a 5kg minimum chargeable weight.';
                    }
                }
            }
        } elseif ($freight_type === 'sea') {
            if (isset($sea_rates[$normalized_mode])) {
                $route = $sea_rates[$normalized_mode];
                if (isset($route['message'])) {
                    $result['message'] = $route['message'];
                    $transit = $route['transit'];
                    $shipping_mark = $route['mark'];
                    $address = $route['address'];
                } else {
                    if ($volume_cbm == 0) {
                        $result['message'] = 'Dimensions required for sea freight';
                        wp_send_json($result);
                    }
                    $cbm = max($volume_cbm, $route['min_cbm']);
                    $total_cost = $route['cbm_rate'] * $cbm;
                    $transit = $route['transit'];
                    $shipping_mark = $route['mark'];
                    $address = $route['address'];
                    $unit = 'CBM';
                    $amount = $cbm;
                }
            }
        }

        // Customize Shipping Mark with Client Details
        $client_id = sprintf('%03d', rand(0, 999));
        if (!empty($shipping_mark)) {
            // First handle specific compound placeholders
            $shipping_mark = str_ireplace('Your Name & Phone Number', "$client_name $client_phone", $shipping_mark);
            
            // Handle Name
            $shipping_mark = str_ireplace(array('Your Name', 'Client Name', 'YOUR NAME'), $client_name, $shipping_mark);
            
            // Handle Phone (avoid breaking 'MPX Phone Number')
            if (stripos($shipping_mark, 'MPX Phone') === false) {
                 $shipping_mark = str_ireplace(array('Phone Number', 'PHONE'), $client_phone, $shipping_mark);
            }
            
            // Append Unique Client ID
            $shipping_mark .= ' #' . $client_id;
        }

        if ($total_cost > 0 || isset($result['message'])) {
            $result = array(
                'success' => $total_cost > 0,
                'client_name' => $client_name,
                'client_phone' => $client_phone,
                'client_id' => $client_id,
                'cost' => $total_cost > 0 ? number_format($total_cost, 2) : '',
                'currency' => $currency,
                'unit' => $unit,
                'amount' => number_format($amount, 2),
                'transit' => $transit,
                'shipping_mark' => $shipping_mark,
                'address' => $address,
                'breakdown' => $total_cost > 0 ? 'Freight: ' . $currency . number_format($total_cost, 2) : '',
                'note' => $note,
                'whatsapp' => get_option('mpx_whatsapp', '+263714294473'),
                'message' => isset($result['message']) ? $result['message'] : '',
            );
        }

        wp_send_json($result);
    }

    /**
     * AJAX handler for PDF generation
     */
    public function generate_pdf() {
        if (!wp_verify_nonce($_POST['nonce'], 'mpx_generate_pdf_nonce')) {
            wp_die('Security check failed');
        }

        $origin = sanitize_text_field($_POST['origin']);
        $mode = sanitize_text_field($_POST['mode']);
        $category = sanitize_text_field($_POST['category']);
        $weight = floatval($_POST['weight']);
        $cost = sanitize_text_field($_POST['cost']);
        $currency = sanitize_text_field($_POST['currency']);
        $transit = sanitize_text_field($_POST['transit']);
        $shipping_mark = sanitize_text_field($_POST['shipping_mark']);
        $address = sanitize_text_field($_POST['address']);

        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('MPX Global');
        $pdf->SetTitle('MPX Shipping Quote');
        $pdf->SetSubject('Shipping Quote');
        $pdf->AddPage();

        $html = "<h1>MPX Global Shipping Quote</h1>";
        $html .= "<p><strong>Origin:</strong> {$origin}</p>";
        $html .= "<p><strong>Mode:</strong> {$mode}</p>";
        $html .= "<p><strong>Category:</strong> {$category}</p>";
        $html .= "<p><strong>Weight:</strong> {$weight} kg</p>";
        $html .= "<h2>Total Cost: {$cost} {$currency}</h2>";
        $html .= "<p><strong>Transit Time:</strong> {$transit}</p>";
        $html .= "<p><strong>Shipping Mark:</strong> {$shipping_mark}</p>";
        $html .= "<p><strong>Warehouse Address:</strong> {$address}</p>";
        $html .= "<p><em>This is an estimate. Final costs may vary.</em></p>";

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('MPX_Quote.pdf', 'D');

        wp_die();
    }


}