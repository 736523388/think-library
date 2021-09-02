<?php


namespace library\service;


use library\Service;
use think\App;
use think\Session;

class TokenService extends Service
{
    /**
     * session实例
     * @var Session
     */
    public $session;

    public function __construct(App $app, Session $session)
    {
        parent::__construct($app);
        $this->session = $session;
    }

    /**
     * 获取当前请求令牌
     * @return string
     */
    public function getInputToken()
    {
        return $this->request->header('user-token-csrf', input('_token_', ''));
    }

    /**
     * 验证表单令牌是否有效
     * @param string $token 表单令牌
     * @param string $node 授权节点
     * @return boolean
     */
    public function checkFormToken($token = null, $node = null)
    {
        if (is_null($token)) $token = $this->getInputToken();
        if (is_null($node)) $node = NodeService::instance()->getCurrent();
        // 读取缓存并检查是否有效
        $cache = $this->session->get($token);
        if (empty($cache['node']) || empty($cache['time']) || empty($cache['token'])) return false;
        if ($cache['token'] !== $token || $cache['time'] + 600 < time() || $cache['node'] !== $node) return false;
        return true;
    }

    /**
     * 清理表单CSRF信息
     * @param string $token
     * @return $this
     */
    public function clearFormToken($token = null)
    {
        if (is_null($token)) $token = $this->getInputToken();
        $this->session->delete($token);
        return $this;
    }

    /**
     * 生成表单CSRF信息
     * @param null|string $node
     * @return array
     */
    public function buildFormToken($node = null)
    {
        list($token, $time) = [uniqid('csrf'), time()];
        foreach ($this->session->get() as $key => $item) {
            if (stripos($key, 'csrf') === 0 && isset($item['time'])) {
                if ($item['time'] + 600 < $time) $this->clearFormToken($key);
            }
        }
        $data = ['node' => NodeService::instance()->fullnode($node), 'token' => $token, 'time' => $time];
        $this->session->set($token, $data);
        return $data;
    }
}