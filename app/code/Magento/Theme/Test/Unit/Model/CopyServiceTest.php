<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Theme\Test\Unit\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\View\Design\Theme\Customization;
use Magento\Framework\View\Design\Theme\Customization\Path;
use Magento\Framework\View\Design\Theme\FileFactory;
use Magento\Theme\Model\CopyService;
use Magento\Theme\Model\Theme;
use Magento\Theme\Model\Theme\File;
use Magento\Widget\Model\Layout\Link;
use Magento\Widget\Model\Layout\Update;
use Magento\Widget\Model\ResourceModel\Layout\Update\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CopyServiceTest extends TestCase
{
    /**#@+
     * @var \Magento\Theme\Model\CopyService
     */
    protected $object;

    /**
     * @var MockObject
     */
    protected $fileFactory;

    /**
     * @var MockObject
     */
    protected $filesystem;

    /**
     * @var MockObject
     */
    protected $sourceTheme;

    /**
     * @var MockObject
     */
    protected $targetTheme;

    /**
     * @var MockObject
     */
    protected $link;

    /**
     * @var MockObject
     */
    protected $linkCollection;

    /**
     * @var MockObject
     */
    protected $update;

    /**
     * @var MockObject
     */
    protected $updateCollection;

    /**
     * @var MockObject
     */
    protected $updateFactory;

    /**
     * @var MockObject
     */
    protected $customizationPath;

    /**
     * @var MockObject[]
     */
    protected $targetFiles = [];

    /**
     * @var MockObject[]
     */
    protected $sourceFiles = [];

    /**
     * @var MockObject
     */
    protected $dirWriteMock;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $sourceFileOne = $this->createPartialMock(File::class, ['__wakeup', 'delete']);
        $sourceFileOne->setData(
            [
                'file_path' => 'fixture_file_path_one',
                'file_type' => 'fixture_file_type_one',
                'content' => 'fixture_content_one',
                'sort_order' => 10,
            ]
        );
        $sourceFileTwo = $this->createPartialMock(File::class, ['__wakeup', 'delete']);
        $sourceFileTwo->setData(
            [
                'file_path' => 'fixture_file_path_two',
                'file_type' => 'fixture_file_type_two',
                'content' => 'fixture_content_two',
                'sort_order' => 20,
            ]
        );
        $this->sourceFiles = [$sourceFileOne, $sourceFileTwo];
        $this->sourceTheme = $this->createPartialMock(
            Theme::class,
            ['__wakeup', 'getCustomization']
        );

        $this->targetFiles = [
            $this->createPartialMock(File::class, ['__wakeup', 'delete']),
            $this->createPartialMock(File::class, ['__wakeup', 'delete']),
        ];
        $this->targetTheme = $this->createPartialMock(
            Theme::class,
            ['__wakeup', 'getCustomization']
        );
        $this->targetTheme->setId(123);

        $this->customizationPath = $this->createMock(Path::class);

        $this->fileFactory = $this->createPartialMock(
            FileFactory::class,
            ['create']
        );
        $this->filesystem =
            $this->createPartialMock(Filesystem::class, ['getDirectoryWrite']);
        $this->dirWriteMock = $this->createPartialMock(
            Write::class,
            ['isDirectory', 'search', 'copy', 'delete', 'read', 'copyFile', 'isExist']
        );
        $this->filesystem->expects(
            $this->any()
        )->method(
            'getDirectoryWrite'
        )->with(
            DirectoryList::MEDIA
        )->will(
            $this->returnValue($this->dirWriteMock)
        );

        /* Init \Magento\Widget\Model\ResourceModel\Layout\Update\Collection model  */
        $this->updateFactory = $this->createPartialMock(\Magento\Widget\Model\Layout\UpdateFactory::class, ['create']);
        $this->update = $this->createPartialMock(
            Update::class,
            ['__wakeup', 'getCollection']
        );
        $this->updateFactory->expects($this->at(0))->method('create')->will($this->returnValue($this->update));
        $this->updateCollection = $this->createPartialMock(
            Collection::class,
            ['addThemeFilter', 'delete', 'getIterator']
        );
        $this->update->expects(
            $this->any()
        )->method(
            'getCollection'
        )->will(
            $this->returnValue($this->updateCollection)
        );

        /* Init Link an Link_Collection model */
        $this->link = $this->createPartialMock(Link::class, ['__wakeup', 'getCollection']);
        $this->linkCollection = $this->createPartialMock(
            \Magento\Widget\Model\ResourceModel\Layout\Link\Collection::class,
            ['addThemeFilter', 'getIterator', 'addFieldToFilter']
        );
        $this->link->expects($this->any())->method('getCollection')->will($this->returnValue($this->linkCollection));

        $eventManager = $this->createPartialMock(ManagerInterface::class, ['dispatch']);

        $this->object = new CopyService(
            $this->filesystem,
            $this->fileFactory,
            $this->link,
            $this->updateFactory,
            $eventManager,
            $this->customizationPath
        );
    }

    protected function tearDown(): void
    {
        $this->object = null;
        $this->filesystem = null;
        $this->fileFactory = null;
        $this->sourceTheme = null;
        $this->targetTheme = null;
        $this->link = null;
        $this->linkCollection = null;
        $this->updateCollection = null;
        $this->updateFactory = null;
        $this->sourceFiles = [];
        $this->targetFiles = [];
    }

    /**
     * cover \Magento\Theme\Model\CopyService::_copyLayoutCustomization
     */
    public function testCopyLayoutUpdates()
    {
        $customization = $this->createPartialMock(
            Customization::class,
            ['getFiles']
        );
        $customization->expects($this->atLeastOnce())->method('getFiles')->will($this->returnValue([]));
        $this->sourceTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($customization)
        );
        $this->targetTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($customization)
        );

        $this->updateCollection->expects($this->once())->method('delete');
        $this->linkCollection->expects($this->once())->method('addThemeFilter');

        $targetLinkOne = $this->createPartialMock(
            Link::class,
            ['__wakeup', 'setId', 'setThemeId', 'save', 'setLayoutUpdateId']
        );
        $targetLinkOne->setData(['id' => 1, 'layout_update_id' => 1]);
        $targetLinkTwo = $this->createPartialMock(
            Link::class,
            ['__wakeup', 'setId', 'setThemeId', 'save', 'setLayoutUpdateId']
        );
        $targetLinkTwo->setData(['id' => 2, 'layout_update_id' => 2]);

        $targetLinkOne->expects($this->at(0))->method('setThemeId')->with(123);
        $targetLinkOne->expects($this->at(1))->method('setLayoutUpdateId')->with(1);
        $targetLinkOne->expects($this->at(2))->method('setId')->with(null);
        $targetLinkOne->expects($this->at(3))->method('save');

        $targetLinkTwo->expects($this->at(0))->method('setThemeId')->with(123);
        $targetLinkTwo->expects($this->at(1))->method('setLayoutUpdateId')->with(2);
        $targetLinkTwo->expects($this->at(2))->method('setId')->with(null);
        $targetLinkTwo->expects($this->at(3))->method('save');

        $linkReturnValues = $this->onConsecutiveCalls(new \ArrayIterator([$targetLinkOne, $targetLinkTwo]));
        $this->linkCollection->expects($this->any())->method('getIterator')->will($linkReturnValues);

        $targetUpdateOne = $this->createPartialMock(
            Update::class,
            ['__wakeup', 'setId', 'load', 'save']
        );
        $targetUpdateOne->setData(['id' => 1]);
        $targetUpdateTwo = $this->createPartialMock(
            Update::class,
            ['__wakeup', 'setId', 'load', 'save']
        );
        $targetUpdateTwo->setData(['id' => 2]);
        $updateReturnValues = $this->onConsecutiveCalls($this->update, $targetUpdateOne, $targetUpdateTwo);
        $this->updateFactory->expects($this->any())->method('create')->will($updateReturnValues);

        $this->object->copy($this->sourceTheme, $this->targetTheme);
    }

    /**
     * cover \Magento\Theme\Model\CopyService::_copyDatabaseCustomization
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCopyDatabaseCustomization()
    {
        $sourceCustom = $this->createPartialMock(
            Customization::class,
            ['getFiles']
        );
        $sourceCustom->expects(
            $this->atLeastOnce()
        )->method(
            'getFiles'
        )->will(
            $this->returnValue($this->sourceFiles)
        );
        $this->sourceTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($sourceCustom)
        );
        $targetCustom = $this->createPartialMock(
            Customization::class,
            ['getFiles']
        );
        $targetCustom->expects(
            $this->atLeastOnce()
        )->method(
            'getFiles'
        )->will(
            $this->returnValue($this->targetFiles)
        );
        $this->targetTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($targetCustom)
        );

        $this->linkCollection->expects(
            $this->any()
        )->method(
            'addFieldToFilter'
        )->will(
            $this->returnValue($this->linkCollection)
        );
        $this->linkCollection->expects(
            $this->any()
        )->method(
            'getIterator'
        )->will(
            $this->returnValue(new \ArrayIterator([]))
        );

        foreach ($this->targetFiles as $targetFile) {
            $targetFile->expects($this->once())->method('delete');
        }

        $newFileOne = $this->createPartialMock(File::class, ['__wakeup', 'setData', 'save']);
        $newFileTwo = $this->createPartialMock(File::class, ['__wakeup', 'setData', 'save']);
        $newFileOne->expects(
            $this->at(0)
        )->method(
            'setData'
        )->with(
            [
                'theme_id' => 123,
                'file_path' => 'fixture_file_path_one',
                'file_type' => 'fixture_file_type_one',
                'content' => 'fixture_content_one',
                'sort_order' => 10,
            ]
        );
        $newFileOne->expects($this->at(1))->method('save');
        $newFileTwo->expects(
            $this->at(0)
        )->method(
            'setData'
        )->with(
            [
                'theme_id' => 123,
                'file_path' => 'fixture_file_path_two',
                'file_type' => 'fixture_file_type_two',
                'content' => 'fixture_content_two',
                'sort_order' => 20,
            ]
        );
        $newFileTwo->expects($this->at(1))->method('save');
        $this->fileFactory->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            []
        )->will(
            $this->onConsecutiveCalls($newFileOne, $newFileTwo)
        );

        $this->object->copy($this->sourceTheme, $this->targetTheme);
    }

    /**
     * cover \Magento\Theme\Model\CopyService::_copyFilesystemCustomization
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCopyFilesystemCustomization()
    {
        $customization = $this->createPartialMock(
            Customization::class,
            ['getFiles']
        );
        $customization->expects($this->atLeastOnce())->method('getFiles')->will($this->returnValue([]));
        $this->sourceTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($customization)
        );
        $this->targetTheme->expects(
            $this->once()
        )->method(
            'getCustomization'
        )->will(
            $this->returnValue($customization)
        );

        $this->linkCollection->expects(
            $this->any()
        )->method(
            'addFieldToFilter'
        )->will(
            $this->returnValue($this->linkCollection)
        );
        $this->linkCollection->expects(
            $this->any()
        )->method(
            'getIterator'
        )->will(
            $this->returnValue(new \ArrayIterator([]))
        );

        $this->customizationPath->expects(
            $this->at(0)
        )->method(
            'getCustomizationPath'
        )->will(
            $this->returnValue('source/path')
        );

        $this->customizationPath->expects(
            $this->at(1)
        )->method(
            'getCustomizationPath'
        )->will(
            $this->returnValue('target/path')
        );

        $this->dirWriteMock->expects(
            $this->any()
        )->method(
            'isDirectory'
        )->will(
            $this->returnValueMap([['source/path', true], ['source/path/subdir', true]])
        );

        $this->dirWriteMock->expects(
            $this->any()
        )->method(
            'isExist'
        )->will(
            $this->returnValueMap(
                [
                    ['target/path', true]
                ]
            )
        );

        $this->dirWriteMock->expects(
            $this->any()
        )->method(
            'read'
        )->will(
            $this->returnValueMap(
                [
                    ['target/path', ['target/path/subdir']],
                    ['source/path', ['source/path/subdir']],
                    ['source/path/subdir', ['source/path/subdir/file_one.jpg', 'source/path/subdir/file_two.png']],
                ]
            )
        );

        $expectedCopyEvents = [
            ['source/path/subdir/file_one.jpg', 'target/path/subdir/file_one.jpg', null],
            ['source/path/subdir/file_two.png', 'target/path/subdir/file_two.png', null],
        ];
        $actualCopyEvents = [];
        $recordCopyEvent = function () use (&$actualCopyEvents) {
            $actualCopyEvents[] = func_get_args();
        };
        $this->dirWriteMock->expects($this->any())->method('copyFile')->will($this->returnCallback($recordCopyEvent));

        $this->object->copy($this->sourceTheme, $this->targetTheme);

        $this->assertEquals($expectedCopyEvents, $actualCopyEvents);
    }
}
