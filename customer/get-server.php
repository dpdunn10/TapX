<!DOCTYPE html>
<html>
    <head>
        <?php
            if(!isset($_COOKIE['business_id']) || !isset($_COOKIE['table_id']) || !isset($_COOKIE['user_name'])){
                header('Location: home.php');
            }
         ?>
        <script src="../jquery-3.1.1.js"></script>
        <script language="javascript" type="text/javascript">
            $(document).ready(function(){
                //Create new websocket
				var addr = 'ws://TapX.duckdns.org:9998/websocket.php';
                var ws = new WebSocket(addr);

                //Send message to server notifying customers wish to summon the waiter/waitress
                $('#send-btn').click(function(){
                    var msg = {
                        business_id: "<?php echo $_COOKIE['business_id']; ?>",
                        type: "summon",
                        name: "<?php echo $_COOKIE['user_name']; ?>",
                        table_id: "<?php echo $_COOKIE['table_id']; ?>",
                        quantity: "NULL",
                        item: "NULL"
                    };
                   ws.send(JSON.stringify(msg));
                   alert("Waitress notified...");
                });

                ws.onerror	= function(ev){
                    if(window.console) console.log('Error Occured: ' + ev.data);
                };
                ws.onclose 	= function(ev){
                    if(window.console) console.log('Disconnected from Server.');
                };

            });
        </script>
    </head>
    <body>
        <div>
            <button id="send-btn" class="button">Get Server</button>
        </div>
    </body>
</html>
