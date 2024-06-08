<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\EventPlaceManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EmailManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly  EventPlaceManager $eventPlaceManager,
    )
    {
    }

    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $this->entityManager->getRepository(Event::class)->findAll(),
        ]);
    }

    #[isGranted('ROLE_USER')]
    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $userInscrit = false;
        /** @var User $user */
        $user = $this->getUser();

        if($user !== null){

            foreach ($event->getParticipants() as $participant) {
                if ($participant === $user) {
                    $userInscrit = true;
                    break;
                }
            }
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'userInscrit'=> $userInscrit,
            'remainingSeats'=>$this->eventPlaceManager->computeRemainingSeats($event)
        ]);
    }

    #[Route('/{id}/register', name: 'app_event_register', methods: ['GET'])]
    public function register(Event $event, EntityManagerInterface $entityManager,EmailManager $emailManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if($user !== null && $this->eventPlaceManager->computeRemainingSeats($event) > 0){
            $event->addParticipant($user);
            $entityManager->persist($event);
            $entityManager->flush();

            $email = (new Email())
                ->from('contact.squadron70@gmail.com')
                ->to($user->getEmail())
                ->subject('Inscription à la conférence')
                ->text('Bonjour, vous êtes inscrit à la conférence : ' .$event->getTitle() . ". Merci de faire confiance à Pierre Softwares.");

            $emailManager->sendMail($email);
        }

        return $this->redirectToRoute('app_event_show',["id"=>$event->getId()],Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/unregister', name: 'app_event_unregister', methods: ['GET'])]
    public function unregister(Event $event, EntityManagerInterface $entityManager,EmailManager $emailManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if($user !== null){
            $event->removeParticipant($user);
            $entityManager->persist($event);
            $entityManager->flush();


            $email = (new Email())
                ->from('contact.squadron70@gmail.com')
                ->to($user->getEmail())
                ->subject('Desinscription à la conférence')
                ->text('Bonjour, vous n\'êtes plus inscrit à la conférence : ' .$event->getTitle() . ". Merci de faire confiance à Pierre Softwares.");

            $emailManager->sendMail($email);
        }

        return $this->redirectToRoute('app_event_show',["id"=>$event->getId()],Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        if (!$this->authorizationChecker->isGranted('EVENT_EDIT', $event)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event): Response
    {
        if (!$this->authorizationChecker->isGranted('EVENT_DELETE', $event)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
