<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;

class Category extends Model{
    
    //getters and setters create by class Model when setData is called;
   
    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    public function save(){
        $sql = new Sql();
        $result = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",array(
            ":idcategory"=>$this->getidcategory(),
            ":descategory"=>$this->getdescategory()
        ));
        Category::updateHTML();
        $this->setData($result[0]);
    }

    public function get($id){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :id",array(":id"=>$id));
        $this->setData($result[0]);
    }

    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_categories WHERE idcategory = :id",array(":id"=>$this->getidcategory()));
        Category::updateHTML();
    }

    public static function updateHTML(){

       $categories = Category::listAll();
       $html = [];
       foreach($categories as $row){
           array_push($html,'<li><a href="/category/'.$row["idcategory"].'">'.$row["descategory"].'</a></li>');
       }
       file_put_contents($_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR."/views/categories-menu.html",implode("",$html));

    }

    public function getProducts($related = true){

        $sql = new Sql();
        if($related==true){
        return $sql->select("SELECT * FROM tb_products WHERE idproduct IN(
            SELECT a.idproduct FROM tb_products a INNER JOIN tb_productscategories b ON
            a.idproduct = b.idproduct WHERE b.idcategory = :id
            )",[":id"=>$this->getidcategory()]);

        }else{
        return $sql->select("SELECT * FROM tb_products WHERE idproduct NOT IN(
            SELECT a.idproduct FROM tb_products a INNER JOIN tb_productscategories b ON
            a.idproduct = b.idproduct WHERE b.idcategory = :id
            )",[":id"=>$this->getidcategory()]);
        }

    }

    public function addProduct(Product $product){
        $sql= new Sql();

        $sql->query("INSERT INTO tb_productscategories(idcategory,idproduct) VALUES(:idcategory,:idproduct)",[
            ":idcategory"=>$this->getidcategory(),":idproduct"=>$product->getidproduct()
        ]);

    }
    public function removeProduct(Product $product){
        $sql= new Sql();

        $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct",[
            ":idcategory"=>$this->getidcategory(),":idproduct"=>$product->getidproduct()
        ]);
    }
    
    
    public function getProductsPage($page=1,$itensPerPage=3){
        $start = ($page-1)*$itensPerPage;
        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS * FROM tb_products a INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
            INNER JOIN tb_categories c ON c.idcategory = b.idcategory WHERE c.idcategory = :id LIMIT $start,$itensPerPage;        
        ",[":id"=>$this->getidcategory()]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return ["data"=>Product::checkProduct($result),
        "total"=>$resultTotal[0]["nrtotal"],
        "pages"=>ceil($resultTotal[0]["nrtotal"]/$itensPerPage)];

    }

    public static function getPages($page=1,$itensPerPage=3){
        $start = ($page-1)*$itensPerPage;
        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS * FROM
            tb_categories ORDER BY descategory LIMIT $start,$itensPerPage;        
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
            FROM tb_categories 
            WHERE descategory LIKE :search 
            ORDER BY descategory LIMIT $start,$itensPerPage;        
        ",[":search"=>"%".$search."%"]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return ["data"=>$result,
        "total"=>$resultTotal[0]["nrtotal"],
        "pages"=>ceil($resultTotal[0]["nrtotal"]/$itensPerPage)];

    }
}

?>