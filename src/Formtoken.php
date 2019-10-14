<?php declare (strict_types = 1);
#coding: utf-8
# +-------------------------------------------------------------------
# | 表单令牌
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\savant;
use Closure;
use sower\exception\ValidateException;
use sower\Request;
use sower\Response;
class Formtoken
{

    /**
     * 表单令牌检测
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param string  $token 表单令牌Token名称
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $token = '')
    {
        $check = $request->checkToken($token ?: '__token__');

        if (false === $check) {
            throw new ValidateException('invalid token');
        }

        return $next($request);
    }

}
