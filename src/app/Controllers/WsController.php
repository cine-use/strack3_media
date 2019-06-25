<?php

namespace app\Controllers;

use app\Models\EventModel;
use Server\CoreBase\Controller;

class WsController extends Controller
{

    protected function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name);
    }

    /**
     * @throws \Exception
     */
    public function onConnect()
    {
        $uid = time();
        $this->bindUid($uid);
        $this->send(['type' => 'welcome', 'id' => $uid]);
    }

    public function login()
    {

    }

    /**
     * @throws \Server\CoreBase\SwooleException
     */
    public function message()
    {
        $this->sendToAll('返回消息');
    }

    public function onClose()
    {

    }
}
