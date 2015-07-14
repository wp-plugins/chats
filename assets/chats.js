(function($) {
    var chats;

    $( document ).ready(function() {
        if(!chats){
            chats = new Chats();
            chats.init();
        }
    });

    function Chats(){
        var thisChat = this;
        var thisChatListener;

        var settings = {
            'tagPrefix' : 'chats',
            'chatHash'  : '',
            'translation' : {
                'title' : 'Chat with us',
                'enter_message' : 'Enter your message ...',
                'finish' : 'Finish',
                'user_name' : 'You',
                'powered_by' : 'Powered by'
            },
            'admin_name': 'Admin',
            'start_message': 'Hello. Do you have any questions?',
            'user_name': 'You',
            'messagesHtml' : '', //html of messages
            'messages' : {}, //loaded array of messages
            'autoFinishTimeout': 900, //finish chat after 15 min without any action
            'startUpOpen': 0,    //open chat after loading
            'ajaxAction' : 'jsChatsProcess'
        };

        var blocks = {};

        this.setup = function(vars){
            var newChat = 0;

            //check hash and cookie or prepare new
            var hash                = $.cookie(chats_parameters.cookie_prefix);
            settings.startUpOpen    = $.cookie(chats_parameters.cookie_prefix+'_status');
            if( !hash || hash == '' ){
                hash = this.randHash();
            }
            settings.chatHash = hash;
            $.cookie(chats_parameters.cookie_prefix, hash, {path:'/'});

            //seems chat should be continued
            //if(settings.startUpOpen == 1){
                //try load messages for hash
                this.loadMessages('user_last', 50);
            //}

            //prepare messages
            this.prepareMessages(1);
        };

        this.postSetup = function(vars){
            //blocks
            blocks.chatBlock    = $('.'+settings.tagPrefix+'_container');
            blocks.chatBody     = $('.'+settings.tagPrefix+'_body');
            blocks.chatTitle    = $('.'+settings.tagPrefix+'_title');
            blocks.chatWrite    = $('.'+settings.tagPrefix+'_write');
            blocks.textarea     = blocks.chatBlock.find('textarea');
            blocks.enterBut     = blocks.chatBlock.find('.'+settings.tagPrefix+'_btn_enter');
            blocks.finisBut     = blocks.chatBlock.find('.'+settings.tagPrefix+'_btn_finish');
            blocks.chatBlockBtn = blocks.chatTitle.find('.'+settings.tagPrefix+'_btn');

            //listen new messages
            thisChat.reloadMessages();

            //open chat
            if(settings.startUpOpen == 1 ){
                this.animateChat('open');
            }

            //events
            blocks.textarea.on('keydown', function(e){
                if(e.which == 13){ //enter
                    thisChat.sendMessage();
                    return false;
                }
            });
            blocks.enterBut.on('click',function(){
                thisChat.sendMessage();
            });
            blocks.finisBut.on('click',function(){
                thisChat.finish();
            });
            blocks.chatBlockBtn.on('click',function(){
                if( blocks.chatBlock.hasClass('active') ){
                    thisChat.animateChat('close');
                }else{
                    thisChat.animateChat('open');
                }
            });
        };

        this.animateChat = function(mode){
            if(mode == 'close'){
                blocks.chatBlock.animate({
                    height: blocks.chatTitle.outerHeight()
                }, 500, function() {
                    blocks.chatBlock.removeClass('active');
                    //blocks.chatBlockBtn.html('+');

                    settings.startUpOpen = 0;
                    $.cookie(chats_parameters.cookie_prefix+'_status', settings.startUpOpen, {path:'/'});
                });
            }

            if(mode == 'open'){
                blocks.chatBlock.animate({
                    height: (blocks.chatTitle.outerHeight() + blocks.chatBody.outerHeight() + blocks.chatWrite.outerHeight())
                }, 500, function() {
                    blocks.chatBlock.addClass('active');
                    //blocks.chatBlockBtn.html('&ndash;');

                    settings.startUpOpen = 1;
                    $.cookie(chats_parameters.cookie_prefix+'_status', settings.startUpOpen, {path:'/'});
                });
            }
        };

        this.loadMessages = function(queryMode, countMessage, successCallback, errorCallback){
            $.ajax({
                type: "POST",
                url: chats_parameters.request_url,
                data: {
                    'mode'          : 'read',
                    'action'        : settings.ajaxAction,
                    'queryMode'     : queryMode,
                    'countMessage'  : countMessage
                },
                dataType: "json",
                success: function(data){
                    if(data && data.messages.length > 0){
                        $.each(data.messages, function(ind,val){
                            settings.messages[val.message_id] = val;
                        });
                        thisChat.prepareMessages();
                    }
                    if(successCallback){
                        successCallback();
                    }
                },
                error: function(){
                    if(errorCallback){
                        successCallback();
                    }
                }
            });
        };

        this.sendMessage = function(){
            var text =  $.trim(blocks.textarea.val());
            blocks.textarea.val('');

            if( text != '' ){
                //var d = new Date();
                //var randID = thisChat.randHash();
                //settings.messages[''] = {name: settings.user_name, text:text, time: (d.getHours()+':'+d.getMinutes()), message_id:randID};
                //thisChat.prepareMessages();

                blocks.chatBlock.addClass('loading');

                $.ajax({
                    type: "POST",
                    url: chats_parameters.request_url,
                    data: {
                        'mode'          : 'add',
                        'action'        : settings.ajaxAction,
                        'message'       : text,
                        'message_page'  : window.location.href
                    },
                    dataType: "json",
                    cache: false,
                    success: function(data){
                        if(data && data.result == 1){
                            thisChat.loadMessages('user_last',1,function(){
                                blocks.chatBlock.removeClass('loading');

                                //kill tmp message
                                //blocks.chatBody.find('#message_'+randID).remove();
                            });
                        }
                    }
                });
            }
        };

        this.prepareMessages = function(addDefault){
            //add default message
            if(addDefault == 1){
                this.messageTemplate(settings.admin_name, settings.start_message, '', '0','1');
            }

            if( Object.keys(settings.messages).length > 0 ){
                settings.messagesHtml = '';
                var newMessages = 0;

                $.each(settings.messages, function(ind,val){
                    if( blocks.chatBody.find('#message_'+val.message_id).length == 0){
                        thisChat.messageTemplate(val.name, val.text, val.time, val.message_id, val.type);
                        newMessages = 1;
                    }
                });

                if(newMessages == 1){
                    blocks.chatBody.append( settings.messagesHtml );
                    blocks.chatBody.scrollTop(blocks.chatBody.prop("scrollHeight"));
                }
            }
        };

        this.randHash = function() {
            var result          = '';
            var words           = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
            var max_position    = words.length - 1;
            var position        = 0;
            for( i = 0; i < 10; ++i ) {
                position = Math.floor ( Math.random() * max_position );
                result = result + words.substring(position, position + 1);
            }
            return result;
        };

        this.escapeHtml = function(text){
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            text = text.replace(/[&<>"']/g, function(m) { return map[m]; });
            text = text.replace('&lt;br /&gt;','<br />');

            return text;
        };

        this.template = function(){
            return '' +
                '<div id="'+settings.chatHash+'" class="'+settings.tagPrefix+'_container">' +
                    '<div class="'+settings.tagPrefix+'_title">' +
                        '<span class="'+settings.tagPrefix+'_text">'+settings.translation.title+'</span>' +
                        '<span class="'+settings.tagPrefix+'_btn">&nbsp;</span>' +   //&ndash;
                        '<span class="'+settings.tagPrefix+'_btn_finish">'+settings.translation.finish+'</span>' +
                    '</div>' +
                    '<div class="'+settings.tagPrefix+'_body">'+settings.messagesHtml+'</div>' +
                    '<div class="'+settings.tagPrefix+'_write">' +
                        '<div class="'+settings.tagPrefix+'_top_write">' +
                            //'<span class="'+settings.tagPrefix+'_btn_finish">'+settings.translation.finish+'</span>' +
                            '<span class="'+settings.tagPrefix+'_preloader">&nbsp;</span>' +
                        '</div>' +
                        '<div class="'+settings.tagPrefix+'_middle_write">' +
                            '<textarea placeholder="'+settings.translation.enter_message+'"></textarea>' +
                            '<span class="'+settings.tagPrefix+'_btn_enter">&nbsp;</span>' +
                        '</div>' +
                        '<div class="'+settings.tagPrefix+'_bottom_write"><a target="_blank" href="http://wp-chat.com">'+settings.translation.powered_by+' <b>wp-chat.com</b></a></div>' +
                    '</div>' +
                '</div>' +
            '';
        };

        this.messageTemplate = function(name, text, time, id, type){
            var id      = (!id ? '' : id);
            var type    = (!type ? 0 : type);
            var time = (!time ? '' : time);
            var text = (!text ? '' : text);
            var name = (!name ? settings.translation.user_name : name);
            settings.messagesHtml += '<div id="message_'+id+'" class="'+settings.tagPrefix+'_message message_type_'+type+'">' +
                '<span class="'+settings.tagPrefix+'_name">'+thisChat.escapeHtml(name)+'</span>' +
                '<span class="'+settings.tagPrefix+'_time">'+thisChat.escapeHtml(time)+'</span>' +
                '<span class="'+settings.tagPrefix+'_text">'+thisChat.escapeHtml(text)+'</span>' +
            '</div>';
        };

        this.init = function(mode, vars){
            this.setup(vars);

            $(document.body).append(this.template());

            this.postSetup();
        };

        this.finish = function(){
            //remove all messages from chat
            $.each(blocks.chatBody.children(),function(ind,val){
                if( ind > 0){
                    $(val).remove();
                }
            });

            thisChat.animateChat('close');

            //remove all messages from db
            $.ajax({
                type: "POST",
                url: chats_parameters.request_url,
                data: {
                    'mode'          : 'finish',
                    'action'        : settings.ajaxAction,
                    'message_page'  : window.location.href
                },
                dataType: "json",
                cache: false,
                success: function(data){
                    //set new hash
                    settings.chatHash = thisChat.randHash();
                    $.cookie(chats_parameters.cookie_prefix, settings.chatHash);

                    settings.messages = {};

                    //set close status for preventing opening after reloading page
                    //settings.startUpOpen = 0;
                    //$.cookie(chats_parameters.cookie_prefix+'_status',1, {path:'/'});
                }
            });

            //close chat
        };

        this.reloadMessages = function(){
            var pause = (settings.startUpOpen == 1 ? 2000 : 15000);
            thisChatListener = setTimeout(function(){
                thisChat.loadMessages('user_last',10,function(){thisChat.reloadMessages()});
            },pause);
        }
    }
})(jQuery);

//cookie
(function ($) {
    jQuery.cookie = function(name, value, options) {
        if (typeof value != 'undefined') { // name and value given, set cookie
            options = options || {};
            if (value === null) {
                value = '';
                options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
            }
            // NOTE Needed to parenthesize options.path and options.domain
            // in the following expressions, otherwise they evaluate to undefined
            // in the packed version for some reason...
            var path = options.path ? '; path=' + (options.path) : '';
            var domain = options.domain ? '; domain=' + (options.domain) : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else { // only name given, get cookie
            var cookieValue = null;
            if (document.cookie && document.cookie != '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    // Does this cookie string begin with the name we want?
                    if (cookie.substring(0, name.length + 1) == (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    };
})(jQuery);