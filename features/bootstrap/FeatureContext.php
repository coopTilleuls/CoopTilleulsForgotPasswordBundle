<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Behat\Behat\Context\Context;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class FeatureContext implements Context
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Client|KernelBrowser
     */
    private $client;

    /**
     * @var PasswordTokenManager
     */
    private $passwordTokenManager;

    public function __construct($client, Registry $doctrine, PasswordTokenManager $passwordTokenManager)
    {
        $this->client = $client;
        $this->doctrine = $doctrine;
        $this->passwordTokenManager = $passwordTokenManager;
    }

    /**
     * @BeforeScenario
     */
    public function resetDatabase(): void
    {
        $purger = new ORMPurger($this->doctrine->getManager());
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        try {
            $purger->purge();
        } catch (DBALException $e) {
            $schemaTool = new SchemaTool($this->doctrine->getManager());
            $schemaTool->createSchema($this->doctrine->getManager()->getMetadataFactory()->getAllMetadata());
        }
    }

    /**
     * @Given I have a valid token
     */
    public function iHaveAValidToken(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser());
    }

    /**
     * @Given I have an expired token
     */
    public function iHaveAnExpiredToken(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser(), new \DateTime('-1 minute'));
    }

    /**
     * @Then I reset my password
     * @Then I reset my password with my :propertyName ":value"
     *
     * @param string $propertyName
     * @param string $value
     */
    public function IResetMyPassword($propertyName = 'email', $value = 'john.doe@example.com'): void
    {
        $this->createUser();

        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/forgot_password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            sprintf(<<<'JSON'
{
    "%s": "%s"
}
JSON
            , $propertyName, $value)
        );
    }

    /**
     * @Then I should receive an email
     */
    public function iShouldReceiveAnEmail(): void
    {
        Assert::assertTrue(
            $this->client->getResponse()->isSuccessful(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        Assert::assertEmpty($this->client->getResponse()->getContent());

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        $messages = $mailCollector->getEvents()->getMessages();
        Assert::assertCount(1, $messages, 'No email has been sent');

        /** @var \Symfony\Component\Mime\Email $message */
        $message = $messages[0];
        Assert::assertInstanceOf(RawMessage::class, $message);
        Assert::assertEquals('Réinitialisation de votre mot de passe', $message->getSubject());
        Assert::assertEquals('no-reply@example.com', $message->getFrom()[0]->getAddress());
        Assert::assertEquals('john.doe@example.com', $message->getTo()[0]->getAddress());
        Assert::assertMatchesRegularExpression('/http:\/\/www\.example\.com\/forgot_password\/(.*)/', $message->getHtmlBody());
    }

    /**
     * @When the page should not be found
     */
    public function thePageShouldNotBeFound(): void
    {
        Assert::assertTrue(
            $this->client->getResponse()->isNotFound(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    /**
     * @Then the response should be empty
     */
    public function theResponseShouldBeEmpty(): void
    {
        Assert::assertTrue(
            $this->client->getResponse()->isEmpty(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    /**
     * @When the request should be invalid with message :message
     *
     * @param string $message
     */
    public function theRequestShouldBeInvalidWithMessage($message): void
    {
        Assert::assertEquals(
            400,
            $this->client->getResponse()->getStatusCode(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        Assert::assertJson($this->client->getResponse()->getContent());
        Assert::assertJsonStringEqualsJsonString(sprintf(<<<'JSON'
{
    "message": "%s"
}
JSON
                , str_ireplace('"', '\"', $message)
            ), $this->client->getResponse()->getContent()
        );
    }

    /**
     * @Then I reset my password using invalid email address
     */
    public function iResetMyPasswordUsingInvalidEmailAddress(): void
    {
        $this->client->request(
            'POST',
            '/forgot_password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<'JSON'
{
    "email": "foo@example.com"
}
JSON
        );
    }

    /**
     * @Then I reset my password using no parameter
     */
    public function iResetMyPasswordUsingNoParameter(): void
    {
        $this->client->request('POST', '/forgot_password/');
    }

    /**
     * @Then I update my password
     */
    public function iUpdateMyPassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser());

        $this->client->request(
            'POST',
            sprintf('/forgot_password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<'JSON'
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I update my password using no password
     */
    public function iUpdateMyPasswordUsingNoPassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser());

        $this->client->request('POST', sprintf('/forgot_password/%s', $token->getToken()));
    }

    /**
     * @Then I update my password using an invalid token
     */
    public function iUpdateMyPasswordUsingAnInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/forgot_password/12345',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<'JSON'
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I update my password using an expired token
     */
    public function iUpdateMyPasswordUsingAnExpiredToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new \DateTime('-1 minute'));

        $this->client->request(
            'POST',
            sprintf('/forgot_password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<'JSON'
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I get a password token
     */
    public function iGetAPasswordToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser());
        $token->setToken('d7xtQlJVyN61TzWtrY6xy37zOxB66BqMSDXEbXBbo2Mw4Jjt9C');
        $this->doctrine->getManager()->persist($token);
        $this->doctrine->getManager()->flush();

        $this->client->request('GET', sprintf('/forgot_password/%s', $token->getToken()));
    }

    /**
     * @Then I should get a password token
     */
    public function iShouldGetAPasswordToken(): void
    {
        Assert::assertTrue(
            $this->client->getResponse()->isSuccessful(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        Assert::assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @Then I get a password token using an expired token
     */
    public function iGetAPasswordTokenUsingAnExpiredToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new \DateTime('-1 minute'));

        $this->client->request('GET', sprintf('/forgot_password/%s', $token->getToken()));
    }

    /**
     * @return User
     */
    private function createUser()
    {
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setUsername('JohnDoe');
        $user->setPassword('password');
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        return $user;
    }
}
