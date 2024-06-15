<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Event;
use App\Form\EventType;
use App\Service\EmailManager;
use Exception;
use Symfony\Component\Mime\Email;
use App\Service\EventPlaceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/event')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface        $entityManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly EventPlaceManager             $eventPlaceManager,
        private readonly EmailManager                  $emailManager
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = [
            'title' => !empty($request->get('title')) ? $request->get('title') : null,
            'description' => !empty($request->get('description')) ? $request->get('description') : null,
            'minStartDate' => !empty($request->get('minStartDate')) ? $request->get('minStartDate') : null,
            'maxStartDate' => !empty($request->get('maxStartDate')) ? $request->get('maxStartDate') : null,
            'creator' => !empty($request->get('creator')) ? $request->get('creator') : null,
            'maxParticipants' => !empty($request->get('maxParticipants')) ? $request->get('maxParticipants') : null,
            'isPublic' => !empty($request->get('isPublic')) ? $request->get('isPublic') : null,
        ];

        $events = $this->entityManager->getRepository(Event::class)->createQueryBuilder('e');

        if ($filters['title']) {
            $title = $filters['title'];
            $events->where('e.title like :searchTitle')
                ->setParameter('searchTitle', "%$title%");
        }

        if ($filters['description']) {
            $description = $filters['description'];
            $events->andWhere('e.description like :searchDescription')
                ->setParameter('searchDescription', "%$description%");
        }

        if ($filters['minStartDate'] && $filters['maxStartDate']) {

            $minDate = $this->createDate($filters['minStartDate']);
            $maxDate = $this->createDate($filters['maxStartDate']);

            if ($minDate && $maxDate) {
                $events->andWhere('e.startDate between :from and :to')
                    ->setParameter('from', $minDate->format('Y-m-d') . ' 00:00:00')
                    ->setParameter('to', $maxDate->format('Y-m-d') . ' 23:59:59');
            }
        }

        if ($filters['creator']) {
            $events->andWhere('e.creator = :creator')
                ->setParameter('creator', $filters['creator']);
        }

        if ($filters['maxParticipants']) {
            $events->andWhere('e.maxParticipants = :maxParticipants')
                ->setParameter('maxParticipants', $filters['maxParticipants']);
        }

        if ($filters['isPublic']) {
            $isPublic = $filters['isPublic'] === 'Yes';
            $events->andWhere('e.isPublic = :isPublic')
                ->setParameter('isPublic', $isPublic);
        }

        $events = $events->getQuery()->getResult();
        $creators = [];

        foreach ($this->entityManager->getRepository(Event::class)->findAll() as $event) {
            if (!in_array($event->getCreator(), $creators)) $creators[] = $event->getCreator();
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'filters' => $filters,
            'creators' => $creators
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
            $event->setCreator($this->getUser());
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

        if ($user !== null) {

            foreach ($event->getParticipants() as $participant) {
                if ($participant === $user) {
                    $userInscrit = true;
                    break;
                }
            }
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'userInscrit' => $userInscrit,
            'remainingSeats' => $this->eventPlaceManager->computeRemainingSeats($event)
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/{id}/register', name: 'app_event_register', methods: ['GET'])]
    public function register(Event $event): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user !== null && $this->eventPlaceManager->computeRemainingSeats($event) > 0) {
            $event->addParticipant($user);
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $email = (new Email())
                ->from('contact.squadron70@gmail.com')
                ->to($user->getEmail())
                ->subject('Inscription à la conférence')
                ->text('Bonjour, vous êtes inscrit à la conférence : ' . $event->getTitle() . ". Merci de faire confiance à Pierre Softwares.");

            $this->emailManager->sendMail($email);
        }

        return $this->redirectToRoute('app_event_show', ["id" => $event->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/{id}/unregister', name: 'app_event_unregister', methods: ['GET'])]
    public function unregister(Event $event): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user !== null) {
            $event->removeParticipant($user);
            $this->entityManager->persist($event);
            $this->entityManager->flush();


            $email = (new Email())
                ->from('contact.squadron70@gmail.com')
                ->to($user->getEmail())
                ->subject('Desinscription à la conférence')
                ->text('Bonjour, vous n\'êtes plus inscrit à la conférence : ' . $event->getTitle() . ". Merci de faire confiance à Pierre Softwares.");

            $this->emailManager->sendMail($email);
        }

        return $this->redirectToRoute('app_event_show', ["id" => $event->getId()], Response::HTTP_SEE_OTHER);
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

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->getPayload()->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }

    public function isValidDate($date, $format = 'Y-m-d'): bool
    {
        if (is_null($date)) {
            return false;
        }

        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    /**
     * @throws Exception
     */
    public function createDate(string $dateString): ?DateTime
    {
        $isValid = $this->isValidDate($dateString);

        if ($isValid){
            return new DateTime($dateString);
        }
        return null;
    }
}
