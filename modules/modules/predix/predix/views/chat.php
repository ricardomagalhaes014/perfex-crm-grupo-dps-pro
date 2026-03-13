<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .chat-body {
        display: flex;
        justify-content: center;
        align-items: center;
        --body-bg: #202123;
        --msger-bg: #444654;
        --border: 2px solid #1e1e1e;
        --left-msg-bg: #8b8da9;
        --right-msg-bg: #16171c;
        font-family: Helvetica, sans-serif;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        box-sizing: inherit;
        width: 100%;
        height: 100%;
    }

    .msger {
        display: flex;
        flex-flow: column wrap;
        justify-content: space-between;
        width: 100%;
        margin: 25px 10px;
        height: calc(100% - 50px);
        border: var(--border);
        border-radius: 5px;
        background: var(--msger-bg);
        box-shadow: 0 15px 15px -5px rgba(0, 0, 0, 0.2);
    }

    .msger-header {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        border-bottom: var(--border);
        background: #2b2c34;
        color: #d9d9d9;
    }

    .msger-chat {
        overflow-y: auto;
        padding: 10px;
        height: 79vh
    }

    .msger-chat::-webkit-scrollbar {
        width: 6px;
    }

    .msger-chat::-webkit-scrollbar-track {
        background: #2b2c34;
    }

    .msger-chat::-webkit-scrollbar-thumb {
        background: #444654;
    }

    .msg {
        display: flex;
        align-items: flex-end;
        margin-bottom: 10px;
    }

    .msg:last-of-type {
        margin: 0;
    }

    .msg-img {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        border-radius: 50%;
    }

    .msg-bubble {
        max-width: 450px;
        padding: 15px;
        border-radius: 15px;
        background: var(--left-msg-bg);
        font-size: 15px;
        line-height: 1.4;
        color: #fff;
    }

    .msg-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .msg-info-name {
        margin-right: 10px;
        font-weight: bold;
    }

    .msg-info-time {
        font-size: 0.85em;
    }

    .left-msg .msg-bubble {
        border-bottom-left-radius: 0;
    }

    /*this css style is used on line 284*/
    .right-msg {
        flex-direction: row-reverse;
    }

    .right-msg .msg-bubble {
        background: var(--right-msg-bg);
        color: #fff;
        border-bottom-right-radius: 0;
    }

    .right-msg .msg-img {
        margin: 0 0 0 10px;
    }

    .msger-inputarea {
        display: flex;
        padding: 10px;
        border-top: var(--border);
        background: #2b2c34;
    }

    .msger-inputarea * {
        padding: 10px;
        border: none;
        border-radius: 3px;
        font-size: 1em;
    }

    .msger-input {
        flex: 1;
        background: #444654;
        color: #d9d9d9;
    }

    .msger-send-btn {
        margin-left: 10px;
        background: #69707a;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
    }

    .msger-send-btn:hover {
        background: #4c5563;
    }

    #delete-button {
        background: none !important;
        border: none;
        padding: 0 !important;
        font-family: arial, sans-serif;
        color: rgb(255, 255, 255);
        text-decoration: underline;
        cursor: pointer;
    }
</style>
<div id="wrapper">
    <div class="content chat-body">
        <div class="row col-md-12">
            <?php
            if (empty(get_option('predix_openai_secret_key'))) {
                ?>
                <div class="col-md-12">
                    <div class="alert alert-danger">
                        <?php echo _l('predix_chat_notification'); ?>
                    </div>
                </div>
                <?php
            }
            ?>
            <section class="msger">
                <header class="msger-header">
                    <div class="msger-header-title">
                        <i class="fas fa-comment-alt"></i> <?php echo _l('predix') ?>
                    </div>
                    <div class="msger-header-options">
                        <button id="delete-button"><?php echo _l('predix_delete_chat_history'); ?></button>
                    </div>
                </header>

                <main class="msger-chat" id="chat-window">
                </main>

                <form class="msger-inputarea">
                    <input class="msger-input" placeholder="<?php echo _l('predix_enter_your_message'); ?>" required>
                    <button type="submit" class="msger-send-btn"><?php echo _l('predix_send_message'); ?></button>
                </form>
            </section>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
    (function ($) {
        "use strict";

        $(document).ready(function () {
            getHistory();

            const msgerForm = $(".msger-inputarea");
            const msgerInput = $(".msger-input");
            const msgerChat = $(".msger-chat");

            const BOT_IMG = "<?php echo module_dir_url('predix', 'assets/images/chatbot.png') ?>";
            const PERSON_IMG = '<?php echo staff_profile_image_url(get_staff_user_id()); ?>';
            const PERSON_NAME = "You";

            function deleteChatHistory() {
                if (!confirm("Are you sure? Your Session and History will delete for good.")) {
                    return false;
                }

                $.ajax({
                    url: '<?php echo admin_url('predix/deleteUserChatHistory')?>',
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        location.reload(); // Reload the page to update the chat history table
                    },
                    error: function (error) {
                        console.error('Error deleting chat history: ' + error.statusText);
                    }
                });
            }

            const deleteButton = $('#delete-button');
            deleteButton.on('click', function (event) {
                event.preventDefault();
                deleteChatHistory();
            });

            msgerForm.on("submit", function (event) {
                event.preventDefault();

                const msgText = msgerInput.val();
                if (!msgText) return;

                <?php
                if (get_option('predix_use_streams_for_chat') == 1) {
                ?>
                    sendMsg(msgText);
                <?php
                } else {
                ?>
                    sendMsgNoStream(msgText);
                <?php
                }
                ?>
            });

            function getHistory() {
                $.ajax({
                    url: '<?php echo admin_url('predix/getUserChatHistory')?>',
                    type: 'GET',
                    success: function (chatHistory) {
                        chatHistory = JSON.parse(chatHistory);

                        for (const row of chatHistory) {
                            appendMessage(PERSON_NAME, PERSON_IMG, "right", row.human_message);
                            appendMessage('<?php echo _l('predix'); ?>', BOT_IMG, "left", row.ai_response, "");
                        }
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            }

            function appendMessage(name, img, side, text, id) {
                // Sanitize user-generated content
                const safeName = document.createTextNode(name);
                const safeImg = document.createTextNode(img);
                const safeText = document.createTextNode(text);
                const safeId = document.createTextNode(id);

                // Create the HTML template with the sanitized content
                const msgHTML = `
      <div class="msg ${side}-msg">
        <div class="msg-img" style="background-image: url(${safeImg.nodeValue})"></div>
        <div class="msg-bubble">
          <div class="msg-info">
            <div class="msg-info-name">${safeName.nodeValue}</div>
            <div class="msg-info-time">${formatDate(new Date())}</div>
          </div>
          <div class="msg-text" id=${safeId.nodeValue}>${safeText.nodeValue}</div>
        </div>
      </div>
    `;

                // Insert the sanitized HTML template into the DOM
                msgerChat.append(msgHTML);
                msgerChat.scrollTop(msgerChat.prop("scrollHeight"));
            }

            function sendMsg(msg) {
                $('#msgerSendBtn').prop('disabled', true);

                $.ajax({
                    url: '<?php echo admin_url('predix/addUserChatMessage')?>',
                    type: 'POST',
                    data: {
                        message: msg
                    },
                    success: function (data) {
                        data = JSON.parse(data);

                        appendMessage(PERSON_NAME, PERSON_IMG, "right", data.message);
                        msgerInput.val("");

                        var uuid = uuidv4();
                        var eventSource = new EventSource(`<?php echo admin_url('predix/generateChatAiResponse/')?>${data.id}`);

                        appendMessage('<?php echo _l('predix'); ?>', BOT_IMG, "left", "", uuid);
                        var div = document.getElementById(uuid);

                        eventSource.onmessage = function (e) {
                            if (e.data == "[DONE]") {
                                $('#msgerSendBtn').prop('disabled', false);
                                eventSource.close();
                            } else {
                                var txt = JSON.parse(e.data).choices[0].delta.content;
                                if (txt !== undefined) {
                                    div.innerHTML += txt.replace(/(?:\r\n|\r|\n)/g, '<br>');
                                }
                            }
                        };

                        eventSource.onerror = function (e) {
                            alert('Check if OpenAI API Key is setup and if it is valid');
                            $('#msgerSendBtn').prop('disabled', false);
                            console.log(e);
                            eventSource.close();
                        };
                    },
                    error: function (error) {
                        alert('Failed');
                        console.error(error);
                    }
                });
            }

            function sendMsgNoStream(msg) {
                $('#msgerSendBtn').prop('disabled', true);

                $.ajax({
                    url: '<?php echo admin_url('predix/addUserChatMessage')?>',
                    type: 'POST',
                    data: {
                        message: msg
                    },
                    success: function (data) {
                        data = JSON.parse(data);

                        appendMessage(PERSON_NAME, PERSON_IMG, "right", data.message);
                        msgerInput.val("");

                        contactBot(data.id);
                    },
                    error: function (error) {
                        alert('Failed');
                        console.error(error);
                    }
                });
            }

            function contactBot(id) {

                var uuid = uuidv4();
                appendMessage('<?php echo _l('predix'); ?>', BOT_IMG, "left", '...', uuid);

                $.ajax({
                    url: `<?php echo admin_url('predix/generateChatAiResponse/')?>${id}`,
                    type: 'POST',
                    success: function (data) {
                        data = JSON.parse(data);

                        var div = document.getElementById(uuid);

                        if (data.ai_response !== undefined) {
                            div.innerHTML = '';
                            div.innerHTML += data.ai_response;
                        }
                        msgerInput.val("");

                    },
                    error: function (error) {
                        alert('Failed');
                        console.error(error);
                    }
                });
            }

            function formatDate(date) {
                const h = "0" + date.getHours();
                const m = "0" + date.getMinutes();

                return `${h.slice(-2)}:${m.slice(-2)}`;
            }

            function uuidv4() {
                return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function (c) {
                    return (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16);
                });
            }
        });
    })(jQuery);

</script>

