<?php


namespace library;


use library\helper\TokenHelper;
use think\exception\HttpResponseException;
use think\Hook;
use think\Request;
use think\Response;

abstract class Controller extends \stdClass
{
    /**
     * @var Request Request实例
     */
    protected $request;

    /**
     * Base constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->request = $request;
        // 控制器初始化
        $this->_initialize();

        // 控制器后置操作
        if (method_exists($this, $method = "_{$this->request->action()}_{$this->request->method()}")) {
            Hook::add('app_end', function (Response $response) use ($method) {
                try {
                    [ob_start(), ob_clean()];
                    $return = call_user_func_array([$this, $method], $this->request->route());
                    if (is_string($return)) {
                        $response->content($response->getContent() . $return);
                    } elseif ($return instanceof Response) {
                        $this->__mergeResponse($response, $return);
                    }
                } catch (HttpResponseException $exception) {
                    $this->__mergeResponse($response, $exception->getResponse());
                } catch (\Exception $exception) {
                    throw $exception;
                }
            });
        }
    }

    /**
     * 控制器初始化
     */
    protected function _initialize()
    {
        if (empty($this->csrf_message)) {
            $this->csrf_message = lang('think_library_csrf_error');
        }
    }

    /**
     * 合并请求对象
     * @param Response $response 目标响应对象
     * @param Response $source 数据源响应对象
     * @return Response
     */
    private function __mergeResponse(Response $response, Response $source)
    {
        $response->code($source->getCode())->content($response->getContent() . $source->getContent());
        foreach ($source->getHeader() as $name => $value) if (!empty($name) && is_string($name)) $response->header($name, $value);
        return $response;
    }

    /**
     * 返回失败的操作
     * @param mixed $info 消息内容
     * @param array $data 返回数据
     * @param integer $code 返回代码
     */
    public function error($info, $data = [], $code = 0)
    {
        $result = ['code' => $code, 'info' => $info, 'data' => $data];
        throw new HttpResponseException(json($result));
    }

    /**
     * 返回成功的操作
     * @param mixed $info 消息内容
     * @param array $data 返回数据
     * @param integer $code 返回代码
     */
    public function success($info, $data = [], $code = 1)
    {
        if ($this->csrf_state) {
            TokenHelper::instance()->clear();
        }
        throw new HttpResponseException(json([
            'code' => $code, 'info' => $info, 'data' => $data,
        ]));
    }

    /**
     * URL重定向
     * @param string $url 跳转链接
     * @param array $vars 跳转参数
     * @param integer $code 跳转代码
     */
    public function redirect($url, $vars = [], $code = 301)
    {
        throw new HttpResponseException(redirect($url, $vars, $code));
    }

    /**
     * 返回视图内容
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param string $node CSRF授权节点
     */
    public function fetch($tpl = '', $vars = [], $node = null)
    {
        foreach ($this as $name => $value) $vars[$name] = $value;
        if ($this->csrf_state) {
            TokenHelper::instance()->fetchTemplate($tpl, $vars, $node);
        } else {
            throw new HttpResponseException(view($tpl, $vars));
        }
    }

    /**
     * 模板变量赋值
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return $this
     */
    public function assign($name, $value = '')
    {
        if (is_string($name)) {
            $this->$name = $value;
        } elseif (is_array($name)) foreach ($name as $k => $v) {
            if (is_string($k)) $this->$k = $v;
        }
        return $this;
    }

    /**
     * 数据回调处理机制
     * @param string $name 回调方法名称
     * @param mixed $one 回调引用参数1
     * @param mixed $two 回调引用参数2
     * @return boolean
     */
    public function callback($name, &$one = [], &$two = [])
    {
        if (is_callable($name)) {
            return call_user_func($name, $this, $one, $two);
        }
        foreach ([$name, "_{$this->request->action()}{$name}"] as $method) {
            if (method_exists($this, $method)) if (false === $this->$method($one, $two)) {
                return false;
            }
        }
        return true;
    }
}