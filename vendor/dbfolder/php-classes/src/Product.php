<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;

class Product extends Model{
    
    //getters and setters create by class Model when setData is called;
   
    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    public static function checkProduct($list){
        foreach ($list as &$row){
            //making changes and putting back into list
            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();

        }
        return $list;
    }

    public function save(){
        $sql = new Sql();
        $result = $sql->select("CALL sp_products_save(:idproduct, :desproduct,:vlprice,:vlwidth,:vlheight,:vllength, :vlweight, :desurl)",array(
            ":idproduct"=>$this->getidproduct(),
            ":desproduct"=>$this->getdesproduct(),
            ":vlprice"=>$this->getvlprice(),
            ":vlwidth"=>$this->getvlwidth(),
            ":vlheight"=>$this->getvlheight(),
            ":vllength"=>$this->getvllength(),
            ":vlweight"=>$this->getvlweight(),
            ":desurl"=>$this->getdesurl(),                    
        ));
        $this->setData($result[0]);
    }

    public function get($id){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_products WHERE idproduct = :id",array(":id"=>$id));
        $this->setData($result[0]);
    }

    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_products WHERE idproduct = :id",array(":id"=>$this->getidproduct()));
    }
    public function checkPhoto(){
        if(file_exists($_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.
        "resources".DIRECTORY_SEPARATOR.
        "site".DIRECTORY_SEPARATOR.
        "img".DIRECTORY_SEPARATOR.$this->getidproduct().".jpg"
        )){
            $url= "/resources/site/img/".$this->getidproduct().".jpg";
        }else{
            $url= "/resources/site/img/product.jpg";
        }

        return $this->setdesphoto($url);
    }

    public function getValues(){
        
        $this->checkPhoto();
        $values = parent::getValues();
        return $values;
        
    }
    public function setPhoto($file){
        $ext = explode(".",$file["name"]);
        //last pos
        $ext = end($ext);
        
        switch($ext){
            case "jpg":
            case "jpeg":
            $image = imagecreatefromjpeg($file["tmp_name"]);
            break;

            case "gif":
            $image = imagecreatefromgif($file["tmp_name"]);
            break;
            case "png":
            $image = imagecreatefrompng($file["tmp_name"]);
            break;

        }
        imagejpeg($image,$_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.
        "resources".DIRECTORY_SEPARATOR.
        "site".DIRECTORY_SEPARATOR.
        "img".DIRECTORY_SEPARATOR.$this->getidproduct().".jpg");

        imagedestroy($image);
        $this->checkPhoto();

    }

    public function getFromUrl($desurl){
        $sql = new Sql();
        $result= $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1",[":desurl"=>$desurl]);

        $this->setData($result[0]);

    }
    public function getCategories(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :id",[":id"=>$this->getidproduct()]);
    }

    public static function getPages($page=1,$itensPerPage=3){
        $start = ($page-1)*$itensPerPage;
        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS * FROM
            tb_products ORDER BY desproduct LIMIT $start,$itensPerPage;        
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