<?php

$state = Extend::state('poll');
Route::set($state['path'] . '/%*%', function($path = "") use($state) {
    HTTP::status(200);
    $token = Request::post('token');
    if (!$token || $token !== Session::get(Guardian::$config['session']['token'])) {
        HTTP::status(404);
        exit;
    }
    $f = LOT . DS . $path . DS . 'poll.data';
    $data = e(File::open($f)->read([]));
    if ($k = Request::post('key', '0', false)) {
        $v = Request::post('value', 0);
        if (!isset($data[$k])) {
            $data[$k] = $v ?: 1;
        } else {
            $data[$k] = $data[$k] + $v;
        }
        if ($data[$k] < 1) unset($data[$k]);
        $id = 'poll.' . md5($k . ':' . $path);
        if ($v === -1) {
            Cookie::reset($id);
        } else {
            Cookie::set($id, 1, $state['cookie']);
        }
        Hook::fire('on.poll.' . (empty($data) ? 'reset' : 'set'), [$k, $v, $data, $path, Request::post()]);
        if (!Message::$x) {
            if (!empty($data)) {
                File::write(To::json($data))->saveTo($f, 0600);
            } else {
                File::open($f)->delete();
            }
        }
    }
    exit;
});