<?php
use \model\User;
use \model\Cart;

/**
 * format float to Real(currency)
 */
function formatPrice($value){
    if(!$value==null){
    return number_format($value,2,",",".");
    }else{
        return 0;
    }
}

/**
 * CheckLogin
 */
function checkLogin($inadmin = true){
    return User::checkLogin($inadmin);
}

/**
 * Get user's username from $_SESSION
 */
function getUserName(){
    $user = User::getFromSession();
    return $user->getdesperson();
}

/**
 * returns the numbers of products in a cart
 */
function getCartNrqtd(){
    $cart = Cart::getSession();
    $total=$cart->getSum();
    return $total["nrqtd"];
}
/**
 * get cart subtotal
 */
function getCartVlSubTotal(){
    $cart = Cart::getSession();
    $total=$cart->getSum();
    return formatPrice($total["vlprice"]);
}

?>
