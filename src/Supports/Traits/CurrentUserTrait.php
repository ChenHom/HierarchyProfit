<?php

namespace Hierarchy\Supports\Traits;

/**
 * trait CurrentUserTrait
 */
trait CurrentUserTrait
{
    public function currentLoginUser()
    {
        if (is_null($user = auth()->user())) {
            $user = ['id' => '', 'name' => 'system', 'alias' => '系統管理者'];
            $user['login_ip'] = 'localhost';
        } else {
            $user = $user->only('name', 'id', 'alias');
            $user['ip'] = ip();
        }
        return $user;
    }
}
