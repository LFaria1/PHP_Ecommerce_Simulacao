<?php

use \Slim\Slim;
use \model\Page;
use \model\Adminpage;
use \model\User;
use \model\Category;
use \model\Product;
use \model\Order;
use \model\Cart;
use model\OrderStatus;

/**
 * Admin Page Routes
 */

$app->get("/admin/users/:iduser/delete",function($iduser){
User::verifyLogin();
$user = new User();
$user->get((int)$iduser);
$user->delete();
header("Location: /admin/users");
exit;

});

$app->get("/admin", function(){
User::verifyLogin();

$page = new Adminpage();
$page->setTpl("index");
});

$app->get("/admin/forgot",function(){
    $page = new Adminpage([
        "header"=>false,
        "footer"=>false
    ]);
$page->setTpl("forgot");
});

$app->post("/admin/forgot",function(){
    $email = $_POST["email"];
    $user = User::forgot($email);
    header("Location: /admin/forgot/sent");
    exit;
});

$app->get("/admin/forgot/sent",function(){
    $page = new Adminpage([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset",function(){
$user = User::validateForgotPassword($_GET["code"]);
    $page = new Adminpage([
        "header"=>false,
        "footer"=>false
    ]);
    $page->setTpl("forgot-reset",array("name"=>$user["desperson"],"code"=>$_GET["code"]));
});

$app->post("/admin/forgot/reset",function(){
    $forgotUser = User::validateForgotPassword($_POST["code"]);
    User::invalidateForgotLink($forgotUser["idrecovery"]);
    $user = new User();
    $user->get((int)$forgotUser["iduser"]);
    $password = password_hash($_POST["password"],PASSWORD_DEFAULT, ["cost"=>12]);

    $user->setPassword($password);
        $page = new Adminpage([
            "header"=>false,
            "footer"=>false
        ]);
    $page->setTpl("forgot-reset-success");
});



$app->get("/admin/login", function(){
$page = new Adminpage([
    "header"=>false,
    "footer"=>false
]);
$page->setTpl("login");
});
$app->post("/admin/login",function(){

User::login($_POST["login"],$_POST["password"]);
header("Location: /admin");
exit;
});

$app->get("/admin/logout",function(){

User::logout();
header("Location: /admin/login");
exit;

});

$app->get("/admin/users",function(){
User::verifyLogin();
$search = (isset($_GET["search"])?$_GET["search"]:"");
$pages = (isset($_GET["page"])?(int)$_GET["page"]:1);


if($search != ""){
    $pagination = User::getPagesSearch($search,$pages);
}else{
    $pagination = User::getPages($pages);
}

$p=[];

for($x = 0; $x<$pagination["pages"];$x++){
    array_push($p,["href"=>"/admin/users?".http_build_query([
        "page"=>$x+1,
        "search"=>$search
    ]),"text"=>$x+1]);

}

$page = new Adminpage();

$page->setTpl("users",array("users"=>$pagination["data"],"search"=>$search,"pages"=>$p));
});

$app->get("/admin/users/create",function(){
User::verifyLogin();

$page = new Adminpage();
$page->setTpl("users-create");
});

$app->post("/admin/users/create",function(){

User::verifyLogin();
$user = new User();
$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
$_POST["despassword"] = password_hash($_POST["despassword"],PASSWORD_DEFAULT, ["cost"=>12]);
$user->setData($_POST);

$user->createUser();

header("Location: /admin/users");
exit;


});

$app->get("/admin/users/:iduser",function($iduser){
User::verifyLogin();
$user = new User();
$user->get((int)$iduser);

$page = new Adminpage();
$page->setTpl("users-update",array("user"=>$user->getValues()));
});
$app->post("/admin/users/:iduser",function($iduser){
User::verifyLogin();
$user = new User();
$user->get((int)$iduser);
$_POST["inadmin"]=(isset($_POST["inadmin"]))?1:0;
$user->setData($_POST);
$user->update();
header("Location: /admin/users");
exit;

});

$app->get("/admin/categories",function(){
User::verifyLogin();

$search = (isset($_GET["search"])?$_GET["search"]:"");
$pages = (isset($_GET["page"])?(int)$_GET["page"]:1);


if($search != ""){
    $pagination = Category::getPagesSearch($search,$pages);

}else{
    $pagination = Category::getPages($pages);
}

$p=[];

for($x = 0; $x<$pagination["pages"];$x++){
    array_push($p,["href"=>"/admin/categories?".http_build_query([
        "page"=>$x+1,
        "search"=>$search
    ]),"text"=>$x+1]);
}

$page = new Adminpage();

$page->setTpl("categories",["categories"=>$pagination["data"],"search"=>$search,"pages"=>$p]);

});

$app->get("/admin/categories/create",function(){
User::verifyLogin();
$page = new Adminpage();
$page->setTpl("categories-create");

});
$app->post("/admin/categories/create",function(){
User::verifyLogin();
$category = new Category();
$category->setData($_POST);
$category->save();
header("Location: /admin/categories");
exit;
});

$app->get("/admin/categories/:id/delete",function($id){
User::verifyLogin();	
$category = new Category();
$category->get((int)$id);
$category->delete();

header("Location: /admin/categories");
exit;

});
$app->get("/admin/categories/:id",function($id){
User::verifyLogin();	
$category = new Category();
$category->get((int)$id);

$page = new Adminpage();
$page->setTpl("categories-update",["category"=>$category->getValues()]);
});

$app->post("/admin/categories/:id",function($id){
User::verifyLogin();
$category = new Category();
$category->get((int)$id);

$category->setData($_POST);
$category->save();
header("Location: /admin/categories");
exit;

});

$app->get("/admin/products",function(){
    User::verifyLogin();
    
    $search = (isset($_GET["search"])?$_GET["search"]:"");
    $pages = (isset($_GET["page"])?(int)$_GET["page"]:1);

    if($search != ""){
    $pagination = Product::getPagesSearch($search,$pages);
    
    }else{
    $pagination = Product::getPages($pages);

    }

    $p=[];

    for($x = 0; $x<$pagination["pages"];$x++){
    array_push($p,["href"=>"/admin/products?".http_build_query([
        "page"=>$x+1,
        "search"=>$search
    ]),"text"=>$x+1]);
    }

    $page= new Adminpage();
    $page->setTpl("products",["products"=>$pagination["data"],"pages"=>$p,"search"=>$search]);    

});

$app->get("/admin/products/create",function(){
    User::verifyLogin();
    $page= new Adminpage();
    $page->setTpl("products-create");    
});
$app->post("/admin/products/create",function(){
    User::verifyLogin();
    $product = new Product();
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit;
});
//edit product
$app->get("/admin/products/:id",function($id){
    User::verifyLogin();
    $product = new Product();
    $product->get($id);
    $page= new Adminpage();
    $page->setTpl("products-update",["product"=>$product->getValues()]);

});
//post to update
$app->post("/admin/products/:id",function($id){
    User::verifyLogin();
    $product = new Product();
    $product->get($id);
    $product->setData($_POST);
    $product->save();
    $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit;

});

$app->get("/admin/products/:id/delete",function($id){
User::verifyLogin();
$product = new Product();
$product->get((int)$id);
$product->delete();

header("Location: /admin/products");
exit;
});

$app->get("/admin/categories/:idcategory/products",function($idcategory){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);
    // var_dump($category->getProducts());
    // exit;

    $page = new Adminpage();
    $page->setTpl("categories-products",
    ["category"=>$category->getValues(),
    "productsRelated"=>$category->getProducts(),
    "productsNotRelated"=>$category->getProducts(false)]
    );

});

$app->get("/admin/categories/:idcategory/products/:idproduct/add",function($idcategory,$idproduct){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);

    $product = new Product();
    $product->get((int)$idproduct);

    $category->addProduct($product);

    header("Location:/admin/categories/".$idcategory."/products");
    exit;

});
$app->get("/admin/categories/:idcategory/products/:idproduct/remove",function($idcategory,$idproduct){
    User::verifyLogin();
    $category = new Category();
    $category->get((int)$idcategory);

    $product = new Product();
    $product->get((int)$idproduct);

    $category->removeProduct($product);

    header("Location:/admin/categories/".$idcategory."/products");
    exit;

});

$app->get("/admin/orders/:idorder/delete",function($idorder){
    User::verifyLogin(true);
    $order = new Order();
    $order->get((int)$idorder);
    $order->delete();
    
    header("location:/admin/orders");
    exit;
});

$app->get("/admin/orders/:idorder/status",function($idorder){
    User::verifyLogin(true);

    $page = new Adminpage();
    $order = new Order();
    $order->get((int)$idorder);
  
    $page->setTpl("order-status",[
        "order"=>$order->getValues(),
        "status"=>OrderStatus::listAll(),
        "msgSuccess"=>OrderStatus::getSuccess(),
        "msgError"=>OrderStatus::getMsgError()
    ]);

});
$app->post("/admin/orders/:idorder/status",function($idorder){
    User::verifyLogin(true);

    //colocar o check do error
    if(!isset($_POST["idstatus"])||!(int)$_POST["idstatus"]>0){
        OrderStatus::setMsgError("ERRO");
        header("Location: /admin/orders/".$idorder."/status");
        exit;
    }

    $order = new Order();
    $order->get((int)$idorder);
    $order->setidstatus($_POST["idstatus"]);
    $order->save();
    OrderStatus::setSuccess("Foi");
    header("Location:/admin/orders/".$idorder."/status");
    exit;

});

$app->get("/admin/orders/:idorder",function($idorder){
    User::verifyLogin(true);

    $page = new Adminpage();
    $order = new Order();
    $order->get((int)$idorder);
    $cart = new Cart();

    $cart->get((int)$order->getidcart());
  
    $page->setTpl("order",[
        "order"=>$order->getValues(),
        "cart"=>$cart->getValues(),
        "products"=>$cart->listProducts()
    ]);
});

$app->get("/admin/orders",function(){
    User::verifyLogin(true);

       
    $search = (isset($_GET["search"])?$_GET["search"]:"");
    $pages = (isset($_GET["page"])?(int)$_GET["page"]:1);


    if($search != ""){
    $pagination = Order::getPagesSearch($search,$pages);
    
    }else{
    $pagination = Order::getPages($pages);

    }

    $p=[];

    for($x = 0; $x<$pagination["pages"];$x++){
    array_push($p,["href"=>"/admin/orders?".http_build_query([
        "page"=>$x+1,
        "search"=>$search
    ]),"text"=>$x+1]);
    }


    $page = new Adminpage();
    $page->setTpl("orders",[
        "orders"=>$pagination["data"],"search"=>$search,"pages"=>$p
    ]);


});

$app->get("/admin/users/:iduser/changepassword",function($iduser){
    User::verifyLogin(true);
    $user = new User();
    $user->get((int)$iduser);


    $page = new Adminpage();
    $page->setTpl("users-password",["user"=>$user->getValues(),"msgError"=>User::getMsgError(),"msgSuccess"=>User::getSuccess()]);

});
$app->post("/admin/users/:iduser/changepassword",function($iduser){
    User::verifyLogin(true);
    if(!isset($_POST["despassword"])||$_POST["despassword"]===""){
        User::setMsgError("Preencha a nova senha");
        header("Location:/admin/users/$iduser/changepassword");
        exit;        
    }

    if(!isset($_POST["despassword-confirm"])||$_POST["despassword-confirm"]===""){
        User::setMsgError("Preencha a confirmação da senha");
        header("Location:/admin/users/$iduser/changepassword");
        exit;        
    }
    if($_POST["despassword"]!==$_POST["despassword-confirm"]){
        User::setMsgError("Preencha corretamente as senhas");
        header("Location:/admin/users/$iduser/changepassword");
        exit;        
    }

    $user = new User();
    $user->get((int)$iduser);
    $user->setPassword($_POST["despassword"]);
    User::setSuccess("Senha alterada");

    header("Location:/admin/users/$iduser/changepassword");
    exit;

});


?>



