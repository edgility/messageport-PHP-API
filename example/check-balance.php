<?php
    require '../src/Messageport.php';

    use Edgility\Messageport\Messageport;

    $messageport = new Messageport('YOUR_API_ID', 'YOUR_API_PASSWORD');

    $obj = $messageport->checkBalance();

    if($obj->code == 200) {
        echo '<pre>';
        echo 'Credits: '.$obj->credits."\n";
        echo 'Default Sender ID: '.$obj->senderid."\n";
        echo 'Mobile Number: '.$obj->mobile;
        echo '</pre>';
    } else {
        echo '['.$obj->code.'] ';
        echo $messageport->getCleanResponse();
    }