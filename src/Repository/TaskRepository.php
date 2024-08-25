<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    private Security $security;
    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Task::class);
        $this->security = $security;
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserTasks(UserInterface $user): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->andWhere('t.isDone = :done')
            ->setParameter('done', false);

        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $queryBuilder->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getResult();
    }
    public function findUserTasksDone(UserInterface $user): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->andWhere('t.isDone = :done')
            ->setParameter('done', true);

        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $queryBuilder->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getResult();
    }

}
