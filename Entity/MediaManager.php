<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Sonata\MediaBundle\Entity\MediaManager as BaseEntityManager;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;

class MediaManager extends BaseEntityManager implements MediaManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = array())
    {
        /**
         * @var QueryBuilder $query
         */
        $query = $this->getRepository()
            ->createQueryBuilder('m')
            ->select('m');

        $fields = $this->getEntityManager()->getClassMetadata($this->class)->getFieldNames();
        foreach ($sort as $field => $direction) {
            if (!in_array($field, $fields)) {
                throw new \RuntimeException(sprintf("Invalid sort field '%s' in '%s' class", $field, $this->class));
            }
        }

        foreach ($sort as $field => $direction) {
            $query->orderBy(sprintf('m.%s', $field), strtoupper($direction));
        }

        $parameters = array();

        if (isset($criteria['category'])) {
            $query->andWhere('m.category IN :category');
            $parameters['category'] = $criteria['category'];
        }

        if (isset($criteria['enabled'])) {
            $query->andWhere('m.enabled = :enabled');
            $parameters['enabled'] = $criteria['enabled'];
        }

        $query->setParameters($parameters);

        $pager = new Pager();
        $pager->setMaxPerPage($limit);
        $pager->setQuery(new ProxyQuery($query));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }

    public function getMediasByCriteria(array $criteria, $orderBy = array())
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('m')
            ->select('m');

        if (isset($criteria['letter'])) {
            $qb->andWhere($qb->expr()->like('COLLATE(m.name, utf8_bin)', 'CAST(:name, _utf8)'))
               ->setParameter('name', $criteria['letter'].'%');
        }

        if (isset($criteria['category'])) {
            $qb->andWhere('m.category = :category')
               ->setParameter('category', $criteria['category']);
        }

        if ($orderBy) {
            foreach ($orderBy as $k => $v) {
                $qb->addOrderBy("m.".$k, $v);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function getLettersByCategory($category)
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery('
            SELECT m.name
            FROM Ok99PrivateZoneMediaBundle:Media m
            WHERE m.category = :category
            ORDER BY
              m.name ASC
        ')->setParameter('category', $category);



        return array_unique(array_map(function($a) {
            return ucfirst(mb_substr($a['name'], 0, 1, 'UTF-8'));
        }, $query->getResult()));
    }
}
