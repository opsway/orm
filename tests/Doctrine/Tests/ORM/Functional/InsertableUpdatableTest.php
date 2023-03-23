<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Upsertable\Insertable;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceChild;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceNonInsertableColumn;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceNonUpdatableColumn;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceNonWritableColumn;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceRoot;
use Doctrine\Tests\Models\Upsertable\JoinedInheritanceWritableColumn;
use Doctrine\Tests\Models\Upsertable\Updatable;
use Doctrine\Tests\OrmFunctionalTestCase;

class InsertableUpdatableTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            JoinedInheritanceRoot::class,
            JoinedInheritanceChild::class,
            JoinedInheritanceWritableColumn::class,
            JoinedInheritanceNonWritableColumn::class,
            JoinedInheritanceNonInsertableColumn::class,
            JoinedInheritanceNonUpdatableColumn::class,
            Updatable::class,
            Insertable::class
        );
    }

    public function testNotInsertableIsFetchedFromDatabase(): void
    {
        $insertable                    = new Insertable();
        $insertable->insertableContent = 'abcdefg';

        $this->_em->persist($insertable);
        $this->_em->flush();

        // gets inserted from default value and fetches value from database
        self::assertEquals('1234', $insertable->nonInsertableContent);

        $insertable->nonInsertableContent = '5678';

        $this->_em->flush();
        $this->_em->clear();

        $insertable = $this->_em->find(Insertable::class, $insertable->id);

        // during UPDATE statement it is not ignored
        self::assertEquals('5678', $insertable->nonInsertableContent);
    }

    public function testNotUpdatableIsFetched(): void
    {
        $updatable                      = new Updatable();
        $updatable->updatableContent    = 'foo';
        $updatable->nonUpdatableContent = 'foo';

        $this->_em->persist($updatable);
        $this->_em->flush();

        $updatable->updatableContent    = 'bar';
        $updatable->nonUpdatableContent = 'baz';

        $this->_em->flush();

        self::assertEquals('foo', $updatable->nonUpdatableContent);

        $this->_em->clear();

        $cleanUpdatable = $this->_em->find(Updatable::class, $updatable->id);

        self::assertEquals('bar', $cleanUpdatable->updatableContent);
        self::assertEquals('foo', $cleanUpdatable->nonUpdatableContent);
    }

    public function testJoinedInheritanceRootColumns(): void
    {
        $entity                           = new JoinedInheritanceChild();
        $entity->rootWritableContent      = 'foo';
        $entity->rootNonWritableContent   = 'foo';
        $entity->rootNonInsertableContent = 'foo';
        $entity->rootNonUpdatableContent  = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check refetch override some non-insertable values
        self::assertEquals('foo', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);

        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceChild::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceChild::class, $entity);
        self::assertEquals('foo', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);

        // update
        $entity->rootWritableContent      = 'bar';
        $entity->rootNonInsertableContent = 'bar';
        $entity->rootNonWritableContent   = 'bar';
        $entity->rootNonUpdatableContent  = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check fetch generated values override prefilled for notUpdatable
        self::assertEquals('bar', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('bar', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);
    }

    public function testJoinedInheritanceWritableColumn(): void
    {
        $entity                  = new JoinedInheritanceWritableColumn();
        $entity->writableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check no any changes for writable value
        self::assertEquals('foo', $entity->writableContent);

        // check insert
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceWritableColumn::class, $cleanEntity);
        self::assertEquals('foo', $cleanEntity->writableContent);

        // update
        $entity->writableContent = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check no any changes for writable value
        self::assertEquals('bar', $entity->writableContent);

        // check update
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceWritableColumn::class, $cleanEntity);
        self::assertEquals('bar', $cleanEntity->writableContent);
    }

    public function testJoinedInheritanceNonWritableColumn(): void
    {
        $entity                     = new JoinedInheritanceNonWritableColumn();
        $entity->nonWritableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check fetch generated by db value
        self::assertEquals('dbDefault', $entity->nonWritableContent);

        // update
        $entity->rootField          = 'bar'; // to have changeset
        $entity->nonWritableContent = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check refetch update non updatable values
        self::assertEquals('dbDefault', $entity->nonWritableContent);

        // check update
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceNonWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonWritableColumn::class, $cleanEntity);
        self::assertEquals('dbDefault', $cleanEntity->nonWritableContent);
    }

    public function testJoinedInheritanceNonInsertableColumn(): void
    {
        $entity                       = new JoinedInheritanceNonInsertableColumn();
        $entity->nonInsertableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check fetch generated by db value
        self::assertEquals('dbDefault', $entity->nonInsertableContent);

        // update
        $entity->nonInsertableContent = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check no any changes for updatable value
        self::assertEquals('bar', $entity->nonInsertableContent);

        // check update
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceNonInsertableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonInsertableColumn::class, $cleanEntity);
        self::assertEquals('bar', $cleanEntity->nonInsertableContent);
    }

    public function testJoinedInheritanceNonUpdatableColumn(): void
    {
        $entity                      = new JoinedInheritanceNonUpdatableColumn();
        $entity->nonUpdatableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check refetch not override insertable value
        self::assertEquals('foo', $entity->nonUpdatableContent);

        // check insert
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceNonUpdatableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonUpdatableColumn::class, $cleanEntity);
        self::assertEquals('foo', $cleanEntity->nonUpdatableContent);

        // update
        $cleanEntity->rootField           = 'bar'; // to have changeset
        $cleanEntity->nonUpdatableContent = 'bar';

        $this->_em->persist($cleanEntity);
        $this->_em->flush();

        // check refetch update non updatable values
        self::assertEquals('foo', $cleanEntity->nonUpdatableContent);

        // check update
        $this->_em->clear();
        $cleanEntity = $this->_em->find(JoinedInheritanceNonUpdatableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonUpdatableColumn::class, $cleanEntity);
        self::assertEquals('foo', $cleanEntity->nonUpdatableContent);
    }
}
