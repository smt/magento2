<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Test\Constraint;

use Magento\CatalogSearch\Test\Page\Adminhtml\CatalogSearchIndex;
use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Class AssertSearchTermSuccessSaveMessage
 * Assert that success message is displayed after search term save
 */
class AssertSearchTermSuccessSaveMessage extends AbstractConstraint
{
    /* tags */
    const SEVERITY = 'high';
    /* end tags */

    /**
     * Text value to be checked
     */
    const SUCCESS_MESSAGE = 'You saved the search term.';

    /**
     * Assert that success message is displayed after search term save
     *
     * @param CatalogSearchIndex $indexPage
     * @return void
     */
    public function processAssert(CatalogSearchIndex $indexPage)
    {
        $actualMessage = $indexPage->getMessagesBlock()->getSuccessMessages();
        \PHPUnit_Framework_Assert::assertEquals(
            self::SUCCESS_MESSAGE,
            $actualMessage,
            'Wrong success message is displayed.'
            . PHP_EOL . "Expected: " . self::SUCCESS_MESSAGE
            . PHP_EOL . "Actual: " . $actualMessage
        );
    }

    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function toString()
    {
        return 'Search term success save message is present.';
    }
}
