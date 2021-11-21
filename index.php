<!DOCTYPE html>
<html>
<head>
  <title>Sign-in with Google</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  <link   rel="stylesheet" 
        href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" 
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" 
        crossorigin="anonymous">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<body>

<header class="w3-container w3-red">
  <h1>Sign in With Google Example</h1>
</header>
<div class="container">
<div class="row">
    <div class="col">&nbsp;
    </div>
</div>
<?php

  
// Start a session so we have a place to store things between redirects
session_start();

// http://localhost/SignInWithGoogle/
// Google Credentials
$googleClientID = getenv("CLIENT_ID");
$googleClientSecret = getenv("CLIENT_SECRET");
$siteBaseURL = getenv("SITE_BASE_URL");
  
//echo '$googleClientID: ' . $googleClientID;
//echo '$googleClientSecret: ' . $googleClientSecret;
//echo '$siteBaseURL: ' . $siteBaseURL;

// This is the URL we'll send the user to first to get their authorization
$authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';

// This is Google's OpenID Connect token endpoint
$tokenURL = 'https://www.googleapis.com/oauth2/v4/token';

// The URL for this script, used as the redirect URL
//$baseURL = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
$baseURL = $siteBaseURL . $_SERVER['PHP_SELF'];

// Start the login process by sending the user
// to Google's authorization page
if(isset($_GET['action']) && $_GET['action'] == 'login') {
  unset($_SESSION['user_id']);

  // Generate a random hash and store in the session
  $_SESSION['state'] = bin2hex(random_bytes(16));

  $params = array(
    'response_type' => 'code',
    'client_id' => $googleClientID,
    'redirect_uri' => $baseURL,
    'scope' => 'openid email https://www.googleapis.com/auth/calendar.readonly',
    'state' => $_SESSION['state']
  );
  
  //echo 'Location: ' . $authorizeURL . '?' . http_build_query($params);
  $redirectUrl = $authorizeURL . '?' . http_build_query($params);
  echo '<div class="w3-panel w3-pale-yellow w3-border">';
  echo '<h3>Step 1: Start the Sign in process by sending request to Google Authorize Endpoint. </h3>';
  echo '<p><a href=' . $redirectUrl  . '>Redirect the user to Google authorization page</a></p>';
  echo '<p><strong>Request:</strong></p>';
  echo '<span style="width:800px; word-wrap:break-word; display:inline-block;">';
  echo ''. $redirectUrl;
  echo '</span>';
  echo '</div>';
  //echo 'Session State:' . $_SESSION['state'];
  // Redirect the user to Google's authorization page
  //header('Location: ' . $authorizeURL . '?' . http_build_query($params));
  //die();
}

if(isset($_GET['action']) && $_GET['action'] == 'logout') {
  unset($_SESSION['user_id']);
  header('Location: '.$baseURL);
  die();
}

//echo 'Code ' . htmlspecialchars($_GET["code"]);
// When Google redirects the user back here, there will be a "code" and "state"
// parameter in the query string
if(isset($_GET['code'])) {
  // Verify the state matches our stored state
  if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {
    //echo '<p>invalid state</p>';
    //header('Location: ' . $baseURL . '?error=invalid_state');
    //die();
  }

  //echo 'Exchange the auth code for a token';
  // Exchange the auth code for a token
  $ch = curl_init($tokenURL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'client_id' => $googleClientID,
    'client_secret' => $googleClientSecret,
    'redirect_uri' => $baseURL,
    'code' => $_GET['code']
  ]));
  $response = curl_exec($ch);
  $data = json_decode($response, true);
  //print_r($data);

  // Note: You'd probably want to use a real JWT library
  // but this will do in a pinch. This is only safe to do
  // because the ID token came from the https connection
  // from Google rather than an untrusted browser redirect

  // Split the JWT string into three parts
  $jwt = explode('.', $data['id_token']);

  // Extract the middle part, base64 decode it, then json_decode it
  $userinfo = json_decode(base64_decode($jwt[1]), true);

  $_SESSION['user_id'] = $userinfo['sub'];
  $_SESSION['email'] = $userinfo['email'];

  // While we're at it, let's store the access token and id token
  // so we can use them later
  $_SESSION['access_token'] = $data['access_token'];
  $_SESSION['id_token'] = $data['id_token'];
  $_SESSION['userinfo'] = $userinfo;   
  
  //header('Location: ' . $baseURL);
  //die();
}

// If there is a user ID in the session
// the user is already logged in
if(!isset($_GET['action'])) {
  if(!empty($_SESSION['user_id'])) {
    echo '<div class="w3-panel w3-pale-sand w3-border">';
    echo '<h3>User Information:</h3>';
    echo '<p>User ID: '.$_SESSION['user_id'].'</p>';
    echo '<p>Email: '.$_SESSION['email'].'</p>';
    //echo '<p><a href="?action=logout">Log Out</a></p>';
    //echo '<a href="?action=logout" class="btn btn-info btn-lg">';
    echo '<a href="'.$siteBaseURL.'" class="btn btn-info btn-lg">';
    echo '<span class="glyphicon glyphicon-log-out"></span> Log out';
    echo '</a>';
    echo '</div>';

    echo '<div class="w3-panel w3-pale-yellow w3-border">';
    echo '<h3>Id Token</h3>';
    echo '<span style="width:800px; word-wrap:break-word; display:inline-block;">';
    print_r($_SESSION['id_token']);
    echo '</span>';  
    echo '</div>';

    echo '<div class="w3-panel w3-pale-red w3-border">';
    echo '<h3>Decoded Id Token</h3>';
    echo '<pre>';
    print_r($_SESSION['userinfo']);
    echo '</pre>';
    echo '</div>';
    
    echo '<div class="w3-panel w3-pale-orange w3-border">';
    echo '<h3>Access Token</h3>';
    echo '<span style="width:800px; word-wrap:break-word; display:inline-block;">';
    print_r($_SESSION['access_token']);
    echo '</span>'; 
    echo '</div>';

    echo '<div class="w3-panel w3-pale-blue w3-border">';
    echo '<h3>User Info</h3>';
    echo '<h6>Google User Info API URL: https://www.googleapis.com/oauth2/v3/userinfo </h6>';
    echo '<pre>';
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer '.$_SESSION['access_token']
    ]);
    curl_exec($ch);
    echo '</pre>';
    echo '</div>';

    echo '<div class="w3-panel w3-pale-green w3-border">';
    echo '<h3>Events from Google Calendar:</h3>';
    echo '<h6>Google Calendar API URL: https://www.googleapis.com/calendar/v3/calendars/primary/events </h6>';
    echo '<h6>Scope: https://www.googleapis.com/auth/calendar.readonly </h6>';
    echo '<pre>';
    $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer '.$_SESSION['access_token']
    ]);
    curl_exec($ch);
    echo '</pre>';
    echo '</div>';

  } else {
    $params = array(
      'response_type' => 'code',
      'client_id' => 'client_id',
      'redirect_uri' => $baseURL,
      'scope' => 'openid email https://www.googleapis.com/auth/calendar.readonly',
      'state' => bin2hex(random_bytes(16))
    );

    echo '<h3>You are not logged in. Click "Login with Google" button to login.</h3>';    
    echo '<p>&nbsp;</p>';
    echo '<div class="row">';
    echo '<div class="col-md-3">';
    echo '<a class="btn btn-outline-dark" href="?action=login" role="button" style="text-transform:none">';
    echo '<img width="20px" style="margin-bottom:3px; margin-right:5px" alt="Google sign-in" src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Google_%22G%22_Logo.svg/512px-Google_%22G%22_Logo.svg.png" />';
    echo 'Login with Google';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '<p>&nbsp;</p>';
    echo '<div class="w3-panel w3-pale-yellow w3-border">';
    echo '<h3>Step 1: Start the Sign in process by sending request to Google Authorize Endpoint. </h3>';
    echo '<h6>'. $authorizeURL .'</h6>';
    echo '<h6>Sample Request:</h6>';
    echo '<span style="width:800px; word-wrap:break-word; display:inline-block;">';
    echo ''. $authorizeURL . '?' . http_build_query($params);
    echo '</span>';
    echo '</div>';
    echo '<div class="w3-panel w3-pale-green w3-border">';
    echo '<h3>Step 2: Exchange the code with token by sending request to Google Token Endpoint. </h3>';
    echo '<h6>' .$tokenURL .'</h6>';
    echo '</div>';
    echo '<div class="w3-panel w3-pale-gray w3-border">';
    echo '<h3>Google API Documentation</h3>';
    echo '<a href="https://developers.google.com/identity/protocols/oauth2" target="_blank">Using OAuth 2.0 to Access Google APIs</a>';
    echo '<a href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank">Using OAuth 2.0 for Web Server Applications</a>';
    echo '<a href="https://developers.google.com/identity/protocols/oauth2/scopes" target="_blank">OAuth 2.0 Scopes for Google APIs</h6>';
    echo '</div>';
    //echo '<p><a href="?action=login"><img src=SignInWithGoogle.jpg /></a></p>';
    
  }
  die();
}
  
?>
</div>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" 
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" 
        crossorigin="anonymous">
</script>
</body>
</html>
