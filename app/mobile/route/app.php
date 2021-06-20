<?php
use think\facade\Route;

Route::rule('/'.BOOKCTRL.'/:id', 'mobile/books/index');
Route::rule('/'.BOOKLISTACT, 'mobile/booklist/index');
Route::rule('/getBooks', 'mobile/booklist/getBooks');
Route::rule('/getOptions', 'mobile/booklist/getOptions');
Route::rule('/'.CHAPTERCTRL.'/:id', 'mobile/chapters/index');
Route::rule('/'.SEARCHCTRL.'/[:keyword]', 'mobile/index/search');
Route::rule('/'.RANKCTRL, 'mobile/rank/index');
Route::rule('/'.UPDATEACT, 'mobile/update/index');
Route::rule('/getUpdate', 'mobile/update/getBooks');
Route::rule('/'.AUTHORCTRL.'/:id', 'mobile/authors/index');
Route::rule('/article/:id', 'index/articles/index');
Route::rule('/articlelist', 'index/articles/list');
Route::rule('/topic/:id', 'index/topics/index');
Route::rule('/addfavor', 'mobile/books/addfavor');
Route::rule('/commentadd', 'mobile/books/commentadd');
Route::rule('/login', 'mobile/account/login');
Route::rule('/register', 'mobile/account/register');
Route::rule('/logout', 'mobile/account/logout');

Route::rule('/ucenter', 'mobile/users/ucenter');
Route::rule('/bookshelf', 'mobile/users/bookshelf');
Route::rule('/getfavors', 'mobile/users/getfavors');
Route::rule('/history', 'mobile/users/history');
Route::rule('/userinfo', 'mobile/users/userinfo');
Route::rule('/delfavors', 'mobile/users/delfavors');
Route::rule('/delhistory', 'mobile/users/delhistory');
Route::rule('/updateUserinfo', 'mobile/users/update');
Route::rule('/bindphone', 'mobile/users/bindphone');
Route::rule('/userphone', 'mobile/users/userphone');
Route::rule('/sendcms', 'mobile/users/sendcms');
Route::rule('/verifyphone', 'mobile/users/verifyphone');
Route::rule('/recovery', 'mobile/account/recovery');
Route::rule('/resetpwd', 'mobile/users/resetpwd');
Route::rule('/leavemsg', 'mobile/users/leavemsg');
Route::rule('/message', 'mobile/users/message');
Route::rule('/promotion', 'mobile/users/promotion');

Route::rule('/wallet', 'mobile/finance/wallet');
Route::rule('/getBuyHistory', 'mobile/finance/getBuyHistory');
Route::rule('/chargehistory', 'mobile/finance/chargehistory');
Route::rule('/spendinghistory', 'mobile/finance/spendinghistory');
Route::rule('/buyhistory', 'mobile/finance/buyhistory');
Route::rule('/charge', 'mobile/finance/charge');
Route::rule('/feedback', 'mobile/finance/feedback');
Route::rule('/buychapter', 'mobile/finance/buychapter');
Route::rule('/vip', 'mobile/finance/vip');
Route::rule('/kami', 'mobile/finance/kami');
Route::rule('/vipexchange', 'mobile/finance/vipexchange');