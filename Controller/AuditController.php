<?php

namespace Stev\DataDogAuditGUIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
 * @Route("/audit")
 */
class AuditController extends Controller
{

    /**
     * @Route("/logs", name="audit_logs")
     * 
     */
    public function indexAction(Request $request)
    {
        $entityClass = $request->get('entityClass');
        $entityId = $request->get('entityid');
        if (empty($entityClass) && empty($entityId)) {
            return array();
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

        $queryUsers = $connection->prepare(
                "SELECT * FROM audit_associations  WHERE tbl = 'User' AND fk IS NOT NULL"
        );
        $queryUsers->execute();
        $users = $queryUsers->fetchAll();
        $userRepo = $em->getRepository('CVSoftUserBundle:User');
        foreach ($users as $key => $user) {
            $userEntity = $userRepo->find($user['fk']);
            if (!empty($userEntity)) {
                $users[$user['id']] = $userEntity->getUsername();
            }
        }
        $query = $connection->prepare(
                "SELECT diff, action, logged_at, blame_id  FROM audit_logs  WHERE tbl = '" . $table . "' AND blame_id IS NOT NULL AND source_id IN "
                . "(SELECT id FROM audit_associations  WHERE tbl = '" . $table . "' AND fk=" . $entityId . ")"
        );

        $query->execute();
        $results = $query->fetchAll();

        for ($i = 0; $i < count($results); $i++) {
            $results[$i]['user'] = $users[(int) $results[$i]['blame_id']];
            $results[$i]['diff'] = json_decode($results[$i]['diff'], true);
            unset($results[$i]['diff']['updatedAt']);
        }

        return $this->render('StevDataDogAuditGUIBundle:Audit:index.html.twig', array(
                    'entries' => $results,
                    'fieldMappings' => $fieldMappings
        ));
    }

    /**
     * @Route("/entity", name="audit_entity_logs", options={"expose"=true})
     *     
     * Ca sa folosesti tot ce trebuie sa faci e sa ai pe fiecare linie din tabel pt care vrei audit
     * data-audit-url, data-audit-entity-class, data-audit-entity-id
     * si sa apelezi entityAuditLogs()
     *
     */
    public function entityAuditAction(Request $request)
    {
        $fk = $request->query->get('fk');
        $tbl = $request->query->get('tbl', null);
        $entity = $request->query->get('entity');
        $includeInserts = (bool) $request->query->get('includeInserts', true);
        $assocsToInclude = $request->query->get('includeAssocs', array());

        $em = $this->getDoctrine()->getManager();
        $meta = $em->getClassMetadata($entity);

        if (null === $tbl) {
            $tbl = $meta->table['name'];
        }

        $fieldMappings = $meta->fieldMappings;

        foreach ($meta->associationMappings as $field => $assocMapping) {
            $fieldMappings[$field] = $em->getClassMetadata($assocMapping['targetEntity'])->table;
        }

        $assocDiffs = array();
        if (!empty($assocsToInclude)) {
            $assocDiffs = $this->getAuditForAssoc($em, $meta, $assocsToInclude, $fk, $includeInserts);
        }

        $results = $this->buildResultsDiff($fk, $entity);

        return $this->render('StevDataDogAuditGUIBundle:Audit:entity.html.twig', array(
                    'entries' => $results,
                    'assocs' => $assocDiffs,
                    'fieldMappings' => $fieldMappings,
                    'includeInserts' => $includeInserts,
        ));
    }

}
