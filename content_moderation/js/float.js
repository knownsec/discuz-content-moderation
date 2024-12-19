jQuery.noConflict();
// 获取当前 script 标签
const scripts = document.getElementsByTagName('script');
const cs = scripts[scripts.length - 1];
const currentScript = document.currentScript || cs;

// 获取 src 属性
let src = currentScript.src;
console.log("query src =========== ", src);

src = src.replace(/&amp;/g, '&');

// 解析 URL 参数
const urlParams = new URLSearchParams(src.split('?')[1]);
console.log("query urlParams =========== ", urlParams.toString());


console.log("query fid =========== ", urlParams.get('fid'));
console.log("query formhash =========== ", urlParams.get('formhash'));
console.log("query key =========== ", urlParams.get('key'));
console.log("query tid =========== ", urlParams.get('tid'));

jQuery(function ($) {

    // 关闭弹窗
    function closeDialog(buttonId, callback) {
        const button = document.getElementById(buttonId); // 获取按钮
        // 定义事件处理函数
        const handleClick = function () {
            button.removeEventListener('click', handleClick); // 移除事件监听

            // 如果提供了回调函数，则执行它
            if (typeof callback === 'function') {
                callback();
            }
        };

        button.addEventListener('click', handleClick);
        setTimeout(() => {
            if (button) {
                button.click(); // 模拟点击按钮
            }
        }, 3000);
    }
    // 浮窗回复
    function floatReply(e) {
        console.log("************* postNew **************");

        e.preventDefault();
        var message = $("#postmessage").val();
        var value = $('input[name="noticetrimstr"]').val();
        $.ajax({
            type: "post",
            url: 'plugin.php?id=content_moderation:ajax_examine_post',
            dataType: "json",
            data: {
                subject: '',
                message: message,
                fid: urlParams.get('fid'),
                tid: urlParams.get('tid'),
                formhash: urlParams.get('formhash'),
                quote: value,
                pType: 2, // 1发帖 2回帖
            },
            success: function (response) {
                console.log(response);
                if (response.code != 200) {
                    showDialog(response.msg, 'error', 'error Tips');
                    if (response.handleMethod == 1) {
                        closeDialog('fwin_dialog_submit', function () {
                            $("#postmessage").val('');
                            $('input[name="noticetrimstr"]').val('');
                        });
                    }
                    return false;
                }
                if (response.examine_text) { // 替换正文
                    $("#postmessage").val(response.examine_text);
                }
                // 提交表单
                $('#postform').submit();
            },
            error: function (error) {
                showDialog('帖子内容中含有非法字符。或系统内部错误，请联系管理员解决', 'error', 'error Tips');
                return false;
            }
        });
    }

    $("#postsubmit").click(function (e) {
        var ps = $("#postmessage") && $("#postmessage").val && $("#postmessage").val()
        // 浮窗回复
        if (ps) {
            floatReply(e);
            return
        }
    })
});