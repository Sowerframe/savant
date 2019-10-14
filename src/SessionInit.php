<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | Session初始化
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\App;
use sower\Request;
use sower\service\respond\Redirect as RedirectResponse;
use sower\Session;
class SessionInit
{

    /** @var Session */
    protected $session;

    /** @var App */
    protected $app;

    public function __construct(App $app, Session $session)
    {
        $this->app     = $app;
        $this->session = $session;
    }

    /**
     * Session初始化
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return void
     */
    public function handle($request, Closure $next)
    {
        // Session初始化
        $varSessionId = $this->app->config->get('session.var_session_id');
        $cookieName   = $this->app->config->get('session.name') ?: 'PHPSESSID';

        if ($varSessionId && $request->request($varSessionId)) {
            $sessionId = $request->request($varSessionId);
        } else {
            $sessionId = $request->cookie($cookieName) ?: '';
        }

        $this->session->setId($sessionId);

        $request->withSession($this->session);

        $response = $next($request)->setSession($this->session);

        $this->app->cookie->set($cookieName, $this->session->getId());

        // 清空当次请求有效的数据
        if (!($response instanceof RedirectResponse)) {
            $this->session->flush();
        }

        return $response;
    }
}
