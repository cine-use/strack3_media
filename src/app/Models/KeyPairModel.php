<?php

namespace app\Models;

use Server\CoreBase\Model;

class KeyPairModel extends Model
{

    // 当前模型绑定表名
    protected $table = 'key_pair';

    // 错误信息
    protected $errorMsg = '';

    /**
     * MediaModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->loader->mysql('mysqlPool', $this);
    }


    public function initialization(&$context)
    {
        parent::initialization($context);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    /**
     * 添加数据
     * @param $data
     * @return mixed
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function add($data)
    {
        $res = $this->db->insert($this->table)->set($data)->query();
        return $res->getResult();
    }

    /**
     * 更新指定字段数据
     * @param $id
     * @param $field
     * @param $value
     * @return mixed
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function update($id, $field, $value)
    {
        $res = $this->db->update($this->table)
            ->set($field, $value)
            ->where('id', $id)
            ->query();
        return $res->affected_rows();
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function delete($id)
    {
        $res = $this->db->delete()
            ->from($this->table)
            ->where('id', $id)
            ->query();
        return $res->affected_rows();
    }


    /**
     * 查询数据
     * @return mixed
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function find()
    {
        $resData = $this->db->select('*')
            ->from($this->table)
            ->limit(1)
            ->query()
            ->getResult();
        if(!empty($resData["result"])){
            return $resData["result"][0];
        }
        return [];
    }

    /**
     * 生成密钥对
     * @return array
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function generate()
    {
        $findData = $this->find();
        $accessKey = md5(create_uuid() . '_access_key');
        $secretKey = md5(create_uuid() . '_secret_key');
        if (!empty($findData)) {
            // 更新数据
            $this->db->update($this->table)
                ->set("access_key", $accessKey)
                ->set("secret_key", $secretKey)
                ->where('id', $findData["id"])
                ->query();
        } else {
            $addData = [
                "access_key" => $accessKey,
                "secret_key" => $secretKey
            ];
            $this->add($addData);
        }
        return [
            "access_key" => $accessKey,
            "secret_key" => $secretKey
        ];
    }

    /**
     * 验证令牌
     * @param $token
     * @return bool
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function check_token($token)
    {
        $keyPairData = $this->find();
        if (!empty($keyPairData)) {
            $correct = md5($keyPairData["access_key"] . $keyPairData["secret_key"]);
            if($correct === $token){
                return true;
            }
        }
        return false;
    }
}