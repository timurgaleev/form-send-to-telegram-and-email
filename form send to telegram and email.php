<?php ignore_user_abort(true); error_reporting(0);




/***************************************************************************
 *                              Settings                                  *
 ***************************************************************************/
const NOTIFICATIONS_EMAIL = "info@timzu.com";
const TELEGRAM_TOKEN = "573038591:AAEnIruKocx12Tasy2e3xbM-Y2KGQlBHZ84";
const TELEGRAM_CHAT_ID = "-308756269";
/***************************************************************************
 *                                Work                                  *
 ***************************************************************************/
// get the data from the form
$input = getInput();
// error form
if (empty($input["text"]) && empty($input["files"]) && (int)$_SERVER['CONTENT_LENGTH'] > 512 * 1024) {
    showPostExceededError();
}
// taking files
$files = getFiles($input);
// error form
$bigFiles = getBigFiles($files);
if (count($bigFiles) != 0) {
    showBigFilesError($input, $bigFiles);
}
// logs error
$errorFiles = getErrorFiles($files);
if (count($errorFiles) != 0) {
    foreach ($errorFiles as $file) {
        error_log("Fail to upload file {$file["name"]}. Error code: {$file["error"]}", 0);
    }
}
// taking success files
$goodFiles = getGoodFiles($files);
// new create id 
$leadId = time();
// e-mail notification
if (defined("NOTIFICATIONS_EMAIL") && NOTIFICATIONS_EMAIL != "") {
    $emailSent = sendEmail($input, $leadId, $errorFiles, $goodFiles);
}
// talegram notification
if (defined("TELEGRAM_TOKEN") && TELEGRAM_TOKEN != "" && defined("TELEGRAM_CHAT_ID") && TELEGRAM_CHAT_ID != "") {
    $telegramSent = sendTelegram($input, $leadId, $errorFiles, $goodFiles);
}
// error notofication
if (!$emailSent && !$telegramSent) {
    showFormError();
}
// URL redirect
if ($input["text"]["redirect"]) {
    redirect($input, $leadId);
}
// thanks page
showDefaultThankyouPage($input);
/***************************************************************************
 *                               Functions                                   *
 ***************************************************************************/
function httpRequest($url, $method = "GET", $headers = [], $data = NULL)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "PHP API Client");
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if ($method == "POST") {
        $headers [] = 'Content-Type: multipart/form-data';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($curl);
    if ($result === false) {
        throw new Exception("httpRequest failed: " . curl_error($curl));
    }
    $responseBody = json_decode($result, true);
    $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($responseStatus != 200) {
        throw new Exception("httpRequest failed: " . print_r($responseBody));
    }
    return $responseBody;
}
function getFileMaxSize($input)
{
    $max_size = -1;
    if ($max_size < 0) {
        $post_max_size = getPostMaxSize();
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }
        $upload_max = parseSize(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
        if ((int)$input["text"]["MAX_FILE_SIZE"] > 0 && (int)$input["text"]["MAX_FILE_SIZE"] < $max_size) {
            $max_size = (int)$input["text"]["MAX_FILE_SIZE"];
        }
    }
    return $max_size;
}
function getPostMaxSize()
{
    $max_size = -1;
    if ($max_size < 0) {
        $post_max_size = parseSize(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }
    }
    return $max_size;
}
function parseSize($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}
function render($html)
{
    echo "
      <html lang='en'>
      <head>
        <meta charset='UTF-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        /*<link rel='shortcut icon' href='./assets/favicon.ico'>*/
        <title>your offer has been sent</title>
        <style>
          body{
            margin: 0;
            background: #fffff;
            background: -webkit-linear-gradient(to right, #858585, #fffff);
            background: linear-gradient(to right, #858585, #fffff);
            height: 100vh;
            padding: 2rem;
            color: white;
            font-family: 'Open Sans', sans-serif;
            text-align: center;
            box-sizing: border-box;
            text-shadow: 0 1px 2px rgba(0,0,0,.1);
          }
          .content {
            position: absolute;
            width: 80%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            color: rgba(0, 0, 0, .75);
            -webkit-box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            -moz-box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            padding: 1rem;
            text-shadow: none;
          }
          @media screen and (min-width: 900px) {
            .content {
              max-width: 600px;
              padding: 2rem;
            }
          }
          .content ul {
            margin: 2rem 0;
            text-align: left;
          }
          .content button {
		-moz-appearance: none;
		-webkit-appearance: none;
		-ms-appearance: none;
		appearance: none;
		-moz-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-color 0.2s ease-in-out;
		-webkit-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-color 0.2s ease-in-out;
		-ms-transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-color 0.2s ease-in-out;
		transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-color 0.2s ease-in-out;
		background-color: transparent;
		border-radius: 0.35em;
		border: solid 3px #efefef;
		color: #787878 !important;
		cursor: pointer;
		display: inline-block;
		font-weight: 400;
		height: 3.15em;
		height: calc(2.75em + 6px);
		line-height: 2.75em;
		min-width: 10em;
		padding: 0 1.5em;
		text-align: center;
		text-decoration: none;
		white-space: nowrap;
          }
          .content button:hover {
			border-color: #49bf9d;
			color: #49bf9d !important;
          }
	</style>
      </head>
      <body>
        <div class='content'>
          $html
          <button onclick='history.go(-1);'>Return</button>
        </div>
      </body>
      </html>
    ";
    exit(0);
}
function getInput()
{
    $input = [
        "text" => $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST : $_GET,
        "files" => $_FILES
    ];
    return $input;
}
function getFiles($input)
{
    $files = [];
    foreach ($input["files"] as $entry) {
        if (!is_array($entry["name"])) {
            if (empty($entry["name"])) continue;
            $entry["name"] = basename($entry["name"]);
            $files [] = $entry;
            continue;
        }
        for ($i = 0; $i < count($entry["name"]); $i++) {
            $file = [];
            foreach ($entry as $name => $values) {
                $file[$name] = $values[$i];
            }
            if (empty($file["name"])) continue;
            $file["name"] = basename($file["name"]);
            $files [] = $file;
        }
    }
    return $files;
}
function getBigFiles($files)
{
    $bigFiles = [];
    foreach ($files as $file) {
        if ($file["error"] == UPLOAD_ERR_INI_SIZE || $file["error"] == UPLOAD_ERR_FORM_SIZE) {
            $bigFiles [] = $file;
        }
    }
    return $bigFiles;
}
function getErrorFiles($files)
{
    $errorFiles = [];
    foreach ($files as $file) {
        if ($file["error"] > UPLOAD_ERR_FORM_SIZE) {
            $errorFiles [] = $file;
        }
    }
    return $errorFiles;
}
function getGoodFiles($files)
{
    $goodFiles = [];
    foreach ($files as $file) {
        if ($file["error"] == UPLOAD_ERR_OK) {
            $goodFiles [] = $file;
        }
    }
    return $goodFiles;
}
function translateUploadError($errorCode)
{
    $errorMessage = "";
    switch ($errorCode) {
        case UPLOAD_ERR_PARTIAL:
            $errorMessage = "The download file was only partially received";
            break;
        case UPLOAD_ERR_NO_FILE:
            $errorMessage = "The file was not uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $errorMessage = "Missing temporary folder";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $errorMessage = "Could not write file to disk";
            break;
        case UPLOAD_ERR_EXTENSION:
            $errorMessage = "PHP-extension stopped downloading the file";
            break;
    }
    return $errorMessage;
}
function getSiteName()
{
    $siteName = $_SERVER["HTTP_REFERER"]
        ? preg_split("/[?\/]/", $_SERVER["HTTP_REFERER"], -1, PREG_SPLIT_NO_EMPTY)[1]
        : "";
    return $siteName;
}
function sendEmail($input, $leadId, $errorFiles, $goodFiles)
{
    $ok = true;
    $siteName = getSiteName();
    $subject = $input["text"]["leadId"]
        ? "Лид {$input["text"]["leadId"]} с сайта $siteName"
        : "Лид $leadId с сайта $siteName";
    $uid = md5(uniqid(time()));
    $body = "--$uid\r\n";
    $body .= "Content-type:text/html; charset=utf-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= "<html><body>";
    $body .= $input["text"]["leadId"]
        ? "<p>Апселл к заказу {$input["text"]["leadId"]}:</p>"
        : "<p>Новый заказ:</p>";
    $body .= "<ul><li>ID: ";
    $body .= $input["text"]["leadId"]
        ? $input["text"]["leadId"]
        : $leadId . "</li>";
    foreach ($input["text"] as $name => $value) {
        $name != "redirect" && $name != "leadId" && $name != "MAX_FILE_SIZE" && $body .= "<li>$name: $value</li>";
    }
    $body .= "
      <li>IP: {$_SERVER["REMOTE_ADDR"]}</li>
      <li>URL: {$_SERVER["HTTP_REFERER"]}</li>
      <li>User Agent: {$_SERVER["HTTP_USER_AGENT"]}</li>
      </ul>
    ";
    if (count($errorFiles) != 0) {
        $body .= "<p>Error download the file:</p><ul>";
        foreach ($errorFiles as $file) {
            $body .= "<li>{$file["name"]}: " . translateUploadError($file["error"]) . "</li>";
        }
        $body .= "</ul>";
    }
    $body .= "</body></html>\r\n\r\n";
    foreach ($goodFiles as $file) {
        $body .= "--$uid\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"{$file["name"]}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$file["name"]}\"\r\n\r\n";
        $content = chunk_split(base64_encode(file_get_contents($file["tmp_name"])));
        $body .= "$content\r\n\r\n";
    }
    $body .= "--$uid--";
    try {
        $sent = mail(NOTIFICATIONS_EMAIL, $subject, $body, implode("\r\n", [
            "MIME-Version: 1.0",
            "Content-type: multipart/mixed; boundary=\"$uid\""
        ]));
        if (!$sent) {
            throw new Exception("Email not accepted by MTA");
        }
    } catch (Exception $e) {
        error_log("Fail to send notification email: $e", 0);
        $ok = false;
    }
    return $ok;
}
function sendTelegram($input, $leadId, $errorFiles, $goodFiles)
{
    $ok = true;
    $siteName = getSiteName();
    $message = $input["text"]["leadId"]
        ? "Apsell to order {$input["text"]["leadId"]} from site $siteName\n\n"
        : "New offer from site $siteName\n\nID: $leadId\n";
    foreach ($input["text"] as $name => $value) {
        $name != "redirect" && $name != "leadId" && $name != "MAX_FILE_SIZE" && $message .= "$name: $value\n";
    }
    $message .= "IP: {$_SERVER["REMOTE_ADDR"]}\nURL: {$_SERVER["HTTP_REFERER"]}\nUser Agent: {$_SERVER["HTTP_USER_AGENT"]}\n";
    if (count($errorFiles) != 0) {
        $message .= "\nError download the file:\n";
        foreach ($errorFiles as $file) {
            $message .= "{$file["name"]}: " . translateUploadError($file["error"]) . "\n";
        }
    }
    $message = urlencode($message);
    try {
        httpRequest("https://api.telegram.org/bot"
            . TELEGRAM_TOKEN
            . "/sendMessage?chat_id="
            . TELEGRAM_CHAT_ID
            . "&text={$message}");
    } catch (Exception $e) {
        error_log("Fail to send notification to telegram: $e", 0);
        $ok = false;
    }
    $leadId = $input["text"]["leadId"] ? $input["text"]["leadId"] : $leadId;
    foreach ($goodFiles as $file) {
        try {
            httpRequest("https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendDocument", "POST", [], [
                "chat_id" => TELEGRAM_CHAT_ID,
                "document" => curl_file_create($file["tmp_name"], "", $file["name"]),
                "caption" => "Attachment to the order $leadId"
            ]);
        } catch (Exception $e) {
            error_log("Fail to send file to telegram: $e", 0);
            $ok = false;
        }
    }
    return $ok;
}
function redirect($input, $leadId)
{
    $redirectUrlSplitted = explode("?", $input["text"]["redirect"], 2);
    $redirectUrl = "{$redirectUrlSplitted[0]}?leadId=";
    $redirectUrl .= $input["text"]["leadId"] ?: $leadId;
    foreach ($input["text"] as $name => $value) {
        $name != "redirect" && $name != "leadId" && $redirectUrl .= "&$name=$value";
    }
    if (count($redirectUrlSplitted) > 1) $redirectUrl .= "&{$redirectUrlSplitted[1]}";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(['redirect' => $redirectUrl]);
        exit(0);
    }
    header("Location: $redirectUrl");
    exit(0);
}
function showDefaultThankyouPage($input)
{
    if ($input["text"]["leadId"]) {
        $html = "<h3>Item added to order</h3>"
            . "<p>We will contact you within 15 minutes to confirm the order.</p><ul>";
        foreach ($input["text"] as $name => $value) {
            if ($name != "redirect" && $name != "leadId" && $name != "MAX_FILE_SIZE") $html .= "<li>$name: $value</li>";
        }
        $html .= "</ul>";
    } else {
        $html = "<h3>Your application is accepted</h3>"
            . "<p>We will contact you shortly</p><ul>";
        foreach ($input["text"] as $name => $value) {
            if ($name != "redirect" && $name != "MAX_FILE_SIZE") $html .= "<li>$name: $value</li>";
        }
        $html .= "</ul><p>If you make a mistake, go back to the order page and fill out the form again.</p>";
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(['html' => $html]);
        exit(0);
    }
    render($html);
}
function showPostExceededError()
{
    $html = "<h3>Form submission error</h3>"
        . "<p>The maximum total size of uploaded files should not exceed"
        . round(getPostMaxSize() / 1024 / 1024, 2) . " МБ</p>"
        . "<p>Go back to the order page and fill out the form again.</p>";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header("Content-Type: application/json; charset=utf-8");
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['html' => $html]);
        exit(0);
    }
    render($html);
}
function showBigFilesError($input, $bigFiles)
{
    $html = "<h3>Form submission error</h3>"
        . "<p>The following files were not downloaded because the size was too large</p><ul>";
    foreach ($bigFiles as $file) {
        $html .= "<li>{$file["name"]}</li>";
    }
    $html .= "<p></ul>Maximum download size - "
        . round(getFileMaxSize($input) / 1024 / 1024, 2) . " МБ</p>";
    $html .= "<p>Go back to the order page and fill out the form again.</p>";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header("Content-Type: application/json; charset=utf-8");
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['html' => $html]);
        exit(0);
    }
    render($html);
}
function showFormError()
{
    $html = "<h3>Form submission error</h3>"
        . "<p>Try again later</p>";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header("Content-Type: application/json; charset=utf-8");
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['html' => $html]);
        exit(0);
    }
    render($html);
}