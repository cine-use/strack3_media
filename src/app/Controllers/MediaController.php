<?php

namespace app\Controllers;

use app\Models\MediaModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MediaController extends BaseController
{

    // 交换机名称
    protected $exchange = "media_exchange";

    // 队列名称
    protected $queue = 'media';

    /**
     * 媒体模型
     * @var \app\Models\MediaModel
     */
    protected $mediaModel;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->mediaModel = $this->loader->model(MediaModel::class, $this);
    }

    /**
     * 发起 AMQP 连接
     */
    protected function connectAMQP()
    {
        $active = $this->config['amqp']['active'];
        $host = $this->config['amqp'][$active]['host'];
        $port = $this->config['amqp'][$active]['port'];
        $user = $this->config['amqp'][$active]['user'];
        $password = $this->config['amqp'][$active]['password'];
        $vhost = $this->config['amqp'][$active]['vhost'];

        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();
        $channel->exchange_declare($this->exchange, 'direct');
        $channel->queue_bind($this->queue, $this->exchange);
        $channel->queue_declare($this->queue);
        return $channel;
    }

    /**
     * 加入指定到AMQP队列
     * @param $param
     */
    protected function publisher($param)
    {
        $channel = $this->connectAMQP();
        $message = new AMQPMessage(json_encode($param));
        $channel->basic_publish($message, $this->exchange);
    }


    /**
     * 获取单一媒体信息 (http 访问)
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_get()
    {
        $param = $this->http_input->getAllPost();

        // 判断请求参数必须存在 md5_name
        if (array_key_exists("md5_name", $param)) {
            $mediaData = $this->mediaModel->getMediaData($param["md5_name"]);
            if (!$mediaData) {
                $this->response([], 404, $this->mediaModel->getError());
            } else {
                $this->response($mediaData);
            }
        } else {
            $this->response('', 404, 'The md5_name parameter must be passed.');
        }
    }

    /**
     * 批量获取媒体信息 (http 访问)
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_select()
    {
        $param = $this->http_input->getAllPost();

        // 判断请求参数必须存在 md5_name
        if (array_key_exists("md5_name", $param)) {
            $mediaListData = [];
            $md5NameList = explode(',', $param['md5_name']);
            foreach ($md5NameList as $md5NameItem){
                $mediaData = $this->mediaModel->getMediaData($md5NameItem);
                if ($mediaData) {
                    array_push($mediaListData, $mediaData);
                }
            }
            if (!empty($mediaListData)) {
                $this->response($mediaListData);
            } else {
                $this->response('', 404, 'No data was found.');
            }
        } else {
            $this->response('', 404, 'The md5_name parameter must be passed.');
        }
    }

    /**
     * 删除指定媒体
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_remove()
    {
        $param = $this->http_input->getAllPost();

        // 判断请求参数必须存在 md5_name
        if (array_key_exists("md5_name", $param)) {
            // 删除数据库指定项
            $mediaResult = $this->mediaModel->deleteByName($param["md5_name"]);
            if ($mediaResult > 0) {
                // 删除指定文件夹
                $targetPath = STRACK_UPLOADS_DIR . "/{$param["md5_name"]}/";
                delete_directory($targetPath);
                $this->response('', 200, 'Media deleted successfully.');
            } else {
                $this->response('', 404, 'Media does not exist.');
            }
        } else {
            $this->response('', 404, 'The md5_name parameter must be passed.');
        }
    }

    /**
     * 上传文件（目前只支持图片和视频）
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_upload()
    {
        // 参数获取
        $param = $this->http_input->getAllPost();
        $mediaAddData = [
            'md5_name' => $param["md5_name"],
            'md5' => $param["md5"],
            'type' => $param["type"],
            'status' => 'yes',
            'param' => json_encode($param),
            'uuid' => create_uuid()
        ];

        $addMediaResult = $this->mediaModel->add($mediaAddData);
        // 如果是视频文件加入到转码队列中
        if ($param["type"] === 'video') {
            $this->publisher($param);
        }
        $this->response($addMediaResult);
    }
}