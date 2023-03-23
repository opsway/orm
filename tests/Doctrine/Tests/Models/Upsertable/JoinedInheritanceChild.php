<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Upsertable;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="joined_inheritance_child")
 */
#[Entity]
#[Table(name: 'joined_inheritance_child')]
class JoinedInheritanceChild extends JoinedInheritanceRoot
{
}
