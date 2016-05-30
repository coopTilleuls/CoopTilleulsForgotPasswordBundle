<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\DBALException;
use ForgotPasswordBundle\Manager\PasswordTokenManager;
use ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Doctrine\ORM\Tools\SchemaTool;

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
        //        $client->setServerParameter('PHP_AUTH_USER', 'admin');
//        $client->setServerParameter('PHP_AUTH_PW', 'admin');

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
        $this->client->request('POST', '/forgot_password/', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], <<<JSON
{
    "username": "john.doe"
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
        \PHPUnit_Framework_Assert::assertEquals('no-reply@parti-de-gauche.fr', key($messages[0]->getFrom()));
        \PHPUnit_Framework_Assert::assertEquals('john.doe@example.com', key($messages[0]->getTo()));
        \PHPUnit_Framework_Assert::assertContains('http://localhost/mot-de-passe-oublie/', $messages[0]->getBody());
    }

    /**
     * @Then I reset my password using invalid email address
     */
    public function iResetMyPasswordUsingInvalidEmailAddress()
    {
        $this->client->request('POST', '/forgot_password/', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], <<<JSON
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
        if (null === ($person = $this->personManager->findUserBy(['email' => 'john.doe@example.com']))) {
            $person = $this->authContext->createPerson();
        }

        $token = $this->passwordTokenManager->createPasswordToken($person);

        $this->client->request('POST', sprintf('/forgot_password/%s', $token->getToken()), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], <<<JSON
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then I can log in
     */
    public function iCanLogIn()
    {
        $this->authContext->iAmAuthenticated();
        $this->iGetMyProfile();
        $this->iShouldSeeMyProfile();
    }

    /**
     * @Then I should see my profile
     */
    public function iShouldSeeMyProfile()
    {
        \PHPUnit_Framework_Assert::assertTrue(
            $this->client->getResponse()->isSuccessful(),
            sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        $expected = <<<JSON
{
    "@context": "/contexts/Person",
    "@id": "/people/1",
    "@type": "http://schema.org/Person",
    "email": "john.doe@example.com",
    "address": null,
    "birthDate": null,
    "description": null,
    "familyName": null,
    "givenName": null,
    "maidenName": null,
    "aliasName": null,
    "genre": "/genres/1",
    "type": "/person_types/1",
    "status": "/person_statuts/1",
    "emailPro": null,
    "twitter": null,
    "facebook": null,
    "website": null,
    "profession": "/profession2s/1",
    "employer": "employeur",
    "unionActivity": "act",
    "observations": "obs"
}
JSON;
        \PHPUnit_Framework_Assert::assertJsonStringEqualsJsonString($expected, $this->client->getResponse()->getContent());
    }

    /**
     * @Then I update my password using an invalid token
     */
    public function iUpdateMyPasswordUsingAnInvalidToken()
    {
        $this->client->request('POST', '/forgot_password/12345', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], <<<JSON
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
        if (null === ($person = $this->personManager->findUserBy(['email' => 'john.doe@example.com']))) {
            $person = $this->authContext->createPerson();
        }

        $token = $this->passwordTokenManager->createPasswordToken($person, new \DateTime('-1 minute'));

        $this->client->request('POST', sprintf('/forgot_password/%s', $token->getToken()), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], <<<JSON
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @When I get my profile
     */
    public function iGetMyProfile()
    {
        $this->client->request('GET', '/profile');
    }

    /**
     * @return User
     */
    private function createUser()
    {
        $user = new User();
        $user->setUsername('foo');
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        return $user;
    }
}
