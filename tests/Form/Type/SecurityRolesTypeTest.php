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

namespace Sonata\UserBundle\Tests\Form\Type;

use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Sonata\UserBundle\Security\EditableRolesBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Quentin Fahrner <quentfahrner@gmail.com>
 */
class SecurityRolesTypeTest extends TypeTestCase
{
    protected $roleBuilder;

    public function testGetDefaultOptions(): void
    {
        $type = new SecurityRolesType($this->roleBuilder);

        $optionResolver = new OptionsResolver();
        $type->configureOptions($optionResolver);

        $options = $optionResolver->resolve();
        $this->assertCount(3, $options['choices']);

        if (method_exists(FormTypeInterface::class, 'setDefaultOptions')) {
            $this->assertTrue($options['choices_as_values']);
        }
    }

    public function testGetParent(): void
    {
        $type = new SecurityRolesType($this->roleBuilder);

        $this->assertEquals(ChoiceType::class, $type->getParent());
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create($this->getSecurityRolesTypeName(), null, [
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ]);

        $form->submit([0 => 'ROLE_FOO']);

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(1, $form->getData());
        $this->assertTrue(in_array('ROLE_FOO', $form->getData()));
    }

    public function testSubmitInvalidData(): void
    {
        $form = $this->factory->create($this->getSecurityRolesTypeName(), null, [
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ]);

        $form->submit([0 => 'ROLE_NOT_EXISTS']);

        $this->assertFalse($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testChoicesAsValues(): void
    {
        $resolver = new OptionsResolver();
        $type = new SecurityRolesType($this->roleBuilder);

        // If 'choices_as_values' option is not defined (Symfony >= 3.0), default value should not be set.
        $type->configureOptions($resolver);

        $this->assertFalse($resolver->hasDefault('choices_as_values'));

        // If 'choices_as_values' option is defined (Symfony 2.8), default value should be set to true.
        $resolver->setDefault('choices_as_values', true);
        $type->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertTrue($resolver->hasDefault('choices_as_values'));
        $this->assertTrue($options['choices_as_values']);
    }

    protected function getExtensions()
    {
        $this->roleBuilder = $roleBuilder = $this->createMock(EditableRolesBuilder::class);

        $this->roleBuilder->expects($this->any())->method('getAllRoles')->will($this->returnValue([
          'ROLE_FOO' => 'ROLE_FOO',
          'ROLE_USER' => 'ROLE_USER',
          'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER',
        ]));

        $childType = new SecurityRolesType($this->roleBuilder);

        return [new PreloadedExtension([
          $childType->getName() => $childType,
        ], [])];
    }

    private function getSecurityRolesTypeName()
    {
        return 'Sonata\UserBundle\Form\Type\SecurityRolesType';
    }
}
