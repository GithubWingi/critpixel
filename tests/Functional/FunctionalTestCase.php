<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Model\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function purgeDatabase(): void
    {
        $em = $this->getEntityManager();
        $purger = new ORMPurger($em);
        $purger->purge();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    protected function service(string $id): object
    {
        return $this->client->getContainer()->get($id);
    }

    protected function get(string $uri, array $parameters = []): Crawler
    {
        return $this->client->request('GET', $uri, $parameters);
    }

    protected function post(string $uri, array $parameters = []): Crawler
    {
        return $this->client->request('POST', $uri, $parameters);
    }


    protected function login(string $email = 'user+0@email.com'): void
    {
        $user = $this->service(EntityManagerInterface::class)->getRepository(User::class)->findOneByEmail($email);

        $this->client->loginUser($user);
    }

    protected function getUser(): ?User
    {
        $user = $this->client->getContainer()->get(Security::class)->getUser();

        return $user instanceof User ? $user : null;
    }

    protected function submit(string $button, array $formData = [], string $method = 'POST'): Crawler
    {
        return $this->client->submitForm($button, $formData, $method);
    }
}
