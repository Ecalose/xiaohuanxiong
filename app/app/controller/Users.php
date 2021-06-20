<?php


namespace app\app\controller;


use app\common\Common;
use app\common\RedisHelper;
use app\model\Book;
use app\model\Comments;
use app\model\User;
use app\model\UserFavor;
use app\model\UserFinance;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\Validate;

class Users extends BaseAuth
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

    public function bookshelf()
    {
        $favors = UserFavor::where('user_id', '=', $this->uid)->select();
        foreach ($favors as &$favor) {
            try {
                $book = Book::findOrFail($favor->book_id);
                $favor['book'] = $book;
                if (substr($book->cover_url, 0, 4) === "http") {

                } else {
                    $book->cover_url = $this->img_domain . $book->cover_url;
                }
            } catch (ModelNotFoundException $e) {

            }
        }
        $result = [
            'success' => 1,
            'favors' => $favors
        ];
        return json($result);
    }

    public function delfavors()
    {
        $ids = explode(',', input('ids')); //书籍id;
        $this->userService->delFavors($this->uid, $ids);
        return json(['success' => 1, 'msg' => '删除收藏']);
    }

    public function switchfavor()
    {
        $redis = RedisHelper::GetInstance();
        if ($redis->exists('favor_lock:' . $this->uid)) { //如果存在锁
            return json(['success' => 0, 'msg' => '操作太频繁']);
        } else {
            $redis->set('favor_lock:' . $this->uid, 1, 3); //写入锁

            $book_id = input('book_id');
            $where[] = ['book_id', '=', $book_id];
            $where[] = ['user_id', '=', $this->uid];
            try {
                $userFaver = UserFavor::where($where)->findOrFail();
                $userFaver->delete(); //如果已收藏，则删除收藏
                return json(['success' => 1, 'isfavor' => 0]); //isfavor为0表示未收藏
            } catch (DataNotFoundException $e) {
            } catch (ModelNotFoundException $e) {
                $userFaver = new UserFavor();
                $userFaver->book_id = $book_id;
                $userFaver->user_id = $this->uid;
                $userFaver->save();
                return json(['success' => 1, 'isfavor' => 1]); //isfavor表示已收藏
            }
        }
    }

    public function history()
    {
        $redis = RedisHelper::GetInstance();
        $vals = $redis->hVals($this->redis_prefix . ':history:' . $this->uid);
        $books = array();
        foreach ($vals as $val) {
            $books[] = json_decode($val, true);
        }
        $result = [
            'success' => 1,
            'books' => $books
        ];
        return json($result);
    }

    public function getVipExpireTime()
    {
        $time = intval(session('vip_expire_time'));
        if (is_null($time) || empty($time)) {
            try {
                $user = User::findOrFail($this->uid);
                $time = $user->vip_expire_time;
                session('vip_expire_time', $time);
            } catch (ModelNotFoundException $e) {
                return [ 'success' => 0, 'msg' => '用户错误'];
            }
        }
        $result = [
            'success' => 1,
            'time' => $time
        ];
        return json($result);
    }

    public function update()
    {
        $nick_name = input('nickname');
        $password = input('password');
        try {
            $user = User::findOrFail($this->uid);
            $user->nick_name = $nick_name;
            if (empty($password) || is_null($password)) {

            } else {
                $user->password = $password;
            }
            $res = $user->save(['id' => $this->uid]);
            if ($res) {
                session('xwx_nick_name', $nick_name);
                return json(['success' => 1, 'msg' => '修改成功', 'userInfo' => $user]);
            } else {
                return json(['success' => 0, 'msg' => '修改失败']);
            }
        } catch (DataNotFoundException $e){
            return json(['success' => 0, 'msg' => '修改失败']);
        }

    }

    public function bindphone()
    {
        try {
            $user = User::findOrFail($this->uid);
            $code = trim(input('phonecode'));
            $phone = trim(input('phone'));
            if (verifycode($code, $phone) == 0) {
                return json(['success' => 0, 'msg' => '验证码错误']);
            }
            if (User::where('mobile', '=', $phone)->find()) {
                return json(['success' => 0, 'msg' => '该手机号码已经存在']);
            }
            $user->mobile = $phone;
            $user->save();
            session('xwx_user_mobile', $phone);
            return json(['success' => 1, 'msg' => '绑定成功']);
        } catch (DataNotFoundException $e) {
            return json(['success' => 0, 'msg' => '用户不存在']);
        } catch (ModelNotFoundException $e) {
            return json(['success' => 0, 'msg' => '用户不存在']);
        }

    }

    public function verifycode()
    {
        $phone = input('phone');
        $code = input('phonecode');
        if (is_null(session('xwx_sms_code')) || $code != session('xwx_sms_code')) {
            return json(['success' => 0, 'msg' => '验证码错误']);
        }
        if (is_null(session('xwx_cms_phone')) || $phone != session('xwx_cms_phone')) {
            return json(['success' => 0, 'msg' => '验证码错误']);
        }
        return json(['success' => 1, 'msg' => '验证码正确']);
    }

    public function resetpwd()
    {
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

    public function subComment()
    {
        $content = strip_tags(input('comment'));
        $book_id = input('book_id');

        $redis = RedisHelper::GetInstance();
        if ($redis->exists('comment_lock:' . $this->uid)) {
            return json(['msg' => '每10秒只能评论一次', 'success' => 0, 'isLogin' => 1]);
        } else {
            $comment = new Comments();
            $comment->user_id = $this->uid;
            $comment->book_id = $book_id;
            $comment->content = $content;
            $result = $comment->save();
            if ($result) {
                $redis->set('comment_lock:' . $this->uid, 1, 10);
                cache('comments:' . $book_id, null); //清除评论缓存
                return json(['msg' => '评论成功', 'success' => 1, 'isLogin' => 1]);
            } else {
                return json(['msg' => '评论失败', 'success' => 0, 'isLogin' => 1]);
            }
        }
    }

    public function getRewards()
    {
        $startItem = input('startItem');
        $pageSize = input('pageSize');
        $map = array();
        $map[] = ['user_id', '=', $this->uid];
        $map[] = ['usage', '=', 4]; //4为奖励记录
        $rewards = UserFinance::where($map)->limit($startItem, $pageSize)->select();
        return json([
            'success' => 1,
            'rewards' => $rewards,
            'startItem' => $startItem,
            'pageSize' => $pageSize
        ]);
    }

    public function isfavor()
    {
        $book_id = input('book_id');
        $isfavor = 0;
        $where[] = ['user_id', '=', $this->uid];
        $where[] = ['book_id', '=', $book_id];
        $userfavor = UserFavor::where($where)->find();
        if (!is_null($userfavor) || !empty($userfavor)) { //收藏本漫画
            $isfavor = 1;
        }
        $result = [
            'success' => 1,
            'isfavor' => $isfavor
        ];
        return json($result);
    }

    public function getUserInfo() {
        try {
            $user = User::findOrFail($this->uid);
            return json(['success' => 1, 'user' => $user]);
        } catch (ModelNotFoundException $e) {
            return json(['success' => 0, 'msg' => '用户信息获取错误']);
        }
    }

    public function setAutoPay() {
        try {
            $user = User::findOrFail($this->uid);
            $autopay = input('autopay');
            $user->autopay = $autopay;
            $result = $user->save();
            if ($result) {
                return json(['success' => 1, 'autopay' => $autopay]);
            } else {
                return json(['success' => 0, 'msg' => '设置失败']);
            }

        } catch (ModelNotFoundException $e) {
            return json(['success' => 0, 'msg' => $e->getMessage() ]);
        }
    }
}