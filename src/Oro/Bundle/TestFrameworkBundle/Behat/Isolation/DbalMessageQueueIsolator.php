<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class DbalMessageQueueIsolator implements IsolatorInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Process
     */
    private $process;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $command = sprintf(
            'php %s/console oro:message-queue:consume --env=%s',
            realpath($this->kernel->getRootDir()),
            $this->kernel->getEnvironment()
        );
        $this->process = new Process($command);
        $this->process->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'MessageQueueConsumer ERR > '.$buffer;
            }
        });
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        /** @var EntityManager $em */
        $em = $this->kernel->getContainer()->get('doctrine')->getManager();
        DbalMessageQueueIsolator::waitForMessageQueue($em->getConnection());

        $process = new Process(sprintf('pkill -f "%s/console oro:message-queue:consume"', $this->kernel->getRootDir()));

        try {
            $process->run();
        } catch (RuntimeException $e) {
            //it's ok
        }
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return 'dbal' === $container->getParameter('message_queue_transport');
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Dbal Message Queue';
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'message-queue';
    }

    /**
     * @param Connection $connection
     */
    public static function waitForMessageQueue(Connection $connection, $timeLimit = 60)
    {
        $time = $timeLimit;
        $result = $connection->executeQuery("SELECT * FROM oro_message_queue")->rowCount();

        while (0 !== $result) {
            if ($time <= 0) {
                throw new RuntimeException('Message Queue was not process messages during time limit');
            }

            $result = $connection->executeQuery("SELECT * FROM oro_message_queue")->rowCount();
            usleep(250000);
            $time -= 0.25;
        }
    }
}
