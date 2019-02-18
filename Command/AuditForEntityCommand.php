<?php

namespace Stev\DataDogAuditGUIBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuditForEntityCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
                ->setName('stev:data-dog:auditForEntity')
                ->addOption('entityClass',null, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'The entity class for which we want to get the audit.', null)
                ->addOption('entityId',null, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'The entity ID.', null)
                ->setDescription('Gets the audit for a certain entity');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('PROCESSING ...');

        /* @var $em \Doctrine\ORM\EntityManager */
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /* @var $auditReader \Stev\DataDogAuditGUIBundle\Services\AuditReader */
        $auditReader = $this->getContainer()->get('stev_data_dog_audit_gui.audit_reader');

        $entityClass = $input->getOption('entityClass');
        $entityId = $input->getOption('entityId');

        $results = $auditReader->getAuditForEntity($entityId, $entityClass, [], true);

        dump($results);

        $output->writeln('FINISHED');
    }

}
