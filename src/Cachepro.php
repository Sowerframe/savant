<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | 请求缓存处理
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\Cache;
use sower\Config;
use sower\Request;
use sower\Response;
class Cachepro
{
    /**
     * 缓存对象
     * @var Cache
     */
    protected $cache;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // 请求缓存规则 true为自动规则
        'request_cache_key'    => true,
        // 请求缓存有效期
        'request_cache_expire' => null,
        // 全局请求缓存排除规则
        'request_cache_except' => [],
        // 请求缓存的Tag
        'request_cache_tag'    => '',
    ];

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache  = $cache;
        $this->config = array_merge($this->config, $config->get('routes'));
    }

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param mixed   $cache
     * @return Response
     */
    public function handle($request, Closure $next, $cache = null)
    {
        if ($request->isGet() && false !== $cache) {
            $cache = $cache ?: $this->getRequestCache($request);

            if ($cache) {
                if (is_array($cache)) {
                    list($key, $expire, $tag) = $cache;
                } else {
                    $key    = str_replace('|', '/', $request->url());
                    $expire = $cache;
                    $tag    = null;
                }

                if (strtotime($request->server('HTTP_IF_MODIFIED_SINCE', '')) + $expire > $request->server('REQUEST_TIME')) {
                    // 读取缓存
                    return Response::create()->code(304);
                } elseif ($this->cache->has($key)) {
                    list($content, $header) = $this->cache->get($key);

                    return Response::create($content)->header($header);
                }
            }
        }

        $response = $next($request);

        if (isset($key) && 200 == $response->getCode() && $response->isAllowCache()) {
            $header                  = $response->getHeader();
            $header['Cache-Control'] = 'max-age=' . $expire . ',must-revalidate';
            $header['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
            $header['Expires']       = gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT';

            $this->cache->tag($tag)->set($key, [$response->getContent(), $header], $expire);
        }

        return $response;
    }

    /**
     * 读取当前地址的请求缓存信息
     * @access protected
     * @param Request $request
     * @return mixed
     */
    protected function getRequestCache($request)
    {
        $key    = $this->config['request_cache_key'];
        $expire = $this->config['request_cache_expire'];
        $except = $this->config['request_cache_except'];
        $tag    = $this->config['request_cache_tag'];

        if (false === $key) {
            // 关闭当前缓存
            return;
        }

        foreach ($except as $rule) {
            if (0 === stripos($request->url(), $rule)) {
                return;
            }
        }

        if ($key instanceof \Closure) {
            $key = call_user_func($key);
        } elseif (true === $key) {
            // 自动缓存功能
            $key = '__URL__';
        } elseif (strpos($key, '|')) {
            list($key, $fun) = explode('|', $key);
        }

        // 特殊规则替换
        if (false !== strpos($key, '__')) {
            $key = str_replace(['__APP__', '__CONTROLLER__', '__ACTION__', '__URL__'], [$request->app(), $request->controller(), $request->action(), md5($request->url(true))], $key);
        }

        if (false !== strpos($key, ':')) {
            $param = $request->param();
            foreach ($param as $item => $val) {
                if (is_string($val) && false !== strpos($key, ':' . $item)) {
                    $key = str_replace(':' . $item, $val, $key);
                }
            }
        } elseif (strpos($key, ']')) {
            if ('[' . $request->ext() . ']' == $key) {
                // 缓存某个后缀的请求
                $key = md5($request->url());
            } else {
                return;
            }
        }

        if (isset($fun)) {
            $key = $fun($key);
        }

        return [$key, $expire, $tag];
    }
}
