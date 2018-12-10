<?php

namespace Stev\DataDogAuditGUIBundle\Services;

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

    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAuditForEntity($entityId, $entityClass, array $assocsToInclude = array(), $includeInserts = true)
    {
        $meta = $this->em->getClassMetadata($entityClass);

        $fieldMappings = $meta->fieldMappings;

        foreach ($meta->associationMappings as $field => $assocMapping) {
            $fieldMappings[$field] = $this->em->getClassMetadata($assocMapping['targetEntity'])->table;
        }

        $assocDiffs = array();
        if (!empty($assocsToInclude)) {
            $assocDiffs = $this->getAuditForAssoc($meta, $assocsToInclude, $entityId, $includeInserts);
        }

        $results = $this->buildResultsDiff($entityId, $entityClass, $includeInserts);

        return array(
            'results' => $results,
            'assocDiffs' => $assocDiffs,
            'fieldMappings' => $fieldMappings,
        );
    }

    /**
     *
     * @param type $em
     * @param type $meta
     * @param type $assocsToInclude
     * @param type $fk
     * @param type $includeInserts
     * @return type
     * @throws \Exception
     */
    private function getAuditForAssoc($meta, $assocsToInclude, $fk, $includeInserts)
    {
        $diff = array();
        $currentObj = $this->em->getRepository($meta->rootEntityName)->find($fk);

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

    private function buildResultsDiff($fk, $entityClass, $includeInserts = false)
    {
        $connection = $this->em->getConnection();

        $meta = $this->em->getClassMetadata($entityClass);

        $tbl = $meta->table['name'];

        $sql = <<<EOD
SELECT al.diff, al.action, al.loggedAt, al.blame_id, aa.label as user
FROM audit_logs AS al LEFT JOIN audit_associations AS aa ON al.blame_id = aa.id
WHERE al.tbl = :table AND al.blame_id IS NOT NULL AND al.source_id IN
(SELECT id FROM audit_associations  WHERE tbl = :table AND fk=:fk)
EOD;

        if ((bool) $includeInserts === false) {
            $sql .= ' AND al.type != "insert" ';
        }

        $query = $connection->prepare($sql);

        $query->execute(array('fk' => $fk, 'table' => $tbl));
        $results = $query->fetchAll();
        for ($i = 0; $i < count($results); $i++) {
            $results[$i]['diff'] = json_decode($results[$i]['diff'], true);
            unset($results[$i]['diff']['updatedAt']);
        }

//        if ((bool) $includeInserts === false) {
//            $results = array_filter($results, function($elem) {
//                return $elem['action'] !== 'insert';
//            });
//        }

        return $results;
    }

}
