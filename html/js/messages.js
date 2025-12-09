var messages = {
    all: [],
    last: 0,
    load: function () {
        $.post('index.php?method=message&json=1', {}, function(data) {
            resp = $.parseJSON(data);
            if (resp.status == 'ok') {
                messages.all = resp.data.messages;
                for (var i in messages.all) {
                    if (messages.last*1 < messages.all[i].id*1) {
                        messages.last = messages.all[i].id;
                    }
                }
                messages.show_tab();
            } else {
                window.alert(resp.error);
            }
        });
    },
    show_tab: function () {
        var type = '';
        if ($('.message-window-tab-active').attr('id') == 'mw-all-messages') {
            type = 'all';
        } else if ($('.message-window-tab-active').attr('id') == 'mw-system-messages') {
            type = 'system';
        } else if ($('.message-window-tab-active').attr('id') == 'mw-chat-messages') {
            type = 'chat';
        }
        $('#message-window-lines').empty();
        for (var i in this.all) {
            if (type == 'all' || this.all[i].type == type) {
                var from = 'system';
                if (this.all[i].from) {
                    from = this.all[i].from;
                }
                $('#message-window-lines').append('<b>' + from + '</b>:' + this.all[i].text + '<br>');
            }
        }
        $('#message-window-lines').scrollTop(10000);
    }
};
map.status_timer = setInterval(function () {
    $.post('index.php?method=message&json=1', {last: messages.last}, function(data) {
        resp = $.parseJSON(data);
        if (resp.status == 'ok') {
            if (map.turn_status != resp.data.turn_status) {
                this.turn_status = resp.data.turn_status;
                map.show_cell_info();
            }
            if (resp.data.messages.length > 0) {
                var type = '';
                if ($('.message-window-tab-active').attr('id') == 'mw-all-messages') {
                    type = 'all';
                } else if ($('.message-window-tab-active').attr('id') == 'mw-system-messages') {
                    type = 'system';
                } else if ($('.message-window-tab-active').attr('id') == 'mw-chat-messages') {
                    type = 'chat';
                }
                for (var i in resp.data.messages) {
                    var msg = resp.data.messages[i];
                    messages.all.push(msg);
                    if (messages.last*1 < msg.id*1) {
                        messages.last = msg.id;
                    }
                    if (type == 'all' || msg.type == type) {
                        var from = 'system';
                        if (msg.from) {
                            from = msg.from;
                        }
                        $('#message-window-lines').append('<b>' + from + '</b>:' + msg.text + '<br>');
                    }
                }
                $('#message-window-lines').scrollTop(10000);
            }
        }
    });
}, 5000);
$(document).on('click', '.message-window-tab', function(e) {
    $('.message-window-tab').removeClass('message-window-tab-active');
    $(e.currentTarget).addClass('message-window-tab-active');
    messages.show_tab();
});
