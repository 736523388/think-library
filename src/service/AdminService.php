<?php


namespace library\service;


use library\Service;
use library\tools\Data;
use think\Db;

class AdminService extends Service
{

    /**
     * 检查指定节点授权
     * --- 需要读取数据库或扫描所有节点
     * @param int $uuid
     * @param string $node
     * @return boolean
     * @throws \ReflectionException
     * @throws \think\exception\DbException
     */
    public function check($uuid = 0, $node = '')
    {
        $service = NodeService::instance();
        if ($uuid === 10000) return true;
        list($real, $nodes) = [$service->fullnode($node), $service->getMethods()];
        if (empty($nodes[$real]['isauth'])) {
            return empty($nodes[$real]['islogin']) || $uuid > 0;
        } else {
            if ($uuid === 0) return false;
            $authorize = Db::name('SystemUser')->where(['id' => $uuid])->value('authorize');
            $subSql = Db::name('SystemAuth')->whereIn('id', $authorize)->where(['status' => 1])->field('id')->buildSql();
            $rules = array_unique(Db::name('SystemAuthNode')->whereRaw("auth in {$subSql}")->column('node'));
            return in_array($real, (array)$rules);
        }
    }

    /**
     * 获取授权节点列表
     * @param array $checkeds
     * @return array
     * @throws \ReflectionException
     */
    public function getTree($checkeds = [])
    {
        list($nodes, $pnodes) = [[], []];
        $methods = array_reverse(NodeService::instance()->getMethods());
        foreach ($methods as $node => $method) {
            $count = substr_count($node, '/');
            $pnode = substr($node, 0, strripos($node, '/'));
            if ($count === 2 && !empty($method['isauth'])) {
                in_array($pnode, $pnodes) or array_push($pnodes, $pnode);
                $nodes[$node] = ['node' => $node, 'title' => $method['title'], 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
            } elseif ($count === 1 && in_array($pnode, $pnodes)) {
                $nodes[$node] = ['node' => $node, 'title' => $method['title'], 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
            }
        }
        foreach (array_keys($nodes) as $key) foreach ($methods as $node => $method) if (stripos($key, "{$node}/") !== false) {
            $pnode = substr($node, 0, strripos($node, '/'));
            $nodes[$node] = ['node' => $node, 'title' => $method['title'], 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
            $nodes[$pnode] = ['node' => $pnode, 'title' => ucfirst($pnode), 'pnode' => '', 'checked' => in_array($pnode, $checkeds)];
        }
        return Data::arr2tree(array_reverse($nodes), 'node', 'pnode', '_sub_');
    }

    public function getList()
    {
        $nodes = [];
        $methods = array_reverse(NodeService::instance()->getMethods());
        foreach ($methods as $node => $method) {
            $count = substr_count($node, '/');
            if ($count === 2 && !empty($method['isauth'])) {
                array_push($nodes, $node);
            }
        }
        return array_reverse($nodes);
    }
}