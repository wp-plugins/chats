<?php
/*
Plugin Name: Chats
Plugin URI: http://www.wp-chat.com
Description: Web Page Chats for Websites
Version: 1.0.1
Author: wp-chat
Author URI: http://www.wp-chat.com
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'Chats' ) ) {
    class Chats
    {
        public static $plugin_name      = 'chats';
        public static $plugin_version   = '1.0.1';
        public static $table_prefix     = 'chats_';
        public static $tag_prefix       = 'chats';
        public static $optionParameters = 'chats_options';
        public static $defaultOptions   = array(
            'panel_background'          => '#2785C1',
            'panel_border_color'        => '#2785C1',
            'body_background'           => '#FFFFFF',
            'btn_finish_background'     => '#FCFCFB',
            'btn_finish_color'          => '#333333',
            'btn_finish_border_color'   => '#C4C4C3',
            'btn_expand_background'     => '#46A0DA',
            'admin_signature_color'     => '#627AAD',
            'admin_text_color'          => '#333333',
            'user_signature_color'      => '#000000',
            'user_text_color'           => '#333333',
            'time_color'                => '#909090',
            'message_border_color'      => '#F2F2F2',
            'write_panel_background'    => '#F0F0EF',
            'write_area_background'     => '#FFFFFF',
            'write_area_color'          => '#333333',

            'width'                     => 300,
            'position'                  => 'right',
            'status'                    => 1,

            'admin_signature'           => 'Admin',
            'user_signature'            => 'You',
            'hello_message'             => 'Hello. Do you have any questions?',
            'panel_title'               => 'Chat with us',
            'enter_text_placeholder'    => 'Enter your message...',
            'btn_finish_text'           => 'Finish',
            'thank_message'             => 'Thank you for using Live Chat. To help us serve you better, please take a moment to complete a short survey. It will display as you close the chat by clicking the chat bubble at the top right of the screen. Thanks for chatting. Please click the "Close" icon, and then tell us how we did.',
            'offline_message'           => 'Sorry, but we are offline now. Please, leave your contact email and your message. We will communicate with you as soon as possible.',
            'email_label'               => 'Email',
            'name_label'                => 'Name',
            'message_label'             => 'Message',
            'send_email'                => 'Send email',
            'offline_thank_message'     => 'Thank you. Message was sent.',
        );
        public static $cookiePrefix     = 'chats_hash';
        public static $translation = array(
            'en'    => array(
                'page_settings_title'               => 'Chats Settings',
                'settings_tab_color'                => 'Colors',
                'settings_width'                    => 'Width',
                'settings_panel_background'         => 'Title panel Background',
                'settings_panel_border_color'       => 'Chat border color',
                'settings_body_background'          => 'Message panel background',
                'settings_btn_finish_background'    => 'Finish button background',
                'settings_btn_finish_color'         => 'Finish button color',
                'settings_btn_finish_border_color'  => 'Finish button border color',
                'settings_btn_expand_background'    => 'Expand button background',
                'settings_admin_signature_color'    => 'Admin signature color',
                'settings_admin_text_color'         => 'Admin text color',
                'settings_user_signature_color'     => 'User signature color',
                'settings_user_text_color'          => 'User text color',
                'settings_time_color'               => 'Message time color',
                'settings_message_border_color'     => 'Message border color',
                'settings_write_panel_background'   => 'Write panel background',
                'settings_write_area_background'    => 'Write area background',
                'settings_write_area_color'         => 'Write area color',

                'settings_tab_template'             => 'Template',
                'settings_position'                 => 'Position',
                'settings_position_left'            => 'Left',
                'settings_position_right'           => 'Right',
                'settings_status'                   => 'Chat status',
                'settings_status_1'                 => 'Chat online',
                'settings_status_2'                 => 'Chat hidden',
                'settings_status_3'                 => 'Chat offline',

                'settings_tab_text'                 => 'Text',
                'settings_admin_signature'          => 'Admin Signature',
                'settings_user_signature'           => 'User Signature',
                'settings_hello_message'            => '"Hello" message',
                'settings_panel_title'              => 'Panel title',
                'settings_enter_text_placeholder'   => 'Enter text placeholder',
                'settings_btn_finish_text'          => 'Finish button label',
                'settings_thank_message'            => '"Thank" message',
                'settings_offline_message'          => '"Offline chat" message',
                'settings_email_label'              => 'Email',
                'settings_name_label'               => 'Name',
                'settings_message_label'            => 'Message label',
                'settings_send_email'               => 'Send email button',
                'settings_offline_thank_message'    => '"Offline thank" message',

                'settings_tab_auth'                 => 'Authorization',
                'settings_personal_key'             => 'Personal key',
                'settings_personal_key_desc'        => 'Be careful, this key should be the same as in admin panel of site',
            )
        );

        /**
         * Listen incoming request from js
         */
        public static function js_process(){
            $personalKey = (string)get_option(ChatsAction::$optionKey, '');
            if(empty($personalKey)){
                return true;
            }

            global $current_user;
            get_currentuserinfo();

            $mode           = @$_POST['mode'];
            $hash           = @$_COOKIE[self::$cookiePrefix];
            $ip             = self::getUserIP();
            $browser        = @$_SERVER['HTTP_USER_AGENT'];
            $currentUserID  = @(int)$current_user->ID;

            $message        = @strip_tags(trim($_POST['message']));
            $message_page   = @strip_tags(trim($_POST['message_page']));
            $queryMode      = @$_POST['queryMode'];
            $countMessage   = @$_POST['countMessage'];
            //offline data
            $text   = @strip_tags(trim($_POST['text']));
            $name   = @strip_tags(trim($_POST['name']));
            $email  = @strip_tags(trim($_POST['email']));

            if( empty($mode) or empty($hash) or !in_array($mode, array('add', 'read', 'finish', 'send_email')) ){
                die();
            }

            $created    = date('Y-m-d H:i:s');

            //save message from user
            if( $mode == 'add' and !empty($message) ){
                $addRes = self::add_messages($hash, $ip, $browser, $message, $created, 0, $message_page, $currentUserID);
                if($addRes){
                    self::send_messages($hash, $ip, $browser, $message, $created, $message_page, $currentUserID);
                }

                print json_encode(array('result' => $addRes));exit;
            }

            //read message of user
            if( $mode == 'read' and !empty($hash) and !empty($ip) and !empty($browser) ){
                $messages = self::read_messages($hash, $ip, $browser, $currentUserID, array('queryMode' => $queryMode, 'countMessage' => $countMessage));

                print json_encode(array('messages' => $messages));exit;
            }

            //finish chat
            if( $mode == 'finish' and !empty($hash) and !empty($ip) and !empty($browser) ){
                //send log operation "finish"
                self::send_log($hash, $ip, $browser, $created, 'finish', array(), $message_page, $currentUserID);

                //get all messages for sending log to user

                //remove all messages
                self::remove_messages($hash, $ip, 'user');
            }

            //send message
            if( $mode == 'send_email' and !empty($hash) and !empty($ip) and !empty($browser) and !empty($text) and !empty($name) and !empty($email) ){
                $sendRes = self::send_offline_messages($hash, $ip, $browser, $text, $name, $email, $created, $message_page, $currentUserID);

                print json_encode(array('result' => $sendRes));exit;
            }

            exit;
        }

        /**
         * Init chat. Should be started at all pages on frontend
         */
        public static function wp_head(){
        	//delete_option( ChatsAction::$optionKey );
            $personalKey = (string)get_option(ChatsAction::$optionKey, '');
            if(empty($personalKey)){
                return true;
            }

            //parameters of chat
            $options  = self::plugin_options( 'get' );
            if( (int)$options['status'] == 2){
                return true;
            }

            //only for frontend
            if ( !is_admin() ) {
                wp_register_script('chats_js', plugins_url(self::$plugin_name . '/assets/chats.js'), array(), self::$plugin_version, false);
                wp_register_style( 'chats_css', plugins_url( self::$plugin_name . '/assets/chats.css' ) );

                //add parameters in js
                $js_parameters  = array(
                    'site_url'      => site_url(),
                    'request_url'   => site_url() . '/wp-admin/admin-ajax.php',
                    'cookie_prefix' => self::$cookiePrefix,
                    'tag_prefix'    => self::$tag_prefix,
                    'sound_path'    => plugins_url(self::$plugin_name . '/assets'),
                    'text'  => array(
                        'admin_signature'           => $options['admin_signature'],
                        'user_signature'            => $options['user_signature'],
                        'hello_message'             => $options['hello_message'],
                        'panel_title'               => $options['panel_title'],
                        'enter_text_placeholder'    => $options['enter_text_placeholder'],
                        'btn_finish_text'           => $options['btn_finish_text'],
                        'offline_message'           => $options['offline_message'],
                        'email_label'               => $options['email_label'],
                        'name_label'                => $options['name_label'],
                        'message_label'             => $options['message_label'],
                        'send_email'                => $options['send_email'],
                        'offline_thank_message'     => $options['offline_thank_message'],
                    ),
                    'color' => array(
                        'panel_background'          => $options['panel_background'],
                        'panel_border_color'        => $options['panel_border_color'],
                        'body_background'           => $options['body_background'],
                        'btn_finish_background'     => $options['btn_finish_background'],
                        'btn_finish_color'          => $options['btn_finish_color'],
                        'btn_finish_border_color'   => $options['btn_finish_border_color'],
                        'btn_expand_background'     => $options['btn_expand_background'],
                        'admin_signature_color'     => $options['admin_signature_color'],
                        'admin_text_color'          => $options['admin_text_color'],
                        'user_signature_color'      => $options['user_signature_color'],
                        'user_text_color'           => $options['user_text_color'],
                        'time_color'                => $options['time_color'],
                        'message_border_color'      => $options['message_border_color'],
                        'write_panel_background'    => $options['write_panel_background'],
                        'write_area_background'     => $options['write_area_background'],
                        'write_area_color'          => $options['write_area_color'],
                    ),
                    'template'  => array(
                        'width'     => $options['width'],
                        'position'  => $options['position'],
                        'status'    => $options['status'],
                    )
                );
                wp_localize_script('chats_js', 'chats_parameters', $js_parameters);

                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'chats_js' );
                wp_enqueue_style( 'chats_css' );

                // Stop any output until cookies are set
                ob_start();
            }
        }

        /**
         * Add html in footer
         */
        public static function wp_footer(){

        }

        /**
         * Notice for user in admin panel
         */
        public static function admin_notices(){
            if(is_admin()) {

                //notice about registration or authorization
                $plugin         = self::$plugin_name.'/'.self::$plugin_name.'.php';
                $personaKey     = (string)get_option(ChatsAction::$optionKey, '');
                $pluginStatus   = is_plugin_active($plugin);
                if( empty($personaKey) and $pluginStatus ){
                    global $status, $page, $s;
                    $nonce      = wp_create_nonce('deactivate-plugin_' . $plugin);
                    $actions    = 'plugins.php?action=deactivate&amp;plugin=' . urlencode($plugin) . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s  . '&amp;_wpnonce=' . $nonce;
                    echo '<div style="height:50px;line-height:50px;font-size:16px;font-weight:bold;" class="notice-warning notice">To use "chats" plugin, please add this site to <a target="_blank" href="'.ChatsAction::$site.'">your account</a> at '.str_replace('http://','',ChatsAction::$site).' or <a href="'.$actions.'">deactivate</a> Chats plugin.</div>';
                }
            }
        }

        public static function admin_menu(){
            if(is_admin()) {
                //settings menu for admin
                add_menu_page('Chats Settings', 'Chats Settings', 'manage_options', 'chats_settings_page', array('Chats', 'chats_settings_page'));
                add_action( 'admin_init', array('Chats', 'register_settings') );
            }
        }

        /**
         * Plugin options
         */
        public static function plugin_options($mode = 'add', $options = array()){
            if(empty($options)){
                $options = self::$defaultOptions;
            }

            if($mode == 'add'){
                update_option(self::$optionParameters, $options);
            }

            if( $mode == 'get' ){
                $optionValue = get_option(self::$optionParameters, $options);
                foreach(self::$defaultOptions as $k_op => $v_op){
                    $optionValue[$k_op] = (empty($optionValue[$k_op]) ? $v_op : $optionValue[$k_op]);
                }
                return $optionValue;
            }

            if( $mode == 'remove' ){
                delete_option( ChatsAction::$optionKey );//delete personal key of plugin
                delete_option( self::$optionParameters );//delete settings options
            }
        }

        /**
         * Processing message from user
         */
        public static function send_messages($hash, $ip, $browser, $message, $created, $message_page = '', $currentUserID = 0){
            //maybe we should get email of logged users?

            $personalKey = (string)get_option(ChatsAction::$optionKey, '');

            $data = array(
                'action'            => 'message',
                'key'               => $personalKey,
                'hash'              => $hash,
                'ip'                => $ip,
                'browser'           => $browser,
                'message'           => $message,
                'message_page'      => $message_page,
                'created'           => $created,
                'plugin_version'    => self::$plugin_version,
                'domain'            => site_url()
            );
            $requestVars = array('body' => array(ChatsAction::$tagAnswer => ChatsAction::convertString($data,'encode')));

            return ChatsAction::requestServer( $requestVars );
        }

        /**
         * Send message from admin to user in offline mode
         */
        public function send_offline_messages($hash, $ip, $browser, $message, $name, $email, $created, $message_page, $currentUserID){
            $personalKey = (string)get_option(ChatsAction::$optionKey, '');

            $data = array(
                'action'            => 'offline_message',
                'key'               => $personalKey,
                'hash'              => $hash,
                'ip'                => $ip,
                'browser'           => $browser,
                'message'           => $message,
                'user_name'         => $name,
                'user_email'        => $email,
                'message_page'      => $message_page,
                'created'           => $created,
                'plugin_version'    => self::$plugin_version,
                'domain'            => site_url()
            );
            $requestVars = array('body' => array(ChatsAction::$tagAnswer => ChatsAction::convertString($data,'encode')));

            return ChatsAction::requestServer( $requestVars );
        }

        /**
         * Processing log from user
         */
        public static function send_log($hash, $ip, $browser, $created, $logCommand, $logData = array(), $referer_page = '', $currentUserID = 0){
            //maybe we should get email of logged users?

            $personalKey = (string)get_option(ChatsAction::$optionKey, '');

            $data = array(
                'action'            => 'log',
                'key'               => $personalKey,
                'hash'              => $hash,
                'ip'                => $ip,
                'browser'           => $browser,
                'log_command'       => $logCommand,
                'log_data'          => $logData,
                'referer_page'      => $referer_page,
                'created'           => $created,
                'plugin_version'    => self::$plugin_version,
                'domain'            => site_url()
            );
            $requestVars = array('body' => array(ChatsAction::$tagAnswer => ChatsAction::convertString($data,'encode')));

            return ChatsAction::requestServer( $requestVars );
        }

        /**
         * Processing update of options
         */
        public static function send_options($options){
            //maybe we should get email of logged users?

            $personalKey = (string)get_option(ChatsAction::$optionKey, '');

            $data = array(
                'action'            => 'update_options',
                'key'               => $personalKey,
                'options'           => json_encode($options),
                'plugin_version'    => self::$plugin_version,
                'domain'            => site_url()
            );
            $requestVars = array('body' => array(ChatsAction::$tagAnswer => ChatsAction::convertString($data,'encode')));

            return ChatsAction::requestServer( $requestVars );
        }

        /**
         * Read messages from db
         */
        public static function read_messages($hash, $ip, $browser = '', $currentUserID = 0, $parameters = array()){
            global $wpdb;

            $list       = array();
            $options    = self::plugin_options( 'get' );

            $limit = @(int)$parameters['countMessage'];
            $limit = (empty($limit) ? 1 : $limit);

            if( empty($parameters['queryMode']) ){

            }

            if( $parameters['queryMode'] == 'api_read' ){
                $sql =  $wpdb->prepare('
                    SELECT * FROM  `'.$wpdb->base_prefix.self::$table_prefix.'messages`
                    WHERE
                        user_hash = %s AND user_ip = %s
                    ORDER BY
                        created DESC
                    ',
                    $hash, $ip
                );
                $list = $wpdb->get_results($sql);
            }

            if( $parameters['queryMode'] == 'user_last' ){
                $sql =  $wpdb->prepare('
                    SELECT id, message, created, message_type FROM  `'.$wpdb->base_prefix.self::$table_prefix.'messages`
                    WHERE
                        user_hash = %s AND user_ip = %s AND ( (user_browser = %s AND message_type = 0) OR (user_browser = "" AND message_type = 1) )
                    ORDER BY
                        created DESC
                    LIMIT 0, '.$limit.'
                    ',
                    $hash, $ip, $browser
                );
                $messages = $wpdb->get_results($sql);

                if( !empty($messages) ){
                    foreach($messages as $k_item => $v_item){
                        $list[] = array(
                            'name'          => ($v_item->message_type == 0 ? $options['user_signature'] : $options['admin_signature']),
                            'message_id'    => $v_item->id.'_'.$hash,
                            'text'          => nl2br($v_item->message),
                            'time'          => date('H:i', @strtotime($v_item->created)),
                            'type'          => $v_item->message_type
                        );
                    }

                    if($limit > 1){
                        $list = array_reverse($list);
                    }
                }
            }

            return $list;
        }

        /**
         * Add message in db
         */
        public static function add_messages($hash, $ip, $browser, $message, $created = '', $message_type = 0, $message_page = '', $currentUserID = 0){
            global $wpdb;

            $created = ( empty($created) ? date('Y-m-d H:i:s') : $created);

            $sql =  $wpdb->prepare(
                'INSERT INTO `'.$wpdb->base_prefix.self::$table_prefix.'messages` SET
                    user_hash = %s,
                    user_ip = %s,
                    user_browser = %s,
                    message = %s,
                    message_page = %s,
                    created = %s,
                    message_type = %d
                ',
                $hash, $ip, $browser, $message, $message_page, $created, $message_type
            );
            return $wpdb->query( $sql );
        }

        /**
         * Remove messages from db
         *
         * @param $hash
         * @param $ip
         *
         * @return bool
         */
        public static function remove_messages($hash, $ip, $mode = '' ){
            global $wpdb;

            if( empty($mode) ){

            }

            //remove sll message of user
            if($mode == 'user'){
                $sql =  $wpdb->prepare('
                    DELETE FROM  `'.$wpdb->base_prefix.self::$table_prefix.'messages` WHERE user_hash = %s AND user_ip = %s',
                    $hash, $ip
                );
                $wpdb->query($sql);
            }

            if($mode == 'all'){
                $sql =  'TRUNCATE TABLE `'.$wpdb->base_prefix.self::$table_prefix.'messages`';
                $wpdb->query($sql);
            }

            return true;
        }

        public static function getUserIP(){
            $user_ip = '';
            if ( getenv('REMOTE_ADDR') ){
                $user_ip = getenv('REMOTE_ADDR');
            }elseif ( getenv('HTTP_FORWARDED_FOR') ){
                $user_ip = getenv('HTTP_FORWARDED_FOR');
            }elseif ( getenv('HTTP_X_FORWARDED_FOR') ){
                $user_ip = getenv('HTTP_X_FORWARDED_FOR');
            }elseif ( getenv('HTTP_X_COMING_FROM') ){
                $user_ip = getenv('HTTP_X_COMING_FROM');
            }elseif ( getenv('HTTP_VIA') ){
                $user_ip = getenv('HTTP_VIA');
            }elseif ( getenv('HTTP_XROXY_CONNECTION') ){
                $user_ip = getenv('HTTP_XROXY_CONNECTION');
            }elseif ( getenv('HTTP_CLIENT_IP') ){
                $user_ip = getenv('HTTP_CLIENT_IP');
            }

            $user_ip = trim($user_ip);
            if ( empty($user_ip) ){
                return '';
            }
            if ( !preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $user_ip) ){
                return '';
            }
            return $user_ip;
        }

        public static function trans($var, $lang = 'en'){
            $text = $var;
            if( isset(self::$translation[$lang]) ){
                if( isset(self::$translation[$lang][$var]) ){
                    $text = self::$translation[$lang][$var];
                }elseif( isset(self::$translation['en'][$var]) ){
                    $text =  self::$translation['en'][$var];
                }
            }

            return $text;
        }

        public static function chats_settings_page(){
            wp_register_script('chats_settings_js', plugins_url(self::$plugin_name . '/assets/jquery.minicolors.min.js'), array(), self::$plugin_version, false);
            wp_register_style( 'chats_settings_css', plugins_url( self::$plugin_name . '/assets/jquery.minicolors.css' ) );
            wp_register_style( 'chats_settings_css2', plugins_url( self::$plugin_name . '/assets/admin.css' ) );

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'chats_settings_js' );
            wp_enqueue_style( 'chats_settings_css' );
            wp_enqueue_style( 'chats_settings_css2' );
        ?>
            <div class="wrap" id="chat_settings_page_over">
                <h2><?php echo self::trans('page_settings_title');?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( self::$optionParameters );
                    //do_settings_sections( self::$plugin_name );
                    $options        = self::plugin_options( 'get' );
                    $personalKey    = (string)get_option(ChatsAction::$optionKey, '');
                    ?>
                    <div id="chat_settings_page">
                        <div id="chats_color_tab" class="tab">
                            <table class="form-table">
                                <tr class="row_title">
                                    <th scope="row" colspan="2"><?php echo self::trans('settings_tab_color');?></th>
                                </tr>
                                <tr>
                                    <th style="width: 283px" scope="row"><?php echo self::trans('settings_panel_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[panel_background]" value="<?php echo esc_attr( @$options['panel_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_btn_finish_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[btn_finish_background]" value="<?php echo esc_attr( @$options['btn_finish_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_btn_finish_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[btn_finish_color]" value="<?php echo esc_attr( @$options['btn_finish_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_btn_finish_border_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[btn_finish_border_color]" value="<?php echo esc_attr( @$options['btn_finish_border_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_btn_expand_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[btn_expand_background]" value="<?php echo esc_attr( @$options['btn_expand_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_panel_border_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[panel_border_color]" value="<?php echo esc_attr( @$options['panel_border_color'] ); ?>" /></td>
                                </tr>
                                <tr class="row_border"><td colspan="2">&nbsp;</td></tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_body_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[body_background]" value="<?php echo esc_attr( @$options['body_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_admin_signature_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[admin_signature_color]" value="<?php echo esc_attr( @$options['admin_signature_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_admin_text_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[admin_text_color]" value="<?php echo esc_attr( @$options['admin_text_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_user_signature_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[user_signature_color]" value="<?php echo esc_attr( @$options['user_signature_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_user_text_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[user_text_color]" value="<?php echo esc_attr( @$options['user_text_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_time_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[time_color]" value="<?php echo esc_attr( @$options['time_color'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_message_border_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[message_border_color]" value="<?php echo esc_attr( @$options['message_border_color'] ); ?>" /></td>
                                </tr>
                                <tr class="row_border"><td colspan="2">&nbsp;</td></tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_write_panel_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[write_panel_background]" value="<?php echo esc_attr( @$options['write_panel_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_write_area_background');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[write_area_background]" value="<?php echo esc_attr( @$options['write_area_background'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_write_area_color');?>:</th>
                                    <td><input class="settings_colorpicker" type="text" name="<?php echo self::$optionParameters;?>[write_area_color]" value="<?php echo esc_attr( @$options['write_area_color'] ); ?>" /></td>
                                </tr>
                            </table>
                        </div>

                        <div id="chats_text_tab" class="tab">
                            <table class="form-table">
                                <tr class="row_title">
                                    <th scope="row" colspan="2"><?php echo self::trans('settings_tab_text');?></th>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_panel_title');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[panel_title]" value="<?php echo esc_attr( @$options['panel_title'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_btn_finish_text');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[btn_finish_text]" value="<?php echo esc_attr( @$options['btn_finish_text'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_admin_signature');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[admin_signature]" value="<?php echo esc_attr( @$options['admin_signature'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_user_signature');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[user_signature]" value="<?php echo esc_attr( @$options['user_signature'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_email_label');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[email_label]" value="<?php echo esc_attr( @$options['email_label'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_name_label');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[name_label]" value="<?php echo esc_attr( @$options['name_label'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_message_label');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[message_label]" value="<?php echo esc_attr( @$options['message_label'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_send_email');?>:</th>
                                    <td><input type="text" name="<?php echo self::$optionParameters;?>[send_email]" value="<?php echo esc_attr( @$options['send_email'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_enter_text_placeholder');?>:</th>
                                    <td><textarea name="<?php echo self::$optionParameters;?>[enter_text_placeholder]"><?php echo esc_attr( @$options['enter_text_placeholder'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_hello_message');?>:</th>
                                    <td><textarea name="<?php echo self::$optionParameters;?>[hello_message]"><?php echo esc_attr( @$options['hello_message'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_thank_message');?>:</th>
                                    <td><textarea name="<?php echo self::$optionParameters;?>[thank_message]"><?php echo esc_attr( @$options['thank_message'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_offline_message');?>:</th>
                                    <td><textarea name="<?php echo self::$optionParameters;?>[offline_message]"><?php echo esc_attr( @$options['offline_message'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_offline_thank_message');?>:</th>
                                    <td><textarea name="<?php echo self::$optionParameters;?>[offline_thank_message]"><?php echo esc_attr( @$options['offline_thank_message'] ); ?></textarea></td>
                                </tr>
                            </table>
                        </div>

                        <div id="chats_template_tab" class="tab">
                            <table class="form-table">
                                <tr class="row_title">
                                    <th scope="row" colspan="2"><?php echo self::trans('settings_tab_template');?></th>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_width');?>:</th>
                                    <td><input style="width:90px;" type="text" name="<?php echo self::$optionParameters;?>[width]" value="<?php echo esc_attr( @$options['width'] ); ?>" /> px</td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_position');?>:</th>
                                    <td>
                                        <select style="width: 185px;" name="<?php echo self::$optionParameters;?>[position]">
                                            <option <?php echo ( (empty($options['position']) or @$options['position'] == 'right') ? 'selected="selected"' : '');?> value="right"><?php echo self::trans('settings_position_right');?></option>
                                            <option <?php echo ( @$options['position'] == 'left' ? 'selected="selected"' : '');?> value="left"><?php echo self::trans('settings_position_left');?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_status');?>:</th>
                                    <td>
                                        <select style="width: 185px;" name="<?php echo self::$optionParameters;?>[status]">
                                            <option <?php echo ( @(int)$options['status'] == 1 ? 'selected="selected"' : '');?> value="1"><?php echo self::trans('settings_status_1');?></option>
                                            <option <?php echo ( @(int)$options['status'] == 2 ? 'selected="selected"' : '');?> value="2"><?php echo self::trans('settings_status_2');?></option>
                                            <option <?php echo ( @(int)$options['status'] == 3 ? 'selected="selected"' : '');?> value="3"><?php echo self::trans('settings_status_3');?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div id="chats_auth_tab" class="tab">
                            <table class="form-table">
                                <tr class="row_title">
                                    <th scope="row" colspan="2"><?php echo self::trans('settings_tab_auth');?></th>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo self::trans('settings_personal_key');?>:</th>
                                    <td>
                                        <input type="text" name="<?php echo self::$optionParameters;?>[personal_key]" value="<?php echo esc_attr($personalKey);?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="2"><?php echo self::trans('settings_personal_key_desc').' '.ChatsAction::$adminRequestSite;?></th>
                                </tr>
                            </table>
                        </div>

                        <?php submit_button(); ?>
                    </div>
                </form>
            </div>
            <script>
                jQuery(document).ready( function() {
                jQuery.each(jQuery('.settings_colorpicker'), function() {
                    jQuery(this).minicolors({
                        defaultValue: jQuery(this).attr('data-defaultValue') || '',
                        inline: jQuery(this).attr('data-inline') === 'true',
                        letterCase: jQuery(this).attr('data-letterCase') || 'lowercase',
                        position: jQuery(this).attr('data-position') || 'bottom left',
                        theme: 'default'
                    });

                });
                })
            </script>
        <?php
        }

        public static function register_settings(){
            register_setting( self::$optionParameters, self::$optionParameters, array('Chats', 'validate_settings') );
        }

        public static function validate_settings($input, $sendOptions = 1){
            $input = array_map('trim',$input);
            //filter
            $input['panel_background']          = self::validate_settings_color($input['panel_background']);
            $input['panel_border_color']        = self::validate_settings_color($input['panel_border_color']);
            $input['body_background']           = self::validate_settings_color($input['body_background']);
            $input['btn_finish_background']     = self::validate_settings_color($input['btn_finish_background']);
            $input['btn_finish_color']          = self::validate_settings_color($input['btn_finish_color']);
            $input['btn_finish_border_color']   = self::validate_settings_color($input['btn_finish_border_color']);
            $input['btn_expand_background']     = self::validate_settings_color($input['btn_expand_background']);
            $input['admin_signature_color']     = self::validate_settings_color($input['admin_signature_color']);
            $input['admin_text_color']          = self::validate_settings_color($input['admin_text_color']);
            $input['user_signature_color']      = self::validate_settings_color($input['user_signature_color']);
            $input['user_text_color']           = self::validate_settings_color($input['user_text_color']);
            $input['time_color']                = self::validate_settings_color($input['time_color']);
            $input['message_border_color']      = self::validate_settings_color($input['message_border_color']);
            $input['write_panel_background']    = self::validate_settings_color($input['write_panel_background']);
            $input['write_area_background']     = self::validate_settings_color($input['write_area_background']);
            $input['write_area_color']          = self::validate_settings_color($input['write_area_color']);

            $input['width']     = (int)$input['width'];
            $input['position']  = (!in_array($input['position'], array('right', 'left')) ? '' : $input['position']);
            $input['status']    = (!in_array((int)$input['status'], array(1, 2, 3)) ? 0 : (int)$input['status']);

            $input['admin_signature']           = self::validate_settings_text($input['admin_signature']);
            $input['user_signature']            = self::validate_settings_text($input['user_signature']);
            $input['hello_message']             = self::validate_settings_text($input['hello_message']);
            $input['panel_title']               = self::validate_settings_text($input['panel_title']);
            $input['enter_text_placeholder']    = self::validate_settings_text($input['enter_text_placeholder']);
            $input['btn_finish_text']           = self::validate_settings_text($input['btn_finish_text']);
            $input['thank_message']             = self::validate_settings_text($input['thank_message']);
            $input['offline_message']           = self::validate_settings_text($input['offline_message']);
            $input['email_label']               = self::validate_settings_text($input['email_label']);
            $input['name_label']                = self::validate_settings_text($input['name_label']);
            $input['message_label']             = self::validate_settings_text($input['message_label']);
            $input['send_email']                = self::validate_settings_text($input['send_email']);
            $input['offline_thank_message']     = self::validate_settings_text($input['offline_thank_message']);

            //values
            $input['panel_background']          = (empty($input['panel_background'])            ? self::$defaultOptions['panel_background']             : $input['panel_background']);
            $input['panel_border_color']        = (empty($input['panel_border_color'])          ? self::$defaultOptions['panel_border_color']           : $input['panel_border_color']);
            $input['body_background']           = (empty($input['body_background'])             ? self::$defaultOptions['body_background']              : $input['body_background']);
            $input['btn_finish_background']     = (empty($input['btn_finish_background'])       ? self::$defaultOptions['btn_finish_background']        : $input['btn_finish_background']);
            $input['btn_finish_color']          = (empty($input['btn_finish_color'])            ? self::$defaultOptions['btn_finish_color']             : $input['btn_finish_color']);
            $input['btn_finish_border_color']   = (empty($input['btn_finish_border_color'])     ? self::$defaultOptions['btn_finish_border_color']      : $input['btn_finish_border_color']);
            $input['btn_expand_background']     = (empty($input['btn_expand_background'])       ? self::$defaultOptions['btn_expand_background']        : $input['btn_expand_background']);
            $input['admin_signature_color']     = (empty($input['admin_signature_color'])       ? self::$defaultOptions['admin_signature_color']        : $input['admin_signature_color']);
            $input['admin_text_color']          = (empty($input['admin_text_color'])            ? self::$defaultOptions['admin_text_color']             : $input['admin_text_color']);
            $input['user_signature_color']      = (empty($input['user_signature_color'])        ? self::$defaultOptions['user_signature_color']         : $input['user_signature_color']);
            $input['user_text_color']           = (empty($input['user_text_color'])             ? self::$defaultOptions['user_text_color']              : $input['user_text_color']);
            $input['time_color']                = (empty($input['time_color'])                  ? self::$defaultOptions['time_color']                   : $input['time_color']);
            $input['message_border_color']      = (empty($input['message_border_color'])        ? self::$defaultOptions['message_border_color']         : $input['message_border_color']);
            $input['write_panel_background']    = (empty($input['write_panel_background'])      ? self::$defaultOptions['write_panel_background']       : $input['write_panel_background']);
            $input['write_area_background']     = (empty($input['write_area_background'])       ? self::$defaultOptions['write_area_background']        : $input['write_area_background']);
            $input['write_area_color']          = (empty($input['write_area_color'])            ? self::$defaultOptions['write_area_color']             : $input['write_area_color']);

            $input['width']     = (empty($input['width'])       ? self::$defaultOptions['width']    : $input['width']);
            $input['position']  = (empty($input['position'])    ? self::$defaultOptions['position'] : $input['position']);
            $input['status']    = (empty($input['status'])      ? self::$defaultOptions['status']   : $input['status']);

            $input['admin_signature']           = (empty($input['admin_signature'])         ? self::$defaultOptions['admin_signature']          : $input['admin_signature']);
            $input['user_signature']            = (empty($input['user_signature'])          ? self::$defaultOptions['user_signature']           : $input['user_signature']);
            $input['hello_message']             = (empty($input['hello_message'])           ? self::$defaultOptions['hello_message']            : $input['hello_message']);
            $input['panel_title']               = (empty($input['panel_title'])             ? self::$defaultOptions['panel_title']              : $input['panel_title']);
            $input['enter_text_placeholder']    = (empty($input['enter_text_placeholder'])  ? self::$defaultOptions['enter_text_placeholder']   : $input['enter_text_placeholder']);
            $input['btn_finish_text']           = (empty($input['btn_finish_text'])         ? self::$defaultOptions['btn_finish_text']          : $input['btn_finish_text']);
            $input['thank_message']             = (empty($input['thank_message'])           ? self::$defaultOptions['thank_message']            : $input['thank_message']);
            $input['offline_message']           = (empty($input['offline_message'])         ? self::$defaultOptions['offline_message']          : $input['offline_message']);
            $input['email_label']               = (empty($input['email_label'])             ? self::$defaultOptions['email_label']              : $input['email_label']);
            $input['name_label']                = (empty($input['name_label'])              ? self::$defaultOptions['name_label']               : $input['name_label']);
            $input['message_label']             = (empty($input['message_label'])           ? self::$defaultOptions['message_label']            : $input['message_label']);
            $input['send_email']                = (empty($input['send_email'])              ? self::$defaultOptions['send_email']               : $input['send_email']);
            $input['offline_thank_message']     = (empty($input['offline_thank_message'])   ? self::$defaultOptions['offline_thank_message']    : $input['offline_thank_message']);

            //special case for personal key
            if( isset($input['personal_key']) ){
                update_option(ChatsAction::$optionKey, $input['personal_key']);
                unset($input['personal_key']);
            }

            //send options to server
            if($sendOptions == 1){
                self::send_options($input);
            }

            return $input;
        }

        public static function validate_settings_color($color = ''){
            $color = trim($color);
            if( empty($color) or !preg_match('/^#[a-f0-9]{6}$/i', $color) ){
                return '';
            }
            return $color;
        }

        public static function validate_settings_text($text){
            $text = trim($text);
            $text = strip_tags($text);

            return $text;
        }

        /**
         * Activation hook
         *
         * Create tables if they don't exist and add plugin options
         *
         * @global    object $wpdb
         */
        public static function install(){
            global $wpdb;

            // Get the correct character collate
            $charset_collate = 'utf8';
            if ( ! empty( $wpdb->charset ) ) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if ( ! empty( $wpdb->collate ) ) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }

            if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $wpdb->base_prefix.self::$table_prefix.'message'.'" ' ) != $wpdb->base_prefix.self::$table_prefix.'message' ) {
                // Setup chat message table
                $sql = '
                    CREATE TABLE IF NOT EXISTS `'.$wpdb->base_prefix.self::$table_prefix.'messages` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_hash` varchar(10) NOT NULL DEFAULT "",
                        `user_ip` varchar(40) NOT NULL DEFAULT "",
                        `user_browser` varchar(255) NOT NULL DEFAULT "",
                        `user_name` varchar(50) NOT NULL DEFAULT "",
                        `message` varchar(1000) NOT NULL DEFAULT "",
                        `message_page` varchar(255) NOT NULL DEFAULT "",
                        `created` datetime DEFAULT NULL,
                        `message_type` tinyint(1) NOT NULL DEFAULT "0" COMMENT "1 - admin, 0 - user",
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET='.$charset_collate.' AUTO_INCREMENT=1
                ;';
                $wpdb->query( $sql );

                //set default options
                self::plugin_options('add');
            }else{
                $sql = 'TRUNCATE TABLE `'.$wpdb->base_prefix.self::$table_prefix.'messages`';
                $wpdb->query( $sql );
            }
        }

        /**
         * Deactivation hook
         *
         * @see        http://codex.wordpress.org/Function_Reference/register_deactivation_hook
         *
         * @global    object $wpdb
         */
        public static function deactivation(){
            global $wpdb;

            $sql = 'TRUNCATE TABLE `'.$wpdb->base_prefix.self::$table_prefix.'messages`';
            $wpdb->query($sql);
        }

        /**
         * Uninstall hook
         *
         * Remove tables and plugin options
         *
         * @global    object $wpdb
         */
        public static function uninstall(){
            global $wpdb;

            //remove table
            $sql = 'DROP TABLE IF EXISTS `'.$wpdb->base_prefix.self::$table_prefix.'messages`';
            $wpdb->query($sql);

            //remove options
            self::plugin_options('remove');
        }
    }

    class ChatsAction{

        public static $site             = 'http://www.wp-chat.com';
        public static $adminRequestSite = 'http://secure.wp-chat.com';
        public static $adminRequestUrl  = '/from-plugin';

        public static $optionKey        = 'chats_key';
        public static $personalKey      = '';

        public static $requestAnswers = array(
            0 => array('status' => 1, 'msg' => 'request success'),
            1 => array('status' => 1, 'msg' => 'connection success'),
            2 => array('status' => 0, 'msg' => 'connection failed'),
            3 => array('status' => 1, 'msg' => 'adding messages success'),
            4 => array('status' => 0, 'msg' => 'adding messages failed'),
            5 => array('status' => 1, 'msg' => 'ping success'),
            6 => array('status' => 0, 'msg' => 'incorrect request'),
            7 => array('status' => 0, 'msg' => 'plugin should be connected'),
            8 => array('status' => 0, 'msg' => 'wrong key'),
            9 => array('status' => 1, 'msg' => 'update options success'),
            10 => array('status' => 0, 'msg' => 'update options failed'),
        );

        public static $tagAnswer = 'chats_tag';

        /**
         * Listens incoming request
         *
         * Constructor for other methods
         */
        public static function init(){
            //check request
            if( !isset($_POST['chats_request']) or empty($_POST['chats_request']) ){
                return true;    //nothing necessary
            }

            self::$personalKey  = (string)get_option(self::$optionKey, '');
            $allowedHost        = @str_replace(array('http://','https://'),'',trim(self::$adminRequestSite,'/'));
            $allowedIP          = @gethostbyname( $allowedHost );

            $refererHost        = @str_replace(array('http://','https://'),'',trim($_SERVER["HTTP_REFERER"],'/'));
            $requestIP          = Chats::getUserIP();

            //check request ip
            $requestIP = Chats::getUserIP();
            if( empty($requestIP) or $requestIP != $allowedIP ){
                return true;    //wrong request ip
            }

            //check referer
            if( empty($refererHost) or $refererHost != $allowedHost ){
                return true;    //wrong referer
            }

            //check data, action, key
            $requestData    = self::convertString($_POST['chats_request'], 'decode');
            if( empty($requestData) or empty($requestData['action']) or !method_exists('ChatsAction','action_'.$requestData['action']) ){
                self::printAnswer(6);
            }

            //check key for actions
            $action     = (string)'action_'.$requestData['action'];
            $requestKey = @(string)$requestData['key'];
            if( $action == 'action_connect' ){
                if( !empty(self::$personalKey) ){
                    self::printAnswer( (self::$personalKey == $requestKey ? 1 : 2) );
                }
            }else{
                if( empty(self::$personalKey) ){
                    self::printAnswer(7);
                }
                if( empty($requestKey) or self::$personalKey != $requestKey ){
                    self::printAnswer(8);
                }
            }

            //run action
            self::$action($requestData);

            exit;
        }

        /**
         * Send message from user to admin
         */
        public static function requestServer($args){
            $url        = trim(self::$adminRequestSite,'/') . self::$adminRequestUrl;
            $postRes    = wp_remote_post( $url, $args );

            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );

                return false;
            }

            return true;
        }

        /**
         * Encode and decode array for hiding parameters from server
         *
         * @param $data
         * @param string $mode
         * @return array|mixed|string|void
         */
        public static function convertString($data, $mode = ''){
            $dataAnswer = array();

            if( empty($data) ){
                return $dataAnswer;
            }

            if($mode == 'decode'){
                $dataAnswer = @urldecode($data);
                $dataAnswer = @base64_decode($dataAnswer);
                $dataAnswer = @strrev($dataAnswer);
                $dataAnswer = @base64_decode($dataAnswer);
                $dataAnswer = @json_decode($dataAnswer,true);
            }

            if($mode == 'encode'){
                $dataAnswer = @json_encode($data);
                $dataAnswer = @base64_encode($dataAnswer);
                $dataAnswer = @strrev($dataAnswer);
                $dataAnswer = @base64_encode($dataAnswer);
                $dataAnswer = @urldecode($dataAnswer);
            }

            return $dataAnswer;
        }

        /**
         * Print answer of action
         * exit at the end
         * @param int $readyAnswer
         * @param array $data
         */
        public static function printAnswer($readyAnswer = 0, $data = array()){
            $answer = array(
                'msg_code'  => $readyAnswer,
                'msg'       => self::$requestAnswers[$readyAnswer]['msg'],
                'status'    => self::$requestAnswers[$readyAnswer]['status'],
                'data'      => $data,
            );
            $answer = self::convertString($answer, 'encode');
            print '<'.self::$tagAnswer.'>'.$answer.'</'.self::$tagAnswer.'>';

            exit;
        }

        /**
         * Connect plugin
         */
        protected static function action_connect(){
            $personalKey = md5(time().rand(1,10000).microtime().rand(1,10000));
            if( !add_option(self::$optionKey, $personalKey) ){
                self::printAnswer(2);
            }

            //send personal key to admin
            $requestVars = array(
                'body' => array(
                    self::$tagAnswer => self::convertString(array(
                        'action'    => 'connect',
                        'key'       => $personalKey,
                        'site'      => site_url(),
                        'pl'        => Chats::$plugin_name,
                        'pl_v'      => Chats::$plugin_version
                    ),'encode')
                )
            );
            $requestRes = self::requestServer($requestVars);

            if($requestRes){
                self::printAnswer(1);
            }else{
                self::printAnswer(2);
            }
        }

        /**
         * Read message of user by hash
         */
        protected static function action_read($data){
            $user_hash  = @$data['hash'];
            $user_ip    = @$data['ip'];
            $messages = Chats::read_messages($user_hash, $user_ip, '', 0, array('queryMode' => 'api_read'));
            self::printAnswer(0, array('messages' => $messages));
        }

        /**
         * Add message for user by hash
         */
        protected static function action_write($data){
            $user_hash      = @(string)$data['hash'];
            $user_ip        = @(string)$data['ip'];
            $message        = @(string)$data['message'];
            $created        = @(string)$data['created'];
            if( Chats::add_messages($user_hash, $user_ip, '', $message, $created, 1) ){
                self::printAnswer(3);
            }else{
                self::printAnswer(4);
            }
        }

        /**
         * Update ping plugin
         */
        protected static function action_ping(){
            self::printAnswer(5, array('plugin_version' => Chats::$plugin_version));
        }

        /**
         * Update settings plugin
         */
        protected static function action_update_options($data){
            $options = @(array)json_decode($data['options'],1);
            unset($options['personal_key']);
            if( !empty($options) ){
                $options = Chats::validate_settings($options, 0);
            }
            if( !empty($options) ){
                Chats::plugin_options('add',$options);
                self::printAnswer(9);
            }

            self::printAnswer(10);
        }

        /**
         * Read settings plugin
         */
        protected static function action_read_options(){

        }
    }
}

//run always for checking incoming request
add_action( 'init', array('ChatsAction', 'init') );

//init plugin on frontend
add_action( 'wp_head', array('Chats', 'wp_head') );
add_action( 'wp_footer', array('Chats', 'wp_footer') );

//init plugin on admin
add_action('admin_notices', array('Chats', 'admin_notices'));
add_action('admin_menu', array('Chats', 'admin_menu'));

//listen incoming request from js
add_action( 'wp_ajax_jsChatsProcess', array( 'Chats', 'js_process' ) );
add_action( 'wp_ajax_nopriv_jsChatsProcess', array( 'Chats', 'js_process' ) );

//manipulation by environments for plugin
register_activation_hook( __FILE__, array( 'Chats', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Chats', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Chats', 'uninstall' ) );