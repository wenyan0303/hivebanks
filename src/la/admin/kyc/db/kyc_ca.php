<?php

//======================================
//  获取ca身份认证的信息列表
// 参数:
// 返回: rows            列表数组
//    log_id        绑定日志id
//    ca_id          caid
//    bind_type      绑定类型
//    bind_name      绑定名称
//    bind_info      绑定内容
//    bind_salt      绑定的盐
//    count_error    错误次数
//    limt_time      限定时间戳
//    ctime          创建时间
//======================================
function kyc_ca_bind_idcard_list()
{

    $db = new DB_COM();

    $sql = "select * from ca_log_bind where ((bind_type = 'text' and bind_name = 'name') or (bind_type='file' and bind_name='idPhoto')  
            or (bind_type='text' and bind_name='idNum')) and count_error = 0
        order by ctime desc ,ca_id desc ";

    $db->query($sql);
    $rows = $db->fetchAll();
    return $rows;
}


//======================================
//  获取指定用户的最新绑定身份证号
// 参数: $ca_id          ca id
// 返回: bind_info        绑定的身份证号
//======================================
function kyc_ca_bind_idnum($ca_id){
    $db = new DB_COM();
    $sql = "select bind_info from ca_log_bind where ca_id = '{$ca_id}' and bind_type = 'text' and bind_name = 'idNum' order by ctime desc  limit 1";
    $db->query($sql);
    $bind_info = $db -> getField($sql,'bind_info');
    return $bind_info;
}
//======================================
//  获取指定用户的最新绑定的姓名
// 参数: $ca_id          ca id
// 返回: bind_info        绑定的姓名
//======================================
function kyc_ca_bind_idname($ca_id){
    $db = new DB_COM();
    $sql = "select bind_info from ca_log_bind where ca_id = '{$ca_id}' and bind_type = 'text' and bind_name = 'name' order by ctime desc  limit 1";
    $db->query($sql);
    $bind_info = $db -> getField($sql,'bind_info');
    return $bind_info;
}
//======================================
// 函数: 查询最新的图像信息
// 参数: $ca_id       用户id
// 返回: $rows        bind数组

//======================================
function  get_ca_log_bind_info($ca_id){
    $db = new DB_COM();
    $sql = "select * from ca_log_bind where ca_id = '{$ca_id}' AND bind_type = 'file' and bind_name = 'idPhoto' order by ctime desc limit 1";
    $db->query($sql);
    $rows = $db->fetchAll();
    return $rows;
}

//======================================
// 函数: 添加绑定信息
// 参数: $ca_id       ca id
// 返回: $data_bind   bind数组
// 返回:true           成功
//     false         失败
//======================================
function ins_ca_info_to_ca_bind($ca_id,$data_bind)
{
    $data_bind['ca_id'] = $ca_id;
    $data_bind['bind_id'] = get_guid();
    $data_bind['utime'] = time();
    $data_bind['bind_flag'] ='1';
    $data_bind['ctime'] = date("Y-m-d H:i:s");
    $db = new DB_COM();
    $sql = $db->sqlInsert('ca_bind', $data_bind);
    $q_id = $db->query($sql);
    if ($q_id == 0)
        return false;
    return true;
}
//======================================
// 函数: 检测绑定信息是否已存在
// 参数: $ca_id       ca id
//      $data_bind_photo  图片绑定数组
// 返回: $rows         bind数组
//======================================
function check_ca_info($ca_id,$data_bind){
    $db = new DB_COM();
    $sql = "UPDATE ca_bind SET bind_flag = '9'  where ca_id = '{$ca_id}' AND bind_type = '{$data_bind['bind_type']}' and bind_name = '{$data_bind['bind_name']}'";
    $db->query($sql);
    $rows = $db->fetchAll();
    return $rows;
}
//======================================
// 函数: 获取ca注册列表
// 参数:
// 返回: $rows         ca注册信息数组
//======================================
function ca_reg_table(){

    $db = new DB_COM();
    $sql = "select * from ca_bind where (bind_flag=2 and bind_name = 'email') or (bind_flag=2 and bind_name = 'cellphone') order by ctime desc";
    $db->query($sql);
    return $db->fetchAll();
}
//======================================
// 函数: ca注册审核确认
// 参数: bind_id       绑定id
// 返回: count         影响的行数
//======================================
function ca_reg_confirm($bind_id){

    $time = time();
    $db = new DB_COM();
    $sql = "update ca_bind set bind_flag = 1, utime = '{$time}'  where bind_id = '{$bind_id}'";
    $db->query($sql);
    return $db->affectedRows();
}

//======================================
// 函数: ba注册审核拒绝
// 参数: bind_id       绑定id
// 返回: count         影响的行数
//======================================
function ca_reg_refuse($bind_id){
    $time = time();
    $db = new DB_COM();
    $sql = "update ca_bind set bind_flag = 3 , utime = '{$time}' where bind_id = '{$bind_id}'";
    $db->query($sql);
    return $db->affectedRows();
}
//======================================
// 函数: ca注册资料审核通过
// 参数:  log_id        日志id
// 返回: false          失败
//       res            信息数组
//======================================
function log_bind_pass($log_id){

    $db = new DB_COM();
    $sql_update = "update ca_log_bind set count_error = 2 where log_id = '{$log_id}'";
    $db->query($sql_update);
    if($db->affectedRows()) {
        $sql_row = "select ca_id,bind_type,bind_name,bind_info from ca_log_bind where log_id='{$log_id}'";
        $db->query($sql_row);
        $res = $db->fetchRow();
        $res['bind_id'] = get_guid();
        $res['utime'] = time();
        $res['ctime'] = date('Y-m-d H:i:s',time());
        $res['bind_flag'] = 1;
        return $res;
    }
    return false;
}
//======================================
// 函数: ca注册资料审核拒绝
// 参数:  log_id        日志id
// 返回: false          失败
//       true           成功
//======================================
function log_bind_refuse($log_id){

    $db = new DB_COM();
    $sql_update = "update ca_log_bind set count_error = 1 where log_id = '{$log_id}'";
    $db->query($sql_update);

    if($db->affectedRows())
        return true;
    return false;
}
//======================================
// 函数: ca注册资料写入库
// 参数:  data          信息数组
// 返回: false          失败
//       true           成功
//======================================
function ca_bind_insert($data){

    $db = new DB_COM();
    $sql = $db->sqlInsert('ca_bind',$data);
    if($db->query($sql))
        return true;
    return false;

}

//======================================
// 函数: 获取ca待审核的地址列表
// 参数:
// 返回: rows          信息数组
//======================================
function ca_address_list()
{
    $db = new DB_COM();
    $sql = "select * from ca_bind where bind_flag = 0 and bind_name = 'bit_address' order by ctime asc";
    $db->query($sql);
    return $db->fetchAll();
}

//======================================
// 函数: ba钱包地址审核通过
// 参数: bind_id        绑定id
//      ba_id           ba_ID
// 返回: false          失败
//       true           成功
//======================================
function ca_address_confirm($bind_id , $ca_id)
{
    $time = time();
    $db = new DB_COM();
    $sql =  "update ca_bind set bind_flag = 1 ,utime = '{$time}' where bind_id = '{$bind_id}'";
    $db->query($sql);
    $res = $db->affectedRows();
    $sql_time = "select ctime from ca_bind where bind_id = '{$bind_id}'";
    $db->query($sql_time);
    $res_time = $db->fetchRow();
    $res_time = $res_time['ctime'];
    //获取之前所有未处理的bit_address
    $sql_prev_log = "select bind_id from ca_bind where ca_id = '{$ca_id}' and ctime < '{$res_time}' and bind_name='bit_address' and bind_flag = 0";
    $db->query($sql_prev_log);
    $res_prev_log = $db->fetchAll();

    if($res_prev_log) {
        $sql = "update ca_bind set bind_flag= 9 ,utime = '{$time}' where ca_id = '{$ca_id}' and ctime < '{$res_time}' and bind_name='bit_address'";
        $db->query($sql);
        $res_nine = $db->affectedRows();

        if(!$res_nine)
            return false;
    }
    if($res)
        return true;
    return false;
}
//======================================
// 函数: ca钱包地址审核拒绝
// 参数: bind_id        绑定id
// 返回: false          失败
//       true           成功
//======================================
function ca_address_refuse($bind_id)
{
    $time = time();
    $db = new DB_COM();
    $sql = "update ca_bind set bind_flag = 2 ,utime = '{$time}' where bind_id = '{$bind_id}'";
    $db->query($sql);
    $res = $db->affectedRows();
    if($res)
        return true;
    return false;
}
