<?php


namespace library\service;


use library\Service;
use library\tools\Data;
use think\Db;

class SystemService extends Service
{
    /**
     * 配置数据缓存
     * @var array
     */
    protected $data = [];

    /**
     * 保存数据内容
     * @param string $name 数据名称
     * @param mixed $value 数据内容
     * @return boolean
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function setData($name, $value)
    {
        $data = ['name' => $name, 'value' => serialize($value)];
        return Data::save('SystemData', $data, 'name');
    }

    /**
     * 读取数据内容
     * @param string $name 数据名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getData($name, $default = [])
    {
        try {
            $value = Db::name('SystemData')->where(['name' => $name])->value('value');
            return empty($value) ? $default : unserialize($value);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * 写入系统日志
     * @param string $action
     * @param string $content
     * @param string $username
     * @return int|string
     */
    public function setOplog($action, $content, $username = '')
    {
        return Db::name('SystemLog')->insert([
            'node'     => NodeService::instance()->getCurrent(),
            'action'   => $action, 'content' => $content,
            'geoip'    => $this->request->isCli() ? '127.0.0.1' : $this->request->ip(),
            'username' => $this->request->isCli() ? 'cli' : $username,
        ]);
    }
    /**
     * 打印输出数据到文件
     * @param mixed $data 输出的数据
     * @param boolean $new 强制替换文件
     * @param string|null $file 文件名称
     */
    public function putDebug($data, $new = false, $file = null)
    {
        if (is_null($file)) $file = RUNTIME_PATH . date('Ymd') . '.txt';
        $str = (is_string($data) ? $data : ((is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true))) . PHP_EOL;
        $new ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}