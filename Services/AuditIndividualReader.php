<?php
/*
 * This code was developed by NIMA SOFTWARE SRL | nimasoftware.com
 * For details contact contact@nimasoftware.com
 */


namespace Stev\DataDogAuditGUIBundle\Services;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Mapping\Annotation\Loggable;

/**
 * Class AuditIndividualReader
 * @package Stev\DataDogAuditGUIBundle\Services
 *
 * Reader for Gedmo Loggable entities
 */
class AuditIndividualReader
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * IndividualAuditReader constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getAuditForEntityByIdAndClass($entityId, $entityClass){
        $this->em = $this->registry->getManagerForClass($entityClass);

        $entity = $this->em->find($entityClass, $entityId);

        if (!$entity) {
            throw new \Exception('Entity with ID '.$entityId.' for class '.$entityClass.' was not found!');
        }

        return $this->getAuditForEntity($entity);
    }

    /**
     * @param string $entityId
     * @param string $entityClass
     * @return \Gedmo\Loggable\Entity\LogEntry[]
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \ReflectionException
     */
    public function getAuditForEntity($entity)
    {

        $this->em = $this->registry->getManagerForClass(get_class($entity));

        $annotationReader = new AnnotationReader();
        $reflClass = new \ReflectionClass($entity);

        $ann = $annotationReader->getClassAnnotation($reflClass, Loggable::class);

        $auditEntityClass = $ann->logEntryClass ? $ann->logEntryClass : 'Gedmo\Loggable\Entity\LogEntry';

        /** @var LogEntryRepository $auditRepo */
        $auditRepo = $this->em->getRepository($auditEntityClass);

        $auditLogs = $auditRepo->getLogEntries($entity);

        return $auditLogs;
    }

}