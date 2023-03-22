<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Upsertable;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="joined_inheritance_non_insertable_column")
 */
#[Entity]
#[Table(name: 'joined_inheritance_non_insertable_column')]
class JoinedInheritanceNonInsertableColumn extends JoinedInheritanceRoot
{
    /**
     * @var string
     * @Column(type="string", insertable=false, updatable=true, generated="ALWAYS")
     */
    #[Column(type: 'string', insertable: false, updatable: true, generated: 'ALWAYS')]
    public $nonInsertableContent;
}
