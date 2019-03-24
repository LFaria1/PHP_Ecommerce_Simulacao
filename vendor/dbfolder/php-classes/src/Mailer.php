<?php

namespace dbfolder;

use Rain\Tpl;

class Mailer{
    const USERNAME="lucassfaria2000@gmail.com";
    CONST PASSWORD = "";
    CONST FROM = "PHP ECOMMERCE";

    private $mail;

    public function __construct($receiverAddress,$receiverName,$subject,$tplName,$data=array()){
        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/email/",
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views/cache/",
            "debug"         => false // set to false to improve the speed
           );
        
           
        Tpl::configure( $config );
        $tpl = new Tpl();

        foreach ($data as $key => $value) {
           $tpl->assign($key,$value);
        }
        $html = $tpl->draw($tplName,true);
    require_once("vendor/autoload.php");
    $this->mail= new \PHPMailer;
    $this->mail->isSMTP();
    $this->mail->SMTPDebug = 0; //disable on production
    $this->mail->SMTPAuth = true;
    $this->mail->SMTPSecure = "ssl";
    $this->mail->Debugoutput = "html";
    $this->mail->Host = "smtp.gmail.com"; //"smtp.gmail.com;other.mailprovider.com"; 
    $this->mail->Port=465;
    $this->mail->Username =Mailer::USERNAME;
    $this->mail->Password = Mailer::PASSWORD;

    $this->mail->setFrom(Mailer::USERNAME,Mailer::FROM);
    //$this->mail->addReplyTo("lucassfaria2000@gmail.com","Lucas");
    $this->mail->addAddress($receiverAddress,$receiverName);
    $this->mail->Subject = $subject;
    $this->mail->msgHTML($html);
    $this->mail->AltBody = " ";

    //$this->mail->addAttachment("path");
  
    }
    public function sendMail(){
        return $this->mail->send();
        
    }
}

?>