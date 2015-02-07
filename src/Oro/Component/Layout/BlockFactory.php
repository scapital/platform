<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\Type\ContainerType;

class BlockFactory implements BlockFactoryInterface
{
    /** @var ExtensionManagerInterface */
    protected $extensionManager;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var BlockOptionsResolver */
    protected $optionsResolver;

    /** @var BlockTypeHelperInterface */
    protected $typeHelper;

    /** @var RawLayout */
    protected $rawLayout;

    /** @var ContextInterface */
    protected $context;

    /** @var BlockBuilder */
    protected $blockBuilder;

    /** @var Block */
    protected $block;

    /**
     * @param ExtensionManagerInterface          $extensionManager
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     */
    public function __construct(
        ExtensionManagerInterface $extensionManager,
        DeferredLayoutManipulatorInterface $layoutManipulator
    ) {
        $this->extensionManager  = $extensionManager;
        $this->layoutManipulator = $layoutManipulator;
        $this->optionsResolver   = $this->createOptionsResolver();
        $this->typeHelper        = $this->createBlockTypeHelper();
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockView(RawLayout $rawLayout, ContextInterface $context, $rootId = null)
    {
        $this->initializeState($rawLayout, $context);
        try {
            $rootId = $rootId
                ? $this->rawLayout->resolveId($rootId)
                : $this->rawLayout->getRootId();

            $this->buildBlocks($rootId);
            $rootView = $this->buildBlockViews($rootId);

            $this->clearState();

            return $rootView;
        } catch (\Exception $e) {
            $this->clearState();
            throw $e;
        }
    }

    /**
     * Initializes the state of this factory
     *
     * @param RawLayout        $rawLayout
     * @param ContextInterface $context
     */
    protected function initializeState(RawLayout $rawLayout, ContextInterface $context)
    {
        $this->rawLayout    = $rawLayout;
        $this->context      = $context;
        $this->blockBuilder = $this->createBlockBuilder();
        $this->block        = $this->createBlock();
    }

    /**
     * Clears the state of this factory
     */
    protected function clearState()
    {
        $this->rawLayout    = null;
        $this->context      = null;
        $this->blockBuilder = null;
        $this->block        = null;
    }

    /**
     * Builds all blocks starting with and including the given root block
     *
     * @param string $rootId
     *
     * @throws Exception\LogicException if a child block is added to not container
     */
    protected function buildBlocks($rootId)
    {
        $this->layoutManipulator->resetCounters();

        // build the root block
        if (!$this->rawLayout->hasProperty($rootId, RawLayout::RESOLVED_OPTIONS, true)) {
            $this->buildBlock($rootId);
        }

        // build child blocks
        $iterator = $this->rawLayout->getHierarchyIterator($rootId);
        foreach ($iterator as $id) {
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS, true)) {
                // the block is already built
                continue;
            }
            if (!$this->isContainerBlock($iterator->getParent())) {
                $blockType = $this->rawLayout->getProperty($iterator->getParent(), RawLayout::BLOCK_TYPE, true);

                throw new Exception\LogicException(
                    sprintf(
                        'The "%s" item cannot be added as a child to "%s" item (block type: %s) '
                        . 'because only container blocks can have children.',
                        $id,
                        $iterator->getParent(),
                        $blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType
                    )
                );
            }
            $this->buildBlock($id);
        }

        // apply layout changes were made by built blocks and build newly added blocks
        $this->layoutManipulator->applyChanges($this->context);
        if ($this->layoutManipulator->getNumberOfAddedItems() !== 0) {
            $this->buildBlocks($rootId);
        }
    }

    /**
     * Builds views for all blocks starting with and including the given root block
     *
     * @param string $rootId
     *
     * @return BlockView The root block view
     */
    protected function buildBlockViews($rootId)
    {
        /** @var BlockView[] $views */
        $views = [];

        // build the root view
        $rootView       = $this->buildBlockView($rootId);
        $views[$rootId] = $rootView;
        // build child views
        $iterator = $this->rawLayout->getHierarchyIterator($rootId);
        foreach ($iterator as $id) {
            $parentView = $views[$iterator->getParent()];

            // build child view
            $view                      = $this->buildBlockView($id, $parentView);
            $parentView->children[$id] = $view;
            $views[$id]                = $view;
        }

        // finish the root view
        $this->finishBlockView($rootView, $rootId);
        // finish child views
        foreach ($iterator as $id) {
            $this->finishBlockView($views[$id], $id);
        }

        return $rootView;
    }

    /**
     * Builds the block
     *
     * @param string $id
     */
    protected function buildBlock($id)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::OPTIONS, true);
        $types     = $this->typeHelper->getBlockTypes($blockType);

        // resolve options
        $resolvedOptions = $this->optionsResolver->resolveOptions($blockType, $options);
        $this->rawLayout->setProperty($id, RawLayout::RESOLVED_OPTIONS, $resolvedOptions);

        // point the block builder state to the current block
        $this->blockBuilder->initialize($id);
        // iterate from parent to current
        foreach ($types as $type) {
            $type->buildBlock($this->blockBuilder, $resolvedOptions);
            $this->extensionManager->buildBlock($type->getName(), $this->blockBuilder, $resolvedOptions);
        }
    }

    /**
     * Created and builds the block view
     *
     * @param string         $id
     * @param BlockView|null $parentView
     *
     * @return BlockView
     */
    protected function buildBlockView($id, BlockView $parentView = null)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::RESOLVED_OPTIONS, true);
        $types     = $this->typeHelper->getBlockTypes($blockType);
        $view      = new BlockView($parentView);

        // point the block view state to the current block
        $this->block->initialize($id);
        // build the view
        foreach ($types as $type) {
            $type->buildView($view, $this->block, $options);
            $this->extensionManager->buildView($type->getName(), $view, $this->block, $options);
        }

        return $view;
    }

    /**
     * Finishes the building of the block view
     *
     * @param BlockView $view
     * @param string    $id
     */
    protected function finishBlockView(BlockView $view, $id)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::RESOLVED_OPTIONS, true);
        $types     = $this->typeHelper->getBlockTypes($blockType);

        // point the block view state to the current block
        $this->block->initialize($id);
        // finish the view
        foreach ($types as $type) {
            $type->finishView($view, $this->block, $options);
            $this->extensionManager->finishView($type->getName(), $view, $this->block, $options);
        }
    }

    /**
     * Creates new instance of the option resolver
     *
     * @return BlockOptionsResolver
     */
    protected function createOptionsResolver()
    {
        return new BlockOptionsResolver($this->extensionManager);
    }

    /**
     * Creates new instance of the block type hierarchy registry
     *
     * @return BlockTypeHelperInterface
     */
    protected function createBlockTypeHelper()
    {
        return new BlockTypeHierarchyRegistry($this->extensionManager);
    }

    /**
     * Creates new instance of the block builder
     *
     * @return BlockBuilder
     */
    protected function createBlockBuilder()
    {
        return new BlockBuilder($this->layoutManipulator, $this->rawLayout, $this->typeHelper, $this->context);
    }

    /**
     * Creates new instance of the block
     *
     * @return Block
     */
    protected function createBlock()
    {
        return new Block($this->rawLayout, $this->typeHelper, $this->context);
    }

    /**
     * Checks whether the given block is a container for other blocks
     *
     * @param string $id
     *
     * @return bool
     */
    protected function isContainerBlock($id)
    {
        return $this->typeHelper->isInstanceOf(
            $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true),
            ContainerType::NAME
        );
    }
}
