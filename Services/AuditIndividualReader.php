<?php
/*
 * This code was developed by NIMA SOFTWARE SRL | nimasoftware.com
 * For details contact contact@nimasoftware.com
 */


namespace Stev\DataDogAuditGUIBundle\Services;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;

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

    public function getAuditForEntity($entityId, $entityClass)
    {

        $entity = $this->em->getRepository($entityClass)->find($entityId);

        /**
         * @var string $auditEntity
         * AbstractLogEntry
         *
         */
        $auditEntityClass = $entityClass.'Logger';

        $this->em = $registry->getManagerForClass($auditEntityClass);

        /** @var LogEntryRepository $auditRepo */
        $auditRepo = $this->em->getRepository($auditEntityClass);

        $auditLogs = $auditRepo->findBy(['objectId' => $entityId, 'objectClass' => $entityClass]);

        dump($auditLogs);die;
    }

}