function popupAuth(button, link){
    var button = jQuery(button);

    jQuery.arcticmodal({
        type: 'ajax',
        url: link,
        ajax: {
            type: 'get',
            cache: false,
            dataType: 'html',
            success: function(data, el, responce) {
                var h = jQuery('<div id="chats_popup_form_over" class="box-modal" style="display:block;">'+responce+'</div>');
                data.body.html(h);
            }
        },
        beforeClose: function(data, el) {

        }
    });
}

function processAuth(button){
    var button = jQuery(button);
    var form = button.closest('form');
    var send = 1;
    if( form.find('.auth_type_field:checked').val() == 2 ){
        if( jQuery.trim(form.find('.auth_email_field').val()) == '' ){
            form.find('.auth_email_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_email_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password1_field').val()) == '' ){
            form.find('.auth_password1_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password1_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password2_field').val()) == '' ){
            form.find('.auth_password2_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password2_field').removeClass('invalid');
        }
    }else if( form.find('.auth_type_field:checked').val() == 1 ){
        if( jQuery.trim(form.find('.auth_email_field').val()) == '' ){
            form.find('.auth_email_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_email_field').removeClass('invalid');
        }
        if( jQuery.trim(form.find('.auth_password1_field').val()) == '' ){
            form.find('.auth_password1_field').addClass('invalid');
            send = 0;
        }else{
            form.find('.auth_password1_field').removeClass('invalid');
        }
    }else{
        send = 0;
    }

    if(send == 1){
        button.css('visibility','hidden');
        jQuery.ajax({
            url: form.attr('action'),
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            success: function(data) {
                if(data){
                    if(data.msg){
                        form.find('#chat_answer_msg').remove();
                        var message = '<div id="chat_answer_msg">'+data.msg+'</div>';
                        jQuery( form ).prepend( message );
                    }

                    if(data.status == 1){
                        form.find('input[type="text"]').val('');
                        form.find('input[type="password"]').val('');
                    }
                    if(data.redirect_url && data.redirect_url != ''){
                        window.location.href = data.redirect_url;
                    }
                }
                button.css('visibility','visible');
            }
        });
    }
}

function popupAuthType(button,type){
    var button = jQuery(button);
    var form = button.closest('form');
    if(type == 1){
        form.find('#tr_confirm_password').css('visibility','hidden');
        form.find('#auth_but_login').css('display','inline-block');
        form.find('#auth_but_register').css('display','none');
    }else{
        form.find('#tr_confirm_password').css('visibility','visible');
        form.find('#auth_but_login').css('display','none');
        form.find('#auth_but_register').css('display','inline-block');
    }
}

function showFullContent(button){
    var button = jQuery(button);
    if(button.hasClass('active')){
        button.removeClass('active');
        button.closest('table').find('tbody').css('display','none');
    }else{
        button.addClass('active');
        button.closest('table').find('tbody').css('display','table-row-group');
    }
}
