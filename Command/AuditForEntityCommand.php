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
                ->addOption('entityClass', null, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'The entity class for which we want to get the audit.', null)
                ->addOption('entityId', null, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'The entity ID.', null)                
                ->setDescription('Gets the audit for a certain entity');
    }


    /*
     * TO-DO:
     * - foloseste exportul asta si la click dreapta pe lista slot-uri
     * - exportul intr-un serviciu separat, si atunci cand e folosit din interfata trimite doar email
     * - cand e folosit din CLI sa aiba optiune de trimitere pe email, dar mereu afiseaza in CLI
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('PROCESSING ...');

        /* @var $auditReader \Stev\DataDogAuditGUIBundle\Services\AuditReader */
        $auditReader = $this->getContainer()->get('stev_data_dog_audit_gui.audit_reader');

        $entityClass = $input->getOption('entityClass');
        $entityId = $input->getOption('entityId');

        $audit = $auditReader->getAuditForEntity($entityId, $entityClass, [], true);

        $headers = array('Action', 'Logged At', 'User', 'Diff');

        $currentDate = new \DateTime();

        /* @var $spout \Stev\SpoutBundle\Services\Spout */
        $spout = $this->getContainer()->get('stev_spout.box_spout');
        $writer = $spout->initWriter(\Box\Spout\Common\Type::XLSX);

        $filename = 'audit_export_' . $currentDate->format('Y-m-dH:i') . '.xlsx';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $writer->openToFile($filePath);
        $writer->addRow($headers);

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders($headers);

        foreach ($audit['results'] as $result) {
            $formattedDiff = [];
            foreach ($result['diff'] as $key => &$diff) {

                if (is_array($diff['old']) && isset($diff['old']['label'])) {
                    $diff['old'] = $diff['old']['label'];
                }

                if (is_array($diff['new']) && isset($diff['new']['label'])) {
                    $diff['new'] = $diff['new']['label'];
                }

                if ($diff['old'] != $diff['new']) {
                    $formattedDiff[] = $key . ': ' . $diff['new'];
                }
            }

            $row = [
                'action' => $result['action'],
                'loggedAt' => $result['loggedAt'],
                'user' => $result['user'],
                'diff' => join(PHP_EOL, $formattedDiff),
            ];

            $writer->addRow($row);
            $table->addRow($row);
            $table->addRow(new \Symfony\Component\Console\Helper\TableSeparator());
        }

        $table->render();
        $writer->close();

        /* @var $mailer \Smart\Bundle\AdminBundle\Services\MailManagers\MailManagerInterface */
        $mailer = $this->getContainer()->get('smart.admin.services.mail_manager');

        $attachments = [new \Smart\Bundle\AdminBundle\Model\File\EmailAttachment($filePath, $filename)];

        $mailer->sendEmail('Audit export for ' . $entityClass . ' and ID ' . $entityId, array(), 'Audit export ' . $currentDate->format('Y-m-d H:i'), 'stefan@nimasoftware.com', null, null, null, $attachments);

        $output->writeln('FINISHED');
    }

}
