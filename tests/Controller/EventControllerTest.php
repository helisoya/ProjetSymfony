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

    private DateTime $dateTime;

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
        $this->dateTime = new DateTime('+8day');

        $this->fixture = new Event();
        $this->fixture->setTitle('My Title');
        $this->fixture->setDescription('My Title');
        $this->fixture->setStartDate($this->dateTime);
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
        $TestEvent->setStartDate($this->dateTime);
        $TestEvent->setMaxParticipants(50);
        $TestEvent->setIsPublic(true);
        $TestEvent->setCreator($this->user);

        $this->client->submitForm('Save', [
            'event[title]' => 'Testing',
            'event[description]' => 'Testing',
            'event[startDate]' => $this->dateTime->format('Y-m-d H:i'),
            'event[maxParticipants]' => '50',
            'event[isPublic]' => '1'
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

        $this->client->request('GET', sprintf('%s%s/show', $this->path, $this->fixture->getId()));

        self::assertResponseStatusCodeSame(200);

        // Use assertions to check that the properties are properly displayed.
        self::assertSelectorTextSame('h2.text-center.mb-4', $this->fixture->getTitle());
        self::assertSelectorTextSame('div.custom-form.ticket-form.mb-5.mb-lg-0 p', $this->fixture->getDescription());
        self::assertSelectorTextSame('.pricing-list-item:nth-child(1)', 'The ' .
            $this->fixture->getStartDate()->format('Y/m/d') .
            ' at ' .
            $this->fixture->getStartDate()->format('H:i:s')
        );
        self::assertSelectorTextSame('.pricing-list-item:nth-child(2)', $this->fixture->getParticipants()->count() . '/' . $this->fixture->getMaxParticipants() . ' participants');
        self::assertSelectorTextSame('.pricing-list-item:nth-child(3)', $this->fixture->isPublic() ? 'Public' : 'Private');
        self::assertSelectorTextSame('.pricing-list-item:nth-child(4)', 'By ' . $this->fixture->getCreator()->getNom() . ' ' . $this->fixture->getCreator()->getPrenom());

    }

    public function testEdit(): void
    {

        $this->manager->persist($this->fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $this->fixture->getId()));

        $this->client->submitForm('Update', [
            'event[title]' => 'Mont Blanc',
            'event[description]' => 'Testing',
            'event[startDate]' => $this->dateTime->format('Y-m-d H:i'),
            'event[maxParticipants]' => '50',
            'event[isPublic]' => '1',
        ]);

        self::assertResponseRedirects('/event/');

        $fixture = $this->repository->findAll();

        self::assertSame('Mont Blanc', $fixture[0]->getTitle());
        self::assertSame('Testing', $fixture[0]->getDescription());
        self::assertSame($this->dateTime->format('Y/M/D h:i'), $fixture[0]->getStartDate()->format('Y/M/D h:i'));
        self::assertSame(50, $fixture[0]->getMaxParticipants());
        self::assertSame(true, $fixture[0]->isPublic());
        self::assertSame(0, $fixture[0]->getParticipants()->count());
        self::assertSame($this->user->getId(), $fixture[0]->getCreator()->getId());
    }

    public function testRemove(): void
    {

        $this->manager->persist($this->fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/show', $this->path, $this->fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/event/');
        self::assertSame(0, $this->repository->count([]));
    }
}
