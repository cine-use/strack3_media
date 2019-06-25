<?php

namespace app\Controllers;

use app\Models\EventModel;

class EventController extends BaseController
{

    /**
     * 事件模型
     * @var \app\Models\MediaModel
     */
    protected $eventModel;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    public function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);
        $this->eventModel = $this->loader->model(EventModel::class, $this);
    }

    /**
     * 添加event
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function http_add()
    {
        // 参数获取
        $param = $this->http_input->getAllPost();
        $this->eventModel->add($param);
    }
}