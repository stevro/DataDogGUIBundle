<?php

namespace Stev\DataDogAuditGUIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Stev\DataDogAuditGUIBundle\Services\AuditIndividualReader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/*
 * Configurari pentru a afisa modalul cu audit
 *
 * Pe  <tr class="entity_details trebuie adaugate urmatoarele atribute:
 * 		data-audit-url="{{path('audit_logs')}}" data-audit-entity-id="{{entity.id}}" data-audit-entity-class="CVSoft\MainBundle\Entity\Project"
 *
 * buton/ link care sa declanseze actiunea(important e id-ul "id="btn-audit-logs"")
 * 		<a class="btn btn-primary btn-xs audit margin-r-5" href="#" id="btn-audit-logs" data-entity="">
  {{ 'project.journal'|trans | desc('Journal')}}
  </a>
 *
 * 	Metoda auditLogs(); apelata in js
 */

/**
 * Audit controller.
 *
 */
class AuditController extends Controller
{

    /**
     * @Route("/logs", name="audit_logs")
     */
    public function indexAction(Request $request)
    {
        $entityClass = $request->get('entityClass');
        $entityId = $request->get('entityid');
        if (empty($entityClass) && empty($entityId)) {
            return $this->render(
                'StevDataDogAuditGUIBundle:Audit:index.html.twig',
                [
                    'entries' => [],
                    'fieldMappings' => [],
                ]
            );
        }

        $em = $this->getDoctrine()->getManager();
        $meta = $em->getClassMetadata($entityClass);
        $table = $meta->getTableName();
        $fieldMappings = $meta->fieldMappings;
        foreach ($meta->associationMappings as $field => $assocMapping) {
            $fieldMappings[$field] = $em->getClassMetadata($assocMapping['targetEntity'])->table;
        }
        $translator = $this->get('translator');

        $connection = $em->getConnection();

        $sql = <<<EOD
SELECT al.diff, al.action, al.loggedAt, al.blame_id, aa.label AS user
FROM audit_logs  AS al
INNER JOIN audit_associations AS aa ON aa.id = al.blame_id
WHERE al.tbl = '{$table}' AND al.source_id IN (SELECT id FROM audit_associations  WHERE tbl = '{$table}' AND fk='{$entityId}')
EOD;

        $query = $connection->prepare($sql);

        $query->execute();
        $results = $query->fetchAll();

        for ($i = 0; $i < count($results); $i++) {
            $results[$i]['diff'] = json_decode($results[$i]['diff'], true);
            unset($results[$i]['diff']['updatedAt']);
        }

        return $this->render(
            'StevDataDogAuditGUIBundle:Audit:index.html.twig',
            [
                'entries' => $results,
                'fieldMappings' => $fieldMappings,
            ]
        );
    }

    /**
     * @Route("/entity/{entityClass}/{entityId}", name="audit_entity_logs", options={"expose"=true})
     * @Method("GET")
     */
    public function entityAuditAction(Request $request, $entityClass, $entityId)
    {
        $includeInserts = (bool)$request->query->get('includeInserts', true);
        $assocsToInclude = $request->query->get('includeAssocs', []);

        if (!is_array($assocsToInclude)) {
            $assocsToInclude = [];
        }

        /* @var $auditReader \Stev\DataDogAuditGUIBundle\Services\AuditReader */
        $auditReader = $this->get('stev_data_dog_audit_gui.audit_reader');

        $audit = $auditReader->getAuditForEntity($entityId, $entityClass, $assocsToInclude, $includeInserts);

        //$request->headers->get('content-type');

        return $this->render(
            'StevDataDogAuditGUIBundle:Audit:entity.html.twig',
            [
                'entries' => $audit['results'],
                'assocs' => $audit['assocDiffs'],
                'fieldMappings' => $audit['fieldMappings'],
                'includeInserts' => $includeInserts,
            ]
        );
    }

    /**
     * @Route("/entity/{entityClass}/{entityId}/individual", name="audit_entity_individual_logs", options={"expose"=true})
     * @Method("GET")
     *
     * sample /audit/entity/AppBundle:EntityName/EntityID/individual
     */
    public function entityIndividualAuditAction(Request $request, $entityClass, $entityId)
    {
        /** @var AuditIndividualReader $auditReader */
        $auditReader = $this->get('stev_data_dog_audit_gui.audit_individual_reader');

        $auditLogs = $auditReader->getAuditForEntityByIdAndClass($entityId, $entityClass);

        return $this->render(
            'StevDataDogAuditGUIBundle:AuditIndividual:entity.html.twig',
            [
                'entries' => $auditLogs,
            ]
        );
    }

}
