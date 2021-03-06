<?php

namespace Lelesys\Plugin\News\Domain\Service;

/* *
 * This script belongs to the package "Lelesys.Plugin.News".               *
 *                                                                         *
 * It is free software; you can redistribute it and/or modify it under     *
 * the terms of the GNU Lesser General Public License, either version 3    *
 * of the License, or (at your option) any later version.                  *
 *                                                                         */

use TYPO3\Flow\Annotations as Flow;
use \Lelesys\Plugin\News\Domain\Model\Comment;

/**
 * Comment Service for the Lelesys.Plugin.News package
 *
 * @Flow\Scope("singleton")
 */
class CommentService {

	/**
	 * The comment repository
	 *
	 * @Flow\Inject
	 * @var \Lelesys\Plugin\News\Domain\Repository\CommentRepository
	 */
	protected $commentRepository;

	/**
	 * The email notification service
	 *
	 * @Flow\Inject
	 * @var \Lelesys\Plugin\News\Service\Notification
	 */
	protected $emailNotification;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Injects settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Shows a list of comments
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function listAll() {
		return $this->commentRepository->findAll();
	}

	/**
	 * Shows a list of comments
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\News $news The news
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function getCommentsByNews(\Lelesys\Plugin\News\Domain\Model\News $news) {
		return $this->commentRepository->findByNews($news);
	}

	/**
	 * Returns non hidden comments of news
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\News $newsObj The news object
	 * @return array $enabledComments
	 */
	public function getEnabledComments($newsObj) {
		$enabledComments = array();
		foreach ($newsObj as $news) {
			$comments = $news->getComments();
			if (count($comments) > 0) {
				foreach ($comments as $comment) {
					if ($comment->getSetHidden() !== TRUE) {
						$enabledComments[$news->getUuid()][] = $comment;
					}
				}
			}
		}
		return $enabledComments;
	}

	/**
	 * Adds the given new comment object to the comment repository
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $newComment A new comment to add
	 * @param \Lelesys\Plugin\News\Domain\Model\News $news The news
	 * @return void
	 */
	public function create(\Lelesys\Plugin\News\Domain\Model\Comment $newComment, \Lelesys\Plugin\News\Domain\Model\News $news) {
		$newComment->setNews($news);
		if ($this->settings['comment']['publish'] !== NULL) {
			$newComment->setSetHidden($this->settings['comment']['publish']);
		}
		$this->commentRepository->add($newComment);

		if ($news->getCreatedBy() !== NULL) {
			if ($news->getCreatedBy()->getPrimaryElectronicAddress() !== NULL) {
				$this->emailNotification->sendCommentApprovalNotification($newComment, $news);
			}
		}
		$this->emitCommentCreated($newComment);
	}

	/**
	 * Removes the given comment object from the comment repository
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $comment The comment to delete
	 * @return void
	 */
	public function delete(\Lelesys\Plugin\News\Domain\Model\Comment $comment) {
		$this->commentRepository->remove($comment);
		$this->emitCommentDeleted($comment);
	}

	/**
	 * Shows the list of news by category
	 *
	 * @param array $filterFlag The active flag
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function listAllCommentsAdmin($filterFlag) {
		if ($filterFlag[0] !== NULL) {
			if ($filterFlag[0] === 'select') {
				return $this->commentRepository->findAll();
			} else {
				return $this->commentRepository->findBySetHidden($filterFlag[0]);
			}
		} else {
			return $this->commentRepository->findAll();
		}
	}

	/**
	 * Return asset for given identifier
	 *
	 * @param string $identifier comment identifier
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 */
	public function filterComment($identifier) {
		$comment = $this->commentRepository->findByIdentifier($identifier);
		return $comment;
	}

	/**
	 * hide's the category
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $comment The comment to unpublish
	 * @return void
	 */
	public function unPublishComment(\Lelesys\Plugin\News\Domain\Model\Comment $comment) {
		$comment->setSetHidden(1);
		$this->commentRepository->update($comment);
	}

	/**
	 * shows's the hidden category
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $category The comment to publish
	 * @return void
	 */
	public function publishComment(\Lelesys\Plugin\News\Domain\Model\Comment $comment) {
		$comment->setSetHidden(0);
		$this->commentRepository->update($comment);
	}

	/**
	 * Signal for Comment created
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $comment The Comment
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitCommentCreated(\Lelesys\Plugin\News\Domain\Model\Comment $comment) {

	}

	/**
	 * Signal for Comment deleted
	 *
	 * @param \Lelesys\Plugin\News\Domain\Model\Comment $comment The Comment
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitCommentDeleted(\Lelesys\Plugin\News\Domain\Model\Comment $comment) {

	}

}

?>
