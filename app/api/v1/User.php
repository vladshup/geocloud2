<?php
namespace app\api\v1;

class User extends \app\inc\Controller
{
    function __construct()
    {

    }

    function get_index()
    {
        $user = new \app\models\User(\app\inc\Input::getPath()->part(4));
        return \app\inc\Response::json($user->getData());
    }
}