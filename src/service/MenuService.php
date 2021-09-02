<?php


namespace library\service;


use library\Service;
use library\tools\Data;
use think\Db;

class MenuService extends Service
{
    /**
     * 获取可选菜单节点
     * @return array
     * @throws \ReflectionException
     */
    public function getList()
    {
        static $nodes = [];
        if (count($nodes) > 0) return $nodes;
        foreach (NodeService::instance()->getMethods() as $node => $method) {
            if ($method['ismenu']) $nodes[] = ['node' => $node, 'title' => $method['title']];
        }
        return $nodes;
    }

    /**
     * 获取系统菜单树数据
     * @param int $uuid 用户id
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTree($uuid)
    {
        $result = (array)Db::name('SystemMenu')->where(['status' => '1'])->order('sort desc,id asc')->select();
        return $this->buildData(Data::arr2tree($result), NodeService::instance()->getMethods(), $uuid);
    }

    /**
     * 后台主菜单权限过滤
     * @param array $menus 当前菜单列表
     * @param array $nodes 系统权限节点
     * @param int $uuid 系统权限节点
     * @return array
     * @throws \ReflectionException
     */
    private function buildData($menus, $nodes, $uuid = 0)
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = $this->buildData($menu['sub'], $nodes, $uuid);
            }
            if (!empty($menu['sub'])) $menu['url'] = '#';
            elseif ($menu['url'] === '#') unset($menus[$key]);
            elseif (preg_match('|^https?://|i', $menu['url'])) continue;
            else {
                $node = $menu['node'];
                $menu['url'] = url($menu['url']) . (empty($menu['params']) ? '' : "?{$menu['params']}");
                if (!AdminService::instance()->check($uuid,$node)) unset($menus[$key]);
            }
        }
        return $menus;
    }
}