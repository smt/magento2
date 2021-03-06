<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogUrlRewrite\Model\Category;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\TestFramework\Helper\ObjectManager;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class CurrentUrlRewritesRegeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\CatalogUrlRewrite\Model\Category\CurrentUrlRewritesRegenerator */
    protected $currentUrlRewritesRegenerator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $filter;

    /** @var \Magento\UrlRewrite\Model\UrlFinderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlFinder;

    /** @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryUrlPathGenerator;

    /** @var \Magento\Catalog\Model\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $category;

    /** @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlRewriteBuilder;

    /** @var \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlRewrite;

    protected function setUp()
    {
        $this->urlRewriteBuilder = $this->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\UrlRewriteBuilder')
            ->disableOriginalConstructor()->getMock();
        $this->urlRewrite = $this->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\UrlRewrite')
            ->disableOriginalConstructor()->getMock();
        $this->category = $this->getMockBuilder('Magento\Catalog\Model\Category')
            ->disableOriginalConstructor()->getMock();
        $this->filter = $this->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\Filter')
            ->disableOriginalConstructor()->getMock();
        $this->filter->expects($this->any())->method('setStoreId')->will($this->returnSelf());
        $this->filter->expects($this->any())->method('setEntityId')->will($this->returnSelf());
        $this->urlFinder = $this->getMockBuilder('Magento\UrlRewrite\Model\UrlFinderInterface')
            ->disableOriginalConstructor()->getMock();
        $this->categoryUrlPathGenerator = $this->getMockBuilder(
            'Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator'
        )->disableOriginalConstructor()->getMock();
        $this->currentUrlRewritesRegenerator = (new ObjectManager($this))->getObject(
            'Magento\CatalogUrlRewrite\Model\Category\CurrentUrlRewritesRegenerator',
            [
                'urlFinder' => $this->urlFinder,
                'categoryUrlPathGenerator' => $this->categoryUrlPathGenerator,
                'urlRewriteBuilder' => $this->urlRewriteBuilder
            ]
        );
    }

    public function testIsAutogeneratedWithoutSaveRewriteHistory()
    {
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will($this->returnValue($this->getCurrentRewritesMocks([[UrlRewrite::IS_AUTOGENERATED => 1]])));
        $this->category->expects($this->once())->method('getData')->with('save_rewrites_history')
            ->will($this->returnValue(false));

        $this->assertEquals(
            [],
            $this->currentUrlRewritesRegenerator->generate('store_id', $this->category)
        );
    }

    public function testSkipGenerationForAutogenerated()
    {
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will(
                $this->returnValue(
                    $this->getCurrentRewritesMocks(
                        [
                            [UrlRewrite::IS_AUTOGENERATED => 1, UrlRewrite::REQUEST_PATH => 'same-path'],
                        ]
                    )
                )
            );
        $this->category->expects($this->once())->method('getData')->with('save_rewrites_history')
            ->will($this->returnValue(true));
        $this->categoryUrlPathGenerator->expects($this->once())->method('getUrlPathWithSuffix')
            ->will($this->returnValue('same-path'));

        $this->assertEquals(
            [],
            $this->currentUrlRewritesRegenerator->generate('store_id', $this->category)
        );
    }

    public function testIsAutogenerated()
    {
        $requestPath = 'autogenerated.html';
        $targetPath = 'some-path.html';
        $storeId = 2;
        $categoryId = 12;
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will(
                $this->returnValue(
                    $this->getCurrentRewritesMocks(
                        [
                            [
                                UrlRewrite::REQUEST_PATH => $requestPath,
                                UrlRewrite::TARGET_PATH => 'custom-target-path',
                                UrlRewrite::STORE_ID => $storeId,
                                UrlRewrite::IS_AUTOGENERATED => 1,
                                UrlRewrite::METADATA => [],
                            ],
                        ]
                    )
                )
            );
        $this->category->expects($this->any())->method('getId')->will($this->returnValue($categoryId));
        $this->category->expects($this->once())->method('getData')->with('save_rewrites_history')
            ->will($this->returnValue(true));
        $this->categoryUrlPathGenerator->expects($this->once())->method('getUrlPathWithSuffix')
            ->will($this->returnValue($targetPath));

        $this->prepareUrlRewriteMock($storeId, $categoryId, $requestPath, $targetPath, OptionProvider::PERMANENT);

        $this->assertEquals(
            [$this->urlRewrite],
            $this->currentUrlRewritesRegenerator->generate($storeId, $this->category)
        );
    }

    public function testSkipGenerationForCustom()
    {
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will(
                $this->returnValue(
                    $this->getCurrentRewritesMocks(
                        [
                            [
                                UrlRewrite::IS_AUTOGENERATED => 0,
                                UrlRewrite::REQUEST_PATH => 'same-path',
                                UrlRewrite::REDIRECT_TYPE => 1,
                            ],
                        ]
                    )
                )
            );
        $this->categoryUrlPathGenerator->expects($this->once())->method('getUrlPathWithSuffix')
            ->will($this->returnValue('same-path'));

        $this->assertEquals(
            [],
            $this->currentUrlRewritesRegenerator->generate('store_id', $this->category)
        );
    }

    public function testGenerationForCustomWithoutTargetPathGeneration()
    {
        $storeId = 12;
        $categoryId = 123;
        $requestPath = 'generate-for-custom-without-redirect-type.html';
        $targetPath = 'custom-target-path.html';
        $description = 'description';
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will(
                $this->returnValue(
                    $this->getCurrentRewritesMocks(
                        [
                            [
                                UrlRewrite::REQUEST_PATH => $requestPath,
                                UrlRewrite::TARGET_PATH => $targetPath,
                                UrlRewrite::REDIRECT_TYPE => 0,
                                UrlRewrite::IS_AUTOGENERATED => 0,
                                UrlRewrite::DESCRIPTION => $description,
                                UrlRewrite::METADATA => [],
                            ],
                        ]
                    )
                )
            );
        $this->categoryUrlPathGenerator->expects($this->never())->method('getUrlPathWithSuffix');
        $this->category->expects($this->any())->method('getId')->will($this->returnValue($categoryId));
        $this->urlRewriteBuilder->expects($this->once())->method('setDescription')->with($description)
            ->will($this->returnSelf());
        $this->prepareUrlRewriteMock($storeId, $categoryId, $requestPath, $targetPath, 0);

        $this->assertEquals(
            [$this->urlRewrite],
            $this->currentUrlRewritesRegenerator->generate($storeId, $this->category)
        );
    }

    public function testGenerationForCustomWithTargetPathGeneration()
    {
        $storeId = 12;
        $categoryId = 123;
        $requestPath = 'generate-for-custom-without-redirect-type.html';
        $targetPath = 'generated-target-path.html';
        $description = 'description';
        $this->urlFinder->expects($this->once())->method('findAllByData')
            ->will(
                $this->returnValue(
                    $this->getCurrentRewritesMocks(
                        [
                            [
                                UrlRewrite::REQUEST_PATH => $requestPath,
                                UrlRewrite::TARGET_PATH => 'custom-target-path.html',
                                UrlRewrite::REDIRECT_TYPE => 'code',
                                UrlRewrite::IS_AUTOGENERATED => 0,
                                UrlRewrite::DESCRIPTION => $description,
                                UrlRewrite::METADATA => [],
                            ],
                        ]
                    )
                )
            );
        $this->categoryUrlPathGenerator->expects($this->any())->method('getUrlPathWithSuffix')
            ->will($this->returnValue($targetPath));
        $this->category->expects($this->any())->method('getId')->will($this->returnValue($categoryId));
        $this->urlRewriteBuilder->expects($this->once())->method('setDescription')->with($description)
            ->will($this->returnSelf());
        $this->prepareUrlRewriteMock($storeId, $categoryId, $requestPath, $targetPath, 'code');

        $this->assertEquals(
            [$this->urlRewrite],
            $this->currentUrlRewritesRegenerator->generate($storeId, $this->category)
        );
    }

    /**
     * @param array $currentRewrites
     * @return array
     */
    protected function getCurrentRewritesMocks($currentRewrites)
    {
        $rewrites = [];
        foreach ($currentRewrites as $urlRewrite) {
            /** @var \PHPUnit_Framework_MockObject_MockObject */
            $url = $this->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\UrlRewrite')
                ->disableOriginalConstructor()->getMock();
            foreach ($urlRewrite as $key => $value) {
                $url->expects($this->any())
                    ->method('get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))))
                    ->will($this->returnValue($value));
            }
            $rewrites[] = $url;
        }
        return $rewrites;
    }

    /**
     * @param mixed $storeId
     * @param mixed $categoryId
     * @param mixed $requestPath
     * @param mixed $targetPath
     * @param mixed $redirectType
     */
    protected function prepareUrlRewriteMock($storeId, $categoryId, $requestPath, $targetPath, $redirectType)
    {
        $this->urlRewriteBuilder->expects($this->any())->method('setStoreId')->with($storeId)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setEntityId')->with($categoryId)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setEntityType')
            ->with(CategoryUrlRewriteGenerator::ENTITY_TYPE)->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setRequestPath')->with($requestPath)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setTargetPath')->with($targetPath)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setIsAutogenerated')->with(0)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setRedirectType')->with($redirectType)
            ->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('setMetadata')->with([])->will($this->returnSelf());
        $this->urlRewriteBuilder->expects($this->any())->method('create')->will($this->returnValue($this->urlRewrite));
    }
}
