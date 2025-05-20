<?php
declare(strict_types=1);
/**
 * Edit button block for view page
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Block\Adminhtml\Partner\View;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class EditButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $partnerId = $this->getPartnerId();
        if (!$partnerId) {
            return [];
        }
        
        return [
            'label' => __('Edit'),
            'on_click' => sprintf("location.href = '%s';", $this->getEditUrl()),
            'class' => 'edit primary',
            'sort_order' => 20
        ];
    }

    /**
     * Get URL for edit button
     *
     * @return string
     */
    public function getEditUrl(): string
    {
        return $this->getUrl('*/*/edit', ['partner_id' => $this->getPartnerId()]);
    }
}