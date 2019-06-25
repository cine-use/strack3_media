<?php
require './../vendor/autoload.php';

use Dj\Upload;
use Ws\Http\Request;
use Ws\Http\Request\Body;
use think\Image;

class UploadFile extends Upload
{
    // 上传token
    //protected $token = '';

    /**
     * @var RequestParam
     */
    protected $request;

    /**
     * FFMEPG转码对象
     * @var \FFMpeg\FFMpeg
     */
    protected $ffmpeg;

    protected $_headers = [
        'Accept' => 'application/json',
        'content-type' => 'application/json'
    ];

    // 上传文件临时存放位置
    protected $tempPath = UPLOAD_DIR . '/tmp';

    // 允许上传图片格式
    protected $allowExt = [
        "gif" => "image",
        "jpeg" => "image",
        "jpg" => "image",
        "bmp" => "image",
        "png" => "image",
        "mov" => "video",
        "mp4" => "video",
        "avi" => "video",
        "wmv" => "video",
        "flv" => "video",
    ];

    // 限制规则
    protected $fileAllowRule = [
        'size' => 209715200 // 上传文件文件最大限制200MB
    ];

    /**
     * UploadFile constructor.
     * @param string $frm_name
     */
    public function __construct(string $frm_name = 'Filedata')
    {
        parent::__construct($frm_name);
        $this->fileAllowRule['ext'] = array_keys($this->allowExt);
        $this->request = new RequestParam();
    }

    /**
     * 初始化FFMPEG对象
     */
    protected function initFFMPEG()
    {
        $this->ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => STRACK_FFMPEG_DIR,
            'ffprobe.binaries' => STRACK_FFPROBE_DIR,
            'timeout' => 300, // The timeout for the underlying process
            'ffmpeg.threads' => 4,   // The number of threads that FFMpeg should use
        ]);
    }

    /**
     * 生成uuid
     * @param string $prefix
     * @return mixed
     * @throws Exception
     */
    protected function create_uuid($prefix = '')
    {
        if (function_exists("uuid_create")) {
            return uuid_create();
        } else {
            return Webpatser\Uuid\Uuid::generate()->string;
        }
    }

    /**
     * 随机字符串加数字
     * @param int $size
     * @return string
     */
    protected function randomString($size = 8)
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $rand_str = null;
        for ($i = 0; $i < $size; $i++) {
            $rand_str .= $chars[mt_rand(0, 61)];
        }
        return time() . $rand_str;
    }

    /**
     * 发送数据到媒体服务器进行进一步处理
     * @param $token
     * @param $fileData
     * @return \Ws\Http\Response
     * @throws \Ws\Http\Exception
     */
    protected function postToMediaService($token, $fileData)
    {
        $http = Request::create();
        $body = Body::json($fileData);
        $res = $http->post(JOB_SERVICE_URL . "/media/upload?sign={$token}", $this->_headers, $body);
        return $res;
    }

    /**
     * 验证token是否有效
     * @param $token
     * @return bool
     * @throws \Ws\Http\Exception
     */
    protected function checkToken($token)
    {
        $http = Request::create();
        $body = Body::json(["token" => $token]);
        $res = $http->post(JOB_SERVICE_URL . '/KeyPair/verify_token', $this->_headers, $body);
        if ($res->body->status == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取文件夹名称
     * @param $saveName
     * @return mixed
     */
    protected function getFolderName($saveName)
    {
        return explode(".", $saveName)[0];
    }

    /**
     * 判断目录是否存在,不存在则创建
     * @param $folderName
     * @param int $mode
     * @return bool
     */
    protected function createDirectory($folderName, $mode = 0777)
    {
        $path = UPLOADS_DIR . '/' . $folderName;
        if (is_dir($path)) {
            //判断目录存在否，存在不创建
            return true;
            //已经存在则输入路径
        } else { //不存在则创建目录
            $re = mkdir($path, $mode, true);
            //第三个参数为true即可以创建多极目录
            if ($re) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 处理图片文件
     * @param $fileData
     * @param $param
     * @return mixed
     */
    protected function convertImage($fileData, $param)
    {
        // 创建文件夹
        $folderName = $this->getFolderName($fileData["savename"]);
        $this->createDirectory($folderName);

        // 重新整理图片数据
        $imageData = [
            'md5_name' => $folderName,
            'md5' => $fileData['md5'],
            'folder' => $folderName,
            'type' => $fileData['type'],
            'path' => "/uploads/{$folderName}/"  // 文件存储路径
        ];

        // 读取原始图片宽高
        $image = Image::open($fileData["savepath"]);

        // 返回图片的宽度
        $imageData["width"] = $image->width();
        // 返回图片的高度
        $imageData["height"] = $image->height();

        // 判断是否需要裁切
        if (array_key_exists('crop', $param)) {
            $cropData = json_decode($param['crop'], true);
            $image->crop($cropData['w'], $cropData['h'], $cropData['x'], $cropData['y']);
        }

        // 图片存储名称前缀
        $imageData["name_prefix"] = $folderName;

        if ($fileData["ext"] !== "gif") {
            // gif图片不用处理直接使用, 统一处理成 jpg 后缀

            // 扩展名
            $imageData["ext"] = $fileData["ext"];

            // 保存原图
            $baseName = UPLOADS_DIR . "/{$folderName}/{$folderName}";

            //最大图片尺寸 2048
            $ratio = $imageData["width"] / $imageData["height"];
            if ($imageData["width"] > 2048) {
                $maxWidth = 2048;
                $maxHeight = $maxWidth / $ratio;
            } else {
                $maxWidth = $imageData["width"];
                $maxHeight = $imageData["height"];
            }

            // 拥有尺寸
            $sizeData = ["origin"];

            // 保存原图
            $image->thumb($maxWidth, $maxHeight)
                ->save("{$baseName}_origin.{$fileData["ext"]}");

            // 更多尺寸
            if (array_key_exists("size", $param)) {
                $sizeList = explode(",", $param["size"]);
                foreach ($sizeList as $sizeItem) {
                    array_push($sizeData, $sizeItem);
                    $sizeArray = explode("x", $sizeItem);
                    if (count($sizeArray) === 2) {
                        $image = \think\Image::open("{$baseName}_origin.{$fileData["ext"]}");
                        $image->thumb($sizeArray[0], $sizeArray[1], \think\Image::THUMB_FILLED)
                            ->save("{$baseName}_{$sizeArray[0]}x{$sizeArray[1]}.{$fileData["ext"]}");
                    }
                }
            }

            $imageData["size"] = join(",", $sizeData);
        } else {
            // 扩展名
            $imageData["ext"] = "gif";
            // 拥有尺寸
            $imageData["size"] = "origin";

            // 移入指定文件夹
            $targetPath = UPLOADS_DIR . "/{$folderName}/{$folderName}_origin.gif";;
            copy($fileData["savepath"], $targetPath);
        }

        // 删除临时文件
        unlink($fileData["savepath"]);

        return $imageData;
    }

    /**
     * 获取视频基础信息
     * @param $fileData
     * @param $param
     * @return array
     */
    protected function convertVideo($fileData, $param)
    {
        $this->initFFMPEG();

        // 创建文件夹
        $folderName = $this->getFolderName($fileData["savename"]);
        $this->createDirectory($folderName);

        // 重新整理视频数据
        $mediaData = [
            'md5_name' => $folderName,
            'md5' => $fileData['md5'],
            'folder' => $folderName,
            'type' => $fileData['type'],
            'path' => "/uploads/{$folderName}/"  // 文件存储路径
        ];

        // 获取当前媒体参数
        $videoData = $this->ffmpeg->getFFProbe()
            ->streams($fileData["savepath"])
            ->videos()
            ->first();

        // 媒体数据
        $mediaData['width'] = $videoData->get("width");// 视频宽度
        $mediaData['height'] = $videoData->get("height");// 视频高度
        $mediaData['duration'] = $videoData->get("duration");// 视频时长

        // 视频帧速率
        $frameRate = $videoData->get("r_frame_rate");
        $mediaData['rate'] = (int)explode("/", $frameRate)[0];

        // 移入指定文件夹
        $targetPath = UPLOADS_DIR . "/{$folderName}/{$folderName}_source.{$fileData['ext']}";

        $mediaData['source_path'] = $targetPath;
        $mediaData['out_thumb_path'] = UPLOADS_DIR . "/{$folderName}/{$folderName}.jpg";
        $mediaData['out_video_path'] = UPLOADS_DIR . "/{$folderName}/{$folderName}.mp4";
        $mediaData['ext'] = 'mp4';

        copy($fileData["savepath"], $targetPath);
        unlink($fileData["savepath"]);

        return $mediaData;
    }

    /**
     * 处理Base64图片
     * @param $postParam
     * @return array
     * @throws Exception
     */
    public function handleBase64Image($postParam)
    {
        //生成随机
        $fileName = $this->randomString(8);

        //图片解码
        $base64Body = substr(strstr($postParam['base64Img'], ','), 1);
        $base64Data = base64_decode($base64Body);

        //生成上传路径
        $fileSavePath = $this->tempPath . '/' . $fileName . '.png';
        file_put_contents($fileSavePath, $base64Data);

        $savePath = str_replace('\\', '/', $fileSavePath);

        $imgData = [
            'name' => $fileName,
            'ext' => 'png',
            'mime' => 'image/png',
            'size' => 0,
            'savename' => $fileName,
            'savepath' => $savePath,
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], $this->host, $savePath),
            'uri' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $savePath),
            'md5' => md5_file($savePath)
        ];

        return $imgData;
    }

    /**
     * 获取并检查上传文件
     * @throws Exception
     */
    public function dealFile()
    {
        // 获取上传post传参
        $postParam = $this->request->post();

        //验证token
        if (array_key_exists("token", $postParam)) {

            if ($this->checkToken($postParam["token"])) {

                // 判断当前上传图像是否是base64类型
                if (array_key_exists("base64Img", $postParam)) {
                    $fileData = $this->handleBase64Image($postParam);
                } else {
                    $fileData = $this->save($this->tempPath, $this->fileAllowRule);
                }


                if (is_array($fileData)) {
                    // 返回数组，文件就上传成功了
                    $fileType = $this->allowExt[$fileData["ext"]];
                    $fileData["type"] = $fileType;   // 文件类型
                    $mediaData = [];
                    switch ($fileType) {
                        case 'image':
                            // 处理图片
                            $mediaData = $this->convertImage($fileData, $postParam);
                            break;
                        case 'video':
                            // 处理视频
                            $mediaData = $this->convertVideo($fileData, $postParam);
                            break;
                    }

                    // 调用SD框架接口进一步处理
                    $response = $this->postToMediaService($postParam["token"], $mediaData);
                    if ($response->code == 200) {
                        response($mediaData);
                    } else {
                        response([], 404, 'Media server exception.');
                    }
                } else {
                    // 如果返回负整数(int)就是发生错误了
                    $errorMsg = [
                        0 => 'No upload file.',
                        -1 => 'Upload failure.',
                        -2 => 'File storage path is illegal.',
                        -3 => 'Upload illegal format files ( gif, jpeg, jpg, bmp, png, mov, mp4, avi, wav, flv )',
                        -4 => 'The size of the file is not in accordance with the rules.',
                        -5 => 'Token validation error'
                    ];
                    response([], 404, $errorMsg[$fileData]);
                }
            } else {
                response([], 404, "Wrong token.");
            }
        } else {
            response([], 404, "Token does not exist.");
        }

    }
}