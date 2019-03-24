<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;
use \dbfolder\User;

class Cart extends Model{

    const SESSION = "cart";
    const SESSION_ERROR = "CartError";

    public static function getSession(){

        $cart = new Cart();

        if(isset($_SESSION[Cart::SESSION])&& (int)$_SESSION[Cart::SESSION]["idcart"]>0){

            $cart->get($_SESSION[Cart::SESSION]["idcart"]);

        }else{ 

             if(!(int)$cart->getidcart()>0){              

                $data = ["dessessionid"=>session_id()];            
                
                if(User::checkLogin(false)){
                    $user = User::getFromSession();
                    $data["iduser"] = $user->getiduser();

                }
                $cart->setData($data);
            
                $cart->save();

                $_SESSION[Cart::SESSION]= $cart->getValues();
            
            }

            
        }
        return $cart;

    }

    public function save(){
        $sql = new Sql();
        
        $result = $sql->select("CALL sp_carts_save(:idcart,:dessessionid,:iduser,:deszipcode,:vlfreight,:nrdays)",array(
            ":idcart"=>$this->getidcart(),
            ":dessessionid"=>$this->getdessessionid(),
            ":iduser"=>$this->getiduser(),
            ":deszipcode"=>$this->getdeszipcode(),
            ":vlfreight"=>$this->getvlfreight(),
            ":nrdays"=>$this->getnrdays()
        ));

        $this->setData($result[0]);
    }
    public function get($id){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",["idcart"=>$id]);
        
        if(count($result)>0){
            $this->setData($result[0]);
            }
    }
    public function getFromSessionID(){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE dessesionid = :dessesionid",["dessesionid"=>session_id()]);

        if(count($result)>0){
        $this->setData($result[0]);
        }
    }
    public function getFromUserID($id){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE iduser = :iduser",[":iduser"=>$id]);

        if(count($result)>0){
        $this->setData($result[0]);
        }
    }

    public static function verifyIfCartExists($id){
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_carts WHERE iduser=:iduser" ,["iduser"=>$id]);
        if(count($result)>0){
            return true;
        }else{
            return false;
        }

    }

    public function mergeCarts($idcartafter){
        $idcartbefore=(int)$_SESSION[Cart::SESSION]["idcart"];
        $sql = new Sql();
        $sql->query("UPDATE tb_cartsproducts SET idcart = :idcartafter WHERE idcart=:idcartbefore",
        [":idcartafter"=>$idcartafter,":idcartbefore"=>$idcartbefore]);
                        

    }

    public function addProduct(Product $product){
        $sql = new Sql();
        $sql->query("INSERT INTO tb_cartsproducts (idcart,idproduct) VALUES (:idcart, :idproduct)",[
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
        ]);
        $this->getSum();
    }
    public function removeProduct(Product $product, $all=false){
        $sql = new Sql();
        if($all){
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL",[
                ":idcart"=>$this->getidcart(),
                ":idproduct"=>$product->getidproduct()
            ]);
        }else{
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1 ",[
                ":idcart"=>$this->getidcart(),
                ":idproduct"=>$product->getidproduct()
            ]);

        }
        $this->getSum();
    }

    public function listProducts(){
        $sql = new Sql();
        $result=$sql->select("SELECT b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight,b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
        FROM tb_cartsproducts a INNER JOIN tb_products b
        ON a.idproduct= b.idproduct
        WHERE a.idcart = :idcart AND a.dtremoved IS NULL GROUP BY b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight,b.desurl 
        ORDER BY b.desproduct", [":idcart"=>$this->getidcart()]);
    
        return Product::checkProduct($result);
    }

    public function getSum(){
        $sql = new Sql();
        $result = $sql->select("SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
        FROM tb_products a INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
        WHERE b.idcart = :idcart AND dtremoved IS NULL",[":idcart"=>$this->getidcart()]);

        if(count($result)>0){
            return $result[0];
            }else{
                return [];
            }        
    }
    public function setFreight($zipcode){
        
        $zipcode = str_replace("-","",$zipcode);
        $total = $this->getSum();

        if($total["vllength"]<16){
            $total["vllength"]=16;
        }
        if($total["vlheight"]<16){
            $total["vlheight"]=16;
        }
        if($total["vlwidth"]<16){
            $total["vlwidth"]=16;
        }

        if($total["nrqtd"]>0){
            
            $parametros = array();
            $parametros['nCdEmpresa'] = '';
            $parametros['sDsSenha'] = '';
            $parametros['sCepOrigem'] = '';
            $parametros['sCepDestino'] = $zipcode;
            $parametros['nVlPeso'] = $total["vlweight"];
            $parametros['nCdFormato'] = '1';
            $parametros['nVlComprimento'] = $total["vllength"];
            $parametros['nVlAltura'] = $total["vlheight"];
            $parametros['nVlLargura'] = $total["vlwidth"];
            $parametros['nVlDiametro'] = 1;
            $parametros['sCdMaoPropria'] = 'n';
            $parametros['nVlValorDeclarado'] = $total["vlprice"];
            $parametros['sCdAvisoRecebimento'] = 'n';
            $parametros['StrRetorno'] = 'xml';
            $parametros['nCdServico'] = '40010';
            $parametros = http_build_query($parametros);
            $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';

            $curl = curl_init($url.'?'.$parametros);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $xml = curl_exec($curl);
            $xml = simplexml_load_string($xml);

            $result = $xml->cServico;
          
            if($result->MsgErro != ""){
                Cart::setMsgError($result->MsgErro);
            }else{
                Cart::clearMsgError();
            }
            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight($result->Valor);
            $this->setdeszipcode($zipcode);
            $this->save();

            return (string)$result;
                
            
       }else{

       }

    }
    public static function formatValue($value){
        $value = str_replace(".","",$value);
        return str_replace(",",".",$value);
    }
    public static function setMsgError($msg){
        $_SESSION[Cart::SESSION_ERROR] =$msg;
    }

    public static function getMsgError(){

        $msg = (isset($_SESSION[Cart::SESSION_ERROR]))?$_SESSION[Cart::SESSION_ERROR]:"";
        Cart::clearMsgError();
        return $msg;
    }

    public static function clearMsgError(){
        $_SESSION[Cart::SESSION_ERROR]= "";
    }

    public function updateFreight(){

        if($this->getdeszipcode() !=""){
            $this->setFreight($this->getdeszipcode());
        }
    }

    public function getValues(){
        $this->updateFreight();
        $total= $this->getSum();
        $this->setvlsubtotal($total["vlprice"]);
        $this->setvltotal($total["vlprice"]+$this->getvlfreight());
        return parent::getValues();
    }

    
    
}

?>