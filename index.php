<?php 
session_start();
require_once("vendor/autoload.php");
require_once("functions.php");

use \Slim\Slim;
use \model\Page;
use \model\Adminpage;
use \model\User;
use \model\Category;
use \model\Product;
use \model\Cart;
use \model\Address;
use \model\Order;
use \model\OrderStatus;
use Rain\Tpl\Exception;

$app = new Slim();

//remove on release
//$app->config('debug', true);

require_once("index-admin.php");
/**
 * User Pages Routes
 */

/**
 * Initial page Route
 */
$app->get('/', function() {
	$products = Product::listAll();
	$page = new Page();	
	$page->setTpl("index",["products"=>Product::checkProduct($products)]);
	//$sql = new model\DB\Sql();
	//$result = $sql->select("SELECT * FROM tb_users");
	//echo json_encode($result);
});

/**
 * Category page route
 * Show to user all itens in the choosen category
 */
$app->get("/category/:id",function($id){
	 $pageNumber = (isset($_GET["page"]))?(int)$_GET["page"]:1;
	 $category = new Category();
	 $category->get((int)$id);
	 $page = new Page();
	 $pagination = $category->getProductsPage($pageNumber);
	 $pages = [];
	 for($i=1; $i<=$pagination["pages"];$i++){
			array_push($pages,[
				"link"=>"/category/".$category->getidcategory()."?page=".$i,
				"page"=>$i
			]);
	 }
	 $page->setTpl("category",["category"=>$category->getValues(),
	 "products"=>$pagination["data"],
	 "pages"=>$pages]);

});

/**
 * Product page route
 * Show to user information about the choosen product
 */
$app->get("/products/:desurl",function($desurl){
	$product = new Product();
	$product->getFromUrl($desurl);

	$page= new Page();
	$page->setTpl("product-detail",[
		"product"=>$product->getValues(),
		"categories"=>$product->getCategories()
	]);
});

/**
 * CART ROUTES
 */

/**
 * Show all items in user's cart
 */
$app->get("/cart",function(){
	$cart = Cart::getSession();

	$page = new Page();
	$page->setTpl("cart",["cart"=>$cart->getValues(),
	"products"=>$cart->listProducts(),
	"error"=>Cart::getMsgError()]);
});

/**
 * Add a product to the cart
 */
$app->get("/cart/:idproduct/add",function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$qtd = isset($_GET["qtd"])?(int)$_GET["qtd"]:1;
	$cart = Cart::getSession();
	for ($i =0; $i<$qtd;$i++){
	$cart->addProduct($product);
	}
	header("Location: /cart");
	exit;
});

/**
 * Remove ONE unit of a product from cart
 */
$app->get("/cart/:idproduct/removeone",function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getSession();
	$cart->removeProduct($product);
	header("Location: /cart");
	exit;
});

/**
 * Remove ALL units of a product from cart
 */
$app->get("/cart/:idproduct/remove",function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getSession();
	$cart->removeProduct($product,true);
	header("Location: /cart");
	exit;
});

/**
 * Set freight cost of order
 */
$app->post("/cart/freight",function(){

	$cart = Cart::getSession();
	$zipcode =$_POST["zipcode"];
	$cart->setFreight($zipcode);
	
	header("Location: /cart");
	exit;
});

/**
 * END CART ROUTES
 */

/**
 * Render Checkout Page
 */
$app->get("/checkout",function(){
	User::verifyLogin(false);
	$address = new Address();
	$cart = Cart::getSession();

	if(isset($_GET["zipcode"])){
		$_GET["zipcode"] = $cart->getdeszipcode();
		$address->loadFromCEP($_GET["zipcode"]);
		$cart->setdeszipcode($_GET["zipcode"]);
		$cart->save();
		$cart->getSum();
	}	

	if(!$address->getdesaddress()) $address->setdesaddress("");
	if(!$address->getdescomplement()) $address->setdescomplement("");
	if(!$address->getdesdistrict()) $address->setdesdistrict("");
	if(!$address->getdescity()) $address->setdescity("");
	if(!$address->getdesnumber()) $address->setdesnumber("");
	
	$page= new Page();
	Address::clearMsgError();
	$page->setTpl("checkout",[
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
		"products"=>$cart->listProducts(),
		"checkoutError"=>Address::getMsgError()]);


});

/**
 * Checkout order
 * redirect to /order/$id page
 */
$app->post("/checkout",function(){
	User::verifyLogin(false);
	Address::clearMsgError();
	if(!isset($_POST["deszipcode"]) || $_POST["deszipcode"]===""){

	}
	if(!isset($_POST["desaddress"]) || $_POST["desaddress"]===""){
		Address::setMsgError("Informe o Endereço");
		header("Location: /checkout");
		exit;
	}
	if(!isset($_POST["desdistrict"]) || $_POST["desdistrict"]===""){
		Address::setMsgError("Informe o Bairro");
		header("Location: /checkout");
		exit;
	}
	//outros

	$user = User::getFromSession();
	$address = new Address();
	$_POST["idperson"]=$user->getidperson();
	$address->setData($_POST);
	$address->save();
	$cart = Cart::getSession();
	$total=$cart->getSum();
	$order = new Order();
	$order->setData([
		"idcart"=>$cart->getidcart(),
		"idaddress"=>$address->getidaddress(),
		"iduser"=>$user->getiduser(),
		"idstatus"=>OrderStatus::EM_ABERTO,
		"vltotal"=>$total["vlprice"]+$cart->getvlfreight()
	]);
	$order->save();
	$_SESSION[Cart::SESSION]=NULL;
	header("Location: /order/".$order->getidorder());
	exit;
		
});

/**
 * LOGIN PAGE ROUTES
 */
$app->get("/login",function(){
	$page = new Page();
	$page->setTpl("login",["error"=>User::getMsgError(),
	"errorR"=>User::getRegisterError(),
	"registerValues"=>(isset($_SESSION["registerValues"])?
	$_SESSION["registerValues"]:["name"=>"","email"=>"","phone"=>"","login"=>""])]);

	
});
$app->post("/login",function(){	
	$user = User::login($_POST["login"],$_POST["password"]);	
	header("Location:/");
	exit;	
});


/**
 * User logout route
 */
$app->get("/logout",function(){
	User::logout();
	header("Location:/login");
	exit;
});

/**
 * Register new user Route
 */
$app->post("/register",function(){
	$_SESSION["registerValues"] = $_POST;

	if(!isset($_POST["login"]) || $_POST["login"]==""){
		User::setRegisterError("Preencha o campo Nome de Usuário.");
		header("Location: /login");
		exit;
	}
	if(!isset($_POST["name"]) || $_POST["name"]==""){
		User::setRegisterError("Preencha o campo nome.");
		header("Location: /login");
		exit;
	}
	if(!isset($_POST["email"]) || $_POST["email"]==""){
		User::setRegisterError("Preencha o campo email.");
		header("Location: /login");
		exit;
	}
	if(!isset($_POST["password"]) || $_POST["password"]==""){
		User::setRegisterError("Preencha o campo senha.");
		header("Location: /login");
		exit;
	}
	if(User::checkLoginExist($_POST["email"])){
		User::setRegisterError("Email já cadastrado.");
		header("Location: /login");
		exit;

	}
	$user = new User();
	$user->setData([
		"inadmin"=>0,
		"deslogin"=>$_POST["login"],
		"desperson"=>$_POST["name"],
		"desemail"=>$_POST["email"],
		"despassword"=>$_POST["password"],
		"nrphone"=>$_POST["phone"],
	]);
	$user->createUser();
	User::login($_POST["login"],$_POST["password"]);
	header("Location:/");
	exit;
});

/**
 * FORGOT PAGE ROUTES
 */
$app->get("/forgot",function(){
    $page = new Page();
	$page->setTpl("forgot");
});

$app->post("/forgot",function(){
    $email = $_POST["email"];
    $user = User::forgot($email,false);
    header("Location: /forgot/sent");
    exit;
});

$app->get("/forgot/sent",function(){
    $page = new Page();
    $page->setTpl("forgot-sent");
});

$app->get("/forgot/reset",function(){
$user = User::validateForgotPassword($_GET["code"]);
$page = new Page();
    $page->setTpl("forgot-reset",array("name"=>$user["desperson"],"code"=>$_GET["code"]));
});

$app->post("/forgot/reset",function(){
    $forgotUser = User::validateForgotPassword($_POST["code"]);
    User::invalidateForgotLink($forgotUser["idrecovery"]);
    $user = new User();
    $user->get((int)$forgotUser["iduser"]);
    $password = password_hash($_POST["password"],PASSWORD_DEFAULT, ["cost"=>12]);

    $user->setPassword($password);
	$page = new Page();
    $page->setTpl("forgot-reset-success");
});
/**
 * END FORGOT PAGE ROUTES
 */

/**
 * USER PROFILE ROUTES
 */
$app->get("/profile",function(){
	User::verifyLogin(false);
	
	$user = new User();
	$user = User::getFromSession();

	//
	$user->get($user->getidperson());              
	$_SESSION[User::SESSION] = $user->getValues();
	//
	
	$page = new Page();
	$page->setTpl("profile",["profileMsg"=>User::getSuccess(),"profileError"=>User::getMsgError(),"user"=>$user->getValues()]);
});

$app->post("/profile",function(){
	if(!isset($_POST["desperson"]) || $_POST["desperson"] ==""){
		User::setMsgError("Preencha o seu Nome");
		header("Location: /profile");
		exit;
	}
	if(!isset($_POST["desemail"]) || $_POST["desemail"] ==""){
		User::setMsgError("Preencha corretamente o seu Email");
		header("Location: /profile");
		exit;
	}

	User::verifyLogin(false);
	$user = new User();
	$user = User::getFromSession();

	if($_POST["desemail"]!==$user->getdesemail()){
		if(User::checkLoginExist($_POST["desemail"])){
			User::setMsgError("Este endereço de Email já está cadastrado.");
			header("Location: /profile");
			exit;
		}
	}
	$_POST["inadmin"] =$user->getinadmin();
	$_POST["despassword"] = $user->getdespassword();
	$user->setData($_POST);
	$user->update();
	User::setSuccess("Mudanças feitas com sucesso");
	
	header("Location: /profile");
	exit;
});
/** 
 * END USER PROFILE ROUTES 
 */

/**
 * Render order page
 */
$app->get("/order/:idorder",function($idorder){
		User::verifyLogin(false);

		$order = new Order();
		$order->get((int)$idorder);

		$page = new Page();
		$page->setTpl("payment",["order"=>$order->getValues()]);
});

//NOT IMPLEMENTED
$app->get("/boleto/:idorder",function($idorder){
		User::verifyLogin(false);

});

/**
 * PROFILE PAGES ROUTES
 */
$app->get("/profile/orders",function(){
		User::verifyLogin(false);
		$user = User::getFromSession();
		$page = new Page();
		$page->setTpl("profile-orders",["orders"=>$user->getOrders()]);

});

$app->get("/profile/orders/:idorder",function($idorder){
		User::verifyLogin(false);
		$order = new Order();
		$order->get((int)$idorder);
		$cart = new Cart();
		$cart->get((int)$order->getidcart());
		$user = User::getFromSession();
		$page = new Page();
		$page->setTpl("profile-orders-detail",["order"=>$order->getValues(),
		"cart"=>$cart->getValues(),
		"products"=>$cart->listProducts()]);

});
$app->get("/profile/change-password",function(){
		User::verifyLogin(false);
		$page = new Page();
		$page->setTpl("profile-change-password",["changePassError"=>User::getMsgError(),"changePassSuccess"=>User::getSuccess()]);

});
$app->post("/profile/change-password",function(){
		User::verifyLogin(false);

		if(!isset($_POST["current_pass"]) || $_POST["current_pass"]==""){
			User::setMsgError("Digite a senha atual");
			header("Location:/profile/change-password");
			exit;
		}
		if(!isset($_POST["new_pass"]) || $_POST["new_pass"]==""){
			User::setMsgError("Digite a nova senha");
			header("Location:/profile/change-password");
			exit;
		}
		if(!isset($_POST["new_pass_confirm"]) || $_POST["new_pass_confirm"]==""){
			User::setMsgError("Confirme a nova senha");
			header("Location:/profile/change-password");
			exit;
		}

		if($_POST["new_pass"] !== $_POST["new_pass_confirm"]){
			User::setMsgError("");
			header("Location:/profile/change-password");
			exit;
		}
		
		if($_POST["current_pass"] == $_POST["new_pass"]){
			User::setMsgError("A nova senha deve ser diferente da atual");
			header("Location:/profile/change-password");
			exit;
		}
		$user= User::getFromSession();
		var_dump($_POST["current_pass"],$user->getdespassword());
		exit;
		
		if(!password_verify($_POST["current_pass"],$user->getdespassword())){
			User::setMsgError("A senha está inválida");
			header("Location:/profile/change-password");
			exit;
		}

		$user->setdespassword($_POST["new_pass"]);
		$user->update();

		User::setSuccess("Senha alterada com sucesso");

		header("Location:/profile/change-password");
		exit;
});
/**
 * END PROFILE PAGE ROUTES
 */	

//.htaccess else routes wont work
$app->run();

?>