<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;
use \dbfolder\User;

class Order extends Model{

    public function save(){

        $sql = new Sql();
        $result = $sql->select("CALL sp_orders_save(:idorder,:idcart,:iduser,:idstatus,:idaddress,:vltotal)",[
            ":idorder"=>$this->getidorder(),
            ":idcart"=>$this->getidcart(),
            ":iduser"=>$this->getiduser(),
            ":idstatus"=>$this->getidstatus(),
            ":idaddress"=>$this->getidaddress(),
            ":vltotal"=>$this->getvltotal()

        ]);

        if(count($result)>0){
            $this->setData($result[0]);
        }

    }
    public function get($idorder){
        $sql = new Sql();
        $result = $sql->select("SELECT * from tb_orders a 
        INNER JOIN tb_ordersstatus b USING(idstatus)
        INNER JOIN tb_carts c USING(idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.idorder = :idorder",[":idorder"=>$idorder]);
       
    if(count($result)>0){
        $this->setData($result[0]);
        }
    }

    public static function listAll(){
        
        $sql = new Sql();
        $result = $sql->select("SELECT * from tb_orders a 
        INNER JOIN tb_ordersstatus b USING(idstatus)
        INNER JOIN tb_carts c USING(idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        ORDER BY a.dtregister DESC");

        return $result;

    }

    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder",[":idorder"=>$this->getidorder()]);
        
    }

    
    public static function getPages($page=1,$itensPerPage=3){
        $start = ($page-1)*$itensPerPage;
        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS * FROM
            tb_orders a 
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC LIMIT $start,$itensPerPage;        
        ");

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return ["data"=>$result,
        "total"=>$resultTotal[0]["nrtotal"],
        "pages"=>ceil($resultTotal[0]["nrtotal"]/$itensPerPage)];

    }

    public static function getPagesSearch($search,$page=1,$itensPerPage=3){
        $start = ((int)$page-1)*$itensPerPage;

        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products 
            WHERE desproduct LIKE :search 
            ORDER BY desproduct LIMIT $start,$itensPerPage;        
        ",[":search"=>"%".$search."%"]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return ["data"=>$result,
        "total"=>$resultTotal[0]["nrtotal"],
        "pages"=>ceil($resultTotal[0]["nrtotal"]/$itensPerPage)];

    }
      


}
?>