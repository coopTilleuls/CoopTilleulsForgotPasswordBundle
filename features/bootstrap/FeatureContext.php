<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use App\Entity\Admin;
use App\Entity\User;
use Behat\Behat\Context\Context;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class FeatureContext implements Context
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(private readonly Client|KernelBrowser $client, private readonly Registry $doctrine, private readonly PasswordTokenManager $passwordTokenManager, private readonly ProviderChainInterface $providerChain, KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
        $this->output = new BufferedOutput();
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
        } catch (Exception) {
            $schemaTool = new SchemaTool($this->doctrine->getManager());
            $schemaTool->createSchema($this->doctrine->getManager()->getMetadataFactory()->getAllMetadata());
        }
    }

    /**
     * @Given I have a valid token
     */
    public function iHaveAValidToken(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('+1 day'));
    }

    /**
     * @Given I have an expired token
     */
    public function iHaveAnExpiredToken(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('-1 minute'));
    }

    /**
     * @When I reset my password
     * @When I reset my password with my :propertyName ":value" on provider ":provider"
     * @When I reset my password with my :propertyName ":value"
     *
     * @param mixed|null $provider
     */
    public function IResetMyPassword(string $propertyName = 'email', string $value = 'john.doe@example.com', $provider = null): void
    {
        $this->createUser();
        $this->createAdmin();
        $headers = $provider ? ['HTTP_FP-provider' => $provider] : [];

        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $headers),
            sprintf(<<<'JSON'
{
  "%s": "%s"
}
JSON,
                $propertyName, $value)
        );
    }

    /**
     * @Then I should receive an email at ":value"
     */
    public function iShouldReceiveAnEmail($value = 'john.doe@example.com'): void
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

        /** @var Symfony\Component\Mime\Email $message */
        $message = $messages[0];
        Assert::assertInstanceOf(RawMessage::class, $message);
        Assert::assertEquals('Réinitialisation de votre mot de passe', $message->getSubject());
        Assert::assertEquals('no-reply@example.com', $message->getFrom()[0]->getAddress());
        Assert::assertEquals($value, $message->getTo()[0]->getAddress());
        Assert::assertMatchesRegularExpression('/http:\/\/www\.example\.com\/api\/forgot-password\/(.*)/', $message->getHtmlBody());
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
     * @When I reset my password using invalid email address
     */
    public function iResetMyPasswordUsingInvalidEmailAddress(): void
    {
        $this->client->request(
            'POST',
            '/api/forgot-password/',
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
     * @When I reset my password using no parameter
     */
    public function iResetMyPasswordUsingNoParameter(): void
    {
        $this->client->request('POST', '/api/forgot-password/');
    }

    /**
     * @When I update my password
     */
    public function iUpdateMyPassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('+1 day'));

        $this->client->request(
            'POST',
            sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            <<<'JSON'
{
    "ignoreMe": "bar",
    "password": "foo"
}
JSON
        );
    }

    /**
     * @Then the password should have been updated
     */
    public function thePasswordShouldHaveBeenUpdated(): void
    {
        $user = $this->doctrine->getManager()->getRepository(User::class)->findOneBy(['username' => 'JohnDoe']);

        Assert::assertNotNull($user, 'Unable to retrieve User object.');
        Assert::assertEquals('foo', $user->getPassword(), sprintf('User password hasn\'t be updated, expected "foo", got "%s".', $user->getPassword()));
    }

    /**
     * @When I update my password using no password
     */
    public function iUpdateMyPasswordUsingNoPassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('+1 day'));

        $this->client->request('POST', sprintf('/api/forgot-password/%s', $token->getToken()));
    }

    /**
     * @When I update my password using an invalid token
     */
    public function iUpdateMyPasswordUsingAnInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/forgot-password/12345',
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
     * @When I update my password using wrong provider
     */
    public function iUpdateMyPasswordUsingWrongProvider(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createAdmin(), new DateTime('+1 day'), $this->providerChain->get('admin'));

        $this->client->request(
            'POST',
            sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'wrong'],
            <<<'JSON'
{
    "adminPassword": "foo"
}
JSON
        );
    }

    /**
     * @When I update my password using a valid provider but an invalid password field
     */
    public function iUpdateMyPasswordUsingAValidProviderButAnInvalidPasswordField(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createAdmin(), new DateTime('+1 day'), $this->providerChain->get('admin'));

        $this->client->request(
            'POST',
            sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'admin'],
            <<<'JSON'
{
    "password": "foo"
}
JSON
        );
    }

    /**
     * @When I update my password using an expired token
     */
    public function iUpdateMyPasswordUsingAnExpiredToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('-1 minute'));

        $this->client->request(
            'POST',
            sprintf('/api/forgot-password/%s', $token->getToken()),
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
     * @When I get a password token
     */
    public function iGetAPasswordToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('+1 day'));
        $token->setToken('d7xtQlJVyN61TzWtrY6xy37zOxB66BqMSDXEbXBbo2Mw4Jjt9C');
        $this->doctrine->getManager()->persist($token);
        $this->doctrine->getManager()->flush();

        $this->client->request('GET', sprintf('/api/forgot-password/%s', $token->getToken()));
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
     * @When I get a password token using an expired token
     */
    public function iGetAPasswordTokenUsingAnExpiredToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), new DateTime('-1 minute'));

        $this->client->request('GET', sprintf('/api/forgot-password/%s', $token->getToken()));
    }

    /**
     * @When I get the OpenApi documentation
     */
    public function iGetOpenApiDocumentation(): void
    {
        $exitCode = $this->application->doRun(new ArgvInput(['behat-test', 'api:openapi:export']), $this->output);
        Assert::assertEquals(0, $exitCode, sprintf('Unable to run "api:openapi:export" command: got %s exit code.', $exitCode));
    }

    /**
     * @Then I should get an OpenApi documentation updated
     */
    public function iShouldGetAnOpenApiDocumentationUpdated(): void
    {
        $output = $this->output->fetch();
        Assert::assertJson($output);
        $openApi = json_decode((string) $output, true);
        Assert::assertEquals($this->getOpenApiPaths(), $openApi['paths']);
        Assert::assertEquals([
            'schemas' => [
                'ForgotPassword:reset' => [
                    'oneOf' => [
                        [
                            'type' => 'object',
                            'required' => ['password'],
                            'properties' => [
                                'password' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        [
                            'type' => 'object',
                            'required' => ['adminPassword'],
                            'properties' => [
                                'adminPassword' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'ForgotPassword:validate' => [
                    'type' => ['object', 'null'],
                ],
                'ForgotPassword:request' => [
                    'oneOf' => [
                        [
                            'type' => 'object',
                            'required' => ['email'],
                            'properties' => [
                                'email' => [
                                    'type' => ['string', 'integer'],
                                ],
                            ],
                        ],
                        [
                            'type' => 'object',
                            'required' => ['username'],
                            'properties' => [
                                'username' => [
                                    'type' => ['string', 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [],
            'parameters' => [],
            'examples' => [],
            'requestBodies' => [],
            'headers' => [],
            'securitySchemes' => [],
        ], $openApi['components']);
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

    private function getOpenApiPaths(): array
    {
        $paths = [
            '/api/forgot-password/' => [
                'ref' => 'ForgotPassword',
                'post' => [
                    'operationId' => 'postForgotPassword',
                    'tags' => ['Forgot password'],
                    'responses' => [
                        204 => [
                            'description' => 'Valid email address, no matter if user exists or not',
                        ],
                        400 => [
                            'description' => 'Missing email parameter or invalid format',
                        ],
                    ],
                    'summary' => 'Generates a token and send email',
                    'parameters' => [
                        [
                            'name' => 'FP-provider',
                            'in' => 'header',
                            'required' => false,
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'requestBody' => [
                        'description' => 'Request a new password',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ForgotPassword:request',
                                ],
                            ],
                        ],
                        'required' => true,
                    ],
                ],
            ],
            '/api/forgot-password/{tokenValue}' => [
                'ref' => 'ForgotPassword',
                'get' => [
                    'operationId' => 'getForgotPassword',
                    'tags' => ['Forgot password'],
                    'responses' => [
                        200 => [
                            'description' => 'Authenticated user',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ForgotPassword:validate',
                                    ],
                                ],
                            ],
                        ],
                        404 => [
                            'description' => 'Token not found or expired',
                        ],
                    ],
                    'summary' => 'Validates token',
                    'parameters' => [
                        [
                            'name' => 'tokenValue',
                            'in' => 'path',
                            'required' => true,
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                        [
                            'name' => 'FP-provider',
                            'in' => 'header',
                            'required' => false,
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'operationId' => 'postForgotPasswordToken',
                    'tags' => ['Forgot password'],
                    'responses' => [
                        204 => [
                            'description' => 'Email address format valid, no matter if user exists or not',
                        ],
                        400 => [
                            'description' => 'Missing password parameter',
                        ],
                        404 => [
                            'description' => 'Token not found',
                        ],
                    ],
                    'summary' => 'Validates token',
                    'parameters' => [
                        [
                            'name' => 'tokenValue',
                            'in' => 'path',
                            'required' => true,
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                        [
                            'name' => 'FP-provider',
                            'in' => 'header',
                            'required' => false,
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'requestBody' => [
                        'description' => 'Reset password',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ForgotPassword:reset',
                                ],
                            ],
                        ],
                        'required' => true,
                    ],
                ],
            ],
        ];

        // BC api-platform/core:^2.7
        if (class_exists(ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle::class)) {
            $paths['/api/forgot-password/']['post']['description'] = '';
            $paths['/api/forgot-password/']['post']['deprecated'] = false;
            $paths['/api/forgot-password/{tokenValue}']['get']['description'] = '';
            $paths['/api/forgot-password/{tokenValue}']['get']['deprecated'] = false;
            $paths['/api/forgot-password/{tokenValue}']['post']['description'] = '';
            $paths['/api/forgot-password/{tokenValue}']['post']['deprecated'] = false;
        }

        return $paths;
    }

    /**
     * @return Admin
     */
    private function createAdmin()
    {
        $admin = new Admin();
        $admin->setEmail('admin@example.com');
        $admin->setUsername('admin@example.com');
        $admin->setPassword('password');
        $this->doctrine->getManager()->persist($admin);
        $this->doctrine->getManager()->flush();

        return $admin;
    }
}
