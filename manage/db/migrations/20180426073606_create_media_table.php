<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateMediaTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('media', ['id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']);

        //添加数据字段
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'limit' => 11, 'comment' => '媒体ID'])
            ->addColumn('md5_name', 'string', ['default' => '', 'limit' => 255, 'comment' => '媒体MD5名称'])
            ->addColumn('md5', 'string', ['default' => '', 'limit' => 128, 'comment' => '文件MD5'])
            ->addColumn('type', 'enum', ['values' => 'image,video,audio', 'default' => 'image', 'comment' => '媒体类型'])
            ->addColumn('status', 'enum', ['values' => 'yes,no', 'default' => 'no', 'comment' => '媒体状态'])
            ->addColumn('param', 'json', ['null' => true, 'comment' => '媒体参数'])
            ->addColumn('uuid', 'char', ['default' => '', 'limit' => 36, 'comment' => '全局唯一标识符']);

        //添加索引
        $table->addIndex(['md5_name'], ['unique' => true, 'name' => 'idx_md5_name']);

        //执行创建
        $table->create();
    }
}
