<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | 跨域请求支持
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\Config;
use sower\Request;
use sower\Response;
class Crossdomain
{
    protected $cookieDomain;

    protected $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE',
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With',
    ];

    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array   $header
     * @return Response
     */
    public function handle($request, Closure $next, ? array $header = [])
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin && strpos($this->cookieDomain, $origin)) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }

        if ($request->method(true) == 'OPTIONS') {
            return Response::create()->code(204)->header($header);
        }

        return $next($request)->header($header);
    }
}
