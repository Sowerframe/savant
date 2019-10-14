<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | 页面Trace中间件
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\App;
use sower\Config;
use sower\Request;
use sower\Response;
use sower\service\respond\Redirect;
class Debug
{

    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    /** @var App */
    protected $app;

    public function __construct(App $app, Config $config)
    {
        $this->app    = $app;
        $this->config = $config->get('trace');
    }

    /**
     * 页面Trace调试
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return void
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Trace调试注入
        if ($this->app->isDebug()) {
            $data = $response->getContent();
            $this->traceDebug($response, $data);
            $response->content($data);
        }

        return $response;
    }

    public function traceDebug(Response $response, &$content)
    {
        $config = $this->config;
        $type   = $config['type'] ?? 'Html';

        unset($config['type']);

        $trace = App::factory($type, '\\sower\\debug\\', $config);

        if ($response instanceof Redirect) {
            //TODO 记录
        } else {
            $output = $trace->output($this->app, $response, $this->app->log->getLog());
            if (is_string($output)) {
                // trace调试信息注入
                $pos = strripos($content, '</body>');
                if (false !== $pos) {
                    $content = substr($content, 0, $pos) . $output . substr($content, $pos);
                } else {
                    $content = $content . $output;
                }
            }
        }
    }
}
