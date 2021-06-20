<?php


namespace app\app\controller;

use app\validate\Phone;

class Util extends Base
{
    public function sendcms()
    {
        $code = generateRandomString();
        $mobile = trim(input('mobile'));
        $data = [
            'mobile' => $mobile
        ];
        $validate = new Phone();
        if (!$validate->check($data)) {
            return json(['success' => 0, 'msg' => '手机格式不正确']);
        }
        $result = sendcode($mobile, $code);
        if ($result['status'] == 0) { //如果发送成功
            session('xwx_sms_code', $code); //写入session
            session('xwx_cms_phone', $mobile);
            return json(['success' => 1, 'code' => $code]);
        } else {
            return json(['success' => 0, 'msg' => $result['msg']]);
        }
    }

    public function statistics()
    {
        $statistics = config('site.statistics');
        return json(['success' => 1, 'statistics' => $statistics]);
    }
}