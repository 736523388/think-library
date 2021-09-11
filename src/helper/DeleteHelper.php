<?php


namespace library\helper;


use library\Helper;
use think\db\Query;

class DeleteHelper extends Helper
{
    /**
     * 数据对象主键名称
     * @var string
     */
    protected $field;

    /**
     * 数据对象主键值
     * @var string
     */
    protected $value;

    /**
     * 逻辑器初始化
     * @param string|Query $dbQuery
     * @param string $field 操作数据主键
     * @param array $where 额外更新条件
     * @return boolean|null
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function init($dbQuery, $field = '', $where = [])
    {
        $this->query = $this->buildQuery($dbQuery);
        $this->field = empty($field) ? $this->query->getPk() : $field;
        $this->value = $this->request->post($this->field);

        // 主键限制处理
        if (!empty($where)) $this->query->where($where);
        if (!isset($where[$this->field]) && is_string($this->value)) {
            $this->query->whereIn($this->field, explode(',', $this->value));
        }
        // 前置回调处理
        if (false === $this->controller->callback('_delete_filter', $this->query, $where)) {
            return null;
        }

        // 阻止危险操作
        if (!$this->query->getOptions('where')) {
            $this->controller->error(lang('think_library_delete_error'));
        }
        // 组装执行数据
        $data = [];
        if (method_exists($this->query, 'getTableFields')) {
            $fields = $this->query->getTableFields();
            if (in_array('deleted', $fields)) $data['deleted'] = 1;
            if (in_array('is_deleted', $fields)) $data['is_deleted'] = 1;
        }

        // 执行删除操作
        $result = empty($data) ? $this->query->delete() : $this->query->update($data);
        // 结果回调处理
        if (false === $this->controller->callback('_delete_result', $result)) {
            return $result;
        }
        // 回复前端结果
        if ($result !== false) {
            $this->controller->success(lang('think_library_delete_success'), '');
        } else {
            $this->controller->error(lang('think_library_delete_error'));
        }
    }
}