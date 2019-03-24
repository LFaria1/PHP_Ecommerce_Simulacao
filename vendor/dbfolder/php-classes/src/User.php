<?php

namespace dbfolder;

use \dbfolder\DB\Sql;
use \dbfolder\Model;
use \dbfolder\Mailer;
define("SECRET_IV", pack("a16","senha"));
define("SECRET", pack("a16","senha"));
class User extends Model{
    const SESSION = "USER";
    const SESSION_ERROR = "USER_ERROR";
    const REGISTER_ERROR = "USER_REGISTER_ERROR";
    const SUCCESS = "USER_SUCCESS_MSG";

    public static function getFromSession(){
        $user = new User();
        if(isset($_SESSION[User::SESSION])&& (int)$_SESSION[User::SESSION]>0){
            
            $user->setData($_SESSION[User::SESSION]);
            
        }

        return $user;
    }
    public static function checkLogin($inadmin=true){
        

        if(!isset($_SESSION[User::SESSION])
        ||
        !$_SESSION[User::SESSION]
        ||
        !(int)$_SESSION[User::SESSION]["iduser"]>0){
            return false;            
        }else{
            if($inadmin ===true && (bool)$_SESSION[User::SESSION]["inadmin"]===true){                
                return true;                
            }else if($inadmin===false){                
                return true;                
            }else{                
                return false;                
            }
        }

    }
    
    //getters and setters create by class Model when setData is called;
    public static function login($login,$password){

            $sql = new Sql();

            $result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN",array(":LOGIN"=>$login));

            if(count($result)=== 0){
                User::setMsgError("Usuário inexistente ou senha inválida.");
                header("Location: /login");
                exit;
            }
            
            $data=$result[0];         
           
            if(password_verify($password,$data["despassword"])==true){
                $user = new User();
                
                $user->get($data["idperson"]);              
                $_SESSION[User::SESSION] = $user->getValues();
                
                if(isset($_SESSION[Cart::SESSION])&& (int)$_SESSION[Cart::SESSION]["idcart"]>0){
                    $cart = new Cart();
                    if(Cart::verifyIfCartExists((int)$user->getiduser())){
                        $cart->getFromUserID((int)$user->getiduser());
                        Cart::mergeCarts($cart->getidcart());
                        $_SESSION[Cart::SESSION]= $cart->getValues();                 

                    }else{                   
                                        
                    $cart->get($_SESSION[Cart::SESSION]["idcart"]);
                    $cart->setData(["iduser"=>$user->getiduser()]);
                    $cart->save();
                    $_SESSION[Cart::SESSION]= $cart->getValues();
                    }
                }
                
                return $user;

            }else{
                User::setMsgError("Usuário inexistente ou senha inválida.");
                header("Location: /login");
                exit;
            }
    }
    public static function verifyLogin($inadmin=true){

        if(!User::checkLogin($inadmin)){
            if($inadmin){
            header("Location: /admin/login");
            exit;
            }else{
            header("Location: /login");
            exit;
            }
        }

    }
    public static function logout(){
        $_SESSION[User::SESSION] = NULL;
        $_SESSION[Cart::SESSION] = NULL;
    }

    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY desperson");
    }
    public function createUser(){
        $sql = new Sql();
        // var_dump($this);
        // exit;
        $result= $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(), 
            ":despassword"=>User::getPasswordHash($this->getdespassword()), 
            ":desemail"=>$this->getdesemail(), 
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        // $result= $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
        //     "aaa","aaa","aaaa","aaa@aaa.com","123123123","1"));
        //pq deu merge
        $this->setData($result[0]);
    }
    
    public function get($id){
        $sql = new Sql();
        $result= $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser",array(":iduser"=>$id));
        $this->setData($result[0]);
    }

    public function update(){

        $sql = new Sql();
        // var_dump($this);
        // exit;
        $result= $sql->select("CALL sp_usersupdate_save(:iduser,:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(), 
            ":despassword"=>User::getPasswordHash($this->getdespassword()), 
            ":desemail"=>$this->getdesemail(), 
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        // $result= $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
        //     "aaa","aaa","aaaa","aaa@aaa.com","123123123","1"));
        //pq deu merge
        $this->setData($result[0]);

    }

    public function delete(){

        $sql = new Sql();
        $sql->query("CALL sp_users_delete(:iduser)",array(":iduser"=>$this->getiduser()));
    }

    public function forgot($email,$inadmin=true){

        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email",array(":email"=>$email));
        if(count($result)===0){
            throw new \Exception("Email não encontrado",);
        }else{
            $result2=$sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",array(
                ":iduser"=>$result[0]["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));
            if(count($result2)===0){
                
                throw new \Exception("Não foi possível recuperar a senha");

            }else{
                $data= $result[0];
                $recovery=$result2[0];
                //ainda não é nem perto de segura
                $code =base64_encode(openssl_encrypt($recovery["idrecovery"],"AES-128-CBC",
                SECRET,
                0,
                SECRET_IV));
                if($inadmin){
                $link= "http://ecommerce.php.com/admin/forgot/reset?code=$code";
                }else{
                $link= "http://ecommerce.php.com/forgot/reset?code=$code";
                }

                $mailer= new Mailer($data["desemail"],$data["desperson"],"Redefinir senha","forgot",array("name"=>$data["desperson"],"link"=>$link));
                $mailer->sendMail();

                return $data;

                // $code2 = openssl_decrypt(base64_decode($code),"AES-128-CBC",
                // SECRET,
                // 0,
                // SECRET_IV);

                // var_dump($code2);
                // exit;

            }
        }

    }
    public static function validateForgotPassword($code){

        $code2 = openssl_decrypt(base64_decode($code),"AES-128-CBC",
                SECRET,
                0,
                SECRET_IV);
        
        $sql= new Sql();
        $result = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a 
        INNER JOIN tb_users b USING(iduser) 
        INNER JOIN tb_persons c USING(idperson) 
        WHERE a.idrecovery=:code
        AND 
        a.dtrecovery IS NULL
        AND 
        DATE_ADD(a.dtregister, INTERVAL 10 HOUR)>=NOW()",array(":code"=>$code2));
        if(count($result)==0){
            throw new \Exception("Código de resgate não é mais válido");
        }else{
            return $result[0];
        }
            
    }

    public static function invalidateForgotLink($idrecovery){

        $sql = new Sql();
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery= NOW() WHERE idrecovery = :idrecovery",
        array(":idrecovery"=>$idrecovery));

    }
    public function setPassword($password){
        $password = User::getPasswordHash($password);
        $sql = new Sql();
        $sql->query("UPDATE tb_users SET DESPASSWORD = :password WHERE iduser = :iduser",array(":password"=>$password,":iduser"=>$this->getiduser()));
    }

    public static function setMsgError($msg){
        $_SESSION[User::SESSION_ERROR] =$msg;
    }

    public static function getMsgError(){

        $msg = (isset($_SESSION[User::SESSION_ERROR]))?$_SESSION[User::SESSION_ERROR]:"";
        User::clearMsgError();
        return $msg;
    }

    public static function clearMsgError(){
        $_SESSION[User::SESSION_ERROR]= NULL;
    }
    public static function getPasswordHash($password){
        return password_hash($password, PASSWORD_DEFAULT,[
            "cost"=>12
        ]);
    }
    public static function setRegisterError($msg){
        $_SESSION[User::REGISTER_ERROR] =$msg;

    }
    public static function getRegisterError(){
        $msg = (isset($_SESSION[User::REGISTER_ERROR]))?$_SESSION[User::REGISTER_ERROR]:"";
        User::clearRegisterError();
        return $msg;        

    }
    public static function clearRegisterError(){
        $_SESSION[User::REGISTER_ERROR]= NULL;
    }
    public static function checkLoginExist($login){
        $sql= new Sql();
        $result = $sql->select("SELECT * FROM tb_persons WHERE desemail = :login",["login"=>$login]);
        return (count($result)>0);
    }

    public static function setSuccess($msg){
        $_SESSION[User::SUCCESS] =$msg;

    }
    public static function getSuccess(){
        $msg = (isset($_SESSION[User::SUCCESS]))?$_SESSION[User::SUCCESS]:"";
        User::clearSuccess();
        return $msg;        

    }
    public static function clearSuccess(){
        $_SESSION[User::SUCCESS]= NULL;
    }

    public function getOrders(){
        $sql = new Sql();
       
            $sql = new Sql();
            $result = $sql->select("SELECT * from tb_orders a 
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser",[":iduser"=>$this->getiduser()]);
           
        if(count($result)>0){
            $this->setData($result[0]);
            }
        return $result;
        
    }

    public static function getPages($page=1,$itensPerPage=3){
        $start = ($page-1)*$itensPerPage;
        $sql = new Sql();

        $result = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS * 
            FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY desperson LIMIT $start,$itensPerPage;        
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
            FROM tb_users a 
            INNER JOIN tb_persons b USING(idperson) 
            WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
            ORDER BY b.desperson LIMIT $start,$itensPerPage;        
        ",[":search"=>"%".$search."%"]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return ["data"=>$result,
        "total"=>$resultTotal[0]["nrtotal"],
        "pages"=>ceil($resultTotal[0]["nrtotal"]/$itensPerPage)];

    }
}

?>