<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Users;
use AppBundle\Form\ForgotStepFinalType;
use AppBundle\Form\ForgotType;
use AppBundle\Form\LoginType;
use AppBundle\Form\RegisterType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;

class AuthController extends Controller
{

    /**
     * @Route("/", name="login")
     * @Method({"GET", "POST"})
     */
    public function loginAction(Request $request) {

        $users = new Users();
        $form = $this->createForm(LoginType::class, $users);
        $form->add('Log\'in', SubmitType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // Récupération d'un champ sous laravel => $request->email
            // Récupération d'un champ sous Symfony avec (formType) => $form['email']->getData();
            // Récupération d'un champ sous Symfony avec formulaire HTML => $request->request->get('name');
            $email = $form['email']->getData();
            $password = $form['password']->getData();

            $repository = $this->getDoctrine()->getRepository(Users::class);
            $result = $repository->authorizedAccess($email, $password);
            if(count($result) === 1) {
                $this->get('session')->set('users', $result[0]);
                $users = $this->get('session')->get('users');
                $this->addFlash("info", "bienvenue ".$users->prenom);
                return $this->redirectToRoute('homepage.index');
            } else {
                $this->addFlash('error', 'Utilisateur ou mot de passe incorrect!');
                $this->redirectToRoute('login');
            }
        }
        return $this->render('Auth/login.html.twig', [
            'users' => $users,
            'form'  => $form->createView()
        ]);
    }

    /**
     * @Route("/register", name="register")
     * @Method({"GET", "POST"})
     * @return string
     */
    public function register(Request $request, \Swift_Mailer $mailer) {

        // Création formulaire
        $users = new Users();
        $form = $this->createForm(RegisterType::class, $users);
        $form->add('Create', SubmitType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $users->setCreatedAt(new \DateTime());
            $users->setFilename("default.jpg");
            $em->persist($users);
            $em->flush();

            // Envoie email
            $name = 'toto';
            $message = (new \Swift_Message('Hello world'))
                ->setFrom('bilel.bekkouche@gmail.com')
                ->setTo('bilel.bekkouche@hotmail.fr')
                ->setBody(
                    $this->renderView(
                        'Emails/contact.html.twig',
                        array('name' => $name)
                    ),
                    'text/html'
                );
            $mailer->send($message);

            $this->addFlash('success', 'Compte créer avec succès!');
            return $this->redirectToRoute('login');
        }

        return $this->render('Auth/register.html.twig', [
            'users' => $users,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/forgot", name="forgot")
     * @Method({"GET", "POST"})
     */
    public function forgotPass(Request $request, \Swift_Mailer $mailer){
        $users = new Users();
        $form = $this->createForm(ForgotType::class, $users);
        $form->add('send', SubmitType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // Générer password
            $newPass = $this->rand_passwd();

            // Vérification si email existe
            $users = $this->getDoctrine()->getRepository(Users::class);
            $validEmail = $users->findBy(array("email" => $form['email']->getData()));

            if(count($validEmail) === 1) {
                // Sauvegarde new pass champ forgot table
                $em = $this->getDoctrine()->getManager();
                $upd = $em->getRepository(Users::class)->find($validEmail[0]->id);

                if(!$upd){
                    throw $this->createNotFoundException(
                        'No users found for email '.$form['email']->getData()
                    );
                }

                $upd->setForgot($newPass);
                $em->flush();

                // Envoie email
                $message = (new \Swift_Message('Forgot password'))
                    ->setFrom('bilel.bekkouche@gmail.com')
                    ->setTo('bilel.bekkouche@hotmail.fr')
                    ->setBody(
                        $this->renderView(
                            'Emails/forgot.html.twig',
                            array('randomPassword' => $newPass)
                        ),
                        'text/html'
                    );
                $mailer->send($message);
            } else {
                $this->addFlash("error", "unknown email");
                $this->redirectToRoute("forgot");
            }
        }
        return $this->render("Auth/forgot.html.twig", [
            "users" => $users,
            "form"  => $form->createView()
        ]);
    }

    /**
     * @Route("/forgotStepFinal", name="forgotStepFinal")
     * @Method({"GET", "POST"})
     */
    public function forgotStepFinal(Request $request){
        $users = new Users();
        $form = $this->createForm(ForgotStepFinalType::class, $users);
        $form->add('validate', SubmitType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // Récupération objet users
            $result = $this->getDoctrine()->getRepository(Users::class);
            $u = $result->findBy(array("forgot" => $form['password']->getData()));

            if(count($u) === 1) {
                $em = $this->getDoctrine()->getManager();
                $upd = $em->getRepository(Users::class)->find($u[0]->id);

                if(!$upd){
                    throw $this->createNotFoundException(
                        'No users found for pass '.$form['password']->getData()
                    );
                }

                $upd->setPassword($form['password']->getData());
                $em->flush();

                $this->addFlash("success", "Modification du mot de passe effectue avec success!");
                return $this->redirectToRoute('login');
            }
        }

        return $this->render("Auth/forgotStepFinal.html.twig", [
            'users' => $users,
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/logout", name="logout")
     * @Method({"GET"})
     * @return string
     */
    public function logout() {
        // Session remove users unset($_SESSION['users']); => $this->get('session')->remove('users');
        // $session_destroy() => $this->get('session')->clear();
        $this->get('session')->remove('users');
        $this->addFlash('info', 'déconnection terminé!');
        return $this->redirectToRoute('homepage.index');
    }

    /**
     * Generate password random shuffle
     * @param int $length
     * @param string $chars
     * @return string
     */
    public function rand_passwd($length = 8, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
        return substr(str_shuffle($chars), 0, $length);
    }
}