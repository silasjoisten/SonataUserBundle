<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Security\Authorization\Voter;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Sonata\UserBundle\Security\RolesMatrixBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Silas Joisten <silasjoisten@hotmail.de>
 */
final class RolesMatrixBuilderTest extends TestCase
{
    private $securityHandler;
    private $authorizationChecker;
    private $admin;
    private $tokenStorage;
    private $token;
    private $pool;
    private $translator;
    private $securityInformation = [
        'GUEST' => [0 => 'VIEW', 1 => 'LIST'],
        'STAFF' => [0 => 'EDIT', 1 => 'LIST', 2 => 'CREATE'],
        'EDITOR' => [0 => 'OPERATOR', 1 => 'EXPORT'],
        'ADMIN' => [0 => 'MASTER'],
    ];

    public function setUp(): void
    {
        $this->securityHandler = $this->createMock(SecurityHandlerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->admin = $this->createMock(AdminInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->pool = $this->createMock(Pool::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testGetRolesNoToken(): void
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn([]);

        $rolesBuilder = new RolesMatrixBuilder(
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->pool
        );
        $this->assertEmpty($rolesBuilder->getRoles());
    }

    public function testGetRoles(): void
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $this->translator
            ->method('trans')
            ->with('Foo Admin')
            ->willReturn('Foo Admin Translated');

        $this->authorizationChecker
            ->method('isGranted')
            ->willReturn(true);

        $this->securityHandler
            ->method('getBaseRole')
            ->willReturn('ROLE_SONATA_ADMIN_FOO_%s');

        $this->admin
            ->method('isGranted')
            ->willReturn(true);

        $this->admin
            ->method('getSecurityHandler')
            ->willReturn($this->securityHandler);

        $this->admin
            ->method('getLabel')
            ->willReturn('Foo Admin');

        $this->admin
            ->method('getTranslator')
            ->willReturn($this->translator);

        $this->admin
            ->method('getSecurityInformation')
            ->willReturn($this->securityInformation);

        $this->pool
            ->method('getAdminServiceIds')
            ->willReturn(['sonata.admin.foo']);

        $this->pool
            ->method('getInstance')
            ->with('sonata.admin.foo')
            ->willReturn($this->admin);

        $expected = [
            'ROLE_SONATA_ADMIN_FOO_GUEST' => 'ROLE_SONATA_ADMIN_FOO_GUEST',
            'ROLE_SONATA_ADMIN_FOO_STAFF' => 'ROLE_SONATA_ADMIN_FOO_STAFF',
            'ROLE_SONATA_ADMIN_FOO_EDITOR' => 'ROLE_SONATA_ADMIN_FOO_EDITOR',
            'ROLE_SONATA_ADMIN_FOO_ADMIN' => 'ROLE_SONATA_ADMIN_FOO_ADMIN',
            'other' => [
                'ROLE_ADMIN' => 'ROLE_ADMIN',
            ],
        ];

        $rolesBuilder = new RolesMatrixBuilder(
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->pool
        );
        $this->assertSame($expected, $rolesBuilder->getRoles(null, false));
    }

    public function testGetAddExclude(): void
    {
        $rolesBuilder = new RolesMatrixBuilder(
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->pool
        );
        $rolesBuilder->addExclude('sonata.admin.bar');

        $this->assertSame(['sonata.admin.bar'], $rolesBuilder->getExclude());
    }
}
