<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\Testing\ClassExtensionTrait;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;

class ChainExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testCouldBeConstructedWithExtensionsArray()
    {
        new ChainExtension(
            [$this->createExtension(), $this->createExtension()],
            $this->createConsumerState()
        );
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onStart($context);
    }

    public function testShouldProxyOnBeforeReceiveToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onBeforeReceive($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onPreReceived($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions()
    {
        $context = $this->createContext();

        $fooExtension = $this->createExtension();
        $fooExtension->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context));
        $barExtension = $this->createExtension();
        $barExtension->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context));

        $consumerState = $this->createConsumerState();
        $consumerState->expects($this->at(0))
            ->method('setExtension')
            ->with($this->identicalTo($fooExtension));
        $consumerState->expects($this->at(1))
            ->method('setExtension')
            ->with($this->identicalTo($barExtension));
        $consumerState->expects($this->at(2))
            ->method('setExtension')
            ->with($this->isNull());

        $chainExtension = new ChainExtension([$fooExtension, $barExtension], $consumerState);
        $chainExtension->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContext()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsumerState
     */
    protected function createConsumerState()
    {
        return $this->createMock(ConsumerState::class);
    }
}
