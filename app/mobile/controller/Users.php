<?php


namespace app\mobile\controller;

use app\model\Book;
use app\common\RedisHelper;
use app\model\UserFavor;
use app\model\User;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\View;

class Users extends BaseUc
{
    protected $userService;
    protected $financeService;
    protected $promotionService;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->userService = app('userService');
        $this->financeService = app('financeService');
        $this->promotionService = app('promotionService');
    }

    public function ucenter()
    {
        $balance = $this->financeService->getBalance($this->uid);
        try {
            $user = User::findOrFail($this->uid);
            $time = $user->vip_expire_time - time();
            $day = 0;
            if ($time > 0) {
                $day = ceil(($user->vip_expire_time - time()) / (60 * 60 * 24));
            }
            session('vip_expire_time', $user->vip_expire_time); //在session里更新用户vip过期时间
            View::assign([
                'balance' => $balance,
                'user' => $user,
                'header_title' => '个人中心',
                'day' => $day
            ]);
            return view($this->tpl);
        } catch (DataNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function bookshelf()
    {
        View::assign([
            'header_title' => '我的收藏'
        ]);
        return view($this->tpl);
    }

    public function getfavors() {
        $page = input('page');
        try {
            $where[] = ['user_id', '=', $this->uid];
            $data = UserFavor::where($where)->order('create_time', 'desc')->limit($page, 15)->selectOrFail();
            $books = array();
            foreach ($data as &$favor) {
                $book = Book::findOrFail($favor->book_id);
                if ($this->end_point == 'id') {
                    $book['param'] = $book['id'];
                } else {
                    $book['param'] = $book['unique_id'];
                }
                $books[] = $book;
            }
            return json(['err' => 0, 'books' => $books]);
        } catch (DataNotFoundException $e) {
            return json(['err' => 1]);
        } catch (ModelNotFoundException $e) {
            return json(['err' => 1]);
        }
    }

    public function delfavors()
    {
        if (request()->isPost()) {
            $ids = explode(',', input('ids')); //书籍id;
            $this->userService->delFavors($this->uid, $ids);
            return json(['err' => 0, 'msg' => '删除收藏']);
        } else {
            return json(['err' => 1, 'msg' => '非法请求']);
        }
    }

    public function history()
    {

        View::assign([
            'header_title' => '阅读历史'
        ]);
        return view($this->tpl);
    }

    public function userinfo()
    {
        if (request()->isPost()) {
            $nick_name = input('nickname');
            try {
                $user = User::findOrFail($this->uid);
                $user->nick_name = $nick_name;
                $result = $user->save();
                if ($result) {
                    session('xwx_nick_name', $nick_name);
                    return json(['msg' => '修改成功']);
                } else {
                    return json(['msg' => '修改失败']);
                }
            } catch (DataNotFoundException $e) {
            } catch (ModelNotFoundException $e) {
                return ['msg' => '用户不存在'];
            }
        }
        View::assign([
            'header_title' => '我的资料'
        ]);
        return view($this->tpl);
    }

    public function bindphone()
    {
        try {
            $user = User::findOrFail($this->uid);
            if ($this->request->isPost()) {
                $code = trim(input('txt_phonecode'));
                $phone = trim(input('txt_phone'));
                if (verifycode($code, $phone) == 0) {
                    return ['err' => 1, 'msg' => '验证码错误'];
                }
                if (User::where('mobile', '=', $phone)->find()) {
                    return ['err' => 1, 'msg' => '该手机号码已经存在'];
                }
                $user->mobile = $phone;
                if ($user->vip_expire_time < time()) { //说明vip已经过期
                    $user->vip_expire_time = time() + 1 * 30 * 24 * 60 * 60;
                } else { //vip没过期，则在现有vip时间上增加
                    $user->vip_expire_time = $user->vip_expire_time + 1 * 30 * 24 * 60 * 60;
                }
                session('vip_expire_time', $user->vip_expire_time); //在session里更新用户vip过期时间
                $user->save();

                session('xwx_user_mobile', $phone);
                return ['err' => 0, 'msg' => '绑定成功'];
            }
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
            return ['err' => 1, 'msg' => '该用户不存在'];
        }

        //如果用户手机已经存在，并且没有进行修改手机验证，也就是没有解锁缓存
        $redis = RedisHelper::GetInstance();
        if (!empty($user->mobile)) {
            if (!$redis->exists($this->redis_prefix . ':xwx_mobile_unlock:' . $this->uid)) {
                $this->redirect('/userphone'); //则重定向至手机信息页
            }
        }
        View::assign([
            'header_title' => '绑定手机'
        ]);
        return view($this->tpl);
    }

    public function verifyphone()
    {
        $phone = input('txt_phone');
        $code = input('txt_phonecode');
        if (verifycode($code, $phone) == 0) {
            return json(['err' => 1, 'msg' => '验证码错误']);
        }
        return json(['err' => 0]);
    }

    public function userphone()
    {
        try {
            $user = User::findOrFail($this->uid);
            View::assign([
                'user' => $user,
                'header_title' => '管理手机'
            ]);
            return view($this->tpl);
        } catch (DataNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function promotion()
    {
        $rewards = cache('rewards:' . $this->uid);
        if (!$rewards) {
            $rewards = $this->promotionService->getRewardsHistory($this->uid);
        }

        $sum = cache('rewards:sum:' . $this->uid);
        if (!$sum) {
            $sum = $this->promotionService->getRewardsSum($this->uid);
        }

        $shortUrl = config('site.mobile_domain').'?pid='.session('xwx_user_id');
        View::assign([
            'rewards' => $rewards,
            'promotion_rate' => (float)config('payment.promotional_rewards_rate') * 100,
            'reg_reward' => config('payment.reg_rewards'),
            'promotion_sum' => $sum,
            'shortUrl' => $shortUrl,
            'header_title' => '推广赚钱'
        ]);
        return view($this->tpl);
    }

    public function resetpwd()
    {
        if (request()->isPost()) {
            $pwd = input('password');
            $validate = new \think\Validate;
            $validate->rule('password', 'require|min:6|max:21');

            $data = [
                'password' => $pwd,
            ];
            if (!$validate->check($data)) {
                return json(['msg' => '密码在6到21位之间', 'success' => 0]);
            }
            try {
                $user = User::findOrFail($this->uid);
                $user->password = $pwd;
                $user->save();
                return json(['msg' => '修改成功', 'success' => 1]);
            } catch (DataNotFoundException $e) {
                return json(['success' => 0, 'msg' => '用户不存在']);
            } catch (ModelNotFoundException $e) {
                return json(['success' => 0, 'msg' => '用户不存在']);
            }
        }
        return view($this->tpl);
    }
}