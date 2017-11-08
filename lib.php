<?php

use u2flib_server\RegisterRequest;

session_start();

// global  vars
$javascript = null;
$jsVars = [];

// database
$pdo = new PDO("mysql:dbname=yubi", 'root', 'defaultPassword');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

// server setup
$scheme = isset($_SERVER['HTTPS']) ? "https://" : "http://";
$appId = $scheme . $_SERVER['HTTP_HOST'];
$u2f = new u2flib_server\U2F($appId);

// setup global js vars
$jsVars['appId'] = $appId;

/***************** REGISTER *************************/

// STEP 1: POST USERNAME
if (isset($_POST['username']) && !empty($_POST['username'])) {
    
    $data = $u2f->getRegisterData();
    list($req,$sigs) = $data;
    $jsonReq = json_encode($req);
    $_SESSION['req'] = $jsonReq;
    $jsonSigs = json_encode($sigs);
    
    $javascript = <<<EOT
    var req = $jsonReq;
    var sigs = $jsonSigs;
    u2f.register(settings.appId, [req], sigs, function(data) {
        var auth = JSON.stringify(data)
        var authfield = document.getElementById('authcode');
        authfield.value = auth;
    });
EOT;
}
    
// STEP 2: POST AUTH CODE
if (isset($_POST['authcode']) && !empty($_POST['authcode'])) {
    $authcode = $_POST['authcode'];
    
    $request = json_decode($_SESSION['req']);
    $response = json_decode($authcode);
    
    $registration = $u2f->doRegister($request, $response);
    
    if ($registration) {
        $ins = $pdo->prepare("insert into registrations (user_id, keyHandle, publicKey, certificate, counter) values (?, ?, ?, ?, ?)");
        $ins->execute(array(1, $registration->keyHandle, $registration->publicKey, $registration->certificate, $registration->counter));
    }
}

/********************** AUTH ***********************/
    
// STEP 1: userId
if (isset($_POST['userId']) && !empty($_POST['userId'])) {
    $userId = $_POST['userId'];
    $sel = $pdo->prepare("select * from registrations where user_id = ?");
    $sel->execute(array(1));
    $data = $sel->fetchAll();
    
    $reqs = json_encode($u2f->getAuthenticateData($data));

    $_SESSION['authReq'] = $reqs;
    
    $javascript = <<<EOT
    var req = $reqs;
    u2f.sign(settings.appId, [], req, function(data) {
        var loginAuthField = document.getElementById('loginAuth');
        loginAuthField.value = JSON.stringify(data);
    });
EOT;
}
    
// STEP 2: loginAuth
if (isset($_POST['loginAuth']) && !empty($_POST['loginAuth'])) {
    $loginAuth = $_POST['loginAuth'];
    
    $request = json_decode($_SESSION['authReq']);
    $response = json_decode($loginAuth);
    
    $sel = $pdo->prepare("select * from registrations where user_id = ?");
    $sel->execute(array(1));
    $data = $sel->fetchAll();
    
    $login = $u2f->doAuthenticate($request, $data, $response);
    
    $javascript = <<<EOT
    alert("Well done!");
EOT;
}