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

namespace Sonata\UserBundle\Security;

/**
 * @author Silas Joisten <silasjoisten@hotmail.de>
 */
interface RolesBuilderInterface
{
    public function getRoles($domain = false, bool $expanded = true): array;

    public function getAdminRolesForView(): array;

    public function getCustomRolesForView(): array;
}
