<?php

namespace EchovoxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use EchovoxBundle\Entity\User;
use EchovoxBundle\Form\UserType;

/**
 * User controller.
 */
class UserController extends Controller
{

    /**
     * Creates a new User entity.
     *
     * @Route("/echovox", name="user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('EchovoxBundle\Form\UserType', $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->senEmail($user->getEmail(), $user->getAge(), $user->getText());
                $message = 'Thank you for your message';
            } catch (\Exception $e) {
                $logger = $this->get('logger');
                $logger->error($e->getMessage());
                $message = 'Message hasn\'t been inserted. We are working on this issue.';
            }

            return $this->render('EchovoxBundle:user:success.html.twig', array('message' => $message));
        }

        return $this->render('EchovoxBundle:user:new.html.twig', array(
                'user' => $user,
                'form' => $form->createView(),
        ));
    }

    /**
     * Send Email.
     *
     * @param string    $email
     * @param integer   $age
     * @param text      $text
     *
     * @return boolean
     */
    protected function senEmail($email, $age, $text)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($this->container->getParameter('echovox.subjectline'))
            ->setFrom($this->container->getParameter('echovox.email_from'))
            ->setTo($this->container->getParameter('echovox.email_to'))
            ->setBody(
                $this->renderView(
                    'EchovoxBundle:user:email.html.twig', array('email' => $email, 'age' => $age, 'text' => $text)
                ), 'text/html'
            )
            ->addPart(
            $this->renderView(
                'EchovoxBundle:user:email.txt.twig', array('email' => $email, 'age' => $age, 'text' => $text)
            ), 'text/plain'
        );

        $this->get('mailer')->send($message);
    }

}
