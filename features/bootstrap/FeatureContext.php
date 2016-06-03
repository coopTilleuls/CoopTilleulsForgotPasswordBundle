<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var PasswordTokenManager
     */
    private $passwordTokenManager;

    /**
     * @param Client               $client
     * @param Registry             $doctrine
     * @param PasswordTokenManager $passwordTokenManager
     */
    public function __construct(Client $client, Registry $doctrine, PasswordTokenManager $passwordTokenManager)
    {
        $this->client = $client;
        $this->doctrine = $doctrine;
        $this->passwordTokenManager = $passwordTokenManager;
    }

    /**
     * @BeforeScenario
     */
    public function resetDatabase()
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
     * @Then I reset my password
     */
    public function iResetMyPassword()
    {
        $this->createUser();

        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/forgot_password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<JSON
{
    "email": "john.doe@example.com"
}
JSON
        );
    }

    /**
     * @Then I should receive an email
     */
    public function iShouldReceiveAnEmail()
    {
        \PHPUnit_Framework_Assert::assertTrue(
            $this->client->getResponse()->isSuccessful(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        \PHPUnit_Framework_Assert::assertEmpty($this->client->getResponse()->getContent());

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        \PHPUnit_Framework_Assert::assertEquals(1, $mailCollector->getMessageCount(), 'No email has been sent');

        /** @var \Swift_Mime_Message[] $messages */
        $messages = $mailCollector->getMessages();
        \PHPUnit_Framework_Assert::assertInstanceOf('Swift_Message', $messages[0]);
        \PHPUnit_Framework_Assert::assertEquals('Réinitialisation de votre mot de passe', $messages[0]->getSubject());
        \PHPUnit_Framework_Assert::assertEquals('no-reply@example.com', key($messages[0]->getFrom()));
        \PHPUnit_Framework_Assert::assertEquals('john.doe@example.com', key($messages[0]->getTo()));
        \PHPUnit_Framework_Assert::assertRegExp('/http:\/\/www\.example\.com\/forgot_password\/(.*)/', $messages[0]->getBody());
    }

    /**
     * @When the page should not be found
     */
    public function thePageShouldNotBeFound()
    {
        \PHPUnit_Framework_Assert::assertTrue(
            $this->client->getResponse()->isNotFound(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    /**
     * @When the request should be invalid
     */
    public function theRequestShouldBeInvalid()
    {
        \PHPUnit_Framework_Assert::assertEquals(
            400,
            $this->client->getResponse()->getStatusCode(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    /**
     * @Then I reset my password using invalid email address
     */
    public function iResetMyPasswordUsingInvalidEmailAddress()
    {
        $this->client->request(
            'POST',
            '/forgot_password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<JSON
{
    "email": "foo@example.com"
}
JSON
        );
    }

    /**
     * @Then I update my password
     */
    public function iUpdateMyPassword()
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser());

        $this->client->request(
            'POST',
            sprintf('/forgot_password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<JSON
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I update my password using an invalid token
     */
    public function iUpdateMyPasswordUsingAnInvalidToken()
    {
        $this->client->request(
            'POST',
            '/forgot_password/12345',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<JSON
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I update my password using an expired token
     */
    public function iUpdateMyPasswordUsingAnExpiredToken()
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new \DateTime('-1 minute'));

        $this->client->request(
            'POST',
            sprintf('/forgot_password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<JSON
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @return User
     */
    private function createUser()
    {
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setPassword('password');
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        return $user;
    }
}
