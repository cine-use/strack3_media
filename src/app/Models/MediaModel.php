<?php

namespace app\Models;

use Server\CoreBase\Model;

class MediaModel extends Model
{

    // 当前模型绑定表名
    protected $table = 'media';

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
     * 删除指定媒体通过 md5_name
     * @param $md5Name
     * @return mixed
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function deleteByName($md5Name)
    {
        $res = $this->db->delete()
            ->from($this->table)
            ->where('md5_name', $md5Name)
            ->query();
        return $res->affected_rows();
    }

    /**
     * 获取指定md5_name媒体
     * @param $md5Name
     * @return null
     * @throws \Server\CoreBase\SwooleException
     * @throws \Throwable
     */
    public function getMediaData($md5Name)
    {
        $resData = $this->db->select('*')
            ->from($this->table)
            ->where('md5_name', $md5Name)
            ->limit(1)
            ->query()
            ->getResult();

        if (!empty($resData["result"])) {
            $rowData = $resData["result"][0];

            // 处理param数据
            $rowData["param"] = json_decode($rowData["param"], true);

            // 检查文件是否存在
            switch ($rowData["param"]["type"]) {
                case "image":
                    // 检查图片文件是否存在
                    $hasError = false;
                    $sizeList = explode(",", $rowData["param"]["size"]);
                    foreach ($sizeList as $sizeItem) {
                        $path = STRACK_UPLOADS_DIR . "/{$rowData["param"]["md5_name"]}/{$rowData["param"]["md5_name"]}_{$sizeItem}.{$rowData["param"]["ext"]}";
                        if (!file_exists($path)) {
                            $hasError = true;
                            break;
                        }
                    }
                    if ($hasError) {
                        $this->errorMsg = 'File does not exist.';
                        return false;
                    } else {
                        return $rowData;
                    }
                    break;
                case "video":
                    // 检查视频文件是否存在
                    $mp4Path = STRACK_UPLOADS_DIR . "/{$rowData["param"]["md5_name"]}/{$rowData["param"]["md5_name"]}.mp4";
                    $movPath = STRACK_UPLOADS_DIR . "/{$rowData["param"]["md5_name"]}/{$rowData["param"]["md5_name"]}.mov";
                    if (file_exists($movPath)) {
                        // 存在mov文件说明转码还未完成
                        $this->errorMsg = 'Transcoding has not yet been completed.';
                        return false;
                    }
                    if (file_exists($mp4Path)) {
                        return $rowData;
                    }else{
                        $this->errorMsg = 'File does not exist.';
                        return false;
                    }
                    break;
            }
            return $rowData;
        } else {
            // 无数据
            $this->errorMsg = 'No recorded data.';
            return false;
        }
    }
}