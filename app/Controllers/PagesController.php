<?php
namespace App\Controllers;

use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class PagesController extends Controller{

  public function home(Request $request, Response $response) {
    if(empty($_SESSION['loggued_as'])) {
    $this->render($response, 'pages/home.twig');
    }
    else {
      $this->render($response, 'pages/preview.twig');
    }
  }

  public function getAccount(Request $request, Response $response) {

    if(!empty($_SESSION['loggued_as']))
    {
      $this->render($response, 'pages/account.twig');
    }
    else {
      $this->render($response, 'pages/home.twig');
    }
  }

  public function postAccount(Request $request, Response $response) {

    $pdo = $this->pdo;
    include_once ('Functions.php');
    $param = $request->getParams();
    if(!empty($param['name']) && !empty($param['password']))
    {
      $result = checkForAccount($param['name'], $param['password'], $pdo);

      if($result['name'] == True && $result['password'] == True )
      {
        $_SESSION['loggued_as'] = $param['name'];
        $_SESSION['Alert'] = "Connexion Succeeded";
        $this->render($response, 'pages/account.twig');
      }
      else if($result['name'] == True && $result['password'] == False)
      {
        $_SESSION['loggued_as'] = "";
        $_SESSION['Alert'] = "Wrong password";
        $this->render($response, 'pages/home.twig');
      }
      else if($result['name'] != True)
      {
        $_SESSION['loggued_as'] = "";
        $info = "Login not Found, Please Sign up";
        $this->render($response, 'pages/signUp.twig', array('login' => filter_var($param['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
      }
    }
    else {
      $_SESSION['loggued_as'] = "";
      $this->render($response, 'pages/home.twig');
    }
  }

  public function getMember(Request $request, Response $response , $info) {
    if(isset($info))
    {
      $_SESSION['Alert'] = $info['Alert'] ? $info['Alert'] : [];
    }
    $this->render($response, 'pages/signUp.twig');
  }

  public function postMember(Request $request, Response $response) {
    $pdo = $this->pdo;
    include_once ('Functions.php');
    $params = $request->getParams();
    $errors = [];

    $Validator = new Validator();
    if($Validator->validate('email',$request->getParam('email')) != true) {
    $errors['email'] = 'Your email is not valid.';}
    if($Validator->validate('pseudo',$request->getParam('pseudo','new')) != true) {
    $errors['pseudo'] = 'Pseudo not valid or already used.';}
    if($Validator->validate('content',$request->getParam('nom')) != true) {
    $errors['nom'] = 'Your lastname is empty.';}
    if($Validator->validate('content',$request->getParam('prenom')) != true) {
    $errors['prenom'] = 'Your firstname is empty.';}
    if($Validator->validate('password',$request->getParam('password')) != true) {
    $errors['password'] = 'Your password is to short';}
    if($Validator->validate('content',$request->getParam('answer')) != true) {
    $errors['answer'] = 'Your secret answer is empty.';}
    if($Validator->validate('gender',$request->getParam('gender')) != true) {
    $errors['gender'] = 'Your gender is not known by abracadamatcha what kind of creature are you?';}

    if(empty($errors))
    {
      $result = createNewAccount($params, $pdo);
      if ($result === true) {
        $this->flash('Account created, mail have been sent for activation' ,'success');
      }
      else {
        $this->flash($result ,'error');
        return $this->render($response, 'pages/signUp.twig');
      }
    }
    else {
    $this->flash($errors, 'error');
    return $this->render($response, 'pages/signUp.twig');
    }
    $this->render($response, 'pages/account.twig');
  }

  public function logout(Request $request, Response $response) {
    if(!empty($_SESSION['loggued_as'])) {
      $_SESSION['loggued_as'] = "";
    }
    $this->render($response, 'pages/home.twig');
  }

  public function getContact(Request $request, Response $response) {
    return $this->render($response, 'pages/contact.twig');

  }

  public function postContact(Request $request, Response $response) {
    $errors = [];

    $Validator = new Validator();
    if($Validator->validate('email',$request->getParam('email')) != true) {
    $errors['email'] = 'Your email is not valid.';}
    if($Validator->validate('pseudo',$request->getParam('pseudo')) != true) {
    $errors['pseudo'] = 'Login not found please create an account before contacting.';}
    if($Validator->validate('content',$request->getParam('content')) != true) {
    $errors['content'] = 'Something is wrong with your message.';}


    if (empty($errors)) {
      $message = \Swift_Message::newInstance('Message de contact')
        ->setFrom([$request->getParam('email') => $request->getParam('pseudo')])
        ->setTo('vincent.balart@hotmail.fr')
        ->setBody("Ceci est une copie du message que vous avez envoyé : {$request->getParam('email')} have send {$request->getParam('content')}");
      $this->mailer->send($message);
      $this->flash('Votre message a bien été envoyé');
    } else {
      $this->flash($errors, 'error');
    }
    return $this->redirect($response, 'contact');
  }
}
 ?>