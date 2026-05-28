<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Functional;

use App\Entity\Admin;
use App\Entity\User;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
class ForgotPasswordTest extends WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['debug'] ??= false;

        return parent::createKernel($options);
    }

    protected KernelBrowser $client;
    protected Registry $doctrine;
    protected PasswordTokenManager $passwordTokenManager;
    protected ProviderChainInterface $providerChain;

    protected function tearDown(): void
    {
        parent::tearDown();
        // Symfony's kernel may register exception handlers during boot; restore them to avoid PHPUnit risky test warnings.
        restore_exception_handler();
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->doctrine = $container->get('doctrine');
        $this->passwordTokenManager = $container->get('coop_tilleuls_forgot_password.manager.password_token');
        $this->providerChain = $container->get('coop_tilleuls_forgot_password.provider_chain');

        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        $purger = new ORMPurger($this->doctrine->getManager());
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        try {
            $purger->purge();
        } catch (\Exception) {
            $schemaTool = new SchemaTool($this->doctrine->getManager());
            $schemaTool->createSchema($this->doctrine->getManager()->getMetadataFactory()->getAllMetadata());
        }
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setUsername('JohnDoe');
        $user->setPassword('password');
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        return $user;
    }

    private function createAdmin(): Admin
    {
        $admin = new Admin();
        $admin->setEmail('admin@example.com');
        $admin->setUsername('admin@example.com');
        $admin->setPassword('password');
        $this->doctrine->getManager()->persist($admin);
        $this->doctrine->getManager()->flush();

        return $admin;
    }

    private function assertEmailSent(string $toAddress): void
    {
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        $this->assertEmpty($this->client->getResponse()->getContent());

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages, 'No email has been sent');

        /** @var \Symfony\Component\Mime\Email $message */
        $message = $messages[0];
        $this->assertInstanceOf(RawMessage::class, $message);
        $this->assertEquals('Réinitialisation de votre mot de passe', $message->getSubject());
        $this->assertEquals('no-reply@example.com', $message->getFrom()[0]->getAddress());
        $this->assertEquals($toAddress, $message->getTo()[0]->getAddress());
        $this->assertMatchesRegularExpression('/http:\/\/www\.example\.com\/api\/forgot-password\/(.*)/', $message->getHtmlBody());
    }

    private function assertResponseInvalidWithMessage(string $message): void
    {
        $this->assertEquals(
            422,
            $this->client->getResponse()->getStatusCode(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString(
            \sprintf('{"message": "%s"}', str_ireplace('"', '\"', $message)),
            $this->client->getResponse()->getContent()
        );
    }

    public function testResetPasswordWithEmail(): void
    {
        $this->createUser();
        $this->createAdmin();
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"email": "john.doe@example.com"}'
        );

        $this->assertEmailSent('john.doe@example.com');
    }

    public function testResetPasswordWithUsername(): void
    {
        $this->createUser();
        $this->createAdmin();
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'admin'],
            '{"username": "admin@example.com"}'
        );

        $this->assertEmailSent('admin@example.com');
    }

    public function testResetPasswordWithInvalidProvider(): void
    {
        $this->createAdmin();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'wrong'],
            '{"email": "admin@example.com"}'
        );

        $this->assertResponseInvalidWithMessage('The provider "wrong" is not defined.');
    }

    public function testResetPasswordWithUnauthorizedField(): void
    {
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"id": "1"}'
        );

        $this->assertResponseInvalidWithMessage('The parameter "id" is not authorized in your configuration.');
    }

    public function testResetPasswordWithValidExistingTokenWithEmail(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('+1 day'));
        $this->createAdmin();
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"email": "john.doe@example.com"}'
        );

        $this->assertEmailSent('john.doe@example.com');
    }

    public function testResetPasswordWithValidExistingTokenWithUsername(): void
    {
        $this->createUser();
        $this->passwordTokenManager->createPasswordToken($this->createAdmin(), $this->providerChain->get('admin'), new \DateTime('+1 day'));
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'admin'],
            '{"username": "admin@example.com"}'
        );

        $this->assertEmailSent('admin@example.com');
    }

    public function testResetPasswordWithExpiredTokenWithEmail(): void
    {
        $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('-1 minute'));
        $this->createAdmin();
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"email": "john.doe@example.com"}'
        );

        $this->assertEmailSent('john.doe@example.com');
    }

    public function testResetPasswordWithExpiredTokenWithUsername(): void
    {
        $this->createUser();
        $this->passwordTokenManager->createPasswordToken($this->createAdmin(), $this->providerChain->get('admin'), new \DateTime('-1 minute'));
        $this->client->enableProfiler();
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'admin'],
            '{"username": "admin@example.com"}'
        );

        $this->assertEmailSent('admin@example.com');
    }

    public function testResetPasswordWithInvalidEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/forgot-password/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"email": "foo@example.com"}'
        );

        $this->assertTrue(
            $this->client->getResponse()->isEmpty(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    public function testResetPasswordWithNoParameter(): void
    {
        $this->client->request('POST', '/api/forgot-password/');

        $this->assertResponseInvalidWithMessage('No parameter sent.');
    }

    public function testUpdatePasswordWithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/forgot-password/12345',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"password": "foo"}'
        );

        $this->assertTrue(
            $this->client->getResponse()->isNotFound(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    public function testUpdatePasswordWithExpiredToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('-1 minute'));

        $this->client->request(
            'POST',
            \sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"password": "foo"}'
        );

        $this->assertTrue(
            $this->client->getResponse()->isNotFound(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    public function testUpdatePasswordWithNoPassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('+1 day'));

        $this->client->request('POST', \sprintf('/api/forgot-password/%s', $token->getToken()));

        $this->assertResponseInvalidWithMessage('No parameter sent.');
    }

    public function testUpdatePassword(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('+1 day'));

        $this->client->request(
            'POST',
            \sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"ignoreMe": "bar", "password": "foo"}'
        );

        $this->assertTrue(
            $this->client->getResponse()->isEmpty(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );

        $user = $this->doctrine->getManager()->getRepository(User::class)->findOneBy(['username' => 'JohnDoe']);
        $this->assertNotNull($user, 'Unable to retrieve User object.');
        $this->assertEquals('foo', $user->getPassword(), \sprintf('User password hasn\'t been updated, expected "foo", got "%s".', $user->getPassword()));
    }

    public function testGetPasswordToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('+1 day'));
        $token->setToken('d7xtQlJVyN61TzWtrY6xy37zOxB66BqMSDXEbXBbo2Mw4Jjt9C');
        $this->doctrine->getManager()->persist($token);
        $this->doctrine->getManager()->flush();

        $this->client->request('GET', \sprintf('/api/forgot-password/%s', $token->getToken()));

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetExpiredPasswordToken(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createUser(), $this->providerChain->get('user'), new \DateTime('-1 minute'));

        $this->client->request('GET', \sprintf('/api/forgot-password/%s', $token->getToken()));

        $this->assertTrue(
            $this->client->getResponse()->isNotFound(),
            \sprintf('Response is not valid: got %d', $this->client->getResponse()->getStatusCode())
        );
    }

    public function testOpenApiDocumentation(): void
    {
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(static::$kernel);
        $application->setAutoExit(false);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $exitCode = $application->doRun(new \Symfony\Component\Console\Input\ArgvInput(['test', 'api:openapi:export']), $output);

        $this->assertSame(0, $exitCode, \sprintf('Unable to run "api:openapi:export" command: got %d exit code.', $exitCode));

        $json = $output->fetch();
        $this->assertJson($json);
        $openApi = json_decode($json, true);
        $this->assertEquals($this->getExpectedOpenApiPaths(), $openApi['paths']);
        $this->assertEquals([
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

    public function testUpdatePasswordWithWrongProvider(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createAdmin(), $this->providerChain->get('admin'), new \DateTime('+1 day'));

        $this->client->request(
            'POST',
            \sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'wrong'],
            '{"adminPassword": "foo"}'
        );

        $this->assertResponseInvalidWithMessage('The provider "wrong" is not defined.');
    }

    public function testUpdatePasswordWithInvalidPasswordField(): void
    {
        $token = $this->passwordTokenManager->createPasswordToken($this->createAdmin(), $this->providerChain->get('admin'), new \DateTime('+1 day'));

        $this->client->request(
            'POST',
            \sprintf('/api/forgot-password/%s', $token->getToken()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_FP-provider' => 'admin'],
            '{"password": "foo"}'
        );

        $this->assertResponseInvalidWithMessage('Parameter "adminPassword" is missing.');
    }

    private function getExpectedOpenApiPaths(): array
    {
        return [
            '/api/forgot-password/' => [
                'ref' => 'ForgotPassword',
                'post' => [
                    'operationId' => 'postForgotPassword',
                    'tags' => ['Forgot password'],
                    'responses' => [
                        204 => [
                            'description' => 'Valid email address, no matter if user exists or not',
                        ],
                        422 => [
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
                        422 => [
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
    }
}
