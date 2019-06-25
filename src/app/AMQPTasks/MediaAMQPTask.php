<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 17-9-7
 * Time: 上午10:35
 */

namespace app\AMQPTasks;

use PhpAmqpLib\Message\AMQPMessage;
use Server\Components\AMQPTaskSystem\AMQPTask;
use Server\Memory\Pool;
use app\Controllers\TranscodeController;

class MediaAMQPTask extends AMQPTask
{

    /**
     * @var \app\Controllers\TranscodeController
     */
    protected $transcodeController;

    /**
     * @param AMQPMessage $message
     * @return \Generator|void
     * @throws \Server\CoreBase\SwooleException
     */
    public function initialization(AMQPMessage $message)
    {
        parent::initialization($message);
        $this->transcodeController = Pool::getInstance()->get(TranscodeController::class);
    }

    /**
     * @param $body
     * @throws \Server\CoreBase\SwooleException
     */
    public function handle($body)
    {
        // 执行转码操作
        $param = json_decode($body, true);
        $this->transcodeController->transCode($param);
        Pool::getInstance()->push($this->transcodeController);
        $this->ack();
    }
}
