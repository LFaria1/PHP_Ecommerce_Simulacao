<?php
use \dbfolder\User;
use \dbfolder\Cart;

function formatPrice($value){
    if(!$value==null){
    return number_format($value,2,",",".");
    }else{
        return 0;
    }
}

function checkLogin($inadmin = true){
    return User::checkLogin($inadmin);
}
function getUserName(){
    $user = User::getFromSession();
    return $user->getdesperson();
}

function getCartNrqtd(){
    $cart = Cart::getSession();
    $total=$cart->getSum();
    return $total["nrqtd"];
}
function getCartVlSubTotal(){
    $cart = Cart::getSession();
    $total=$cart->getSum();
    return formatPrice($total["vlprice"]);
}

?>
