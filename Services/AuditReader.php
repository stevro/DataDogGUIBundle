<?php

use Stev\DataDogAuditGUIBundle\Services;

/**
 * Description of AuditReader
 *
 * @author stefan
 */
class AuditReader
{

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function getAuditForAssoc($em, $meta, $assocsToInclude, $fk, $includeInserts)
    {
        $diff = array();
        $currentObj = $em->getRepository($meta->rootEntityName)->find($fk);

        if (!$currentObj) {
            throw new \Exception('Invalid ID for the searched object!');
        }

        foreach ($meta->associationMappings as $field => $assocMapping) {

            if (!in_array($assocMapping['targetEntity'], $assocsToInclude)) {
                continue;
            }

            $assoc = $currentObj->{'get' . ucfirst($field)}();

            if (!$assoc) {
                continue;
            }

            if ($assoc instanceof \Doctrine\Common\Collections\Collection) {
                foreach ($assoc as $a) {
                    $diff[$field][(string) $a] = $this->buildResultsDiff($a->getId(), $assocMapping['targetEntity'], $includeInserts);
                }
            } else {
                $diff[$field][(string) $assoc] = $this->buildResultsDiff($assoc->getId(), $assocMapping['targetEntity'], $includeInserts);
            }
        }

        return $diff;
    }

    public function buildResultsDiff($fk, $entityClass, $includeInserts = false)
    {
        $em = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();

        $meta = $em->getClassMetadata($entityClass);

        $tbl = $meta->table['name'];

        $query = $connection->prepare(
                "SELECT al.diff, al.action, al.logged_at, al.blame_id, aa.label as user "
                . "FROM audit_logs AS al LEFT JOIN audit_associations AS aa ON al.blame_id = aa.id "
                . "WHERE al.tbl = :table AND al.blame_id IS NOT NULL AND al.source_id IN "
                . "(SELECT id FROM audit_associations  WHERE tbl = :table AND fk=:fk)"
        );


        $query->execute(array('fk' => $fk, 'table' => $tbl));
        $results = $query->fetchAll();
        for ($i = 0; $i < count($results); $i++) {
            $results[$i]['diff'] = json_decode($results[$i]['diff'], true);
            unset($results[$i]['diff']['updatedAt']);
        }

        if ($includeInserts === false) {
            $results = array_filter($results, function($elem) {
                return $elem['action'] !== 'insert';
            });
        }

        return $results;
    }

}
