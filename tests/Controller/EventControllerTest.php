<?php

namespace Controller;

use App\Entity\Event;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/event/';

    private User $user;
    private Event $fixture;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Event::class);
        $this->user = $this->manager->getRepository(User::class)->findAll()[0];
        $this->client->loginUser($this->user);
        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->fixture = new Event();
        $this->fixture->setTitle('My Title');
        $this->fixture->setDescription('My Title');
        $this->fixture->setStartDate(new DateTime('2024-06-05T19:45'));
        $this->fixture->setMaxParticipants(85);
        $this->fixture->setIsPublic('My Title');
        $this->fixture->setCreator($this->user);

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Event index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $TestEvent = new Event();
        $TestEvent->setTitle('Testing');
        $TestEvent->setDescription('Testing');
        $TestEvent->setStartDate(new DateTime('2024-06-05T19:45'));
        $TestEvent->setMaxParticipants(50);
        $TestEvent->setIsPublic(true);
        $TestEvent->setCreator($this->user);

        $this->client->submitForm('Save', [
            'event[title]' => 'Testing',
            'event[description]' => 'Testing',
            'event[startDate]' => '2024-06-05T19:45',
            'event[maxParticipants]' => '50',
            'event[isPublic]' => '1',
            'event[participants]' => [],
            'event[creator]' => $this->user->getId(),
        ]);

        self::assertResponseRedirects($this->path);
        $createdEvent = $this->repository->findAll()[0];
        self::assertSame($TestEvent->getTitle(), $createdEvent->getTitle());
        self::assertSame($TestEvent->getDescription(), $createdEvent->getDescription());
        self::assertSame($TestEvent->getStartDate()->format('Y/M/D h:i'), $createdEvent->getStartDate()->format('Y/M/D h:i'));
        self::assertSame($TestEvent->getMaxParticipants(), $createdEvent->getMaxParticipants());
        self::assertSame($TestEvent->isPublic(), $createdEvent->isPublic());
        self::assertSame($TestEvent->getCreator()->getId(), $createdEvent->getCreator()->getId());
    }

    public function testShow(): void
    {
        $this->manager->persist($this->fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $this->fixture->getId()));

        self::assertResponseStatusCodeSame(200);

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {

        $this->manager->persist($this->fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $this->fixture->getId()));

        $this->client->submitForm('Update', [
            'event[title]' => 'Mont Blanc',
            'event[description]' => 'Testing',
            'event[startDate]' => '2024-06-05T20:45',
            'event[maxParticipants]' => '50',
            'event[isPublic]' => '1',
            'event[participants]' => [],
            'event[creator]' => $this->user->getId(),
        ]);

        self::assertResponseRedirects('/event/');

        $fixture = $this->repository->findAll();

        self::assertSame('Mont Blanc', $fixture[0]->getTitle());
        self::assertSame('Testing', $fixture[0]->getDescription());
        self::assertSame((new DateTime('2024-06-05T20:45'))->format('Y/M/D h:i'), $fixture[0]->getStartDate()->format('Y/M/D h:i'));
        self::assertSame(50, $fixture[0]->getMaxParticipants());
        self::assertSame(true, $fixture[0]->isPublic());
        self::assertSame(0, $fixture[0]->getParticipants()->count());
        self::assertSame($this->user->getId(), $fixture[0]->getCreator()->getId());
    }

    public function testRemove(): void
    {

        $this->manager->persist($this->fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $this->fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/event/');
        self::assertSame(0, $this->repository->count([]));
    }
}
