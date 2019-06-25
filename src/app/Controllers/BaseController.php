<?php

namespace app\Controllers;

use Server\CoreBase\Controller;
use app\Models\KeyPairModel;

class BaseController extends Controller
{

    /**
     * 通用返回数据
     * @param $data
     * @param int $code
     * @param string $msg
     */
    protected function response($data, $code = 200, $msg = '')
    {
        $resData = ["status" => $code, "message" => $msg, "data" => $data];
        $this->http_output->end(json_encode($resData));
    }


    /**
     * 扩展http input 获取 Row Post值
     */
    protected function getPostRowData()
    {
        $swoole_http_request = $this->http_input->request;
        if (array_key_exists('content-type', $swoole_http_request->header) && false !== strpos($swoole_http_request->header['content-type'], 'application/json')) {
            $this->http_input->request->post = json_decode($swoole_http_request->rawcontent(), true);
        }
    }


    /**
     * 判断是否是合法请求
     * @param string $controllerName
     * @param string $methodName
     * @throws \Exception
     */
    protected function initialization($controllerName, $methodName)
    {
        parent::initialization($controllerName, $methodName);

        $this->getPostRowData();

        // 这里判断http请求授权权限
        $controllerName = $this->context["controller_name"];
        if (strtolower($controllerName) !== "keypaircontroller") {
            // 除了Token页面外都需要验证token是否有效
            $param = $this->http_input->getAllGet();
            if (!empty($param["sign"])) {
                $keyPairModel = $this->loader->model(KeyPairModel::class, $this);
                $checkResult = $keyPairModel->check_token($param["sign"]);
                if (!$checkResult) {
                    $this->response([], 404, "Wrong token.");
                }
            } else {
                $this->response([], 404, "Token does not exist.");
            }
        }
    }
}