<?php
/*
Plugin Name: Chats
Plugin URI: http://www.wp-chat.com
Description: Web Page Chats for Websites
Version: 1.0
Author: wp-chat
Author URI: http://www.wp-chat.com
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'Chats' ) ) {
    class Chats
    {
        public static $plugin_name      = 'chats';
        public static $plugin_version   = '1.0';
        public static $table_prefix     = 'chats_';
        public static $optionParameters = 'chats_options';
        public static $defaultOptions   = array(
            'chat_width' => 300,
        );
        public static $cookiePrefix     = 'chats_hash';

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

            if( empty($mode) or empty($hash) or !in_array($mode, array('add', 'read', 'finish')) ){
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

            exit;
        }

        /**
         * Init chat. Should be started at all pages on frontend
         */
        public static function wp_head(){
            $personalKey = (string)get_option(ChatsAction::$optionKey, '');
            if(empty($personalKey)){
                return true;
            }

            //only for frontend
            if ( !is_admin() ) {
                wp_register_script('chats_js', plugins_url(self::$plugin_name . '/assets/chats.js'), array(), self::$plugin_version, false);
                wp_register_style( 'chats_css', plugins_url( self::$plugin_name . '/assets/chats.css' ) );

                //add parameters in js
                $js_parameters = array(
                    'site_url'      => site_url(),
                    'request_url'   => site_url() . '/wp-admin/admin-ajax.php',
                    'cookie_prefix' => self::$cookiePrefix
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
                global $status, $page, $s;
                $context    = $status;
                $plugin     = 'chats/chats.php';
                $nonce      = wp_create_nonce('deactivate-plugin_' . $plugin);
                $actions    = 'plugins.php?action=deactivate&amp;plugin=' . urlencode($plugin) . '&amp;plugin_status=' . $context . '&amp;paged=' . $page . '&amp;s=' . $s  . '&amp;_wpnonce=' . $nonce;

                $personaKey     = (string)get_option(ChatsAction::$optionKey, '');
                $pluginStatus   = is_plugin_active($plugin);
                if( !empty($personaKey) or !$pluginStatus ){
                    return;
                }
                $msg = '';
                echo '<div style="height:50px;line-height:50px;font-size:16px;font-weight:bold;" class="notice-warning notice">To use "chats" plugin, please add this site to <a target="_blank" href="'.ChatsAction::$site.'">your account</a> at '.str_replace('http://','',ChatsAction::$site).' or <a href="'.$actions.'">deactivate</a> Chats plugin.</div>';
            }
        }

        /**
         * Plugin options
         */
        public static function plugin_options($mode = 'add', $options = array()){
            if(!empty($options)){
                $options = self::$defaultOptions;
            }

            if($mode == 'add'){
                update_option(self::$optionParameters, $options);
            }

            if( $mode == 'get' ){
                return get_option(self::$optionParameters, $options);
            }

            if( $mode == 'remove' ){
                delete_option( self::$optionParameters );
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
         * Read messages from db
         */
        public static function read_messages($hash, $ip, $browser = '', $currentUserID = 0, $parameters = array()){
            global $wpdb;

            $list = array();

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
                            'name'          => ($v_item->message_type == 0 ? 'You' : 'Admin'),
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

                //set options
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
        protected static function action_update_options(){

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

//listen incoming request from js
add_action( 'wp_ajax_jsChatsProcess', array( 'Chats', 'js_process' ) );
add_action( 'wp_ajax_nopriv_jsChatsProcess', array( 'Chats', 'js_process' ) );

//manipulation by environments for plugin
register_activation_hook( __FILE__, array( 'Chats', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Chats', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Chats', 'uninstall' ) );