<?php
  if($_SERVER['REQUEST_METHOD'] != "POST") {    // Consume only POST
    header("HTTP/1.0 403 Forbidden");
    print("Forbidden");
    exit();
  }

  $email = $_POST["email"];
  error_log(json_encode($email));

  // Ensure the incoming request has a valid auth key (right now random generated UUID)
  $authKey = $email["authKey"];
  error_log("Incoming authKey: " . $authKey);
  if($authKey != "91414da8-c679-11e6-9d9d-cec0c932ce01") {
    print("Un Authorized");
    http_response_code(401);
    exit();
  }

  $consKey   = "KxJqMM3osjoH6AxS9fAR";
  $consSec   = "n53XZjiKhd1hghyvnDgf7t1bglAnvswAUF3JJTUU";

  $body = $email["body"];

  $case_attachment = $_FILES["case_attachment"];
  //error_log('Case Attachment: ' . json_encode($case_attachment));

  if($case_attachment["size"]["attachment"] != 0) {
    error_log('Attachment IS present');

    $target_dir = "../upload/server/php/files/" . uniqid(date("Ymd")) . "/";
    mkdir($target_dir);
    $attachmentName = $_FILES["case_attachment"]["name"]["attachment"];
    error_log('Attachment name: ' . $attachmentName);
    $target_file = $target_dir . basename($attachmentName);

    if (move_uploaded_file($_FILES["case_attachment"]["tmp_name"]["attachment"], $target_file)) {
      error_log("The file ". basename( $_FILES["case_attachment"]["name"]["attachment"]). " has been uploaded.");

      $attachmentUrl = 'https://files.spidercloud.com/' . substr($target_file, 3);
      $body = $body . '\n\n' . 'Attachments:\n' . $attachmentUrl;
      error_log('body is now: ' . $body);
    } else {
      error_log("Sorry, there was an error uploading your file.");
    }
  } else {
    error_log('Attachment is NOT present');
  }

  $subdomain = "spidercloud";
  $baseUrl = "https://" . $subdomain . ".desk.com";
  
  $token = "zMlZL6KBqxtbqZqY63IA";
  $tokenSec = "D8xvz5Y4qnWEF43XqXuDPzYy4H3NxHxTT3rrdJmr";

  $subject = $email["subject"];
  $fromEmail = $email["fromEmail"];

  error_log('subject: ' . $subject);
  error_log('fromEmail: ' . $fromEmail);
  error_log('body: ' . $body);
  
  session_start();
  
  try {
    $oauth = new OAuth($consKey, $consSec);
    $oauth->enableDebug();
    
    $oauth->setToken($token, $tokenSec);

    $postData = '{
      "type"    : "email",
      "message" : {
        "body"     : "' . $body . '",
        "from"     : "' . $fromEmail .'",
        "cc"       : "' . $fromEmail .'",
        "subject"  : "' . $subject .'",
        "direction": "in"
      }
    }';
    error_log('post data: ' . json_encode($postData));

    $postHeaders = array(
      "Content-Type" => "application/json",
      "Accept" => "application/json",
    );
    $caseNewUrl = $baseUrl . '/api/v2/cases';
    $oauth->fetch($caseNewUrl, $postData, OAUTH_HTTP_METHOD_POST, $postHeaders);
    $jsonResp = json_decode($oauth->getLastResponse());
    error_log('reply: ' . json_encode($jsonResp));

    $redirectUrl = "Location: " . $_SERVER["HTTP_REFERER"];
    //error_log('redirectUrl: ' . $redirectUrl);
    header($redirectUrl);
  } catch(OAuthException $E) {
    error_log('Exception: ' . $E);
    print_r("Failed!");
    http_response_code(501);
  }
?>
