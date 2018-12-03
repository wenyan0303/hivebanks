<?php

require_once '../inc/common.php';
require_once 'db/ba_base.php';
require_once 'db/ba_bind.php';
require_once '../inc/judge_format.php';
require_once 'db/ba_log_bind.php';
require_once "db/com_option_config.php";

header("cache-control:no-cache,must-revalidate");
header("Content-Type:application/json;charset=utf-8");

/*
========================== 代理商注册（手机） ==========================
GET参数
  country_code    国家代码
  cellphone       手机号码
  pass_word       密码原文
  pass_word_hash  密码HASH
  sms_code        手机验证码
  bit_type        代理数字货币类型
返回
  errcode = 0     请求成功
说明
*/

php_begin();
$args = array('country_code', 'cellphone', 'pass_word_hash', 'sms_code', 'bit_type','pass_word');
chk_empty_args('GET', $args);

// 国家代码
$country_code = get_arg_str('GET', 'country_code');
// 手机号码
$cellphone = get_arg_str('GET', 'cellphone');
// 密码HASH
$pass_word_hash = get_arg_str('GET', 'pass_word_hash');

// 原始密码
$pass_word = get_arg_str('GET', 'pass_word');
// 验证码
$sms_code = get_arg_str('GET', 'sms_code');
// 代理数字货币类型
$bit_type = get_arg_str('GET', 'bit_type');

// 用户基本信息
$data_base = array();
// 用户绑定信息
$data_bind = array();
// 密码绑定信息
$data_bind_pass = array();
// 创建用户ba_id
$ba_id = get_guid();
// 创建用户bind_id
$bind_id = get_guid();
// 判断是否为手机号
$is_phone = isPhone($cellphone);
if (!$is_phone) {
    exit_error('100', 'The input format is incorrect');
}
//判断密码强度
$score = Determine_password_strength($pass_word);
if($score <= 3){
    exit_error('119','密码过于简单请重新设置!');
}
$bit_type_row = bit_type_exist_or_not($bit_type);
if(!$bit_type_row)
    exit_error("122","当前la不支持此代理类型");
if(ba_can_reg_or_not()["option_value"] != 1)
    exit_error("120","当前la未开通注册");

$variable = 'cellphone';
$cellphone_num = $country_code . '-' . $cellphone;
// 获取最新的创建记录
$row = get_ba_id_by_variable($variable, $cellphone_num);

$variable_code = 'phone_code';
$timestamp = time();
$rec = get_ba_log_bind_by_variable($variable_code , $cellphone_num);
if(!$rec){
    exit_error('113','无匹配的认证信息');
}
// 基本信息参数整理
$data_base['ba_id'] = $ba_id;
$data_base['ba_type'] = $bit_type;

// 绑定手机信息整理
$data_bind['bind_id'] = get_guid();
$data_bind['ba_id'] = $ba_id;
$data_bind['bind_name'] = 'cellphone';
$data_bind['bind_info'] = $cellphone_num;
$data_bind['bind_flag'] = 2;
$data_bind['bind_type'] = 'text';

// 绑定登录密码参数整理
$data_bind_pass = array();
$data_bind_pass['bind_id'] = get_guid();
$data_bind_pass['ba_id'] = $ba_id;
$data_bind_pass['bind_type'] = 'hash';
$data_bind_pass['bind_name'] = 'password_login';
$data_bind_pass['bind_info'] = $pass_word_hash;
$data_bind_pass['bind_flag'] = 1;
// 手机号码地址已经存在
if ($row) {
    // 是否注册验证完成
    switch ($row['bind_flag']) {
        case 0:
            // exit_ok('Please verify email as soon as possible!');
            break;
        case 1:
            exit_error('105', 'Registered users please login directly!');
            break;
        case 9:
            break;
    }
}

//判断是否可以验证
if($rec['limt_time'] > $timestamp && $rec['count_error'] != 0){
    exit_error('116',$rec['limt_time'] - $timestamp);
}
if($rec){
    // 绑定参数设定
    $data_log_bind = $rec;
    $data_log_bind['count_error'] = $rec['count_error']+1;
    $data_log_bind['limt_time'] = $timestamp + pow(2,$data_log_bind['count_error']);
    unset($data_log_bind['log_id']);
}
//超时判断
if((strtotime($rec['ctime']) + 5*60) < $timestamp){
    $phone_used = upd_ba_phone_log_bind_info($rec['ba_id']);
    exit_error('111','信息过期，请重试！');
}
if(empty($rec) ||$rec['bind_salt'] != $sms_code || $rec['bind_info']!= $cellphone_num)
    exit_error('110','验证码信息不正确');
if(($rec['limt_time'] + 29*60) < $timestamp){
    exit_error("111","验证超时");
}

$data_base['base_amount'] = 0;
$data_base['lock_amount'] =0;
$data_base['ba_level'] = 0;
$data_base['ba_account'] = "hivebanks_".$cellphone;
$data_base['security_level'] = 2;
$data_base['utime'] = time();
$data_base['ctime'] = date("Y-m-d H:i:s");
$ret = ins_base_ba_reg_base_info($data_base);
$bind_phone = ins_bind_ba_reg_bind_info($data_bind);
$bind_pass = ins_bind_ba_reg_bind_info($data_bind_pass);
// 判断用户绑定信息和用户基本信息是否都写入成功
if ($ret && $bind_phone && $bind_pass) {
    exit_ok();
} else {
    exit_error();
}
