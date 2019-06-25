<?php
require 'RequestParam.php';
require 'UploadFile.php';

define("UPLOAD_DIR", __DIR__);
define('MYROOT', UPLOAD_DIR . "/..");
define("UPLOADS_DIR", MYROOT . '/src/www/uploads');

//读取当前系统设置
$env = parse_ini_file(MYROOT . '/.env', true);

define('JOB_SERVICE_URL', $env["job_service_url"]);
define("STRACK_FFMPEG_DIR", MYROOT . "/bin/natron/bin/ffmpeg");
define("STRACK_FFPROBE_DIR", MYROOT . "/bin/natron/bin/ffprobe");

/**
 * 返回数据
 * @param $data
 * @param int $code
 * @param string $msg
 */
function response($data, $code = 200, $msg = '')
{
    header('Access-Control-Allow-Origin:*');
    echo json_encode(["status" => $code, "message" => $msg, "data" => $data]);
}

$uploadFile = new UploadFile();

try {
    $uploadFile->dealFile();
} catch (Exception $e) {
    response($e->getMessage());
}