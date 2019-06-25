<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 17-9-15
 * Time: 下午2:28
 */

namespace app\Process;

use app\AMQPTasks\MediaAMQPTask;
use Server\Components\AMQPTaskSystem\AMQPTaskProcess;

class MediaAmqpProcess extends AMQPTaskProcess
{

    /**
     * 路由消息返回class名称
     * @param $body
     * @return string
     */
    protected function route($body)
    {
        return MediaAMQPTask::class;
    }

    /**
     * 开始进程
     * @param $process
     * @throws \Exception
     */
    public function start($process)
    {
        parent::start($process);

        //获取一个channel
        $channel = $this->connection->channel();

        //创建一个队列
        $channel->queue_declare("media");

        //框架默认提供的路由，也可以自己写
        $this->createDirectConsume($channel,'media', 2, false, "media_exchange");

        //等待所有的channel
        $this->connection->waitAllChannel();
    }


    /**
     * 进程关闭回调方法
     */
    protected function onShutDown()
    {

    }
}
