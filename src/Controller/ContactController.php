<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Model\Contact;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, TranslatorInterface $translator, MailerInterface $mailer): Response
    {
        $contact = new Contact();

        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            $mailContent = $this->renderView('mail/contact.html.twig', compact('contact'));

            $from = new Address($this->getParameter('app.mail_from'), $this->getParameter('app.mail_from_name'));
            $to = new Address($this->getParameter('app.mail_reply_to'), $this->getParameter('app.mail_reply_to_name'));

            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject($translator->trans('contact.email.subject', [
                    'firstname' => $contact->getFirstname(),
                    'lastname' => $contact->getLastname(),
                ]))
                ->text($mailContent)
                ->html($mailContent);

            $mailer->send($email);

            $this->addFlash('success', 'contact.form.success');

            return $this->redirectToRoute('app_contact');
        }


        return $this->render('contact/index.html.twig', [
            'slug' => 'contact',
            'form' => $form->createView(),
            'controller_name' => 'ContactController',
        ]);
    }
}
