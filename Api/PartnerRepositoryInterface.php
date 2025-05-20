<?php
/**
 * Partner repository interface
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;

/**
 * Interface PartnerRepositoryInterface
 * @api
 */
interface PartnerRepositoryInterface
{
    /**
     * Save partner
     *
     * @param PartnerInterface $partner
     * @return PartnerInterface
     * @throws LocalizedException
     */
    public function save(PartnerInterface $partner): PartnerInterface;

    /**
     * Get partner by ID
     *
     * @param int $partnerId
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $partnerId): PartnerInterface;

    /**
     * Get active partner by ID
     *
     * @param int $partnerId
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getActiveById(int $partnerId): PartnerInterface;

    /**
     * Get partner by slug
     *
     * @param string $slug
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getBySlug(string $slug): PartnerInterface;

    /**
     * Get active partner by slug
     *
     * @param string $slug
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getActiveBySlug(string $slug): PartnerInterface;

    /**
     * Delete partner
     *
     * @param PartnerInterface $partner
     * @return bool
     * @throws LocalizedException
     */
    public function delete(PartnerInterface $partner): bool;

    /**
     * Delete partner by ID
     *
     * @param int $partnerId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $partnerId): bool;

    /**
     * Get partner list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface;

    /**
     * Get active partners list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
     */
    public function getActiveList(SearchCriteriaInterface $searchCriteria): \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface;
}