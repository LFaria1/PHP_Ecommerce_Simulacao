<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;
use \dbfolder\User;

class OrderStatus extends Model{

    CONST SESSION_ERROR = "ORDERSESSION_ERROR";
    CONST SUCCESS = "ORDERSESSION_SUCCESS";

    CONST EM_ABERTO = 1;
    CONST AGUARDADO_PAGAMENTO = 2;
    CONST PAGO = 3;
    CONST ENTREGUE = 4;

    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_ordersstatus ORDER BY desstatus");
    }

    public static function setMsgError($msg){
        $_SESSION[OrderStatus::SESSION_ERROR] =$msg;
    }

    public static function getMsgError(){

        $msg = (isset($_SESSION[OrderStatus::SESSION_ERROR]))?$_SESSION[OrderStatus::SESSION_ERROR]:"";
        OrderStatus::clearMsg();
        return $msg;
    }

    public static function clearMsg(){
        $_SESSION[OrderStatus::SESSION_ERROR]= "";
    }

    public static function setSuccess($msg){
        $_SESSION[OrderStatus::SUCCESS] =$msg;

    }
    public static function getSuccess(){
        $msg = (isset($_SESSION[OrderStatus::SUCCESS]))?$_SESSION[OrderStatus::SUCCESS]:"";
        OrderStatus::clearMsg();
        return $msg;        

    }

}

?>