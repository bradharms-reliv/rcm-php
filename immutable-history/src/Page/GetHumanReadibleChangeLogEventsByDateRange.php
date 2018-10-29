<?php

namespace Rcm\ImmutableHistory\Page;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Rcm\ImmutableHistory\HumanReadableChangeLog\ChangeLogEvent;
use Rcm\ImmutableHistory\HumanReadableChangeLog\GetHumanReadableChangeLogEventsByDateRangeInterface;
use Rcm\ImmutableHistory\VersionActions;

class GetHumanReadibleChangeLogEventsByDateRange implements GetHumanReadableChangeLogEventsByDateRangeInterface
{
    protected $entityManager;

    public function __construct(EntityManager $entityManger)
    {
        $this->entityManager = $entityManger;
    }

    public function __invoke(\DateTime $greaterThanDate, \DateTime $lessThanDate): array
    {
        $doctrineRepo = $this->entityManager->getRepository(ImmutablePageVersionEntity::class);

        $criteria = new \Doctrine\Common\Collections\Criteria();
        $criteria->where($criteria->expr()->gt('date', $greaterThanDate));
        $criteria->andWhere($criteria->expr()->lt('date', $lessThanDate));
        $criteria->orderBy(['date' => Criteria::DESC, 'id' => Criteria::DESC]);

        $entities = $doctrineRepo->matching($criteria)->toArray();

        $entityToHumanReadable = function ($version): ChangeLogEvent {
            switch ($version->getAction()) { //@TODO handle more action types
                case VersionActions::CREATE_UNPUBLISHED_FROM_NOTHING:
                    $actionDescription = 'created an unpublished version of';
                    break;
                case VersionActions::PUBLISH_FROM_NORTHING:
                    $actionDescription = 'created a published version of ';
                    break;
                default:
                    throw new \Exception('Unknown action type found: ' . $version->getAction());
            }

            $event = new ChangeLogEvent();
            $event->setDate($version->getDate());
            $event->setUserId($version->getUserId());
            $event->setActionDescription($actionDescription);
            $event->setResourceDescription(
                'page "' . $version->getPathname()
                . '" on site #' . $version->getSiteId()
                . ' ({{siteIdToDomainName}}' . $version->getPathname() . ')');
            $event->setMetaData(
                [
                    'siteId' => $version->getSiteId(),
                    'relativeUrl' => $version->getPathname()
                ]
            );

            return $event;
        };

        return array_map($entityToHumanReadable, $entities);
    }
}
