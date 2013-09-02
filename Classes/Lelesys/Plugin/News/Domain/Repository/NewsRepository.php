<?php

namespace Lelesys\Plugin\News\Domain\Repository;

/* *
 * This script belongs to the package "Lelesys.Plugin.News".               *
 *                                                                         *
 * It is free software; you can redistribute it and/or modify it under     *
 * the terms of the GNU Lesser General Public License, either version 3    *
 * of the License, or (at your option) any later version.                  *
 *                                                                         */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * A repository for News
 *
 * @Flow\Scope("singleton")
 */
class NewsRepository extends \TYPO3\Flow\Persistence\Doctrine\Repository {

	/**
	 *
	 * @var array
	 */
	protected $defaultOrderings = array('dateTime' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING);

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Get news entries
	 *
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface The query result
	 */
	public function getEnabledNews() {
		$query = $this->createQuery();
		return $query->matching(
								$query->equals('hidden', 0)
						)
						->setOrderings(array('dateTime' => \TYPO3\Flow\Persistence\Generic\Query::ORDER_DESCENDING))
						->execute();
	}

	/**
	 * Get the news list by category
	 *
	 * @param string $category The category
	 * @param string $folder
	 * @param string $tag
	 * @param array $pluginArguments
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function getEnabledNewsBySelection($category = NULL, $folder = NULL, $pluginArguments = array(), $tag = NULL) {
		$emConfig = $this->entityManager->getConfiguration();
		$emConfig->addCustomDatetimeFunction('DATEDIFF', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\DateDiff');
		$query = $this->createQuery();

		$queryBuilder = ObjectAccess::getProperty($query, 'queryBuilder', TRUE);

		$constraints = array();
		if (!empty($folder)) {
			$constraints[] = 'n.folder = ' . "'" . $folder . "'";
		}
		if (!empty($category)) {
			$constraints[] = 'c.Persistence_Object_Identifier IN (' . "'" . $category . "'" . ')';
		}
		if (!empty($tag)) {
			$constraints[] = 't.Persistence_Object_Identifier IN (' . "'" . $tag . "'" . ')';
		}
		$newsConstraints = '';
		$count = count($constraints);
		$newCount = 1;
		foreach ($constraints as $contraint) {
			if ($count > $newCount) {
				$newsConstraints .= $contraint . ' OR ';
			} else {
				$newsConstraints .= $contraint;
			}
			$newCount++;
		}
		$queryBuilder
				->resetDQLParts()
				->select('n')
				->from('Lelesys\Plugin\News\Domain\Model\News', 'n')
				->leftjoin('n.categories', 'c')
				->leftjoin('n.tags', 't')
				->where('n.startDate is null and n.endDate >= current_date()
					OR DATEDIFF(n.startDate,current_date())<1 and n.endDate >= current_date()
					OR n.endDate is null and n.startDate is null
					OR n.endDate is null and n.startDate <= current_date() and DATEDIFF(n.startDate,current_date())<1');
		if ((!empty($category)) || (!empty($folder)) || (!empty($tag))) {
			$queryBuilder->andWhere(
					$newsConstraints
			);
		}

		if (!empty($pluginArguments['orderBy'])) {
			if ($pluginArguments['sortBy'] === 'DESC') {
				$queryBuilder->orderBy('n.dateTime', 'DESC');
			} else {
				$queryBuilder->orderBy('n.dateTime', 'ASC');
			}
		}
		return $query->execute();
	}

	/**
	 * Get the news list by category
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Category $category The category
	 * @param \Lelesys\Plugin\News\Domain\Model\Folder $folder
	 * @param \Lelesys\Plugin\News\Domain\Model\Tag $tag
	 * @param array $pluginArguments
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function getNewsAdmin(\Lelesys\Plugin\News\Domain\Model\Category $category = NULL, \Lelesys\Plugin\News\Domain\Model\Folder $folder = NULL, $pluginArguments = array(), \Lelesys\Plugin\News\Domain\Model\Tag $tag = NULL) {
		$query = $this->createQuery();
		$constraint = array();
		if (!empty($folder)) {
			$constraint[] = $query->equals('folder', $folder);
		}
		if (!empty($category)) {
			$constraint[] = $query->contains('categories', $category);
		}
		if ((!empty($category)) || (!empty($folder))) {
			$query = $query->matching(
					$query->logicalOr(
							$constraint
					)
			);
		}

		return $query->execute();
	}

	/**
	 * Shows list of news as per month.
	 *
	 * @param integer $year
	 * @param string $month
	 * @param array $pluginArguments
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function archiveNewsList($year, $month, $pluginArguments) {
		$emConfig = $this->entityManager->getConfiguration();
		$emConfig->addCustomDatetimeFunction('DATEDIFF', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\DateDiff');
		$emConfig->addCustomDatetimeFunction('YEAR', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\Year');
		$emConfig->addCustomDatetimeFunction('MONTH', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\Month');

		$query = $this->createQuery();
		$queryBuilder = ObjectAccess::getProperty($query, 'queryBuilder', TRUE);

		$queryBuilder
				->resetDQLParts()
				->select('n')
				->from('Lelesys\Plugin\News\Domain\Model\News', 'n')
				->where('n.startDate is null and n.endDate >= current_date()
					OR DATEDIFF(n.startDate,current_date())<1 and n.endDate >= current_date()
					OR n.endDate is null and n.startDate is null
					OR n.endDate is null and n.startDate <= current_date() and DATEDIFF(n.startDate,current_date())<1')
				->andWhere('YEAR(n.dateTime) = ' . $year . ' AND MONTH(n.dateTime) = ' . date("n", strtotime($month)) . '');

		if (!empty($pluginArguments['orderBy'])) {
			if ($pluginArguments['sortBy'] === 'DESC') {
				$queryBuilder->orderBy('n.dateTime', 'DESC');
			} else {
				$queryBuilder->orderBy('n.dateTime', 'ASC');
			}
		}
		return $query->execute();
	}

	/**
	 * Finds all the news by search value
	 *
	 * @param string $searchval
	 * @param array $pluginArguments
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function searchAll($searchval, $pluginArguments) {
		$emConfig = $this->entityManager->getConfiguration();
		$emConfig->addCustomDatetimeFunction('DATEDIFF', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\DateDiff');
		$query = $this->createQuery();
		$queryBuilder = ObjectAccess::getProperty($query, 'queryBuilder', TRUE);

		$queryBuilder
				->resetDQLParts()
				->select('n')
				->from('Lelesys\Plugin\News\Domain\Model\News', 'n')
				->where('(n.startDate is null and n.endDate >= current_date()
					OR DATEDIFF(n.startDate,current_date())<1 and n.endDate >= current_date()
					OR n.endDate is null and n.startDate is null
					OR n.endDate is null and n.startDate <= current_date() and DATEDIFF(n.startDate,current_date())<1) AND n.hidden = 0')
				->andWhere('n.title like :searchterm OR n.subTitle like :searchterm OR n.description like :searchterm')
				->setParameter('searchterm', '%' . $searchval . '%');

		if (!empty($pluginArguments['orderBy'])) {
			if ($pluginArguments['sortBy'] === 'DESC') {
				$queryBuilder->orderBy('n.dateTime', 'DESC');
			} else {
				$queryBuilder->orderBy('n.dateTime', 'ASC');
			}
		}
		return $query->execute();
	}

	/**
	 * Finds all the news by year and month
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function archiveDateView() {
		$emConfig = $this->entityManager->getConfiguration();
		$emConfig->addCustomDatetimeFunction('DATEDIFF', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\DateDiff');
		$emConfig->addCustomDatetimeFunction('YEAR', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\Year');
		$emConfig->addCustomDatetimeFunction('MONTH', 'Lelesys\Plugin\News\Doctrine\Query\Mysql\Month');

		$query = $this->createQuery();
		$queryBuilder = ObjectAccess::getProperty($query, 'queryBuilder', TRUE);
		$queryBuilder
				->resetDQLParts()
				->select('YEAR(n.dateTime) year, MONTH(n.dateTime) month, COUNT(n) cnt')
				->from('Lelesys\Plugin\News\Domain\Model\News', 'n')
				->where('(n.startDate is null and n.endDate >= current_date()
					OR DATEDIFF(n.startDate,current_date())<1 and n.endDate >= current_date()
					OR n.endDate is null and n.startDate is null
					OR n.endDate is null and n.startDate <= current_date() and DATEDIFF(n.startDate,current_date())<1) AND n.hidden = 0')
				->groupBy('year, month')
				->orderBy('year', 'DESC');

		return $query->execute();
	}

}

?>
