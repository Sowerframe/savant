<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | 多语言加载
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\App;
use sower\Lang;
use sower\Request;
class Langs
{
    /** @var Lang */
    protected $lang;

    /** @var App */
    protected $app;

    public function __construct(Lang $lang, App $app)
    {
        $this->lang = $lang;
        $this->app  = $app;
    }

    /**
     * 路由初始化（路由规则注册）
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return void
     */
    public function handle($request, Closure $next)
    {
        // 自动侦测当前语言
        $langset = $this->lang->detect();

        if ($this->lang->defaultLangSet() != $langset) {
            // 加载系统语言包
            $this->lang->load([
                $this->app->getSowerPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php',
            ]);

            $this->app->Langs($langset);
        }

        $this->lang->saveToCookie($this->app->cookie);

        return $next($request);
    }
}
