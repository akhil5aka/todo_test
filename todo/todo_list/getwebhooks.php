
    <?php
$webhook_data = file_get_contents('php://input');
file_put_contents('/tmp/consumewebhook.log', $webhook_data)
?>